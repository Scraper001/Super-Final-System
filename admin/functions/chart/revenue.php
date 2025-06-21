<?php
// api/revenue-data.php - Backend endpoint to fetch revenue data from the database
include "../../../connection/connection.php";
$conn = con();


$date = $_GET['date'];
$filter = $_GET['filter'];

$where = "";
$group = "";
$params = [];

// Normalize payment statuses
$valid_status = "TRIM(LOWER(payment_status)) IN ('initial', 'full payment')";

switch ($filter) {
    case "daily":
        $where = "DATE(date_created) = ?";
        $group = "DATE(date_created)";
        $params[] = $date;
        break;

    case "weekly":
        $start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
        $end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
        $where = "DATE(date_created) BETWEEN ? AND ?";
        $group = "DATE(date_created)";
        $params[] = $start;
        $params[] = $end;
        break;

    case "monthly":
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));
        $where = "MONTH(date_created) = ? AND YEAR(date_created) = ?";
        $group = "DATE(date_created)";
        $params[] = $month;
        $params[] = $year;
        break;

    case "annually":
        $year = date('Y', strtotime($date));
        $where = "YEAR(date_created) = ?";
        $group = "MONTH(date_created)";
        $params[] = $year;
        break;
}

// Build the query
$sql = "SELECT $group AS label, SUM(COALESCE(cash, 0)) AS total
        FROM POS
        WHERE $where AND $valid_status
        GROUP BY $group
        ORDER BY label ASC";

$stmt = $conn->prepare($sql);

// Dynamically bind params
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$data = ["labels" => [], "values" => []];
while ($row = $result->fetch_assoc()) {
    $data["labels"][] = $row["label"];
    $data["values"][] = (int) $row["total"];
}

header('Content-Type: application/json');
echo json_encode($data);