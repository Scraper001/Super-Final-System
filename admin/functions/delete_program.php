<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to capture any unexpected output
ob_start();

// Debug: Log the start of the script
error_log("DELETE SCRIPT START - " . date('Y-m-d H:i:s'));

try {
    // Debug: Check if connection file exists
    if (!file_exists("../connection/connection.php")) {
        throw new Exception("Connection file not found at ../connection/connection.php");
    }

    error_log("Including connection file...");
    include "../connection/connection.php";

    // Debug: Check if function exists
    if (!function_exists('con')) {
        throw new Exception("Function 'con()' not found in connection file");
    }

    error_log("Calling con() function...");
    $conn = con();

    // Debug: Log connection status
    if (!$conn) {
        throw new Exception("Database connection failed - con() returned null/false");
    }

    error_log("Database connected successfully");

    // Debug: Check request method
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    }

    // Debug: Log POST data
    error_log("POST data: " . json_encode($_POST));

    // Debug: Check if ID is provided
    if (!isset($_POST['id'])) {
        throw new Exception("ID parameter not set in POST data");
    }

    if (empty($_POST['id'])) {
        throw new Exception("ID parameter is empty");
    }

    $id = $_POST['id'];
    error_log("ID to delete: " . $id);

    // Debug: Validate ID is numeric
    if (!is_numeric($id) || $id <= 0) {
        throw new Exception("Invalid ID format. Expected positive number, got: " . $id);
    }

    error_log("ID validation passed");

    // Debug: Check if record exists before deletion
    $checkSql = "SELECT COUNT(*) as count FROM program WHERE id = ?";
    error_log("Preparing check query: " . $checkSql);
    $checkStmt = $conn->prepare($checkSql);

    if (!$checkStmt) {
        throw new Exception("Prepare failed for check query. MySQL Error: " . $conn->error);
    }

    error_log("Check statement prepared successfully");
    $checkStmt->bind_param("i", $id);

    if (!$checkStmt->execute()) {
        throw new Exception("Check query execution failed. MySQL Error: " . $checkStmt->error);
    }

    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();

    error_log("Record count for ID $id: " . $row['count']);

    if ($row['count'] == 0) {
        throw new Exception("Record with ID $id does not exist in the database");
    }

    $checkStmt->close();
    error_log("Record exists, proceeding with student relationship checks");

    // NEW: Check if program has any transactions in pos_transactions
    $transactionCheckSql = "SELECT COUNT(*) as transaction_count FROM pos_transactions WHERE program_id = ?";
    error_log("Preparing transaction check query: " . $transactionCheckSql);
    $transactionStmt = $conn->prepare($transactionCheckSql);

    if (!$transactionStmt) {
        throw new Exception("Prepare failed for transaction check query. MySQL Error: " . $conn->error);
    }

    $transactionStmt->bind_param("i", $id);

    if (!$transactionStmt->execute()) {
        throw new Exception("Transaction check query execution failed. MySQL Error: " . $transactionStmt->error);
    }

    $transactionResult = $transactionStmt->get_result();
    $transactionRow = $transactionResult->fetch_assoc();
    $transactionCount = $transactionRow['transaction_count'];

    error_log("Transaction count for program ID $id: " . $transactionCount);

    // NEW: Check if program has associated students in student_schedules
    $scheduleCheckSql = "SELECT COUNT(*) as schedule_count FROM student_schedules WHERE program_id = ?";
    error_log("Preparing schedule check query: " . $scheduleCheckSql);
    $scheduleStmt = $conn->prepare($scheduleCheckSql);

    if (!$scheduleStmt) {
        throw new Exception("Prepare failed for schedule check query. MySQL Error: " . $conn->error);
    }

    $scheduleStmt->bind_param("i", $id);

    if (!$scheduleStmt->execute()) {
        throw new Exception("Schedule check query execution failed. MySQL Error: " . $scheduleStmt->error);
    }

    $scheduleResult = $scheduleStmt->get_result();
    $scheduleRow = $scheduleResult->fetch_assoc();
    $scheduleCount = $scheduleRow['schedule_count'];

    error_log("Schedule count for program ID $id: " . $scheduleCount);

    $transactionStmt->close();

    // NEW: Prevent deletion if program has any transactions or scheduled students
    if ($transactionCount > 0 || $scheduleCount > 0) {
        error_log("Cannot delete program ID $id - has $transactionCount transactions and $scheduleCount scheduled students");

        // Clean output buffer before sending response
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete this program because it has associated student transactions or schedules.",
            'details' => [
                'program_id' => $id,
                'transaction_count' => $transactionCount,
                'schedule_count' => $scheduleCount,
                'total_records' => $transactionCount + $scheduleCount
            ],
            'reason' => 'Program has associated student data'
        ]);
        return;
    }

    error_log("No associated transactions or schedules found, proceeding with deletion");

    // Proceed with deletion
    $sql = "DELETE FROM program WHERE id = ?";
    error_log("Preparing delete query: " . $sql);
    $ps = $conn->prepare($sql);

    if (!$ps) {
        throw new Exception("Prepare failed for delete query. MySQL Error: " . $conn->error);
    }

    error_log("Delete statement prepared successfully");
    $ps->bind_param("i", $id);

    error_log("Executing delete query...");
    if ($ps->execute()) {
        // Debug: Check affected rows
        $affectedRows = $ps->affected_rows;
        error_log("Delete executed successfully. Affected rows: " . $affectedRows);

        if ($affectedRows > 0) {
            // Clean output buffer before sending response
            ob_clean();
            error_log("DELETE SCRIPT SUCCESS - Record $id deleted");
            echo json_encode([
                'success' => true,
                'message' => 'Program deleted successfully',
                'affected_rows' => $affectedRows,
                'deleted_id' => $id
            ]);
        } else {
            throw new Exception("No rows were affected during deletion. Record may not exist or may be protected.");
        }
    } else {
        throw new Exception("Delete execution failed. MySQL Error: " . $ps->error);
    }

    $ps->close();
    $conn->close();

} catch (Exception $e) {
    // Clean output buffer before sending error response
    ob_clean();

    // Log error for server-side debugging
    error_log("DELETE SCRIPT ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e),
        'debug_info' => [
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'undefined',
            'post_data' => $_POST,
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename(__FILE__),
            'line' => $e->getLine()
        ]
    ]);
} catch (Error $e) {
    // Catch fatal errors
    ob_clean();
    error_log("DELETE SCRIPT FATAL ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Fatal error occurred: ' . $e->getMessage(),
        'error_type' => 'Fatal Error'
    ]);
} finally {
    // Ensure connections are closed even if an exception occurs
    if (isset($checkStmt) && $checkStmt) {
        $checkStmt->close();
    }
    if (isset($transactionStmt) && $transactionStmt) {
        $transactionStmt->close();
    }
    if (isset($scheduleStmt) && $scheduleStmt) {
        $scheduleStmt->close();
    }
    if (isset($ps) && $ps) {
        $ps->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }

    error_log("DELETE SCRIPT END - " . date('Y-m-d H:i:s'));

    // End output buffering
    ob_end_flush();
}
?>