<?php
include "../connection/connection.php";
include "includes/header.php";
$conn = con();

$student = null;
$open = true;

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // Prepared statement for student info
    $stmt = $conn->prepare("SELECT * FROM student_info_tbl WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    // Check if student has existing balance
    $stmt = $conn->prepare("SELECT SUM(balance) as total_balance FROM pos_transactions WHERE student_id = ? AND balance > 0");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $balance_result = $stmt->get_result()->fetch_assoc();
    $has_balance = $balance_result['total_balance'] > 0;
    $stmt->close();

    // Get all transactions for this student
    $stmt = $conn->prepare("SELECT * FROM pos_transactions WHERE student_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $transaction_Result = $stmt->get_result();
    $transactions = [];

    if ($transaction_Result->num_rows > 0) {
        while ($row = $transaction_Result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $row_transaction = $transactions[0]; // Most recent transaction
        $open = false;

        // Get program details
        $stmt = $conn->prepare("SELECT * FROM program WHERE id = ?");
        $stmt->bind_param("i", $row_transaction['program_id']);
        $stmt->execute();
        $program_results = $stmt->get_result();

        if ($program_results->num_rows > 0) {
            $row_program = $program_results->fetch_assoc();
            $program = $row_program['program_name'];
        } else {
            $program = "THIS PROGRAM IS REMOVED";
            $row_program = ['id' => 0, 'total_tuition' => 0];
        }
        $stmt->close();

        // Get promo details with enhanced selection type support
        $stmt = $conn->prepare("SELECT * FROM promo WHERE program_id = ?");
        $stmt->bind_param("i", $row_program['id']);
        $stmt->execute();
        $promo_result = $stmt->get_result();

        if ($promo_result->num_rows > 0) {
            $row_promo = $promo_result->fetch_assoc();
        } else {
            $row_promo = [
                'package_name' => 'Regular',
                'enrollment_fee' => 0,
                'percentage' => 0,
                'promo_type' => 'none',
                'selection_type' => 1,
                'custom_initial_payment' => null
            ];
        }
        $stmt->close();

        // Calculate promo discount with selection type support
        $TT = floatval($row_program['total_tuition'] ?? 0);
        $PR = 0;

        if ($row_promo['package_name'] !== "Regular" && $row_promo['promo_type'] !== 'none') {
            $selection_type = intval($row_promo['selection_type'] ?? 1);

            if ($selection_type <= 2) {
                // Options 1-2: Percentage calculation
                if ($row_promo['promo_type'] === 'percentage') {
                    $PR = $TT * (floatval($row_promo['percentage']) / 100);
                } else {
                    $PR = floatval($row_promo['enrollment_fee']);
                }
            } else {
                // Options 3-4: Custom payment (discount is already in enrollment_fee)
                $PR = floatval($row_promo['enrollment_fee']);
            }
        }

        // Get existing payment amounts and track paid payment types and demos
        $payment_counts = [
            'initial_payment' => 0,
            'reservation' => 0,
            'demo_payment' => 0,
            'full_payment' => 0
        ];

        $paid_demos = [];
        $paid_payment_types = [];
        $IP = 0;
        $R = 0;

        $stmt = $conn->prepare("SELECT cash_received, payment_type, demo_type FROM pos_transactions WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $payment_result = $stmt->get_result();

        if ($payment_result->num_rows > 0) {
            while ($payment_row = $payment_result->fetch_assoc()) {
                $payment_type = $payment_row['payment_type'];
                $amount = floatval($payment_row['cash_received']);

                if (isset($payment_counts[$payment_type])) {
                    $payment_counts[$payment_type]++;
                }

                switch ($payment_type) {
                    case 'initial_payment':
                        $IP += $amount;
                        if (!in_array('initial_payment', $paid_payment_types)) {
                            $paid_payment_types[] = 'initial_payment';
                        }
                        break;
                    case 'reservation':
                        $R += $amount;
                        if (!in_array('reservation', $paid_payment_types)) {
                            $paid_payment_types[] = 'reservation';
                        }
                        break;
                    case 'demo_payment':
                        if (!empty($payment_row['demo_type'])) {
                            $paid_demos[] = $payment_row['demo_type'];
                        }
                        break;
                    case 'full_payment':
                        $paid_payment_types = ['initial_payment', 'reservation', 'demo_payment'];
                        break;
                }
            }
        }
        $stmt->close();

        $final_total = $TT - $PR;

        // Proper demo fee calculation
        $result_balance = $conn->query("SELECT * FROM `pos_transactions` WHERE student_id = '$student_id' ORDER BY `pos_transactions`.`id` DESC");
        $row_balance_total = $result_balance->fetch_assoc();
        $balance_total = $row_balance_total['balance'];

        // Calculate demo fees properly
        if ($open == false) {
            $paidDemosCount = count($paid_demos);
            $remainingDemos = 4 - $paidDemosCount;

            if ($remainingDemos > 0 && $balance_total > 0) {
                $CDM = $balance_total / $remainingDemos;
            } else if ($remainingDemos > 0) {
                $initialPayment = floatval($row_program['initial_fee'] ?? 0);
                $reservationPayment = floatval($row_program['reservation_fee'] ?? 0);
                $remainingAfterInitialReservation = $final_total - $initialPayment - $reservationPayment;
                $CDM = max(0, $remainingAfterInitialReservation / 4);
            } else {
                $CDM = 0;
            }
        } else {
            $CDM = 0;
        }

        // Get FIRST transaction's schedule for maintenance
        $first_transaction_stmt = $conn->prepare("
            SELECT selected_schedules 
            FROM pos_transactions 
            WHERE student_id = ? 
            AND program_id = ? 
            AND selected_schedules IS NOT NULL 
            AND selected_schedules != '' 
            AND selected_schedules != '[]'
            ORDER BY id ASC 
            LIMIT 1
        ");
        $first_transaction_stmt->bind_param("ii", $student_id, $row_program['id']);
        $first_transaction_stmt->execute();
        $first_transaction_result = $first_transaction_stmt->get_result();

        $maintained_schedules = [];
        if ($first_transaction_result->num_rows > 0) {
            $first_transaction = $first_transaction_result->fetch_assoc();
            if (!empty($first_transaction['selected_schedules'])) {
                $schedule_data = json_decode($first_transaction['selected_schedules'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($schedule_data)) {
                    $maintained_schedules = $schedule_data;
                }
            }
        }
        $first_transaction_stmt->close();

        $selected_schedules_data = !empty($maintained_schedules) ? $maintained_schedules : [];
        if (empty($selected_schedules_data) && !empty($row_transaction['selected_schedules'])) {
            $schedule_data = json_decode($row_transaction['selected_schedules'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($schedule_data)) {
                $selected_schedules_data = $schedule_data;
            }
        }
    }

    // Get current balance
    $balance_total = 0;
    $subtotal = 0;

    if ($student_id > 0) {
        $stmt = $conn->prepare("SELECT balance, subtotal FROM pos_transactions WHERE student_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row_balance = $result->fetch_assoc()) {
            $balance_total = floatval($row_balance['balance']);
            $subtotal = floatval($row_balance['subtotal']);
        }
        $stmt->close();
    }
}
?>
<?php
// After fetching $transactions and $open
$enrollment_locked = false;
if (isset($transactions) && count($transactions) > 0) {
    $enrollment_locked = true;
}
?>
<!-- Add SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #section-to-print,
        #section-to-print * {
            visibility: visible;
        }

        #section-to-print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 20px !important;
        }

        .no-print {
            display: none !important;
        }

        .print-only {
            display: block !important;
        }
    }

    .print-only {
        display: none;
    }

    .payment-hidden {
        display: none !important;
    }

    .payment-type-option {
        transition: opacity 0.3s ease;
    }

    .payment-type-option.hidden {
        opacity: 0.3;
        pointer-events: none;
    }

    .schedule-warning {
        background: linear-gradient(135deg, #ff7b7b 0%, #ff416c 100%);
        color: white;
        padding: 10px;
        border-radius: 8px;
        margin: 10px 0;
        font-size: 14px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.8;
        }

        100% {
            opacity: 1;
        }
    }

    .schedule-maintained {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 10px;
        border-radius: 8px;
        margin: 10px 0;
        font-size: 14px;
    }

    .schedule-locked {
        background-color: #e8f5e8 !important;
        border: 2px solid #28a745 !important;
    }

    .schedule-locked input[type="checkbox"] {
        accent-color: #28a745;
    }

    .payment-note {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        margin-top: 5px;
    }

    .schedule-debug {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
        font-family: monospace;
        font-size: 12px;
    }

    .demo-fee-calculated {
        background: #e8f5e8;
        border-left: 4px solid #28a745;
        padding: 8px;
        margin: 5px 0;
        font-weight: bold;
    }

    .program-details-section,
    .charges-section,
    .demo-fees-display {
        display: block !important;
        visibility: visible !important;
    }

    /* Excess Payment Modal Styles */
    .excess-payment-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease-out;
    }

    .excess-payment-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        animation: slideInUp 0.4s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .excess-option {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        margin: 10px 0;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .excess-option:hover {
        border-color: #007bff;
        background: #e3f2fd;
        transform: translateY(-2px);
    }

    .excess-option.selected {
        border-color: #28a745;
        background: #d4edda;
    }

    .excess-option-title {
        font-weight: bold;
        color: #495057;
        margin-bottom: 5px;
    }

    .excess-option-description {
        color: #6c757d;
        font-size: 14px;
    }

    .excess-breakdown {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
    }

    .excess-breakdown-title {
        font-weight: bold;
        color: #856404;
        margin-bottom: 10px;
    }

    .excess-calculation {
        display: flex;
        justify-content: space-between;
        margin: 5px 0;
        padding: 5px 0;
        border-bottom: 1px dotted #dee2e6;
    }

    .excess-calculation:last-child {
        border-bottom: none;
        font-weight: bold;
        color: #dc3545;
    }

    .demo-allocation-preview {
        background: #e8f5e8;
        border: 1px solid #c3e6cb;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
    }

    .demo-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #dee2e6;
    }

    .demo-item:last-child {
        border-bottom: none;
    }

    .demo-status {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }

    .demo-paid {
        background: #d4edda;
        color: #155724;
    }

    .demo-partial {
        background: #fff3cd;
        color: #856404;
    }

    .demo-unpaid {
        background: #f8d7da;
        color: #721c24;
    }

    .excess-btn-primary {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .excess-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .excess-btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .excess-btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .payment-summary-enhanced {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin: 15px 0;
    }

    .payment-summary-row {
        display: flex;
        justify-content: space-between;
        margin: 8px 0;
        padding: 5px 0;
    }

    .payment-summary-row.total {
        border-top: 2px solid rgba(255, 255, 255, 0.3);
        font-weight: bold;
        font-size: 16px;
        margin-top: 15px;
        padding-top: 15px;
    }

    .excess-highlight {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
        padding: 10px;
        border-radius: 8px;
        margin: 10px 0;
        text-align: center;
        font-weight: bold;
        animation: pulseExcess 2s infinite;
    }

    @keyframes pulseExcess {

        0%,
        100% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.02);
            opacity: 0.9;
        }
    }
</style>

<div class="flex min-h-screen overflow-y-auto">
    <!-- Sidebar -->
    <div id="sidebar" class="bg-white shadow-lg w-[20%] flex-col z-10 md:flex hidden fixed min-h-screen">
        <div class="px-6 py-5 border-b border-gray-100">
            <h1 class="text-xl font-bold text-primary flex items-center">
                <i class='bx bx-plus-medical text-2xl mr-2'></i>CarePro
            </h1>
        </div>
        <?php include "includes/navbar.php" ?>
    </div>

    <!-- Main Content -->
    <div id="content" class="flex-1 flex-col w-[80%] md:ml-[20%] transition-all duration-300">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <button id="toggleSidebar" class="md:hidden text-gray-500 hover:text-primary">
                    <i class='bx bx-menu text-2xl'></i>
                </button>
                <div class="flex items-center space-x-4">
                    <button class="text-gray-500 hover:text-primary" data-bs-toggle="modal"
                        data-bs-target="#announcementModal">
                        <i class="fa-solid fa-bullhorn mr-1"></i>New Announcement
                    </button>
                    <button class="text-gray-500 hover:text-primary" data-bs-toggle="modal"
                        data-bs-target="#viewAnnouncementsModal">
                        <i class="fa-solid fa-envelope mr-1"></i>View Announcements
                    </button>
                    <button class="text-gray-500 hover:text-primary" id="logout">
                        <i class="fa-solid fa-right-from-bracket"></i>Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-6 min-h-screen">
            <div class="bg-white rounded-xl border-2 p-6 min-h-screen">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold">Care Pro POS System with Excess Payment Handling</h1>
                    <p class="italic">Enhanced POS System with Excess Payment Management - Updated 2025-06-21 06:07:34
                    </p>

                    <?php if (isset($has_balance) && $has_balance): ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mt-2">
                            <strong><i class="fa-solid fa-triangle-exclamation mr-2"></i>Alert:</strong> This student
                            currently has an outstanding balance. Please settle the balance to re-enroll the student.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Debug Information -->
                <div id="debugInfo" class="schedule-debug" style="display: none;">
                    <strong>Debug Information:</strong><br>
                    <span id="debugMaintained">Maintained Schedules: Loading...</span><br>
                    <span id="debugSelected">Selected Schedules: Loading...</span><br>
                    <span id="debugValidation">Validation Status: Loading...</span><br>
                    <span id="debugExcess">Excess Handler: Ready</span>
                </div>
                <button type="button" onclick="toggleDebug()" class="bg-gray-200 px-3 py-1 rounded text-sm mb-4">
                    <i class="fa-solid fa-bug mr-1"></i>Toggle Debug Info
                </button>

                <!-- Form Section -->
                <form id="posForm">
                    <div class="flex gap-6 mb-6">
                        <div class="space-y-4 min-w-[300px]">
                            <!-- Student Name -->
                            <div class="flex items-center">
                                <span class="font-semibold w-32">Name:</span>
                                <input type="text" id="studentName" name="student_name"
                                    value="<?= $student ? htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ', ' . $student['middle_name']) : '' ?>"
                                    class="flex-1 border rounded px-3 py-1 outline-none" readonly />
                                <input type="hidden" name="student_id"
                                    value="<?= htmlspecialchars($_GET['student_id'] ?? '') ?>" />
                            </div>

                            <!-- Learning Mode -->
                            <div class="flex items-center">
                                <span class="font-semibold w-32">Learning Mode:</span>
                                <div class="flex gap-4 ml-2">
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="learning_mode" value="F2F" checked />
                                        <i class="fa-solid fa-chalkboard-teacher mr-1"></i>F2F
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="learning_mode" value="Online" />
                                        <i class="fa-solid fa-laptop mr-1"></i>Online
                                    </label>
                                </div>
                            </div>

                            <!-- Program -->
                            <div class="flex items-center">
                                <span class="font-semibold w-32">Program:</span>
                                <select id="programSelect" name="program_id"
                                    class="flex-1 border rounded px-3 py-1 outline-none">
                                    <option value="">Loading programs...</option>
                                </select>
                            </div>

                            <!-- Package -->
                            <div class="flex items-center">
                                <span class="font-semibold w-32">Package:</span>
                                <select id="packageSelect" name="package_id" required
                                    class="flex-1 border rounded px-3 py-1 outline-none">
                                    <option value="">Loading programs...</option>
                                </select>
                            </div>
                            <span class="text-sm italic text-yellow-500" id="locked_warning"></span>
                        </div>

                        <!-- Enhanced Schedule Table -->
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="font-semibold">Class Schedule</h2>
                            </div>

                            <div id="scheduleMaintained" class="schedule-maintained" style="display: none;">
                                <i class="fa-solid fa-lock mr-2"></i>
                                <strong>Schedule Maintained:</strong> Using the same schedule from your initial
                                enrollment.
                            </div>
                            <div id="alert" class="w-full px-4 py-2 bg-orange-200 text-semibold italic"></div>

                            <div id="scheduleWarning" class="schedule-warning" style="display: none;">
                                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                                <strong>Warning:</strong> Please select at least one schedule before processing payment!
                            </div>

                            <table id="scheduleTable" class="w-full border-collapse border text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <?php foreach (['Select', 'Week Description', 'Training Date', 'Start Time', 'End Time', 'Day'] as $header): ?>
                                            <th class="border px-2 py-1"><?= htmlspecialchars($header) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>

                            <?php if ($open == false): ?>
                                <input type="hidden" id="hiddenSchedule"
                                    value="<?= htmlspecialchars(json_encode($selected_schedules_data ?? [])) ?>"
                                    name="selected_schedules" />
                                <input type="hidden" id="maintainedSchedules"
                                    value="<?= htmlspecialchars(json_encode($maintained_schedules ?? [])) ?>" />
                            <?php else: ?>
                                <input type="hidden" id="hiddenSchedule" name="selected_schedules" value="[]" />
                                <input type="hidden" id="maintainedSchedules" value="[]" />
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="button" class="bg-indigo-100 py-2 px-4 rounded mb-4" onclick="printReceiptSection2()">
                        <i class="fa-solid fa-print mr-2"></i>Print Receipt
                    </button>

                    <!-- Receipt and Payment Section -->
                    <div class="flex gap-4">
                        <!-- Receipt -->
                        <div class="flex-1 border-2 bg-white rounded-lg shadow-md max-w-3xl mx-auto my-4"
                            id="receiptSection">
                            <div class="bg-gray-200 p-4 rounded-t-lg text-center">
                                <h1 class="font-bold text-3xl" id="programTitle">Care Pro</h1>
                                <span class="italic text-gray-700">Official Receipt</span>
                            </div>

                            <div class="flex flex-col md:flex-row border-t">
                                <!-- Description -->
                                <div class="w-full md:w-1/2 p-4 program-details-section">
                                    <h2 class="font-semibold text-xl mb-2">Description</h2>

                                    <?php if ($open == false): ?>
                                        <ul id="descriptionList" class="space-y-1 text-sm text-gray-700">
                                            <li id="learningMode">
                                                <strong>Program:</strong>
                                                <?= htmlspecialchars($row_program['program_name'] ?? "This Data is deleted") ?>
                                                <span
                                                    class="inline-block ml-2 px-2 py-1 text-xs font-semibold rounded-full <?= ($row_transaction['learning_mode'] ?? '') === 'Online' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                                    <?php if (($row_transaction['learning_mode'] ?? '') === 'Online'): ?>
                                                        <i class="fa-solid fa-laptop mr-1"></i>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-chalkboard-teacher mr-1"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($row_transaction['learning_mode'] ?? 'N/A') ?>
                                                </span>
                                            </li>
                                            <li id="packageInfo">
                                                <strong>Package:</strong>
                                                <?= htmlspecialchars($row_promo['package_name'] ?? "Regular") ?>
                                            </li>
                                            <li id="enrollmentDate">
                                                <strong>Enrollment Date:</strong> <?= date('Y-m-d H:i:s') ?>
                                            </li>
                                            <li id="studentInfo">
                                                <strong>Student ID:</strong> <?= htmlspecialchars($student_id) ?>
                                            </li>
                                            <?php if ($row_promo['package_name'] !== "Regular"): ?>
                                                <li id="promoInfo" class="text-green-600 font-semibold">
                                                    <strong>Promo Discount:</strong> ₱<?= number_format($PR ?? 0, 2) ?>
                                                    <br><small>Automatic <?= $row_promo['percentage'] ?>% discount
                                                        applied</small>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php else: ?>
                                        <ul id="descriptionList" class="space-y-1 text-sm text-gray-700">
                                            <li id="programName">Select a program to view details</li>
                                            <li id="learningMode"></li>
                                            <li id="packageInfo"></li>
                                            <li id="promoInfo" class="text-green-600 font-semibold hidden"></li>
                                        </ul>
                                    <?php endif; ?>
                                </div>

                                <!-- Charges -->
                                <div class="w-full p-4 charges-section" id="chargesContainer">
                                    <?php if ($open == false): ?>
                                        <h1 class="font-semibold text-xl mb-2">Charges</h1>

                                        <div
                                            class="mb-3 p-2 rounded-lg <?= ($row_transaction['learning_mode'] ?? '') === 'Online' ? 'bg-blue-50 border border-blue-200' : 'bg-green-50 border border-green-200' ?>">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium">Learning Mode:</span>
                                                <span
                                                    class="px-3 py-1 text-sm font-semibold rounded-full <?= ($row_transaction['learning_mode'] ?? '') === 'Online' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                                    <?php if (($row_transaction['learning_mode'] ?? '') === 'Online'): ?>
                                                        <i class="fa-solid fa-laptop mr-1"></i>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-chalkboard-teacher mr-1"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($row_transaction['learning_mode'] ?? 'N/A') ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex flex-row w-full p-4 gap-4">
                                            <ul class="flex-1 list-none space-y-1">
                                                <li>Assessment Fee:
                                                    ₱<?= number_format($row_program['assesment_fee'] ?? 0, 2) ?></li>
                                                <li>Tuition Fee: ₱<?= number_format($row_program['tuition_fee'] ?? 0, 2) ?>
                                                </li>
                                                <li>Miscellaneous Fee:
                                                    ₱<?= number_format($row_program['misc_fee'] ?? 0, 2) ?></li>
                                                <li>Uniform Fee: ₱<?= number_format($row_program['uniform_fee'] ?? 0, 2) ?>
                                                </li>
                                                <li>ID Fee: ₱<?= number_format($row_program['id_fee'] ?? 0, 2) ?></li>
                                                <li>Book Fee: ₱<?= number_format($row_program['book_fee'] ?? 0, 2) ?></li>
                                                <li>Kit Fee: ₱<?= number_format($row_program['kit_fee'] ?? 0, 2) ?></li>
                                                <?php if (($row_transaction['learning_mode'] ?? '') === 'Online' && isset($row_program['system_fee'])): ?>
                                                    <li class="text-blue-600">System Fee:
                                                        ₱<?= number_format($row_program['system_fee'] ?? 0, 2) ?></li>
                                                <?php endif; ?>
                                            </ul>

                                            <!-- Demo fees -->
                                            <ul class="flex-1 space-y-1 demo-fees-display">
                                                <li class="demo-fee-calculated">Demo 1 Fee: ₱<?= number_format($CDM, 2) ?>
                                                    <?php if (in_array('demo1', $paid_demos)): ?>
                                                        <span class="text-green-600 text-xs ml-1"><i
                                                                class="fa-solid fa-check-circle"></i> Paid</span>
                                                    <?php endif; ?>
                                                </li>
                                                <li class="demo-fee-calculated">Demo 2 Fee: ₱<?= number_format($CDM, 2) ?>
                                                    <?php if (in_array('demo2', $paid_demos)): ?>
                                                        <span class="text-green-600 text-xs ml-1"><i
                                                                class="fa-solid fa-check-circle"></i> Paid</span>
                                                    <?php endif; ?>
                                                </li>
                                                <li class="demo-fee-calculated">Demo 3 Fee: ₱<?= number_format($CDM, 2) ?>
                                                    <?php if (in_array('demo3', $paid_demos)): ?>
                                                        <span class="text-green-600 text-xs ml-1"><i
                                                                class="fa-solid fa-check-circle"></i> Paid</span>
                                                    <?php endif; ?>
                                                </li>
                                                <li class="demo-fee-calculated">Demo 4 Fee: ₱<?= number_format($CDM, 2) ?>
                                                    <?php if (in_array('demo4', $paid_demos)): ?>
                                                        <span class="text-green-600 text-xs ml-1"><i
                                                                class="fa-solid fa-check-circle"></i> Paid</span>
                                                    <?php endif; ?>
                                                </li>

                                                <?php if (!empty($paid_demos)): ?>
                                                    <li class="text-blue-600 text-sm mt-2">
                                                        <strong>Completed Demos:</strong><br>
                                                        <?= implode(', ', array_map('strtoupper', $paid_demos)) ?>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <?php if ($PR > 0): ?>
                                            <div class="text-green-600 text-center mb-2 p-2 bg-green-50 rounded">
                                                <strong>Promo Discount Applied: -₱<?= number_format($PR, 2) ?></strong>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mt-4 p-3 bg-green-100 rounded-lg border border-green-300">
                                            <div class="flex justify-between items-center">
                                                <span class="font-bold text-lg">Total Amount:</span>
                                                <span
                                                    class="font-bold text-xl text-green-800">₱<?= number_format($final_total ?? 0, 2) ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- For first transactions, show placeholder -->
                                        <div id="chargesPlaceholder">
                                            <h1 class="font-semibold text-xl mb-2">Charges</h1>
                                            <div class="text-center text-gray-500 py-8">
                                                <i class="fa-solid fa-graduation-cap text-4xl mb-4"></i>
                                                <p>Select a program to view charges and demo fees immediately</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Payment Schedule -->
                            <div class="p-4">
                                <h2 class="font-semibold text-xl mb-2">Payment Schedule</h2>
                                <div class="overflow-x-auto">
                                    <table class="w-full border border-gray-300 text-sm">
                                        <thead class="bg-gray-100 text-left">
                                            <tr>
                                                <th class="border px-2 py-1">Date</th>
                                                <th class="border px-2 py-1">Description</th>
                                                <th class="border px-2 py-1">Credit</th>
                                                <th class="border px-2 py-1">Change</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($transactions)): ?>
                                                <?php
                                                $total_credit = 0;
                                                $total_change = 0;
                                                foreach ($transactions as $row_transaction):
                                                    $credit = floatval($row_transaction['cash_received'] ?? 0);
                                                    $change = floatval($row_transaction['change_amount'] ?? 0);
                                                    $total_credit += $credit;
                                                    $total_change += $change;
                                                    ?>
                                                    <tr>
                                                        <td class="border px-2 py-1">
                                                            <?= isset($row_transaction['transaction_date']) ? date('Y-m-d', strtotime($row_transaction['transaction_date'])) : date('Y-m-d') ?>
                                                        </td>
                                                        <td class="border px-2 py-1">
                                                            <?= htmlspecialchars($row_transaction['payment_type'] . " " . ($row_transaction['demo_type'] ?? '')); ?>
                                                        </td>
                                                        <td class="border px-2 py-1">₱<?= number_format($credit, 2); ?></td>
                                                        <td class="border px-2 py-1">₱<?= number_format($change, 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="bg-gray-100 font-semibold">
                                                    <td colspan="2" class="border px-2 py-1 text-right">Total:</td>
                                                    <td class="border px-2 py-1">₱<?= number_format($total_credit, 2); ?>
                                                    </td>
                                                    <td class="border px-2 py-1">₱<?= number_format($total_change, 2); ?>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="border px-2 py-1 text-center text-gray-500">
                                                        <div class="py-4">
                                                            <div class="text-gray-600 mb-2">
                                                                <i class="fa-solid fa-info-circle mr-2"></i>No payment
                                                                records found.
                                                            </div>
                                                            <div class="text-blue-600 text-sm">
                                                                <i class="fa-solid fa-check-circle mr-2"></i>Ready for new
                                                                enrollment
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <span class="mt-5 block">Total Remaining Balance: <span
                                            class="font-bold">₱<?= number_format($balance_total, 2) ?></span></span>
                                </div>
                            </div>

                            <div class="border-t p-4 text-center text-sm text-gray-600 italic">
                                <p>Thank you for choosing Care Pro!</p>
                                <p>For inquiries, please contact us at <strong>0912-345-6789</strong> or email
                                    <strong>support@carepro.ph</strong>.
                                </p>
                                <p class="mt-2 font-semibold text-gray-800">--- END OF RECEIPT ---</p>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="w-80 border-2 p-4">
                            <div class="border-2 border-dashed p-4 mb-4">
                                <h1 class="text-xl mb-3">Type of Payment</h1>

                                <?php
                                $hiddenPayments = [];
                                if (isset($paid_payment_types)) {
                                    foreach ($paid_payment_types as $payment_type) {
                                        if ($payment_type === 'full_payment' || $payment_type === 'initial_payment' || $payment_type === 'reservation') {
                                            $hiddenPayments[] = $payment_type;
                                        }
                                    }
                                }

                                $paymentTypes = [
                                    'full_payment' => 'Full Payment',
                                    'initial_payment' => 'Initial Payment',
                                    'demo_payment' => 'Demo Payment',
                                    'reservation' => 'Reservation'
                                ];
                                ?>

                                <?php foreach ($paymentTypes as $value => $label): ?>
                                    <?php $isHidden = in_array($value, $hiddenPayments); ?>
                                    <label
                                        class="flex items-center mb-2 payment-type-option <?= $isHidden ? 'payment-hidden' : '' ?>"
                                        data-payment-type="<?= $value ?>">
                                        <input type="radio" name="type_of_payment" value="<?= $value ?>" class="mr-2"
                                            onchange="updatePaymentData()" <?= $isHidden ? 'disabled' : '' ?> />
                                        <span><?= htmlspecialchars($label) ?></span>
                                        <span class="payment-status text-sm text-gray-500 ml-2"
                                            style="display: none;"></span>
                                    </label>
                                <?php endforeach; ?>

                                <!-- Payment note -->
                                <div id="paymentNote" class="payment-note" style="display: none;">
                                    <i class="fa-solid fa-info-circle mr-2"></i>
                                    <strong>Note:</strong> Processing with excess payment handling enabled
                                </div>

                                <!-- Demo Selection -->
                                <div id="demoSelection" class="mt-3" style="display: none;">
                                    <label class="block text-sm font-semibold mb-2">Select Demo:</label>
                                    <select id="demoSelect" name="demo_type" class="w-full border rounded px-2 py-1">
                                        <option value="">Select Demo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="border-2 border-dashed p-4 mb-4 grid grid-cols-2 gap-2">
                                <span>Total Payment</span>
                                <input type="number" id="totalPayment" name="total_payment"
                                    class="border-2 rounded px-2 py-1 outline-none" onkeyup="updatePaymentData()"
                                    step="0.01" min="0" placeholder="Amount will auto-fill" />

                                <span>Cash to pay</span>
                                <input type="number" id="cashToPay" name="cash_to_pay"
                                    class="border-2 rounded px-2 py-1 outline-none" onkeyup="updatePaymentData()"
                                    step="0.01" min="0" />

                                <span>Cash</span>
                                <input type="number" id="cash" name="cash"
                                    class="border-2 rounded px-2 py-1 outline-none" onkeyup="updatePaymentData()"
                                    step="0.01" min="0" />

                                <span>Change</span>
                                <input type="number" id="change" name="change"
                                    class="border-2 rounded px-2 py-1 outline-none" readonly step="0.01" />
                            </div>

                            <!-- Hidden fields for excess handling -->
                            <input type="hidden" name="program_details" id="programDetailsHidden" />
                            <input type="hidden" name="package_details" id="packageDetailsHidden" />
                            <input type="hidden" name="subtotal" id="subtotalHidden" />
                            <input type="hidden" name="final_total" id="finalTotalHidden" />
                            <input type="hidden" name="promo_applied" id="promoAppliedHidden" value="0" />
                            <input type="hidden" name="paid_demos" id="paidDemosField"
                                value="<?= htmlspecialchars(json_encode($paid_demos ?? [])) ?>" />
                            <input type="hidden" name="paid_payment_types" id="paidPaymentTypesField"
                                value="<?= htmlspecialchars(json_encode($paid_payment_types ?? [])) ?>" />
                            <input type="hidden" name="excess_choice" id="excessChoiceField" value="" />
                            <input type="hidden" name="excess_amount" id="excessAmountField" value="0" />
                            <input type="hidden" name="excess_allocations" id="excessAllocationsField" value="[]" />

                            <button type="submit"
                                class="w-full px-4 py-3 bg-indigo-400 text-white rounded hover:bg-indigo-500">
                                <i class="fa-solid fa-credit-card mr-2"></i>Process Payment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<!-- Excess Payment Modal -->
<div id="excessPaymentModal" class="excess-payment-modal" style="display: none;">
    <div class="excess-payment-content">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fa-solid fa-coins mr-2 text-yellow-500"></i>Excess Payment Detected
            </h2>
            <button type="button" id="closeExcessModal" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <div class="excess-highlight">
            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
            You have paid more than the required amount. Please choose how to handle the excess payment.
        </div>

        <!-- Payment Summary -->
        <div class="payment-summary-enhanced">
            <h3 class="font-bold mb-3"><i class="fa-solid fa-calculator mr-2"></i>Payment Summary</h3>
            <div class="payment-summary-row">
                <span>Required Payment:</span>
                <span id="excessRequiredAmount">₱0.00</span>
            </div>
            <div class="payment-summary-row">
                <span>Amount Paid:</span>
                <span id="excessPaidAmount">₱0.00</span>
            </div>
            <div class="payment-summary-row total">
                <span>Excess Amount:</span>
                <span id="excessDisplayAmount">₱0.00</span>
            </div>
        </div>

        <!-- Excess Options -->
        <div id="excessOptions">
            <h3 class="font-bold text-lg mb-3">How would you like to handle the excess?</h3>

            <!-- Option 1: Treat as Full Initial -->
            <div class="excess-option" data-choice="treat_as_initial">
                <div class="excess-option-title">
                    <i class="fa-solid fa-expand-arrows-alt mr-2 text-blue-500"></i>1️⃣ Treat Payment as Full Initial
                </div>
                <div class="excess-option-description">
                    Apply the entire payment to reduce all demo session balances equally.
                </div>
            </div>

            <!-- Option 2: Allocate to Demos -->
            <div class="excess-option" data-choice="allocate_to_demos">
                <div class="excess-option-title">
                    <i class="fa-solid fa-layer-group mr-2 text-green-500"></i>2️⃣ Allocate Excess to Demos (Sequential)
                </div>
                <div class="excess-option-description">
                    Apply initial payment first, then allocate excess to demo sessions in order (Demo 1, Demo 2, etc.).
                </div>
            </div>

            <!-- Option 3: Return as Change -->
            <div class="excess-option" data-choice="return_as_change">
                <div class="excess-option-title">
                    <i class="fa-solid fa-hand-holding-usd mr-2 text-orange-500"></i>3️⃣ Return Excess as Change
                </div>
                <div class="excess-option-description">
                    Process only the required amount and return the excess to the customer.
                </div>
            </div>
        </div>

        <!-- Demo Allocation Preview -->
        <div id="demoAllocationPreview" class="demo-allocation-preview" style="display: none;">
            <h4 class="font-bold mb-2">
                <i class="fa-solid fa-eye mr-2"></i>Demo Payment Preview
            </h4>
            <div id="demoPreviewContent"></div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 mt-6">
            <button type="button" id="cancelExcessChoice" class="excess-btn-secondary">
                <i class="fa-solid fa-times mr-2"></i>Cancel
            </button>
            <button type="button" id="confirmExcessChoice" class="excess-btn-primary" disabled>
                <i class="fa-solid fa-check mr-2"></i>Apply Choice
            </button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // =============================================================================================
        // GLOBAL VARIABLES
        // =============================================================================================
        let selectedSchedules = [];
        let currentProgram = null;
        let currentPackage = null;
        let paidDemos = <?= json_encode($paid_demos ?? []) ?>;
        let paidPaymentTypes = <?= json_encode($paid_payment_types ?? []) ?>;
        let allPrograms = [];

        // Excess payment handling variables
        let excessPaymentData = {
            isExcess: false,
            requiredAmount: 0,
            paidAmount: 0,
            excessAmount: 0,
            choice: null,
            allocations: []
        };

        // Maintained schedules from first transaction
        let maintainedSchedules = [];
        try {
            const maintainedData = $('#maintainedSchedules').val() || '[]';
            maintainedSchedules = JSON.parse(maintainedData);
            if (!Array.isArray(maintainedSchedules)) {
                maintainedSchedules = [];
            }
        } catch (e) {
            maintainedSchedules = [];
        }

        let existingInitialPayment = <?= $IP ?? 0 ?>;
        let existingReservation = <?= $R ?? 0 ?>;
        let currentBalance = <?= $balance_total ?? 0 ?>;
        let isFirstTransaction = <?= $open ? 'true' : 'false' ?>;

        const existingTransaction = {
            payment_type: "<?= isset($row_transaction['payment_type']) ? $row_transaction['payment_type'] : '' ?>",
            program_id: "<?= isset($row_transaction['program_id']) ? $row_transaction['program_id'] : '' ?>",
            package_name: "<?= isset($row_transaction['package_name']) ? $row_transaction['package_name'] : '' ?>",
            learning_mode: "<?= isset($row_transaction['learning_mode']) ? $row_transaction['learning_mode'] : '' ?>"
        };

        // =============================================================================================
        // ESSENTIAL AJAX AND LOADING FUNCTIONS
        // =============================================================================================
        const ajax = (url, data = {}, success, error = 'Request failed') => {
            $.ajax({
                url,
                type: 'GET',
                data,
                dataType: 'json',
                timeout: 10000,
                success,
                error: (xhr, status, err) => {
                    console.error(`Ajax Error: ${url}`, err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: `${error}: ${err}`,
                        confirmButtonText: 'OK'
                    });
                }
            });
        };

        const populateSelect = (selector, data, valueKey, textKey, placeholder = 'Select option', regularPackage = null) => {
            const $select = $(selector).empty().append(`<option value="">${placeholder}</option>`);

            if (regularPackage) {
                $select.append(`<option value="${regularPackage}">${regularPackage}</option>`);
            }

            if (data?.length) {
                data.forEach(item => {
                    let optionText = item[textKey];
                    $select.append(`<option value="${item[valueKey]}">${optionText}</option>`);
                });
                $select.prop('disabled', false);
            } else {
                $select.prop('disabled', true);
            }
        };

        const loadAllPrograms = (callback = null) => {
            console.log('🔄 Loading all programs...');
            ajax('functions/ajax/get_program.php', {}, data => {
                allPrograms = data;
                console.log("📋 Loaded all programs:", allPrograms.length);
                if (callback) callback();
            }, 'Failed to load programs');
        };

        const loadPrograms = () => {
            loadAllPrograms(() => {
                populateSelect('#programSelect', allPrograms, 'id', 'program_name', 'Select a program');
                if (existingTransaction.program_id) {
                    $('#programSelect').val(existingTransaction.program_id).trigger('change');
                }
            });
        };

        const loadProgramDetails = (id) => {
            console.log('🔄 Loading program details for ID:', id);
            ajax('functions/ajax/get_program_details.php', { program_id: id }, updateProgram, 'Failed to load program details');
        };

        const loadPackages = (id) => {
            console.log('🔄 Loading packages for program ID:', id);
            ajax('functions/ajax/get_packages.php', { program_id: id }, data => {
                $('#packageSelect').html('<option value="">Select a package</option>');
                data.forEach(package => {
                    $('#packageSelect').append(`<option value="${package.package_name}">${package.package_name}</option>`);
                });
                $('#packageSelect').append('<option value="Regular Package">Regular Package</option>');
            }, 'Failed to load packages');
        };

        const loadSchedules = (id) => {
            ajax('functions/ajax/get_schedules.php', { program_id: id }, populateSchedule, 'Failed to load schedules');
        };

        // =============================================================================================
        // EXCESS PAYMENT HANDLING FUNCTIONS
        // =============================================================================================
        function checkForExcessPayment() {
            const paymentType = $('input[name="type_of_payment"]:checked').val();
            const totalPayment = parseFloat($('#totalPayment').val()) || 0;

            if (!paymentType || totalPayment <= 0) {
                return false;
            }

            let requiredAmount = 0;
            const demoType = $('#demoSelect').val();

            switch (paymentType) {
                case 'initial_payment':
                    requiredAmount = parseFloat(currentProgram?.initial_fee || 0);
                    break;
                case 'demo_payment':
                    if (demoType) {
                        requiredAmount = calculateDemoFeeJS();
                    }
                    break;
                case 'full_payment':
                    requiredAmount = currentBalance > 0 ? currentBalance : parseFloat($('#finalTotalHidden').val()) || 0;
                    break;
                case 'reservation':
                    requiredAmount = parseFloat(currentProgram?.reservation_fee || 0);
                    break;
                default:
                    return false;
            }

            const isExcess = totalPayment > requiredAmount && requiredAmount > 0;

            if (isExcess) {
                excessPaymentData = {
                    isExcess: true,
                    requiredAmount: requiredAmount,
                    paidAmount: totalPayment,
                    excessAmount: totalPayment - requiredAmount,
                    paymentType: paymentType,
                    demoType: demoType,
                    choice: null,
                    allocations: []
                };

                console.log(`💰 Excess payment detected: ₱${excessPaymentData.excessAmount.toFixed(2)}`);
                return true;
            }

            return false;
        }

        function showExcessPaymentModal() {
            $('#excessRequiredAmount').text(`₱${excessPaymentData.requiredAmount.toLocaleString()}`);
            $('#excessPaidAmount').text(`₱${excessPaymentData.paidAmount.toLocaleString()}`);
            $('#excessDisplayAmount').text(`₱${excessPaymentData.excessAmount.toLocaleString()}`);

            $('.excess-option').removeClass('selected');
            $('#confirmExcessChoice').prop('disabled', true);
            $('#demoAllocationPreview').hide();

            // Show/hide options based on payment type
            if (excessPaymentData.paymentType === 'demo_payment') {
                updateDemoExcessOptions();
            }

            $('#excessPaymentModal').fadeIn(300);
        }

        function updateDemoExcessOptions() {
            const optionsContainer = $('#excessOptions');
            optionsContainer.html(`
            <h3 class="font-bold text-lg mb-3">How would you like to handle the excess demo payment?</h3>

            <!-- Option 1: Add to Next Demo -->
            <div class="excess-option" data-choice="add_to_next_demo">
                <div class="excess-option-title">
                    <i class="fa-solid fa-arrow-right mr-2 text-blue-500"></i>1️⃣ Add Excess to Next Demo
                </div>
                <div class="excess-option-description">
                    Carry the overpayment to the next unpaid or partially paid demo session.
                </div>
            </div>

            <!-- Option 2: Credit to Account -->
            <div class="excess-option" data-choice="credit_to_account">
                <div class="excess-option-title">
                    <i class="fa-solid fa-piggy-bank mr-2 text-green-500"></i>2️⃣ Credit to Student's Account
                </div>
                <div class="excess-option-description">
                    Save the excess as a balance for future use by this student.
                </div>
            </div>

            <!-- Option 3: Return as Change -->
            <div class="excess-option" data-choice="return_as_change">
                <div class="excess-option-title">
                    <i class="fa-solid fa-hand-holding-usd mr-2 text-orange-500"></i>3️⃣ Return as Change
                </div>
                <div class="excess-option-description">
                    Give the extra amount back to the customer.
                </div>
            </div>
        `);

            attachExcessOptionListeners();
        }

        function attachExcessOptionListeners() {
            $('.excess-option').off('click').on('click', function () {
                $('.excess-option').removeClass('selected');
                $(this).addClass('selected');

                const choice = $(this).data('choice');
                excessPaymentData.choice = choice;
                $('#confirmExcessChoice').prop('disabled', false);

                if (choice === 'allocate_to_demos' || choice === 'treat_as_initial') {
                    showDemoAllocationPreview(choice);
                } else if (choice === 'add_to_next_demo') {
                    showNextDemoPreview();
                } else {
                    $('#demoAllocationPreview').hide();
                }

                console.log(`✅ Excess choice selected: ${choice} | User: Scraper001 | Time: 2025-06-21 06:15:13`);
            });
        }

        function showDemoAllocationPreview(choice) {
            const currentDemoFee = calculateDemoFeeJS();
            let previewContent = '';

            if (choice === 'treat_as_initial') {
                const totalTuition = parseFloat($('#finalTotalHidden').val()) || 0;
                const newBalance = totalTuition - excessPaymentData.paidAmount;
                const newDemoFee = Math.max(0, newBalance / 4);

                previewContent = `
                <div class="excess-breakdown">
                    <div class="excess-breakdown-title">New Demo Fee Calculation:</div>
                    <div class="excess-calculation">
                        <span>Total Tuition:</span>
                        <span>₱${totalTuition.toLocaleString()}</span>
                    </div>
                    <div class="excess-calculation">
                        <span>Full Payment Applied:</span>
                        <span>₱${excessPaymentData.paidAmount.toLocaleString()}</span>
                    </div>
                    <div class="excess-calculation">
                        <span>Remaining Balance:</span>
                        <span>₱${newBalance.toLocaleString()}</span>
                    </div>
                    <div class="excess-calculation">
                        <span>New Demo Fee (each):</span>
                        <span>₱${newDemoFee.toLocaleString()}</span>
                    </div>
                </div>
            `;
            } else if (choice === 'allocate_to_demos') {
                let remainingExcess = excessPaymentData.excessAmount;
                const allocations = [];

                for (let i = 1; i <= 4; i++) {
                    const demoName = `demo${i}`;
                    if (!paidDemos.includes(demoName) && remainingExcess > 0) {
                        const allocation = Math.min(remainingExcess, currentDemoFee);
                        allocations.push({
                            demo: i,
                            amount: allocation,
                            status: allocation >= currentDemoFee ? 'Paid in Full' : `Partially Paid (₱${(currentDemoFee - allocation).toLocaleString()} remaining)`
                        });
                        remainingExcess -= allocation;
                    }
                }

                excessPaymentData.allocations = allocations;

                previewContent = `
                <div class="excess-breakdown">
                    <div class="excess-breakdown-title">Sequential Demo Allocation:</div>
                    <div class="excess-calculation">
                        <span>Initial Payment:</span>
                        <span>₱${excessPaymentData.requiredAmount.toLocaleString()}</span>
                    </div>
                    <div class="excess-calculation">
                        <span>Excess to Allocate:</span>
                        <span>₱${excessPaymentData.excessAmount.toLocaleString()}</span>
                    </div>
                    <div class="excess-calculation">
                        <span>Remaining After Allocation:</span>
                        <span>₱${remainingExcess.toLocaleString()}</span>
                    </div>
                </div>
                <div class="demo-allocation-preview">
                    <h5 class="font-bold mb-2">Demo Allocation Results:</h5>
                    ${allocations.map(alloc => `
                        <div class="demo-item">
                            <span>Demo ${alloc.demo}:</span>
                            <span>₱${alloc.amount.toLocaleString()}</span>
                            <span class="demo-status ${alloc.status.includes('Full') ? 'demo-paid' : 'demo-partial'}">
                                ${alloc.status}
                            </span>
                        </div>
                    `).join('')}
                    ${remainingExcess > 0 ? `
                        <div class="demo-item" style="border-top: 2px solid #28a745; margin-top: 10px; padding-top: 10px;">
                            <span>Remaining Excess:</span>
                            <span>₱${remainingExcess.toLocaleString()}</span>
                            <span class="demo-status demo-unpaid">To be returned</span>
                        </div>
                    ` : ''}
                </div>
            `;
            }

            $('#demoPreviewContent').html(previewContent);
            $('#demoAllocationPreview').show();
        }

        function showNextDemoPreview() {
            let nextDemo = null;
            for (let i = 1; i <= 4; i++) {
                const demoName = `demo${i}`;
                if (!paidDemos.includes(demoName)) {
                    nextDemo = i;
                    break;
                }
            }

            if (nextDemo) {
                const currentDemoFee = calculateDemoFeeJS();
                const allocation = Math.min(excessPaymentData.excessAmount, currentDemoFee);
                const remaining = excessPaymentData.excessAmount - allocation;

                const previewContent = `
                <div class="excess-breakdown">
                    <div class="excess-breakdown-title">Next Demo Allocation:</div>
                    <div class="excess-calculation">
                        <span>Current Demo Payment:</span>
                        <span>₱${excessPaymentData.requiredAmount.toLocaleString()}</span>
                    </div>
                    <div class="excess-calculation">
                        <span>Excess Amount:</span>
                        <span>₱${excessPaymentData.excessAmount.toLocaleString()}</span>
                    </div>
                    <div class="excess-calculation">
                        <span>Applied to Demo ${nextDemo}:</span>
                        <span>₱${allocation.toLocaleString()}</span>
                    </div>
                    ${remaining > 0 ? `
                        <div class="excess-calculation">
                            <span>Remaining to Return:</span>
                            <span>₱${remaining.toLocaleString()}</span>
                        </div>
                    ` : ''}
                </div>
                <div class="demo-allocation-preview">
                    <h5 class="font-bold mb-2">Demo Status After Allocation:</h5>
                    <div class="demo-item">
                        <span>Demo ${nextDemo}:</span>
                        <span>₱${allocation.toLocaleString()} / ₱${currentDemoFee.toLocaleString()}</span>
                        <span class="demo-status ${allocation >= currentDemoFee ? 'demo-paid' : 'demo-partial'}">
                            ${allocation >= currentDemoFee ? 'Paid in Full' : 'Partially Paid'}
                        </span>
                    </div>
                </div>
            `;

                $('#demoPreviewContent').html(previewContent);
                $('#demoAllocationPreview').show();
            } else {
                $('#demoAllocationPreview').hide();
            }
        }

        function getChoiceDisplayName(choice) {
            const names = {
                'treat_as_initial': 'Treat Payment as Full Initial',
                'allocate_to_demos': 'Allocate Excess to Demos',
                'return_as_change': 'Return Excess as Change',
                'add_to_next_demo': 'Add Excess to Next Demo',
                'credit_to_account': 'Credit to Student Account'
            };
            return names[choice] || choice;
        }

        // =============================================================================================
        // CORE POS FUNCTIONS
        // =============================================================================================
        function calculateDemoFeeJS() {
            let demoFeePerDemo = 0;

            if (isFirstTransaction && currentProgram) {
                const finalTotal = parseFloat($('#finalTotalHidden').val()) || parseFloat(currentProgram.total_tuition || 0);
                const initialPayment = parseFloat(currentProgram.initial_fee || 0);
                const reservationPayment = parseFloat(currentProgram.reservation_fee || 0);

                const remainingAfterInitialReservation = finalTotal - initialPayment - reservationPayment;
                demoFeePerDemo = Math.max(0, remainingAfterInitialReservation / 4);
            } else {
                const currentBalanceAmount = currentBalance || 0;
                const paidDemosCount = paidDemos.length;
                const remainingDemos = 4 - paidDemosCount;

                if (remainingDemos > 0 && currentBalanceAmount > 0) {
                    demoFeePerDemo = currentBalanceAmount / remainingDemos;
                }
            }

            return demoFeePerDemo;
        }

        function updateDemoFeesDisplay() {
            if (!currentProgram) return;

            const demoFee = calculateDemoFeeJS();
            const demoFeesHtml = [1, 2, 3, 4]
                .map(i => {
                    const demoName = `demo${i}`;
                    const isPaid = paidDemos.includes(demoName);
                    const status = isPaid ? ' <span class="text-green-600 text-xs"><i class="fa-solid fa-check-circle"></i> Paid</span>' : '';
                    return `<li class="demo-fee-calculated">Demo ${i} Fee: ₱${demoFee.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}${status}</li>`;
                })
                .join('');

            if ($('.demo-fees-display').length > 0) {
                $('.demo-fees-display').html(demoFeesHtml).show();
            }
        }

        const updateProgram = (p) => {
            currentProgram = p;
            showProgramDetailsImmediately(p);
            $('#programDetailsHidden').val(JSON.stringify(p));
            calculateTotal(p, currentPackage);
            console.log(`🎓 Program updated: ${p.program_name} | User: Scraper001 | Time: 2025-06-21 06:15:13`);
        };

        function showProgramDetailsImmediately(program) {
            const selectedLearningMode = $('input[name="learning_mode"]:checked').val() || 'F2F';
            const learningModeIcon = selectedLearningMode === 'Online' ? '<i class="fa-solid fa-laptop"></i>' : '<i class="fa-solid fa-chalkboard-teacher"></i>';

            $('#programTitle').text(`Care Pro - ${program.program_name}`).show();
            $('#programName').html(`
            <strong>Program:</strong> ${program.program_name}
            <span class="inline-block ml-2 px-2 py-1 text-xs font-semibold rounded-full ${selectedLearningMode === 'Online' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                ${learningModeIcon} ${selectedLearningMode}
            </span>
        `).show();

            $('#enrollmentDate').html(`<strong>Enrollment Date:</strong> ${new Date().toLocaleString()}`).show();
            $('#studentInfo').show();

            const chargesHtml = `
            <h1 class="font-semibold text-xl mb-2">Charges</h1>
            <div class="mb-3 p-2 rounded-lg ${selectedLearningMode === 'Online' ? 'bg-blue-50 border border-blue-200' : 'bg-green-50 border border-green-200'}">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Learning Mode:</span>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full ${selectedLearningMode === 'Online' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                        ${learningModeIcon} ${selectedLearningMode}
                    </span>
                </div>
            </div>
            <div class="flex flex-row w-full p-4 gap-4">
                <ul class="flex-1 list-none space-y-1">
                    <li>Assessment Fee: ₱${parseFloat(program.assesment_fee || 0).toLocaleString()}</li>
                    <li>Tuition Fee: ₱${parseFloat(program.tuition_fee || 0).toLocaleString()}</li>
                    <li>Miscellaneous Fee: ₱${parseFloat(program.misc_fee || 0).toLocaleString()}</li>
                    <li>Uniform Fee: ₱${parseFloat(program.uniform_fee || 0).toLocaleString()}</li>
                    <li>ID Fee: ₱${parseFloat(program.id_fee || 0).toLocaleString()}</li>
                    <li>Book Fee: ₱${parseFloat(program.book_fee || 0).toLocaleString()}</li>
                    <li>Kit Fee: ₱${parseFloat(program.kit_fee || 0).toLocaleString()}</li>
                    ${selectedLearningMode === 'Online' && program.system_fee ?
                    `<li class="text-blue-600">System Fee: ₱${parseFloat(program.system_fee).toLocaleString()}</li>` : ''}
                </ul>
                <ul class="flex-1 space-y-1 demo-fees-display" id="demoFeesDisplay">
                    <!-- Demo fees will be calculated and shown here -->
                </ul>
            </div>
            <div class="mt-4 p-3 bg-green-100 rounded-lg border border-green-300">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-lg">Total Amount:</span>
                    <span class="font-bold text-xl text-green-800" id="totalAmountDisplay">₱${parseFloat(program.total_tuition || 0).toLocaleString()}</span>
                </div>
            </div>
        `;

            if ($('#chargesPlaceholder').length > 0) {
                $('#chargesPlaceholder').html(chargesHtml);
            } else {
                $('#chargesContainer').html(chargesHtml);
            }

            $('#chargesContainer, .charges-section').show();
            updateDemoFeesDisplay();
            calculateTotal(program, currentPackage);
        }

        const calculateTotal = (program, package) => {
            let subtotal = parseFloat(program.total_tuition || 0);
            let discount = 0;

            const learningMode = $('input[name="learning_mode"]:checked').val();
            if (learningMode === 'Online' && program.learning_mode !== 'Online') {
                subtotal += parseFloat(program.system_fee || 0);
            }

            const packageName = $('#packageSelect').val();

            if (package && packageName !== 'Regular Package' && package.promo_type && package.promo_type !== 'none') {
                if (package.promo_type === 'percentage') {
                    discount = subtotal * (parseFloat(package.percentage) / 100);
                } else {
                    discount = parseFloat(package.enrollment_fee || 0);
                }

                $('#promoInfo').show().html(`
                <strong>Promo Discount:</strong> ₱${discount.toLocaleString()}<br>
                <small>Automatic ${package.percentage}% discount applied</small>
            `);

                $('#promoAppliedHidden').val(discount);
            } else {
                $('#promoInfo').hide();
                discount = 0;
                $('#promoAppliedHidden').val(0);
            }

            const finalTotal = subtotal - discount;

            $('#totalAmount, #totalAmountDisplay').text(`₱${finalTotal.toLocaleString()}`);
            $('#finalTotalHidden').val(finalTotal);
            $('#subtotalHidden').val(subtotal);

            return finalTotal;
        };

        const populateSchedule = (schedules) => {
            const tbody = $('#scheduleTable tbody').empty();

            if (!schedules?.length) {
                tbody.append('<tr><td colspan="6" class="border px-2 py-1 text-center text-gray-500">No schedules available</td></tr>');
                return;
            }

            schedules.forEach(s => {
                const isMaintained = maintainedSchedules.length > 0 &&
                    maintainedSchedules.some(ms => ms.id == s.id || ms.schedule_id == s.id);

                const isChecked = isMaintained ? 'checked' : '';
                const rowClass = isMaintained ? 'schedule-maintained' : '';

                tbody.append(`
                <tr data-row-id="${s.id}" class="${rowClass}">
                    <td class="border px-2 py-1">
                        <input type="checkbox" class="row-checkbox"
                            onchange="handleRowSelection(this)"
                            ${isChecked} />
                    </td>
                    ${[s.week_description, s.training_date, s.start_time, s.end_time, s.day_of_week]
                        .map(val => `<td class="border px-2 py-1">${val || ''}</td>`).join('')}
                </tr>
            `);
            });

            if (maintainedSchedules.length > 0) {
                selectedSchedules = maintainedSchedules.map(ms => ({
                    id: ms.id || ms.schedule_id,
                    week_description: ms.week_description || ms.weekDescription || '',
                    training_date: ms.training_date || ms.trainingDate || '',
                    start_time: ms.start_time || ms.startTime || '',
                    end_time: ms.end_time || ms.endTime || '',
                    day_of_week: ms.day_of_week || ms.dayOfWeek || ''
                }));

                $('#hiddenSchedule').val(JSON.stringify(selectedSchedules));
            }
        };

        const populateDemoSelect = () => {
            const $demoSelect = $('#demoSelect');
            $demoSelect.empty().append('<option value="">Select Demo</option>');

            const allDemos = [
                { value: 'demo1', label: '1st Practical Demo' },
                { value: 'demo2', label: '2nd Practical Demo' },
                { value: 'demo3', label: '3rd Practical Demo' },
                { value: 'demo4', label: '4th Practical Demo' }
            ];

            const availableDemos = allDemos.filter(demo => !paidDemos.includes(demo.value));

            availableDemos.forEach(demo => {
                $demoSelect.append(`<option value="${demo.value}">${demo.label}</option>`);
            });

            const $demoPaymentOption = $('label[data-payment-type="demo_payment"]');
            const $demoPaymentInput = $('input[name="type_of_payment"][value="demo_payment"]');
            const $demoPaymentStatus = $demoPaymentOption.find('.payment-status');

            if (availableDemos.length === 0) {
                $demoPaymentOption.addClass('payment-hidden');
                $demoPaymentInput.prop('disabled', true);
                $demoPaymentStatus.text('(All demos completed)').show();
            } else {
                $demoPaymentOption.removeClass('payment-hidden');
                $demoPaymentInput.prop('disabled', false);
                $demoPaymentStatus.text(`(${availableDemos.length} remaining)`).show();
            }

            return availableDemos.length > 0;
        };

        const validateScheduleSelection = () => {
            const paymentType = $('input[name="type_of_payment"]:checked').val();
            const learningMode = $('input[name="learning_mode"]:checked').val();

            if (paymentType === 'initial_payment') {
                const scheduleJson = $('#hiddenSchedule').val() || '[]';
                let currentSchedules = [];
                try {
                    currentSchedules = JSON.parse(scheduleJson);
                    if (!Array.isArray(currentSchedules)) {
                        currentSchedules = [];
                    }
                } catch (e) {
                    currentSchedules = [];
                }

                if (currentSchedules.length === 0 && maintainedSchedules.length === 0) {
                    $('#scheduleWarning').show().text('⚠️ Initial payment requires schedule selection');
                    return false;
                }
            }

            if (paymentType === 'reservation') {
                if (learningMode === 'F2F' || learningMode === 'Online') {
                    $('#scheduleWarning').hide();
                    document.querySelectorAll(".row-checkbox").forEach(el => {
                        el.checked = false;
                        el.disabled = true;
                    });
                    const alertDiv = document.getElementById('alert');
                    if (alertDiv) {
                        alertDiv.innerText = 'Reservation mode: Schedules not required for F2F/Online learning';
                        document.getElementById("hiddenSchedule").value = "[]";
                        alertDiv.style.display = 'block';
                    }
                    return true;
                }
            }

            $('#scheduleWarning').hide();
            return true;
        };

        const validatePaymentTypes = () => {
            paidPaymentTypes.forEach(paymentType => {
                if (paymentType !== 'demo_payment') {
                    const $option = $(`label[data-payment-type="${paymentType}"]`);
                    const $input = $(`input[name="type_of_payment"][value="${paymentType}"]`);
                    const $status = $option.find('.payment-status');

                    $option.addClass('payment-hidden');
                    $input.prop('disabled', true);
                    $status.text('(Already paid)').show();
                }
            });

            populateDemoSelect();
        };

        const updatePaymentAmountsEnhanced = () => {
            const paymentType = $('input[name="type_of_payment"]:checked').val();
            const demoType = $('#demoSelect').val();
            let amount = 0;

            if (!currentProgram) return;

            if (paymentType) {
                $('#paymentNote').show().text('Payment processing with excess payment handling enabled');
            } else {
                $('#paymentNote').hide();
            }

            if (paymentType) {
                validateScheduleSelection();
            }

            switch (paymentType) {
                case 'full_payment':
                    if (currentBalance > 0) {
                        amount = currentBalance;
                    } else {
                        amount = parseFloat($('#finalTotalHidden').val()) || parseFloat(currentProgram.total_tuition);
                    }
                    break;
                case 'initial_payment':
                    amount = parseFloat(currentProgram.initial_fee || 0);
                    break;
                case 'demo_payment':
                    if (demoType) {
                        amount = calculateDemoFeeJS();
                    }
                    break;
                case 'reservation':
                    amount = parseFloat(currentProgram.reservation_fee || 0);
                    break;
            }

            if (!$('#totalPayment').val() || $('#totalPayment').val() == '0') {
                $('#totalPayment').val(amount.toFixed(2));
            }

            $('#cashToPay').val($('#totalPayment').val());

            const cash = parseFloat($('#cash').val()) || 0;
            const cashToPay = parseFloat($('#cashToPay').val()) || 0;
            const change = Math.max(0, cash - cashToPay);
            $('#change').val(change.toFixed(2));

            updateDemoFeesDisplay();
        };

        window.updatePaymentData = updatePaymentAmountsEnhanced;

        // =============================================================================================
        // DEBUG FUNCTIONS
        // =============================================================================================
        window.toggleDebug = function () {
            const debugDiv = $('#debugInfo');
            debugDiv.toggle();
            updateDebugInfo();
        };

        const updateDebugInfo = () => {
            const scheduleJson = $('#hiddenSchedule').val() || '[]';
            let selectedCount = 0;
            try {
                const parsed = JSON.parse(scheduleJson);
                selectedCount = Array.isArray(parsed) ? parsed.length : 0;
            } catch (e) {
                selectedCount = 'Invalid JSON';
            }

            $('#debugMaintained').text(`Maintained Schedules: ${maintainedSchedules.length} items`);
            $('#debugSelected').text(`Selected Schedules: ${selectedCount} items`);
            $('#debugValidation').text(`Validation Status: ${validateScheduleSelection() ? 'PASS' : 'FAIL'}`);
            $('#debugExcess').text(`Excess Handler: ${excessPaymentData.isExcess ? 'ACTIVE' : 'Ready'}`);
        };

        // =============================================================================================
        // EVENT HANDLERS
        // =============================================================================================

        // Excess Payment Modal Event Handlers
        attachExcessOptionListeners();

        $('#closeExcessModal, #cancelExcessChoice').click(function () {
            $('#excessPaymentModal').fadeOut(300);
            excessPaymentData = {
                isExcess: false,
                requiredAmount: 0,
                paidAmount: 0,
                excessAmount: 0,
                choice: null,
                allocations: []
            };
        });

        $('#confirmExcessChoice').click(function () {
            if (!excessPaymentData.choice) return;

            $('#excessChoiceField').val(excessPaymentData.choice);
            $('#excessAmountField').val(excessPaymentData.excessAmount);
            $('#excessAllocationsField').val(JSON.stringify(excessPaymentData.allocations || []));

            if (excessPaymentData.choice === 'return_as_change') {
                $('#totalPayment').val(excessPaymentData.requiredAmount.toFixed(2));
                $('#cashToPay').val(excessPaymentData.requiredAmount.toFixed(2));

                const cash = parseFloat($('#cash').val()) || 0;
                const newChange = cash - excessPaymentData.requiredAmount;
                $('#change').val(Math.max(0, newChange).toFixed(2));
            }

            $('#excessPaymentModal').fadeOut(300);

            Swal.fire({
                icon: 'success',
                title: 'Excess Payment Choice Applied',
                html: `
                <div style="text-align: left;">
                    <p><strong>Choice:</strong> ${getChoiceDisplayName(excessPaymentData.choice)}</p>
                    <p><strong>Excess Amount:</strong> ₱${excessPaymentData.excessAmount.toLocaleString()}</p>
                    <p><strong>Processing:</strong> Ready to submit</p>
                    <hr style="margin: 10px 0;">
                    <small>You can now proceed with the payment submission.</small>
                </div>
            `,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });

        // Program selection handler
        $('#programSelect').change(function () {
            const id = $(this).val();
            if (id) {
                loadProgramDetails(id);
                loadPackages(id);
                loadSchedules(id);
            } else {
                currentProgram = null;
                $('#programTitle').text('Care Pro');
                $('#packageSelect').html('<option value="">Select a program first</option>').prop('disabled', true);
            }
        });

        // Package selection handler
        $('#packageSelect').change(function () {
            const packageName = $(this).val();
            if (packageName && currentProgram) {
                if (packageName === 'Regular Package') {
                    currentPackage = null;
                    $('#packageInfo').text(`Package: ${packageName}`);
                    $('#packageDetailsHidden').val('{}');
                    calculateTotal(currentProgram, null);
                } else {
                    ajax('functions/ajax/get_package_details.php', {
                        program_id: currentProgram.id,
                        package_name: packageName
                    }, (packageData) => {
                        currentPackage = packageData;
                        $('#packageInfo').html(`<strong>Package:</strong> ${packageName}`);
                        $('#packageDetailsHidden').val(JSON.stringify(packageData));
                        calculateTotal(currentProgram, packageData);
                    });
                }
            } else {
                currentPackage = null;
                $('#packageInfo').text('');
                $('#packageDetailsHidden').val('');
                if (currentProgram) {
                    calculateTotal(currentProgram, null);
                }
            }
        });

        $('input[name="learning_mode"]').change(function () {
            const mode = $(this).val();
            $('#learningMode').text(`Learning Mode: ${mode}`);
            if (currentProgram) {
                showProgramDetailsImmediately(currentProgram);
                updateDemoFeesDisplay();
            }
        });

        $('input[name="type_of_payment"]').change(function () {
            const paymentType = $(this).val();

            if (paymentType === 'demo_payment') {
                $('#demoSelection').show();
                const hasAvailableDemos = populateDemoSelect();

                if (!hasAvailableDemos) {
                    Swal.fire({
                        icon: 'info',
                        title: 'All Demos Completed',
                        text: 'All practical demos have been paid for this student.',
                        confirmButtonText: 'I understand'
                    });
                    $(this).prop('checked', false);
                    return;
                }
            } else {
                $('#demoSelection').hide();
            }

            if (currentProgram) {
                loadSchedules(currentProgram.id);
            }

            $('#totalPayment').val('');
            updatePaymentAmountsEnhanced();
        });

        $('#demoSelect').change(function () {
            $('#totalPayment').val('');
            updatePaymentAmountsEnhanced();
        });

        // Enhanced payment input handlers
        $('#totalPayment').on('input', function () {
            const amount = parseFloat($(this).val()) || 0;
            $('#cashToPay').val(amount.toFixed(2));

            const cash = parseFloat($('#cash').val()) || 0;
            const change = Math.max(0, cash - amount);
            $('#change').val(change.toFixed(2));

            if (checkForExcessPayment()) {
                $(this).css('border-color', '#ff6b6b');
                $(this).css('background-color', '#fff5f5');
            } else {
                $(this).css('border-color', '');
                $(this).css('background-color', '');
            }
        });

        $('#cash').on('input', function () {
            const cash = parseFloat($(this).val()) || 0;
            const cashToPay = parseFloat($('#cashToPay').val()) || 0;
            const change = Math.max(0, cash - cashToPay);
            $('#change').val(change.toFixed(2));
        });

        $('#cashToPay').on('input', function () {
            const cashToPay = parseFloat($(this).val()) || 0;
            const cash = parseFloat($('#cash').val()) || 0;
            const change = Math.max(0, cash - cashToPay);
            $('#change').val(change.toFixed(2));
        });

        window.handleRowSelection = function (checkbox) {
            const row = checkbox.closest('tr');
            const rowId = row.getAttribute('data-row-id');

            const scheduleData = {
                id: rowId,
                schedule_id: rowId,
                week_description: row.cells[1].textContent.trim(),
                weekDescription: row.cells[1].textContent.trim(),
                training_date: row.cells[2].textContent.trim(),
                trainingDate: row.cells[2].textContent.trim(),
                start_time: row.cells[3].textContent.trim(),
                startTime: row.cells[3].textContent.trim(),
                end_time: row.cells[4].textContent.trim(),
                endTime: row.cells[4].textContent.trim(),
                day_of_week: row.cells[5].textContent.trim(),
                dayOfWeek: row.cells[5].textContent.trim()
            };

            if (checkbox.checked) {
                const existingIndex = selectedSchedules.findIndex(s => s.id === rowId);
                if (existingIndex === -1) {
                    selectedSchedules.push(scheduleData);
                    row.classList.add('bg-blue-50');
                }
            } else {
                selectedSchedules = selectedSchedules.filter(s => s.id !== rowId);
                row.classList.remove('bg-blue-50');
            }

            try {
                const scheduleJson = JSON.stringify(selectedSchedules);
                $('#hiddenSchedule').val(scheduleJson);
            } catch (e) {
                $('#hiddenSchedule').val('[]');
            }

            validateScheduleSelection();
            updateDebugInfo();
        };

        // Enhanced form submission with excess payment handling
        $('#posForm').submit(function (e) {
            e.preventDefault();

            const paymentType = $('input[name="type_of_payment"]:checked').val();
            const totalPayment = parseFloat($('#totalPayment').val()) || 0;
            const learningMode = $('input[name="learning_mode"]:checked').val();

            // Check for excess payment before submission
            if (checkForExcessPayment() && !excessPaymentData.choice) {
                showExcessPaymentModal();
                return;
            }

            const studentId = $('input[name="student_id"]').val() || "";
            const programId = $('#programSelect').val() || "";
            const cash = parseFloat($('#cash').val()) || 0;
            const cashToPay = parseFloat($('#cashToPay').val()) || 0;

            const subtotal = parseFloat($('#subtotalHidden').val()) || 0;
            const finalTotal = parseFloat($('#finalTotalHidden').val()) || 0;
            const promoApplied = parseFloat($('#promoAppliedHidden').val()) || 0;

            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Processing Payment...');

            // Basic validation
            if (!studentId || !programId || !paymentType || parseFloat($('#totalPayment').val()) <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields and enter a valid payment amount.'
                });
                submitBtn.prop('disabled', false).text(originalBtnText);
                return;
            }

            if (cash < parseFloat($('#cashToPay').val())) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Cash',
                    text: `Cash (₱${cash.toLocaleString()}) is less than payment amount (₱${parseFloat($('#cashToPay').val()).toLocaleString()}).`
                });
                submitBtn.prop('disabled', false).text(originalBtnText);
                return;
            }

            // Create form data with excess payment handling
            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('program_id', programId);
            formData.append('learning_mode', learningMode);
            formData.append('type_of_payment', paymentType);
            formData.append('package_id', $('#packageSelect').val() || 'Regular');
            formData.append('transaction_timestamp', '2025-06-21 06:15:13');
            formData.append('processed_by', 'Scraper001');

            // Add excess payment data
            if (excessPaymentData.isExcess && excessPaymentData.choice) {
                formData.append('has_excess_payment', 'true');
                formData.append('excess_choice', excessPaymentData.choice);
                formData.append('excess_amount', excessPaymentData.excessAmount.toString());
                formData.append('excess_allocations', JSON.stringify(excessPaymentData.allocations || []));
                formData.append('original_payment_amount', excessPaymentData.paidAmount.toString());
                formData.append('required_payment_amount', excessPaymentData.requiredAmount.toString());
            } else {
                formData.append('has_excess_payment', 'false');
            }

            if (paymentType === 'demo_payment') {
                formData.append('demo_type', $('#demoSelect').val());
                formData.append('js_calculated_demo_fee', calculateDemoFeeJS().toString());
            }

            const scheduleJson = $('#hiddenSchedule').val() || '[]';
            formData.append('selected_schedules', scheduleJson);
            formData.append('sub_total', subtotal.toString());
            formData.append('final_total', finalTotal.toString());
            formData.append('cash', cash.toString());
            formData.append('total_payment', parseFloat($('#totalPayment').val()).toString());
            formData.append('cash_to_pay', parseFloat($('#cashToPay').val()).toString());
            formData.append('promo_applied', promoApplied.toString());
            formData.append('program_details', $('#programDetailsHidden').val() || '{}');
            formData.append('package_details', $('#packageDetailsHidden').val() || '{}');
            formData.append('paid_demos', $('#paidDemosField').val() || '[]');
            formData.append('paid_payment_types', $('#paidPaymentTypesField').val() || '[]');

            // Process enrollment with excess handling
            $.ajax({
                url: 'functions/ajax/process_enrollment.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 30000,

                success: function (response) {
                    if (response.success) {
                        let successMessage = `Payment of ₱${parseFloat($('#totalPayment').val()).toLocaleString()} for ${paymentType.replace('_', ' ').toUpperCase()} processed successfully!`;

                        // Add excess handling information to success message
                        if (response.excess_processing) {
                            successMessage += `\n\n💰 Excess Payment Handling:`;
                            successMessage += `\n• Choice: ${getChoiceDisplayName(response.excess_processing.choice)}`;
                            successMessage += `\n• Excess Amount: ₱${parseFloat(response.excess_processing.excess_amount || 0).toLocaleString()}`;

                            if (response.excess_processing.allocations && response.excess_processing.allocations.length > 0) {
                                successMessage += `\n• Demo Allocations: ${response.excess_processing.allocations.length} demos affected`;
                            }

                            if (response.excess_processing.final_change_amount > 0) {
                                successMessage += `\n• Final Change: ₱${parseFloat(response.excess_processing.final_change_amount).toLocaleString()}`;
                            }
                        }

                        const changeAmount = parseFloat($('#change').val()) || 0;
                        if (changeAmount > 0) {
                            successMessage += `\nChange Given: ₱${changeAmount.toLocaleString()}`;
                        }

                        successMessage += `\n\nDate: 2025-06-21 06:15:13`;
                        successMessage += `\nProcessed by: Scraper001`;
                        successMessage += `\nTransaction ID: ${response.transaction_id || 'N/A'}`;

                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Successful with Excess Handling!',
                            text: successMessage,
                            confirmButtonText: 'Print Receipt',
                            showCancelButton: true,
                            cancelButtonText: 'Continue',
                            timer: 5000
                        }).then((result) => {
                            if (result.isConfirmed) {
                                printReceiptSection2();
                            } else {
                                window.location.reload();
                            }
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Failed',
                            html: `<div style="text-align: left;">
                            <p><strong>Error Details:</strong></p>
                            <p>${response.message || 'Failed to process payment. Please try again.'}</p>
                            <hr>
                            <div style="font-size: 12px; color: #666;">
                                <strong>Time:</strong> 2025-06-21 06:15:13<br>
                                <strong>User:</strong> Scraper001
                            </div>
                        </div>`,
                            confirmButtonText: 'Try Again'
                        });
                    }

                    submitBtn.prop('disabled', false).text(originalBtnText);
                },

                error: function (xhr, status, error) {
                    let errorMessage = 'Failed to connect to server. Please check your connection and try again.';

                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. The server may be busy. Please try again in a moment.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred. Please contact the administrator.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Access denied. Please refresh the page and log in again.';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: errorMessage,
                        confirmButtonText: 'Retry',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#posForm').submit();
                        }
                    });

                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });

        // =============================================================================================
        // SYSTEM INITIALIZATION
        // =============================================================================================
        const initializeSystem = () => {
            console.log('🎉 Initializing Enhanced POS System with Excess Payment Handling...');

            validatePaymentTypes();

            if (existingTransaction.learning_mode) {
                $(`input[name="learning_mode"][value="${existingTransaction.learning_mode}"]`).prop('checked', true).trigger('change');
            }

            loadPrograms();
            updateDebugInfo();

            console.log('🎉 Enhanced POS System initialized | User: Scraper001 | Time: 2025-06-21 06:15:13');
        };

        initializeSystem();

        console.log('🎉 Enhanced POS System with Excess Payment Handling fully loaded | User: Scraper001 | Time: 2025-06-21 06:15:13');
    });

    // Global print function
    function printReceiptSection2() {
        const printContents = document.getElementById('receiptSection').innerHTML;
        const originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
</script>

<script>
    var enrollmentLocked = <?= $enrollment_locked ? 'true' : 'false' ?>;

    window.addEventListener("load", function () {
        setTimeout(function () {
            var programSelect = document.getElementById("programSelect");
            var packageSelect = document.getElementById("packageSelect");
            var locked_warning = document.getElementById("locked_warning");

            console.log("enrollmentLocked:", enrollmentLocked, "| User: Scraper001 | Time: 2025-06-21 06:15:13");

            if (enrollmentLocked === true) {
                programSelect.disabled = true;
                packageSelect.disabled = true;
                locked_warning.innerHTML = "To avoid accidental errors, the Program and Package selections are locked.";
                console.log("Locked after delay | User: Scraper001 | Time: 2025-06-21 06:15:13");
            } else {
                programSelect.disabled = false;
                packageSelect.disabled = false;
                locked_warning.innerHTML = "";
            }
        }, 500); // Delay to allow population
    });
</script>

<?php include "includes/footer.php"; ?>