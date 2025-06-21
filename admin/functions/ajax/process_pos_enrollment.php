<?php
// functions/ajax/process_pos_enrollment.php


header('Content-Type: application/json');
include "../../connection/connection.php";


try {
    $conn = con();

    // Validate required fields
    $required_fields = ['student_id', 'program_id', 'learning_mode', 'payment_type', 'amount_paid'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit;
        }
    }

    // Extract and sanitize data
    $student_id = intval($_POST['student_id']);
    $program_id = intval($_POST['program_id']);
    $learning_mode = $_POST['learning_mode'];
    $payment_type = $_POST['payment_type'];
    $demo_type = $_POST['demo_type'] ?? null;
    $amount_paid = floatval($_POST['amount_paid']);
    $cash_received = floatval($_POST['cash_received']);
    $change_given = floatval($_POST['change_given']);
    $total_program_cost = floatval($_POST['total_program_cost']);
    $package_name = $_POST['package_name'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? '[]';
    $enrollment_status = $_POST['enrollment_status'] ?? 'Enrolled';
    $promo_applied = $_POST['promo_applied'] ?? '';


    date_default_timezone_set('Asia/Manila');
    $date_created2 = date('Y-m-d H:i:s');
    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Added Credit or new Transaction to : $student_id', '$date_created2' )");




    // Calculate financial details
    $promo_discount = 0;
    $system_fee = 0;
    $subtotal = $total_program_cost;

    if (!empty($promo_applied)) {
        $promo_data = json_decode($promo_applied, true);
        if ($promo_data) {
            if ($promo_data['promo_type'] === 'percentage') {
                $promo_discount = $subtotal * (floatval($promo_data['percentage']) / 100);
            } else if ($promo_data['promo_type'] === 'fixed') {
                $promo_discount = floatval($promo_data['enrollment_fee']);
            }
        }
    }

    // Add system fee for online learning
    if ($learning_mode === 'Online') {
        $program_query = "SELECT system_fee FROM program WHERE id = ?";
        $program_stmt = $conn->prepare($program_query);
        $program_stmt->bind_param("i", $program_id);
        $program_stmt->execute();
        $program_result = $program_stmt->get_result();
        if ($program_result && $program_row = $program_result->fetch_assoc()) {
            $system_fee = floatval($program_row['system_fee']);
            $subtotal += $system_fee;
        }
    }

    $final_total = $subtotal - $promo_discount;
    $balance = $final_total - $amount_paid;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert POS transaction
        $pos_query = "
            INSERT INTO pos_transactions (
                student_id, program_id, learning_mode, package_name, payment_type, 
                demo_type, selected_schedules, subtotal, promo_discount, system_fee, 
                total_amount, cash_received, change_amount, balance, enrollment_status, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
        ";

        $pos_stmt = $conn->prepare($pos_query);
        $pos_stmt->bind_param(
            "iisssssddddddds",
            $student_id,
            $program_id,
            $learning_mode,
            $package_name,
            $payment_type,
            $demo_type,
            $selected_schedules,
            $subtotal,
            $promo_discount,
            $system_fee,
            $final_total,
            $cash_received,
            $change_given,
            $balance,
            $enrollment_status
        );

        if (!$pos_stmt->execute()) {
            throw new Exception("Failed to insert POS transaction");
        }

        $transaction_id = $conn->insert_id;

        // Insert enrollment record
        $enrollment_query = "
            INSERT INTO student_enrollments (
                student_id, program_id, pos_transaction_id, learning_mode, 
                selected_schedules, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";

        $enrollment_stmt = $conn->prepare($enrollment_query);
        $enrollment_stmt->bind_param(
            "iiisss",
            $student_id,
            $program_id,
            $transaction_id,
            $learning_mode,
            $selected_schedules,
            $enrollment_status
        );

        if (!$enrollment_stmt->execute()) {
            throw new Exception("Failed to insert enrollment record");
        }

        // Update student status
        $student_update_query = "
            UPDATE student_info_tbl 
            SET enrollment_status = ?, programs = CONCAT(IFNULL(programs, ''), ?, ',')
            WHERE id = ?
        ";

        $student_stmt = $conn->prepare($student_update_query);
        $program_name_query = "SELECT program_name FROM program WHERE id = ?";
        $program_name_stmt = $conn->prepare($program_name_query);
        $program_name_stmt->bind_param("i", $program_id);
        $program_name_stmt->execute();
        $program_name_result = $program_name_stmt->get_result();
        $program_name = $program_name_result->fetch_assoc()['program_name'] ?? '';

        $student_stmt->bind_param("ssi", $enrollment_status, $program_name, $student_id);
        $student_stmt->execute();




        // Count locked schedules
        $locked_count = 0;
        $schedule_locked = false;

        if ($payment_type !== 'reservation' && !empty($selected_schedules)) {
            $schedules = json_decode($selected_schedules, true);
            if ($schedules && is_array($schedules)) {
                $locked_count = count($schedules);
                $schedule_locked = true;
            }
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Enrollment processed successfully',
            'transaction_id' => $transaction_id,
            'schedule_locked' => $schedule_locked,
            'locked_count' => $locked_count,
            'balance' => $balance
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process enrollment: ' . $e->getMessage()
    ]);
}
?>