<?php
include "../../connection/connection.php";
$conn = con();

// Get JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);
$student_id = $data['student_id'] ?? null;

// Validate student ID
if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Get photo path before deleting (to delete the file)
    $stmt = $conn->prepare("SELECT photo_path FROM student_info_tbl WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $photo_path = $row['photo_path'];

        // Delete the student record
        $stmt = $conn->prepare("DELETE FROM student_info_tbl WHERE id = ?");
        $stmt->bind_param("i", $student_id);

        if ($stmt->execute()) {
            // Delete the photo file if it exists
            if ($photo_path && file_exists($photo_path)) {
                unlink($photo_path);
            }

            // Commit the transaction
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            // Rollback on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to delete student record']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
} catch (Exception $e) {
    // Rollback on exception
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
$conn->close();
?>