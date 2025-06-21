<?php

// api/get_schedules.php
header('Content-Type: application/json');
include "../../connection/connection.php";
$conn = con();

try {
    $program_id = $_GET['program_id'] ?? '';

    if (empty($program_id)) {
        echo json_encode([]);
        exit;
    }

    // Get schedules and check if they're locked (reserved by other students)
    $query = "
        SELECT s.*, 
               CASE 
                   WHEN EXISTS (
                       SELECT 1 FROM pos_transactions pt 
                       WHERE pt.program_id = s.program_id 
                       AND JSON_CONTAINS(pt.selected_schedules, JSON_OBJECT('id', s.id))
                       AND pt.status = 'Active'
                       AND pt.enrollment_status = 'Reserved'
                   ) THEN 1 
                   ELSE 0 
               END as is_locked
        FROM schedules s 
        WHERE s.program_id = ? 
        ORDER BY s.training_date, s.start_time
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    echo json_encode($schedules);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>