<?php
// api/get_transaction_receipt.php
header('Content-Type: application/json');
include "../../connection/connection.php";
$conn = con();

try {
    $transaction_id = $_GET['transaction_id'] ?? '';

    if (empty($transaction_id)) {
        echo json_encode(['error' => 'Transaction ID required']);
        exit;
    }

    // Get transaction details with student and program info
    $query = "
        SELECT 
            pt.*,
            si.first_name, si.last_name, si.middle_name,
            p.program_name, p.assesment_fee, p.tuition_fee, p.misc_fee,
            p.ojt_fee, p.uniform_fee, p.id_fee, p.book_fee, p.kit_fee
        FROM pos_transactions pt
        JOIN student_info_tbl si ON pt.student_id = si.id
        JOIN program p ON pt.program_id = p.id
        WHERE pt.id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>