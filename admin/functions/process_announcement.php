<?php
// Include database connection
include "../../connection/connection.php";
$conn = con();

// Initialize response variables
$response = [
    'status' => 'error',
    'message' => 'Unknown action'
];

// Ensure action is set
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Create announcement
    if ($action == 'create') {
        // Validate inputs
        if (empty($_POST['message'])) {
            $response['message'] = 'Message is required';
        } else {
            $message = $_POST['message'];
            $recipientType = $_POST['recipientType'];

            // Determine the group based on recipient type
            $group = 'all'; // Default

            if ($recipientType == 'program' && !empty($_POST['program'])) {
                $group = 'program:' . $_POST['program'];
            } elseif ($recipientType == 'student' && !empty($_POST['studentNumber'])) {
                $group = 'student:' . $_POST['studentNumber'];
            }

            // Get last announcement_id to increment
            $maxIdQuery = "SELECT MAX(announcement_id) as max_id FROM announcement";
            $maxIdResult = $conn->query($maxIdQuery);
            $maxIdRow = $maxIdResult->fetch_assoc();
            $announcement_id = ($maxIdRow['max_id'] ?? 0) + 1;

            // Get user_no from session (replace with your actual session variable)
            $user_no = $_SESSION['user_id'] ?? 1; // Default to 1 if not set

            // Insert into database
            $insertQuery = "INSERT INTO announcement (announcement_id, user_no, `group`, message) 
                            VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param('iiss', $announcement_id, $user_no, $group, $message);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Announcement created successfully'
                ];

                // Set session message for display
                $_SESSION['success_message'] = 'Announcement created successfully!';
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to create announcement: ' . $conn->error
                ];

                // Set session error message
                $_SESSION['error_message'] = 'Failed to create announcement: ' . $conn->error;
            }

            $stmt->close();
        }
    }

    // Update announcement
    elseif ($action == 'update') {
        // Validate inputs
        if (empty($_POST['id']) || empty($_POST['message'])) {
            $response['message'] = 'ID and message are required';
        } else {
            $id = $_POST['id'];
            $message = $_POST['message'];
            $recipientType = $_POST['recipientType'];

            // Determine the group based on recipient type
            $group = 'all'; // Default

            if ($recipientType == 'program' && !empty($_POST['program'])) {
                $group = 'program:' . $_POST['program'];
            } elseif ($recipientType == 'student' && !empty($_POST['studentNumber'])) {
                $group = 'student:' . $_POST['studentNumber'];
            }

            // Update the record
            $updateQuery = "UPDATE announcement SET `group` = ?, message = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param('ssi', $group, $message, $id);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Announcement updated successfully'
                ];

                // Set session message for display
                $_SESSION['success_message'] = 'Announcement updated successfully!';
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update announcement: ' . $conn->error
                ];

                // Set session error message
                $_SESSION['error_message'] = 'Failed to update announcement: ' . $conn->error;
            }

            $stmt->close();
        }
    }

    // Delete announcement
    elseif ($action == 'delete') {
        // Validate ID
        if (empty($_POST['id'])) {
            $response['message'] = 'ID is required';
        } else {
            $id = $_POST['id'];

            // Delete the record
            $deleteQuery = "DELETE FROM announcement WHERE id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Announcement deleted successfully'
                ];

                // Set session message for display
                $_SESSION['success_message'] = 'Announcement deleted successfully!';
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to delete announcement: ' . $conn->error
                ];

                // Set session error message
                $_SESSION['error_message'] = 'Failed to delete announcement: ' . $conn->error;
            }

            $stmt->close();
        }
    }
}

// Close database connection
$conn->close();

// Redirect back to announcements page
header('Location: ../announcement.php');
exit;
?>