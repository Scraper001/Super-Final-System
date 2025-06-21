<?php
// Database connection
include "../../connection/connection.php";

$conn = con();
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $student_number = mysqli_real_escape_string($conn, $_POST['studentNumber']);
    $entry_date = mysqli_real_escape_string($conn, $_POST['entryDate']);
    $last_name = mysqli_real_escape_string($conn, $_POST['lastName']);
    $first_name = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middle_name = isset($_POST['middleName']) ? mysqli_real_escape_string($conn, $_POST['middleName']) : null;
    $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
    $civil_status = mysqli_real_escape_string($conn, $_POST['civilStatus']);
    $user_email = mysqli_real_escape_string($conn, $_POST['user_email']);
    $user_contact = mysqli_real_escape_string($conn, $_POST['user_contact']);

    $region = mysqli_real_escape_string($conn, $_POST['region_name']);
    $province = mysqli_real_escape_string($conn, $_POST['province_name']);
    $city = mysqli_real_escape_string($conn, $_POST['city_name']);
    $brgy = mysqli_real_escape_string($conn, $_POST['barangay_name']);
    $purok = isset($_POST['purok']) ? mysqli_real_escape_string($conn, $_POST['purok']) : null;

    $birth_region = mysqli_real_escape_string($conn, $_POST['birth_region_name']);
    $birth_province = mysqli_real_escape_string($conn, $_POST['birth_province_name']);
    $birth_city = mysqli_real_escape_string($conn, $_POST['birth_city_name']);
    $birth_brgy = mysqli_real_escape_string($conn, $_POST['birth_barangay_name']);

    $educational_attainment = mysqli_real_escape_string($conn, $_POST['educationalAttainment']);
    $classification = mysqli_real_escape_string($conn, $_POST['classification']);

    $emergency_name = mysqli_real_escape_string($conn, $_POST['emergencyName']);
    $relationship = mysqli_real_escape_string($conn, $_POST['relationship']);
    $emergency_contact = mysqli_real_escape_string($conn, $_POST['emergencyContact']);
    $emergency_email = mysqli_real_escape_string($conn, $_POST['emergencyEmail']);
    $emergency_region = mysqli_real_escape_string($conn, $_POST['emergency_region_name']);
    $emergency_province = mysqli_real_escape_string($conn, $_POST['emergency_province_name']);
    $emergency_city = mysqli_real_escape_string($conn, $_POST['emergency_city_name']);
    $emergency_brgy = mysqli_real_escape_string($conn, $_POST['emergency_barangay_name']);
    $emergency_purok = isset($_POST['emergencyPurok']) ? mysqli_real_escape_string($conn, $_POST['emergencyPurok']) : null;

    $referral = mysqli_real_escape_string($conn, $_POST['referral']);
    $other_referral = ($referral === 'other' && isset($_POST['otherReferral'])) ? mysqli_real_escape_string($conn, $_POST['otherReferral']) : null;
    $knowledge_source = mysqli_real_escape_string($conn, $_POST['knowledgeSource']);

    $requirements = isset($_POST['requirements']) ? implode(", ", $_POST['requirements']) : null;

    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photoUpload']) && $_FILES['photoUpload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/student_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['photoUpload']['name'], PATHINFO_EXTENSION);
        $new_filename = $student_number . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['photoUpload']['tmp_name'], $target_file)) {
            $photo_path = $target_file;
        }
    }

    // Build SQL query based on whether a photo is uploaded
    if ($photo_path !== null) {
        $sql = "UPDATE student_info_tbl SET 
                    entry_date=?, last_name=?, first_name=?, middle_name=?, 
                    birthdate=?, civil_status=?, user_email=?, user_contact=?, 
                    region=?, province=?, city=?, brgy=?, purok=?, 
                    birth_region=?, birth_province=?, birth_city=?, birth_brgy=?, 
                    educational_attainment=?, classification=?, 
                    emergency_name=?, relationship=?, emergency_contact=?, emergency_email=?, 
                    emergency_region=?, emergency_province=?, emergency_city=?, emergency_brgy=?, emergency_purok=?, 
                    referral=?, other_referral=?, knowledge_source=?, requirements=?, photo_path=? 
                WHERE student_number=?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param(
            "ssssssssssssssssssssssssssssssssss",
            $entry_date,
            $last_name,
            $first_name,
            $middle_name,
            $birthdate,
            $civil_status,
            $user_email,
            $user_contact,
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
            $requirements,
            $photo_path,
            $student_number
        );
    } else {
        $sql = "UPDATE student_info_tbl SET 
                    entry_date=?, last_name=?, first_name=?, middle_name=?, 
                    birthdate=?, civil_status=?, user_email=?, user_contact=?, 
                    region=?, province=?, city=?, brgy=?, purok=?, 
                    birth_region=?, birth_province=?, birth_city=?, birth_brgy=?, 
                    educational_attainment=?, classification=?, 
                    emergency_name=?, relationship=?, emergency_contact=?, emergency_email=?, 
                    emergency_region=?, emergency_province=?, emergency_city=?, emergency_brgy=?, emergency_purok=?, 
                    referral=?, other_referral=?, knowledge_source=?, requirements=?
                WHERE student_number=?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param(
            "sssssssssssssssssssssssssssssssss",
            $entry_date,
            $last_name,
            $first_name,
            $middle_name,
            $birthdate,
            $civil_status,
            $user_email,
            $user_contact,
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
            $requirements,
            $student_number
        );
    }

    // Execute
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Student record updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>