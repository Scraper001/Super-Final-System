<?php
// api/get_packages.php
header('Content-Type: application/json');
include "../../connection/connection.php";
$conn = con();

try {
    $program_id = $_GET['program_id'] ?? '';

    if (empty($program_id)) {
        echo json_encode([]);
        exit;
    }

    $query = "SELECT * FROM promo WHERE program_id = ? ORDER BY package_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $packages = [];
    while ($row = $result->fetch_assoc()) {
        $packages[] = [
            'id' => $row['id'],
            'package_name' => $row['package_name'],
            'enrollment_fee' => $row['enrollment_fee'],
            'percentage' => $row['percentage'],
            'promo_type' => $row['promo_type']
        ];
    }

    echo json_encode($packages);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>