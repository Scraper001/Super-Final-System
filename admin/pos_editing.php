<?php

include "../connection/connection.php";
include "includes/header.php";
$conn = con();

// Get enrollment data if editing
$enrollment = null;
$transaction = null;
$student = null;
if (isset($_GET['enrollment_id'])) {
    $enrollment_id = $_GET['enrollment_id'];

    // Get enrollment details
    $enrollment_query = $conn->query("SELECT * FROM student_enrollments WHERE id = '$enrollment_id'");
    $enrollment = $enrollment_query->fetch_assoc();

    if ($enrollment) {
        // Get transaction details
        $transaction_query = $conn->query("SELECT * FROM pos_transactions WHERE id = '{$enrollment['pos_transaction_id']}'");
        $transaction = $transaction_query->fetch_assoc();

        // Get student info if not already loaded
        if (!$student) {
            $student_query = $conn->query("SELECT * FROM student_info_tbl WHERE id = '{$enrollment['student_id']}'");
            $student = $student_query->fetch_assoc();
        }
    }
}

$isEdit = $enrollment && $transaction;
?>

<!-- Add SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                    <h1 class="text-2xl font-bold">Care Pro POS</h1>
                    <p class="italic">A user friendly POS</p>
                    <?php if (isset($has_balance) && $has_balance): ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mt-2">
                            <strong>Alert:</strong> This student currently has an outstanding balance. Please settle the
                            balance to re-enroll the student.
                            <br>
                            <em>(This message appeared after adding the record. No need to panic.)</em>
                        </div>

                    <?php endif; ?>
                </div>

                <!-- Form Section -->
                <form id="posForm">

                    <?php if ($isEdit): ?>
                        <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>" />
                        <input type="hidden" name="pos_transaction_id" value="<?= $transaction['id'] ?>" />
                        <input type="hidden" name="is_edit" value="1" />
                    <?php endif; ?>

                    <div class="flex gap-6 mb-6">
                        <div class="space-y-4 min-w-[300px]">
                            <!-- Student Name -->
                            <div class="flex items-center">
                                <span class="font-semibold w-32">Name:</span>
                                <input type="text" id="studentName" name="student_name"
                                    value="<?= $student ? $student['last_name'] . ', ' . $student['first_name'] . ', ' . $student['middle_name'] : '' ?>"
                                    class="flex-1 border rounded px-3 py-1 outline-none" readonly />
                                <input type="hidden" name="student_id"
                                    value="<?= $student ? $student['id'] : ($_GET['student_id'] ?? '') ?>" />
                            </div>

                            <!-- Learning Mode -->
                            <!-- Learning Mode (pre-select if editing) -->
                            <div class="flex items-center">
                                <span class="font-semibold w-32">Learning Mode:</span>
                                <div class="flex gap-4 ml-2">
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="learning_mode" value="F2F" 
                                            <?= ($isEdit && $enrollment['learning_mode'] == 'F2F') ? 'checked' : '' ?> />F2F
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="learning_mode" value="Online" 
                                            <?= ($isEdit && $enrollment['learning_mode'] == 'Online') ? 'checked' : '' ?> />Online
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
                                <select id="packageSelect" name="package_id"
                                    class="flex-1 border rounded px-3 py-1 outline-none" disabled>
                                    <option value="">Select a program first</option>
                                </select>
                            </div>
                        </div>

                        <!-- Schedule Table -->
                        <div class="flex-1">
                            <h2 class="font-semibold mb-2">Class Schedule</h2>
                            <table id="scheduleTable" class="w-full border-collapse border text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <?php foreach (['Select', 'Week Description', 'Training Date', 'Start Time', 'End Time', 'Day'] as $header): ?>
                                            <th class="border px-2 py-1"><?= $header ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="border px-2 py-1 text-center text-gray-500">Select a
                                            program
                                            to view schedules</td>
                                    </tr>
                                </tbody>
                            </table>
                            <input type="hidden" id="hiddenSchedule" name="selected_schedules" />
                            <div class="mt-4 p-4 bg-gray-100 rounded">
                                <h3 class="font-bold mb-2">Selected Schedules:</h3>
                                <pre id="selectedDisplay"
                                    class="text-xs bg-white p-2 rounded border">No schedules selected</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt and Payment Section -->
                    <div class="flex gap-4">
                        <!-- Receipt -->
                        <div class="flex-1 border-2 bg-white">
                            <div class="bg-gray-200 p-4">
                                <h1 class="font-bold text-2xl" id="programTitle">Care Pro</h1>
                                <span class="italic">Official Receipt</span>
                            </div>

                            <div class="flex">
                                <div class="w-1/2 p-4">
                                    <h1 class="font-semibold text-xl mb-2">Description</h1>
                                    <ul id="descriptionList">
                                        <li id="programName">Select a program to view details</li>
                                        <li id="learningMode"></li>
                                        <li id="packageInfo"></li>
                                        <li id="promoInfo" class="text-green-600 font-semibold" style="display: none;">
                                        </li>
                                    </ul>
                                </div>
                                <div class="w-1/2 p-4">
                                    <h1 class="font-semibold text-xl mb-2">Charges</h1>
                                    <ul id="chargesList">
                                        <li id="assessmentFee"></li>
                                        <li id="tuitionFee"></li>
                                        <li id="miscFee"></li>
                                        <li id="systemFee" style="display: none;"></li>
                                        <li id="otherFees"></li>
                                        <li id="promoDiscount" class="text-green-600" style="display: none;"></li>
                                    </ul>
                                    <div class="mt-4 p-2 bg-gray-100 rounded">
                                        <span class="font-bold">Subtotal: </span>
                                        <span id="subtotalAmount" class="font-bold text-lg">₱0</span>
                                    </div>
                                    <div class="mt-2 p-2 bg-green-100 rounded">
                                        <span class="font-bold">Total: </span>
                                        <span id="totalAmount" class="font-bold text-lg">₱0</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Schedule -->
                            <div class="p-4">
                                <h2 class="font-semibold text-xl mb-2">Payment Schedule</h2>
                                <table class="w-full border-2">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <?php foreach (['Date', 'Description', 'Debit', 'Credit', 'Balance'] as $header): ?>
                                                <th class="border-2 p-2 text-left"><?= $header ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentScheduleBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="w-80 border-2 p-4">
                            <div class="border-2 border-dashed p-4 mb-4">
                                <h1 class="text-xl mb-3">Type of Payment</h1>
                                <?php foreach (['Full Payment', 'Initial Payment', 'Demo Payment', 'Reservation'] as $type): ?>
                                    <label class="flex items-center mb-2">
                                        <input type="radio" name="type_of_payment"
                                            value="<?= strtolower(str_replace(' ', '_', $type)) ?>" class="mr-2" />
                                        <span><?= $type ?></span>
                                    </label>
                                <?php endforeach; ?>

                                <!-- Demo Selection (hidden by default) -->
                                <div id="demoSelection" class="mt-3" style="display: none;">
                                    <label class="block text-sm font-semibold mb-2">Select Demo:</label>
                                    <select id="demoSelect" name="demo_type" class="w-full border rounded px-2 py-1">
                                        <option value="">Select Demo</option>
                                        <option value="demo1">1st Practical Demo</option>
                                        <option value="demo2">2nd Practical Demo</option>
                                        <option value="demo3">3rd Practical Demo</option>
                                        <option value="demo4">4th Practical Demo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="border-2 border-dashed p-4 mb-4 grid grid-cols-2 gap-2">
                                <span>Total Payment</span>
                                <input type="number" id="totalPayment" name="total_payment"
                                    class="border-2 rounded px-2 py-1 outline-none" readonly />

                                <span>Cash to pay</span>
                                <input type="number" id="cashToPay" name="cash_to_pay"
                                    class="border-2 rounded px-2 py-1 outline-none" readonly />

                                <span>Cash</span>
                                <input type="number" id="cash" name="cash"
                                    class="border-2 rounded px-2 py-1 outline-none" />

                                <span>Change</span>
                                <input type="number" id="change" name="change"
                                    class="border-2 rounded px-2 py-1 outline-none" readonly />
                            </div>

                            <!-- Hidden fields for form data -->
                            <input type="hidden" name="program_details" id="programDetailsHidden" />
                            <input type="hidden" name="package_details" id="packageDetailsHidden" />
                            <input type="hidden" name="final_total" id="finalTotalHidden" />
                            <input type="hidden" name="promo_applied" id="promoAppliedHidden" />

                            <button type="submit"
                                class="w-full px-4 py-3 bg-indigo-400 text-white rounded hover:bg-indigo-500"
                                <?= (isset($has_balance) && $has_balance) ? 'disabled' : '' ?>>
                                <?= $isEdit ? 'Update Enrollment' : 'Process Payment' ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
    $(document).ready(function () {
        let selectedSchedules = [];
        let currentProgram = null;
        let currentPackage = null;
        let hasExistingBalance = <?= isset($has_balance) && $has_balance ? 'true' : 'false' ?>;
        let isEdit = <?= $isEdit ? 'true' : 'false' ?>;


        let editData = null;
        <?php if ($isEdit): ?>
                editData = {
                    enrollment: <?= json_encode($enrollment) ?>,
                    transaction: <?= json_encode($transaction) ?>
                };
        <?php endif; ?>
        
        // Initialize edit mode
        if (isEdit && editData) {
            initializeEditMode();
        }

        function initializeEditMode() {
            // Pre-select program
            $('#programSelect').val(editData.enrollment.program_id).trigger('change');
            
            // Pre-select package after program loads
            setTimeout(() => {
                if (editData.transaction.package_name) {
                    $('#packageSelect').val(editData.transaction.package_name).trigger('change');
                }
            }, 1000);
            
            // Pre-select payment type and other fields
            setTimeout(() => {
                $(`input[name="type_of_payment"][value="${editData.transaction.payment_type}"]`).prop('checked', true).trigger('change');
                
                // Set demo type if applicable
                if (editData.transaction.demo_type) {
                    $('#demoSelect').val(editData.transaction.demo_type);
                }
                
                // Set cash amount
                $('#cash').val(editData.transaction.cash_received);
                
                // Schedule selection will be handled automatically in populateSchedule function
                
            }, 1200);
        }
            
        const ajax = (url, data = {}, success, error = 'Request failed') => {
            $.ajax({
                url, 
                type: 'GET', 
                data, 
                dataType: 'json', 
                timeout: 10000,
                success,
                error: (xhr, status, err) => { 
                    console.error(`${error}: ${err}`); 
                    if (typeof success === 'function') {
                        success([]); // Return empty array on error for payment history
                    }
                }
            });
        };

        // POST AJAX helper
        const ajaxPost = (url, data, success, error = 'Request failed') => {
            $.ajax({
                url, type: 'POST', data, dataType: 'json', timeout: 10000,
                success,
                error: () => { console.error(error); }
            });
        };

        // Populate select dropdown
        const populateSelect = (selector, data, valueKey, textKey, placeholder = 'Select option') => {
            const $select = $(selector).empty().append(`<option value="">${placeholder}</option>`);
            if (data?.length) {
                data.forEach(item => $select.append(`<option value="${item[valueKey]}">${item[textKey]}</option>`));
                $select.prop('disabled', false);
            }
        };

        // Calculate total with promo
        const calculateTotal = (program, package) => {
            let subtotal = parseFloat(program.total_tuition || 0);
            let systemFee = 0;
            let discount = 0;

            // Add system fee for online learning
            const learningMode = $('input[name="learning_mode"]:checked').val();
            if (learningMode === 'Online') {
                systemFee = parseFloat(program.system_fee || 0);
                subtotal += systemFee;
                $('#systemFee').show().text(`System Fee: ₱${systemFee.toLocaleString()}`);
            } else {
                $('#systemFee').hide();
            }

            // Apply promo discount
            if (package && package.promo_type) {
                if (package.promo_type === 'percentage') {
                    discount = subtotal * (parseFloat(package.percentage) / 100);
                } else if (package.promo_type === 'fixed') {
                    discount = parseFloat(package.enrollment_fee);
                }

                $('#promoDiscount').show().text(`Promo Discount: -₱${discount.toLocaleString()}`);
                $('#promoInfo').show().text(`Promo: ${package.package_name} (${package.promo_type === 'percentage' ? package.percentage + '%' : '₱' + package.enrollment_fee.toLocaleString()} discount)`);
            } else {
                $('#promoDiscount').hide();
                $('#promoInfo').hide();
            }

            const finalTotal = subtotal - discount;

            $('#subtotalAmount').text(`₱${subtotal.toLocaleString()}`);
            $('#totalAmount').text(`₱${finalTotal.toLocaleString()}`);
            $('#finalTotalHidden').val(finalTotal);

            updatePaymentAmounts();
            return finalTotal;
        };

        const updateProgram = (p) => {
            currentProgram = p;
            $('#programTitle').text(`Care Pro - ${p.program_name}`);
            $('#programName').text(`Program: ${p.program_name}`);

            const fees = ['assesment_fee', 'tuition_fee', 'misc_fee'];
            const labels = ['Assessment Fee', 'Tuition Fee', 'Miscellaneous Fee'];
            fees.forEach((fee, i) => $(`#${fee.replace('_fee', 'Fee').replace('assesment', 'assessment')}`).text(`${labels[i]}: ₱${p[fee].toLocaleString()}`));

            $('#otherFees').html(['ojt', 'uniform', 'id', 'book', 'kit']
                .map(type => `${type.toUpperCase()} Fee: ₱${p[type + '_fee'].toLocaleString()}`).join('<br>'));

            $('#programDetailsHidden').val(JSON.stringify(p));

            // Enhanced Payment schedule with credits
            const payments = [
                { desc: 'Reserve Fee', amt: parseFloat(p.reservation_fee), type: 'reservation' },
                { desc: 'Initial Payment', amt: parseFloat(p.initial_fee), type: 'initial_payment' },
                { desc: '1st Practical Demo', amt: parseFloat(p.demo1_fee_hidden), type: 'demo_payment', demo: 'demo1' },
                { desc: '2nd Practical Demo', amt: parseFloat(p.demo2_fee_hidden), type: 'demo_payment', demo: 'demo2' },
                { desc: '3rd Practical Demo', amt: parseFloat(p.demo3_fee_hidden), type: 'demo_payment', demo: 'demo3' },
                { desc: '4th Practical Demo', amt: parseFloat(p.demo4_fee_hidden), type: 'demo_payment', demo: 'demo4' }
            ];

            // Get user's payment history if editing
            let userPayments = [];
            if (isEdit && editData?.enrollment?.student_id) {
                // Fetch user's payment history
                ajax('functions/ajax/get_user_payments.php', { 
                    student_id: editData.enrollment.student_id,
                    program_id: p.id 
                }, (paymentHistory) => {
                    userPayments = paymentHistory || [];
                    console.log('Loaded payment history:', userPayments);
                    renderPaymentSchedule(payments, userPayments);
                });
            } else {
                // For new enrollments or if no edit data, show empty schedule
                renderPaymentSchedule(payments, []);
            }

            // Fallback: If no payment history loaded after 2 seconds, try to get current transaction data
            if (isEdit) {
                setTimeout(() => {
                    if (userPayments.length === 0 && editData?.transaction) {
                        console.log('No payment history found, using current transaction data');
                        
                        // Create payment entry from current transaction
                        const currentPayment = {
                            payment_type: editData.transaction.payment_type,
                            demo_type: editData.transaction.demo_type,
                            amount_paid: parseFloat(editData.transaction.amount_paid || editData.transaction.cash_received || 0),
                            payment_date: editData.transaction.payment_date || editData.transaction.created_at || new Date().toISOString().split('T')[0],
                            cash_received: parseFloat(editData.transaction.cash_received || 0)
                        };
                        
                        if (currentPayment.amount_paid > 0) {
                            renderPaymentSchedule(payments, [currentPayment]);
                        }
                    }
                }, 2000);
            }

            calculateTotal(p, currentPackage);
        };


        function renderPaymentSchedule(payments, userPayments) {
            let runningBalance = 0;
            let totalDebit = 0;
            let totalCredit = 0;

            const scheduleRows = payments.map(({ desc, amt, type, demo }) => {
            const userPayment = userPayments.find(p => p.payment_type === type && (type !== 'demo_payment' || p.demo_type === demo));
            const creditAmount = userPayment ? parseFloat(userPayment.amount_paid) : 0;
            const paymentDate = userPayment ? formatDate(userPayment.payment_date) : '';

            totalDebit += amt;
            totalCredit += creditAmount;

            // ✅ NEW: Compute per-row balance only
            const balance = amt - creditAmount;

            return `
                <tr class="${creditAmount > 0 ? 'bg-green-50' : ''}">
                    <td class="border-2 p-2 text-center">${paymentDate}</td>
                    <td class="border-2 p-2">${desc}</td>
                    <td class="border-2 p-2 text-right">₱${amt.toLocaleString()}</td>
                    <td class="border-2 p-2 text-right ${creditAmount > 0 ? 'text-green-600 font-semibold' : ''}">
                        ${creditAmount > 0 ? '₱' + creditAmount.toLocaleString() : '-'}
                    </td>
                    <td class="border-2 p-2 text-right ${balance <= 0 ? 'text-green-600 font-semibold' : ''}">
                        ₱${Math.max(0, balance).toLocaleString()}
                    </td>
                </tr>
            `;
        });


            const discrepancy = totalDebit - totalCredit;

            const summaryRow = `
                <tr class="font-bold bg-gray-100">
                    <td colspan="2" class="border-2 p-2 text-right">Totals:</td>
                    <td class="border-2 p-2 text-right">₱${totalDebit.toLocaleString()}</td>
                    <td class="border-2 p-2 text-right">₱${totalCredit.toLocaleString()}</td>
                    <td class="border-2 p-2 text-right ${discrepancy === 0 ? 'text-green-600' : 'text-red-600'}">
                        ${discrepancy === 0 ? 'Balanced' : 'Discrepancy: ₱' + discrepancy.toLocaleString()}
                    </td>
                </tr>
            `;

            $('#paymentScheduleBody').html(scheduleRows.join('') + summaryRow);
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: '2-digit',
                    day: '2-digit', 
                    year: 'numeric'
                });
            } catch (e) {
                return dateString;
            }
        }

                        // Update payment amounts based on payment type
        const updatePaymentAmounts = () => {
            const paymentType = $('input[name="type_of_payment"]:checked').val();
            const demoType = $('#demoSelect').val();
            let amount = 0;

            if (!currentProgram) return;

            switch (paymentType) {
                case 'full_payment':
                    amount = parseFloat($('#finalTotalHidden').val()) || parseFloat(currentProgram.total_tuition);
                    break;
                case 'initial_payment':
                    amount = parseFloat(currentProgram.initial_fee);
                    break;
                case 'demo_payment':
                    if (demoType) {
                        amount = parseFloat(currentProgram[demoType + '_fee_hidden']);
                    }
                    break;
                case 'reservation':
                    amount = parseFloat(currentProgram.reservation_fee);
                    break;
            }

            $('#totalPayment').val(amount);
            $('#cashToPay').val(amount);

            // Calculate change
            const cash = parseFloat($('#cash').val()) || 0;
            const change = cash - amount;
            $('#change').val(change >= 0 ? change : 0);
        };

        // Populate schedule table
        const populateSchedule = (schedules) => {
            const tbody = $('#scheduleTable tbody').empty();
            const isReservation = $('input[name="type_of_payment"]:checked').val() === 'reservation';

            if (!schedules?.length) {
                tbody.append('<tr><td colspan="6" class="border px-2 py-1 text-center text-gray-500">No schedules available</td></tr>');
                return;
            }

            // Get user's selected schedules if editing
            let userSelectedSchedules = [];
            if (isEdit && editData?.enrollment?.selected_schedules) {
                try {
                    userSelectedSchedules = JSON.parse(editData.enrollment.selected_schedules);
                } catch (e) {
                    console.error('Error parsing selected schedules:', e);
                }
            }

            schedules.forEach(s => {
                // Check if this schedule was previously selected by the user
                const isSelected = userSelectedSchedules.some(selected => selected.id == s.id);
                
                tbody.append(`
                    <tr data-row-id="${s.id}" class="${isSelected ? 'bg-blue-50' : ''}">
                        <td class="border px-2 py-1">
                            <input type="checkbox" 
                                class="row-checkbox" 
                                data-row-id="${s.id}"
                                onchange="handleRowSelection(this)" 
                                ${isReservation ? 'disabled' : ''} 
                                ${isSelected ? 'checked' : ''}>
                        </td>
                        ${[s.week_description, s.training_date, s.start_time, s.end_time, s.day_of_week]
                            .map(val => `<td class="border px-2 py-1">${val || ''}</td>`).join('')}
                    </tr>
                `);
                
                // Add to selectedSchedules array if it was previously selected
                if (isSelected) {
                    const scheduleData = {
                        id: s.id,
                        weekDescription: s.week_description,
                        trainingDate: s.training_date,
                        startTime: s.start_time,
                        endTime: s.end_time,
                        dayOfWeek: s.day_of_week
                    };
                    
                    // Avoid duplicates
                    if (!selectedSchedules.some(existing => existing.id == s.id)) {
                        selectedSchedules.push(scheduleData);
                    }
                }
            });
            
            // Update hidden field and display
            $('#hiddenSchedule').val(JSON.stringify(selectedSchedules));
            $('#selectedDisplay').text(selectedSchedules.length ? JSON.stringify(selectedSchedules, null, 2) : 'No schedules selected');
        };

        // Reset functions
        const resetProgram = () => {
            currentProgram = null;
            $('#programTitle').text('Care Pro');
            $('#programName').text('Select a program to view details');
            ['#learningMode', '#assessmentFee', '#tuitionFee', '#miscFee', '#otherFees', '#systemFee', '#packageInfo', '#promoInfo', '#promoDiscount'].forEach(el => $(el).text('').hide());
            $('#totalAmount, #subtotalAmount').text('₱0');
            $('#paymentScheduleBody').empty();
            $('#programDetailsHidden').val('');
        };

        const resetSchedule = () => {
            $('#scheduleTable tbody').html('<tr><td colspan="6" class="border px-2 py-1 text-center text-gray-500">Select a program to view schedules</td></tr>');
            selectedSchedules = [];
            $('#hiddenSchedule').val('');
            $('#selectedDisplay').text('No schedules selected');
        };

        // Load functions
        const loadPrograms = () => ajax('functions/ajax/get_program.php', {}, data => populateSelect('#programSelect', data, 'id', 'program_name', 'Select a program'));
        const loadProgramDetails = (id) => ajax('functions/ajax/get_program_details.php', { program_id: id }, updateProgram);
        const loadPackages = (id) => ajax('functions/ajax/get_packages.php', { program_id: id }, data => {
            populateSelect('#packageSelect', data, 'package_name', 'package_name', 'Select a package');
        });
        const loadSchedules = (id) => ajax('functions/ajax/get_schedules.php', { program_id: id }, populateSchedule);

        // Event handlers
        $('#programSelect').change(function () {
            const id = $(this).val();
            if (id) {
                loadProgramDetails(id);
                loadPackages(id);
                loadSchedules(id);
            } else {
                resetProgram();
                $('#packageSelect').html('<option value="">Select a program first</option>').prop('disabled', true);
                resetSchedule();
            }
        });

        $('#packageSelect').change(function () {
            const packageName = $(this).val();
            if (packageName && currentProgram) {
                // Get package details
                ajax('functions/ajax/get_package_details.php', {
                    program_id: currentProgram.id,
                    package_name: packageName
                }, (packageData) => {
                    currentPackage = packageData;
                    $('#packageInfo').text(`Package: ${packageName}`);
                    $('#packageDetailsHidden').val(JSON.stringify(packageData));
                    calculateTotal(currentProgram, packageData);
                });
            } else {
                currentPackage = null;
                $('#packageInfo').text('');
                $('#packageDetailsHidden').val('');
                if (currentProgram) {
                    calculateTotal(currentProgram, null);
                }
            }
        });

        // Learning mode change
        $('input[name="learning_mode"]').change(function () {
            const mode = $(this).val();
            $('#learningMode').text(`Learning Mode: ${mode}`);
            if (currentProgram) {
                calculateTotal(currentProgram, currentPackage);
            }
        });

        // Payment type change
        $('input[name="type_of_payment"]').change(function () {
            const paymentType = $(this).val();

            if (paymentType === 'demo_payment') {
                $('#demoSelection').show();
            } else {
                $('#demoSelection').hide();
                $('#demoSelect').val('');
            }

            if (paymentType === 'reservation') {
                $('#scheduleTable input[type="checkbox"]').prop('disabled', true).prop('checked', false);
                selectedSchedules = [];
                $('#hiddenSchedule').val('');
                $('#selectedDisplay').text('Schedule selection disabled for reservation');
            } else {
                $('#scheduleTable input[type="checkbox"]').prop('disabled', false);
                $('#selectedDisplay').text(selectedSchedules.length ? JSON.stringify(selectedSchedules, null, 2) : 'No schedules selected');
            }

            updatePaymentAmounts();
        });

        // Demo selection change
        $('#demoSelect').change(updatePaymentAmounts);

        // Cash input change
        $('#cash').on('input', function () {
            const cash = parseFloat($(this).val()) || 0;
            const cashToPay = parseFloat($('#cashToPay').val()) || 0;
            const change = cash - cashToPay;
            $('#change').val(change >= 0 ? change : 0);
        });

        // Global functions for row selection
        window.handleRowSelection = function (checkbox) {
            const row = checkbox.closest('tr');
            const rowId = row.getAttribute('data-row-id');

            if (checkbox.checked) {
                selectedSchedules.push({
                    id: rowId,
                    weekDescription: row.cells[1].textContent.trim(),
                    trainingDate: row.cells[2].textContent.trim(),
                    startTime: row.cells[3].textContent.trim(),
                    endTime: row.cells[4].textContent.trim(),
                    dayOfWeek: row.cells[5].textContent.trim()
                });
                row.classList.add('bg-blue-50');
            } else {
                selectedSchedules = selectedSchedules.filter(s => s.id !== rowId);
                row.classList.remove('bg-blue-50');
            }

            $('#hiddenSchedule').val(JSON.stringify(selectedSchedules));
            $('#selectedDisplay').text(selectedSchedules.length ? JSON.stringify(selectedSchedules, null, 2) : 'No schedules selected');
        };

        // Form submission
        $('#posForm').submit(function (e) {
            e.preventDefault();

                if (!isEdit && hasExistingBalance) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Process',
                        text: 'Student has existing balance in another program. Please settle first.'
                    });
                    return;
            }

            // Validation
            const studentId = $('input[name="student_id"]').val();
            const programId = $('#programSelect').val();
            const learningMode = $('input[name="learning_mode"]:checked').val();
            const paymentType = $('input[name="type_of_payment"]:checked').val();
            const cash = parseFloat($('#cash').val());
            const cashToPay = parseFloat($('#cashToPay').val());

            if (!studentId || !programId || !learningMode || !paymentType) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields.'
                });
                return;
            }

            if (paymentType !== 'reservation' && selectedSchedules.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select at least one schedule.'
                });
                return;
            }

            if (paymentType === 'demo_payment' && !$('#demoSelect').val()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select a demo type.'
                });
                return;
            }

            if (cash < cashToPay) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Cash',
                    text: 'Cash amount is less than the required payment.'
                });
                return;
            }

               
            if (isEdit) {
                // Update existing enrollment
                const formData = $(this).serialize();
                ajaxPost('functions/ajax/update_enrollment.php', formData, (response) => {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Enrollment updated successfully.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to update enrollment.'
                        });
                    }
                });
    } else {


    }
        });

   
        // Initial load
        loadPrograms();
    });
</script>

<?php include "includes/footer.php"; ?>