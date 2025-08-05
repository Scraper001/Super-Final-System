<?php
include '../../../connection/connection.php';
$conn = con();

$student_id = $_GET['student_id'] ?? null;
$program_id = $_GET['program_id'] ?? null;

if (!$student_id || !$program_id) {
    die(json_encode(['success' => false, 'message' => 'Missing required data']));
}

$stmt = $conn->prepare("
    SELECT package_data, created_at, created_by
    FROM student_package_selections
    WHERE student_id = ? AND program_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$stmt->bind_param("ii", $student_id, $program_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'package_data' => $data ? $data['package_data'] : null,
    'created_at' => $data ? $data['created_at'] : null,
    'created_by' => $data ? $data['created_by'] : null
]);