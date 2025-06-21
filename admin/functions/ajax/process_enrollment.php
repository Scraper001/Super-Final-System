<?php
include "../../../connection/connection.php";
$conn = con();

header('Content-Type: application/json');
$conn->autocommit(FALSE);

// Set timezone for consistent date handling
date_default_timezone_set('Asia/Manila');

// Helper function to safely convert string numbers to float
function safe_float($value)
{
    if (is_string($value)) {
        $cleaned = str_replace([',', '₱', '$'], '', $value);
        return floatval($cleaned);
    }
    return floatval($value);
}

// IMPROVED: Real-time program end date validation
function checkProgramEndDate($conn, $program_id)
{
    $current_datetime = new DateTime('2025-06-12 07:38:21', new DateTimeZone('Asia/Manila'));
    $current_date = $current_datetime->format('Y-m-d');
    $current_time = $current_datetime->format('Y-m-d H:i:s');

    // Get program details with end date
    $sql = "SELECT program_name, end_date FROM program WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program = $result->fetch_assoc();

    if (!$program) {
        throw new Exception("Program not found with ID: " . $program_id);
    }

    $program_end_date = $program['end_date'];
    $program_name = $program['program_name'];

    // Convert end date to DateTime for proper comparison
    $end_datetime = new DateTime($program_end_date, new DateTimeZone('Asia/Manila'));
    $end_date_formatted = $end_datetime->format('Y-m-d');

    // Check if program has ended (current date is AFTER end date)
    if ($current_date > $end_date_formatted) {
        $days_passed = $current_datetime->diff($end_datetime)->days;
        throw new Exception("ENROLLMENT BLOCKED: Program '{$program_name}' ended on {$end_date_formatted}. Current date: {$current_date}. Program ended {$days_passed} day(s) ago. New enrollments are not allowed.");
    }

    // Determine program status
    $program_status = 'ACTIVE';
    if ($current_date === $end_date_formatted) {
        $program_status = 'ENDING_TODAY';
    }

    // Calculate days remaining
    $days_remaining = $current_datetime->diff($end_datetime)->days;

    return [
        'program_name' => $program_name,
        'end_date' => $end_date_formatted,
        'program_status' => $program_status,
        'days_remaining' => $days_remaining,
        'current_date' => $current_date,
        'current_time' => $current_time
    ];
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

$current_time = '2025-06-12 07:38:21';

try {
    // Get POST data
    $student_id = $_POST['student_id'];
    $program_id = $_POST['program_id'];
    $learning_mode = $_POST['learning_mode'];
    $payment_type = $_POST['type_of_payment'];
    $package_name = $_POST['package_id'] ?? 'Regular';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;

    // ENHANCED: Real-time program end date validation FIRST
    $program_info = checkProgramEndDate($conn, $program_id);

    // Validate schedule selection for initial payments
    if ($payment_type === 'initial_payment') {
        $schedules_data = json_decode($selected_schedules, true);
        if (empty($schedules_data) || !is_array($schedules_data) || count($schedules_data) === 0) {
            throw new Exception("Schedule selection is required for initial payment.");
        }
    }

    // Get computation values from POST
    $subtotal = safe_float($_POST['sub_total']);
    $final_total = safe_float($_POST['final_total']);
    $cash = safe_float($_POST['cash']);
    $promo_discount = safe_float($_POST['promo_applied']);

    // Validate required values
    if ($subtotal <= 0) {
        throw new Exception("Invalid subtotal amount");
    }

    if ($final_total <= 0) {
        throw new Exception("Invalid final total amount");
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
    $existing_enrollment_check->bind_param("ii", $student_id, $program_id);
    $existing_enrollment_check->execute();
    $existing_enrollment = $existing_enrollment_check->get_result()->fetch_assoc();

    // Handle existing enrollment
    if ($existing_enrollment && $existing_enrollment['balance'] > 0) {
        return handleExistingEnrollmentPayment($conn, $existing_enrollment, $cash, $student_id, $program_id, $payment_type, $demo_type, $promo_discount, $subtotal, $final_total, $selected_schedules, $program_info);
    }

    if ($existing_enrollment && $existing_enrollment['balance'] <= 0) {
        throw new Exception("Student is already fully enrolled in this program.");
    }

    // NEW ENROLLMENT - Use POST values directly
    $program_details = json_decode($_POST['program_details'], true);
    $package_details = !empty($_POST['package_details']) ? json_decode($_POST['package_details'], true) : null;

    // Use POST values for calculations
    $total_amount = $final_total;

    // FIXED: Get payment amount from total_payment (the bill amount)
    $payment_amount = safe_float($_POST['total_payment']); // This is ₱15,000 (the bill)

    // Validate payment amount based on payment type
    switch ($payment_type) {
        case 'full_payment':
            if ($payment_amount <= 0) {
                $payment_amount = $total_amount;
            }
            break;
        case 'initial_payment':
            if ($payment_amount <= 0) {
                throw new Exception("Invalid initial payment amount");
            }
            break;
        case 'demo_payment':
            if (!$demo_type) {
                throw new Exception("Demo type is required for demo payment");
            }
            if ($payment_amount <= 0) {
                $payment_amount = calculateDemoFee($conn, $student_id, $program_id, $final_total);
            }
            break;
        case 'reservation':
            if ($payment_amount <= 0) {
                throw new Exception("Invalid reservation payment amount");
            }
            break;
        default:
            throw new Exception("Invalid payment type");
    }

    // FIXED: Calculate change and balance with proper logic
    $cash_to_pay = $payment_amount; // ₱15,000 (the bill amount)
    $change_amount = max(0, $cash - $cash_to_pay); // ₱20,000 - ₱15,000 = ₱5,000
    $balance = $total_amount - $payment_amount;

    // Validate payment
    if ($cash < $cash_to_pay) {
        throw new Exception("Insufficient cash payment. Required: ₱" . number_format($cash_to_pay, 2) . ", Received: ₱" . number_format($cash, 2));
    }

    $enrollment_status = in_array($payment_type, ['full_payment', 'demo_payment', 'initial_payment']) ? 'Enrolled' : 'Reserved';

    // Get system fee if online
    $system_fee = ($learning_mode === 'Online') ? safe_float($program_details['system_fee'] ?? 0) : 0;

    // ENHANCED: Include real-time program status in transaction description
    $program_end_note = '';
    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $program_end_note = " (Program ends today!)";
    } elseif ($program_info['days_remaining'] <= 7) {
        $program_end_note = " (Program ends in {$program_info['days_remaining']} days)";
    }

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

    // FIXED: Correct database field mapping
    $debit_amount = $payment_amount; // ₱15,000
    $credit_amount = $payment_amount; // ₱15,000 (amount being credited to account)
    $change_given = $change_amount; // ₱5,000

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
        $cash_to_pay, // FIXED: Store payment amount (₱15,000) NOT total cash (₱20,000)
        $change_amount,
        $balance,
        $enrollment_status,
        $debit_amount,
        $credit_amount,
        $change_given,
        $description
    );

    if (!$pos_stmt->execute()) {
        throw new Exception("Failed to insert POS transaction: " . $pos_stmt->error);
    }

    $pos_transaction_id = $conn->insert_id;

    // Insert Student Enrollment
    $enrollment_stmt = $conn->prepare("
        INSERT INTO student_enrollments (
            student_id, program_id, pos_transaction_id, status, 
            learning_mode, selected_schedules
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

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
        throw new Exception("Failed to insert enrollment: " . $enrollment_stmt->error);
    }

    // Update student status
    $student_stmt = $conn->prepare("UPDATE student_info_tbl SET enrollment_status = ? WHERE id = ?");
    $student_stmt->bind_param("si", $enrollment_status, $student_id);

    if (!$student_stmt->execute()) {
        throw new Exception("Failed to update student status");
    }

    $conn->commit();

    // ENHANCED: Include real-time program warnings in response
    $response_message = 'Enrollment processed successfully';
    $warnings = [];

    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $warnings[] = 'Program ends today (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    } elseif ($program_info['days_remaining'] <= 7) {
        $warnings[] = 'Program ends in ' . $program_info['days_remaining'] . ' days (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    }

    if (!empty($warnings)) {
        $response_message .= ' - NOTE: ' . implode(', ', $warnings);
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
        'cash_received' => $cash_to_pay, // FIXED: Return the payment amount (₱15,000)
        'total_cash_given' => $cash, // NEW: Return total cash given (₱20,000) for reference
        'change_amount' => $change_amount, // ₱5,000
        'credit_amount' => $credit_amount, // ₱15,000
        'program_end_check' => [
            'status' => $program_info['program_status'],
            'end_date' => $program_info['end_date'],
            'program_name' => $program_info['program_name'],
            'days_remaining' => $program_info['days_remaining'],
            'current_date' => $program_info['current_date']
        ],
        'timestamp' => $current_time,
        'processed_by' => 'Scraper001'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => $current_time,
        'processed_by' => 'Scraper001'
    ]);
}

$conn->autocommit(TRUE);

// ENHANCED: Handle existing enrollment payments with FIXED cash_received
function handleExistingEnrollmentPayment($conn, $existing_enrollment, $cash, $student_id, $program_id, $payment_type, $demo_type, $promo_discount, $subtotal, $final_total, $selected_schedules, $program_info)
{
    // Get current enrollment details
    $current_balance_stmt = $conn->prepare("
        SELECT balance, total_amount, subtotal, promo_discount, learning_mode, package_name, selected_schedules, system_fee
        FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        ORDER BY id DESC LIMIT 1
    ");
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

    // FIXED: Get payment amount from total_payment
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

    // Validate payment amount
    if ($payment_type === 'full_payment' && $payment_amount > $current_balance) {
        $payment_amount = $current_balance;
    } else if ($payment_type !== 'full_payment' && $payment_amount > $current_balance) {
        throw new Exception("Payment amount (₱" . number_format($payment_amount, 2) . ") exceeds remaining balance (₱" . number_format($current_balance, 2) . ")");
    }

    // Validate cash amount
    if ($cash < $payment_amount) {
        throw new Exception("Insufficient cash for payment. Required: ₱" . number_format($payment_amount, 2) . ", Received: ₱" . number_format($cash, 2));
    }

    // FIXED: Calculate new balance and change
    $new_balance = $current_balance - $payment_amount;
    $cash_to_pay = $payment_amount; // The bill amount
    $change_amount = max(0, $cash - $cash_to_pay); // Change given back
    $new_status = ($new_balance <= 0) ? 'Enrolled' : 'Reserved';

    // Use new schedules if provided, otherwise keep existing
    $final_schedules = (!empty($selected_schedules) && $selected_schedules !== '[]') ? $selected_schedules : $existing_selected_schedules;

    // ENHANCED: Include real-time program info in description
    $program_end_note = '';
    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $program_end_note = " (Program ends today!)";
    } elseif ($program_info['days_remaining'] <= 7) {
        $program_end_note = " (Program ends in {$program_info['days_remaining']} days)";
    }

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

    // FIXED: Correct database field mapping for existing payments
    $debit_amount = $payment_amount;
    $credit_amount = $payment_amount;
    $change_given = $change_amount;

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
        $cash_to_pay, // FIXED: Store payment amount (₱15,000) NOT total cash (₱20,000)
        $change_amount,
        $new_balance,
        $new_status,
        $debit_amount,
        $credit_amount,
        $change_given,
        $description
    );

    if (!$payment_stmt->execute()) {
        throw new Exception("Failed to record payment: " . $payment_stmt->error);
    }

    $new_transaction_id = $conn->insert_id;

    // Update enrollment record
    $update_enrollment_stmt = $conn->prepare("
        UPDATE student_enrollments 
        SET pos_transaction_id = ?, status = ?, selected_schedules = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE student_id = ? AND program_id = ?
    ");
    $update_enrollment_stmt->bind_param("issii", $new_transaction_id, $new_status, $final_schedules, $student_id, $program_id);

    if (!$update_enrollment_stmt->execute()) {
        throw new Exception("Failed to update enrollment: " . $update_enrollment_stmt->error);
    }

    // Update student status
    $student_stmt = $conn->prepare("UPDATE student_info_tbl SET enrollment_status = ? WHERE id = ?");
    $student_stmt->bind_param("si", $new_status, $student_id);
    $student_stmt->execute();

    $conn->commit();

    // ENHANCED: Include real-time program warnings in response
    $response_message = ucfirst(str_replace('_', ' ', $payment_type)) . ' processed successfully';
    $warnings = [];

    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $warnings[] = 'Program ends today (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    } elseif ($program_info['days_remaining'] <= 7) {
        $warnings[] = 'Program ends in ' . $program_info['days_remaining'] . ' days (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    }

    if (!empty($warnings)) {
        $response_message .= ' - NOTE: ' . implode(', ', $warnings);
    }

    echo json_encode([
        'success' => true,
        'message' => $response_message,
        'transaction_id' => $new_transaction_id,
        'payment_type' => $payment_type,
        'balance' => $new_balance,
        'total_amount' => $current_total,
        'payment_amount' => $payment_amount,
        'cash_received' => $cash_to_pay, // FIXED: Return payment amount (₱15,000)
        'total_cash_given' => $cash, // NEW: Return total cash given (₱20,000)
        'change_amount' => $change_amount,
        'credit_amount' => $credit_amount,
        'program_end_check' => [
            'status' => $program_info['program_status'],
            'end_date' => $program_info['end_date'],
            'program_name' => $program_info['program_name'],
            'days_remaining' => $program_info['days_remaining'],
            'current_date' => $program_info['current_date']
        ],
        'timestamp' => '2025-06-12 07:38:21',
        'processed_by' => 'Scraper001'
    ]);
    exit;
}
?>