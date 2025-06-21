<?php
header('Content-Type: application/json');


include "../../connection/connection.php";
$conn = con();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];


    $sql = "DELETE FROM tuitionfee WHERE id = ?";
    $ps = $conn->prepare($sql);
    $ps->bind_param("i", $id);


    if ($ps->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }

    $ps->close();
    $conn->close();
}
?>