<?php
/**
 * Enhanced Process Enrollment with Excess Payment Handling
 * CarePro POS System - Complete Implementation
 * User: Scraper001 | Time: 2025-06-21 09:18:55
 */
include '../../../connection/connection.php';
$conn = con();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
$student_id = intval($_POST['student_id'] ?? 0);
$program_id = intval($_POST['program_id'] ?? 0);
$type_of_payment = $_POST['type_of_payment'] ?? '';
// Block demo payment if no initial_payment for this student/program
if ($type_of_payment === 'demo_payment') {
    $checkInit = $conn->query("SELECT id FROM pos_transactions WHERE student_id='$student_id' AND program_id='$program_id' AND payment_type='initial_payment' LIMIT 1");
    if ($checkInit->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Initial Payment is required before any Demo Payment can be processed."
        ]);
        exit;
    }
}
// Enhanced error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * Safe float conversion with validation
 */
function safe_float($value)
{
    if (is_null($value) || $value === '')
        return 0.0;
    return floatval($value);
}

function isDemoFullyPaid($conn, $student_id, $program_id, $demo_type, $required_amount)
{
    $stmt = $conn->prepare("
SELECT COALESCE(SUM(cash_received), 0) as total_paid
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND payment_type = 'demo_payment'
AND demo_type = ?
");
    $stmt->bind_param("iis", $student_id, $program_id, $demo_type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_paid = floatval($result['total_paid'] ?? 0);
    return $total_paid >= ($required_amount - 0.01); // Allow for small rounding differences
}

function getRemainingDemoAmount($conn, $student_id, $program_id, $demo_type, $required_amount)
{
    $stmt = $conn->prepare("
SELECT COALESCE(SUM(cash_received), 0) as total_paid
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND payment_type = 'demo_payment'
AND demo_type = ?
");
    $stmt->bind_param("iis", $student_id, $program_id, $demo_type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_paid = floatval($result['total_paid'] ?? 0);
    return max(0, $required_amount - $total_paid);
}
function calculateCorrectRemainingBalance($conn, $student_id, $program_id)
{
    // Get program total cost
    $program_stmt = $conn->prepare("SELECT total_tuition FROM program WHERE id = ?");
    $program_stmt->bind_param("i", $program_id);
    $program_stmt->execute();
    $program_result = $program_stmt->get_result()->fetch_assoc();
    $total_tuition = floatval($program_result['total_tuition'] ?? 0);
    $program_stmt->close();
    // Get any promo discount applied
    $promo_stmt = $conn->prepare("
SELECT promo_discount
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
AND promo_discount > 0
ORDER BY id ASC LIMIT 1
");
    $promo_stmt->bind_param("ii", $student_id, $program_id);
    $promo_stmt->execute();
    $promo_result = $promo_stmt->get_result()->fetch_assoc();
    $promo_discount = floatval($promo_result['promo_discount'] ?? 0);
    $promo_stmt->close();
    // Sum ALL payments made by the student
    $payments_stmt = $conn->prepare("
SELECT SUM(cash_received) as total_paid
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
AND payment_type IN ('initial_payment', 'demo_payment', 'full_payment')
");
    $payments_stmt->bind_param("ii", $student_id, $program_id);
    $payments_stmt->execute();
    $payments_result = $payments_stmt->get_result()->fetch_assoc();
    $total_paid = floatval($payments_result['total_paid'] ?? 0);
    $payments_stmt->close();
    // Calculate actual remaining balance
    $adjusted_total = $total_tuition - $promo_discount;
    $remaining_balance = max(0, $adjusted_total - $total_paid);
    // Log the calculation
    error_log(sprintf(
        "[%s] Balance Recalculation - User: %s\n
        Student ID: %s\n
        Program ID: %s\n
        Total Tuition: ₱%s\n
        Promo Discount: ₱%s\n
        Adjusted Total: ₱%s\n
        Total Payments: ₱%s\n
        Calculated Remaining: ₱%s",
        date('Y-m-d H:i:s'),
        'Scraper001',
        $student_id,
        $program_id,
        number_format($total_tuition, 2),
        number_format($promo_discount, 2),
        number_format($adjusted_total, 2),
        number_format($total_paid, 2),
        number_format($remaining_balance, 2)
    ));
    return $remaining_balance;
}

/**
 * Debug logging function
 */
function debug_log($message, $data = null)
{
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . ($data ? " Data: " . json_encode($data) : ""));
}
/**
 * ENHANCED: Excess Payment Handler for Initial Payments
 */
/**
 * Get the ACTUAL current balance for a student in a program
 * This fixes the calculation issues by directly summing all transactions
 */
function getActualBalance($conn, $student_id, $program_id)
{
    // First, get the program total cost
    $program_stmt = $conn->prepare("SELECT total_tuition FROM program WHERE id = ?");
    $program_stmt->bind_param("i", $program_id);
    $program_stmt->execute();
    $program_result = $program_stmt->get_result()->fetch_assoc();
    $total_tuition = floatval($program_result['total_tuition'] ?? 0);
    // Get any promo discount applied
    $promo_stmt = $conn->prepare("
SELECT promo_discount
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
AND promo_discount > 0
ORDER BY id ASC LIMIT 1
");
    $promo_stmt->bind_param("ii", $student_id, $program_id);
    $promo_stmt->execute();
    $promo_result = $promo_stmt->get_result()->fetch_assoc();
    $promo_discount = floatval($promo_result['promo_discount'] ?? 0);
    // Get total of ALL payments made (initial, demos, etc.)
    $payments_stmt = $conn->prepare("
SELECT COALESCE(SUM(cash_received), 0) as total_payments
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
AND payment_type IN ('initial_payment', 'demo_payment', 'full_payment')
");
    $payments_stmt->bind_param("ii", $student_id, $program_id);
    $payments_stmt->execute();
    $payments_result = $payments_stmt->get_result()->fetch_assoc();
    $total_payments = floatval($payments_result['total_payments'] ?? 0);
    // Calculate actual remaining balance
    $adjusted_tuition = $total_tuition - $promo_discount;
    $actual_balance = $adjusted_tuition - $total_payments;
    // Log the calculation for debugging
    error_log(sprintf(
        "[%s] ACTUAL Balance Calculation - User: %s
        Student ID: %s
        Program ID: %s
        Total Tuition: %s
        Promo Discount: %s
        Adjusted Tuition: %s
        Total Payments: %s
        Actual Balance: %s",
        date('Y-m-d H:i:s'),
        'Scraper001',
        $student_id,
        $program_id,
        number_format($total_tuition, 2),
        number_format($promo_discount, 2),
        number_format($adjusted_tuition, 2),
        number_format($total_payments, 2),
        number_format($actual_balance, 2)
    ));
    return max(0, $actual_balance);
}

/**
 * Get detailed demo payments information for a student
 * This function properly calculates demo fees accounting for partial payments
 */
function getDetailedDemoPayments($conn, $student_id, $program_id)
{
    // Get program total tuition
    $program_stmt = $conn->prepare("SELECT total_tuition FROM program WHERE id = ?");
    $program_stmt->bind_param("i", $program_id);
    $program_stmt->execute();
    $program_result = $program_stmt->get_result()->fetch_assoc();
    $total_tuition = safe_float($program_result['total_tuition'] ?? 0);
    // Get initial payment amount
    $initial_stmt = $conn->prepare("
SELECT SUM(cash_received) as initial_pay
FROM pos_transactions
WHERE student_id = ? AND program_id = ? AND payment_type = 'initial_payment'
");
    $initial_stmt->bind_param("ii", $student_id, $program_id);
    $initial_stmt->execute();
    $initial_result = $initial_stmt->get_result()->fetch_assoc();
    $initial_payment = safe_float($initial_result['initial_pay'] ?? 0);
    // Get promo discount
    $promo_stmt = $conn->prepare("
SELECT promo_discount
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
AND promo_discount > 0
ORDER BY id ASC LIMIT 1
");
    $promo_stmt->bind_param("ii", $student_id, $program_id);
    $promo_stmt->execute();
    $promo_result = $promo_stmt->get_result()->fetch_assoc();
    $promo_discount = safe_float($promo_result['promo_discount'] ?? 0);
    // Calculate adjusted tuition after promo
    $adjusted_tuition = $total_tuition - $promo_discount;
    // Calculate total remaining amount after initial payment
    $total_remaining = $adjusted_tuition - $initial_payment;
    // Calculate default demo fee (original division of remaining amount into 4 equal parts)
    $default_demo_fee = $total_remaining / 4;
    // Get detailed demo payments
    $demo_query = $conn->prepare("
SELECT
pt.demo_type,
SUM(pt.cash_received) as total_paid,
COUNT(*) as payment_count,
MAX(pt.transaction_date) as last_payment_date
FROM pos_transactions pt
WHERE pt.student_id = ?
AND pt.program_id = ?
AND pt.payment_type = 'demo_payment'
GROUP BY pt.demo_type
ORDER BY pt.demo_type
");
    $demo_query->bind_param("ii", $student_id, $program_id);
    $demo_query->execute();
    $demo_result = $demo_query->get_result();
    $demo_details = [];
    $total_paid_for_demos = 0;
    // Process demo payment details
    while ($row = $demo_result->fetch_assoc()) {
        $demo_type = $row['demo_type'];
        $total_paid = safe_float($row['total_paid']);
        $total_paid_for_demos += $total_paid;
        // Check if demo is fully paid (within 1 cent tolerance for rounding)
        $is_fully_paid = ($total_paid >= ($default_demo_fee - 0.01));
        $demo_details[$demo_type] = [
            'paid_amount' => $total_paid,
            'required_amount' => $default_demo_fee,
            'balance' => $is_fully_paid ? 0 : max(0, $default_demo_fee - $total_paid),
            'status' => $is_fully_paid ? 'paid' : ($total_paid > 0 ? 'partial' : 'unpaid'),
            'last_payment' => $row['last_payment_date'],
            'payment_count' => intval($row['payment_count'])
        ];
    }
    // Count paid demos
    $paid_demos = array_filter($demo_details, function ($detail) {
        return $detail['status'] === 'paid';
    });
    $paid_demos_count = count($paid_demos);
    $remaining_demos_count = 4 - $paid_demos_count;
    // Calculate actual remaining balance - total balance minus what's already been paid
    $total_paid = $initial_payment + $total_paid_for_demos;
    $current_balance = max(0, $adjusted_tuition - $total_paid);
    // Recalculate demo fees for unpaid demos - divide by 4 to keep fees consistent
    $recalculated_demo_fee = $current_balance / 4;
    // Fill in unpaid demos with recalculated fees
    foreach (['demo1', 'demo2', 'demo3', 'demo4'] as $demo) {
        if (!isset($demo_details[$demo])) {
            $demo_details[$demo] = [
                'paid_amount' => 0,
                'required_amount' => $recalculated_demo_fee,
                'balance' => $recalculated_demo_fee,
                'status' => 'unpaid',
                'last_payment' => null,
                'payment_count' => 0
            ];
        }
    }
    return [
        'demo_details' => $demo_details,
        'total_tuition' => $total_tuition,
        'initial_payment' => $initial_payment,
        'total_remaining' => $total_remaining,
        'default_demo_fee' => $default_demo_fee,
        'recalculated_demo_fee' => $recalculated_demo_fee,
        'paid_demos_count' => $paid_demos_count,
        'remaining_demos_count' => $remaining_demos_count,
        'total_paid_for_demos' => $total_paid_for_demos,
        'current_balance' => $current_balance,
        'promo_discount' => $promo_discount,
        'adjusted_tuition' => $adjusted_tuition
    ];
}

function handleExcessInitialPayment($conn, $student_id, $program_id, $excess_option, $original_amount, $required_amount, $excess_amount, $total_tuition, $start_balance)
{
    $result = [
        'processed_amount' => $required_amount,
        'change_amount' => $excess_amount,
        'description' => '',
        'demo_allocations' => [],
        'final_balance' => null
    ];

    switch ($excess_option) {
        case 'treat_as_full':
            // [existing code]
            break;

        case 'allocate_to_demos':
            // Get reservation payment - ADD THIS
            $reservation_query = $conn->prepare("SELECT SUM(cash_received) as total_paid FROM pos_transactions WHERE student_id = ? AND program_id = ? AND payment_type = 'reservation'");
            $reservation_query->bind_param("ii", $student_id, $program_id);
            $reservation_query->execute();
            $reservation_result = $reservation_query->get_result()->fetch_assoc();
            $reservation_payment = floatval($reservation_result['total_paid'] ?? 0);
            $reservation_query->close();

            // Get promo discount - ADD THIS
            $promo_query = $conn->prepare("SELECT promo_discount FROM pos_transactions WHERE student_id = ? AND program_id = ? AND promo_discount > 0 ORDER BY id ASC LIMIT 1");
            $promo_query->bind_param("ii", $student_id, $program_id);
            $promo_query->execute();
            $promo_result = $promo_query->get_result()->fetch_assoc();
            $promo_discount = floatval($promo_result['promo_discount'] ?? 0);
            $promo_query->close();

            // FIXED: Calculate base demo fee correctly accounting for ALL upfront payments
            $base_demo_fee = ($total_tuition - $required_amount - $reservation_payment - $promo_discount) / 4;

            // Log the calculation for debugging
            error_log(sprintf(
                "[%s] Demo Fee Calculation - User: %s\n" .
                "Total: ₱%s | Initial: ₱%s | Reservation: ₱%s | Promo: ₱%s | Base Fee: ₱%s",
                "2025-08-05 11:31:45",
                "Scraper001",
                number_format($total_tuition, 2),
                number_format($required_amount, 2),
                number_format($reservation_payment, 2),
                number_format($promo_discount, 2),
                number_format($base_demo_fee, 2)
            ));

            $remaining_excess = $excess_amount;
            $demo_allocations = [];

            // [rest of existing allocation code]

            break;

        case 'return_as_change':
        default:
            // [existing code]
            break;
    }

    return $result;
}
/**
 * ENHANCED: Excess Payment Handler for Demo Payments
 */


//this is the libagger


function handleExcessDemoPayment($conn, $student_id, $program_id, $excess_option, $original_amount, $required_amount, $excess_amount, $current_demo)
{
    $result = [
        'processed_amount' => $required_amount,
        'change_amount' => $excess_amount,
        'description' => '',
        'demo_allocations' => [],
        'final_balance' => null
    ];

    // Get demo order
    $demos = ['demo1', 'demo2', 'demo3', 'demo4'];
    $current_index = array_search($current_demo, $demos);
    if ($current_index === false)
        $current_index = 0;

    switch ($excess_option) {
        case 'allocate_to_next_demo':
            $remaining_excess = $excess_amount;
            $demo_allocations = [];
            // Get base demo fee using your calculateDemoFee logic
            $base_demo_fee = calculateDemoFee($conn, $student_id, $program_id);

            // Get already paid amounts for each demo
            $existing_demos = [];
            $stmt = $conn->prepare("SELECT demo_type, SUM(cash_received) as paid FROM pos_transactions WHERE student_id = ? AND program_id = ? AND payment_type = 'demo_payment' GROUP BY demo_type");
            $stmt->bind_param("ii", $student_id, $program_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $existing_demos[$row['demo_type']] = safe_float($row['paid']);
            }
            $stmt->close();

            // Start allocation with next demo after the current one
            $running_balance = getActualBalance($conn, $student_id, $program_id) - $required_amount;
            for ($i = $current_index + 1; $i < count($demos); $i++) {
                if ($remaining_excess <= 0)
                    break;
                $demo_type = $demos[$i];
                $already_paid = isset($existing_demos[$demo_type]) ? $existing_demos[$demo_type] : 0.0;
                $demo_remaining = $base_demo_fee - $already_paid;
                error_log("[DEBUG] EXCESS ALLOC | allocating to $demo_type | already_paid: $already_paid | demo_remaining: $demo_remaining | remaining_excess: $remaining_excess");
                if ($demo_remaining > 0) {
                    $allocation = min($remaining_excess, $demo_remaining);
                    if ($allocation > 0) {
                        $running_balance -= $allocation;
                        $stmt = $conn->prepare(
                            "INSERT INTO pos_transactions
                (student_id, program_id, payment_type, demo_type, cash_received, balance, description, status, processed_by, transaction_date)
                VALUES (?, ?, 'demo_payment', ?, ?, ?, 'Excess allocation from previous demo payment', 'Active', 'Scraper001', NOW())"
                        );
                        $stmt->bind_param("iisdd", $student_id, $program_id, $demo_type, $allocation, $running_balance);
                        $stmt->execute();
                        $stmt->close();
                        $remaining_excess -= $allocation;
                        error_log("[DEBUG] EXCESS ALLOC | inserted $allocation to $demo_type | running_balance: $running_balance | remaining_excess now: $remaining_excess");
                    }
                }
            }
            $result = [
                'processed_amount' => $required_amount,
                'change_amount' => $remaining_excess,
                'description' => "Excess demo payment allocated to subsequent demos.",
                'demo_allocations' => $demo_allocations,
                'final_balance' => $running_balance
            ];
            break;

        case 'credit_to_account':
            // Credit the excess to the student's account as a 'credit' transaction
            $stmt = $conn->prepare(
                "INSERT INTO pos_transactions
            (student_id, program_id, payment_type, demo_type, cash_received, balance, description, status, processed_by, transaction_date)
            VALUES (?, ?, 'credit', NULL, ?, ?, 'Excess demo payment credited to account', 'Active', 'Scraper001', NOW())"
            );
            $running_balance = getActualBalance($conn, $student_id, $program_id) - $required_amount;
            $credit_balance = $running_balance; // you can update this logic if you want credit to reduce the balance
            $stmt->bind_param("iidd", $student_id, $program_id, $excess_amount, $credit_balance);
            $stmt->execute();
            $stmt->close();
            $result = [
                'processed_amount' => $required_amount,
                'change_amount' => 0,
                'description' => "Excess demo payment credited to student account.",
                'demo_allocations' => [],
                'final_balance' => $credit_balance
            ];
            break;

        case 'return_as_change':
        default:
            // Just return the excess as change
            $result = [
                'processed_amount' => $required_amount,
                'change_amount' => $excess_amount,
                'description' => "Excess demo payment (₱" . number_format($excess_amount, 2) . ") returned as change.",
                'demo_allocations' => [],
                'final_balance' => getActualBalance($conn, $student_id, $program_id) - $required_amount
            ];
            break;
    }


    return $result;
}


function getExistingDemoPayments($conn, $student_id, $program_id, $demo_type)
{
    $stmt = $conn->prepare("
SELECT
SUM(cash_received) as total_paid,
COUNT(*) as payment_count,
GROUP_CONCAT(id) as payment_ids,
MAX(transaction_date) as last_payment_date
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND payment_type = 'demo_payment'
AND demo_type = ?
");
    $stmt->bind_param("iis", $student_id, $program_id, $demo_type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return [
        'total_paid' => floatval($result['total_paid'] ?? 0),
        'payment_count' => intval($result['payment_count'] ?? 0),
        'payment_ids' => $result['payment_ids'] ?? '',
        'last_payment' => $result['last_payment_date'] ?? null
    ];
}

function calculateAndCheckDemoFee($conn, $student_id, $program_id, $demo_type)
{
    // Get program total cost and payments
    $stmt = $conn->prepare("
SELECT
p.total_tuition,
COALESCE((
SELECT SUM(cash_received)
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND payment_type = 'initial_payment'
), 0) as initial_payment,
COALESCE((
SELECT promo_discount
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND promo_discount > 0
ORDER BY id ASC LIMIT 1
), 0) as promo_discount
FROM program p
WHERE p.id = ?
");
    $stmt->bind_param("iiiii", $student_id, $program_id, $student_id, $program_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_tuition = floatval($result['total_tuition']);
    $initial_payment = floatval($result['initial_payment']);
    $promo_discount = floatval($result['promo_discount']);
    // Calculate base demo fee
    $remaining_balance = $total_tuition - ($promo_discount + $initial_payment);
    $demo_fee = $remaining_balance / 4;
    // Check for existing credits for this demo
    $credit_stmt = $conn->prepare("
SELECT
COALESCE(SUM(cash_received), 0) as paid_amount,
COALESCE(SUM(CASE WHEN payment_type = 'credit' THEN cash_received ELSE 0 END), 0) as credit_amount
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND (
(payment_type = 'demo_payment' AND demo_type = ?)
OR
(payment_type = 'credit' AND demo_type = ?)
)
");
    $credit_stmt->bind_param("iiss", $student_id, $program_id, $demo_type, $demo_type);
    $credit_stmt->execute();
    $credit_result = $credit_stmt->get_result()->fetch_assoc();
    $paid_amount = floatval($credit_result['paid_amount']);
    $credit_amount = floatval($credit_result['credit_amount']);
    return [
        'demo_fee' => $demo_fee,
        'paid_amount' => $paid_amount,
        'credit_amount' => $credit_amount,
        'remaining_amount' => max(0, $demo_fee - $paid_amount),
        'total_tuition' => $total_tuition,
        'initial_payment' => $initial_payment,
        'promo_discount' => $promo_discount,
        'has_credit' => $credit_amount > 0
    ];
}

function checkDuplicateDemoPayments($conn, $student_id, $program_id, $demo_type)
{
    $stmt = $conn->prepare("
SELECT
COUNT(*) as payment_count,
SUM(cash_received) as total_paid,
GROUP_CONCAT(id) as transaction_ids
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND payment_type = 'demo_payment'
AND demo_type = ?
");
    $stmt->bind_param("iis", $student_id, $program_id, $demo_type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return [
        'payment_count' => intval($result['payment_count']),
        'total_paid' => floatval($result['total_paid']),
        'transaction_ids' => $result['transaction_ids']
    ];
}
function getCurrentBalance($conn, $student_id, $program_id)
{
    $stmt = $conn->prepare("
SELECT balance
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
ORDER BY id DESC LIMIT 1
");
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return floatval($result['balance'] ?? 0);
}
/**
 * Find next unpaid demo
 */
function findNextUnpaidDemo($conn, $student_id, $program_id, $current_demo)
{
    $demos = ['demo1', 'demo2', 'demo3', 'demo4'];
    $current_index = array_search($current_demo, $demos);
    if ($current_index === false)
        return null;
    for ($i = $current_index + 1; $i < count($demos); $i++) {
        $check_demo = $demos[$i];
        // Check if demo is unpaid
        $stmt = $conn->prepare("SELECT id FROM pos_transactions WHERE student_id = ? AND program_id = ? AND demo_type = ?");
        $stmt->bind_param("iis", $student_id, $program_id, $check_demo);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            return $check_demo;
        }
    }
    return null;
}
/**
 * Check if program has ended or is ending soon
 */
function checkProgramEndDate($conn, $program_id)
{
    $stmt = $conn->prepare("SELECT program_name, end_date FROM program WHERE id = ?");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if (!$result) {
        return ['status' => 'NOT_FOUND', 'message' => 'Program not found'];
    }
    if (!$result['end_date']) {
        return ['status' => 'ACTIVE', 'message' => 'No end date set'];
    }
    $end_date = new DateTime($result['end_date']);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($end_date < $today) {
        return [
            'status' => 'ENDED',
            'message' => 'ENROLLMENT BLOCKED: Program "' . $result['program_name'] . '" has ended on ' . $end_date->format('Y-m-d'),
            'program_name' => $result['program_name'],
            'end_date' => $result['end_date']
        ];
    }
    if ($end_date->format('Y-m-d') === $today->format('Y-m-d')) {
        return [
            'status' => 'ENDING_TODAY',
            'message' => 'WARNING: Program "' . $result['program_name'] . '" ends today!',
            'program_name' => $result['program_name'],
            'end_date' => $result['end_date']
        ];
    }
    $days_until_end = $today->diff($end_date)->days;
    if ($days_until_end <= 7) {
        return [
            'status' => 'ENDING_SOON',
            'message' => 'Notice: Program "' . $result['program_name'] . '" ends in ' . $days_until_end . ' days',
            'program_name' => $result['program_name'],
            'end_date' => $result['end_date'],
            'days_remaining' => $days_until_end
        ];
    }
    return ['status' => 'ACTIVE', 'message' => 'Program is active'];
}
/**
 * Calculate demo fee for a student - FIXED to use actual balance
 */
function calculateDemoFee($conn, $student_id, $program_id, $final_total = null)
{
    try {
        // Get actual remaining balance first
        $actual_balance = getActualBalance($conn, $student_id, $program_id);
        // Get paid demos info
        $demo_stmt = $conn->prepare("
SELECT
COUNT(DISTINCT CASE WHEN payment_type = 'demo_payment' AND cash_received > 0 THEN demo_type END) as paid_demos_count,
GROUP_CONCAT(DISTINCT CASE WHEN payment_type = 'demo_payment' AND cash_received > 0 THEN demo_type END) as paid_demos
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
");
        if (!$demo_stmt) {
            throw new Exception("Failed to prepare demo count query: " . $conn->error);
        }
        $demo_stmt->bind_param("ii", $student_id, $program_id);
        $demo_stmt->execute();
        $demo_result = $demo_stmt->get_result()->fetch_assoc();
        $paid_demos_count = intval($demo_result['paid_demos_count'] ?? 0);
        $paid_demos = explode(',', $demo_result['paid_demos'] ?? '');
        $demo_stmt->close();
        // Get full payment info
        $payment_stmt = $conn->prepare("
SELECT
demo_type,
SUM(cash_received) as total_paid
FROM pos_transactions
WHERE student_id = ?
AND program_id = ?
AND payment_type = 'demo_payment'
GROUP BY demo_type
");
        if (!$payment_stmt) {
            throw new Exception("Failed to prepare payment query: " . $conn->error);
        }
        $payment_stmt->bind_param("ii", $student_id, $program_id);
        $payment_stmt->execute();
        $payment_result = $payment_stmt->get_result();
        $demo_payments = [];
        while ($row = $payment_result->fetch_assoc()) {
            $demo_payments[$row['demo_type']] = floatval($row['total_paid']);
        }
        $payment_stmt->close();
        // Calculate demo fee based on actual remaining balance
        $remaining_demos = 4 - $paid_demos_count;
        $demo_fee = $remaining_demos > 0 ? ($actual_balance / 4) : 0;
        // Log calculation details
        error_log(sprintf(
            "[%s] Demo Fee Calculation - User: %s\n
Student ID: %s\n
Program ID: %s\n
Actual Balance: ₱%s\n
Paid Demos: %d (%s)\n
Remaining Demos: %d\n
Calculated Fee: ₱%s\n
Timestamp: %s",
            date('Y-m-d H:i:s'),
            'scrapper22',
            $student_id,
            $program_id,
            number_format($actual_balance, 2),
            $paid_demos_count,
            implode(', ', $paid_demos),
            $remaining_demos,
            number_format($demo_fee, 2),
            date('Y-m-d H:i:s')
        ));
        return $demo_fee;
    } catch (Exception $e) {
        error_log("Error in calculateDemoFee: " . $e->getMessage());
        throw $e;
    }
}
$current_time = date('Y-m-d H:i:s');
try {
    // Get POST data
    $student_id = $_POST['student_id'];
    $program_id = $_POST['program_id'];
    $learning_mode = $_POST['learning_mode'];
    $payment_type = $_POST['type_of_payment'];
    $package_name = $_POST['package_id'] ?? 'Regular';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;
    $payment_amount = safe_float($_POST['total_payment'] ?? 0);
    $cash_given = safe_float($_POST['cash'] ?? 0);
    // ENHANCED: Get excess payment data
    $has_excess_payment = isset($_POST['has_excess_payment']) && $_POST['has_excess_payment'] === 'true';
    $excess_choice = $_POST['excess_choice'] ?? null;
    $excess_amount = safe_float($_POST['excess_amount'] ?? 0);
    $excess_allocations = $_POST['excess_allocations'] ?? '[]';
    $original_payment_amount = safe_float($_POST['original_payment_amount'] ?? 0);
    $required_payment_amount = safe_float($_POST['required_payment_amount'] ?? 0);
    // In your main processing code:
    debug_log("💰 Excess Payment Data", [
        'has_excess' => $has_excess_payment,
        'choice' => $excess_choice,
        'excess_amount' => $excess_amount,
        'original_payment' => $original_payment_amount,
        'required_payment' => $required_payment_amount
    ]);
    // Real-time program end date validation
    $program_info = checkProgramEndDate($conn, $program_id);
    // Validate schedule selection for initial payments
    if ($payment_type === 'initial_payment') {
        $schedules_data = json_decode($selected_schedules, true);
        if (!is_array($schedules_data) || empty($schedules_data)) {
            // Check if there are maintained schedules from previous transaction
            $maintained_stmt = $conn->prepare("
SELECT selected_schedules
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
AND selected_schedules IS NOT NULL
AND selected_schedules != ''
AND selected_schedules != '[]'
ORDER BY id ASC LIMIT 1
");
            $maintained_stmt->bind_param("ii", $student_id, $program_id);
            $maintained_stmt->execute();
            $maintained_result = $maintained_stmt->get_result()->fetch_assoc();
            if (!$maintained_result || empty($maintained_result['selected_schedules'])) {
                throw new Exception("Schedule selection is required for initial payments");
            }
            debug_log("✅ Using maintained schedules for initial payment");
        }
    }
    // Get existing balance and payment info
    $balance_info_stmt = $conn->prepare("
SELECT subtotal, total_amount, balance, promo_discount,
learning_mode, package_name, selected_schedules, system_fee
FROM pos_transactions
WHERE student_id = ? AND program_id = ?
ORDER BY id DESC LIMIT 1
");
    $balance_info_stmt->bind_param("ii", $student_id, $program_id);
    $balance_info_stmt->execute();
    $balance_info_result = $balance_info_stmt->get_result();
    $current_balance = 0;
    $current_total = 0;
    $current_subtotal = 0;
    $existing_promo_discount = 0;
    $learning_mode_from_db = $learning_mode;
    $package_name_from_db = $package_name;
    $existing_selected_schedules = $selected_schedules;
    $system_fee = 0;
    if ($balance_info_result->num_rows > 0) {
        $balance_info = $balance_info_result->fetch_assoc();
        $current_balance = safe_float($balance_info['balance']);
        $current_total = safe_float($balance_info['total_amount']);
        $current_subtotal = safe_float($balance_info['subtotal']);
        $existing_promo_discount = safe_float($balance_info['promo_discount']);
        $learning_mode_from_db = $balance_info['learning_mode'];
        $package_name_from_db = $balance_info['package_name'];
        $existing_selected_schedules = $balance_info['selected_schedules'];
        $system_fee = safe_float($balance_info['system_fee']);
    }
    // Get payment amount from form
    $payment_amount = safe_float($_POST['total_payment'] ?? 0);
    $cash = safe_float($_POST['cash'] ?? 0);
    // Get computation values from POST
    $subtotal = safe_float($_POST['sub_total'] ?? 0);
    $final_total = safe_float($_POST['final_total'] ?? 0);
    $promo_discount = safe_float($_POST['promo_applied'] ?? 0);
    // Validate required values

    if ($final_total <= 0 && !$current_total) {
        throw new Exception("Invalid final total amount");
    }
    // Use existing values if this is a continuing enrollment
    if ($current_total > 0) {
        $final_total = $current_total;
        $subtotal = $current_subtotal;
        $promo_discount = $existing_promo_discount;
        $learning_mode = $learning_mode_from_db;
        $package_name = $package_name_from_db;
        $selected_schedules = $existing_selected_schedules;
    }
    // ENHANCED: Process excess payment if applicable
    $excess_processing_result = null;
    $final_payment_amount = $payment_amount;
    $final_change_amount = max(0, $cash - $payment_amount);
    // FIXED: Get the actual current balance before this payment
    $start_balance = getActualBalance($conn, $student_id, $program_id);
    if ($has_excess_payment && $excess_choice) {
        if ($payment_type === 'initial_payment') {
            $excess_processing_result = handleExcessInitialPayment(
                $conn,
                $student_id,
                $program_id,
                $excess_choice,
                $original_payment_amount,
                $required_payment_amount,
                $excess_amount,
                $final_total,
                $start_balance
            );
        } elseif ($payment_type === 'demo_payment') {
            $demo_fee = calculateDemoFee($conn, $student_id, $program_id);
            $existing = getExistingDemoPayments($conn, $student_id, $program_id, $demo_type);
            $already_paid = $existing['total_paid'];
            $remaining_needed = max(0, $demo_fee - $already_paid);

            $main_payment = min($payment_amount, $remaining_needed);
            $excess_payment = $payment_amount - $main_payment;

            error_log("[DEBUG] DEMO PAYMENT | demo: $demo_type | already_paid: $already_paid | needed: $remaining_needed | main_payment: $main_payment | excess: $excess_payment | demo_fee: $demo_fee");
            $conn->query("INSERT INTO pos_debug_log (event, context, data, processed_by)
    VALUES (MAIN_DEMO_PAYMENT', 'Calculating main/excess for $demo_type',
    '" . $conn->real_escape_string(json_encode([
                            'already_paid' => $already_paid,
                            'remaining_needed' => $remaining_needed,
                            'main_payment' => $main_payment,
                            'excess_payment' => $excess_payment,
                            'demo_fee' => $demo_fee
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "',
    'Scraper001')");
            if ($main_payment > 0) {
                $balance_after = getActualBalance($conn, $student_id, $program_id) - $main_payment;
                error_log("[DEBUG] INSERT MAIN | demo: $demo_type | main_payment: $main_payment | balance_after: $balance_after");
                $stmt = $conn->prepare(
                    "INSERT INTO pos_transactions
            (student_id, program_id, payment_type, demo_type, cash_received, balance, description, status, processed_by, transaction_date)
            VALUES (?, ?, 'demo_payment', ?, ?, ?, 'Demo payment', 'Active', 'Scraper001', NOW())"
                );
                $stmt->bind_param("iisdd", $student_id, $program_id, $demo_type, $main_payment, $balance_after);
                $stmt->execute();
                $stmt->close();
            } else {
                error_log("[DEBUG] SKIP MAIN | Nothing needed for $demo_type (already fully paid)");
            }

            if ($excess_payment > 0) {
                error_log("[DEBUG] CALLING EXCESS HANDLER | excess_payment: $excess_payment | starting at next demo after $demo_type");
                $excess_processing_result = handleExcessDemoPayment(
                    $conn,
                    $student_id,
                    $program_id,
                    $excess_choice,
                    $main_payment,        // original_amount (what was paid to this demo)
                    $remaining_needed,    // required_amount (what this demo needed)
                    $excess_payment,      // excess_amount (what's left to pass on)
                    $demo_type            // start allocating to next demo after this one
                );
            }
        }
        if ($excess_processing_result) {
            $final_payment_amount = $excess_processing_result['processed_amount'];
            $final_change_amount = max(0, ($cash - $final_payment_amount) + $excess_processing_result['change_amount']);
        }
    }
    // FIXED: Calculate what the balance will be after this payment
    $balance = max(0, $start_balance - $final_payment_amount);
    debug_log("💼 Balance Calculation - User: Scraper001", [
        'student_id' => $student_id,
        'program_id' => $program_id,
        'start_balance' => $start_balance,
        'payment_amount' => $final_payment_amount,
        'new_balance' => $balance,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    // If excess was processed and allocated, the final balance for the response is different
    $response_balance = $balance;
    if ($excess_processing_result && isset($excess_processing_result['final_balance']) && !is_null($excess_processing_result['final_balance'])) {
        $response_balance = $excess_processing_result['final_balance'];
    }
    // Validate payment
    if ($cash < $final_payment_amount) {
        throw new Exception("Insufficient cash payment. Required: ₱" . number_format($final_payment_amount, 2) . ", Received: ₱" . number_format($cash, 2));
    }
    $enrollment_status = in_array($payment_type, ['full_payment', 'demo_payment', 'initial_payment']) ? 'Enrolled' : 'Reserved';
    // Get system fee if online
    if ($learning_mode === 'Online' && !$system_fee) {
        $program_stmt = $conn->prepare("SELECT system_fee FROM program WHERE id = ?");
        $program_stmt->bind_param("i", $program_id);
        $program_stmt->execute();
        $program_result = $program_stmt->get_result()->fetch_assoc();
        $system_fee = safe_float($program_result['system_fee'] ?? 0);
    }
    // Create description with excess payment info
    $description = "Payment processed - " . ucfirst(str_replace('_', ' ', $payment_type));
    if ($excess_processing_result) {
        $description .= " (Excess: " . $excess_processing_result['description'] . ")";
    }
    // Start transaction
    $conn->begin_transaction();
    try {
        // ENHANCED: Program end date validation with blocking
        if ($program_info['status'] === 'ENDED') {
            throw new Exception($program_info['message']);
        }
        // FIXED: Insert POS transaction with correct field mapping
        $sql = "INSERT INTO pos_transactions (
student_id, program_id, learning_mode, package_name, payment_type,
demo_type, selected_schedules, subtotal, promo_discount, system_fee,
total_amount, cash_received, change_amount, balance, enrollment_status,
debit_amount, credit_amount, change_given, description, status,
processed_by, transaction_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 'Scraper001', NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $debit_amount = 0;
        $credit_amount = $final_payment_amount;
        if ($excess_choice == "allocate_to_demos") {
            $change_given = 0;
            $final_change_amount = 0;
        } else {
            $change_given = $final_change_amount;
        }
        $stmt->bind_param(
            "iisssssdddddddsddds",
            $student_id,
            $program_id,
            $learning_mode,
            $package_name,
            $payment_type,
            $demo_type,
            $selected_schedules,
            $subtotal,
            $promo_discount,
            $system_fee,
            $final_total,
            $final_payment_amount,
            $final_change_amount,
            $balance,
            $enrollment_status,
            $debit_amount,
            $credit_amount,
            $change_given,
            $description
        );
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert transaction: " . $stmt->error);
        }
        $transaction_id = $stmt->insert_id;
        // ENHANCED: Log excess payment processing if applicable
        if ($excess_processing_result && isset($excess_processing_result['demo_allocations'])) {
            $excess_data = [
                'choice' => $excess_choice,
                'excess_amount' => $excess_amount,
                'allocations' => $excess_processing_result['demo_allocations'],
                'description' => $excess_processing_result['description'],
                'processed_at' => $current_time,
                'processed_by' => 'Scraper001'
            ];
            // Check if excess_processing_log table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'excess_processing_log'");
            if ($table_check->num_rows > 0) {
                $log_stmt = $conn->prepare("
INSERT INTO excess_processing_log (
transaction_id, student_id, program_id, excess_amount,
excess_choice, allocations_data, final_change_amount,
processed_at, processed_by, notes
) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Scraper001', ?)
");
                $allocations_json = json_encode($excess_processing_result['demo_allocations']);
                $notes = $excess_processing_result['description'];
                $log_stmt->bind_param(
                    "iiiisdds",
                    $transaction_id,
                    $student_id,
                    $program_id,
                    $excess_amount,
                    $excess_choice,
                    $allocations_json,
                    $excess_processing_result['change_amount'],
                    $notes
                );
                $log_stmt->execute();
            }
        }
        // Insert/Update Student Enrollment
        $enrollment_check = $conn->prepare("SELECT id FROM student_enrollments WHERE student_id = ? AND program_id = ?");
        $enrollment_check->bind_param("ii", $student_id, $program_id);
        $enrollment_check->execute();
        $existing_enrollment = $enrollment_check->get_result()->fetch_assoc();
        if ($existing_enrollment) {
            // Update existing enrollment
            $update_enrollment = $conn->prepare("
UPDATE student_enrollments
SET pos_transaction_id = ?, status = ?, selected_schedules = ?, updated_at = NOW()
WHERE student_id = ? AND program_id = ?
");
            $update_enrollment->bind_param("issii", $transaction_id, $enrollment_status, $selected_schedules, $student_id, $program_id);
            $update_enrollment->execute();
        } else {
            // Insert new enrollment
            $enrollment_stmt = $conn->prepare("
INSERT INTO student_enrollments (
student_id, program_id, pos_transaction_id, status,
learning_mode, selected_schedules
) VALUES (?, ?, ?, ?, ?, ?)
");
            $enrollment_stmt->bind_param("iiisss", $student_id, $program_id, $transaction_id, $enrollment_status, $learning_mode, $selected_schedules);
            $enrollment_stmt->execute();
        }
        // Update student status
        $student_stmt = $conn->prepare("UPDATE student_info_tbl SET enrollment_status = ? WHERE id = ?");
        $student_stmt->bind_param("si", $enrollment_status, $student_id);
        $student_stmt->execute();
        $conn->commit();
        debug_log("✅ Transaction completed successfully", [
            'transaction_id' => $transaction_id,
            'student_id' => $student_id,
            'payment_type' => $payment_type,
            'amount' => $final_payment_amount,
            'balance' => $response_balance,
            'excess_handled' => $excess_choice ?? 'none'
        ]);
        // ENHANCED: Prepare response with excess payment information
        $response_message = 'Payment processed successfully';
        $warnings = [];
        if ($program_info['status'] === 'ENDING_TODAY') {
            $warnings[] = 'Program ends today (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
        } elseif (isset($program_info['days_remaining']) && $program_info['days_remaining'] <= 7) {
            $warnings[] = 'Program ends in ' . $program_info['days_remaining'] . ' days (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
        }
        if (!empty($warnings)) {
            $response_message .= ' - NOTE: ' . implode(', ', $warnings);
        }
        if ($excess_processing_result) {
            $response_message .= ' with excess payment handling (' . $excess_choice . ')';
        }
        // Calculate correct remaining balance after this transaction
        $corrected_balance = calculateCorrectRemainingBalance($conn, $student_id, $program_id);
        // Use the corrected balance in the response
        $response = [
            'success' => true,
            'message' => $response_message,
            'transaction_id' => $transaction_id,
            'enrollment_status' => $enrollment_status,
            'balance' => $corrected_balance, // Use corrected balance here
            'subtotal' => $subtotal,
            'promo_discount' => $promo_discount,
            'total_amount' => $final_total,
            'payment_amount' => $final_payment_amount,
            'cash_received' => $final_payment_amount,
            'total_cash_given' => $cash,
            'change_amount' => $final_change_amount,
            'credit_amount' => $credit_amount,
            'program_end_check' => [
                'status' => $program_info['status'],
                'end_date' => $program_info['end_date'] ?? null,
                'program_name' => $program_info['program_name'] ?? '',
                'days_remaining' => $program_info['days_remaining'] ?? null,
                'current_date' => date('Y-m-d')
            ],
            'timestamp' => $current_time,
            'processed_by' => 'Scraper001'
        ];
        // Add recalculation info to the response
        $response['balance_calculation'] = [
            'method' => 'recalculated',
            'original_balance' => $balance, // Keep the original for comparison
            'corrected_balance' => $corrected_balance,
            'recalculated_at' => date('Y-m-d H:i:s')
        ];
        // Add excess processing information to response
        if ($excess_processing_result) {
            $response['excess_processing'] = [
                'success' => true,
                'choice' => $excess_choice,
                'excess_amount' => $excess_amount,
                'final_change_amount' => $excess_processing_result['change_amount'],
                'processing_notes' => [$excess_processing_result['description']],
                'processed_at' => $current_time,
                'processed_by' => 'Scraper001'
            ];
            if (isset($excess_processing_result['demo_allocations'])) {
                $response['excess_processing']['allocations'] = $excess_processing_result['demo_allocations'];
            }
        }
        // FIXED: Add updated demo fees information to response
// Get the updated demo info after the balance correction
        $updated_demo_info = getDetailedDemoPayments($conn, $student_id, $program_id);
        $response['demo_fees'] = [
            'total_tuition' => $updated_demo_info['total_tuition'],
            'initial_payment' => $updated_demo_info['initial_payment'],
            'total_remaining' => $updated_demo_info['total_remaining'],
            'recalculated_demo_fee' => $updated_demo_info['recalculated_demo_fee'],
            'paid_demos_count' => $updated_demo_info['paid_demos_count'],
            'remaining_demos_count' => $updated_demo_info['remaining_demos_count'],
            'demo_details' => $updated_demo_info['demo_details']
        ];
        echo json_encode($response);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    debug_log("❌ Enrollment processing error", $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => $current_time,
        'processed_by' => 'Scraper001'
    ]);
}
$conn->close();
?>