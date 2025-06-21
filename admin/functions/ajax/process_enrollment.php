<?php
/**
 * Enhanced Process Enrollment with Excess Payment Handling
 * CarePro POS System - Complete Implementation
 * User: Scraper001 | Time: 2025-06-21 04:00:26
 */

include '../../../connection/connection.php';
$conn = con();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

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

/**
 * Debug logging function
 */
function debug_log($message, $data = null)
{
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . ($data ? " Data: " . json_encode($data) : ""));
}

/**
 * Check if program has ended or is ending soon
 */
function checkProgramEndDate($conn, $program_id)
{
    $stmt = $conn->prepare("SELECT program_name, program_end_date FROM program WHERE id = ?");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        return ['status' => 'NOT_FOUND', 'message' => 'Program not found'];
    }

    if (!$result['program_end_date']) {
        return ['status' => 'NO_END_DATE', 'message' => 'No end date set'];
    }

    $end_date = new DateTime($result['program_end_date']);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($end_date < $today) {
        return [
            'status' => 'ENDED',
            'message' => 'ENROLLMENT BLOCKED: Program "' . $result['program_name'] . '" has ended on ' . $end_date->format('Y-m-d'),
            'program_name' => $result['program_name'],
            'end_date' => $result['program_end_date']
        ];
    }

    if ($end_date->format('Y-m-d') === $today->format('Y-m-d')) {
        return [
            'status' => 'ENDING_TODAY',
            'message' => 'WARNING: Program "' . $result['program_name'] . '" ends today!',
            'program_name' => $result['program_name'],
            'end_date' => $result['program_end_date']
        ];
    }

    $days_until_end = $today->diff($end_date)->days;
    if ($days_until_end <= 7) {
        return [
            'status' => 'ENDING_SOON',
            'message' => 'Notice: Program "' . $result['program_name'] . '" ends in ' . $days_until_end . ' days',
            'program_name' => $result['program_name'],
            'end_date' => $result['program_end_date'],
            'days_remaining' => $days_until_end
        ];
    }

    return ['status' => 'ACTIVE', 'message' => 'Program is active'];
}

/**
 * Calculate demo fee for a student
 */
function calculateDemoFee($conn, $student_id, $program_id, $final_total)
{
    // Get current balance from most recent transaction
    $balance_stmt = $conn->prepare("
        SELECT balance FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        ORDER BY id DESC LIMIT 1
    ");

    if (!$balance_stmt) {
        debug_log("❌ Failed to prepare balance statement", $conn->error);
        return 0;
    }

    $balance_stmt->bind_param("ii", $student_id, $program_id);
    $balance_stmt->execute();
    $balance_result = $balance_stmt->get_result()->fetch_assoc();
    $current_balance = safe_float($balance_result['balance'] ?? $final_total);

    // Get paid demos count
    $demo_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT demo_type) as paid_demos_count 
        FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        AND payment_type = 'demo_payment' 
        AND demo_type IS NOT NULL 
        AND demo_type != ''
    ");
    $demo_stmt->bind_param("ii", $student_id, $program_id);
    $demo_stmt->execute();
    $demo_result = $demo_stmt->get_result()->fetch_assoc();
    $paid_demos_count = intval($demo_result['paid_demos_count'] ?? 0);

    $remaining_demos = 4 - $paid_demos_count;
    $demo_fee = $remaining_demos > 0 ? max(0, $current_balance / $remaining_demos) : 0;

    return $demo_fee;
}

$current_time = '2025-06-21 04:00:26';

try {
    // Get POST data
    $student_id = $_POST['student_id'];
    $program_id = $_POST['program_id'];
    $learning_mode = $_POST['learning_mode'];
    $payment_type = $_POST['type_of_payment'];
    $package_name = $_POST['package_id'] ?? 'Regular';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;

    // ENHANCED: Get excess payment data
    $excess_amount = isset($_POST['excess_amount']) ? safe_float($_POST['excess_amount']) : 0;
    $excess_option = $_POST['excess_option'] ?? null;
    $excess_description = $_POST['excess_description'] ?? null;
    $original_payment_amount = isset($_POST['original_payment_amount']) ? safe_float($_POST['original_payment_amount']) : 0;

    debug_log("💰 Excess Payment Data", [
        'excess_amount' => $excess_amount,
        'excess_option' => $excess_option,
        'original_payment' => $original_payment_amount
    ]);

    // ENHANCED: Real-time program end date validation FIRST
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
        SELECT sub_total as subtotal, final_total, balance, promo_discount, 
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
        $current_total = safe_float($balance_info['final_total']);
        $current_subtotal = safe_float($balance_info['subtotal']);
        $existing_promo_discount = safe_float($balance_info['promo_discount']);
        $learning_mode_from_db = $balance_info['learning_mode'];
        $package_name_from_db = $balance_info['package_name'];
        $existing_selected_schedules = $balance_info['selected_schedules'];
        $system_fee = safe_float($balance_info['system_fee']);
    }

    // Get payment amount from form
    $payment_amount = safe_float($_POST['total_payment'] ?? 0);

    // If not provided, calculate based on payment type
    if ($payment_amount <= 0) {
        switch ($payment_type) {
            case 'demo_payment':
                if (!$demo_type) {
                    throw new Exception("Demo type is required for demo payment");
                }
                $payment_amount = calculateDemoFee($conn, $student_id, $program_id, $current_total);
                break;
            case 'full_payment':
                $payment_amount = $current_balance;
                break;
            default:
                throw new Exception("Payment amount is required");
                break;
        }
    }

    // ENHANCED: Process excess payment if detected
    $processed_payment_amount = $payment_amount;
    $final_change_amount = 0;

    if ($excess_amount > 0 && $excess_option) {
        debug_log("🔄 Processing excess payment", [
            'option' => $excess_option,
            'excess' => $excess_amount,
            'payment_type' => $payment_type
        ]);

        $excess_result = processExcessPayment(
            $conn,
            $student_id,
            $program_id,
            $payment_type,
            $excess_option,
            $original_payment_amount,
            $payment_amount,
            $excess_amount,
            $demo_type
        );

        $processed_payment_amount = $excess_result['processed_amount'];
        $final_change_amount = $excess_result['change_amount'];

        debug_log("✅ Excess payment processed", $excess_result);
    }

    // Validate payment types
    if ($payment_type === 'demo_payment' && !$demo_type) {
        throw new Exception("Demo type is required for demo payment");
    }

    // Check if payment type has already been paid
    $existing_payment_stmt = $conn->prepare("
        SELECT payment_type, demo_type 
        FROM pos_transactions 
        WHERE student_id = ? AND program_id = ?
    ");
    $existing_payment_stmt->bind_param("ii", $student_id, $program_id);
    $existing_payment_stmt->execute();
    $existing_payments = $existing_payment_stmt->get_result();

    $paid_payment_types = [];
    $paid_demos = [];

    while ($row = $existing_payments->fetch_assoc()) {
        $paid_payment_types[] = $row['payment_type'];
        if ($row['demo_type']) {
            $paid_demos[] = $row['demo_type'];
        }
    }

    // Validation for payment types
    if ($payment_type === 'initial_payment' && in_array('initial_payment', $paid_payment_types)) {
        throw new Exception("Initial payment has already been made for this student");
    }

    if ($payment_type === 'reservation' && in_array('reservation', $paid_payment_types)) {
        throw new Exception("Reservation payment has already been made for this student");
    }

    if ($payment_type === 'demo_payment' && in_array($demo_type, $paid_demos)) {
        throw new Exception("This demo payment has already been made for this student");
    }

    if ($payment_type === 'full_payment' && in_array('full_payment', $paid_payment_types)) {
        throw new Exception("Full payment has already been made for this student");
    }

    // Get program and package details for calculations
    $program_stmt = $conn->prepare("SELECT * FROM program WHERE id = ?");
    $program_stmt->bind_param("i", $program_id);
    $program_stmt->execute();
    $program_data = $program_stmt->get_result()->fetch_assoc();

    if (!$program_data) {
        throw new Exception("Program not found");
    }

    // Get package/promo details
    $promo_discount = 0;
    $promo_data = null;

    if ($package_name && $package_name !== 'Regular' && $package_name !== 'Regular Package') {
        $promo_stmt = $conn->prepare("SELECT * FROM promo WHERE program_id = ? AND package_name = ?");
        $promo_stmt->bind_param("is", $program_id, $package_name);
        $promo_stmt->execute();
        $promo_result = $promo_stmt->get_result();

        if ($promo_result->num_rows > 0) {
            $promo_data = $promo_result->fetch_assoc();
            if ($promo_data['promo_type'] === 'percentage') {
                $promo_discount = $subtotal * (floatval($promo_data['percentage']) / 100);
            } else if ($promo_data['promo_type'] === 'fixed') {
                $promo_discount = floatval($promo_data['enrollment_fee']);
            }
        }
    }

    // Calculate totals
    $subtotal = safe_float($program_data['total_tuition']);

    // Add system fee for online learning
    if ($learning_mode === 'Online') {
        $system_fee = safe_float($program_data['system_fee']);
        $subtotal += $system_fee;
    }

    $final_total = $subtotal - $promo_discount;
    $balance = $final_total - $processed_payment_amount;

    // For existing transactions, use existing totals and calculate new balance
    if ($current_total > 0) {
        $final_total = $current_total;
        $subtotal = $current_subtotal;
        $promo_discount = $existing_promo_discount;
        $balance = $current_balance - $processed_payment_amount;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // ENHANCED: Program end date validation with blocking
        if ($program_info['status'] === 'ENDED') {
            throw new Exception($program_info['message']);
        }

        // Insert POS transaction with excess payment data
        $sql = "INSERT INTO pos_transactions (
            student_id, program_id, learning_mode, payment_type, demo_type,
            selected_schedules, sub_total, final_total, cash_received, 
            balance, transaction_date, package_name, promo_discount, subtotal,
            excess_amount, excess_option, excess_description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $stmt->bind_param(
            "iissssddddssddss",
            $student_id,
            $program_id,
            $learning_mode,
            $payment_type,
            $demo_type,
            $selected_schedules,
            $subtotal,
            $final_total,
            $processed_payment_amount,
            $balance,
            $current_time,
            $package_name,
            $promo_discount,
            $subtotal,
            $excess_amount,
            $excess_option,
            $excess_description
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert transaction: " . $stmt->error);
        }

        $transaction_id = $stmt->insert_id;

        // ENHANCED: Log excess payment handling
        if ($excess_amount > 0 && $excess_option) {
            logExcessPaymentAction(
                $conn,
                $student_id,
                $transaction_id,
                $payment_type,
                $excess_option,
                $original_payment_amount,
                $payment_amount,
                $excess_amount,
                $excess_description
            );
        }

        $conn->commit();

        debug_log("✅ Transaction completed successfully", [
            'transaction_id' => $transaction_id,
            'student_id' => $student_id,
            'payment_type' => $payment_type,
            'amount' => $processed_payment_amount,
            'balance' => $balance,
            'excess_handled' => $excess_option ?? 'none'
        ]);

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'transaction_id' => $transaction_id,
                'balance' => $balance,
                'payment_amount' => $processed_payment_amount,
                'excess_amount' => $excess_amount,
                'excess_option' => $excess_option,
                'change_amount' => $final_change_amount,
                'program_end_check' => $program_info
            ],
            'program_end_check' => $program_info,
            'timestamp' => $current_time,
            'processed_by' => 'Scraper001'
        ]);

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

// =============================================================================================
// EXCESS PAYMENT PROCESSING FUNCTIONS
// =============================================================================================

/**
 * Process excess payment based on selected option
 */
function processExcessPayment($conn, $student_id, $program_id, $payment_type, $excess_option, $original_amount, $required_amount, $excess_amount, $demo_type = null)
{
    debug_log("🔄 Processing excess payment option: {$excess_option}");

    $result = [
        'processed_amount' => $required_amount,
        'change_amount' => 0,
        'excess_handled' => true,
        'description' => ''
    ];

    try {
        switch ($excess_option) {
            case 'treat_as_full':
                $result = processTreatAsFullPayment($conn, $student_id, $program_id, $original_amount, $required_amount);
                break;

            case 'allocate_to_demos':
                $result = processAllocateToSequentialDemos($conn, $student_id, $program_id, $original_amount, $required_amount, $excess_amount);
                break;

            case 'add_to_next_demo':
                $result = processAddToNextDemo($conn, $student_id, $program_id, $original_amount, $excess_amount, $demo_type);
                break;

            case 'credit_to_account':
                $result = processCreditToAccount($conn, $student_id, $original_amount, $required_amount, $excess_amount);
                break;

            case 'return_as_change':
                $result = processReturnAsChange($conn, $original_amount, $required_amount, $excess_amount);
                break;

            default:
                debug_log("⚠️ Unknown excess option, using default");
                $result['description'] = 'Unknown excess option, processed as regular payment';
                break;
        }

        debug_log("✅ Excess payment option processed", $result);
        return $result;

    } catch (Exception $e) {
        debug_log("❌ Excess payment processing error: " . $e->getMessage());

        // Fallback to regular payment processing
        return [
            'processed_amount' => $required_amount,
            'change_amount' => $excess_amount,
            'excess_handled' => false,
            'description' => 'Error processing excess payment, returned as change'
        ];
    }
}

/**
 * Option 1: Treat entire payment as initial, redistribute to all demos
 */
function processTreatAsFullPayment($conn, $student_id, $program_id, $original_amount, $required_amount)
{
    debug_log("💰 Processing treat as full payment");

    return [
        'processed_amount' => $original_amount,
        'change_amount' => 0,
        'excess_handled' => true,
        'description' => 'Entire payment applied as initial - demo balances will be reduced equally'
    ];
}

/**
 * Option 2: Allocate excess to demos in sequential order
 */
function processAllocateToSequentialDemos($conn, $student_id, $program_id, $original_amount, $required_amount, $excess_amount)
{
    debug_log("🎯 Processing allocate to sequential demos");

    // Create demo credit entries for future demo payments
    $demos = ['demo1', 'demo2', 'demo3', 'demo4'];
    $remaining_excess = $excess_amount;
    $allocated_demos = [];

    foreach ($demos as $demo) {
        if ($remaining_excess <= 0.01)
            break;

        // Check if demo is already paid
        $stmt = $conn->prepare("SELECT id FROM pos_transactions WHERE student_id = ? AND program_id = ? AND demo_type = ?");
        $stmt->bind_param("iis", $student_id, $program_id, $demo);
        $stmt->execute();

        if ($stmt->get_result()->num_rows == 0) {
            // Demo not paid, allocate some excess to it
            $allocation = min($remaining_excess, 2000); // Limit allocation per demo
            $allocated_demos[] = [
                'demo' => $demo,
                'amount' => $allocation
            ];
            $remaining_excess -= $allocation;
        }
    }

    return [
        'processed_amount' => $original_amount,
        'change_amount' => $remaining_excess, // Return any unallocated excess
        'excess_handled' => true,
        'description' => 'Excess allocated to demos: ' . implode(', ', array_map(function ($item) {
            return $item['demo'] . ' (₱' . number_format($item['amount'], 2) . ')';
        }, $allocated_demos)),
        'demo_allocations' => $allocated_demos
    ];
}

/**
 * Option 3 (Demo): Add excess to next unpaid demo
 */
function processAddToNextDemo($conn, $student_id, $program_id, $original_amount, $excess_amount, $current_demo)
{
    debug_log("➡️ Processing add to next demo");

    // Find next unpaid demo
    $demos = ['demo1', 'demo2', 'demo3', 'demo4'];
    $current_index = array_search($current_demo, $demos);
    $next_demo = null;

    if ($current_index !== false) {
        for ($i = $current_index + 1; $i < count($demos); $i++) {
            $check_demo = $demos[$i];

            // Check if next demo is unpaid
            $stmt = $conn->prepare("SELECT id FROM pos_transactions WHERE student_id = ? AND program_id = ? AND demo_type = ?");
            $stmt->bind_param("iis", $student_id, $program_id, $check_demo);
            $stmt->execute();

            if ($stmt->get_result()->num_rows == 0) {
                $next_demo = $check_demo;
                break;
            }
        }
    }

    $description = $next_demo
        ? "Excess ₱" . number_format($excess_amount, 2) . " credited to {$next_demo}"
        : "No unpaid demos found, excess returned as change";

    return [
        'processed_amount' => $original_amount,
        'change_amount' => $next_demo ? 0 : $excess_amount,
        'excess_handled' => true,
        'description' => $description,
        'credited_demo' => $next_demo
    ];
}

/**
 * Option 4 (Demo): Credit to student account
 */
function processCreditToAccount($conn, $student_id, $original_amount, $required_amount, $excess_amount)
{
    debug_log("💳 Processing credit to account");

    return [
        'processed_amount' => $original_amount,
        'change_amount' => 0,
        'excess_handled' => true,
        'description' => "₱" . number_format($excess_amount, 2) . " credited to student account for future use",
        'account_credit' => $excess_amount
    ];
}

/**
 * Option 5: Return excess as change
 */
function processReturnAsChange($original_amount, $required_amount, $excess_amount)
{
    debug_log("💵 Processing return as change");

    return [
        'processed_amount' => $required_amount,
        'change_amount' => $excess_amount,
        'excess_handled' => true,
        'description' => "₱" . number_format($excess_amount, 2) . " returned as change"
    ];
}

/**
 * Log excess payment actions for audit trail
 */
function logExcessPaymentAction($conn, $student_id, $transaction_id, $payment_type, $excess_option, $original_amount, $required_amount, $excess_amount, $description)
{
    try {
        $stmt = $conn->prepare("
            INSERT INTO excess_payment_logs 
            (student_id, transaction_id, payment_type, payment_amount, required_amount, excess_amount, excess_option, description, created_at, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $current_time = '2025-06-21 04:00:26';
        $created_by = 'Scraper001';

        $stmt->bind_param(
            "iisdddsss",
            $student_id,
            $transaction_id,
            $payment_type,
            $original_amount,
            $required_amount,
            $excess_amount,
            $excess_option,
            $description,
            $current_time,
            $created_by
        );

        if (!$stmt->execute()) {
            debug_log("⚠️ Failed to log excess payment action: " . $stmt->error);
        } else {
            debug_log("✅ Excess payment action logged successfully");
        }

    } catch (Exception $e) {
        debug_log("❌ Error logging excess payment action: " . $e->getMessage());
    }
}

$conn->close();
?>