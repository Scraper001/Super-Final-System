<?php
// functions/ajax/check_enrollment.php
header('Content-Type: application/json');
include "../../../connection/connection.php";

try {
    $conn = con();

    if (
        !isset($_POST['student_id']) || !isset($_POST['program_id']) ||
        empty($_POST['student_id']) || empty($_POST['program_id'])
    ) {
        echo json_encode(['error' => 'Student ID and Program ID are required']);
        exit;
    }

    $student_id = intval($_POST['student_id']);
    $program_id = intval($_POST['program_id']);

    // Check if student is already enrolled in this program
    $query = "
        SELECT COUNT(*) as count 
        FROM student_enrollments 
        WHERE student_id = ? 
        AND program_id = ? 
        AND status IN ('Enrolled', 'Reserved')
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $exists = $row['count'] > 0;

        echo json_encode([
            'exists' => $exists,
            'message' => $exists ? 'Student is already enrolled in this program' : 'Student can be enrolled'
        ]);
    } else {
        echo json_encode(['exists' => false, 'message' => 'Check completed']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to check enrollment: ' . $e->getMessage()]);
}
?>