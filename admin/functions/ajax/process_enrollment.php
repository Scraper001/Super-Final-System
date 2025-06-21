<?php
/**
 * Fixed Process Enrollment with Proper Excess Payment Validation
 * CarePro POS System - Validation-First Approach
 * User: Scraper001 | Time: 2025-06-21 09:47:17
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
 * VALIDATION: Check if excess allocation is allowed
 */
function validateExcessAllocation($payment_type, $excess_choice, $excess_amount)
{
    $validation_errors = [];

    if ($payment_type === 'initial_payment') {
        if (in_array($excess_choice, ['treat_as_full', 'allocate_to_demos'])) {
            $validation_errors[] = "ERROR: Cannot allocate initial payment excess to demo allocation. System limitation prevents this operation.";
        }
    }

    if ($payment_type === 'demo_payment') {
        if (in_array($excess_choice, ['add_to_next_demo', 'credit_to_account'])) {
            $validation_errors[] = "ERROR: Cannot allocate demo excess to another demo. System limitation prevents this operation.";
        }
    }

    return [
        'valid' => empty($validation_errors),
        'errors' => $validation_errors,
        'suggested_action' => empty($validation_errors) ? null : 'Please choose to return excess as change or adjust payment amount.'
    ];
}

/**
 * SIMPLIFIED: Excess Payment Handler - Only Allows Valid Options
 */
function handleExcessPayment($excess_option, $original_amount, $required_amount, $excess_amount, $payment_type)
{
    // First validate if the option is allowed
    $validation = validateExcessAllocation($payment_type, $excess_option, $excess_amount);

    if (!$validation['valid']) {
        return [
            'success' => false,
            'errors' => $validation['errors'],
            'suggested_action' => $validation['suggested_action']
        ];
    }

    $result = [
        'success' => true,
        'processed_amount' => $required_amount,
        'change_amount' => $excess_amount,
        'description' => "Excess payment of ₱" . number_format($excess_amount, 2) . " returned as change.",
        'allocation_notes' => []
    ];

    // Only 'return_as_change' is allowed based on your validation
    if ($excess_option === 'return_as_change') {
        $result['description'] = "Excess payment of ₱" . number_format($excess_amount, 2) . " returned as change (user selected).";
    } else {
        // All other options are blocked and converted to return_as_change
        $result['description'] = "Excess payment of ₱" . number_format($excess_amount, 2) . " returned as change (allocation blocked by system validation).";
        $result['allocation_notes'] = ["User requested: " . $excess_option . " but system validation prevented allocation"];
    }

    return $result;
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
 * ENHANCED: Schedule validation function - Required for full payments
 */
function validateScheduleSelection($payment_type, $selected_schedules, $conn, $student_id, $program_id)
{
    // VALIDATION: Full payment and initial payment require schedule selection
    if (in_array($payment_type, ['full_payment', 'initial_payment'])) {
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
                return [
                    'valid' => false,
                    'message' => "VALIDATION ERROR: We can't process " . str_replace('_', ' ', $payment_type) . " if we have no selected schedule. Please select a schedule first."
                ];
            }

            debug_log("✅ Using maintained schedules for " . $payment_type);
            return [
                'valid' => true,
                'message' => "Using maintained schedules from previous transaction",
                'schedules' => $maintained_result['selected_schedules']
            ];
        }
    }

    return ['valid' => true, 'message' => 'Schedule validation passed'];
}

$current_time = '2025-06-21 09:47:17';

try {
    // Get POST data
    $student_id = $_POST['student_id'];
    $program_id = $_POST['program_id'];
    $learning_mode = $_POST['learning_mode'];
    $payment_type = $_POST['type_of_payment'];
    $package_name = $_POST['package_id'] ?? 'Regular';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;

    // Get excess payment data
    $has_excess_payment = isset($_POST['has_excess_payment']) && $_POST['has_excess_payment'] === 'true';
    $excess_choice = $_POST['excess_choice'] ?? null;
    $excess_amount = safe_float($_POST['excess_amount'] ?? 0);
    $original_payment_amount = safe_float($_POST['original_payment_amount'] ?? 0);
    $required_payment_amount = safe_float($_POST['required_payment_amount'] ?? 0);

    debug_log("💰 Excess Payment Data", [
        'has_excess' => $has_excess_payment,
        'choice' => $excess_choice,
        'excess_amount' => $excess_amount,
        'original_payment' => $original_payment_amount,
        'required_payment' => $required_payment_amount,
        'payment_type' => $payment_type
    ]);

    // VALIDATION: Check excess allocation before processing
    if ($has_excess_payment && $excess_choice) {
        $excess_validation = validateExcessAllocation($payment_type, $excess_choice, $excess_amount);
        if (!$excess_validation['valid']) {
            throw new Exception(implode(' ', $excess_validation['errors']) . ' ' . $excess_validation['suggested_action']);
        }
    }

    // Real-time program end date validation
    $program_info = checkProgramEndDate($conn, $program_id);

    // VALIDATION: Enhanced schedule validation
    $schedule_validation = validateScheduleSelection($payment_type, $selected_schedules, $conn, $student_id, $program_id);
    if (!$schedule_validation['valid']) {
        throw new Exception($schedule_validation['message']);
    }

    // Use maintained schedules if found and current schedules are empty
    if (isset($schedule_validation['schedules'])) {
        $selected_schedules = $schedule_validation['schedules'];
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
    if ($subtotal <= 0 && !$current_subtotal) {
        throw new Exception("Invalid subtotal amount");
    }

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

    // VALIDATION-FIRST: Process excess payment with validation
    $excess_processing_result = null;
    $final_payment_amount = $payment_amount;
    $final_change_amount = max(0, $cash - $payment_amount);

    if ($has_excess_payment && $excess_choice) {
        $excess_processing_result = handleExcessPayment(
            $excess_choice,
            $original_payment_amount,
            $required_payment_amount,
            $excess_amount,
            $payment_type
        );

        if (!$excess_processing_result['success']) {
            throw new Exception(implode(' ', $excess_processing_result['errors']) . ' ' . ($excess_processing_result['suggested_action'] ?? ''));
        }

        $final_payment_amount = $excess_processing_result['processed_amount'];
        $final_change_amount = max(0, ($cash - $final_payment_amount) + $excess_processing_result['change_amount']);
    }

    $balance = $final_total - $final_payment_amount;

    // For existing transactions, calculate new balance
    if ($current_balance > 0) {
        $balance = $current_balance - $final_payment_amount;
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
        // Program end date validation with blocking
        if ($program_info['status'] === 'ENDED') {
            throw new Exception($program_info['message']);
        }

        // Insert POS transaction
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

        $debit_amount = $final_payment_amount;
        $credit_amount = $final_payment_amount;
        $change_given = $final_change_amount;

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

        // Log excess payment validation/handling
        if ($excess_processing_result) {
            debug_log("💰 Excess payment validation applied", [
                'transaction_id' => $transaction_id,
                'choice_requested' => $excess_choice,
                'validation_result' => 'Excess returned as change due to system validation',
                'description' => $excess_processing_result['description'],
                'allocation_notes' => $excess_processing_result['allocation_notes'] ?? []
            ]);
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

        debug_log("✅ Transaction completed with validation", [
            'transaction_id' => $transaction_id,
            'student_id' => $student_id,
            'payment_type' => $payment_type,
            'amount' => $final_payment_amount,
            'balance' => $balance,
            'excess_validation' => $excess_choice ?? 'none',
            'schedule_validation' => $schedule_validation['message']
        ]);

        // Prepare response
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
            $response_message .= ' with excess payment validation applied';
        }

        $response = [
            'success' => true,
            'message' => $response_message,
            'transaction_id' => $transaction_id,
            'enrollment_status' => $enrollment_status,
            'balance' => $balance,
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
            'schedule_validation' => $schedule_validation,
            'timestamp' => $current_time,
            'processed_by' => 'Scraper001'
        ];

        // Add validation information to response
        if ($excess_processing_result) {
            $response['excess_processing'] = [
                'success' => true,
                'validation_applied' => true,
                'choice_requested' => $excess_choice,
                'final_change_amount' => $excess_processing_result['change_amount'],
                'processing_notes' => [$excess_processing_result['description']],
                'allocation_notes' => $excess_processing_result['allocation_notes'] ?? [],
                'validation_message' => 'System validation prevented allocation and returned excess as change',
                'processed_at' => $current_time,
                'processed_by' => 'Scraper001'
            ];
        }

        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    debug_log("❌ Enrollment processing error with validation", $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'validation_error' => true,
        'timestamp' => $current_time,
        'processed_by' => 'Scraper001'
    ]);
}

$conn->close();
?>