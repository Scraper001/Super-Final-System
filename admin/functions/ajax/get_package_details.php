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

        // Add selection type handling for display in POS
        $selection_type = intval($package['selection_type'] ?? 1);
        $package['selection_type_display'] = "Option " . $selection_type;

        if ($selection_type <= 2) {
            $package['calculation_method'] = 'Percentage-based';
            $package['display_info'] = "Automatic " . $package['percentage'] . "% discount";
        } else {
            $package['calculation_method'] = 'Manual payment';
            $package['display_info'] = "Required initial: ₱" . number_format($package['custom_initial_payment'] ?? 0, 2);
        }

        echo json_encode($package);
    } else {
        echo json_encode(['error' => 'Package not found']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch package details: ' . $e->getMessage()]);
}
?>