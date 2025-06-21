<?php
// api/process_payment.php
header('Content-Type: application/json');
include "../connection/connection.php";
$conn = con();

try {
    $conn->begin_transaction();

    // Get form data
    $student_id = $_POST['student_id'] ?? '';
    $learning_mode = $_POST['learning_mode'] ?? '';
    $program_id = $_POST['program_id'] ?? '';
    $package_id = $_POST['package_id'] ?? '';
    $payment_type = $_POST['type_of_payment'] ?? '';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? '';
    $cash = floatval($_POST['cash'] ?? 0);
    $cash_to_pay = floatval($_POST['cash_to_pay'] ?? 0);
    $total_payment = floatval($_POST['total_payment'] ?? 0);
    $change = floatval($_POST['change'] ?? 0);

    $program_details = json_decode($_POST['program_details'] ?? '{}', true);
    $package_details = json_decode($_POST['package_details'] ?? '{}', true);
    $promo_applied = json_decode($_POST['promo_applied'] ?? '{}', true);

    // Validation
    if (empty($student_id) || empty($program_id) || empty($payment_type)) {
        throw new Exception('Missing required fields');
    }

    if ($cash < $cash_to_pay) {
        throw new Exception('Insufficient cash amount');
    }

    // Check if student has outstanding balance
    $balance_check = $conn->prepare("SELECT SUM(balance) as total_balance FROM pos_transactions WHERE student_id = ? AND balance > 0");
    $balance_check->bind_param("i", $student_id);
    $balance_check->execute();
    $balance_result = $balance_check->get_result()->fetch_assoc();
    $existing_balance = floatval($balance_result['total_balance'] ?? 0);

    // Calculate fees
    $assessment_fee = floatval($program_details['assesment_fee'] ?? 0);
    $tuition_fee = floatval($program_details['tuition_fee'] ?? 0);
    $misc_fee = floatval($program_details['misc_fee'] ?? 0);
    $other_fees = floatval($program_details['ojt_fee'] ?? 0) +
        floatval($program_details['uniform_fee'] ?? 0) +
        floatval($program_details['id_fee'] ?? 0) +
        floatval($program_details['book_fee'] ?? 0) +
        floatval($program_details['kit_fee'] ?? 0);

    $subtotal = $assessment_fee + $tuition_fee + $misc_fee + $other_fees;

    // Calculate promo discount
    $promo_discount = 0;
    if ($promo_applied && $subtotal >= floatval($promo_applied['enrollment_fee'] ?? 0)) {
        $promo_discount = floor(($subtotal * floatval($promo_applied['percentage'] ?? 0)) / 100);
    }

    $total_amount = $subtotal - $promo_discount;
    $new_balance = ($total_amount + $existing_balance) - $cash_to_pay;

    // Determine enrollment status
    $enrollment_status = ($payment_type === 'reservation') ? 'Reserved' : 'Enrolled';

    // Get package name
    $package_name = $package_details['package_name'] ?? null;

    // Insert into pos_transactions
    $insert_transaction = $conn->prepare("
        INSERT INTO pos_transactions (
            student_id, program_id, learning_mode, package_name, payment_type, demo_type,
            selected_schedules, subtotal, promo_discount, total_amount, cash_received,
            change_amount, balance, enrollment_status, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
    ");

    $insert_transaction->bind_param(
        "iissssdddddds",
        $student_id,
        $program_id,
        $learning_mode,
        $package_name,
        $payment_type,
        $demo_type,
        $selected_schedules,
        $subtotal,
        $promo_discount,
        $total_amount,
        $cash,
        $change,
        $new_balance,
        $enrollment_status
    );

    if (!$insert_transaction->execute()) {
        throw new Exception('Failed to insert transaction');
    }

    $transaction_id = $conn->insert_id;

    // Insert into student_enrollments
    $insert_enrollment = $conn->prepare("
        INSERT INTO student_enrollments (
            student_id, program_id, pos_transaction_id, learning_mode, 
            selected_schedules, status
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insert_enrollment->bind_param(
        "iiisss",
        $student_id,
        $program_id,
        $transaction_id,
        $learning_mode,
        $selected_schedules,
        $enrollment_status
    );

    if (!$insert_enrollment->execute()) {
        throw new Exception('Failed to insert enrollment');
    }

    // Update student_info_tbl enrollment status and schedule
    $update_student = $conn->prepare("
        UPDATE student_info_tbl 
        SET enrollment_status = ?, schedule = ?, programs = ?
        WHERE id = ?
    ");

    $program_name = $program_details['program_name'] ?? '';
    $update_student->bind_param(
        "sssi",
        $enrollment_status,
        $selected_schedules,
        $program_name,
        $student_id
    );

    if (!$update_student->execute()) {
        throw new Exception('Failed to update student info');
    }

    // If there was an existing balance, create a credit transaction
    if ($existing_balance > 0) {
        $credit_transaction = $conn->prepare("
            INSERT INTO pos_transactions (
                student_id, program_id, learning_mode, package_name, payment_type,
                selected_schedules, subtotal, total_amount, cash_received,
                change_amount, balance, enrollment_status, status
            ) VALUES (?, ?, 'Credit', 'Balance Transfer', 'balance_transfer', 
                     '', ?, ?, 0, 0, 0, 'Credit Applied', 'Active')
        ");

        $credit_transaction->bind_param(
            "iidd",
            $student_id,
            $program_id,
            $existing_balance,
            $existing_balance
        );

        $credit_transaction->execute();

        // Update previous balances to 0
        $clear_balances = $conn->prepare("
            UPDATE pos_transactions 
            SET balance = 0 
            WHERE student_id = ? AND balance > 0 AND id != ?
        ");
        $clear_balances->bind_param("ii", $student_id, $transaction_id);
        $clear_balances->execute();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'transaction_id' => $transaction_id,
        'enrollment_status' => $enrollment_status,
        'balance' => $new_balance
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>