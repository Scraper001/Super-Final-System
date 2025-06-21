<?php
// auto_load_checker.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost';
$dbname = 'carepro';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

function checkAndUpdateStudentStatus($pdo)
{
    $updatedStudents = [];

    try {
        // Query to find students whose programs have ended
        // We'll consider a program ended if the latest schedule end date has passed
        $sql = "
            SELECT DISTINCT 
                s.student_id,
                st.student_number,
                st.first_name,
                st.last_name,
                p.program_name,
                MAX(STR_TO_DATE(CONCAT(sch.training_date, ' ', sch.end_time), '%Y-%m-%d %H:%i:%s')) as last_schedule_end
            FROM student_schedules s
            INNER JOIN student_info_tbl st ON s.student_id = st.id
            INNER JOIN program p ON s.program_id = p.id
            INNER JOIN schedules sch ON s.schedule_id = sch.id
            WHERE s.status = 'enrolled' 
            AND st.enrollment_status IN ('Enrolled', 'Reserved')
            GROUP BY s.student_id, st.student_number, st.first_name, st.last_name, p.program_name
            HAVING last_schedule_end < NOW()
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $expiredStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update each expired student
        foreach ($expiredStudents as $student) {
            // Update student_info_tbl enrollment_status to empty
            $updateStudent = "UPDATE student_info_tbl SET enrollment_status = '' WHERE id = ?";
            $updateStmt = $pdo->prepare($updateStudent);
            $updateStmt->execute([$student['student_id']]);

            // Update student_schedules status to completed
            $updateSchedule = "UPDATE student_schedules SET status = 'completed' WHERE student_id = ? AND status = 'enrolled'";
            $updateScheduleStmt = $pdo->prepare($updateSchedule);
            $updateScheduleStmt->execute([$student['student_id']]);

            // Log the activity
            $logActivity = "INSERT INTO logs (user_id, activity, dnt) VALUES (?, ?, ?)";
            $logStmt = $pdo->prepare($logActivity);
            $logStmt->execute([
                $student['student_id'],
                'Program completed - Status reset to available',
                date('Y-m-d H:i:s')
            ]);

            $updatedStudents[] = [
                'student_id' => $student['student_id'],
                'student_number' => $student['student_number'],
                'name' => $student['first_name'] . ' ' . $student['last_name'],
                'program' => $student['program_name'],
                'completion_date' => $student['last_schedule_end']
            ];
        }

        return [
            'success' => true,
            'updated_count' => count($updatedStudents),
            'updated_students' => $updatedStudents,
            'timestamp' => date('Y-m-d H:i:s')
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Get current active students count for monitoring
function getActiveStudentsCount($pdo)
{
    try {
        $sql = "SELECT COUNT(*) as active_count FROM student_info_tbl WHERE enrollment_status IN ('Enrolled', 'Reserved')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['active_count'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Get students nearing completion (within 7 days)
function getStudentsNearingCompletion($pdo)
{
    try {
        $sql = "
            SELECT DISTINCT 
                s.student_id,
                st.student_number,
                st.first_name,
                st.last_name,
                p.program_name,
                MAX(STR_TO_DATE(CONCAT(sch.training_date, ' ', sch.end_time), '%Y-%m-%d %H:%i:%s')) as last_schedule_end
            FROM student_schedules s
            INNER JOIN student_info_tbl st ON s.student_id = st.id
            INNER JOIN program p ON s.program_id = p.id
            INNER JOIN schedules sch ON s.schedule_id = sch.id
            WHERE s.status = 'enrolled' 
            AND st.enrollment_status IN ('Enrolled', 'Reserved')
            GROUP BY s.student_id, st.student_number, st.first_name, st.last_name, p.program_name
            HAVING last_schedule_end BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Handle different request types
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : 'check';

if ($method === 'GET' || $method === 'POST') {
    switch ($action) {
        case 'check':
            $result = checkAndUpdateStudentStatus($pdo);
            echo json_encode($result);
            break;

        case 'stats':
            $activeCount = getActiveStudentsCount($pdo);
            $nearingCompletion = getStudentsNearingCompletion($pdo);
            echo json_encode([
                'success' => true,
                'active_students' => $activeCount,
                'nearing_completion' => count($nearingCompletion),
                'nearing_completion_list' => $nearingCompletion,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        case 'health':
            echo json_encode([
                'success' => true,
                'status' => 'healthy',
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION
            ]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    echo json_encode(['error' => 'Method not allowed']);
}
?>