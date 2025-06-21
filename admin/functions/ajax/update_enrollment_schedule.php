<?php
// Create this file: functions/ajax/update_enrollment_schedule.php
include "../../../connection/connection.php";
$conn = con();

header('Content-Type: application/json');

try {
    $student_id = $_POST['student_id'] ?? null;
    $program_id = $_POST['program_id'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;
    $timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');
    $updated_by = $_POST['updated_by'] ?? 'System';

    if (!$student_id || !$program_id) {
        throw new Exception("Student ID and Program ID are required");
    }

    // Update the enrollment record with new schedule selection
    $stmt = $conn->prepare("
        UPDATE student_enrollments 
        SET 
            selected_schedules = ?,
            updated_at = CURRENT_TIMESTAMP,
            schedule_updated_at = CURRENT_TIMESTAMP
        WHERE student_id = ? AND program_id = ?
    ");

    $stmt->bind_param("sii", $selected_schedules, $student_id, $program_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Also update the most recent POS transaction
            $pos_stmt = $conn->prepare("
                UPDATE pos_transactions 
                SET selected_schedules = ?
                WHERE student_id = ? AND program_id = ?
                ORDER BY id DESC LIMIT 1
            ");

            $pos_stmt->bind_param("sii", $selected_schedules, $student_id, $program_id);
            $pos_stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'student_id' => $student_id,
                'program_id' => $program_id,
                'timestamp' => $timestamp,
                'updated_by' => $updated_by,
                'affected_rows' => $stmt->affected_rows
            ]);
        } else {
            throw new Exception("No enrollment record found to update");
        }
    } else {
        throw new Exception("Failed to update schedule: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>