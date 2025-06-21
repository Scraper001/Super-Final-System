<?php
include "../../../connection/connection.php";
$conn = con();

header('Content-Type: application/json');
$conn->autocommit(FALSE);

// Debug function
function debug_log($message, $data = null)
{
    $log_message = "[DEBUG] " . $message;
    if ($data !== null) {
        $log_message .= ": " . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    error_log($log_message);
    file_put_contents('debug_enrollment.log', date('Y-m-d H:i:s') . " - " . $log_message . "\n", FILE_APPEND);
}

// Helper function to safely convert string numbers to float
function safe_float($value)
{
    if (is_string($value)) {
        $cleaned = str_replace([',', '₱', '$'], '', $value);
        return floatval($cleaned);
    }
    return floatval($value);
}

// ⭐ FIXED: Check if program has ended with correct table name and error handling
function checkProgramEndDate($conn, $program_id)
{
    debug_log("🔍 CHECKING PROGRAM END DATE", [
        'program_id' => $program_id,
        'current_date' => '2025-06-08 16:28:07',
        'current_user' => 'Scraper001'
    ]);

    // First, check if end_date column exists in the correct table 'program'
    $column_check = $conn->query("SHOW COLUMNS FROM program LIKE 'end_date'");
    if (!$column_check || $column_check->num_rows === 0) {
        debug_log("⚠️ WARNING: end_date column does not exist in program table", [
            'action' => 'Creating end_date column',
            'table' => 'program',
            'timestamp' => '2025-06-08 16:28:07'
        ]);

        // Create the end_date column if it doesn't exist
        $add_column_sql = "ALTER TABLE program ADD COLUMN end_date DATE NULL AFTER program_name";
        if (!$conn->query($add_column_sql)) {
            debug_log("❌ Failed to create end_date column", $conn->error);
            // Continue without end date check if column creation fails
            return [
                'program_name' => 'Unknown Program',
                'end_date' => null,
                'program_status' => 'ACTIVE',
                'end_date_check_available' => false
            ];
        }

        debug_log("✅ end_date column created successfully in program table");

        // Set a default end_date for existing programs (1 year from now)
        $default_end_date = date('Y-m-d', strtotime('+1 year'));
        $update_sql = "UPDATE program SET end_date = '$default_end_date' WHERE end_date IS NULL";
        if ($conn->query($update_sql)) {
            debug_log("✅ Default end_date set for existing programs", $default_end_date);
        }
    }

    // Now check the program with end_date using correct table name
    $program_check = $conn->prepare("
        SELECT 
            program_name,
            end_date,
            DATE(end_date) as end_date_only,
            CURDATE() as current_date,
            CASE 
                WHEN end_date IS NULL THEN 'ACTIVE'
                WHEN DATE(end_date) < CURDATE() THEN 'ENDED'
                WHEN DATE(end_date) = CURDATE() THEN 'ENDING_TODAY'
                ELSE 'ACTIVE'
            END as program_status
        FROM program 
        WHERE id = ?
    ");

    if (!$program_check) {
        debug_log("❌ Failed to prepare program check statement", $conn->error);
        throw new Exception("Database error: Unable to prepare program validation query");
    }

    $program_check->bind_param("i", $program_id);
    if (!$program_check->execute()) {
        debug_log("❌ Failed to execute program check", $program_check->error);
        throw new Exception("Database error: Unable to validate program");
    }

    $program_result = $program_check->get_result()->fetch_assoc();

    if (!$program_result) {
        throw new Exception("Program not found (ID: {$program_id})");
    }

    debug_log("📅 PROGRAM END DATE CHECK RESULT", [
        'program_name' => $program_result['program_name'],
        'end_date' => $program_result['end_date'],
        'current_date' => $program_result['current_date'],
        'program_status' => $program_result['program_status'],
        'check_time' => '2025-06-08 16:28:07',
        'user' => 'Scraper001'
    ]);

    if ($program_result['program_status'] === 'ENDED') {
        $end_date_formatted = date('F j, Y', strtotime($program_result['end_date']));
        throw new Exception("❌ ENROLLMENT BLOCKED: Program '{$program_result['program_name']}' has ended on {$end_date_formatted}. New enrollments are not allowed for ended programs.");
    }

    if ($program_result['program_status'] === 'ENDING_TODAY') {
        debug_log("⚠️ WARNING: Program ending today", [
            'program_name' => $program_result['program_name'],
            'end_date' => $program_result['end_date']
        ]);
        // Allow enrollment but log warning
    }

    // Add flag to indicate end date check was available
    $program_result['end_date_check_available'] = true;

    return $program_result;
}

// Calculate demo fee properly
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

    if (!$demo_stmt) {
        debug_log("❌ Failed to prepare demo statement", $conn->error);
        return 0;
    }

    $demo_stmt->bind_param("ii", $student_id, $program_id);
    $demo_stmt->execute();
    $demo_result = $demo_stmt->get_result()->fetch_assoc();
    $paid_demos_count = intval($demo_result['paid_demos_count'] ?? 0);

    $remaining_demos = 4 - $paid_demos_count;
    $demo_fee = $remaining_demos > 0 ? max(0, $current_balance / $remaining_demos) : 0;

    debug_log("FIXED Demo fee calculation", [
        'current_balance' => $current_balance,
        'paid_demos_count' => $paid_demos_count,
        'remaining_demos' => $remaining_demos,
        'demo_fee' => $demo_fee
    ]);

    return $demo_fee;
}

// Initial environment check
debug_log("🚀 SCRIPT STARTED WITH CORRECT TABLE NAME AND ERROR HANDLING", [
    'POST_data_keys' => array_keys($_POST),
    'connection_status' => $conn ? 'Connected' : 'Failed',
    'current_time' => '2025-06-08 16:28:07 UTC',
    'current_user' => 'Scraper001',
    'program_table' => 'program (not program_tbl)'
]);

try {
    // Validate connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get POST data with validation
    if (!isset($_POST['student_id']) || !isset($_POST['program_id'])) {
        throw new Exception("Missing required parameters: student_id or program_id");
    }

    $student_id = intval($_POST['student_id']);
    $program_id = intval($_POST['program_id']);
    $learning_mode = $_POST['learning_mode'] ?? '';
    $payment_type = $_POST['type_of_payment'] ?? '';
    $package_name = $_POST['package_id'] ?? 'Regular';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;

    if ($student_id <= 0 || $program_id <= 0) {
        throw new Exception("Invalid student_id or program_id");
    }

    debug_log("📋 VALIDATED INPUT PARAMETERS", [
        'student_id' => $student_id,
        'program_id' => $program_id,
        'learning_mode' => $learning_mode,
        'payment_type' => $payment_type,
        'package_name' => $package_name,
        'demo_type' => $demo_type
    ]);

    // ⭐ ENHANCED: Check program end date with proper error handling
    debug_log("🔍 PERFORMING PROGRAM END DATE VALIDATION", [
        'program_id' => $program_id,
        'validation_time' => '2025-06-08 16:28:07',
        'user' => 'Scraper001'
    ]);

    try {
        $program_info = checkProgramEndDate($conn, $program_id);

        debug_log("✅ PROGRAM END DATE VALIDATION COMPLETED", [
            'program_name' => $program_info['program_name'],
            'program_status' => $program_info['program_status'],
            'end_date' => $program_info['end_date'] ?? 'Not set',
            'end_date_check_available' => $program_info['end_date_check_available'] ?? false
        ]);
    } catch (Exception $e) {
        // If program end date check fails but it's not a blocking error, continue
        if (strpos($e->getMessage(), 'ENROLLMENT BLOCKED') !== false) {
            throw $e; // Re-throw blocking errors
        }

        debug_log("⚠️ Program end date check failed, continuing without check", $e->getMessage());
        $program_info = [
            'program_name' => 'Unknown Program',
            'end_date' => null,
            'program_status' => 'ACTIVE',
            'end_date_check_available' => false
        ];
    }

    // Validate schedule selection for initial payments
    if ($payment_type === 'initial_payment') {
        $schedules_data = json_decode($selected_schedules, true);
        if (empty($schedules_data) || !is_array($schedules_data) || count($schedules_data) === 0) {
            throw new Exception("Schedule selection is required for initial payment.");
        }
        debug_log("Initial payment schedule validation passed", count($schedules_data));
    }

    // Get computation values from POST with validation
    $subtotal = safe_float($_POST['sub_total'] ?? 0);
    $final_total = safe_float($_POST['final_total'] ?? 0);
    $cash = safe_float($_POST['cash'] ?? 0);
    $promo_discount = safe_float($_POST['promo_applied'] ?? 0);

    debug_log("POST Values Analysis", [
        'sub_total_raw' => $_POST['sub_total'] ?? 'not set',
        'final_total_raw' => $_POST['final_total'] ?? 'not set',
        'cash_raw' => $_POST['cash'] ?? 'not set',
        'promo_applied_raw' => $_POST['promo_applied'] ?? 'not set',
        'sub_total_processed' => $subtotal,
        'final_total_processed' => $final_total,
        'cash_processed' => $cash,
        'promo_discount_processed' => $promo_discount
    ]);

    // Validate required values
    if ($subtotal <= 0) {
        throw new Exception("Invalid subtotal amount");
    }

    if ($final_total <= 0) {
        throw new Exception("Invalid final total amount");
    }

    if ($cash <= 0) {
        throw new Exception("Invalid cash amount");
    }

    // Check for unpaid balances in OTHER programs
    $balance_check = $conn->prepare("
        SELECT COUNT(*) AS has_other_balance
        FROM pos_transactions
        WHERE student_id = ? 
          AND balance > 0 
          AND status = 'Active'
          AND program_id != ?
    ");

    if (!$balance_check) {
        debug_log("❌ Failed to prepare balance check", $conn->error);
        throw new Exception("Database error: Unable to prepare balance check query");
    }

    $balance_check->bind_param("ii", $student_id, $program_id);
    $balance_check->execute();
    $balance_result = $balance_check->get_result()->fetch_assoc();

    if ($balance_result['has_other_balance'] > 0) {
        throw new Exception("Cannot enroll. Student has unpaid balances in another program. Please settle it first.");
    }

    // Check if enrolled in the same program
    $existing_enrollment_check = $conn->prepare("
        SELECT se.id, se.status, pt.balance, pt.total_amount
        FROM student_enrollments se
        JOIN pos_transactions pt ON se.pos_transaction_id = pt.id
        WHERE se.student_id = ? AND se.program_id = ? AND se.status IN ('Enrolled', 'Reserved')
    ");

    if (!$existing_enrollment_check) {
        debug_log("❌ Failed to prepare enrollment check", $conn->error);
        throw new Exception("Database error: Unable to prepare enrollment check query");
    }

    $existing_enrollment_check->bind_param("ii", $student_id, $program_id);
    $existing_enrollment_check->execute();
    $existing_enrollment = $existing_enrollment_check->get_result()->fetch_assoc();

    // Handle existing enrollment
    if ($existing_enrollment && $existing_enrollment['balance'] > 0) {
        debug_log("Found existing enrollment with balance", $existing_enrollment);
        return handleExistingEnrollmentPayment($conn, $existing_enrollment, $cash, $student_id, $program_id, $payment_type, $demo_type, $promo_discount, $subtotal, $final_total, $selected_schedules, $program_info);
    }

    if ($existing_enrollment && $existing_enrollment['balance'] <= 0) {
        throw new Exception("Student is already fully enrolled in this program.");
    }

    // NEW ENROLLMENT - Use POST values directly
    $program_details = json_decode($_POST['program_details'] ?? '{}', true);
    $package_details = !empty($_POST['package_details']) ? json_decode($_POST['package_details'], true) : null;

    debug_log("Program Details", $program_details);
    debug_log("Package Details", $package_details);

    // Use POST values for calculations
    $total_amount = $final_total;

    debug_log("Using POST calculation values", [
        'subtotal_from_post' => $subtotal,
        'promo_discount_from_post' => $promo_discount,
        'final_total_from_post' => $final_total,
        'total_amount_used' => $total_amount
    ]);

    // Calculate payment amount based on payment type
    switch ($payment_type) {
        case 'full_payment':
            $payment_amount = $total_amount;
            break;
        case 'initial_payment':
            $payment_amount = $cash;
            break;
        case 'demo_payment':
            if (!$demo_type) {
                throw new Exception("Demo type is required for demo payment");
            }
            // Use the corrected demo fee calculation
            $payment_amount = calculateDemoFee($conn, $student_id, $program_id, $final_total);
            break;
        case 'reservation':
            $payment_amount = $cash;
            break;
        default:
            throw new Exception("Invalid payment type");
    }

    // Calculate change and balance
    $change_amount = $cash - $payment_amount;
    $balance = $total_amount - $payment_amount;

    debug_log("Payment calculations", [
        'payment_type' => $payment_type,
        'payment_amount' => $payment_amount,
        'cash_received' => $cash,
        'change_amount' => $change_amount,
        'remaining_balance' => $balance
    ]);

    // Validate payment
    if ($change_amount < 0) {
        throw new Exception("Insufficient cash payment. Required: ₱" . number_format($payment_amount, 2) . ", Received: ₱" . number_format($cash, 2));
    }

    $enrollment_status = in_array($payment_type, ['full_payment', 'demo_payment', 'initial_payment']) ? 'Enrolled' : 'Reserved';

    // Get system fee if online
    $system_fee = ($learning_mode === 'Online') ? safe_float($program_details['system_fee'] ?? 0) : 0;

    // ⭐ ENHANCED: Include program end date info in transaction description
    $program_end_note = ($program_info['program_status'] === 'ENDING_TODAY') ? " (Program ends today)" : "";
    $description = "Initial enrollment - " . ucfirst(str_replace('_', ' ', $payment_type)) . $program_end_note;

    // Insert POS Transaction
    $pos_stmt = $conn->prepare("
        INSERT INTO pos_transactions (
            student_id, program_id, learning_mode, package_name, payment_type, 
            demo_type, selected_schedules, subtotal, promo_discount, system_fee, 
            total_amount, cash_received, change_amount, balance, enrollment_status,
            debit_amount, credit_amount, change_given, description, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
    ");

    if (!$pos_stmt) {
        debug_log("❌ Failed to prepare POS transaction statement", $conn->error);
        throw new Exception("Database error: Unable to prepare transaction query");
    }

    $debit_amount = $payment_amount;
    $credit_amount = 0;
    $change_given = max(0, $change_amount);

    debug_log("Final database values", [
        'subtotal' => $subtotal,
        'promo_discount' => $promo_discount,
        'system_fee' => $system_fee,
        'total_amount' => $total_amount,
        'cash_received' => $cash,
        'change_amount' => $change_amount,
        'balance' => $balance,
        'debit_amount' => $debit_amount,
        'change_given' => $change_given,
        'description' => $description,
        'program_end_status' => $program_info['program_status']
    ]);

    $pos_stmt->bind_param(
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
        $total_amount,
        $cash,
        $change_amount,
        $balance,
        $enrollment_status,
        $debit_amount,
        $credit_amount,
        $change_given,
        $description
    );

    if (!$pos_stmt->execute()) {
        debug_log("POS transaction insert failed", $pos_stmt->error);
        throw new Exception("Failed to insert POS transaction: " . $pos_stmt->error);
    }

    $pos_transaction_id = $conn->insert_id;
    debug_log("✅ POS TRANSACTION CREATED WITH PROGRAM END DATE CHECK", [
        'transaction_id' => $pos_transaction_id,
        'program_status' => $program_info['program_status'],
        'program_end_date' => $program_info['end_date'],
        'timestamp' => '2025-06-08 16:28:07'
    ]);

    // Insert Student Enrollment
    $enrollment_stmt = $conn->prepare("
        INSERT INTO student_enrollments (
            student_id, program_id, pos_transaction_id, status, 
            learning_mode, selected_schedules
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$enrollment_stmt) {
        debug_log("❌ Failed to prepare enrollment statement", $conn->error);
        throw new Exception("Database error: Unable to prepare enrollment query");
    }

    $enrollment_stmt->bind_param(
        "iiisss",
        $student_id,
        $program_id,
        $pos_transaction_id,
        $enrollment_status,
        $learning_mode,
        $selected_schedules
    );

    if (!$enrollment_stmt->execute()) {
        debug_log("Enrollment insert failed", $enrollment_stmt->error);
        throw new Exception("Failed to insert enrollment: " . $enrollment_stmt->error);
    }

    // Update student status
    $student_stmt = $conn->prepare("UPDATE student_info_tbl SET enrollment_status = ? WHERE id = ?");
    if (!$student_stmt) {
        debug_log("❌ Failed to prepare student update statement", $conn->error);
        throw new Exception("Database error: Unable to prepare student update query");
    }

    $student_stmt->bind_param("si", $enrollment_status, $student_id);

    if (!$student_stmt->execute()) {
        debug_log("Student status update failed", $student_stmt->error);
        throw new Exception("Failed to update student status");
    }

    $conn->commit();
    debug_log("✅ TRANSACTION COMMITTED SUCCESSFULLY WITH PROGRAM END DATE VALIDATION", [
        'transaction_id' => $pos_transaction_id,
        'enrollment_status' => $enrollment_status,
        'program_end_check' => 'PASSED',
        'timestamp' => '2025-06-08 16:28:07',
        'user' => 'Scraper001'
    ]);

    // ⭐ ENHANCED: Include program end date warning in response
    $response_message = 'Enrollment processed successfully';
    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $response_message .= ' (Note: Program ends today - ' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    }

    echo json_encode([
        'success' => true,
        'message' => $response_message,
        'transaction_id' => $pos_transaction_id,
        'enrollment_status' => $enrollment_status,
        'balance' => $balance,
        'subtotal' => $subtotal,
        'promo_discount' => $promo_discount,
        'total_amount' => $total_amount,
        'payment_amount' => $payment_amount,
        'change_amount' => $change_amount,
        'program_end_check' => [
            'status' => $program_info['program_status'],
            'end_date' => $program_info['end_date'],
            'program_name' => $program_info['program_name'],
            'check_available' => $program_info['end_date_check_available']
        ],
        'timestamp' => '2025-06-08 16:28:07',
        'processed_by' => 'Scraper001'
    ]);

} catch (Exception $e) {
    debug_log("❌ EXCEPTION OCCURRED", [
        'error_message' => $e->getMessage(),
        'timestamp' => '2025-06-08 16:28:07',
        'user' => 'Scraper001'
    ]);
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => '2025-06-08 16:28:07',
        'processed_by' => 'Scraper001'
    ]);
}

$conn->autocommit(TRUE);

// ⭐ ENHANCED: Handle existing enrollment payments with program end date check
function handleExistingEnrollmentPayment($conn, $existing_enrollment, $cash, $student_id, $program_id, $payment_type, $demo_type, $promo_discount, $subtotal, $final_total, $selected_schedules, $program_info)
{
    debug_log("🔄 HANDLE EXISTING ENROLLMENT PAYMENT WITH PROGRAM END DATE CHECK", [
        'cash' => $cash,
        'payment_type' => $payment_type,
        'demo_type' => $demo_type,
        'subtotal_from_post' => $subtotal,
        'final_total_from_post' => $final_total,
        'program_status' => $program_info['program_status'],
        'program_end_date' => $program_info['end_date'],
        'timestamp' => '2025-06-08 16:28:07',
        'user' => 'Scraper001'
    ]);

    // Get current enrollment details
    $current_balance_stmt = $conn->prepare("
        SELECT balance, total_amount, subtotal, promo_discount, learning_mode, package_name, selected_schedules, system_fee
        FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        ORDER BY id DESC LIMIT 1
    ");

    if (!$current_balance_stmt) {
        debug_log("❌ Failed to prepare current balance statement", $conn->error);
        throw new Exception("Database error: Unable to prepare balance query");
    }

    $current_balance_stmt->bind_param("ii", $student_id, $program_id);
    $current_balance_stmt->execute();
    $balance_info = $current_balance_stmt->get_result()->fetch_assoc();

    $current_balance = safe_float($balance_info['balance']);
    $current_total = safe_float($balance_info['total_amount']);
    $current_subtotal = safe_float($balance_info['subtotal']);
    $existing_promo_discount = safe_float($balance_info['promo_discount']);
    $learning_mode = $balance_info['learning_mode'];
    $package_name = $balance_info['package_name'];
    $existing_selected_schedules = $balance_info['selected_schedules'];
    $system_fee = safe_float($balance_info['system_fee']);

    // Calculate payment amount based on type
    $payment_amount = 0;

    switch ($payment_type) {
        case 'demo_payment':
            if (!$demo_type) {
                throw new Exception("Demo type is required for demo payment");
            }
            $payment_amount = calculateDemoFee($conn, $student_id, $program_id, $current_total);
            break;
        case 'full_payment':
            // For full payment of existing enrollment, pay the remaining balance
            $payment_amount = $current_balance;
            if ($cash < $current_balance) {
                throw new Exception("Insufficient cash for full payment. Required: ₱" . number_format($current_balance, 2));
            }
            break;
        default:
            // For other payment types, use cash amount but validate against balance
            $payment_amount = $cash;
            if ($cash > $current_balance) {
                throw new Exception("Payment exceeds remaining balance (₱" . number_format($current_balance, 2) . ")");
            }
            break;
    }

    // Calculate new balance and change
    $new_balance = $current_balance - $payment_amount;
    $change_amount = $cash - $payment_amount;
    $new_status = ($new_balance <= 0) ? 'Enrolled' : 'Reserved';

    debug_log("Balance payment calculation with program end date info", [
        'current_balance' => $current_balance,
        'payment_amount' => $payment_amount,
        'new_balance' => $new_balance,
        'change_amount' => $change_amount,
        'program_status' => $program_info['program_status'],
        'program_end_date' => $program_info['end_date']
    ]);

    // Use new schedules if provided, otherwise keep existing
    $final_schedules = (!empty($selected_schedules) && $selected_schedules !== '[]') ? $selected_schedules : $existing_selected_schedules;

    // ⭐ ENHANCED: Include program end date info in description
    $program_end_note = ($program_info['program_status'] === 'ENDING_TODAY') ? " (Program ends today)" : "";
    $description = ($payment_type === 'demo_payment')
        ? "Demo payment - " . $demo_type . $program_end_note
        : "Balance payment - " . $payment_type . $program_end_note;

    // Insert new payment transaction
    $payment_stmt = $conn->prepare("
        INSERT INTO pos_transactions (
            student_id, program_id, learning_mode, package_name, payment_type, 
            demo_type, selected_schedules, subtotal, promo_discount, system_fee, 
            total_amount, cash_received, change_amount, balance, enrollment_status,
            debit_amount, credit_amount, change_given, description, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
    ");

    if (!$payment_stmt) {
        debug_log("❌ Failed to prepare payment statement", $conn->error);
        throw new Exception("Database error: Unable to prepare payment query");
    }

    $debit_amount = $payment_amount;
    $credit_amount = 0;
    $change_given = max(0, $change_amount);

    $payment_stmt->bind_param(
        "iisssssdddddddsddds",
        $student_id,
        $program_id,
        $learning_mode,
        $package_name,
        $payment_type,
        $demo_type,
        $final_schedules,
        $current_subtotal,
        $existing_promo_discount,
        $system_fee,
        $current_total,
        $cash,
        $change_amount,
        $new_balance,
        $new_status,
        $debit_amount,
        $credit_amount,
        $change_given,
        $description
    );

    if (!$payment_stmt->execute()) {
        debug_log("Payment transaction insert failed", $payment_stmt->error);
        throw new Exception("Failed to record payment: " . $payment_stmt->error);
    }

    $new_transaction_id = $conn->insert_id;

    // Update enrollment record
    $update_enrollment_stmt = $conn->prepare("
        UPDATE student_enrollments 
        SET pos_transaction_id = ?, status = ?, selected_schedules = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE student_id = ? AND program_id = ?
    ");

    if (!$update_enrollment_stmt) {
        debug_log("❌ Failed to prepare enrollment update statement", $conn->error);
        throw new Exception("Database error: Unable to prepare enrollment update query");
    }

    $update_enrollment_stmt->bind_param("issii", $new_transaction_id, $new_status, $final_schedules, $student_id, $program_id);

    if (!$update_enrollment_stmt->execute()) {
        throw new Exception("Failed to update enrollment: " . $update_enrollment_stmt->error);
    }

    // Update student status
    $student_stmt = $conn->prepare("UPDATE student_info_tbl SET enrollment_status = ? WHERE id = ?");
    if (!$student_stmt) {
        debug_log("❌ Failed to prepare student update statement", $conn->error);
        throw new Exception("Database error: Unable to prepare student update query");
    }

    $student_stmt->bind_param("si", $new_status, $student_id);
    $student_stmt->execute();

    $conn->commit();

    // ⭐ ENHANCED: Include program end date warning in response
    $response_message = ucfirst(str_replace('_', ' ', $payment_type)) . ' processed successfully';
    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $response_message .= ' (Note: Program ends today - ' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    }

    echo json_encode([
        'success' => true,
        'message' => $response_message,
        'transaction_id' => $new_transaction_id,
        'payment_type' => $payment_type,
        'balance' => $new_balance,
        'total_amount' => $current_total,
        'change_amount' => $change_amount,
        'payment_amount' => $payment_amount,
        'program_end_check' => [
            'status' => $program_info['program_status'],
            'end_date' => $program_info['end_date'],
            'program_name' => $program_info['program_name'],
            'check_available' => $program_info['end_date_check_available']
        ],
        'timestamp' => '2025-06-08 16:28:07',
        'processed_by' => 'Scraper001'
    ]);
    exit;
}
?>