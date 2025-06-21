<?php
// functions/ajax/get_program.php
header('Content-Type: application/json');
include "../../../connection/connection.php";

try {
    $conn = con();

    // Check if learning_mode filter is requested
    $learningModeFilter = isset($_GET['learning_mode']) ? $_GET['learning_mode'] : null;

    // Base query
    $query = "SELECT id, program_name, learning_mode FROM program WHERE 1=1";

    // Add learning mode filter if provided
    if ($learningModeFilter) {
        $query .= " AND learning_mode = '" . mysqli_real_escape_string($conn, $learningModeFilter) . "'";
    }

    $query .= " ORDER BY program_name ASC";

    $result = $conn->query($query);

    $programs = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    }

    // Log for debugging
    error_log("Programs Query: " . $query);
    error_log("Learning Mode Filter: " . ($learningModeFilter ?: 'None'));
    error_log("Programs Found: " . count($programs));

    echo json_encode($programs);

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch programs: ' . $e->getMessage()]);
}
?>