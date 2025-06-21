<?php
// functions/ajax/get_packages.php
header('Content-Type: application/json');
include "../../../connection/connection.php";

try {
    $conn = con();

    if (!isset($_GET['program_id']) || empty($_GET['program_id'])) {
        echo json_encode(['error' => 'Program ID is required']);
        exit;
    }

    $program_id = intval($_GET['program_id']);

    $query = "SELECT DISTINCT 
                p.package_name,
                pr.learning_mode,
                CASE 
                    WHEN UPPER(pr.learning_mode) LIKE '%ONLINE%' OR UPPER(pr.learning_mode) LIKE '%VIRTUAL%' THEN 'ONLINE'
                    WHEN UPPER(pr.learning_mode) LIKE '%FACE%' OR UPPER(pr.learning_mode) LIKE '%F2F%' OR UPPER(pr.learning_mode) LIKE '%PHYSICAL%' THEN 'F2F'
                    ELSE pr.learning_mode
                END as mode_description
              FROM promo p 
              JOIN program pr ON p.program_id = pr.id 
              WHERE p.program_id = ? 
              ORDER BY p.package_name ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $packages = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
    }

    echo json_encode($packages);

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch packages: ' . $e->getMessage()]);
}
?>