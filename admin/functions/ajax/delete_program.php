<?php
// Start session first


// Start output buffering to prevent any accidental output
ob_start();

// Disable any error output that might corrupt JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Log debugging info
error_log("=== PROGRAM DELETE START === " . date('Y-m-d H:i:s'));
error_log("GET parameters: " . json_encode($_GET));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

try {
    // Check connection file
    if (!file_exists('../../../connection/connection.php')) {
        throw new Exception('Connection file not found at ../../../connection/connection.php');
    }

    include '../../../connection/connection.php';

    // Check if con() function exists
    if (!function_exists('con')) {
        throw new Exception('con() function not found in connection file');
    }

    $conn = con();

    // Check connection
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    if (mysqli_connect_errno()) {
        throw new Exception('Database connection error: ' . mysqli_connect_error());
    }

    error_log("Database connection successful");

    // Check if program ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Program ID is required');
    }

    $program_id = intval($_GET['id']);
    error_log("Program ID to delete: " . $program_id);

    // Validate ID
    if ($program_id <= 0) {
        throw new Exception('Invalid program ID: ' . $_GET['id']);
    }

    // Check if program exists first
    $check_query = "SELECT id, program_name FROM program WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);

    if (!$check_stmt) {
        throw new Exception('Failed to prepare check query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($check_stmt, "i", $program_id);

    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception('Failed to execute check query: ' . mysqli_stmt_error($check_stmt));
    }

    $check_result = mysqli_stmt_get_result($check_stmt);
    $program_data = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);

    if (!$program_data) {
        throw new Exception('Program with ID ' . $program_id . ' does not exist');
    }

    error_log("Program found: " . json_encode($program_data));

    // NEW: Check if program has students enrolled (pos_transaction records)
    $pos_check_query = "SELECT COUNT(*) as student_count FROM pos_transactions WHERE program_id = ?";
    $pos_check_stmt = mysqli_prepare($conn, $pos_check_query);

    if (!$pos_check_stmt) {
        throw new Exception('Failed to prepare pos_transaction check query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($pos_check_stmt, "i", $program_id);

    if (!mysqli_stmt_execute($pos_check_stmt)) {
        throw new Exception('Failed to execute pos_transaction check query: ' . mysqli_stmt_error($pos_check_stmt));
    }

    $pos_result = mysqli_stmt_get_result($pos_check_stmt);
    $pos_data = mysqli_fetch_assoc($pos_result);
    mysqli_stmt_close($pos_check_stmt);

    $student_count = intval($pos_data['student_count']);
    error_log("Students enrolled in program: " . $student_count);

    // If there are students enrolled, prevent deletion
    if ($student_count > 0) {
        ob_clean();
        $response = [
            'success' => false,
            'message' => 'Cannot delete program "' . $program_data['program_name'] . '" because we have student currently enrolled in this Program. To delete kindly wait for the program to end, or detele the records of student. for more instruction call IT the team behind the system',
            'error_code' => 'PROGRAM_HAS_STUDENTS',
            'student_count' => $student_count,
            'program_name' => $program_data['program_name']
        ];
        echo json_encode($response);
        exit;
    }

    // Start transaction
    if (!mysqli_begin_transaction($conn)) {
        throw new Exception('Failed to start transaction: ' . mysqli_error($conn));
    }

    error_log("Transaction started");

    // Delete related records in correct order (child tables first)

    // 1. Delete student_schedules
    $delete_student_schedules = "DELETE FROM student_schedules WHERE program_id = ?";
    $stmt_student_schedules = mysqli_prepare($conn, $delete_student_schedules);

    if (!$stmt_student_schedules) {
        throw new Exception('Failed to prepare student_schedules delete query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_student_schedules, "i", $program_id);

    if (!mysqli_stmt_execute($stmt_student_schedules)) {
        throw new Exception('Failed to delete student_schedules: ' . mysqli_stmt_error($stmt_student_schedules));
    }

    $student_schedules_deleted = mysqli_stmt_affected_rows($stmt_student_schedules);
    error_log("Student schedules deleted: " . $student_schedules_deleted);
    mysqli_stmt_close($stmt_student_schedules);

    // 2. Delete related schedules
    $delete_schedules = "DELETE FROM schedules WHERE program_id = ?";
    $stmt_schedules = mysqli_prepare($conn, $delete_schedules);

    if (!$stmt_schedules) {
        throw new Exception('Failed to prepare schedules delete query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_schedules, "i", $program_id);

    if (!mysqli_stmt_execute($stmt_schedules)) {
        throw new Exception('Failed to delete schedules: ' . mysqli_stmt_error($stmt_schedules));
    }

    $schedules_deleted = mysqli_stmt_affected_rows($stmt_schedules);
    error_log("Schedules deleted: " . $schedules_deleted);
    mysqli_stmt_close($stmt_schedules);

    // 3. Delete related promos
    $delete_promos = "DELETE FROM promo WHERE program_id = ?";
    $stmt_promos = mysqli_prepare($conn, $delete_promos);

    if (!$stmt_promos) {
        throw new Exception('Failed to prepare promos delete query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_promos, "i", $program_id);

    if (!mysqli_stmt_execute($stmt_promos)) {
        throw new Exception('Failed to delete promos: ' . mysqli_stmt_error($stmt_promos));
    }

    $promos_deleted = mysqli_stmt_affected_rows($stmt_promos);
    error_log("Promos deleted: " . $promos_deleted);
    mysqli_stmt_close($stmt_promos);

    // Finally, delete the program
    $delete_program = "DELETE FROM program WHERE id = ?";
    $stmt_program = mysqli_prepare($conn, $delete_program);

    if (!$stmt_program) {
        throw new Exception('Failed to prepare program delete query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_program, "i", $program_id);

    if (!mysqli_stmt_execute($stmt_program)) {
        $error_msg = mysqli_stmt_error($stmt_program);
        $mysql_errno = mysqli_stmt_errno($stmt_program);
        $conn_error = mysqli_error($conn);

        error_log("Program delete execution failed:");
        error_log("Statement error: " . $error_msg);
        error_log("Statement errno: " . $mysql_errno);
        error_log("Connection error: " . $conn_error);

        throw new Exception('Failed to execute program delete: ' . ($error_msg ?: $conn_error ?: 'Unknown error'));
    }

    $affected_rows = mysqli_stmt_affected_rows($stmt_program);
    error_log("Program delete affected rows: " . $affected_rows);

    if ($affected_rows > 0) {
        // Log the deletion activity (only if session exists)
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            date_default_timezone_set('Asia/Manila');
            $date_created2 = date('Y-m-d H:i:s');
            $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
            $log_query = "INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES (?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            if ($log_stmt) {
                $activity = "Deleted Program: " . $program_id;
                mysqli_stmt_bind_param($log_stmt, "sss", $user_id, $activity, $date_created2);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }
        }

        // Commit transaction
        if (!mysqli_commit($conn)) {
            throw new Exception('Failed to commit transaction: ' . mysqli_error($conn));
        }

        error_log("Transaction committed successfully");
        mysqli_stmt_close($stmt_program);

        // Clear any previous output and send clean JSON response
        ob_clean();

        $response = [
            'success' => true,
            'message' => 'Program "' . $program_data['program_name'] . '" deleted successfully',
            'deleted_program' => $program_data,
            'deleted_counts' => [
                'student_schedules' => $student_schedules_deleted,
                'schedules' => $schedules_deleted,
                'promos' => $promos_deleted,
                'program' => $affected_rows
            ]
        ];

        echo json_encode($response);
        exit;
    } else {
        // Rollback transaction
        mysqli_rollback($conn);
        error_log("No rows affected - program may not exist");

        ob_clean();
        $response = [
            'success' => false,
            'message' => 'Program not found or already deleted'
        ];
        echo json_encode($response);
        exit;
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn) {
        mysqli_rollback($conn);
        error_log("Transaction rolled back due to error");
    }

    error_log("ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Clear any previous output and send clean JSON response
    ob_clean();

    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'program_id' => $program_id ?? 'not set',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($response);
    exit;
} finally {
    // Clean up resources
    if (isset($stmt_student_schedules) && $stmt_student_schedules) {
        mysqli_stmt_close($stmt_student_schedules);
    }
    if (isset($stmt_schedules) && $stmt_schedules) {
        mysqli_stmt_close($stmt_schedules);
    }
    if (isset($stmt_promos) && $stmt_promos) {
        mysqli_stmt_close($stmt_promos);
    }
    if (isset($stmt_program) && $stmt_program) {
        mysqli_stmt_close($stmt_program);
    }
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }

    error_log("=== PROGRAM DELETE END === " . date('Y-m-d H:i:s'));
}
?>