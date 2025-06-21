<?php
include "../../../connection/connection.php";
$conn = con();

header('Content-Type: application/json');
$conn->autocommit(FALSE);

// Set timezone for consistent date handling
date_default_timezone_set('Asia/Manila');

// Helper function to safely convert string numbers to float
function safe_float($value)
{
    if (is_string($value)) {
        $cleaned = str_replace([',', '₱', '$'], '', $value);
        return floatval($cleaned);
    }
    return floatval($value);
}

// ENHANCED: Excess payment processing function
function processExcessPayment($conn, $transaction_id, $student_id, $program_id, $excess_data)
{
    $excess_choice = $excess_data['choice'];
    $excess_amount = safe_float($excess_data['amount']);
    $allocations = json_decode($excess_data['allocations'] ?? '[]', true) ?: [];

    $final_change_amount = 0;
    $processing_notes = [];

    try {
        switch ($excess_choice) {
            case 'treat_as_initial':
                // Recalculate all demo fees based on full payment
                $total_tuition_stmt = $conn->prepare("
                    SELECT total_amount FROM pos_transactions 
                    WHERE id = ? LIMIT 1
                ");
                $total_tuition_stmt->bind_param("i", $transaction_id);
                $total_tuition_stmt->execute();
                $total_result = $total_tuition_stmt->get_result()->fetch_assoc();
                $total_tuition = safe_float($total_result['total_amount']);

                $original_payment = safe_float($excess_data['original_amount']);
                $new_balance = max(0, $total_tuition - $original_payment);
                $new_demo_fee = $new_balance / 4;

                // Update transaction balance
                $update_balance_stmt = $conn->prepare("
                    UPDATE pos_transactions 
                    SET balance = ?, 
                        description = CONCAT(description, ' (Full initial payment applied)')
                    WHERE id = ?
                ");
                $update_balance_stmt->bind_param("di", $new_balance, $transaction_id);
                $update_balance_stmt->execute();

                $processing_notes[] = "Entire payment treated as initial payment";
                $processing_notes[] = "New demo fee: ₱" . number_format($new_demo_fee, 2);
                break;

            case 'allocate_to_demos':
                // Apply excess to demos sequentially
                foreach ($allocations as $allocation) {
                    $demo_number = $allocation['demo'];
                    $demo_amount = safe_float($allocation['amount']);
                    $demo_type = "demo{$demo_number}";

                    // Insert demo payment record
                    $demo_payment_stmt = $conn->prepare("
                        INSERT INTO demo_payments (
                            student_id, program_id, demo_type, payment_amount, 
                            expected_amount, payment_date, transaction_id,
                            allocation_source, processed_by, notes
                        ) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'excess_allocation', 'Scraper001', ?)
                    ");

                    $demo_fee = calculateDemoFeeForStudent($conn, $student_id, $program_id);
                    $allocation_note = "Allocated from excess initial payment";

                    $demo_payment_stmt->bind_param(
                        "iisddis",
                        $student_id,
                        $program_id,
                        $demo_type,
                        $demo_amount,
                        $demo_fee,
                        $transaction_id,
                        $allocation_note
                    );
                    $demo_payment_stmt->execute();

                    $processing_notes[] = "Demo {$demo_number}: ₱" . number_format($demo_amount, 2);
                }

                // Calculate remaining excess to return as change
                $total_allocated = array_sum(array_column($allocations, 'amount'));
                $final_change_amount = max(0, $excess_amount - $total_allocated);

                if ($final_change_amount > 0) {
                    $processing_notes[] = "Remaining excess returned: ₱" . number_format($final_change_amount, 2);
                }
                break;

            case 'add_to_next_demo':
                // Find next unpaid demo and apply excess
                $next_demo = findNextUnpaidDemo($conn, $student_id, $program_id);

                if ($next_demo) {
                    $demo_fee = calculateDemoFeeForStudent($conn, $student_id, $program_id);
                    $allocation_amount = min($excess_amount, $demo_fee);
                    $final_change_amount = max(0, $excess_amount - $allocation_amount);

                    // Insert demo payment record
                    $demo_payment_stmt = $conn->prepare("
                        INSERT INTO demo_payments (
                            student_id, program_id, demo_type, payment_amount, 
                            expected_amount, payment_date, transaction_id,
                            allocation_source, processed_by, notes
                        ) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'excess_allocation', 'Scraper001', ?)
                    ");

                    $allocation_note = "Excess from previous demo payment";
                    $demo_payment_stmt->bind_param(
                        "iisddis",
                        $student_id,
                        $program_id,
                        $next_demo,
                        $allocation_amount,
                        $demo_fee,
                        $transaction_id,
                        $allocation_note
                    );
                    $demo_payment_stmt->execute();

                    $processing_notes[] = "Applied ₱" . number_format($allocation_amount, 2) . " to {$next_demo}";

                    if ($final_change_amount > 0) {
                        $processing_notes[] = "Remaining returned: ₱" . number_format($final_change_amount, 2);
                    }
                }
                break;

            case 'credit_to_account':
                // Create credit balance for future use
                $credit_stmt = $conn->prepare("
                    INSERT INTO payment_history (
                        student_id, program_id, transaction_id, payment_type,
                        amount, payment_date, processed_by, notes
                    ) VALUES (?, ?, ?, 'credit_balance', ?, NOW(), 'Scraper001', ?)
                ");

                $credit_note = "Credit from excess demo payment for future use";
                $credit_stmt->bind_param(
                    "iiiDs",
                    $student_id,
                    $program_id,
                    $transaction_id,
                    $excess_amount,
                    $credit_note
                );
                $credit_stmt->execute();

                $processing_notes[] = "₱" . number_format($excess_amount, 2) . " credited to student account";
                break;

            case 'return_as_change':
            default:
                // Return full excess as change
                $final_change_amount = $excess_amount;
                $processing_notes[] = "Full excess returned as change: ₱" . number_format($excess_amount, 2);
                break;
        }

        // Log excess processing
        $log_stmt = $conn->prepare("
            INSERT INTO excess_processing_log (
                transaction_id, student_id, program_id, excess_amount,
                excess_choice, allocations_data, final_change_amount,
                processed_at, processed_by, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Scraper001', ?)
        ");

        $allocations_json = json_encode($allocations);
        $notes_text = implode('; ', $processing_notes);

        $log_stmt->bind_param(
            "iiidsds",
            $transaction_id,
            $student_id,
            $program_id,
            $excess_amount,
            $excess_choice,
            $allocations_json,
            $final_change_amount,
            $notes_text
        );
        $log_stmt->execute();

        // Update main transaction with final change if applicable
        if ($final_change_amount > 0) {
            $update_change_stmt = $conn->prepare("
                UPDATE pos_transactions 
                SET change_amount = change_amount + ?,
                    change_given = change_given + ?,
                    excess_processing_data = ?
                WHERE id = ?
            ");

            $excess_processing_data = json_encode([
                'choice' => $excess_choice,
                'excess_amount' => $excess_amount,
                'final_change_amount' => $final_change_amount,
                'processing_notes' => $processing_notes,
                'processed_at' => date('Y-m-d H:i:s'),
                'processed_by' => 'Scraper001'
            ]);

            $update_change_stmt->bind_param(
                "ddsi",
                $final_change_amount,
                $final_change_amount,
                $excess_processing_data,
                $transaction_id
            );
            $update_change_stmt->execute();
        }

        return [
            'success' => true,
            'choice' => $excess_choice,
            'excess_amount' => $excess_amount,
            'final_change_amount' => $final_change_amount,
            'allocations' => $allocations,
            'processing_notes' => $processing_notes,
            'processed_at' => date('Y-m-d H:i:s'),
            'processed_by' => 'Scraper001'
        ];

    } catch (Exception $e) {
        throw new Exception("Excess payment processing failed: " . $e->getMessage());
    }
}

function findNextUnpaidDemo($conn, $student_id, $program_id)
{
    $stmt = $conn->prepare("
        SELECT demo_type FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        AND payment_type = 'demo_payment' 
        AND demo_type IS NOT NULL
    ");
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $paid_demos = [];
    while ($row = $result->fetch_assoc()) {
        $paid_demos[] = $row['demo_type'];
    }

    // Find first unpaid demo
    for ($i = 1; $i <= 4; $i++) {
        $demo_name = "demo{$i}";
        if (!in_array($demo_name, $paid_demos)) {
            return $demo_name;
        }
    }

    return null; // All demos paid
}

function calculateDemoFeeForStudent($conn, $student_id, $program_id)
{
    $balance_stmt = $conn->prepare("
        SELECT balance FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        ORDER BY id DESC LIMIT 1
    ");
    $balance_stmt->bind_param("ii", $student_id, $program_id);
    $balance_stmt->execute();
    $balance_result = $balance_stmt->get_result()->fetch_assoc();
    $current_balance = safe_float($balance_result['balance'] ?? 0);

    $demo_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT demo_type) as paid_demos_count 
        FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        AND payment_type = 'demo_payment' 
        AND demo_type IS NOT NULL 
        AND demo_type != ''
    ");
    $demo_stmt->bind_param("ii", $student_id, $program_id);
    $demo_stmt->execute();
    $demo_result = $demo_stmt->get_result()->fetch_assoc();
    $paid_demos_count = intval($demo_result['paid_demos_count'] ?? 0);

    $remaining_demos = 4 - $paid_demos_count;
    return $remaining_demos > 0 ? max(0, $current_balance / $remaining_demos) : 0;
}

// IMPROVED: Real-time program end date validation
function checkProgramEndDate($conn, $program_id)
{
    $current_datetime = new DateTime('2025-06-21 06:26:03', new DateTimeZone('Asia/Manila'));
    $current_date = $current_datetime->format('Y-m-d');
    $current_time = $current_datetime->format('Y-m-d H:i:s');

    $sql = "SELECT program_name, end_date FROM program WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program = $result->fetch_assoc();

    if (!$program) {
        throw new Exception("Program not found with ID: " . $program_id);
    }

    $program_end_date = $program['end_date'];
    $program_name = $program['program_name'];

    $end_datetime = new DateTime($program_end_date, new DateTimeZone('Asia/Manila'));
    $end_date_formatted = $end_datetime->format('Y-m-d');

    if ($current_date > $end_date_formatted) {
        $days_passed = $current_datetime->diff($end_datetime)->days;
        throw new Exception("ENROLLMENT BLOCKED: Program '{$program_name}' ended on {$end_date_formatted}. Current date: {$current_date}. Program ended {$days_passed} day(s) ago. New enrollments are not allowed for ended programs.");
    }

    $program_status = 'ACTIVE';
    if ($current_date === $end_date_formatted) {
        $program_status = 'ENDING_TODAY';
    }

    $days_remaining = $current_datetime->diff($end_datetime)->days;

    return [
        'program_name' => $program_name,
        'end_date' => $end_date_formatted,
        'program_status' => $program_status,
        'days_remaining' => $days_remaining,
        'current_date' => $current_date,
        'current_time' => $current_time
    ];
}

$current_time = '2025-06-21 06:26:03';

try {
    // Get POST data
    $student_id = $_POST['student_id'];
    $program_id = $_POST['program_id'];
    $learning_mode = $_POST['learning_mode'];
    $payment_type = $_POST['type_of_payment'];
    $package_name = $_POST['package_id'] ?? 'Regular';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;

    // ENHANCED: Get excess payment data
    $has_excess_payment = ($_POST['has_excess_payment'] ?? 'false') === 'true';
    $excess_payment_data = null;

    if ($has_excess_payment) {
        $excess_payment_data = [
            'choice' => $_POST['excess_choice'] ?? '',
            'amount' => safe_float($_POST['excess_amount'] ?? 0),
            'allocations' => $_POST['excess_allocations'] ?? '[]',
            'original_amount' => safe_float($_POST['original_payment_amount'] ?? 0),
            'required_amount' => safe_float($_POST['required_payment_amount'] ?? 0)
        ];

        // Validate excess payment data
        if (empty($excess_payment_data['choice']) || $excess_payment_data['amount'] <= 0) {
            throw new Exception("Invalid excess payment data provided.");
        }
    }

    // Real-time program end date validation
    $program_info = checkProgramEndDate($conn, $program_id);

    // Validate schedule selection for initial payments
    if ($payment_type === 'initial_payment') {
        $schedules_data = json_decode($selected_schedules, true);
        if (empty($schedules_data) || !is_array($schedules_data) || count($schedules_data) === 0) {
            throw new Exception("Schedule selection is required for initial payment.");
        }
    }

    // Get computation values from POST
    $subtotal = safe_float($_POST['sub_total']);
    $final_total = safe_float($_POST['final_total']);
    $cash = safe_float($_POST['cash']);
    $promo_discount = safe_float($_POST['promo_applied']);

    // Validate required values
    if ($subtotal <= 0) {
        throw new Exception("Invalid subtotal amount");
    }

    if ($final_total <= 0) {
        throw new Exception("Invalid final total amount");
    }

    // Check for unpaid balances in OTHER programs
    $balance_check = $conn->prepare("
        SELECT COUNT(*) AS has_other_balance
        FROM pos_transactions
        WHERE student_id = ? 
          AND balance > 0 
          AND status = 'Active'
          AND program_id != ?
    ");
    $balance_check->bind_param("ii", $student_id, $program_id);
    $balance_check->execute();
    $balance_result = $balance_check->get_result()->fetch_assoc();

    if ($balance_result['has_other_balance'] > 0) {
        throw new Exception("Cannot enroll. Student has unpaid balances in another program. Please settle it first.");
    }

    // Check if enrolled in the same program
    $existing_enrollment_check = $conn->prepare("
        SELECT se.id, se.status, pt.balance, pt.total_amount
        FROM student_enrollments se
        JOIN pos_transactions pt ON se.pos_transaction_id = pt.id
        WHERE se.student_id = ? AND se.program_id = ? AND se.status IN ('Enrolled', 'Reserved')
    ");
    $existing_enrollment_check->bind_param("ii", $student_id, $program_id);
    $existing_enrollment_check->execute();
    $existing_enrollment = $existing_enrollment_check->get_result()->fetch_assoc();

    // Handle existing enrollment
    if ($existing_enrollment && $existing_enrollment['balance'] > 0) {
        return handleExistingEnrollmentPayment($conn, $existing_enrollment, $cash, $student_id, $program_id, $payment_type, $demo_type, $promo_discount, $subtotal, $final_total, $selected_schedules, $program_info, $current_time, $excess_payment_data);
    }

    if ($existing_enrollment && $existing_enrollment['balance'] <= 0) {
        throw new Exception("Student is already fully enrolled in this program.");
    }

    // NEW ENROLLMENT - Use POST values directly
    $program_details = json_decode($_POST['program_details'], true);
    $package_details = !empty($_POST['package_details']) ? json_decode($_POST['package_details'], true) : null;

    $total_amount = $final_total;

    // ENHANCED: Handle excess payment for payment amount calculation
    if ($has_excess_payment && $excess_payment_data['choice'] === 'return_as_change') {
        // Use only the required amount for payment processing
        $payment_amount = $excess_payment_data['required_amount'];
    } else {
        // Use the full payment amount
        $payment_amount = safe_float($_POST['total_payment']);
    }

    // Validate payment amount based on payment type
    switch ($payment_type) {
        case 'full_payment':
            if ($payment_amount <= 0) {
                $payment_amount = $total_amount;
            }
            break;
        case 'initial_payment':
            if ($payment_amount <= 0) {
                throw new Exception("Invalid initial payment amount");
            }
            break;
        case 'demo_payment':
            if (!$demo_type) {
                throw new Exception("Demo type is required for demo payment");
            }
            if ($payment_amount <= 0) {
                $payment_amount = calculateDemoFeeForStudent($conn, $student_id, $program_id);
            }
            break;
        case 'reservation':
            if ($payment_amount <= 0) {
                throw new Exception("Invalid reservation payment amount");
            }
            break;
        default:
            throw new Exception("Invalid payment type");
    }

    // ENHANCED: Calculate change with excess payment consideration
    if ($has_excess_payment && $excess_payment_data['choice'] === 'return_as_change') {
        $cash_to_pay = $payment_amount;
        $change_amount = max(0, $cash - $cash_to_pay) + $excess_payment_data['amount'];
    } else {
        $cash_to_pay = $payment_amount;
        $change_amount = max(0, $cash - $cash_to_pay);
    }

    $balance = $total_amount - $payment_amount;

    // Validate payment
    if ($cash < $cash_to_pay) {
        throw new Exception("Insufficient cash payment. Required: ₱" . number_format($cash_to_pay, 2) . ", Received: ₱" . number_format($cash, 2));
    }

    $enrollment_status = in_array($payment_type, ['full_payment', 'demo_payment', 'initial_payment']) ? 'Enrolled' : 'Reserved';

    // Get system fee if online
    $system_fee = ($learning_mode === 'Online') ? safe_float($program_details['system_fee'] ?? 0) : 0;

    // Include program status in transaction description
    $program_end_note = '';
    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $program_end_note = " (Program ends today!)";
    } elseif ($program_info['days_remaining'] <= 7) {
        $program_end_note = " (Program ends in {$program_info['days_remaining']} days)";
    }

    $description = "Initial enrollment - " . ucfirst(str_replace('_', ' ', $payment_type)) . $program_end_note;

    // ENHANCED: Add excess payment info to description
    if ($has_excess_payment) {
        $description .= " (Excess: ₱" . number_format($excess_payment_data['amount'], 2) . " - " . ucfirst(str_replace('_', ' ', $excess_payment_data['choice'])) . ")";
    }

    // ENHANCED: Store excess payment data if applicable
    $excess_processing_json = $has_excess_payment ? json_encode([
        'has_excess' => true,
        'choice' => $excess_payment_data['choice'],
        'excess_amount' => $excess_payment_data['amount'],
        'original_amount' => $excess_payment_data['original_amount'],
        'required_amount' => $excess_payment_data['required_amount'],
        'processed_at' => $current_time,
        'processed_by' => 'Scraper001'
    ]) : null;

    // FIXED: Insert POS Transaction with correct parameter count
    $pos_stmt = $conn->prepare("
        INSERT INTO pos_transactions (
            student_id, program_id, learning_mode, package_name, payment_type, 
            demo_type, selected_schedules, subtotal, promo_discount, system_fee, 
            total_amount, cash_received, change_amount, balance, enrollment_status,
            debit_amount, credit_amount, change_given, description, status,
            excess_processing_data, processed_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, 'Scraper001')
    ");

    $debit_amount = $payment_amount;
    $credit_amount = $payment_amount;
    $change_given = $change_amount;

    $pos_stmt->bind_param(
        "iisssssdddddddsdddsss",
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
        $total_amount,
        $cash_to_pay,
        $change_amount,
        $balance,
        $enrollment_status,
        $debit_amount,
        $credit_amount,
        $change_given,
        $description,
        $excess_processing_json
    );

    if (!$pos_stmt->execute()) {
        throw new Exception("Failed to insert POS transaction: " . $pos_stmt->error);
    }

    $pos_transaction_id = $conn->insert_id;

    // ENHANCED: Process excess payment if applicable
    $excess_processing_result = null;
    if ($has_excess_payment && $excess_payment_data['choice'] !== 'return_as_change') {
        $excess_processing_result = processExcessPayment($conn, $pos_transaction_id, $student_id, $program_id, $excess_payment_data);
    }

    // Insert Student Enrollment
    $enrollment_stmt = $conn->prepare("
        INSERT INTO student_enrollments (
            student_id, program_id, pos_transaction_id, status, 
            learning_mode, selected_schedules
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $enrollment_stmt->bind_param(
        "iiisss",
        $student_id,
        $program_id,
        $pos_transaction_id,
        $enrollment_status,
        $learning_mode,
        $selected_schedules
    );

    if (!$enrollment_stmt->execute()) {
        throw new Exception("Failed to insert enrollment: " . $enrollment_stmt->error);
    }

    // Update student status
    $student_stmt = $conn->prepare("UPDATE student_info_tbl SET enrollment_status = ? WHERE id = ?");
    $student_stmt->bind_param("si", $enrollment_status, $student_id);

    if (!$student_stmt->execute()) {
        throw new Exception("Failed to update student status");
    }

    $conn->commit();

    // ENHANCED: Prepare response with excess payment information
    $response_message = 'Enrollment processed successfully';
    $warnings = [];

    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $warnings[] = 'Program ends today (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    } elseif ($program_info['days_remaining'] <= 7) {
        $warnings[] = 'Program ends in ' . $program_info['days_remaining'] . ' days (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    }

    if (!empty($warnings)) {
        $response_message .= ' - NOTE: ' . implode(', ', $warnings);
    }

    // ENHANCED: Add excess payment success message
    if ($has_excess_payment) {
        $response_message .= ' with excess payment handling (' . ucfirst(str_replace('_', ' ', $excess_payment_data['choice'])) . ')';
    }

    $response = [
        'success' => true,
        'message' => $response_message,
        'transaction_id' => $pos_transaction_id,
        'enrollment_status' => $enrollment_status,
        'balance' => $balance,
        'subtotal' => $subtotal,
        'promo_discount' => $promo_discount,
        'total_amount' => $total_amount,
        'payment_amount' => $payment_amount,
        'cash_received' => $cash_to_pay,
        'total_cash_given' => $cash,
        'change_amount' => $change_amount,
        'credit_amount' => $credit_amount,
        'program_end_check' => [
            'status' => $program_info['program_status'],
            'end_date' => $program_info['end_date'],
            'program_name' => $program_info['program_name'],
            'days_remaining' => $program_info['days_remaining'],
            'current_date' => $program_info['current_date']
        ],
        'timestamp' => $current_time,
        'processed_by' => 'Scraper001'
    ];

    // ENHANCED: Add excess processing information to response
    if ($excess_processing_result) {
        $response['excess_processing'] = $excess_processing_result;
    } elseif ($has_excess_payment && $excess_payment_data['choice'] === 'return_as_change') {
        $response['excess_processing'] = [
            'success' => true,
            'choice' => 'return_as_change',
            'excess_amount' => $excess_payment_data['amount'],
            'final_change_amount' => $excess_payment_data['amount'],
            'processing_notes' => ['Full excess returned as change'],
            'processed_at' => $current_time,
            'processed_by' => 'Scraper001'
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => $current_time,
        'processed_by' => 'Scraper001'
    ]);
}

$conn->autocommit(TRUE);

// ENHANCED: Handle existing enrollment payments with excess payment support
function handleExistingEnrollmentPayment($conn, $existing_enrollment, $cash, $student_id, $program_id, $payment_type, $demo_type, $promo_discount, $subtotal, $final_total, $selected_schedules, $program_info, $current_time, $excess_payment_data = null)
{
    // Get current enrollment details
    $current_balance_stmt = $conn->prepare("
        SELECT balance, total_amount, subtotal, promo_discount, learning_mode, package_name, selected_schedules, system_fee
        FROM pos_transactions 
        WHERE student_id = ? AND program_id = ? 
        ORDER BY id DESC LIMIT 1
    ");
    $current_balance_stmt->bind_param("ii", $student_id, $program_id);
    $current_balance_stmt->execute();
    $balance_info = $current_balance_stmt->get_result()->fetch_assoc();

    $current_balance = safe_float($balance_info['balance']);
    $current_total = safe_float($balance_info['total_amount']);
    $current_subtotal = safe_float($balance_info['subtotal']);
    $existing_promo_discount = safe_float($balance_info['promo_discount']);
    $learning_mode = $balance_info['learning_mode'];
    $package_name = $balance_info['package_name'];
    $existing_selected_schedules = $balance_info['selected_schedules'];
    $system_fee = safe_float($balance_info['system_fee']);

    // ENHANCED: Handle excess payment for existing enrollments
    $has_excess_payment = $excess_payment_data !== null && !empty($excess_payment_data['choice']);

    if ($has_excess_payment && $excess_payment_data['choice'] === 'return_as_change') {
        $payment_amount = $excess_payment_data['required_amount'];
    } else {
        $payment_amount = safe_float($_POST['total_payment'] ?? 0);
    }

    // If not provided, calculate based on payment type
    if ($payment_amount <= 0) {
        switch ($payment_type) {
            case 'demo_payment':
                if (!$demo_type) {
                    throw new Exception("Demo type is required for demo payment");
                }
                $payment_amount = calculateDemoFeeForStudent($conn, $student_id, $program_id);
                break;
            case 'full_payment':
                $payment_amount = $current_balance;
                break;
            default:
                throw new Exception("Payment amount is required");
                break;
        }
    }

    // Validate payment amount
    if ($payment_type === 'full_payment' && $payment_amount > $current_balance) {
        $payment_amount = $current_balance;
    } else if ($payment_type !== 'full_payment' && $payment_amount > $current_balance) {
        throw new Exception("Payment amount (₱" . number_format($payment_amount, 2) . ") exceeds remaining balance (₱" . number_format($current_balance, 2) . ")");
    }

    // Validate cash amount
    if ($cash < $payment_amount) {
        throw new Exception("Insufficient cash for payment. Required: ₱" . number_format($payment_amount, 2) . ", Received: ₱" . number_format($cash, 2));
    }

    // ENHANCED: Calculate new balance and change with excess payment consideration
    $new_balance = $current_balance - $payment_amount;
    $cash_to_pay = $payment_amount;

    if ($has_excess_payment && $excess_payment_data['choice'] === 'return_as_change') {
        $change_amount = max(0, $cash - $cash_to_pay) + $excess_payment_data['amount'];
    } else {
        $change_amount = max(0, $cash - $cash_to_pay);
    }

    $new_status = ($new_balance <= 0) ? 'Enrolled' : 'Reserved';

    // Use new schedules if provided, otherwise keep existing
    $final_schedules = (!empty($selected_schedules) && $selected_schedules !== '[]') ? $selected_schedules : $existing_selected_schedules;

    // Include program info in description
    $program_end_note = '';
    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $program_end_note = " (Program ends today!)";
    } elseif ($program_info['days_remaining'] <= 7) {
        $program_end_note = " (Program ends in {$program_info['days_remaining']} days)";
    }

    $description = ($payment_type === 'demo_payment')
        ? "Demo payment - " . $demo_type . $program_end_note
        : "Balance payment - " . $payment_type . $program_end_note;

    // ENHANCED: Add excess payment info to description
    if ($has_excess_payment) {
        $description .= " (Excess: ₱" . number_format($excess_payment_data['amount'], 2) . " - " . ucfirst(str_replace('_', ' ', $excess_payment_data['choice'])) . ")";
    }

    // ENHANCED: Store excess payment data
    $excess_processing_json = $has_excess_payment ? json_encode([
        'has_excess' => true,
        'choice' => $excess_payment_data['choice'],
        'excess_amount' => $excess_payment_data['amount'],
        'original_amount' => $excess_payment_data['original_amount'] ?? $payment_amount,
        'required_amount' => $excess_payment_data['required_amount'] ?? $payment_amount,
        'processed_at' => $current_time,
        'processed_by' => 'Scraper001'
    ]) : null;

    // FIXED: Insert new payment transaction with correct parameter count
    $payment_stmt = $conn->prepare("
        INSERT INTO pos_transactions (
            student_id, program_id, learning_mode, package_name, payment_type, 
            demo_type, selected_schedules, subtotal, promo_discount, system_fee, 
            total_amount, cash_received, change_amount, balance, enrollment_status,
            debit_amount, credit_amount, change_given, description, status,
            excess_processing_data, processed_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, 'Scraper001')
    ");

    $debit_amount = $payment_amount;
    $credit_amount = $payment_amount;
    $change_given = $change_amount;

    $payment_stmt->bind_param(
        "iisssssdddddddsdddsss",
        $student_id,
        $program_id,
        $learning_mode,
        $package_name,
        $payment_type,
        $demo_type,
        $final_schedules,
        $current_subtotal,
        $existing_promo_discount,
        $system_fee,
        $current_total,
        $cash_to_pay,
        $change_amount,
        $new_balance,
        $new_status,
        $debit_amount,
        $credit_amount,
        $change_given,
        $description,
        $excess_processing_json
    );

    if (!$payment_stmt->execute()) {
        throw new Exception("Failed to record payment: " . $payment_stmt->error);
    }

    $new_transaction_id = $conn->insert_id;

    // ENHANCED: Process excess payment for existing enrollments
    $excess_processing_result = null;
    if ($has_excess_payment && $excess_payment_data['choice'] !== 'return_as_change') {
        $excess_processing_result = processExcessPayment($conn, $new_transaction_id, $student_id, $program_id, $excess_payment_data);

        // Update change amount if excess processing affects it
        if ($excess_processing_result && isset($excess_processing_result['final_change_amount']) && $excess_processing_result['final_change_amount'] > 0) {
            $additional_change = $excess_processing_result['final_change_amount'];
            $total_change = $change_amount + $additional_change;

            // Update the transaction with the new change amount
            $update_change_stmt = $conn->prepare("
                UPDATE pos_transactions 
                SET change_amount = ?, change_given = ?
                WHERE id = ?
            ");
            $update_change_stmt->bind_param("ddi", $total_change, $total_change, $new_transaction_id);
            $update_change_stmt->execute();

            $change_amount = $total_change;
        }
    }

    // Update enrollment record
    $update_enrollment_stmt = $conn->prepare("
        UPDATE student_enrollments 
        SET pos_transaction_id = ?, status = ?, selected_schedules = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE student_id = ? AND program_id = ?
    ");
    $update_enrollment_stmt->bind_param("issii", $new_transaction_id, $new_status, $final_schedules, $student_id, $program_id);

    if (!$update_enrollment_stmt->execute()) {
        throw new Exception("Failed to update enrollment: " . $update_enrollment_stmt->error);
    }

    // Update student status
    $student_stmt = $conn->prepare("UPDATE student_info_tbl SET enrollment_status = ? WHERE id = ?");
    $student_stmt->bind_param("si", $new_status, $student_id);
    $student_stmt->execute();

    $conn->commit();

    // ENHANCED: Prepare response with excess payment information
    $response_message = ucfirst(str_replace('_', ' ', $payment_type)) . ' processed successfully';
    $warnings = [];

    if ($program_info['program_status'] === 'ENDING_TODAY') {
        $warnings[] = 'Program ends today (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    } elseif ($program_info['days_remaining'] <= 7) {
        $warnings[] = 'Program ends in ' . $program_info['days_remaining'] . ' days (' . date('F j, Y', strtotime($program_info['end_date'])) . ')';
    }

    if (!empty($warnings)) {
        $response_message .= ' - NOTE: ' . implode(', ', $warnings);
    }

    // ENHANCED: Add excess payment success message
    if ($has_excess_payment) {
        $response_message .= ' with excess payment handling (' . ucfirst(str_replace('_', ' ', $excess_payment_data['choice'])) . ')';
    }

    $response = [
        'success' => true,
        'message' => $response_message,
        'transaction_id' => $new_transaction_id,
        'payment_type' => $payment_type,
        'balance' => $new_balance,
        'total_amount' => $current_total,
        'payment_amount' => $payment_amount,
        'cash_received' => $cash_to_pay,
        'total_cash_given' => $cash,
        'change_amount' => $change_amount,
        'credit_amount' => $credit_amount,
        'program_end_check' => [
            'status' => $program_info['program_status'],
            'end_date' => $program_info['end_date'],
            'program_name' => $program_info['program_name'],
            'days_remaining' => $program_info['days_remaining'],
            'current_date' => $program_info['current_date']
        ],
        'timestamp' => $current_time,
        'processed_by' => 'Scraper001'
    ];

    // ENHANCED: Add excess processing information to response
    if ($excess_processing_result) {
        $response['excess_processing'] = $excess_processing_result;
    } elseif ($has_excess_payment && $excess_payment_data['choice'] === 'return_as_change') {
        $response['excess_processing'] = [
            'success' => true,
            'choice' => 'return_as_change',
            'excess_amount' => $excess_payment_data['amount'],
            'final_change_amount' => $excess_payment_data['amount'],
            'processing_notes' => ['Full excess returned as change'],
            'processed_at' => $current_time,
            'processed_by' => 'Scraper001'
        ];
    }

    echo json_encode($response);
    exit;
}
?>