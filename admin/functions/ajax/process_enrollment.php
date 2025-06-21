<?php
/**
 * CarePro Enhanced POS Payment Processing with Excess Handling
 * Author: Copilot for Scraper001
 */

include '../../../connection/connection.php';
$conn = con();

header('Content-Type: application/json');
error_reporting(E_ALL);

function respond($data)
{
    echo json_encode($data);
    exit;
}

function safe_float($val)
{
    if (is_null($val) || $val === '')
        return 0.0;
    return floatval($val);
}

// --- INPUTS ---
$student_id = intval($_POST['student_id'] ?? 0);
$program_id = intval($_POST['program_id'] ?? 0);
$payment_type = $_POST['type_of_payment'] ?? '';
$package_name = $_POST['package_id'] ?? 'Regular';
$demo_type = $_POST['demo_type'] ?? null;
$total_payment = safe_float($_POST['total_payment'] ?? 0);
$cash = safe_float($_POST['cash'] ?? 0);
$selected_schedules = $_POST['selected_schedules'] ?? null;
$sub_total = safe_float($_POST['sub_total'] ?? 0);
$final_total = safe_float($_POST['final_total'] ?? 0);
$promo_discount = safe_float($_POST['promo_applied'] ?? 0);
$learning_mode = $_POST['learning_mode'] ?? 'F2F';

// Excess handling
$has_excess = ($_POST['has_excess_payment'] ?? 'false') === 'true';
$excess_choice = $_POST['excess_choice'] ?? null;
$excess_amount = safe_float($_POST['excess_amount'] ?? 0);
$original_payment_amount = safe_float($_POST['original_payment_amount'] ?? 0);
$required_payment_amount = safe_float($_POST['required_payment_amount'] ?? 0);
$excess_demo_type = $_POST['excess_demo_type'] ?? null;

// --- HELPER TO FIND DEMO SESSIONS ---
function get_demo_sessions($program_id, $conn)
{
    // Assume always 4 demos, can be dynamic if needed
    return ['demo1', 'demo2', 'demo3', 'demo4'];
}

// --- EXCESS PAYMENT HANDLING ---
function handle_initial_excess($opt, $original, $required, $excess, $final_total, $conn, $student_id, $program_id)
{
    $result = [
        'processed_amount' => $required,
        'change_amount' => $excess,
        'description' => '',
        'demo_balances' => [],
    ];
    $demos = get_demo_sessions($program_id, $conn);

    if ($opt === 'treat_as_full') {
        // All payment treated as initial, recalc demo balances
        $remaining = max(0, $final_total - $original);
        $per_demo = $remaining / count($demos);
        $result['processed_amount'] = $original; // all as initial
        $result['change_amount'] = 0;
        $result['description'] = "Full payment credited to initial tuition. All demo session balances recalculated.";
        foreach ($demos as $d)
            $result['demo_balances'][$d] = $per_demo;
    } elseif ($opt === 'allocate_to_demos') {
        // Initial paid, excess allocated sequentially to demos
        $demo_fees = [];
        $remaining = $excess;
        // get original per-demo balance
        $per_demo = ($final_total - $required) / count($demos);
        foreach ($demos as $d) {
            if ($remaining <= 0) {
                $demo_fees[$d] = 0;
                continue;
            }
            $credit = min($remaining, $per_demo);
            $demo_fees[$d] = $credit;
            $remaining -= $credit;
        }
        $result['processed_amount'] = $original;
        $result['change_amount'] = max(0, $remaining);
        $result['description'] = "Excess allocated to demos in order. Per-demo allocation: " . json_encode($demo_fees);
        $result['demo_balances'] = $demo_fees;
    } else { // 'return_as_change'
        $result['processed_amount'] = $required;
        $result['change_amount'] = $excess;
        $result['description'] = "Excess returned as change.";
    }
    return $result;
}

function handle_demo_excess($opt, $original, $required, $excess, $student_id, $program_id, $demo_type, $conn)
{
    $result = [
        'processed_amount' => $required,
        'change_amount' => $excess,
        'description' => '',
        'allocation' => [],
    ];
    $demos = get_demo_sessions($program_id, $conn);
    if ($opt === 'add_to_next_demo') {
        // Find next unpaid/partially paid demo
        $next_demo = null;
        $found = false;
        foreach ($demos as $d) {
            if ($found) {
                $next_demo = $d;
                break;
            }
            if ($d === $demo_type)
                $found = true;
        }
        if ($next_demo) {
            // In a real system, credit the excess to the next demo's balance
            $result['processed_amount'] = $required;
            $result['change_amount'] = 0;
            $result['allocation'] = [$next_demo => $excess];
            $result['description'] = "Excess credited to next demo ($next_demo).";
        } else {
            $result['description'] = "No next demo to allocate, excess returned as change.";
        }
    } elseif ($opt === 'credit_to_account') {
        // Save excess as account credit (implement a field in student table if needed)
        // For now, just describe it
        $result['processed_amount'] = $required;
        $result['change_amount'] = 0;
        $result['allocation'] = ['account_credit' => $excess];
        $result['description'] = "Excess credited to student account.";
    } else { // 'return_as_change'
        $result['processed_amount'] = $required;
        $result['change_amount'] = $excess;
        $result['description'] = "Excess returned as change.";
    }
    return $result;
}

// --- MAIN PAYMENT LOGIC ---
try {
    if (!$student_id || !$program_id)
        throw new Exception("Missing student or program.");

    $demo_result = null;
    $change = max(0, $cash - $total_payment);
    $processed_amount = $total_payment;

    if ($has_excess && $excess_choice) {
        if ($payment_type === 'initial_payment') {
            $demo_result = handle_initial_excess($excess_choice, $original_payment_amount, $required_payment_amount, $excess_amount, $final_total, $conn, $student_id, $program_id);
        }
        if ($payment_type === 'demo_payment') {
            $demo_result = handle_demo_excess($excess_choice, $original_payment_amount, $required_payment_amount, $excess_amount, $student_id, $program_id, $excess_demo_type, $conn);
        }
        if ($demo_result) {
            $processed_amount = $demo_result['processed_amount'];
            $change = max(0, ($cash - $processed_amount) + $demo_result['change_amount']);
        }
    }

    // --- Save transaction in pos_transactions ---
    $sql = "INSERT INTO pos_transactions 
        (student_id, program_id, learning_mode, package_name, payment_type, demo_type, selected_schedules, subtotal, promo_discount, total_amount, cash_received, change_amount, balance, enrollment_status, description, processed_by, transaction_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    $enrollment_status = ($payment_type === 'reservation') ? 'Reserved' : 'Enrolled';
    $desc = "Payment processed ($payment_type)";
    if ($demo_result)
        $desc .= " [Excess: {$demo_result['description']}]";

    $balance = max(0, $final_total - $processed_amount);

    $stmt->bind_param(
        "iissssssddddddss",
        $student_id,
        $program_id,
        $learning_mode,
        $package_name,
        $payment_type,
        $demo_type,
        $selected_schedules,
        $sub_total,
        $promo_discount,
        $final_total,
        $processed_amount,
        $change,
        $balance,
        $enrollment_status,
        $desc,
        'Scraper001'
    );
    $stmt->execute();

    // TODO: Save/Update demo balances and/or account credit where necessary

    respond([
        'success' => true,
        'message' => 'Payment processed.',
        'payment_type' => $payment_type,
        'processed_amount' => $processed_amount,
        'change' => $change,
        'demo_result' => $demo_result,
        'enrollment_status' => $enrollment_status,
        'description' => $desc,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);

} catch (Exception $e) {
    respond([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>