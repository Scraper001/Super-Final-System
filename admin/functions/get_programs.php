<?php
// get_programs.php

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed (adjust origin as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use GET request.'
    ]);
    exit();
}

try {
    // Check if specific program ID is requested
    $program_id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if ($program_id) {
        // Get specific program with schedules
        $program_sql = "SELECT * FROM programs WHERE id = ?";
        $program_stmt = $pdo->prepare($program_sql);
        $program_stmt->execute([$program_id]);
        $program = $program_stmt->fetch();

        if (!$program) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Program not found'
            ]);
            exit();
        }

        // Get schedules for this program
        $schedule_sql = "SELECT * FROM program_schedules WHERE program_id = ? ORDER BY training_date ASC, start_time ASC";
        $schedule_stmt = $pdo->prepare($schedule_sql);
        $schedule_stmt->execute([$program_id]);
        $schedules = $schedule_stmt->fetchAll();

        $program['schedules'] = $schedules;

        echo json_encode([
            'success' => true,
            'data' => $program
        ]);

    } else {
        // Get all programs with their schedules
        $sql = "SELECT p.id, p.program_name, p.trainor_id, p.opening_class, p.closing_class, p.created_at, p.updated_at,
                       COUNT(ps.id) as schedule_count
                FROM programs p
                LEFT JOIN program_schedules ps ON p.id = ps.program_id
                GROUP BY p.id
                ORDER BY p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $programs = $stmt->fetchAll();

        // Get schedules for each program (optional - for detailed view)
        $include_schedules = isset($_GET['include_schedules']) && $_GET['include_schedules'] === 'true';

        if ($include_schedules) {
            foreach ($programs as &$program) {
                $schedule_sql = "SELECT * FROM program_schedules WHERE program_id = ? ORDER BY training_date ASC, start_time ASC";
                $schedule_stmt = $pdo->prepare($schedule_sql);
                $schedule_stmt->execute([$program['id']]);
                $program['schedules'] = $schedule_stmt->fetchAll();
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $programs,
            'count' => count($programs)
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected error: ' . $e->getMessage()
    ]);
}
?>