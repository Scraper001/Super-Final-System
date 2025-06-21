<?php
include "../../connection/connection.php";
$conn = con();

// Validate POST data
if (
    !isset($_POST['id']) ||
    !isset($_POST['Program_name']) ||
    !isset($_POST['Trainor_ID']) ||
    !isset($_POST['schedule']) ||
    !isset($_POST['openningClasses']) ||
    !isset($_POST['clossingClasses'])
) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$id = $_POST['id'];
$program_name = $_POST['Program_name'];
$trainor_id = $_POST['Trainor_ID'];
$schedule_raw = $_POST['schedule'];
$class_schedule = json_decode($schedule_raw, true);

// Fallback if decoding fails or input is just a plain string
if (!is_array($class_schedule)) {
    $class_schedule_str = $schedule_raw;
} else {
    $class_schedule_str = implode(", ", $class_schedule);
}

$opening = $_POST['openningClasses'];
$closing = $_POST['clossingClasses'];

// Update query using prepared statement
$stmt = $conn->prepare("UPDATE programs SET program_name = ?, trainor_id = ?, class_schedule = ?, OpeningClasses = ?, ClossingClasses = ? WHERE id = ?");
$stmt->bind_param("sssssi", $program_name, $trainor_id, $class_schedule_str, $opening, $closing, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
