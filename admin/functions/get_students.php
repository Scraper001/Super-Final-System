<?php
// get_student.php
// Database connection
include "../../connection/connection.php";
$conn = con();
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM student_info_tbl WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'No student found.']);
    }
}
?>