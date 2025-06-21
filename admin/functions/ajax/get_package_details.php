<?php
// functions/ajax/get_package_details.php
header('Content-Type: application/json');
include "../../../connection/connection.php";

try {
    $conn = con();

    if (
        !isset($_GET['program_id']) || !isset($_GET['package_name']) ||
        empty($_GET['program_id']) || empty($_GET['package_name'])
    ) {
        echo json_encode(['error' => 'Program ID and Package Name are required']);
        exit;
    }

    $program_id = intval($_GET['program_id']);
    $package_name = $_GET['package_name'];

    $query = "SELECT * FROM promo WHERE program_id = ? AND package_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $program_id, $package_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $package = $result->fetch_assoc();
        echo json_encode($package);
    } else {
        echo json_encode(['error' => 'Package not found']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch package details: ' . $e->getMessage()]);
}
?>