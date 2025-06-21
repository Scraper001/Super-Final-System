<?php
// api/get_student_transactions.php
header('Content-Type: application/json');
include "../../connection/connection.php";
$conn = con();

try {
    $student_id = $_GET['student_id'] ?? '';

    if (empty($student_id)) {
        echo json_encode([]);
        exit;
    }

    $query = "
        SELECT 
            pt.*,
            p.program_name,
            DATE_FORMAT(pt.transaction_date, '%M %d, %Y') as formatted_date,
            CASE 
                WHEN pt.balance > 0 THEN 'Unpaid Balance'
                WHEN pt.balance = 0 AND pt.payment_type != 'full_payment' THEN 'Partially Paid'
                ELSE 'Fully Paid'
            END as payment_status
        FROM pos_transactions pt
        JOIN program p ON pt.program_id = p.id
        WHERE pt.student_id = ?
        ORDER BY pt.transaction_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    echo json_encode($transactions);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>