<?php
// functions/ajax/get_student_transactions.php
header('Content-Type: application/json');
include "../../../connection/connection.php";

try {
    $conn = con();

    if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
        echo json_encode(['error' => 'Student ID is required']);
        exit;
    }

    $student_id = intval($_GET['student_id']);

    $query = "
        SELECT 
            pt.id,
            pt.transaction_date,
            pt.payment_type,
            pt.demo_type,
            pt.cash_received as amount_paid,
            pt.total_amount,
            pt.balance,
            pt.status,
            p.program_name,
            CASE 
                WHEN pt.payment_type = 'full_payment' THEN 'Full Payment'
                WHEN pt.payment_type = 'initial_payment' THEN 'Initial Payment'
                WHEN pt.payment_type = 'demo_payment' THEN CONCAT('Demo Payment - ', pt.demo_type)
                WHEN pt.payment_type = 'reservation' THEN 'Reservation Fee'
                ELSE pt.payment_type
            END as payment_description
        FROM pos_transactions pt
        LEFT JOIN program p ON pt.program_id = p.id
        WHERE pt.student_id = ? 
        AND pt.status = 'Active'
        ORDER BY pt.transaction_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }

    echo json_encode($transactions);

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch student transactions: ' . $e->getMessage()]);
}
?>