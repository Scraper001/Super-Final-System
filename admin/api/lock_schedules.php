<?php
// api/lock_schedules.php
header('Content-Type: application/json');
include "../../connection/connection.php";
$conn = con();

try {
    $student_id = $_POST['student_id'] ?? '';
    $program_id = $_POST['program_id'] ?? '';
    $selected_schedules = $_POST['selected_schedules'] ?? '';
    $payment_type = $_POST['payment_type'] ?? '';

    if ($payment_type === 'reservation' && !empty($selected_schedules)) {
        $schedules = json_decode($selected_schedules, true);

        if (is_array($schedules)) {
            foreach ($schedules as $schedule) {
                // Mark schedule as reserved (this could be a separate table or flag)
                $lock_query = $conn->prepare("
                    INSERT INTO schedule_locks (schedule_id, student_id, program_id, locked_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE locked_at = NOW()
                ");

                if (isset($schedule['id'])) {
                    $lock_query->bind_param("iii", $schedule['id'], $student_id, $program_id);
                    $lock_query->execute();
                }
            }
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>