<?php
include "../../connection/connection.php";
$conn = con();

$student_id = intval($_GET['student_id'] ?? 0);
$program_id = intval($_GET['program_id'] ?? 0);
$out = ['has_initial_payment' => false];

if ($student_id && $program_id) {
    $sql = "SELECT COUNT(*) as cnt FROM pos_transactions WHERE student_id = ? AND program_id = ? AND payment_type = 'initial_payment'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $out['has_initial_payment'] = (intval($result['cnt']) > 0);
    $stmt->close();
}
header('Content-Type: application/json');
echo json_encode($out);