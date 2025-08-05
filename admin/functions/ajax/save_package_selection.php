<?php
include '../../../connection/connection.php';
$conn = con();

$student_id = $_POST['student_id'] ?? null;
$program_id = $_POST['program_id'] ?? null;
$package_data = $_POST['package_data'] ?? null;
$timestamp = $_POST['timestamp'] ?? null;
$user = $_POST['user'] ?? 'System';

if (!$student_id || !$program_id || !$package_data) {
    die(json_encode(['success' => false, 'message' => 'Missing required data']));
}

// Save to database
$stmt = $conn->prepare("
    INSERT INTO student_package_selections 
    (student_id, program_id, package_data, created_at, created_by)
    VALUES (?, ?, ?, NOW(), ?)
    ON DUPLICATE KEY UPDATE
    package_data = ?, updated_at = NOW(), updated_by = ?
");

$stmt->bind_param(
    "iissss",
    $student_id,
    $program_id,
    $package_data,
    $user,
    $package_data,
    $user
);

$success = $stmt->execute();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Package selection saved' : 'Failed to save package selection'
]);