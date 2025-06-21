<?php

include "../../connection/connection.php";
$conn = con();
$_SESSION['status'] = "";


if (isset($_POST['savePOS'])) {

    // Get POST values
    $student_number = $_POST['student_number'] ?? '';
    $program_name = $_POST['program_name'] ?? '';
    $package_number = $_POST['package_number'] ?? '';
    $student_name = $_POST['student_name'] ?? '';
    $balance = $_POST['balance'] ?? '';
    $discount = $_POST['discount'] ?? '';
    $demo1 = $_POST['demo1_hidden'] ?? 0;
    $demo2 = $_POST['demo2_hidden'] ?? 0;
    $demo3 = $_POST['demo3_hidden'] ?? 0;
    $demo4 = $_POST['demo4_hidden'] ?? 0;
    $final_schedule = $_POST['final_schedule'];
    $cash = $_POST['cash'] ?? 0;
    $change = $_POST['change'] ?? 0;
    date_default_timezone_set('Asia/Manila');
    $date_created = date('Y-m-d H:i:s');

    // Check if student already enrolled
    $sql_checking = "SELECT * FROM pos WHERE student_number = '$student_number'";
    $result_checking = $conn->query($sql_checking);

    if ($result_checking->num_rows > 0) {
        $_SESSION['status'] = "This student is already enrolled.";
        exit();
    }

    // Get program schedule
    $sql_checking2 = "SELECT * FROM programs WHERE program_name = '$program_name'";
    $result_checking2 = $conn->query($sql_checking2);

    if ($result_checking2->num_rows > 0) {
        $row_check2 = $result_checking2->fetch_assoc();

        $today = date('Y-m-d');
        $start_date = $row_check2['OpeningClasses'];
        $end_date = $row_check2['ClossingClasses'];

        if ($today >= $start_date && $today <= $end_date) {
            $_SESSION['status'] = "You can't enroll. The class has already started.";
            header("location: ../pos_adding.php");
            exit();
        } elseif ($today > $end_date) {
            $_SESSION['status'] = "You can't enroll. The class has already ended.";
            header("location: ../pos_adding.php");
            exit();
        }

        // Determine payment status
        $payment_status = '';
        if (!empty($_POST['full_hidden'])) {
            $payment_status = 'Full Payment';

            $sql_28 = "UPDATE `student_info_tbl` SET enrollment_status = 'Enrolled', programs = '$program_name', schedule = '$final_schedule' WHERE student_number = '$student_number'";
            $conn->query($sql_28);
            echo $sql_28;
        } elseif (!empty($_POST['initial_hidden'])) {
            $payment_status = 'Initial';
            $sql_29 = "UPDATE `student_info_tbl` SET enrollment_status = 'Enrolled', programs = '$program_name', schedule = '$final_schedule' WHERE student_number = '$student_number'";

            $conn->query($sql_29);

            echo $sql_29;

        } elseif (!empty($_POST['reserve_hidden'])) {
            $payment_status = 'Reserve';

            $sql_30 = "UPDATE `student_info_tbl` SET enrollment_status = 'Reserved', programs = '$program_name', schedule = '$final_schedule' WHERE student_number = '$student_number'";
            $conn->query($sql_30);
            echo $sql_30;
        }

        // Determine type
        $type = '';
        if (!empty($_POST['online_hidden'])) {
            $type = 'Online';
        } elseif (!empty($_POST['f2f_hidden'])) {
            $type = 'F2F';
        }

        // Insert into POS table
        $sql = "INSERT INTO pos 
            (`student_number`, `program_name`, `package_number`, `student_name`, `demo1`, `demo2`, `demo3`, `demo4`, `payment_status`, `type`, `cash`, `change`, `balance`, `discount`, `date_created`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssiiiissiiiss",
            $student_number,
            $program_name,
            $package_number,
            $student_name,
            $demo1,
            $demo2,
            $demo3,
            $demo4,
            $payment_status,
            $type,
            $cash,
            $change,
            $balance,
            $discount,
            $date_created
        );


        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();

            // Receipt Output
            echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; padding: 20px; }
        .receipt { background: #fff; max-width: 400px; margin: auto; padding: 25px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .receipt h2 { text-align: center; margin-bottom: 20px; }
        .line { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #ddd; }
        .total { font-weight: bold; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
        @media print {
            body { background: none; padding: 0; }
            .receipt { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt">
        <h2>Care Pro</h2>
        <div class="line"><span>Student:</span><span>{$student_name}</span></div>
        <div class="line"><span>Student No:</span><span>{$student_number}</span></div>
        <div class="line"><span>Program:</span><span>{$program_name}</span></div>
        <div class="line"><span>Package #:</span><span>{$package_number}</span></div>
        <div class="line"><span>Type:</span><span>{$type}</span></div>
        <div class="line"><span>Status:</span><span>{$payment_status}</span></div>
        <div class="line"><span>Demo 1:</span><span>₱{$demo1}</span></div>
        <div class="line"><span>Demo 2:</span><span>₱{$demo2}</span></div>
        <div class="line"><span>Demo 3:</span><span>₱{$demo3}</span></div>
        <div class="line"><span>Demo 4:</span><span>₱{$demo4}</span></div>
        <div class="line total"><span>Cash:</span><span>₱{$cash}</span></div>
        <div class="line total"><span>Change:</span><span>₱{$change}</span></div>
        <div class="line total"><span>Balance:</span><span>₱{$balance}</span></div>
        <div class="line"><span>Discount:</span><span>{$discount}%</span></div>

        <div class="line"><span>Schedule:</span> <br><span>{$final_schedule}</span></div>
        <div class="footer">Thank you for your payment!<br><a href="../pos.php">Back to POS</a></div>
    </div>
</body>
</html>
HTML;
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Program not found.";
    }
}

?>