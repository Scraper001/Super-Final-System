<?php
// functions/ajax/get_user_payments.php
include "../../../connection/connection.php";
$conn = con();

header('Content-Type: application/json');

if (!isset($_GET['student_id']) || !isset($_GET['program_id'])) {
    echo json_encode([]);
    exit;
}

$student_id = mysqli_real_escape_string($conn, $_GET['student_id']);
$program_id = mysqli_real_escape_string($conn, $_GET['program_id']);

try {
    // Get all payments for this student and program
    $query = "
        SELECT 
            pt.payment_type,
            pt.demo_type,
            pt.amount_paid,
            pt.payment_date,
            pt.cash_received,
            se.id as enrollment_id
        FROM pos_transactions pt
        JOIN student_enrollments se ON pt.id = se.pos_transaction_id
        WHERE se.student_id = '$student_id' 
        AND se.program_id = '$program_id'
        ORDER BY pt.payment_date ASC
    ";

    $result = $conn->query($query);
    $payments = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = [
                'payment_type' => $row['payment_type'],
                'demo_type' => $row['demo_type'],
                'amount_paid' => floatval($row['amount_paid']),
                'payment_date' => $row['payment_date'],
                'cash_received' => floatval($row['cash_received']),
                'enrollment_id' => $row['enrollment_id']
            ];
        }
    }

    echo json_encode($payments);

} catch (Exception $e) {
    error_log("Error fetching user payments: " . $e->getMessage());
    echo json_encode([]);
}

$conn->close();
?>