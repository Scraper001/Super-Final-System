<?php
// 3. Create update_selected_schedules.php for saving schedule changes
// functions/ajax/update_selected_schedules.php

include "../../../connection/connection.php";
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $program_id = $_POST['program_id'];
    $selected_schedules = $_POST['selected_schedules'];

    try {
        // Update the most recent enrollment record
        $update_sql = "
            UPDATE student_enrollments 
            SET selected_schedules = ?, updated_at = NOW() 
            WHERE student_id = ? AND program_id = ? 
            ORDER BY enrollment_date DESC 
            LIMIT 1
        ";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sii", $selected_schedules, $student_id, $program_id);

        if ($stmt->execute()) {
            // Also update the student_info_tbl schedule field if needed
            $update_student_sql = "UPDATE student_info_tbl SET schedule = ? WHERE id = ?";
            $stmt2 = $conn->prepare($update_student_sql);
            $stmt2->bind_param("si", $selected_schedules, $student_id);
            $stmt2->execute();

            echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
        } else {
            throw new Exception('Failed to update schedule');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>