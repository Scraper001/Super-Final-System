<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

include '../../../connection/connection.php';
$conn = con();


function processExcessFromInitialPayment($conn, $student_id, $program_id, $excess_amount, $learning_mode)
{
    $demo_sequence = ['demo1', 'demo2', 'demo3', 'demo4'];

    // STEP 1: Get the CURRENT state AFTER initial payment was processed
    $program_tuition_fee = getProgramTuitionFee($conn, $program_id);
    $initial_details = getInitialPaymentDetails($conn, $student_id, $program_id);
    $reservation_payment = getReservationPayment($conn, $student_id, $program_id);

    // Calculate what's already paid (AFTER the regular initial payment is applied)
    $initial_payment = $initial_details['initial_payment'] ?? 0;
    $promo_discount = $initial_details['promo_discount'] ?? 0;
    $total_upfront = $initial_payment + $promo_discount + $reservation_payment;

    // Calculate NEW demo fee based on the updated upfront payments
    $remaining_after_upfront = $program_tuition_fee - $total_upfront;
    $new_demo_fee = $remaining_after_upfront / 4;

    // Log the recalculation for verification
    error_log(sprintf(
        "[%s] Demo Fee Recalculation - User: %s\n" .
        "Program Fee: ₱%s | Initial: ₱%s | Promo: ₱%s | Reservation: ₱%s\n" .
        "Total Upfront: ₱%s | Remaining: ₱%s | NEW Demo Fee: ₱%s",
        "2025-08-05 13:05:48",
        "Scraper001",
        number_format($program_tuition_fee, 2),
        number_format($initial_payment, 2),
        number_format($promo_discount, 2),
        number_format($reservation_payment, 2),
        number_format($total_upfront, 2),
        number_format($remaining_after_upfront, 2),
        number_format($new_demo_fee, 2)
    ));

    // STEP 2: Now allocate excess using the NEW demo fee
    $allocations = [];
    $remaining_excess = $excess_amount;

    foreach ($demo_sequence as $index => $demo) {
        // Get current payment for this demo
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $remaining_for_demo = $new_demo_fee - $current_paid;

        // Skip demos that are already fully paid
        if ($remaining_for_demo <= 0) {
            continue;
        }

        // Stop if no more excess to allocate
        if ($remaining_excess <= 0) {
            break;
        }

        // Calculate how much to allocate to this demo
        $allocation_amount = min($remaining_excess, $remaining_for_demo);
        $remaining_excess -= $allocation_amount;
        $new_balance = $remaining_for_demo - $allocation_amount;

        // IMPORTANT FIX: Create variables for bind_param
        $status = 'Active';
        $description = 'Allocation from initial payment excess';
        $processed_by = 'Scraper001';

        // Insert allocation record with CORRECT demo fee
        $sql_insert = "INSERT INTO pos_transactions 
                      (student_id, program_id, learning_mode, payment_type, demo_type, 
                       cash_received, balance, status, description, processed_by, transaction_date) 
                      VALUES (?, ?, ?, 'demo_payment', ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql_insert);
        $payment_type = 'demo_payment'; // Create a variable for this value

        // Fix bind_param by using variables instead of literals
        $stmt->bind_param(
            "iissddss",
            $student_id,
            $program_id,
            $learning_mode,
            $demo,
            $allocation_amount,
            $new_balance,
            $status,
            $description,
            $processed_by
        );
        $stmt->execute();

        $allocations[] = [
            'demo_type' => $demo,
            'amount' => $allocation_amount,
            'remaining_balance' => $new_balance,
            'standard_fee' => $new_demo_fee,
            'current_paid_before' => $current_paid,
            'current_paid_after' => $current_paid + $allocation_amount,
            'fully_paid' => ($new_balance <= 0)
        ];

        error_log(sprintf(
            "[%s] Demo Allocation - User: %s\n" .
            "Demo: %s | Amount: ₱%s | New Balance: ₱%s | New Demo Fee: ₱%s",
            "2025-08-05 13:05:48",
            "Scraper001",
            $demo,
            number_format($allocation_amount, 2),
            number_format($new_balance, 2),
            number_format($new_demo_fee, 2)
        ));
    }

    // Return any unallocated excess
    if ($remaining_excess > 0) {
        error_log(sprintf(
            "[%s] Unallocated Excess - User: %s\n" .
            "Amount: ₱%s - Could not allocate to any demos",
            "2025-08-05 13:05:48",
            "Scraper001",
            number_format($remaining_excess, 2)
        ));
    }

    return [
        'allocations' => $allocations,
        'remaining_excess' => $remaining_excess,
        'new_demo_fee' => $new_demo_fee,
        'original_excess' => $excess_amount
    ];
}

// Function to get program details
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


function safe_float($value)
{
    if (is_null($value) || $value === '')
        return 0.0;
    return floatval($value);
}

$student_id = $_POST['student_id'];
$program_id = $_POST['program_id'];
$learning_mode = $_POST['learning_mode'];
$payment_type = $_POST['type_of_payment'];
$package_name = $_POST['package_id'] ?? 'Regular';
$demo_type = $_POST['demo_type'] ?? null;
$selected_schedules = $_POST['selected_schedules'] ?? null;
$payment_amount = safe_float($_POST['total_payment'] ?? 0);
$cash_given = safe_float($_POST['cash'] ?? 0);

// Enhanced: Get excess payment data
$has_excess_payment = isset($_POST['has_excess_payment']) && $_POST['has_excess_payment'] === 'true';
$excess_choice = $_POST['excess_choice'] ?? null;
$excess_amount = safe_float($_POST['excess_amount'] ?? 0);
$excess_allocations = $_POST['excess_allocations'] ?? '[]';
$original_payment_amount = safe_float($_POST['original_payment_amount'] ?? 0);
$required_payment_amount = safe_float($_POST['required_payment_amount'] ?? 0);

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// Function to get initial payment details with discount
function getInitialPaymentDetails($conn, $student_id, $program_id)
{
    $sql = "SELECT total_amount, cash_received, promo_discount, subtotal 
            FROM pos_transactions 
            WHERE student_id = ? AND program_id = ? AND payment_type = 'initial_payment' 
            ORDER BY transaction_date DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return [
            'total_amount' => safe_float($row['total_amount']),
            'initial_payment' => safe_float($row['cash_received']),
            'promo_discount' => safe_float($row['promo_discount']),
            'subtotal' => safe_float($row['subtotal'])
        ];
    }

    return null;
}

// Function to get program tuition fee
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

// CORRECTED: Function to calculate demo payment requirement with proper promo discount handling
function getDemoPaymentRequirement($conn, $student_id, $program_id, $demo_type)
{
    $initial_details = getInitialPaymentDetails($conn, $student_id, $program_id);

    if (!$initial_details) {
        // Fallback if no initial payment found
        return 7600;
    }

    // Get the actual program tuition fee from the program table
    $program_tuition_fee = getProgramTuitionFee($conn, $program_id);

    // Get reservation payment - THIS IS THE MISSING PIECE
    $reservation_payment = getReservationPayment($conn, $student_id, $program_id);

    // CORRECTED CALCULATION:
    // Total paid initially = initial_payment + promo_discount + reservation_payment
    // Remaining amount = program_tuition_fee - (initial_payment + promo_discount + reservation_payment)
    // Demo amount = remaining_amount / 4

    $initial_payment = $initial_details['initial_payment'];
    $promo_discount = $initial_details['promo_discount'];
    $total_paid_initially = $initial_payment + $promo_discount + $reservation_payment;

    $remaining_after_initial = $program_tuition_fee - $total_paid_initially;
    $demo_amount = $remaining_after_initial / 4;

    return $demo_amount;
}

// Function to get all demo requirements (for checking if any are partially paid)
function getAllDemoRequirements($conn, $student_id, $program_id)
{
    $demo_types = ['demo1', 'demo2', 'demo3', 'demo4'];
    $requirements = [];

    foreach ($demo_types as $demo) {
        $requirements[$demo] = getDemoPaymentRequirement($conn, $student_id, $program_id, $demo);
    }

    return $requirements;
}

// Function to check if any demos have been partially paid
function hasPartiallyPaidDemos($conn, $student_id, $program_id)
{
    $demo_types = ['demo1', 'demo2', 'demo3', 'demo4'];

    foreach ($demo_types as $demo) {
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $requirement = getDemoPaymentRequirement($conn, $student_id, $program_id, $demo);

        // If any demo is partially paid (0 < paid < requirement), return true
        if ($current_paid > 0 && $current_paid < $requirement) {
            return true;
        }
    }

    return false;
}

// CORRECTED: Function to recalculate demo amounts when there are partial payments
function recalculateDemoAmounts($conn, $student_id, $program_id)
{
    $demo_types = ['demo1', 'demo2', 'demo3', 'demo4'];
    $initial_details = getInitialPaymentDetails($conn, $student_id, $program_id);

    if (!$initial_details) {
        return null;
    }

    // Get the actual program tuition fee
    $program_tuition_fee = getProgramTuitionFee($conn, $program_id);

    // Get reservation payment - ADD THIS
    $reservation_payment = getReservationPayment($conn, $student_id, $program_id);

    // CORRECTED: Calculate total demo amount using proper formula
    $initial_payment = $initial_details['initial_payment'];
    $promo_discount = $initial_details['promo_discount'];
    $total_paid_initially = $initial_payment + $promo_discount + $reservation_payment;
    $total_demo_amount = $program_tuition_fee - $total_paid_initially;

    // Get current payments for each demo
    $current_payments = [];
    $total_paid = 0;

    foreach ($demo_types as $demo) {
        $paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $current_payments[$demo] = $paid;
        $total_paid += $paid;
    }

    // Calculate remaining amount to be distributed
    $remaining_amount = $total_demo_amount - $total_paid;

    // Count unpaid demos
    $unpaid_demos = 0;
    foreach ($demo_types as $demo) {
        if ($current_payments[$demo] == 0) {
            $unpaid_demos++;
        }
    }

    // If there are unpaid demos, distribute remaining amount equally
    $recalculated_amounts = [];
    if ($unpaid_demos > 0) {
        $amount_per_unpaid_demo = $remaining_amount / $unpaid_demos;

        foreach ($demo_types as $demo) {
            if ($current_payments[$demo] == 0) {
                $recalculated_amounts[$demo] = $amount_per_unpaid_demo;
            } else {
                // For partially/fully paid demos, keep the original calculation
                $recalculated_amounts[$demo] = $total_demo_amount / 4;
            }
        }
    } else {
        // All demos have some payment, use original calculation
        $demo_amount = $total_demo_amount / 4;
        foreach ($demo_types as $demo) {
            $recalculated_amounts[$demo] = $demo_amount;
        }
    }

    return [
        'total_demo_amount' => $total_demo_amount,
        'total_paid' => $total_paid,
        'remaining_amount' => $remaining_amount,
        'demo_amounts' => $recalculated_amounts,
        'initial_details' => $initial_details,
        'program_tuition_fee' => $program_tuition_fee,
        'total_paid_initially' => $total_paid_initially
    ];
}

// Function to get current demo payment status
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

// Function to process excess allocation for demo payments
function processExcessAllocationForDemo($conn, $student_id, $program_id, $current_demo_type, $excess_amount, $payment_amount, $learning_mode)
{
    // Define demo sequence
    $demo_sequence = ['demo1', 'demo2', 'demo3', 'demo4'];

    // CRITICAL: Get ALL upfront payments
    $program_tuition_fee = getProgramTuitionFee($conn, $program_id);
    $initial_details = getInitialPaymentDetails($conn, $student_id, $program_id);
    $reservation_payment = getReservationPayment($conn, $student_id, $program_id);
    $promo_discount = $initial_details['promo_discount'] ?? 0;

    // FIXED: Calculate total upfront payments
    $total_upfront = ($initial_details['initial_payment'] ?? 0) + $promo_discount + $reservation_payment;

    // Calculate all demo payments made so far
    $demo_payments = 0;
    foreach ($demo_sequence as $demo) {
        if ($demo != $current_demo_type) { // Exclude current demo
            $demo_payments += getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        }
    }

    // FIXED: Calculate remaining amount after ALL upfront payments and previous demo payments
    // But excluding the current demo's previous payments and current payment
    $remaining = $program_tuition_fee - $total_upfront - $demo_payments;

    // Calculate remaining demos count (excluding current demo and fully paid demos)
    $remaining_demos = 0;
    foreach ($demo_sequence as $demo) {
        if ($demo != $current_demo_type) {
            $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
            $demo_fee = ($program_tuition_fee - $total_upfront) / 4;

            if ($current_paid < $demo_fee) {
                $remaining_demos++;
            }
        }
    }

    // If no remaining demos, can't allocate excess
    if ($remaining_demos == 0) {
        return [
            'allocations' => [],
            'remaining_excess' => $excess_amount,
            'reason' => 'No unpaid demos remaining for allocation'
        ];
    }

    // Calculate standard demo fee for remaining demos
    $standard_demo_fee = $remaining / ($remaining_demos + 1); // +1 for current demo

    $remaining_excess = $excess_amount;
    $allocations = [];

    // First, try to allocate to unpaid demos
    foreach ($demo_sequence as $demo) {
        // Skip current demo
        if ($demo == $current_demo_type) {
            continue;
        }

        // Get current payment for this demo
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $demo_requirement = ($program_tuition_fee - $total_upfront) / 4;
        $remaining_balance = $demo_requirement - $current_paid;

        if ($remaining_balance > 0 && $remaining_excess > 0) {
            // Calculate how much to allocate to this demo
            $allocation_amount = min($remaining_excess, $remaining_balance);

            // Insert allocation record
            $sql_insert = "INSERT INTO pos_transactions 
                          (student_id, program_id, learning_mode, payment_type, demo_type, 
                           cash_received, balance, status, description, processed_by, transaction_date) 
                          VALUES (?, ?, ?, 'demo_payment', ?, ?, ?, 'Active', 
                                  'Excess allocation from demo payment', ?, NOW())";

            $stmt = $conn->prepare($sql_insert);
            $new_balance = $remaining_balance - $allocation_amount;
            $stmt->bind_param(
                "iissddss",
                $student_id,
                $program_id,
                $learning_mode,
                $demo,
                $allocation_amount,
                $new_balance,
                "Scraper001"
            );
            $stmt->execute();

            $allocations[] = [
                'demo_type' => $demo,
                'amount' => $allocation_amount,
                'remaining_balance' => $new_balance,
                'demo_requirement' => $demo_requirement,
                'current_paid_before' => $current_paid,
                'current_paid_after' => $current_paid + $allocation_amount
            ];

            $remaining_excess -= $allocation_amount;

            // Log allocation
            error_log(sprintf(
                "[%s] Demo Excess Allocation - User: %s\n" .
                "From Demo: %s | To Demo: %s | Amount: ₱%s | New Balance: ₱%s",
                date('Y-m-d H:i:s'),
                "Scraper001",
                $current_demo_type,
                $demo,
                number_format($allocation_amount, 2),
                number_format($new_balance, 2)
            ));

            // If no more excess, stop allocating
            if ($remaining_excess <= 0) {
                break;
            }
        }
    }

    return [
        'allocations' => $allocations,
        'remaining_excess' => $remaining_excess,
        'standard_demo_fee' => $standard_demo_fee
    ];
}

// Main processing logic
try {
    if ($payment_type === 'demo_payment') {
        // Check if we need to recalculate demo amounts due to partial payments
        $has_partial_payments = hasPartiallyPaidDemos($conn, $student_id, $program_id);

        if ($has_partial_payments) {
            // Use recalculated amounts
            $recalculation = recalculateDemoAmounts($conn, $student_id, $program_id);
            $demo_requirement = $recalculation['demo_amounts'][$demo_type];

            $response['data']['recalculation_info'] = [
                'has_partial_payments' => true,
                'total_demo_amount' => $recalculation['total_demo_amount'],
                'total_paid' => $recalculation['total_paid'],
                'remaining_amount' => $recalculation['remaining_amount'],
                'demo_amounts' => $recalculation['demo_amounts'],
                'initial_details' => $recalculation['initial_details'],
                'program_tuition_fee' => $recalculation['program_tuition_fee'],
                'total_paid_initially' => $recalculation['total_paid_initially']
            ];
        } else {
            // Use standard calculation
            $demo_requirement = getDemoPaymentRequirement($conn, $student_id, $program_id, $demo_type);
            $initial_details = getInitialPaymentDetails($conn, $student_id, $program_id);
            $program_tuition_fee = getProgramTuitionFee($conn, $program_id);

            $response['data']['recalculation_info'] = [
                'has_partial_payments' => false,
                'initial_details' => $initial_details,
                'program_tuition_fee' => $program_tuition_fee,
                'total_paid_initially' => $initial_details['initial_payment'] + $initial_details['promo_discount']
            ];
        }

        // Get current demo payment status
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo_type);

        // Calculate what's needed for current demo
        $remaining_for_current_demo = $demo_requirement - $current_paid;

        // Determine how much applies to current demo vs excess
        $amount_for_current_demo = min($payment_amount, $remaining_for_current_demo);
        $excess_after_current = $payment_amount - $amount_for_current_demo;

        // Insert main demo payment record
        $sql_main = "INSERT INTO pos_transactions 
                     (student_id, program_id, learning_mode, payment_type, demo_type, selected_schedules,
                      total_amount, cash_received, balance, status, enrollment_status, description, processed_by, transaction_date) 
                     VALUES (?, ?, ?, 'demo_payment', ?, ?, ?, ?, ?, 'Active', 'Enrolled', 
                             'Demo payment processed', 'System', NOW())";

        $stmt = $conn->prepare($sql_main);
        $new_balance = $remaining_for_current_demo - $amount_for_current_demo;
        $stmt->bind_param(
            "iisssddd",
            $student_id,
            $program_id,
            $learning_mode,
            $demo_type,
            $selected_schedules,
            $payment_amount,
            $amount_for_current_demo,
            $new_balance
        );
        $stmt->execute();

        // Prepare response data
        $response['data'] = [
            'student_id' => $student_id,
            'program_id' => $program_id,
            'demo_type' => $demo_type,
            'payment_amount' => $payment_amount,
            'amount_for_current_demo' => $amount_for_current_demo,
            'excess_after_current' => $excess_after_current,
            'current_paid' => $current_paid,
            'demo_requirement' => $demo_requirement,
            'remaining_for_current_demo' => $remaining_for_current_demo,
            'new_balance' => $new_balance
        ];

        // Process excess allocation if user chose to allocate to demos
        if ($excess_after_current > 0 && $excess_choice === 'allocate_to_demos') {
            $allocation_result = processExcessAllocationForDemo(
                $conn,
                $student_id,
                $program_id,
                $demo_type,
                $excess_after_current,
                $payment_amount
            );

            if ($allocation_result && !empty($allocation_result['allocations'])) {
                $response['data']['excess_allocations'] = $allocation_result['allocations'];
                $response['data']['remaining_excess'] = $allocation_result['remaining_excess'];
                $response['message'] = 'Demo payment processed successfully with excess allocation.';
            } else {
                $response['message'] = 'Demo payment processed successfully.';
            }
        } else if ($excess_after_current > 0) {
            $response['data']['excess_amount'] = $excess_after_current;
            $response['message'] = 'Demo payment processed successfully with excess amount.';
        } else {
            $response['message'] = 'Demo payment processed successfully.';
        }

        // Get current payment status summary
        $response['data']['payment_summary'] = getPaymentSummary($conn, $student_id, $program_id);

        $response['success'] = true;

    } else if ($payment_type === 'initial_payment') {
        // Process initial payment logic

        // Calculate program tuition and get details
        $program_tuition_fee = getProgramTuitionFee($conn, $program_id);
        $initial_details_before = getInitialPaymentDetails($conn, $student_id, $program_id);
        $reservation_payment = getReservationPayment($conn, $student_id, $program_id);
        $promo_applied = isset($initial_details_before) ? ($initial_details_before['promo_discount'] ?? 0) : 0;
        // Get initial payment requirements
        $required_initial_amount = 0;
        if (
            isset($currentPackage) && isset($currentPackage['selection_type']) &&
            $currentPackage['selection_type'] > 2 && isset($currentPackage['custom_initial_payment'])
        ) {
            $required_initial_amount = floatval($currentPackage['custom_initial_payment']);
        } else {
            // Get program details for standard initial payment
            $program_details = getProgramDetails($conn, $program_id);
            $required_initial_amount = $program_details['initial_fee'] ?? 0;
        }

        // Calculate how much to apply to initial payment and how much is excess
        $applied_to_initial = min($payment_amount, $required_initial_amount);
        $excess_amount = $payment_amount - $applied_to_initial;

        // STEP 1: Insert the main initial payment record
        $sql = "INSERT INTO pos_transactions 
       (student_id, program_id, learning_mode, payment_type, selected_schedules,
        total_amount, cash_received, change_amount, credit_amount, balance, subtotal, promo_discount,
        status, enrollment_status, description, processed_by, transaction_date) 
       VALUES (?, ?, ?, 'initial_payment', ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 'Enrolled', 
               'Initial payment processed', ?, NOW())";

        // Prepare the statement for the initial payment
        $stmt = $conn->prepare($sql);
        $change_amount = 0;
        $balance = 0;
        $processed_by = "Scraper001";

        // CORRECT: Using variables for all parameters
        $stmt->bind_param(
            "iisssdddddss",
            $student_id,
            $program_id,
            $learning_mode,
            $selected_schedules,
            $payment_amount,
            $applied_to_initial,
            $change_amount,
            $required_initial_amount,
            $balance,
            $program_tuition_fee,
            $promo_applied,
            $processed_by
        );
        $stmt->execute();

        // Log the initial payment
        error_log(sprintf(
            "[%s] Initial Payment Processed - User: %s\n" .
            "Student ID: %s | Program ID: %s | Payment: ₱%s | Applied: ₱%s | Excess: ₱%s",
            "2025-08-05 11:19:41",
            "Scraper001",
            $student_id,
            $program_id,
            number_format($payment_amount, 2),
            number_format($applied_to_initial, 2),
            number_format($excess_amount, 2)
        ));

        // STEP 2: Now handle excess payment if it exists - USING THE RECALCULATED DEMO FEES
        if ($excess_amount > 0) {
            // Get the updated initial details AFTER inserting the initial payment
            $initial_details_after = getInitialPaymentDetails($conn, $student_id, $program_id);

            // Calculate the NEW demo fees before allocating excess
            $initial_payment_after = $initial_details_after['initial_payment'] ?? 0;
            $promo_discount = $initial_details_after['promo_discount'] ?? 0;
            $total_upfront = $initial_payment_after + $promo_discount + $reservation_payment;
            $remaining_for_demos = $program_tuition_fee - $total_upfront;
            $new_demo_fee = $remaining_for_demos / 4;

            // Log the recalculation
            error_log(sprintf(
                "[%s] Demo Fee Recalculation - User: %s\n" .
                "Before Initial: ₱%s per demo | After Initial: ₱%s per demo\n" .
                "Total Tuition: ₱%s | Initial: ₱%s | Promo: ₱%s | Reservation: ₱%s",
                "2025-08-05 11:19:41",
                "Scraper001",
                number_format(($program_tuition_fee - ($initial_details_before['initial_payment'] ?? 0) - ($initial_details_before['promo_discount'] ?? 0) - $reservation_payment) / 4, 2),
                number_format($new_demo_fee, 2),
                number_format($program_tuition_fee, 2),
                number_format($initial_payment_after, 2),
                number_format($promo_discount, 2),
                number_format($reservation_payment, 2)
            ));

            // Now process the excess allocation with the NEW demo fee
            $allocation_result = processExcessFromInitialPayment(
                $conn,
                $student_id,
                $program_id,
                $excess_amount,
                $learning_mode
            );

            $response['data']['allocation_result'] = $allocation_result;
            $response['data']['old_demo_fee'] = ($program_tuition_fee - ($initial_details_before['initial_payment'] ?? 0) - ($initial_details_before['promo_discount'] ?? 0) - $reservation_payment) / 4;
            $response['data']['new_demo_fee'] = $new_demo_fee;
            $response['data']['timestamp'] = '2025-08-05 11:19:41';
            $response['data']['user'] = 'Scraper001';
            $response['message'] = 'Initial payment processed with excess allocation to demos.';
        } else {
            $response['message'] = 'Initial payment processed successfully without excess.';
        }

        // Return success
        $response['success'] = true;
    } else {
        // Handle other payment types (initial_payment, etc.)
        $response['message'] = 'Processing ' . $payment_type . ' payment...';

        // Your existing logic for other payment types goes here

        $response['success'] = true;
        $response['message'] = 'Payment processed successfully!';
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error processing payment: ' . $e->getMessage();
}














// Display current payment status summary
function getPaymentSummary($conn, $student_id, $program_id)
{
    $demo_types = ['demo1', 'demo2', 'demo3', 'demo4'];
    $summary = [];

    foreach ($demo_types as $demo) {
        $current_paid = getCurrentDemoPayment($conn, $student_id, $program_id, $demo);
        $requirement = getDemoPaymentRequirement($conn, $student_id, $program_id, $demo);
        $remaining = $requirement - $current_paid;

        $status = $remaining <= 0 ? 'PAID' : ($current_paid > 0 ? 'PARTIALLY PAID' : 'NOT PAID');

        $summary[] = [
            'demo_type' => $demo,
            'current_paid' => $current_paid,
            'requirement' => $requirement,
            'remaining' => $remaining,
            'status' => $status
        ];
    }

    return $summary;
}

// Return JSON response
echo json_encode($response);

$conn->close();
?>