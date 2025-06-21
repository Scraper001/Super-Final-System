<?php
// functions/ajax/get_program_details.php
header('Content-Type: application/json');
include "../../../connection/connection.php";

try {
    $conn = con();

    if (!isset($_GET['program_id']) || empty($_GET['program_id'])) {
        echo json_encode(['error' => 'Program ID is required']);
        exit;
    }

    $program_id = intval($_GET['program_id']);

    $query = "SELECT * FROM program WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $program = $result->fetch_assoc();
        echo json_encode($program);
    } else {
        echo json_encode(['error' => 'Program not found']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch program details: ' . $e->getMessage()]);
}
?>