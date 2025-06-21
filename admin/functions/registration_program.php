<?php
include "../../connection/connection.php";
$conn = con();

// Check if required fields are set
if (!isset($_POST['program_name']) || !isset($_POST['trainor_id']) || !isset($_POST['class_schedule']) || !isset($_POST['OpeningClasses']) || !isset($_POST['ClosingClasses'])) {
    echo "Error: Missing required fields";
    exit;
}

$program_name = $_POST['program_name'];
$trainor_id = $_POST['trainor_id'];

// Decode JSON string to array, then join with commas
$class_schedule = json_decode($_POST['class_schedule']);
if (!is_array($class_schedule) || empty($class_schedule)) {
    echo "Error: Invalid or empty class schedule";
    exit;
}
$class_schedule_str = implode(", ", $class_schedule);


$OpeningClasses = $_POST['OpeningClasses'];
$ClosingClasses = $_POST['ClosingClasses'];
// Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare("INSERT INTO programs (program_name, trainor_id, class_schedule,OpeningClasses,ClossingClasses) VALUES (?, ?, ?, ?,?)");
$stmt->bind_param("sssss", $program_name, $trainor_id, $class_schedule_str, $OpeningClasses, $ClosingClasses);

if ($stmt->execute()) {
    echo "Program added successfully";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>