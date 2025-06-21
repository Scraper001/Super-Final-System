<?php
// functions/ajax/get_schedules.php
header('Content-Type: application/json');
include "../../../connection/connection.php";

try {
    $conn = con();

    if (!isset($_GET['program_id']) || empty($_GET['program_id'])) {
        echo json_encode(['error' => 'Program ID is required']);
        exit;
    }

    $program_id = intval($_GET['program_id']);

    // Get schedules and check if they're reserved
    $query = "
        SELECT s.*, 
               CASE 
                   WHEN EXISTS (
                       SELECT 1 FROM student_enrollments se 
                       WHERE JSON_CONTAINS(se.selected_schedules, JSON_OBJECT('id', CAST(s.id AS CHAR)))
                       AND se.status IN ('Reserved', 'Enrolled')
                   ) THEN '1' 
                   ELSE '0' 
               END as is_reserved
        FROM schedules s 
        WHERE s.program_id = ? 
        ORDER BY s.training_date ASC, s.start_time ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
    }

    echo json_encode($schedules);

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch schedules: ' . $e->getMessage()]);
}
?>