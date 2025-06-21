<?php
include "../../connection/connection.php";

$conn = con();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int) $_POST['row_id']; // This is now safe
    echo $program_name = $_POST['program'];
    echo $package_number = (int) $_POST['package_number']; // Make sure this is an integer
    echo $tuition_fee = (int) $_POST['tuitionfee'];
    echo $misc_fee = (int) $_POST['miscfee'];
    echo $ojt_medical = (int) $_POST['ojtmedical'];
    echo $system_fee = (int) $_POST['systemfee'];
    echo $assessment_fee = (int) $_POST['assessmentfee'];
    echo $uniform = (int) $_POST['uniform'];
    echo $id_fee = (int) $_POST['id'];
    echo $books = (int) $_POST['books'];
    echo $kit = (int) $_POST['kit'];
    echo $demo1 = (int) $_POST['demo1'];
    echo $demo2 = (int) $_POST['demo2'];
    echo $demo3 = (int) $_POST['demo3'];
    echo $demo4 = (int) $_POST['demo4'];


    echo $reservation_fee = (int) $_POST['reservation_fee'];

    // Total based tuition calculation
    $total_fee = $tuition_fee + $misc_fee + $ojt_medical + $system_fee + $assessment_fee + $uniform + $id_fee + $books + $kit + $demo1 + $demo2 + $demo3 + $demo4;

    // Prepare the update query
    echo $sql = "UPDATE tuitionfee SET
            program_name = ?, 
            package_number = ?, 
            `tuition Fee` = ?, 
            `misc Fee` = ?, 
            `ojtmedical` = ?, 
            `system Fee` = ?, 
            `assessment Fee` = ?, 
            uniform = ?, 
            IDfee = ?, 
            books = ?, 
            kit = ?, 
            demo1 = ?, 
            demo2 = ?, 
            demo3 = ?, 
            demo4 = ?, 
            totalbasedtuition = ?,
            reservation_fee = ? 
            WHERE id = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters
        $stmt->bind_param(
            'siiiiiiiiiiiiiiiii', // Define the types: 's' for string, 'i' for integer
            $program_name,
            $package_number,
            $tuition_fee,
            $misc_fee,
            $ojt_medical,
            $system_fee,
            $assessment_fee,
            $uniform,
            $id_fee,
            $books,
            $kit,
            $demo1,
            $demo2,
            $demo3,
            $demo4,
            $total_fee,
            $reservation_fee,
            $id // ID is last to ensure the right row is updated
        );

        // Execute the statement
        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}
?>