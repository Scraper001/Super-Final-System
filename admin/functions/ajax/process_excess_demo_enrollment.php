<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

include '../../../connection/connection.php';
$conn = con();

error_reporting(E_ALL);
ini_set('display_errors', 1);

function safe_float($value)
{
    if (is_null($value) || $value === '')
        return 0.0;
    return floatval($value);
}

function debug_log($message, $data = null)
{
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . ($data ? " Data: " . json_encode($data) : ""));
}

function getProgramDetails($conn, $program_id)
{
    $sql = "SELECT * FROM program WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row;
    }

    return [
        'program_name' => 'Unknown Program',
        'initial_fee' => 0,
        'reservation_fee' => 0,
        'assesment_fee' => 0,
        'tuition_fee' => 0,
        'total_tuition' => 0,
        'misc_fee' => 0
    ];
}

function getProgramTuitionFee($conn, $program_id)
{
    $sql = "SELECT total_tuition FROM program WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return safe_float($row['total_tuition']);
    }

    return 0;
}

function getInitialPaymentDetails($conn, $student_id, $program_id)
{
    $sql = "SELECT 
                SUM(cash_received) as total_initial_payment,
                MAX(promo_discount) as promo_discount,
                MAX(subtotal) as subtotal,
                MAX(total_amount) as total_amount
            FROM pos_transactions 
            WHERE student_id = ? AND program_id = ? AND payment_type = 'initial_payment'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return [
            'total_amount' => safe_float($row['total_amount']),
            'initial_payment' => safe_float($row['total_initial_payment']),
            'promo_discount' => safe_float($row['promo_discount']),
            'subtotal' => safe_float($row['subtotal'])
        ];
    }

    return [
        'total_amount' => 0,
        'initial_payment' => 0,
        'promo_discount' => 0,
        'subtotal' => 0
    ];
}

function getReservationPayment($conn, $student_id, $program_id)
{
    $sql = "SELECT SUM(cash_received) as total_paid FROM pos_transactions 
            WHERE student_id = ? AND program_id = ? AND payment_type = 'reservation'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return safe_float($row['total_paid'] ?? 0);
}

function getCurrentDemoPayment($conn, $student_id, $program_id, $demo_type)
{
    $sql = "SELECT SUM(cash_received) as total_paid FROM pos_transactions 
            WHERE student_id = ? AND program_id = ? AND demo_type = ? AND payment_type = 'demo_payment'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $student_id, $program_id, $demo_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return safe_float($row['total_paid'] ?? 0);
}

/**
 * CORRECTED: Calculate demo fee using the after-promo amount
 * Demo Fee = (After Promo Amount - Reservation - Initial) ÷ 4
 */
function calculateCorrectDemoFee($conn, $student_id, $program_id)
{
    // Get program total tuition
    $total_tuition = getProgramTuitionFee($conn, $program_id);

    // Get promo discount from any existing transaction
    $promo_discount = 0;
    $promo_sql = "SELECT promo_discount FROM pos_transactions 
                  WHERE student_id = ? AND program_id = ? AND promo_discount > 0 
                  ORDER BY transaction_date ASC LIMIT 1";
    $promo_stmt = $conn->prepare($promo_sql);
    $promo_stmt->bind_param("ii", $student_id, $program_id);
    $promo_stmt->execute();
    $promo_result = $promo_stmt->get_result();
    if ($promo_row = $promo_result->fetch_assoc()) {
        $promo_discount = safe_float($promo_row['promo_discount']);
    }

    // Calculate after-promo amount
    $after_promo_amount = $total_tuition - $promo_discount;

    // Get payments that reduce demo amount
    $reservation_payment = getReservationPayment($conn, $student_id, $program_id);
    $initial_details = getInitialPaymentDetails($conn, $student_id, $program_id);
    $initial_payment = $initial_details['initial_payment'];

    // CORRECTED FORMULA: (After Promo - Reservation - Initial) ÷ 4
    $demo_fee = ($after_promo_amount - $reservation_payment - $initial_payment) / 4;

    error_log(sprintf(
        "[%s] CORRECTED Demo Fee Calculation - User: %s
        Total Tuition: ₱%s
        Promo Discount: ₱%s
        After Promo: ₱%s
        Reservation: ₱%s
        Initial: ₱%s
        CORRECT Demo Fee: ₱%s",
        date('Y-m-d H:i:s'),
        'Scraper001',
        number_format($total_tuition, 2),
        number_format($promo_discount, 2),
        number_format($after_promo_amount, 2),
        number_format($reservation_payment, 2),
        number_format($initial_payment, 2),
        number_format($demo_fee, 2)
    ));

    return $demo_fee;
}

/**
 * CRITICAL: Handle demo overpayments by creating adjustment records
 */
function handleDemoOverpaymentAdjustments($conn, $student_id, $program_id)
{
    $demo_sequence = ['demo1', 'demo2', 'demo3', 'demo4'];
    $correct_demo_fee = calculateCorrectDemoFee($conn, $student_id, $program_id);

    $adjustments = [];
    $total_overpayment = 0;

    foreach ($demo_sequence as $demo) {
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $overpayment = $current_paid - $correct_demo_fee;

        if ($overpayment > 0.01) { // Overpaid by more than 1 cent
            // Create adjustment record to handle overpayment
            $payment_type = 'demo_adjustment';
            $status = 'Active';
            $description = sprintf(
                'Overpayment adjustment: Paid ₱%s, Required ₱%s, Overpaid ₱%s',
                number_format($current_paid, 2),
                number_format($correct_demo_fee, 2),
                number_format($overpayment, 2)
            );
            $processed_by = 'Scraper001';

            $sql_adjustment = "INSERT INTO pos_transactions 
                              (student_id, program_id, payment_type, demo_type, 
                               cash_received, balance, status, description, processed_by, transaction_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql_adjustment);
            $negative_amount = -$overpayment; // Negative to represent refund/credit
            $new_balance = 0; // Demo is now correctly balanced

            $stmt->bind_param(
                "iissddsss",
                $student_id,
                $program_id,
                $payment_type,
                $demo,
                $negative_amount,
                $new_balance,
                $status,
                $description,
                $processed_by
            );

            if ($stmt->execute()) {
                $adjustments[] = [
                    'demo_type' => $demo,
                    'overpayment' => $overpayment,
                    'adjustment_amount' => $negative_amount,
                    'original_paid' => $current_paid,
                    'correct_amount' => $correct_demo_fee,
                    'description' => $description
                ];

                $total_overpayment += $overpayment;

                error_log(sprintf(
                    "[%s] Demo Overpayment Adjustment - User: %s
                    Demo: %s | Overpayment: ₱%s | Adjustment: ₱%s",
                    date('Y-m-d H:i:s'),
                    'Scraper001',
                    $demo,
                    number_format($overpayment, 2),
                    number_format($negative_amount, 2)
                ));
            }
        }
    }

    return [
        'adjustments' => $adjustments,
        'total_overpayment' => $total_overpayment,
        'correct_demo_fee' => $correct_demo_fee
    ];
}

/**
 * FIXED: Process excess from initial payment with overpayment handling
 */
function processExcessFromInitialPayment($conn, $student_id, $program_id, $excess_amount, $learning_mode)
{
    // First, handle any existing overpayments
    $overpayment_adjustments = handleDemoOverpaymentAdjustments($conn, $student_id, $program_id);

    $demo_sequence = ['demo1', 'demo2', 'demo3', 'demo4'];
    $correct_demo_fee = $overpayment_adjustments['correct_demo_fee'];

    // FIXED: Get original package information to maintain consistency
    $package_info_sql = "SELECT package_name, promo_discount FROM pos_transactions 
                        WHERE student_id = ? AND program_id = ? AND payment_type = 'initial_payment' 
                        ORDER BY transaction_date DESC LIMIT 1";
    $package_stmt = $conn->prepare($package_info_sql);
    $package_stmt->bind_param("ii", $student_id, $program_id);
    $package_stmt->execute();
    $package_result = $package_stmt->get_result();

    $original_package_name = 'Regular Package';
    if ($package_row = $package_result->fetch_assoc()) {
        $original_package_name = $package_row['package_name'] ?? 'Regular Package';
    }
    $package_stmt->close();

    $remaining_excess = $excess_amount;
    $allocations = [];

    error_log(sprintf(
        "[%s] Starting Excess Allocation with Package Preservation - User: %s
        Original Package: %s | Excess Amount: ₱%s | Correct Demo Fee: ₱%s",
        date('Y-m-d H:i:s'),
        'Scraper001',
        $original_package_name,
        number_format($excess_amount, 2),
        number_format($correct_demo_fee, 2)
    ));

    // Now allocate excess to demos that still need payment (after adjustments)
    foreach ($demo_sequence as $demo) {
        if ($remaining_excess <= 0.01)
            break;

        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $needed_amount = $correct_demo_fee - $current_paid;

        // Only allocate if this demo actually needs payment
        if ($needed_amount > 0.01) {
            $allocation_amount = min($remaining_excess, $needed_amount);

            // Create variables for bind_param
            $payment_type = 'demo_payment';
            $status = 'Active';
            $description = sprintf(
                'Allocation from initial payment excess (package: %s, corrected demo fee ₱%s)',
                $original_package_name,
                number_format($correct_demo_fee, 2)
            );
            $processed_by = 'Scraper001';

            // FIXED: Include package_name in allocation records
            $sql_insert = "INSERT INTO pos_transactions 
                          (student_id, program_id, learning_mode, payment_type, demo_type, package_name,
                           cash_received, balance, status, description, processed_by, transaction_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql_insert);
            $new_balance = $needed_amount - $allocation_amount;

            $stmt->bind_param(
                "iissssddsss",  // Added 's' for package_name
                $student_id,
                $program_id,
                $learning_mode,
                $payment_type,
                $demo,
                $original_package_name,  // FIXED: Include original package name
                $allocation_amount,
                $new_balance,
                $status,
                $description,
                $processed_by
            );

            if (!$stmt->execute()) {
                error_log("Error inserting allocation with package preservation: " . $stmt->error);
                continue;
            }

            $allocations[] = [
                'demo_type' => $demo,
                'amount' => $allocation_amount,
                'needed_amount' => $needed_amount,
                'new_balance' => $new_balance,
                'correct_demo_fee' => $correct_demo_fee,
                'current_paid_before' => $current_paid,
                'current_paid_after' => $current_paid + $allocation_amount,
                'fully_paid' => ($new_balance <= 0.01),
                'package_preserved' => $original_package_name  // FIXED: Track package preservation
            ];

            $remaining_excess -= $allocation_amount;

            error_log(sprintf(
                "[%s] Demo Allocation with Package Preservation - User: %s
                Demo: %s | Allocated: ₱%s | Package: %s | New Balance: ₱%s",
                date('Y-m-d H:i:s'),
                'Scraper001',
                $demo,
                number_format($allocation_amount, 2),
                $original_package_name,
                number_format($new_balance, 2)
            ));
        }
    }

    return [
        'allocations' => $allocations,
        'remaining_excess' => $remaining_excess,
        'correct_demo_fee' => $correct_demo_fee,
        'original_excess' => $excess_amount,
        'overpayment_adjustments' => $overpayment_adjustments,
        'package_preserved' => $original_package_name  // FIXED: Return preserved package info
    ];
}

/**
 * FIXED: Process excess allocation for demo payments - NOW CONTINUES TO ALL SUBSEQUENT DEMOS
 */
function processExcessAllocationForDemo($conn, $student_id, $program_id, $current_demo_type, $excess_amount, $learning_mode, $original_package_name = 'Regular Package')
{
    $demo_sequence = ['demo1', 'demo2', 'demo3', 'demo4'];
    $current_index = array_search($current_demo_type, $demo_sequence);

    if ($current_index === false) {
        return [
            'allocations' => [],
            'remaining_excess' => $excess_amount,
            'reason' => 'Invalid demo type'
        ];
    }

    $correct_demo_fee = calculateCorrectDemoFee($conn, $student_id, $program_id);
    $remaining_excess = $excess_amount;
    $allocations = [];

    // Get original promo discount to preserve in allocations
    $promo_discount_from_original = 0;
    $promo_sql = "SELECT promo_discount FROM pos_transactions 
                  WHERE student_id = ? AND program_id = ? AND promo_discount > 0 
                  ORDER BY transaction_date ASC LIMIT 1";
    $promo_stmt = $conn->prepare($promo_sql);
    $promo_stmt->bind_param("ii", $student_id, $program_id);
    $promo_stmt->execute();
    $promo_result = $promo_stmt->get_result();
    if ($promo_row = $promo_result->fetch_assoc()) {
        $promo_discount_from_original = safe_float($promo_row['promo_discount']);
    }
    $promo_stmt->close();

    error_log(sprintf(
        "[%s] FIXED: Starting Enhanced Demo Allocation - User: %s
        Current Demo: %s | Excess Amount: ₱%s | Demo Fee: ₱%s",
        date('Y-m-d H:i:s'),
        'Scraper001',
        $current_demo_type,
        number_format($excess_amount, 2),
        number_format($correct_demo_fee, 2)
    ));

    // FIXED: Allocate to ALL demos after the current one, not just the next one
    for ($i = $current_index + 1; $i < count($demo_sequence); $i++) {
        if ($remaining_excess <= 0.01) {
            error_log(sprintf("[%s] No more excess to allocate, stopping at demo index %d", date('Y-m-d H:i:s'), $i));
            break;
        }

        $demo = $demo_sequence[$i];
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $needed_amount = $correct_demo_fee - $current_paid;

        error_log(sprintf(
            "[%s] Processing %s: Current Paid: ₱%s, Needed: ₱%s, Remaining Excess: ₱%s",
            date('Y-m-d H:i:s'),
            $demo,
            number_format($current_paid, 2),
            number_format($needed_amount, 2),
            number_format($remaining_excess, 2)
        ));

        if ($needed_amount > 0.01) {
            $allocation_amount = min($remaining_excess, $needed_amount);
            $new_balance = $needed_amount - $allocation_amount;

            // Create variables matching your database schema
            $payment_type = 'demo_payment';
            $subtotal = $allocation_amount; // Required field in your schema
            $total_amount = $allocation_amount; // Required field
            $cash_received = $allocation_amount; // Required field
            $change_amount = 0; // Required field
            $status = 'Active';
            $enrollment_status = 'Enrolled'; // Required field
            $description = sprintf(
                'Excess allocation from %s payment (package: %s, corrected demo fee ₱%s, sequence: %d of %d)',
                $current_demo_type,
                $original_package_name,
                number_format($correct_demo_fee, 2),
                $i - $current_index,
                count($demo_sequence) - $current_index - 1
            );
            $processed_by = 'Scraper001';

            // FIXED: SQL matching your actual database schema
            $sql_insert = "INSERT INTO pos_transactions 
                          (student_id, program_id, learning_mode, package_name, payment_type, demo_type,
                           subtotal, promo_discount, total_amount, cash_received, change_amount, balance,
                           status, enrollment_status, description, processed_by, transaction_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql_insert);

            // FIXED: 16 parameters matching the SQL statement above
            $stmt->bind_param(
                "iissssddddddssss", // 16 characters for 16 parameters
                $student_id,                    // i
                $program_id,                    // i
                $learning_mode,                 // s
                $original_package_name,         // s
                $payment_type,                  // s
                $demo,                          // s (demo_type)
                $subtotal,                      // d
                $promo_discount_from_original,  // d
                $total_amount,                  // d
                $cash_received,                 // d
                $change_amount,                 // d
                $new_balance,                   // d (balance)
                $status,                        // s
                $enrollment_status,             // s
                $description,                   // s
                $processed_by                   // s
            );

            if (!$stmt->execute()) {
                error_log("Error inserting demo allocation for {$demo}: " . $stmt->error);
                continue;
            }

            $allocations[] = [
                'demo_type' => $demo,
                'amount' => $allocation_amount,
                'needed_amount' => $needed_amount,
                'new_balance' => $new_balance,
                'correct_demo_fee' => $correct_demo_fee,
                'current_paid_before' => $current_paid,
                'current_paid_after' => $current_paid + $allocation_amount,
                'package_preserved' => $original_package_name,
                'promo_discount_preserved' => $promo_discount_from_original,
                'fully_paid' => ($new_balance <= 0.01),
                'sequence_number' => $i - $current_index
            ];

            $remaining_excess -= $allocation_amount;

            error_log(sprintf(
                "[%s] FIXED: Successfully allocated to %s - Amount: ₱%s, New Balance: ₱%s, Remaining Excess: ₱%s",
                date('Y-m-d H:i:s'),
                $demo,
                number_format($allocation_amount, 2),
                number_format($new_balance, 2),
                number_format($remaining_excess, 2)
            ));
        } else {
            error_log(sprintf(
                "[%s] %s already fully paid (₱%s >= ₱%s), skipping",
                date('Y-m-d H:i:s'),
                $demo,
                number_format($current_paid, 2),
                number_format($correct_demo_fee, 2)
            ));
        }
    }

    error_log(sprintf(
        "[%s] FIXED: Demo allocation completed - Allocated to %d demos, Remaining excess: ₱%s",
        date('Y-m-d H:i:s'),
        count($allocations),
        number_format($remaining_excess, 2)
    ));

    return [
        'allocations' => $allocations,
        'remaining_excess' => $remaining_excess,
        'correct_demo_fee' => $correct_demo_fee,
        'package_preserved' => $original_package_name,
        'promo_discount_preserved' => $promo_discount_from_original
    ];
}

function getDemoPaymentRequirement($conn, $student_id, $program_id, $demo_type)
{
    return calculateCorrectDemoFee($conn, $student_id, $program_id);
}

function getPaymentSummary($conn, $student_id, $program_id)
{
    $demo_types = ['demo1', 'demo2', 'demo3', 'demo4'];
    $correct_demo_fee = calculateCorrectDemoFee($conn, $student_id, $program_id);
    $summary = [];

    foreach ($demo_types as $demo) {
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $remaining = $correct_demo_fee - $current_paid;

        $status = 'NOT PAID';
        if ($current_paid >= $correct_demo_fee) {
            $status = $current_paid > $correct_demo_fee ? 'OVERPAID' : 'PAID';
        } elseif ($current_paid > 0) {
            $status = 'PARTIALLY PAID';
        }

        $summary[] = [
            'demo_type' => $demo,
            'current_paid' => $current_paid,
            'requirement' => $correct_demo_fee,
            'remaining' => max(0, $remaining),
            'overpayment' => $remaining < 0 ? abs($remaining) : 0,
            'status' => $status
        ];
    }

    return $summary;
}

// Main processing starts here
try {
    $student_id = intval($_POST['student_id'] ?? 0);
    $program_id = intval($_POST['program_id'] ?? 0);
    $learning_mode = $_POST['learning_mode'] ?? '';
    $payment_type = $_POST['type_of_payment'] ?? '';
    $package_name = $_POST['package_id'] ?? 'Regular';
    $demo_type = $_POST['demo_type'] ?? null;
    $selected_schedules = $_POST['selected_schedules'] ?? null;
    $payment_amount = safe_float($_POST['total_payment'] ?? 0);
    $cash_given = safe_float($_POST['cash'] ?? 0);

    $has_excess_payment = isset($_POST['has_excess_payment']) && $_POST['has_excess_payment'] === 'true';
    $excess_choice = $_POST['excess_choice'] ?? null;
    $excess_amount = safe_float($_POST['excess_amount'] ?? 0);
    $excess_allocations = $_POST['excess_allocations'] ?? '[]';
    $original_payment_amount = safe_float($_POST['original_payment_amount'] ?? 0);
    $required_payment_amount = safe_float($_POST['required_payment_amount'] ?? 0);

    // Extract direct allocation flags
    $direct_allocation = isset($_POST['direct_allocation']) && $_POST['direct_allocation'] === 'true';
    $force_allocation = isset($_POST['force_allocation']) && $_POST['force_allocation'] === 'true';
    $allocation_amount = safe_float($_POST['allocation_amount'] ?? 0);
    $target_demo = $_POST['target_demo'] ?? null;

    $response = [
        'success' => false,
        'message' => '',
        'data' => []
    ];

    if ($student_id <= 0 || $program_id <= 0 || empty($payment_type)) {
        throw new Exception("Missing required parameters: student_id, program_id, or payment_type");
    }

    debug_log("Processing payment with overpayment correction handling", [
        'student_id' => $student_id,
        'program_id' => $program_id,
        'payment_type' => $payment_type,
        'payment_amount' => $payment_amount,
        'excess_choice' => $excess_choice,
        'excess_amount' => $excess_amount
    ]);

    if ($payment_type === 'demo_payment') {
        if (empty($demo_type) || $demo_type === null) {
            throw new Exception("Demo type is required for demo payments. Please select which demo you want to pay for.");
        }

        // FIXED: Validate demo_type is a valid demo
        $valid_demos = ['demo1', 'demo2', 'demo3', 'demo4'];
        if (!in_array($demo_type, $valid_demos)) {
            throw new Exception("Invalid demo type: {$demo_type}. Valid options are: " . implode(', ', $valid_demos));
        }

        $demo_requirement = calculateCorrectDemoFee($conn, $student_id, $program_id);
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo_type);
        $remaining_for_current_demo = max(0, $demo_requirement - $current_paid);
        $amount_for_current_demo = min($payment_amount, $remaining_for_current_demo);
        $excess_after_current = $payment_amount - $amount_for_current_demo;

        // FIXED: Get and preserve original package information for demo payments
        $package_name = $_POST['package_id'] ?? 'Regular';
        $package_details = null;
        $promo_discount_from_original = 0;

        // Get original package info from existing transactions to maintain consistency
        $original_package_sql = "SELECT package_name, promo_discount FROM pos_transactions 
                            WHERE student_id = ? AND program_id = ? 
                            AND (payment_type = 'initial_payment' OR payment_type = 'reservation')
                            AND package_name IS NOT NULL AND package_name != ''
                            ORDER BY transaction_date ASC LIMIT 1";
        $original_stmt = $conn->prepare($original_package_sql);
        $original_stmt->bind_param("ii", $student_id, $program_id);
        $original_stmt->execute();
        $original_result = $original_stmt->get_result();

        if ($original_row = $original_result->fetch_assoc()) {
            $package_name = $original_row['package_name'] ?? $package_name;
            $promo_discount_from_original = floatval($original_row['promo_discount'] ?? 0);
        }
        $original_stmt->close();

        // If still no package found, try to get from current form or use Regular
        if (!$package_name || $package_name === '') {
            $package_name = $_POST['package_id'] ?? 'Regular Package';
        }

        // Insert main demo payment record with preserved package info
        $payment_type_var = 'demo_payment';
        $status_var = 'Active';
        $enrollment_status_var = 'Enrolled';
        $description_var = sprintf(
            'Demo payment processed (package: %s, corrected fee: ₱%s)',
            $package_name,
            number_format($demo_requirement, 2)
        );
        $processed_by_var = 'Scraper001';

        // FIXED: Include package_name and preserve promo_discount in demo payment
        $sql_main = "INSERT INTO pos_transactions 
                 (student_id, program_id, learning_mode, payment_type, demo_type, package_name, selected_schedules,
                  total_amount, cash_received, balance, promo_discount, status, enrollment_status, description, processed_by, transaction_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql_main);
        $new_balance = $remaining_for_current_demo - $amount_for_current_demo;

        // FIXED: Include package_name and promo_discount in bind_param
        $stmt->bind_param(
            "iisssssddddssss",  // Added 's' for package_name and 'd' for promo_discount
            $student_id,         // i
            $program_id,         // i  
            $learning_mode,      // s
            $payment_type_var,   // s
            $demo_type,          // s
            $package_name,       // s - FIXED: Include package name
            $selected_schedules, // s
            $payment_amount,     // d
            $amount_for_current_demo, // d
            $new_balance,        // d
            $promo_discount_from_original, // d - FIXED: Include preserved promo discount
            $status_var,         // s
            $enrollment_status_var, // s
            $description_var,    // s
            $processed_by_var    // s
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert demo payment: " . $stmt->error);
        }

        error_log(sprintf(
            "[%s] Demo Payment with Package Preservation - User: %s
            Student ID: %d | Program ID: %d | Demo: %s | Package: %s
            Payment Amount: ₱%s | Applied to Demo: ₱%s | Excess: ₱%s
            Demo Requirement: ₱%s | New Balance: ₱%s | Promo Discount: ₱%s",
            date('Y-m-d H:i:s'),
            'Scraper001',
            $student_id,
            $program_id,
            $demo_type,
            $package_name,
            number_format($payment_amount, 2),
            number_format($amount_for_current_demo, 2),
            number_format($excess_after_current, 2),
            number_format($demo_requirement, 2),
            number_format($new_balance, 2),
            number_format($promo_discount_from_original, 2)
        ));

        $response['data'] = [
            'student_id' => $student_id,
            'program_id' => $program_id,
            'demo_type' => $demo_type,
            'package_preserved' => $package_name,
            'promo_discount_preserved' => $promo_discount_from_original,
            'payment_amount' => $payment_amount,
            'amount_for_current_demo' => $amount_for_current_demo,
            'excess_after_current' => $excess_after_current,
            'current_paid' => $current_paid,
            'demo_requirement' => $demo_requirement,
            'remaining_for_current_demo' => $remaining_for_current_demo,
            'new_balance' => $new_balance
        ];

        // CRITICAL FIX: Direct allocation for "allocate_to_next_demo" option
        if ($excess_after_current > 0 && $excess_choice === 'allocate_to_next_demo') {
            // Get the next demo in sequence
            $demo_sequence = ['demo1', 'demo2', 'demo3', 'demo4'];
            $current_index = array_search($demo_type, $demo_sequence);

            if ($current_index !== false && $current_index < 3) {
                $target_demo = $demo_sequence[$current_index + 1];

                // Calculate target demo requirement
                $target_demo_requirement = $demo_requirement; // Same as current demo
                $target_current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $target_demo);
                $target_remaining = max(0, $target_demo_requirement - $target_current_paid);

                // Determine allocation amount - don't allocate more than needed
                $allocation_amount = min($excess_after_current, $target_remaining);

                if ($allocation_amount > 0) {
                    // Create direct payment record for next demo
                    $new_balance = $target_remaining - $allocation_amount;

                    // Create variables for insertion
                    $payment_type_var = 'demo_payment';
                    $subtotal = $allocation_amount;
                    $total_amount = $allocation_amount;
                    $cash_received = $allocation_amount;
                    $change_amount = 0;
                    $status_var = 'Active';
                    $enrollment_status_var = 'Enrolled';
                    $description_var = sprintf(
                        'Excess allocation from %s (amount: ₱%s, timestamp: %s)',
                        $demo_type,
                        number_format($allocation_amount, 2),
                        date('Y-m-d H:i:s')
                    );
                    $processed_by_var = 'Scraper001';

                    // CRITICAL: Use the same schema as your main payment record
                    $allocation_sql = "INSERT INTO pos_transactions 
                                      (student_id, program_id, learning_mode, payment_type, demo_type, 
                                       package_name, subtotal, promo_discount, total_amount, cash_received,
                                       change_amount, balance, status, enrollment_status, description, 
                                       processed_by, transaction_date) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                    $allocation_stmt = $conn->prepare($allocation_sql);

                    if ($allocation_stmt) {
                        $allocation_stmt->bind_param(
                            "iissssddddddssss",
                            $student_id,                    // i
                            $program_id,                    // i
                            $learning_mode,                 // s
                            $payment_type_var,              // s
                            $target_demo,                   // s
                            $package_name,                  // s
                            $subtotal,                      // d
                            $promo_discount_from_original,  // d
                            $total_amount,                  // d
                            $cash_received,                 // d
                            $change_amount,                 // d
                            $new_balance,                   // d
                            $status_var,                    // s
                            $enrollment_status_var,         // s
                            $description_var,               // s
                            $processed_by_var               // s
                        );

                        // Execute the allocation insert
                        if ($allocation_stmt->execute()) {
                            // Add allocation details to response
                            $response['data']['direct_allocation'] = [
                                'source_demo' => $demo_type,
                                'target_demo' => $target_demo,
                                'amount' => $allocation_amount,
                                'target_requirement' => $target_demo_requirement,
                                'target_remaining_before' => $target_remaining,
                                'target_remaining_after' => $new_balance,
                                'timestamp' => date('Y-m-d H:i:s'),
                                'user' => 'Scraper001',
                                'success' => true
                            ];

                            error_log(sprintf(
                                "[%s] DIRECT ALLOCATION SUCCESS - User: %s
                                Source: %s, Target: %s, Amount: ₱%s
                                Target Requirement: ₱%s, Remaining Before: ₱%s, Remaining After: ₱%s",
                                date('Y-m-d H:i:s'),
                                'Scraper001',
                                $demo_type,
                                $target_demo,
                                number_format($allocation_amount, 2),
                                number_format($target_demo_requirement, 2),
                                number_format($target_remaining, 2),
                                number_format($new_balance, 2)
                            ));

                            // Update message to indicate successful allocation
                            $response['message'] = 'Demo payment processed successfully with direct allocation to next demo.';

                            // Subtract the allocated amount from the excess
                            $excess_after_current -= $allocation_amount;
                        } else {
                            error_log("ERROR in direct allocation: " . $allocation_stmt->error);
                        }

                        $allocation_stmt->close();
                    }
                }
            }
        }

        // If there's still excess after direct allocation and 'return_as_change' was chosen
        if ($excess_after_current > 0 && $excess_choice === 'return_as_change') {
            // Update the change_amount in the most recently inserted record
            $last_insert_id = $conn->insert_id; // Get the ID of the payment we just inserted

            // Update the change_amount field for this transaction
            $update_change_sql = "UPDATE pos_transactions 
                         SET change_amount = ?, 
                             change_given = ?,
                             change_verified = 1,
                             excess_amount = ?,
                             excess_option = 'return_as_change',
                             excess_description = ?
                         WHERE id = ?";

            $update_stmt = $conn->prepare($update_change_sql);
            $description = sprintf(
                'Change of ₱%s returned from excess payment for %s',
                number_format($excess_after_current, 2),
                $demo_type
            );

            if ($update_stmt) {
                $update_stmt->bind_param(
                    "dddsi",
                    $excess_after_current,   // change_amount
                    $excess_after_current,   // change_given
                    $excess_after_current,   // excess_amount
                    $description,            // excess_description
                    $last_insert_id          // id
                );

                if ($update_stmt->execute()) {
                    error_log(sprintf(
                        "[%s] CHANGE RECORDED SUCCESSFULLY - User: %s
                Transaction ID: %d, Change Amount: ₱%s",
                        date('Y-m-d H:i:s'),
                        'Scraper001',
                        $last_insert_id,
                        number_format($excess_after_current, 2)
                    ));

                    // Update response to reflect change recorded
                    $response['data']['change_returned'] = $excess_after_current;
                    $response['data']['change_recorded'] = true;
                    $response['data']['transaction_id'] = $last_insert_id;
                    $response['message'] = 'Demo payment processed successfully with excess returned as change.';
                } else {
                    error_log("ERROR recording change amount: " . $update_stmt->error);
                }

                $update_stmt->close();
            }
        }

        // FIXED: Process excess using the enhanced function that continues to ALL subsequent demos
        if ($excess_after_current > 0 && $excess_choice === 'allocate_to_demos' && empty($response['data']['direct_allocation'])) {
            $allocation_result = processExcessAllocationForDemo(
                $conn,
                $student_id,
                $program_id,
                $demo_type,
                $excess_after_current,
                $learning_mode,
                $package_name  // FIXED: Pass package name to allocation function
            );

            if ($allocation_result && !empty($allocation_result['allocations'])) {
                $response['data']['excess_allocations'] = $allocation_result['allocations'];
                $response['data']['remaining_excess'] = $allocation_result['remaining_excess'];

                // Count how many demos were fully paid
                $fully_paid_count = 0;
                foreach ($allocation_result['allocations'] as $alloc) {
                    if ($alloc['fully_paid']) {
                        $fully_paid_count++;
                    }
                }

                $response['data']['demos_fully_paid'] = $fully_paid_count;
                $response['message'] = sprintf(
                    'Demo payment processed with enhanced allocation to %d subsequent demos (%d fully paid). Remaining excess: ₱%s',
                    count($allocation_result['allocations']),
                    $fully_paid_count,
                    number_format($allocation_result['remaining_excess'], 2)
                );

                error_log(sprintf(
                    "[%s] FIXED: Enhanced allocation completed - %d demos allocated, %d fully paid, ₱%s remaining",
                    date('Y-m-d H:i:s'),
                    count($allocation_result['allocations']),
                    $fully_paid_count,
                    number_format($allocation_result['remaining_excess'], 2)
                ));
            } else {
                $response['message'] = 'Demo payment processed successfully with package preservation and corrected excess allocation.';
            }
        } else if (empty($response['message'])) {
            $response['message'] = 'Demo payment processed successfully with package preserved.';
        }

        $response['data']['payment_summary'] = getPaymentSummary($conn, $student_id, $program_id);
        $response['success'] = true;
    } else if ($payment_type === 'initial_payment') {
        $program_tuition_fee = getProgramTuitionFee($conn, $program_id);
        $initial_details_before = getInitialPaymentDetails($conn, $student_id, $program_id);
        $reservation_payment = getReservationPayment($conn, $student_id, $program_id);
        $promo_applied = $initial_details_before['promo_discount'];

        $program_details = getProgramDetails($conn, $program_id);
        $required_initial_amount = $program_details['initial_fee'] ?? 0;

        $applied_to_initial = min($payment_amount, $required_initial_amount);
        $excess_amount = $payment_amount - $applied_to_initial;

        // FIXED: Get and preserve original package information
        $package_name = $_POST['package_id'] ?? 'Regular';
        $package_details = null;
        $promo_discount_to_apply = 0;

        if ($package_name !== 'Regular' && $package_name !== 'Regular Package') {
            // Get original package details to preserve promo information
            $package_sql = "SELECT * FROM promo WHERE program_id = ? AND package_name = ?";
            $package_stmt = $conn->prepare($package_sql);
            $package_stmt->bind_param("is", $program_id, $package_name);
            $package_stmt->execute();
            $package_result = $package_stmt->get_result();

            if ($package_row = $package_result->fetch_assoc()) {
                $package_details = $package_row;

                // Calculate promo discount based on selection type
                $selection_type = intval($package_details['selection_type'] ?? 1);
                if ($selection_type <= 2) {
                    if ($package_details['promo_type'] === 'percentage') {
                        $promo_discount_to_apply = $program_tuition_fee * (floatval($package_details['percentage']) / 100);
                    } else {
                        $promo_discount_to_apply = floatval($package_details['enrollment_fee']);
                    }
                }
            }
            $package_stmt->close();
        }

        // Insert the main initial payment record with preserved package info
        $payment_type_var = 'initial_payment';
        $status_var = 'Active';
        $enrollment_status_var = 'Enrolled';
        $description_var = 'Initial payment processed with excess allocation - package preserved';
        $processed_by_var = 'Scraper001';

        // FIXED: Include package_name and promo_discount in the INSERT
        $sql = "INSERT INTO pos_transactions 
           (student_id, program_id, learning_mode, payment_type, package_name, selected_schedules,
            total_amount, cash_received, change_amount, credit_amount, balance, subtotal, promo_discount,
            status, enrollment_status, description, processed_by, transaction_date) 
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $change_amount = 0;
        $balance = 0;

        // FIXED: Include package_name in bind_param
        $stmt->bind_param(
            "iissssdddddddssss",  // Added 's' for package_name
            $student_id,
            $program_id,
            $learning_mode,
            $payment_type_var,
            $package_name,          // FIXED: Include package name
            $selected_schedules,
            $payment_amount,
            $applied_to_initial,
            $change_amount,
            $required_initial_amount,
            $balance,
            $program_tuition_fee,
            $promo_discount_to_apply,  // FIXED: Use calculated promo discount
            $status_var,
            $enrollment_status_var,
            $description_var,
            $processed_by_var
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert initial payment: " . $stmt->error);
        }

        error_log(sprintf(
            "[%s] Initial Payment with Package Preservation - User: %s
            Package: %s | Promo Discount: ₱%s | Excess: ₱%s",
            date('Y-m-d H:i:s'),
            'Scraper001',
            $package_name,
            number_format($promo_discount_to_apply, 2),
            number_format($excess_amount, 2)
        ));

        if ($excess_amount > 0) {
            $allocation_result = processExcessFromInitialPayment(
                $conn,
                $student_id,
                $program_id,
                $excess_amount,
                $learning_mode
            );

            $response['data']['allocation_result'] = $allocation_result;
            $response['data']['package_preserved'] = $package_name;
            $response['data']['promo_discount_applied'] = $promo_discount_to_apply;
            $response['data']['timestamp'] = date('Y-m-d H:i:s');
            $response['data']['user'] = 'Scraper001';
            $response['message'] = 'Initial payment processed with package preservation and excess allocation.';
        } else {
            $response['message'] = 'Initial payment processed successfully with package preserved.';
        }

        $response['success'] = true;

    } else {
        throw new Exception("Unsupported payment type: " . $payment_type);
    }

} catch (Exception $e) {
    error_log("Error in process_excess_demo_enrollment.php: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Error processing payment: ' . $e->getMessage();
    $response['timestamp'] = date('Y-m-d H:i:s');
    $response['processed_by'] = 'Scraper001';
}

echo json_encode($response);
$conn->close();
?>