<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "careprodb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get student information
    $student_number = $_POST['student_number'];
    $student_name = $_POST['student_name']; // For receipt only

    // Get payment details
    $payment_mode = isset($_POST['mode']) && $_POST['mode'] == 'f2f' ? 'Face to Face' : 'Online';
    $total_tuition = floatval($_POST['original_total_tuition']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $discount_amount = floatval($_POST['discount_amount']);
    $final_tuition = floatval($_POST['final_tuition']);
    $amount_paid = floatval($_POST['total_pay']);
    $remaining_balance = floatval($_POST['balance']);
    $cash_tendered = floatval($_POST['Cash']);
    $change_amount = floatval($_POST['Change']);

    // Determine payment type
    $payment_type = "Partial";
    if (isset($_POST['full']) && $_POST['full'] == 'on') {
        $payment_type = "Full Payment";
    } elseif (isset($_POST['reserve']) && $_POST['reserve'] == 'on') {
        $payment_type = "Reserve";
    }

    // Generate a unique receipt number
    $receipt_number = "CP" . date("YmdHis") . rand(100, 999);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert into student_payments table
        $sql = "INSERT INTO student_payments (
                    student_number, payment_mode, total_tuition, 
                    discount_percentage, discount_amount, final_tuition, 
                    amount_paid, remaining_balance, payment_type, 
                    cash_tendered, change_amount, receipt_number
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssddddddsddss",
            $student_number,
            $payment_mode,
            $total_tuition,
            $discount_percentage,
            $discount_amount,
            $final_tuition,
            $amount_paid,
            $remaining_balance,
            $payment_type,
            $cash_tendered,
            $change_amount,
            $receipt_number
        );

        $stmt->execute();
        $payment_id = $stmt->insert_id;

        // Insert payment details
        $payment_items = [];

        // Check for partial payment
        if (isset($_POST['partial_amount']) && floatval($_POST['partial_amount']) > 0) {
            $payment_items['Partial'] = floatval($_POST['partial_amount']);
        }

        // Check for demos
        if (isset($_POST['demo1']) && $_POST['demo1'] == 'on') {
            $payment_items['Demo1'] = floatval($_POST['demo1_value']);
        }
        if (isset($_POST['demo2']) && $_POST['demo2'] == 'on') {
            $payment_items['Demo2'] = floatval($_POST['demo2_value']);
        }
        if (isset($_POST['demo3']) && $_POST['demo3'] == 'on') {
            $payment_items['Demo3'] = floatval($_POST['demo3_value']);
        }
        if (isset($_POST['demo4']) && $_POST['demo4'] == 'on') {
            $payment_items['Demo4'] = floatval($_POST['demo4_value']);
        }

        // Insert each payment item
        $sql = "INSERT INTO payment_details (payment_id, payment_item, amount) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($payment_items as $item => $amount) {
            $stmt->bind_param("isd", $payment_id, $item, $amount);
            $stmt->execute();
        }

        // Update or insert student balance
        $sql = "INSERT INTO student_balance (student_number, total_tuition, total_paid, remaining_balance) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                total_paid = total_paid + ?, 
                remaining_balance = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sddddd",
            $student_number,
            $final_tuition,
            $amount_paid,
            $remaining_balance,
            $amount_paid,
            $remaining_balance
        );
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Redirect to receipt page
        header("Location: print_receipt.php?receipt_number=" . $receipt_number);
        exit();

    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}

$conn->close();
?>