

<?php
include "../connection/connection.php";
$conn = con();
include "includes/header.php";

// Initialize variables
$row = null;
$programs = [];
$tuition_packages = [];
$selected_program = '';
$package_details = null;
$payment_processed = false;
$receipt_data = null;
$schedules = [];

// Debug: Check if POST is working
if ($_POST) {
    error_log("POST data received: " . print_r($_POST, true));
}

// Get student info if ID is provided
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $sql = "SELECT * FROM student_info_tbl WHERE student_number = '$id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
    }
}

// Get all programs for dropdown
$sql_programs = "SELECT * FROM programs ORDER BY program_name";
$result_programs = $conn->query($sql_programs);
if ($result_programs && $result_programs->num_rows > 0) {
    while ($program_row = $result_programs->fetch_assoc()) {
        $programs[] = $program_row;
    }
}

// Handle search_program form submission
if (isset($_POST['search_program']) && isset($_POST['program_name']) && !empty($_POST['program_name'])) {
    $program_name = mysqli_real_escape_string($conn, $_POST['program_name']);
    $id = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : '';

    // Get tuition packages for selected program
    $sql_tuition = "SELECT * FROM tuitionfee WHERE program_name = '$program_name' ORDER BY program_name";
    $result_tuition = $conn->query($sql_tuition);

    if ($result_tuition && $result_tuition->num_rows > 0) {
        while ($tuition_row = $result_tuition->fetch_assoc()) {
            $tuition_packages[] = $tuition_row;
        }
        $selected_program = $program_name;
    }
}

$program_id = 0;
// Handle finalize_search form submission
if (isset($_POST['finalize_search'])) {
    $package_name = isset($_POST['package_name']) ? mysqli_real_escape_string($conn, $_POST['package_name']) : '';
    $id = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : '';
    $program_name = isset($_POST['program_name']) ? mysqli_real_escape_string($conn, $_POST['program_name']) : '';

    // Get package details from database
    if (!empty($package_name)) {
        $sql_package = "SELECT * FROM tuitionfee WHERE program_name = '$package_name'";
        $result_package = $conn->query($sql_package);
        if ($result_package && $result_package->num_rows > 0) {
            $package_details = $result_package->fetch_assoc();
        }
    }

    $sql_select_program = "SELECT * FROM programs WHERE program_name = '$program_name'";
    $result_program = $conn->query($sql_select_program);
    $row_program = $result_program->fetch_assoc();

    $program_id = $row_program['id'];

    // Get schedules for the selected program
    $sql_schedule = "SELECT * FROM program_schedules WHERE program_id = '$program_id' ORDER BY week";
    $result_schedule = $conn->query($sql_schedule);
    if ($result_schedule && $result_schedule->num_rows > 0) {
        while ($row_sched = $result_schedule->fetch_assoc()) {
            $schedules[] = $row_sched;
        }
    }
}

// Handle payment processing
// Handle payment processing
if (isset($_POST['process_payment'])) {
    // Debug: Log all POST data
    error_log("Payment POST data: " . print_r($_POST, true));

    $student_number = mysqli_real_escape_string($conn, $_POST['student_number']);
    $program_name = mysqli_real_escape_string($conn, $_POST['program_name']);
    $package_id = mysqli_real_escape_string($conn, $_POST['package_id']);
    $program_id = mysqli_real_escape_string($conn, $_POST['program_id']);
    $payment_type = mysqli_real_escape_string($conn, $_POST['payment_type']);
    $class_type = mysqli_real_escape_string($conn, $_POST['class_type']);
    $cash_payment = floatval($_POST['cash_payment']);
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
    $selected_weeks = isset($_POST['week']) ? $_POST['week'] : [];

    // Debug: Check what we received for selected weeks
    error_log("Selected weeks received: " . print_r($selected_weeks, true));
    error_log("Is selected_weeks empty? " . (empty($selected_weeks) ? 'YES' : 'NO'));
    error_log("Count of selected weeks: " . count($selected_weeks));

    // Validate that at least one schedule is selected
    if (empty($selected_weeks)) {
        echo "<script>alert('Please select at least one training schedule.');</script>";
        error_log("ERROR: No schedules selected - showing alert");
    } else {
        error_log("SUCCESS: Schedules selected, proceeding with payment");

        // Get package details
        $sql_package = "SELECT * FROM tuitionfee WHERE id = '$package_id'";
        $result_package = $conn->query($sql_package);
        $package_data = $result_package->fetch_assoc();

        // Get student details
        $sql_student = "SELECT CONCAT(first_name, ' ', last_name, ' ', middle_name) as full_name FROM student_info_tbl WHERE student_number = '$student_number'";
        $result_student = $conn->query($sql_student);
        $student_data = $result_student->fetch_assoc();

        // ... rest of your payment processing code remains the same

        // Calculate amounts
        $demo1 = $package_data['demo1'];
        $demo2 = $package_data['demo2'];
        $demo3 = $package_data['demo3'];
        $demo4 = $package_data['demo4'];
        $total_amount = $package_data['totalbasedtuition'];
        $system_fee = $package_data['system Fee'];

        // Adjust total based on class type (deduct system fee for Face-to-Face)
        $adjusted_total = $total_amount;
        if ($class_type == 'face_to_face') {
            $adjusted_total = $total_amount - $system_fee;
        }

        // Apply discount
        $discount_amount = $adjusted_total * ($discount / 100);
        $discounted_amount = $adjusted_total - $discount_amount;
        $remaining_payment = $cash_payment;
        $balance = 0;
        $change = 0;

        // Initialize demo balances
        $demo1_balance = $demo1;
        $demo2_balance = $demo2;
        $demo3_balance = $demo3;
        $demo4_balance = $demo4;

        if ($payment_type == 'reserve') {
            // Reserve payment - deduct from first demo only
            $enrollment_status = 'Reserved';
            if ($cash_payment >= $demo1) {
                $demo1_balance = 0;
                $remaining_payment -= $demo1;
                $change = $remaining_payment;
            } else {
                $demo1_balance = $demo1 - $cash_payment;
            }
            $balance = $discounted_amount - $cash_payment;

        } elseif ($payment_type == 'full') {
            // Full payment
            $enrollment_status = 'Enrolled';
            if ($cash_payment >= $discounted_amount) {
                $demo1_balance = 0;
                $demo2_balance = 0;
                $demo3_balance = 0;
                $demo4_balance = 0;
                $change = $cash_payment - $discounted_amount;
                $balance = 0;
            } else {
                $balance = $discounted_amount - $cash_payment;
            }

        } else { // partial payment (enroll)
            $enrollment_status = 'Enrolled';

            // Distribute payment across demos
            if ($remaining_payment >= $demo1) {
                $demo1_balance = 0;
                $remaining_payment -= $demo1;

                if ($remaining_payment >= $demo2) {
                    $demo2_balance = 0;
                    $remaining_payment -= $demo2;

                    if ($remaining_payment >= $demo3) {
                        $demo3_balance = 0;
                        $remaining_payment -= $demo3;

                        if ($remaining_payment >= $demo4) {
                            $demo4_balance = 0;
                            $remaining_payment -= $demo4;
                            $change = $remaining_payment;
                        } else {
                            $demo4_balance = $demo4 - $remaining_payment;
                        }
                    } else {
                        $demo3_balance = $demo3 - $remaining_payment;
                    }
                } else {
                    $demo2_balance = $demo2 - $remaining_payment;
                }
            } else {
                $demo1_balance = $demo1 - $remaining_payment;
            }

            $balance = $discounted_amount - $cash_payment + $change;
        }

        // Generate unique transaction ID
        $transaction_id = 'POS' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Prepare selected schedules data
        $schedule_ids = [];
        foreach ($selected_weeks as $week_id) {
            $schedule_ids[] = intval($week_id);
        }
        $selected_schedules_json = json_encode($schedule_ids);

        error_log("About to insert transaction with schedules: " . $selected_schedules_json);

        // Insert into new POS transactions table
        $sql_insert = "INSERT INTO pos_transactions (
                transaction_id, student_number, student_name, program_name, program_id, package_id,
                payment_type, class_type, total_tuition, system_fee, adjusted_total,
                cash_payment, discount_percentage, discount_amount, change_amount, balance,
                demo1_balance, demo2_balance, demo3_balance, demo4_balance,
                enrollment_status, selected_schedules, transaction_date
            ) VALUES (
                '$transaction_id', '$student_number', '{$student_data['full_name']}', '$program_name', '$program_id', '$package_id',
                '$payment_type', '$class_type', '$total_amount', '$system_fee', '$adjusted_total',
                '$cash_payment', '$discount', '$discount_amount', '$change', '$balance',
                '$demo1_balance', '$demo2_balance', '$demo3_balance', '$demo4_balance',
                '$enrollment_status', '$selected_schedules_json', NOW()
            )";

        if ($conn->query($sql_insert)) {
            $pos_transaction_id = $conn->insert_id;
            error_log("Transaction inserted successfully with ID: " . $pos_transaction_id);

            // Insert selected schedules into detail table
            foreach ($selected_weeks as $schedule_id) {
                $sql_schedule_detail = "SELECT * FROM program_schedules WHERE id = '$schedule_id'";
                $result_schedule_detail = $conn->query($sql_schedule_detail);
                if ($result_schedule_detail && $result_schedule_detail->num_rows > 0) {
                    $schedule_detail = $result_schedule_detail->fetch_assoc();

                    $sql_insert_schedule = "INSERT INTO pos_schedule_selections (
                            pos_transaction_id, schedule_id, week_number, description, 
                            training_date, start_time, end_time, day_of_week
                        ) VALUES (
                            '$pos_transaction_id', '$schedule_id', '{$schedule_detail['week']}', 
                            '{$schedule_detail['description']}', '{$schedule_detail['training_date']}', 
                            '{$schedule_detail['start_time']}', '{$schedule_detail['end_time']}', 
                            '{$schedule_detail['day_of_week']}'
                        )";

                    if ($conn->query($sql_insert_schedule)) {
                        error_log("Schedule detail inserted for schedule ID: " . $schedule_id);
                    } else {
                        error_log("Error inserting schedule detail: " . $conn->error);
                    }
                }
            }

            // Update student enrollment status
            $sql_update_student = "UPDATE student_info_tbl SET enrollment_status = '$enrollment_status' WHERE student_number = '$student_number'";
            $conn->query($sql_update_student);

            // Prepare receipt data
            $receipt_data = [
                'transaction_id' => $transaction_id,
                'student_number' => $student_number,
                'student_name' => $student_data['full_name'],
                'program_name' => $program_name,
                'payment_type' => $payment_type,
                'class_type' => $class_type,
                'cash_payment' => $cash_payment,
                'change' => $change,
                'balance' => $balance,
                'discount' => $discount,
                'discount_amount' => $discount_amount,
                'enrollment_status' => $enrollment_status,
                'demo1_balance' => $demo1_balance,
                'demo2_balance' => $demo2_balance,
                'demo3_balance' => $demo3_balance,
                'demo4_balance' => $demo4_balance,
                'total_amount' => $total_amount,
                'system_fee' => $system_fee,
                'adjusted_total' => $adjusted_total,
                'discounted_amount' => $discounted_amount,
                'selected_weeks' => count($selected_weeks),
                'date' => date('Y-m-d H:i:s'),
            ];

            $payment_processed = true;
            $_SESSION['payment_success'] = true;
            error_log("Payment processed successfully!");
        } else {
            error_log("Error inserting transaction: " . $conn->error);
        }
    }
}

// Get all students for the table
$sql_students = "SELECT * FROM student_info_tbl ORDER BY student_number";
$result_students = $conn->query($sql_students);
?>

<div class="flex h-screen overflow-hidden flex-row">
    <!-- Sidebar -->
    <div id="sidebar"
        class="bg-white shadow-lg transition-all duration-300 w-[20%] flex flex-col z-10 md:flex hidden fixed min-h-screen">
        <div class="px-6 py-5 flex items-center border-b border-gray-100">
            <h1 class="text-xl font-bold text-primary flex items-center">
                <i class='bx bx-plus-medical text-2xl mr-2'></i>
                <span class="text"> CarePro</span>
            </h1>
        </div>
        <?php include "includes/navbar.php" ?>
    </div>

    <!-- Main Content -->
    <div id="content" class="flex-1 flex flex-col w-[80%] md:ml-[20%] ml-0 transition-all duration-300">
        <header class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="toggleSidebar"
                        class="md:hidden flex text-gray-500 hover:text-primary focus:outline-none mr-4">
                        <i class='bx bx-menu text-2xl'></i>
                    </button>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#announcementModal">
                        <i class="fa-solid fa-bullhorn mr-1"></i>
                        New Announcement
                    </button>
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#viewAnnouncementsModal">
                        <i class="fa-solid fa-envelope mr-1"></i>
                        View Announcements
                    </button>
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer" id="logout"
                        data-swal-template="#my-template">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto no-scrollbar bg-gray-50 p-6">
            <h2 class="font-semibold my-2 text-sm"><a href="index.php" class="text-gray-400">Dashboard / </a>POS</h2>

            <div class="container mx-auto px-4 py-8 max-w-6xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-indigo-700">Student POS System</h1>
                        <p class="text-gray-600">Manage student enrollment and payment</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-500"><?php echo date('F d, Y'); ?></span>
                    </div>
                </div>

                <?php if ($payment_processed && $receipt_data): ?>
                    <!-- Receipt Modal -->
                    <div id="receiptModal"
                        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                            <div id="receiptContent" class="text-center">
                                <h2 class="text-2xl font-bold mb-4">CAREPRO RECEIPT</h2>
                                <div class="border-b pb-2 mb-4">
                                    <p><strong>Transaction ID:</strong> <?php echo $receipt_data['transaction_id']; ?></p>
                                    <p><strong>Date:</strong> <?php echo $receipt_data['date']; ?></p>
                                    <p><strong>Student ID:</strong> <?php echo $receipt_data['student_number']; ?></p>
                                    <p><strong>Student Name:</strong> <?php echo $receipt_data['student_name']; ?></p>
                                    <p><strong>Program:</strong> <?php echo $receipt_data['program_name']; ?></p>
                                </div>

                                <div class="text-left mb-4">
                                    <p><strong>Payment Type:</strong> <?php echo ucfirst($receipt_data['payment_type']); ?>
                                    </p>
                                    <p><strong>Class Type:</strong>
                                        <?php echo ucfirst(str_replace('_', ' ', $receipt_data['class_type'])); ?></p>
                                    <p><strong>Status:</strong> <?php echo $receipt_data['enrollment_status']; ?></p>
                                    <p><strong>Total Amount:</strong>
                                        ₱<?php echo number_format($receipt_data['total_amount'], 2); ?></p>
                                    <?php if ($receipt_data['class_type'] == 'face_to_face'): ?>
                                        <p><strong>System Fee (Deducted):</strong>
                                            ₱<?php echo number_format($receipt_data['system_fee'], 2); ?></p>
                                        <p><strong>Adjusted Total:</strong>
                                            ₱<?php echo number_format($receipt_data['adjusted_total'], 2); ?></p>
                                    <?php endif; ?>
                                    <?php if ($receipt_data['discount'] > 0): ?>
                                        <p><strong>Discount:</strong> <?php echo $receipt_data['discount']; ?>%</p>
                                        <p><strong>Discount Amount:</strong>
                                            ₱<?php echo number_format($receipt_data['discount_amount'], 2); ?></p>
                                        <p><strong>Final Amount:</strong>
                                            ₱<?php echo number_format($receipt_data['discounted_amount'], 2); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Cash Received:</strong>
                                        ₱<?php echo number_format($receipt_data['cash_payment'], 2); ?></p>
                                    <p><strong>Change:</strong> ₱<?php echo number_format($receipt_data['change'], 2); ?>
                                    </p>
                                    <p><strong>Balance:</strong> ₱<?php echo number_format($receipt_data['balance'], 2); ?>
                                    </p>
                                    <p><strong>Selected Schedules:</strong> <?php echo $receipt_data['selected_weeks']; ?>
                                        week(s)</p>
                                </div>

                                <div class="border-t pt-2 text-left">
                                    <p class="font-semibold">Remaining Demo Payments:</p>
                                    <p>Demo 1: ₱<?php echo number_format($receipt_data['demo1_balance'], 2); ?></p>
                                    <p>Demo 2: ₱<?php echo number_format($receipt_data['demo2_balance'], 2); ?></p>
                                    <p>Demo 3: ₱<?php echo number_format($receipt_data['demo3_balance'], 2); ?></p>
                                    <p>Demo 4: ₱<?php echo number_format($receipt_data['demo4_balance'], 2); ?></p>
                                </div>
                            </div>

                            <div class="flex justify-center space-x-4 mt-6">
                                <button onclick="printReceipt()"
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    Print Receipt
                                </button>
                                <button onclick="closeReceipt()"
                                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!isset($_GET['id'])): ?>
                    <!-- Student Selection -->
                    <div class="mb-4 w-3/5">
                        <input type="text" id="searchInput" placeholder="Search student..."
                            class="w-full px-4 py-2 border border-gray-300 rounded" />
                    </div>
                    <div class="px-2 py-4 w-3/5 h-3/5 overflow-y-auto border-dashed border-2 mb-2">
                        <table class="w-full" id="studentTable">
                            <thead>
                                <tr>
                                    <th class="text-left px-4 py-2">Student_ID</th>
                                    <th class="text-left px-4 py-2">Student_name</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_students && $result_students->num_rows > 0) {
                                    while ($student_row = $result_students->fetch_assoc()) { ?>
                                        <tr class="bg-gray-200 border-2">
                                            <td class="py-2 px-4"><?php echo htmlspecialchars($student_row['student_number']); ?>
                                            </td>
                                            <td class="py-2 px-4">
                                                <?php echo htmlspecialchars($student_row['first_name'] . " " . $student_row['last_name'] . " " . $student_row['middle_name']); ?>
                                            </td>
                                            <td class="py-2 px-4">
                                                <a href="pos_adding.php?id=<?php echo urlencode($student_row['student_number']); ?>"
                                                    class="btn-success">Select Student</a>
                                            </td>
                                        </tr>
                                    <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <!-- Student Selected - Show Program Selection -->
                    <div class="flex flex-col bg-yellow-400/50 px-5 py-2">
                        <h1>Student name selected, You are about to add record to:</h1>
                        <?php if ($row): ?>
                            <span
                                class="font-bold"><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name'] . " " . $row['middle_name']); ?></span>
                        <?php endif; ?>
                        <a href="pos_adding.php" class="text-indigo-600">Cancel the Recording</a>
                    </div>

                    <div class="min-h-screen w-full px-5 py-4 bg-gray-300/50 rounded mt-5">
                        <?php if (!$package_details): ?>
                            <div class="grid grid-cols-2 gap-6 w-full">
                                <!-- STEP 1: Program Selection -->
                                <div class="flex flex-col">
                                    <h3 class="text-lg font-semibold mb-3">Step 1: Select Program</h3>
                                    <form method="POST" class="flex flex-col">
                                        <select class="w-full py-2 px-5 border-2 outline-none border-indigo-400 rounded"
                                            name="program_name" required>
                                            <option value="">Choose Program</option>
                                            <?php foreach ($programs as $program): ?>
                                                <option value="<?php echo htmlspecialchars($program['program_name']); ?>" <?php echo ($selected_program == $program['program_name']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($program['program_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                        <button type="submit" name="search_program" class="btn-success w-full mt-2">Load
                                            Packages</button>
                                    </form>
                                </div>

                                <!-- STEP 2: Package Selection -->
                                <div class="flex flex-col">
                                    <h3 class="text-lg font-semibold mb-3">Step 2: Select Package</h3>
                                    <?php if (!empty($tuition_packages)): ?>
                                        <form method="POST" class="flex flex-col">
                                            <select class="w-full py-2 px-5 border-2 outline-none border-indigo-400 rounded"
                                                name="package_name" required>
                                                <option value="">Choose Package</option>
                                                <?php foreach ($tuition_packages as $package): ?>
                                                    <option value="<?php echo htmlspecialchars($package['program_name']); ?>">
                                                        <?php echo htmlspecialchars($package['program_name']); ?>
                                                        - ₱<?php echo number_format($package['totalbasedtuition'], 2); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                            <input type="hidden" name="program_name"
                                                value="<?php echo htmlspecialchars($selected_program); ?>">
                                            <button type="submit" name="finalize_search" class="btn-success w-full mt-2">Select
                                                Package</button>
                                        </form>
                                    <?php else: ?>
                                        <p class="text-gray-500">Please select a program first</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- STEP 3: Payment Processing -->
                            <!-- STEP 3: Payment Processing -->
                                    <div class="bg-white rounded-lg shadow-lg p-6">
                                        <h3 class="text-xl font-semibold mb-4 text-indigo-700">Step 3: Process Payment</h3>
                                
                                        <div cla    ss="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            <!-- Pac        kage Details -->
                                            <div class="        bg-gray-50 p-4 rounded">
                                                <h4 class="font        -semibold mb-3">Package Details</h4>
                                                <div cla        ss="space-y-2 text-sm">
                                                    <p><stro        ng>Program:</strong> <?php echo htmlspecialchars($package_details['program_name']); ?></p>
                                                    <p><stro        ng>Package Number:</strong> <?php echo htmlspecialchars($package_details['package_number']); ?></p>
                                                    <p><stro        ng>Total Tuition:</strong> ₱<?php echo number_format($package_details['totalbasedtuition'], 2); ?></p>
                                                    <p><strong>S        ystem Fee:</strong> ₱<?php echo number_format($package_details['system Fee'], 2); ?></p>
                                                    <div class="        border-t pt-2 mt-2">
                                                        <p><stro        ng>Demo Payments:</strong></p>
                                                        <p c        lass="ml-4">Demo 1: ₱<?php echo number_format($package_details['demo1'], 2); ?></p>
                                                        <p c        lass="ml-4">Demo 2: ₱<?php echo number_format($package_details['demo2'], 2); ?></p>
                                                                <p class="ml-4">Demo 3: ₱<?php echo number_format($package_details['demo3'], 2); ?></p>
                                                                <p class="ml-4">Demo 4: ₱<?php echo number_format($package_details['demo4'], 2); ?></p>
                                                        </div>
                                                    </div>
                                            </div>
                                        </di    v>

                                        <!-- Payment Form - MOVED SCHEDULE SELECTION INSIDE THE FORM -->
                                        <form method="POST" class="mt-6" onsubmit="return validatePaymentForm()">
                                    
                                            <!-- Schedule Selection - NOW INSIDE THE FORM -->
                                            <div class="bg-gray-50 p-4 rounded mb-6">
                                                <h4 class="font-semibold mb-3">Training Schedule (Required)</h4>
                                                <?php if (!empty($schedules)): ?>
                                                        <div class="max-h-60 overflow-y-auto space-y-2">
                                                            <?php foreach ($schedules as $schedule): ?>
                                                                    <label class="flex items-start space-x-3 p-2 border rounded hover:bg-gray-100 cursor-pointer">
                                                                        <input type="checkbox" name="week[]" value="<?php echo $schedule['id']; ?>" 
                                                                            class="mt-1 schedule-checkbox" data-week="<?php echo $schedule['week']; ?>">
                                                                        <div class="text-sm">
                                                                            <p class="font-medium">Week <?php echo $schedule['week']; ?></p>
                                                                            <p class="text-gray-600"><?php echo htmlspecialchars($schedule['description']); ?></p>
                                                                            <p class="text-gray-500">
                                                                                <?php echo date('M d, Y', strtotime($schedule['training_date'])); ?> | 
                                                                                <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                                                                                <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                                                            </p>
                                                                            <p class="text-gray-500"><?php echo $schedule['day_of_week']; ?></p>
                                                                        </div>
                                                                    </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                <?php else: ?>
                                                        <p class="text-gray-500">No schedules available for this program.</p>
                                                <?php endif; ?>
                                            </div>

                                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                                <!-- Payment Type -->
                                                <div>
                                                    <label class="block text-sm font-medium mb-2">Payment Type</label>
                                                    <select name="payment_type" id="payment_type" class="w-full p-2 border rounded" required onchange="updatePaymentCalculation()">
                                                        <option value="">Select Payment Type</option>
                                                        <option value="reserve">Reserve (Demo 1 Only)</option>
                                                        <option value="partial">Partial Payment (Enroll)</option>
                                                        <option value="full">Full Payment</option>
                                                    </select>
                                                </div>

                                                <!-- Class Type -->
                                                <div>
                                                    <label class="block text-sm font-medium mb-2">Class Type</label>
                                                    <select name="class_type" id="class_type" class="w-full p-2 border rounded" required onchange="updatePaymentCalculation()">
                                                        <option value="">Select Class Type</option>
                                                        <option value="online">Online</option>
                                                        <option value="face_to_face">Face to Face</option>
                                                    </select>
                                                </div>

                                                <!-- Discount -->
                                                <div>
                                                    <label class="block text-sm font-medium mb-2">Discount (%)</label>
                                                    <input type="number" name="discount" id="discount" class="w-full p-2 border rounded" 
                                                        min="0" max="100" step="0.01" value="0" onchange="updatePaymentCalculation()">
                                                </div>
                                            </div>

                                            <!-- Payment Calculation Display -->
                                            <div class="mt-6 bg-blue-50 p-4 rounded">
                                                <h4 class="font-semibold mb-3">Payment Calculation</h4>
                                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                                    <div>
                                                        <p class="text-gray-600">Original Amount:</p>
                                                        <p class="font-semibold" id="original_amount">₱<?php echo number_format($package_details['totalbasedtuition'], 2); ?></p>
                                                    </div>
                                                    <div id="system_fee_display" style="display: none;">
                                                        <p class="text-gray-600">System Fee (Deducted):</p>
                                                        <p class="font-semibold text-red-600" id="system_fee_amount">₱<?php echo number_format($package_details['system Fee'], 2); ?></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-600">Adjusted Total:</p>
                                                        <p class="font-semibold" id="adjusted_total">₱<?php echo number_format($package_details['totalbasedtuition'], 2); ?></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-600">Final Amount (After Discount):</p>
                                                        <p class="font-semibold text-green-600" id="final_amount">₱<?php echo number_format($package_details['totalbasedtuition'], 2); ?></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Cash Payment -->
                                            <div class="mt-6">
                                                <label class="block text-sm font-medium mb-2">Cash Payment Amount</label>
                                                <input type="number" name="cash_payment" id="cash_payment" class="w-full p-3 border rounded text-lg" 
                                                    min="0" step="0.01" required onchange="calculateChange()">
                                            </div>

                                            <!-- Change and Balance Display -->
                                            <div class="mt-4 grid grid-cols-2 gap-4">
                                                <div class="bg-green-50 p-3 rounded">
                                                    <p class="text-sm text-gray-600">Change:</p>
                                                    <p class="text-lg font-semibold text-green-600" id="change_display">₱0.00</p>
                                                </div>
                                                <div class="bg-yellow-50 p-3 rounded">
                                                    <p class="text-sm text-gray-600">Remaining Balance:</p>
                                                    <p class="text-lg font-semibold text-yellow-600" id="balance_display">₱0.00</p>
                                                </div>
                                            </div>

                                            <!-- Hidden Fields -->
                                            <input type="hidden" name="student_number" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                            <input type="hidden" name="program_name" value="<?php echo htmlspecialchars($selected_program); ?>">
                                            <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($package_details['id']); ?>">
                                            <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">

                                            <div class="mt-6 flex justify-end space-x-4">
                                                <a href="pos_adding.php?id=<?php echo urlencode($_GET['id']); ?>" class="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                                    Back
                                                </a>
                                                <button type="submit" name="process_payment" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                                    Process Payment
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                <?php endif; ?>
            </div>
<?php endif; ?>

<!-- JavaScript for Payment Calculations -->
<script>
// Package data from PHP
const packageData = {
    totalTuition: <?php echo $package_details ? $package_details['totalbasedtuition'] : 0; ?>,
    systemFee: <?php echo $package_details ? $package_details['system Fee'] : 0; ?>,
    demo1: <?php echo $package_details ? $package_details['demo1'] : 0; ?>,
    demo2: <?php echo $package_details ? $package_details['demo2'] : 0; ?>,
    demo3: <?php echo $package_details ? $package_details['demo3'] : 0; ?>,
    demo4: <?php echo $package_details ? $package_details['demo4'] : 0; ?>
};

function updatePaymentCalculation() {
    const classType = document.getElementById('class_type').value;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    
    let adjustedTotal = packageData.totalTuition;
    
    // Deduct system fee for face-to-face classes
    if (classType === 'face_to_face') {
        adjustedTotal = packageData.totalTuition - packageData.systemFee;
        document.getElementById('system_fee_display').style.display = 'block';
    } else {
        document.getElementById('system_fee_display').style.display = 'none';
    }
    
    // Apply discount
    const discountAmount = adjustedTotal * (discount / 100);
    const finalAmount = adjustedTotal - discountAmount;
    
    // Update display
    document.getElementById('adjusted_total').textContent = '₱' + adjustedTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('final_amount').textContent = '₱' + finalAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
    
    // Recalculate change if cash payment is entered
    calculateChange();
}

function calculateChange() {
    const classType = document.getElementById('class_type').value;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const cashPayment = parseFloat(document.getElementById('cash_payment').value) || 0;
    
    let adjustedTotal = packageData.totalTuition;
    if (classType === 'face_to_face') {
        adjustedTotal = packageData.totalTuition - packageData.systemFee;
    }
    
    const discountAmount = adjustedTotal * (discount / 100);
    const finalAmount = adjustedTotal - discountAmount;
    
    const change = Math.max(0, cashPayment - finalAmount);
    const balance = Math.max(0, finalAmount - cashPayment);
    
    document.getElementById('change_display').textContent = '₱' + change.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('balance_display').textContent = '₱' + balance.toLocaleString('en-US', {minimumFractionDigits: 2});
}

function validatePaymentForm() {
    const selectedSchedules = document.querySelectorAll('input[name="week[]"]:checked');
    console.log('Selected schedules count:', selectedSchedules.length); // Debug log
    
    if (selectedSchedules.length === 0) {
        alert('Please select at least one training schedule.');
        return false;
    }
    
    const paymentType = document.getElementById('payment_type').value;
    const classType = document.getElementById('class_type').value;
    const cashPayment = parseFloat(document.getElementById('cash_payment').value) || 0;
    
    if (!paymentType || !classType) {
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (cashPayment <= 0) {
        alert('Please enter a valid cash payment amount.');
        return false;
    }
    
    console.log('Form validation passed'); // Debug log
    return true;
}
// Receipt functions
function printReceipt() {
    const receiptContent = document.getElementById('receiptContent');
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .center { text-align: center; }
                    .left { text-align: left; }
                    .border { border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
                </style>
            </head>
            <body>
                ${receiptContent.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function closeReceipt() {
    document.getElementById('receiptModal').style.display = 'none';
    window.location.href = 'pos_adding.php';
}

// Student search functionality
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const table = document.getElementById('studentTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let match = false;
        
        for (let j = 0; j < cells.length - 1; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }
        
        rows[i].style.display = match ? '' : 'none';
    }
});

// Auto-show receipt modal if payment was processed
<?php if ($payment_processed): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('receiptModal').style.display = 'flex';
    });
<?php endif; ?>
</script>

</div>
</main>
</div>
</div>

<?php include "includes/footer.php" ?>