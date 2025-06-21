<?php

// Database connection
include "../../connection/connection.php";

$conn = con();
// Check connection
if ($conn->connect_error) {
    $response = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ];
    echo json_encode($response);
    exit;
}

// Function to check missing requirements
function getMissingRequirements($submitted_requirements)
{
    $all_requirements = [
        'PSA Birth Certificate',
        'TOR, ALS Certificate or FORM 137',
        'Passport size photos'
    ];

    $missing = [];

    if (empty($submitted_requirements)) {
        return $all_requirements;
    }

    // Convert submitted requirements to array
    // Use a different delimiter that won't conflict with commas in requirement names
    $submitted = [];
    if (is_array($submitted_requirements)) {
        $submitted = $submitted_requirements;
    } else {
        // If it's a string, we need to be careful with the split
        // For now, let's handle it as an array from $_POST
        $submitted = [];
    }

    // Check each requirement individually
    foreach ($all_requirements as $requirement) {
        $found = false;
        foreach ($submitted as $submitted_req) {
            if (trim($submitted_req) === trim($requirement)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missing[] = $requirement;
        }
    }

    return $missing;
}

// Process only if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic input validation and sanitization
    $student_number = mysqli_real_escape_string($conn, $_POST['studentNumber']);

    date_default_timezone_set('Asia/Manila');
    $date_created2 = date('Y-m-d H:i:s');
    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Added Student: $student_number', '$date_created2' )");

    $entry_date = mysqli_real_escape_string($conn, $_POST['entryDate']);
    $last_name = mysqli_real_escape_string($conn, $_POST['lastName']);
    $first_name = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middle_name = isset($_POST['middleName']) ? mysqli_real_escape_string($conn, $_POST['middleName']) : null;
    $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
    $civil_status = mysqli_real_escape_string($conn, $_POST['civilStatus']);
    $user_email = mysqli_real_escape_string($conn, $_POST['user_email']);
    $user_contact = mysqli_real_escape_string($conn, $_POST['user_contact']);

    // Current Address
    $region = mysqli_real_escape_string($conn, $_POST['region_name']);
    $province = mysqli_real_escape_string($conn, $_POST['province_name']);
    $city = mysqli_real_escape_string($conn, $_POST['city_name']);
    $brgy = mysqli_real_escape_string($conn, $_POST['barangay_name']);
    $purok = isset($_POST['purok']) ? mysqli_real_escape_string($conn, $_POST['purok']) : null;

    // Birth Place
    $birth_region = mysqli_real_escape_string($conn, $_POST['birth_region_name']);
    $birth_province = mysqli_real_escape_string($conn, $_POST['birth_province_name']);
    $birth_city = mysqli_real_escape_string($conn, $_POST['birth_city_name']);
    $birth_brgy = mysqli_real_escape_string($conn, $_POST['birth_barangay_name']);

    // Educational Background
    $educational_attainment = mysqli_real_escape_string($conn, $_POST['educationalAttainment']);
    $classification = mysqli_real_escape_string($conn, $_POST['classification']);

    // Emergency Contact
    $emergency_name = mysqli_real_escape_string($conn, $_POST['emergencyName']);
    $relationship = mysqli_real_escape_string($conn, $_POST['relationship']);
    $emergency_contact = mysqli_real_escape_string($conn, $_POST['emergencyContact']);
    $emergency_email = mysqli_real_escape_string($conn, $_POST['emergencyEmail']);
    $emergency_region = mysqli_real_escape_string($conn, $_POST['emergency_region_name']);
    $emergency_province = mysqli_real_escape_string($conn, $_POST['emergency_province_name']);
    $emergency_city = mysqli_real_escape_string($conn, $_POST['emergency_city_name']);
    $emergency_brgy = mysqli_real_escape_string($conn, $_POST['emergency_barangay_name']);
    $emergency_purok = isset($_POST['emergencyPurok']) ? mysqli_real_escape_string($conn, $_POST['emergencyPurok']) : null;

    // Referral Information
    $referral = mysqli_real_escape_string($conn, $_POST['referral']);
    $other_referral = ($referral === 'other' && isset($_POST['otherReferral'])) ?
        mysqli_real_escape_string($conn, $_POST['otherReferral']) : null;
    $knowledge_source = mysqli_real_escape_string($conn, $_POST['knowledgeSource']);

    // Requirements - Handle as array first, then convert to string for storage
    $requirements_array = isset($_POST['requirements']) ? $_POST['requirements'] : [];
    $requirements_string = !empty($requirements_array) ? implode(", ", $requirements_array) : null;

    // Debug: Log what we received
    error_log("Requirements array received: " . print_r($requirements_array, true));
    error_log("Requirements string for storage: " . $requirements_string);

    // Check for missing requirements using the array
    $missing_requirements = getMissingRequirements($requirements_array);

    // Debug: Log missing requirements
    error_log("Missing requirements calculated: " . print_r($missing_requirements, true));

    // Photo upload handling
    $photo_path = null;
    if (isset($_FILES['photoUpload']) && $_FILES['photoUpload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/student_photos/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($_FILES['photoUpload']['name'], PATHINFO_EXTENSION);
        $new_filename = $student_number . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES['photoUpload']['tmp_name'], $target_file)) {
            $photo_path = $target_file;
        }
    }

    // SQL query to insert data
    $sql = "INSERT INTO student_info_tbl (
                student_number, entry_date, last_name, first_name, middle_name, 
                birthdate, civil_status, user_email, user_contact, region, province, city, brgy, purok,
                birth_region, birth_province, birth_city, birth_brgy,
                educational_attainment, classification, 
                emergency_name, relationship, emergency_contact, emergency_email,
                emergency_region, emergency_province, emergency_city, emergency_brgy, emergency_purok,
                referral, other_referral, knowledge_source, requirements, photo_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $response = [
            'status' => 'error',
            'message' => 'Prepare statement failed: ' . $conn->error
        ];
        echo json_encode($response);
        exit;
    }

    // Bind parameters
    $stmt->bind_param(
        "ssssssssisssssssssssssssssssssssss",
        $student_number,
        $entry_date,
        $last_name,
        $first_name,
        $middle_name,
        $birthdate,
        $civil_status,
        $user_contact,
        $user_email,
        $region,
        $province,
        $city,
        $brgy,
        $purok,
        $birth_region,
        $birth_province,
        $birth_city,
        $birth_brgy,
        $educational_attainment,
        $classification,
        $emergency_name,
        $relationship,
        $emergency_contact,
        $emergency_email,
        $emergency_region,
        $emergency_province,
        $emergency_city,
        $emergency_brgy,
        $emergency_purok,
        $referral,
        $other_referral,
        $knowledge_source,
        $requirements_string,
        $photo_path
    );

    // Execute the statement
    if ($stmt->execute()) {
        $response = [
            'status' => 'success',
            'message' => 'Student registration completed successfully!',
            'student_number' => $student_number,
            'student_name' => $first_name . ' ' . $last_name,
            'has_missing_requirements' => !empty($missing_requirements),
            'missing_requirements' => array_values($missing_requirements),
            'submitted_requirements' => $requirements_array
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Error: ' . $stmt->error
        ];
    }

    // Close statement
    $stmt->close();
} else {
    // Not a POST request
    $response = [
        'status' => 'error',
        'message' => 'Invalid request method'
    ];
}

// Close connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>