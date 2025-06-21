<?php
// functions/ajax/get_program.php
header('Content-Type: application/json');
include "../../connection/connection.php";

try {
    $conn = con();

    $query = "SELECT id, program_name FROM program ORDER BY program_name ASC";
    $result = $conn->query($query);

    $programs = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
    }

    echo json_encode($programs);

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch programs: ' . $e->getMessage()]);
}
?>