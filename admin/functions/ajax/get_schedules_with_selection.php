<?php
include "../../../connection/connection.php";
$conn = con();

$program_id = $_GET['program_id'] ?? '';
$student_id = $_GET['student_id'] ?? '';

if (!$program_id) {
    echo json_encode([]);
    exit;
}

// Get all schedules for the program
$schedules_sql = "SELECT * FROM schedules WHERE program_id = '$program_id'";
$schedules_result = $conn->query($schedules_sql);

$schedules = [];
while ($row = $schedules_result->fetch_assoc()) {
    $schedules[] = $row;
}

// If student_id is provided, check for existing selections
if ($student_id && !empty($schedules)) {
    // Get existing selected schedules from pos_transactions or student_enrollments
    $selection_sql = "SELECT selected_schedules FROM pos_transactions 
                     WHERE student_id = '$student_id' AND program_id = '$program_id' 
                     ORDER BY created_at DESC LIMIT 1";
    $selection_result = $conn->query($selection_sql);

    if ($selection_result->num_rows > 0) {
        $selection_row = $selection_result->fetch_assoc();
        $selected_schedules_json = $selection_row['selected_schedules'];

        if ($selected_schedules_json) {
            try {
                $selected_schedules = json_decode($selected_schedules_json, true);
                $selected_ids = array_column($selected_schedules, 'id');

                // Mark schedules as selected
                foreach ($schedules as &$schedule) {
                    $schedule['is_selected'] = in_array($schedule['id'], $selected_ids);
                }
            } catch (Exception $e) {
                // If JSON parsing fails, treat as no selections
                foreach ($schedules as &$schedule) {
                    $schedule['is_selected'] = false;
                }
            }
        }
    }
}

// Set default selection status if not set
foreach ($schedules as &$schedule) {
    if (!isset($schedule['is_selected'])) {
        $schedule['is_selected'] = false;
    }
}

header('Content-Type: application/json');
echo json_encode($schedules);
?>