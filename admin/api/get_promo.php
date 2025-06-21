<?php
// api/get_promo.php
header('Content-Type: application/json');
include "../../connection/connection.php";
$conn = con();

try {
    $program_id = $_GET['program_id'] ?? '';

    if (empty($program_id)) {
        echo json_encode([]);
        exit;
    }

    $query = "SELECT * FROM promo WHERE program_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $promos = [];
    while ($row = $result->fetch_assoc()) {
        $promos[] = $row;
    }

    echo json_encode($promos);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>