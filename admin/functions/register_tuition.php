<?php

include '../../connection/connection.php';
$conn = con();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program = $_POST['program'] ?? '';
    $package = $_POST['package_number'] ?? '';
    $tuition = $_POST['tuition_fee'] ?? 0;
    $misc = $_POST['misc_fee'] ?? 0;
    $ojt = $_POST['ojt_fee_and_medical'] ?? 0;
    $system = $_POST['system_fee'] ?? 0;
    $assessment = $_POST['assessment_fee'] ?? 0;
    $uniform = $_POST['uniform_scrub_and_polo_drifit'] ?? 0;
    $id = $_POST['id'] ?? 0;
    $books = $_POST['books'] ?? 0;
    $kit = $_POST['kit'] ?? 0;

    $demo1 = $_POST['demo1'] ?? 0;
    $demo2 = $_POST['demo2'] ?? 0;
    $demo3 = $_POST['demo3'] ?? 0;
    $demo4 = $_POST['demo4'] ?? 0;
    $total = $_POST['totalbasedtuition'] ?? 0;



    //this is reservation Fee

    $reservation_fee = $_POST['reservation_fee'];

    // Check for SQL query execution errors
    if ($conn->error) {
        die("Connection error: " . $conn->error);
    }

    // Make sure to use the correct table name (tuitionfee, not truitionfee)
    // And match column names exactly as they appear in the table

    $sql = "INSERT INTO tuitionfee (program_name, package_number, `tuition Fee`, `misc Fee`, ojtmedical, `system Fee`, `assessment Fee`, uniform, IDfee, books, kit, demo1, demo2, demo3, demo4, totalbasedtuition, reservation_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Check if prepare succeeded
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssiiiiiiiiiiiiiii", $program, $package, $tuition, $misc, $ojt, $system, $assessment, $uniform, $id, $books, $kit, $demo1, $demo2, $demo3, $demo4, $total, $reservation_fee);

    // Execute and check for errors
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }


    date_default_timezone_set('Asia/Manila');
    $date_created2 = date('Y-m-d H:i:s');
    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Added Program: $program', '$date_created2' )");

    $stmt->close();
    $conn->close();
    echo "success";
}
?>