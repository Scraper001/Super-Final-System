<?php
include "../../../connection/connection.php";
$conn = con();

if ($_POST['is_edit']) {
    $enrollment_id = $_POST['enrollment_id'];
    $pos_transaction_id = $_POST['pos_transaction_id'];

    // Update student_enrollments table
    $update_enrollment = $conn->prepare("UPDATE student_enrollments SET 
        learning_mode = ?, 
        selected_schedules = ?, 
        updated_at = NOW() 
        WHERE id = ?");

    $update_enrollment->bind_param(
        "ssi",
        $_POST['learning_mode'],
        $_POST['selected_schedules'],
        $enrollment_id
    );

    // Update pos_transactions table
    $update_transaction = $conn->prepare("UPDATE pos_transactions SET 
        learning_mode = ?,
        package_name = ?,
        payment_type = ?,
        demo_type = ?,
        selected_schedules = ?,
        total_amount = ?,
        cash_received = ?,
        change_amount = ?,
        updated_at = NOW()
        WHERE id = ?");

    $update_transaction->bind_param(
        "sssssdddi",
        $_POST['learning_mode'],
        $_POST['package_name'],
        $_POST['type_of_payment'],
        $_POST['demo_type'],
        $_POST['selected_schedules'],
        $_POST['final_total'],
        $_POST['cash'],
        $_POST['change'],
        $pos_transaction_id
    );

    if ($update_enrollment->execute() && $update_transaction->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update enrollment']);
    }
}
?>