<?php
// save_program.php

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed (adjust origin as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'careprodb'; // Change to your database name
$username = 'root'; // Change to your database username
$password = ''; // Change to your database password

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST request.'
    ]);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON format'
    ]);
    exit();
}

// Validate required fields
$required_fields = ['program_name', 'trainor_id'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Missing required field: $field"
        ]);
        exit();
    }
}

// Validate schedule data
if (empty($data['class_schedule']) || !is_array($data['class_schedule'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'At least one schedule is required'
    ]);
    exit();
}

// Validate each schedule entry
foreach ($data['class_schedule'] as $index => $schedule) {
    $required_schedule_fields = ['week', 'description', 'date', 'startTime', 'endTime', 'dayOfWeek'];
    foreach ($required_schedule_fields as $field) {
        if (empty($schedule[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing schedule field '$field' in schedule " . ($index + 1)
            ]);
            exit();
        }
    }

    // Validate day of week
    $valid_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    if (!in_array($schedule['dayOfWeek'], $valid_days)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Invalid day of week in schedule " . ($index + 1)
        ]);
        exit();
    }

    // Validate time format and range
    if (!validateTime($schedule['startTime']) || !validateTime($schedule['endTime'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Invalid time format in schedule " . ($index + 1)
        ]);
        exit();
    }

    if ($schedule['startTime'] >= $schedule['endTime']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "End time must be after start time in schedule " . ($index + 1)
        ]);
        exit();
    }

    // Validate date format
    if (!validateDate($schedule['date'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Invalid date format in schedule " . ($index + 1)
        ]);
        exit();
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert program
    $program_sql = "INSERT INTO programs (program_name, trainor_id, opening_class, closing_class) VALUES (?, ?, ?, ?)";
    $program_stmt = $pdo->prepare($program_sql);
    $program_stmt->execute([
        $data['program_name'],
        $data['trainor_id'],
        !empty($data['opening_class']) ? $data['opening_class'] : null,
        !empty($data['closing_class']) ? $data['closing_class'] : null
    ]);

    // Get the inserted program ID
    $program_id = $pdo->lastInsertId();

    // Insert schedules
    $schedule_sql = "INSERT INTO program_schedules (program_id, week, description, training_date, start_time, end_time, day_of_week) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $schedule_stmt = $pdo->prepare($schedule_sql);

    foreach ($data['class_schedule'] as $schedule) {
        $schedule_stmt->execute([
            $program_id,
            $schedule['week'],
            $schedule['description'],
            $schedule['date'],
            $schedule['startTime'],
            $schedule['endTime'],
            $schedule['dayOfWeek']
        ]);
    }

    // Commit transaction
    $pdo->commit();

    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Program and schedules saved successfully!',
        'program_id' => $program_id,
        'schedules_count' => count($data['class_schedule'])
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Rollback transaction on any other error
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected error: ' . $e->getMessage()
    ]);
}

// Helper function to validate time format (HH:MM)
function validateTime($time)
{
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
}

// Helper function to validate date format (YYYY-MM-DD)
function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Optional: Function to get all programs with their schedules
function getAllPrograms($pdo)
{
    try {
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(
                           CONCAT(ps.week, '|', ps.description, '|', ps.training_date, '|', 
                                  ps.start_time, '|', ps.end_time, '|', ps.day_of_week)
                           SEPARATOR ';'
                       ) as schedules
                FROM programs p
                LEFT JOIN program_schedules ps ON p.id = ps.program_id
                GROUP BY p.id
                ORDER BY p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return false;
    }
}

// Optional: Function to get a specific program with schedules
function getProgramById($pdo, $program_id)
{
    try {
        // Get program details
        $program_sql = "SELECT * FROM programs WHERE id = ?";
        $program_stmt = $pdo->prepare($program_sql);
        $program_stmt->execute([$program_id]);
        $program = $program_stmt->fetch();

        if (!$program) {
            return false;
        }

        // Get schedules
        $schedule_sql = "SELECT * FROM program_schedules WHERE program_id = ? ORDER BY training_date ASC, start_time ASC";
        $schedule_stmt = $pdo->prepare($schedule_sql);
        $schedule_stmt->execute([$program_id]);
        $schedules = $schedule_stmt->fetchAll();

        $program['schedules'] = $schedules;
        return $program;
    } catch (PDOException $e) {
        return false;
    }
}

// Optional: Function to update a program
function updateProgram($pdo, $program_id, $data)
{
    try {
        $pdo->beginTransaction();

        // Update program
        $program_sql = "UPDATE programs SET program_name = ?, trainor_id = ?, opening_class = ?, closing_class = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $program_stmt = $pdo->prepare($program_sql);
        $program_stmt->execute([
            $data['program_name'],
            $data['trainor_id'],
            !empty($data['opening_class']) ? $data['opening_class'] : null,
            !empty($data['closing_class']) ? $data['closing_class'] : null,
            $program_id
        ]);

        // Delete existing schedules
        $delete_sql = "DELETE FROM program_schedules WHERE program_id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$program_id]);

        // Insert new schedules
        $schedule_sql = "INSERT INTO program_schedules (program_id, week, description, training_date, start_time, end_time, day_of_week) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $schedule_stmt = $pdo->prepare($schedule_sql);

        foreach ($data['class_schedule'] as $schedule) {
            $schedule_stmt->execute([
                $program_id,
                $schedule['week'],
                $schedule['description'],
                $schedule['date'],
                $schedule['startTime'],
                $schedule['endTime'],
                $schedule['dayOfWeek']
            ]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

// Optional: Function to delete a program
function deleteProgram($pdo, $program_id)
{
    try {
        $sql = "DELETE FROM programs WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$program_id]);
    } catch (PDOException $e) {
        return false;
    }
}

?>