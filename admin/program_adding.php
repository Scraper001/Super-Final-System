<?php include '../connection/connection.php';
include "includes/header.php";
$conn = con();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST['schedule']) && is_array($_POST['schedule'])) {
        //INSERT TO THE FIRST DATBASE:
        $program_name = $_POST['program_name'];
        $learning_mode = $_POST['learning_mode'];
        $assessment_fee = $_POST['assessment_fee'];
        $tuition_fee = $_POST['tuition_fee'];
        $misc_fee = $_POST['misc_fee'];
        $ojt_fee = $_POST['ojt_fee'];

        if (!isset($_POST['system_fee'])) {
            $system_fee = 0;
        } else {
            $system_fee = $_POST['system_fee'];
        }
        $uniform_fee = $_POST['uniform_fee'];
        $id_fee = $_POST['id_fee'];
        $book_fee = $_POST['book_fee'];
        $kit_fee = $_POST['kit_fee'];
        $total_tuition = $_POST['total_tuition'];
        $demo1_fee_hidden = $_POST['demo1_fee_hidden'];
        $demo2_fee_hidden = $_POST['demo2_fee_hidden'];
        $demo3_fee_hidden = $_POST['demo3_fee_hidden'];
        $demo4_fee_hidden = $_POST['demo4_fee_hidden'];
        $reservation_fee = $_POST['reservation_fee'];
        $initial_fee = $_POST['initial_fee'];

        $insert_program = "INSERT INTO `program`(
            `program_name`,`learning_mode`, `assesment_fee`, `tuition_fee`, 
            `misc_fee`, `ojt_fee`, `system_fee`, `uniform_fee`, `id_fee`, 
            `book_fee`, `kit_fee`, `total_tuition`, `demo1_fee_hidden`, 
            `demo2_fee_hidden`, `demo3_fee_hidden`, `demo4_fee_hidden`, 
            `reservation_fee`, `initial_fee`
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $prepare = $conn->prepare($insert_program);
        $prepare->bind_param(
            "ssiddddddddddddddd",

            $program_name,
            $learning_mode,
            $assessment_fee,
            $tuition_fee,
            $misc_fee,
            $ojt_fee,
            $system_fee,
            $uniform_fee,
            $id_fee,
            $book_fee,
            $kit_fee,
            $total_tuition,
            $demo1_fee_hidden,
            $demo2_fee_hidden,
            $demo3_fee_hidden,
            $demo4_fee_hidden,
            $reservation_fee,
            $initial_fee
        );

        $prepare->execute();

        $program_id = $conn->insert_id;

        if (isset($_POST['schedule']) && is_array($_POST['schedule'])) {
            foreach ($_POST['schedule'] as $index => $details) {
                $weekDescription = !empty($details['week_description']) ? $details['week_description'] : null;
                $trainingDate = !empty($details['training_date']) ? $details['training_date'] : null;
                $start_time = !empty($details['start_time']) ? $details['start_time'] : null;
                $end_time = !empty($details['end_time']) ? $details['end_time'] : null;
                $day_of_week = !empty($details['day_of_week']) ? $details['day_of_week'] : null;
                $day_value = !empty($details['day_value']) ? $details['day_value'] : null;

                if (!$weekDescription || !$trainingDate || !$start_time || !$day_of_week || !$day_value) {
                    continue;
                }

                $sql_schedule = "INSERT INTO `schedules`(`program_id`, `week_description`, `training_date`, `start_time`,`end_time`, `day_of_week`, `day_value`) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_schedule);
                $stmt->bind_param(
                    "issssss",
                    $program_id,
                    $weekDescription,
                    $trainingDate,
                    $start_time,
                    $end_time,
                    $day_of_week,
                    $day_value
                );
                $stmt->execute();
            }
        }

        date_default_timezone_set('Asia/Manila');
        $date_created2 = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Added Program: $program_name', '$date_created2' )");

        if (isset($_POST['promos']) && is_array($_POST['promos'])) {
            foreach ($_POST['promos'] as $index => $promo_details) {
                // Debug: Log the received data
                error_log("Processing promo $index: " . json_encode($promo_details));

                $package_name = !empty($promo_details['package_name']) ? trim($promo_details['package_name']) : null;
                $enrollment_fee = !empty($promo_details['enrollment_fee']) ? floatval($promo_details['enrollment_fee']) : 0;
                $percentage = !empty($promo_details['percentage']) ? floatval($promo_details['percentage']) : 0;
                $promo_type = !empty($promo_details['promo_type']) ? trim($promo_details['promo_type']) : null;
                $selection_type = !empty($promo_details['selection_type']) ? intval($promo_details['selection_type']) : 1;
                $custom_initial_payment = !empty($promo_details['custom_initial_payment']) ? floatval($promo_details['custom_initial_payment']) : null;

                // Validate required fields based on selection type
                if (!$package_name || !$promo_type || $selection_type < 1 || $selection_type > 4) {
                    error_log("Skipping promo $index - Invalid data: Package=$package_name, Type=$promo_type, Selection=$selection_type");
                    continue;
                }

                // Validate based on selection type
                if ($selection_type <= 2) {
                    // Options 1-2: Percentage calculation
                    if ($enrollment_fee <= 0 || $percentage <= 0) {
                        error_log("Skipping promo $index - Invalid percentage data: Fee=$enrollment_fee, Percentage=$percentage");
                        continue;
                    }
                } else {
                    // Options 3-4: Custom initial payment
                    if (!$custom_initial_payment || $custom_initial_payment <= 0) {
                        error_log("Skipping promo $index - Invalid custom payment: Payment=$custom_initial_payment");
                        continue;
                    }
                }

                // Check if this promo already exists
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM `promo` WHERE `program_id` = ? AND `package_name` = ? AND `promo_type` = ?");
                $check_stmt->bind_param("iss", $program_id, $package_name, $promo_type);
                $check_stmt->execute();
                $check_stmt->bind_result($count);
                $check_stmt->fetch();
                $check_stmt->close();

                // Insert only if promo does not exist
                if ($count == 0) {
                    $stmt = $conn->prepare("INSERT INTO `promo` (`program_id`, `package_name`, `enrollment_fee`, `percentage`, `promo_type`, `selection_type`, `custom_initial_payment`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isddsis", $program_id, $package_name, $enrollment_fee, $percentage, $promo_type, $selection_type, $custom_initial_payment);

                    if ($stmt->execute()) {
                        error_log("Successfully inserted promo: $package_name");
                    } else {
                        error_log("Error inserting promo for index $index: " . $stmt->error);
                    }

                    $stmt->close();
                } else {
                    error_log("Duplicate promo skipped for index $index: $package_name");
                }
            }
        }

        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                    title: 'Success!',
                    text: 'Program Successfully Added',
                    icon: 'success',
                    confirmButtonText: 'OK'
                    }).then(() => {
                    window.location.href = 'program_management.php';
                    });
                });
              </script>";
    }
}

?>

<div class="flex h-screen overflow-hidden flex-row">
    <!-- Sidebar -->
    <div id="sidebar"
        class="bg-white shadow-lg transition-all duration-300 w-[20%] flex flex-col z-10 md:flex hidden fixed min-h-screen">
        <!-- Logo -->
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
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="toggleSidebar"
                        class="md:hidden flex text-gray-500 hover:text-primary focus:outline-none mr-4">
                        <i class='bx bx-menu text-2xl'></i>
                    </button>
                </div>

                <div class="flex items-center space-x-4">
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer" id="logout"
                        data-swal-template="#my-template">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
            <!-- Breadcrumb -->
            <h2 class="font-semibold my-2 text-xs">
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> >
                <a href="program_management.php" class="hover:text-green-400 text-gray-400">Program Management</a> >
                Add New Program
            </h2>

            <form method="POST" id="myForm" class="w-full min-h-screen p-4 bg-white rounded-xl shadow-md flex flex-col">
                <div class="w-full mb-6 px-4 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold">Create New Program</h1>
                            <p class="text-gray-600 italic">Add a new training program with schedules and promotions</p>
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <div class="w-full p-2">
                        <div class="mb-6">
                            <label for="program_name" class="block text-sm font-medium text-gray-700 mb-1">Program
                                Name</label>
                            <input type="text" class="border-2 outline-none py-2 px-5 text-xl w-full rounded-lg"
                                id="program_name" name="program_name" required placeholder="Enter program name">
                        </div>

                        <!-- Schedule Section -->
                        <div class="w-full mb-8">
                            <h2 class="text-xl font-semibold mb-4 text-gray-700 border-b pb-2">Class Schedule Management
                            </h2>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Left Panel: Schedule Form -->
                                <div class="bg-white p-4 rounded-lg shadow-sm border">
                                    <h3 class="font-semibold text-gray-700 mb-4">Add New Schedule</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Opening
                                                Date</label>
                                            <input type="date" id="openingClasses"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Ending
                                                Date</label>
                                            <input type="date" id="endingClasses"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Training
                                            Date</label>
                                        <input type="date" id="trainingDate"
                                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <span id="dateError" class="text-red-500 text-xs hidden"></span>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Day of Week</label>
                                        <select id="daySelect"
                                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                            <option value="">Select a day</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Start
                                                Time</label>
                                            <input type="time" id="startTime"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                            <input type="time" id="endTime"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                    </div>

                                    <button type="button" id="addSchedule"
                                        class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors">
                                        <i class="fas fa-plus mr-2"></i> Add Schedule
                                    </button>

                                    <div id="messageContainer" class="mt-2 min-h-[24px] text-center"></div>
                                </div>

                                <!-- Right Panel: Schedule List -->
                                <div class="bg-white p-4 rounded-lg shadow-sm border">
                                    <h3 class="font-semibold text-gray-700 mb-4">Current Schedules</h3>

                                    <div class="border rounded-lg overflow-hidden">
                                        <div class="max-h-[330px] overflow-y-auto">
                                            <table class="w-full">
                                                <thead class="bg-gray-50 sticky top-0">
                                                    <tr>
                                                        <th
                                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Week</th>
                                                        <th
                                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Date</th>
                                                        <th
                                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Time</th>
                                                        <th
                                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Day</th>
                                                        <th
                                                            class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="scheduleTableBody" class="divide-y divide-gray-200">
                                                    <!-- Schedule rows will be added here via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <span>Schedules are used for planning student training sessions</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden container for database storage -->
                        <div id="hiddenInputs" class="hidden"></div>

                        <!-- Fees Section -->
                        <div class="w-full mb-8">
                            <h2 class="text-xl font-semibold mb-4 text-gray-700 border-b pb-2">Fee Structure</h2>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Left Panel: Main Fees -->
                                <div class="bg-white p-4 rounded-lg shadow-sm border">
                                    <h3 class="font-semibold text-gray-700 mb-4">Program Fees</h3>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Learning
                                            Mode</label>
                                        <div class="flex space-x-4">
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="learning_mode" value="F2F"
                                                    class="form-radio h-4 w-4 text-green-600" checked>
                                                <span class="ml-2">Face to Face</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="learning_mode" value="Online"
                                                    class="form-radio h-4 w-4 text-green-600">
                                                <span class="ml-2">Online</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Assessment
                                                Fee</label>
                                            <input type="number" name="assessment_fee" id="assessment_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tuition
                                                Fee</label>
                                            <input type="number" name="tuition_fee" id="tuition_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Miscellaneous
                                                Fee</label>
                                            <input type="number" name="misc_fee" id="misc_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">OJT Fee &
                                                Medical</label>
                                            <input type="number" name="ojt_fee" id="ojt_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">System
                                                Fee</label>
                                            <input type="number" name="system_fee" id="system_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Uniform
                                                Fee</label>
                                            <input type="number" name="uniform_fee" id="uniform_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">ID Fee</label>
                                            <input type="number" name="id_fee" id="id_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Books
                                                Fee</label>
                                            <input type="number" name="book_fee" id="book_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Kit Fee</label>
                                            <input type="number" name="kit_fee" id="kit_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>
                                    </div>

                                    <div class="mt-6 bg-green-50 p-4 rounded-lg">
                                        <h4 class="font-medium text-gray-800 mb-2">Total Tuition:</h4>
                                        <p id="total_amount" class="text-2xl font-bold text-green-600">₱0.00</p>
                                        <input type="hidden" name="total_tuition" id="total_tuition_hidden" value="0">
                                    </div>
                                </div>

                                <!-- Right Panel: Payment Options & Demo Fees -->
                                <div class="bg-white p-4 rounded-lg shadow-sm border">
                                    <h3 class="font-semibold text-gray-700 mb-4">Payment Structure</h3>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Reservation
                                                Fee</label>
                                            <div class="relative mt-1 rounded-md shadow-sm">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">₱</span>
                                                </div>
                                                <input type="number" name="reservation_fee" id="reservation_payment"
                                                    class="w-full border rounded-lg pl-8 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="0.00">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial
                                                Payment</label>
                                            <div class="relative mt-1 rounded-md shadow-sm">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">₱</span>
                                                </div>
                                                <input type="number" name="initial_fee" id="initial_payment"
                                                    class="w-full border rounded-lg pl-8 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Demo Fees Section -->
                                    <div class="mt-6">
                                        <h4 class="font-medium text-gray-800 mb-3">Demo Fee Structure</h4>
                                        <p class="text-sm text-gray-500 mb-3">Demo fees are automatically calculated
                                            based on remaining balance.</p>

                                        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Demo
                                                    1</label>
                                                <input type="number" name="demo1_fee" id="demo1_fee"
                                                    class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                                                    placeholder="0.00" readonly>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Demo
                                                    2</label>
                                                <input type="number" name="demo2_fee" id="demo2_fee"
                                                    class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                                                    placeholder="0.00" readonly>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Demo
                                                    3</label>
                                                <input type="number" name="demo3_fee" id="demo3_fee"
                                                    class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                                                    placeholder="0.00" readonly>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Demo
                                                    4</label>
                                                <input type="number" name="demo4_fee" id="demo4_fee"
                                                    class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                                                    placeholder="0.00" readonly>
                                            </div>
                                        </div>

                                        <!-- Hidden inputs for demo fees -->
                                        <input type="hidden" name="demo1_fee_hidden" id="demo1_fee_hidden" value="0">
                                        <input type="hidden" name="demo2_fee_hidden" id="demo2_fee_hidden" value="0">
                                        <input type="hidden" name="demo3_fee_hidden" id="demo3_fee_hidden" value="0">
                                        <input type="hidden" name="demo4_fee_hidden" id="demo4_fee_hidden" value="0">
                                    </div>

                                    <div class="mt-6 text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <span>Demo fees represent the remaining balance after initial payment, divided
                                            into 4 equal installments</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Promo Section -->
                        <div class="w-full mb-8">
                            <h2 class="text-xl font-semibold mb-4 text-gray-700 border-b pb-2">Promotion Management</h2>

                            <div id="promoMessageContainer" class="mb-4 text-center min-h-[24px]"></div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Left Panel: Promo Form -->
                                <div class="bg-white p-4 rounded-lg shadow-sm border">
                                    <h3 class="font-semibold text-gray-700 mb-4">Add New Promotion</h3>

                                    <!-- Selection Type -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Promotion
                                            Type</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <label
                                                class="flex items-center p-2 border rounded cursor-pointer hover:bg-gray-50"
                                                id="selection1">
                                                <input type="radio" name="promo_selection" value="1" class="mr-2"
                                                    checked>
                                                <span class="text-sm">1 - Auto %</span>
                                            </label>
                                            <label
                                                class="flex items-center p-2 border rounded cursor-pointer hover:bg-gray-50"
                                                id="selection2">
                                                <input type="radio" name="promo_selection" value="2" class="mr-2">
                                                <span class="text-sm">2 - Auto %</span>
                                            </label>
                                            <label
                                                class="flex items-center p-2 border rounded cursor-pointer hover:bg-gray-50"
                                                id="selection3">
                                                <input type="radio" name="promo_selection" value="3" class="mr-2">
                                                <span class="text-sm">3 - Manual</span>
                                            </label>
                                            <label
                                                class="flex items-center p-2 border rounded cursor-pointer hover:bg-gray-50"
                                                id="selection4">
                                                <input type="radio" name="promo_selection" value="4" class="mr-2">
                                                <span class="text-sm">4 - Manual</span>
                                            </label>
                                        </div>

                                        <div class="mt-2 p-2 bg-blue-50 rounded text-xs">
                                            <p><strong>Options 1-2:</strong> Percentage calculation will be applied
                                                automatically</p>
                                            <p><strong>Options 3-4:</strong> You need to calculate and declare your
                                                initial payment amount</p>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Package Name</label>
                                        <input type="text" id="packageInput" placeholder="Enter package name"
                                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>

                                    <!-- Percentage Input (for options 1-2) -->
                                    <div class="mb-4" id="percentageSection">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Percentage
                                            (%)</label>
                                        <input type="number" id="percentageInput"
                                            placeholder="Enter discount percentage" step="0.01" min="0" max="100"
                                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>

                                    <!-- Custom Initial Payment (for options 3-4) -->
                                    <div class="mb-4 hidden" id="customPaymentSection">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Required Initial
                                            Payment (PHP)</label>
                                        <input type="number" id="customInitialPaymentInput"
                                            placeholder="Enter required initial payment" step="0.01" min="0"
                                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Discount
                                            (PHP)</label>
                                        <input type="number" id="enrollmentFeeInput" placeholder="Enter discount amount"
                                            step="0.01" min="0"
                                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Promo
                                            Type/Description</label>
                                        <input type="text" id="promoTypeInput"
                                            placeholder="Enter promo type or description"
                                            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    </div>

                                    <button type="button" id="addPromo"
                                        class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors">
                                        <i class="fas fa-plus mr-2"></i> Add Promotion
                                    </button>
                                </div>

                                <!-- Right Panel: Promo List -->
                                <div class="bg-white p-4 rounded-lg shadow-sm border">
                                    <h3 class="font-semibold text-gray-700 mb-4">Current Promotions</h3>

                                    <div class="border rounded-lg overflow-hidden">
                                        <div class="max-h-[400px] overflow-y-auto">
                                            <table class="w-full">
                                                <thead class="bg-gray-50 sticky top-0">
                                                    <tr>
                                                        <th
                                                            class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Type</th>
                                                        <th
                                                            class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Package</th>
                                                        <th
                                                            class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Discount</th>
                                                        <th
                                                            class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Description</th>
                                                        <th
                                                            class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="promoTableBody" class="divide-y divide-gray-200">
                                                    <!-- Promos will be added here via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <span>Promotions are special offers for this program that affect pricing</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden inputs container for promo data -->
                            <div id="promoHiddenInputs" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                <div class="w-full flex items-center gap-4 border-t pt-6 mt-4">
                    <button type="submit" name="process"
                        class="px-6 py-2.5 rounded-lg bg-green-600 hover:bg-green-700 text-white font-medium transition-colors shadow-md flex-grow">
                        <i class="fas fa-save mr-2"></i> Create Program
                    </button>
                    <a href="program_management.php"
                        class="px-6 py-2.5 rounded-lg bg-gray-500 hover:bg-gray-600 text-white font-medium transition-colors shadow-md flex-grow text-center">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                </div>
            </form>
        </main>

        <?php include "includes/footer.php" ?>
    </div>
</div>

<?php include "form/modal_program.php" ?>

<script>
    // Schedule Management Script
    let scheduleCounter = 0;
    let schedules = [];

    // Day options
    const weekdayOptions = [
        { value: 'Weekday 1', text: 'Weekday 1' },
        { value: 'Weekday 2', text: 'Weekday 2' },
        { value: 'Weekday 3', text: 'Weekday 3' },
        { value: 'Weekday 4', text: 'Weekday 4' },
    ];

    const weekendOptions = [
        { value: 'Weekend 1', text: 'Weekend 1' },
        { value: 'Weekend 2', text: 'Weekend 2' }
    ];

    // Elements
    const openingClasses = document.getElementById('openingClasses');
    const endingClasses = document.getElementById('endingClasses');
    const trainingDate = document.getElementById('trainingDate');
    const daySelect = document.getElementById('daySelect');
    const startTime = document.getElementById('startTime');
    const endTime = document.getElementById('endTime');
    const addButton = document.getElementById('addSchedule');
    const tableBody = document.getElementById('scheduleTableBody');
    const dateError = document.getElementById('dateError');
    const messageContainer = document.getElementById('messageContainer');
    const hiddenInputs = document.getElementById('hiddenInputs');

    // Helper functions
    function showMessage(message, type = 'error') {
        if (messageContainer) {
            const messageClass = type === 'success' ? 'text-green-600' : 'text-red-600';
            messageContainer.innerHTML = `<p class="${messageClass} text-sm font-medium">${message}</p>`;
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
        }
    }

    function isWeekend(date) {
        const day = new Date(date).getDay();
        return day === 0 || day === 6; // Sunday = 0, Saturday = 6
    }

    function getWeekDescription(trainingDate, openingDate) {
        const training = new Date(trainingDate);
        const opening = new Date(openingDate);
        const diffTime = training.getTime() - opening.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const weekNumber = Math.ceil(diffDays / 7);
        return `Week ${weekNumber}`;
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    function formatTime(timeString) {
        if (!timeString) return '';
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function populateDayOptions() {
        if (!trainingDate || !daySelect) return;

        const selectedDate = trainingDate.value;
        if (!selectedDate) {
            daySelect.innerHTML = '<option value="">Select a day</option>';
            return;
        }

        const isWeekendDay = isWeekend(selectedDate);
        const options = isWeekendDay ? weekendOptions : weekdayOptions;

        daySelect.innerHTML = '<option value="">Select a day</option>';
        options.forEach(option => {
            daySelect.innerHTML += `<option value="${option.value}">${option.text}</option>`;
        });
    }

    function validateForm() {
        if (!openingClasses || !endingClasses || !trainingDate || !daySelect || !startTime) {
            return false;
        }

        const opening = openingClasses.value;
        const ending = endingClasses.value;
        const training = trainingDate.value;
        const day = daySelect.value;
        const start = startTime.value;
        const end = endTime ? endTime.value : '';

        // Reset error message
        if (dateError) {
            dateError.classList.add('hidden');
            dateError.textContent = '';
        }

        // Check if all required fields are filled
        if (!opening || !ending || !training || !day || !start) {
            showMessage('Please fill in all required fields.');
            return false;
        }

        // Validate training date is within class period
        if (training < opening || training > ending) {
            if (dateError) {
                dateError.textContent = 'Training date must be between class opening and ending dates.';
                dateError.classList.remove('hidden');
            }
            showMessage('Training date is outside the class period.');
            return false;
        }

        // Validate start time is before end time (if end time is provided)
        if (end && start >= end) {
            showMessage('Start time must be before end time.');
            return false;
        }

        return true;
    }

    function createTextbox(name, value) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = name;
        input.value = value;
        input.readOnly = true;
        input.style.display = 'none';
        return input;
    }

    function addScheduleRowToTable(schedule) {
        if (!tableBody) return;

        const isWeekendDay = isWeekend(schedule.trainingDate);
        const rowBgClass = isWeekendDay ? 'bg-yellow-50' : 'bg-blue-50';

        const row = document.createElement('tr');
        row.id = `schedule_row_${schedule.id}`;
        row.className = `${rowBgClass} hover:bg-opacity-80 transition-colors`;

        const endTimeDisplay = schedule.endTime ? formatTime(schedule.endTime) : '-';

        row.innerHTML = `
            <td class="px-4 py-2">${schedule.weekDescription}</td>
            <td class="px-4 py-2">${formatDate(schedule.trainingDate)}</td>
            <td class="px-4 py-2">${formatTime(schedule.startTime)} - ${endTimeDisplay}</td>
            <td class="px-4 py-2"><span class="px-2 py-1 text-xs rounded-full ${isWeekendDay ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">${schedule.dayOfWeek}</span></td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="removeSchedule(${schedule.id})" class="bg-red-500 hover:bg-red-600 text-white text-xs rounded px-2 py-1 transition-colors">
                    <i class="fas fa-trash-alt mr-1"></i> Remove
                </button>
            </td>
        `;

        tableBody.appendChild(row);

        // Create hidden inputs
        if (hiddenInputs) {
            const hiddenContainer = document.createElement('div');
            hiddenContainer.id = `hidden_container_${schedule.id}`;
            hiddenContainer.appendChild(createTextbox(`schedule[${schedule.id}][week_description]`, schedule.weekDescription));
            hiddenContainer.appendChild(createTextbox(`schedule[${schedule.id}][training_date]`, schedule.trainingDate));
            hiddenContainer.appendChild(createTextbox(`schedule[${schedule.id}][start_time]`, schedule.startTime));
            hiddenContainer.appendChild(createTextbox(`schedule[${schedule.id}][end_time]`, schedule.endTime || ''));
            hiddenContainer.appendChild(createTextbox(`schedule[${schedule.id}][day_of_week]`, schedule.dayOfWeek));
            hiddenContainer.appendChild(createTextbox(`schedule[${schedule.id}][day_value]`, schedule.dayValue));
            hiddenInputs.appendChild(hiddenContainer);
        }
    }

    function addScheduleToTable(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!validateForm()) return;

        scheduleCounter++;
        const weekDesc = getWeekDescription(trainingDate.value, openingClasses.value);
        const selectedDayText = daySelect.options[daySelect.selectedIndex].text;

        const schedule = {
            id: scheduleCounter,
            weekDescription: weekDesc,
            trainingDate: trainingDate.value,
            startTime: startTime.value,
            endTime: endTime ? endTime.value : '',
            dayOfWeek: selectedDayText,
            dayValue: daySelect.value
        };

        schedules.push(schedule);
        addScheduleRowToTable(schedule);

        // Clear form (except class dates)
        trainingDate.value = '';
        daySelect.innerHTML = '<option value="">Select a day</option>';
        startTime.value = '';
        if (endTime) endTime.value = '';

        showMessage('Schedule added successfully!', 'success');
    }

    function removeSchedule(id) {
        // Remove from schedules array
        schedules = schedules.filter(schedule => schedule.id !== id);

        // Remove table row
        const row = document.getElementById(`schedule_row_${id}`);
        if (row) row.remove();

        // Remove hidden inputs
        const hiddenContainer = document.getElementById(`hidden_container_${id}`);
        if (hiddenContainer) hiddenContainer.remove();

        showMessage('Schedule removed successfully!', 'success');
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function () {
        if (trainingDate) {
            trainingDate.addEventListener('change', populateDayOptions);
        }

        if (addButton) {
            addButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                addScheduleToTable(event);
            });
        }
    });

    // Make functions available globally
    window.removeSchedule = removeSchedule;
</script>

<script>
    function handleLearningModeChange() {
        const systemFeeInput = document.getElementById('system_fee');
        const learningModeRadios = document.querySelectorAll('input[name="learning_mode"]');

        if (!systemFeeInput) {
            console.warn('System fee input not found');
            return;
        }

        learningModeRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                if (this.checked) {
                    if (this.value === 'F2F') {
                        // Disable system fee for F2F
                        systemFeeInput.disabled = true;
                        systemFeeInput.value = '0.00'; // Set to 0 when disabled
                        systemFeeInput.style.backgroundColor = '#e9ecef'; // Visual indication
                        systemFeeInput.style.cursor = 'not-allowed';
                    } else if (this.value === 'Online') {
                        // Enable system fee for Online
                        systemFeeInput.disabled = false;
                        systemFeeInput.style.backgroundColor = ''; // Reset background
                        systemFeeInput.style.cursor = '';
                    }

                    // Recalculate fees after changing system fee
                    if (typeof calculateFees === 'function') {
                        calculateFees();
                    }

                    // Recalculate promo if function exists
                    if (typeof calculateUponEnrollment === 'function') {
                        calculateUponEnrollment();
                    }
                }
            });
        });
    }

    // Initialize the learning mode handler when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        handleLearningModeChange();

        // Set initial state based on pre-selected radio button
        const selectedMode = document.querySelector('input[name="learning_mode"]:checked');
        if (selectedMode) {
            const systemFeeInput = document.getElementById('system_fee');
            if (systemFeeInput) {
                if (selectedMode.value === 'F2F') {
                    systemFeeInput.disabled = true;
                    systemFeeInput.value = '0';
                    systemFeeInput.style.backgroundColor = '#e9ecef';
                    systemFeeInput.style.cursor = 'not-allowed';
                } else {
                    systemFeeInput.disabled = false;
                    systemFeeInput.style.backgroundColor = '';
                    systemFeeInput.style.cursor = '';
                }
            }
        }
    });
    function calculateFees() {
        // Get all fee input values
        const assessmentFee = parseFloat(document.getElementById('assessment_fee').value) || 0;
        const tuitionFee = parseFloat(document.getElementById('tuition_fee').value) || 0;
        const miscFee = parseFloat(document.getElementById('misc_fee').value) || 0;
        const ojtFee = parseFloat(document.getElementById('ojt_fee').value) || 0;
        const systemFee = parseFloat(document.getElementById('system_fee').value) || 0;
        const uniformFee = parseFloat(document.getElementById('uniform_fee').value) || 0;
        const idFee = parseFloat(document.getElementById('id_fee').value) || 0;
        const bookFee = parseFloat(document.getElementById('book_fee').value) || 0;
        const kitFee = parseFloat(document.getElementById('kit_fee').value) || 0;

        // Get initial payment
        const initialPayment = parseFloat(document.getElementById('initial_payment').value) || 0;

        // Check for different possible reservation fee field IDs
        let reservationFee = 0;
        const reservationFeeElement = document.getElementById('reservation_fee') ||
            document.getElementById('reservation_payment');
        if (reservationFeeElement) {
            reservationFee = parseFloat(reservationFeeElement.value) || 0;
        }

        // Calculate total tuition
        const totalTuition = assessmentFee + tuitionFee + miscFee + ojtFee + systemFee +
            uniformFee + idFee + bookFee + kitFee;

        // Calculate remaining balance after BOTH initial payment AND reservation fee
        const remainingBalance = totalTuition;

        // Calculate demo fee as equal installments of the remaining balance
        const demoFee = remainingBalance / 4;

        console.log("Calculation details:");
        console.log("Total Tuition:", totalTuition);
        console.log("Initial Payment:", initialPayment);
        console.log("Reservation Fee:", reservationFee);
        console.log("Remaining Balance:", remainingBalance);
        console.log("Demo Fee (each):", demoFee);

        // Update demo fee fields
        document.getElementById('demo1_fee').value = demoFee.toFixed(2);
        document.getElementById('demo2_fee').value = demoFee.toFixed(2);
        document.getElementById('demo3_fee').value = demoFee.toFixed(2);
        document.getElementById('demo4_fee').value = demoFee.toFixed(2);

        // Update total amount display
        document.getElementById('total_amount').textContent = '₱' + totalTuition.toFixed(2);

        // Update hidden inputs for form submission
        document.getElementById('total_tuition_hidden').value = totalTuition.toFixed(2);
        document.getElementById('demo1_fee_hidden').value = demoFee.toFixed(2);
        document.getElementById('demo2_fee_hidden').value = demoFee.toFixed(2);
        document.getElementById('demo3_fee_hidden').value = demoFee.toFixed(2);
        document.getElementById('demo4_fee_hidden').value = demoFee.toFixed(2);
    }

    // Add event listeners to make sure all inputs trigger recalculation
    document.addEventListener('DOMContentLoaded', function () {
        calculateFees(); // Initial calculation

        // Add listeners to all fee input fields
        const feeInputIds = [
            'assessment_fee', 'tuition_fee', 'misc_fee', 'ojt_fee', 'system_fee',
            'uniform_fee', 'id_fee', 'book_fee', 'kit_fee'
        ];

        feeInputIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', calculateFees);
            }
        });

        // Add listeners for payment fields
        const initialPaymentEl = document.getElementById('initial_payment');
        if (initialPaymentEl) {
            initialPaymentEl.addEventListener('input', calculateFees);
        }

        // Check for different possible reservation fee field IDs
        const reservationFeeEl = document.getElementById('reservation_fee') ||
            document.getElementById('reservation_payment');
        if (reservationFeeEl) {
            reservationFeeEl.addEventListener('input', calculateFees);
        }

        // Set up learning mode change handlers
        const learningModeRadios = document.querySelectorAll('input[name="learning_mode"]');
        learningModeRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                const systemFeeInput = document.getElementById('system_fee');
                if (systemFeeInput) {
                    if (this.checked && this.value === 'F2F') {
                        systemFeeInput.disabled = true;
                        systemFeeInput.value = '0';
                        systemFeeInput.style.backgroundColor = '#e9ecef';
                    } else if (this.checked && this.value === 'Online') {
                        systemFeeInput.disabled = false;
                        systemFeeInput.style.backgroundColor = '';
                    }
                    calculateFees();
                }
            });
        });
    });
    // Initialize calculation on page load with error handling
    document.addEventListener('DOMContentLoaded', function () {
        try {
            calculateFees();

            // Add event listeners to all fee inputs to recalculate when any value changes
            const feeInputs = [
                'assessment_fee', 'tuition_fee', 'misc_fee', 'ojt_fee', 'system_fee',
                'uniform_fee', 'id_fee', 'book_fee', 'kit_fee', 'initial_payment', 'reservation_fee'
            ];

            feeInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('input', calculateFees);
                }
            });

            // Handle learning mode changes
            const learningModeRadios = document.querySelectorAll('input[name="learning_mode"]');
            if (learningModeRadios.length > 0) {
                learningModeRadios.forEach(radio => {
                    radio.addEventListener('change', function () {
                        const systemFeeInput = document.getElementById('system_fee');
                        if (systemFeeInput) {
                            if (this.checked && this.value === 'F2F') {
                                systemFeeInput.disabled = true;
                                systemFeeInput.value = '0';
                                systemFeeInput.style.backgroundColor = '#e9ecef';
                            } else if (this.checked && this.value === 'Online') {
                                systemFeeInput.disabled = false;
                                systemFeeInput.style.backgroundColor = '';
                            }
                            calculateFees();
                        }
                    });
                });

                // Set initial state for system fee based on learning mode
                const selectedMode = document.querySelector('input[name="learning_mode"]:checked');
                if (selectedMode && selectedMode.value === 'F2F') {
                    const systemFeeInput = document.getElementById('system_fee');
                    if (systemFeeInput) {
                        systemFeeInput.disabled = true;
                        systemFeeInput.value = '0';
                        systemFeeInput.style.backgroundColor = '#e9ecef';
                    }
                }
            }
        } catch (error) {
            console.error('Error initializing fee calculation:', error);
        }
    });

    // Initialize calculation on page load
    document.addEventListener('DOMContentLoaded', function () {
        calculateFees();

        // Add event listeners to all fee inputs to recalculate when any value changes
        const feeInputs = [
            'assessment_fee', 'tuition_fee', 'misc_fee', 'ojt_fee', 'system_fee',
            'uniform_fee', 'id_fee', 'book_fee', 'kit_fee', 'initial_payment', 'reservation_fee'
        ];

        feeInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', calculateFees);
            }
        });

        // Handle learning mode changes
        const learningModeRadios = document.querySelectorAll('input[name="learning_mode"]');
        learningModeRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                if (this.checked && this.value === 'F2F') {
                    const systemFeeInput = document.getElementById('system_fee');
                    if (systemFeeInput) {
                        systemFeeInput.disabled = true;
                        systemFeeInput.value = '0';
                        systemFeeInput.style.backgroundColor = '#e9ecef';
                    }
                } else if (this.checked && this.value === 'Online') {
                    const systemFeeInput = document.getElementById('system_fee');
                    if (systemFeeInput) {
                        systemFeeInput.disabled = false;
                        systemFeeInput.style.backgroundColor = '';
                    }
                }
                calculateFees();
            });
        });

        // Set initial state for system fee based on learning mode
        const selectedMode = document.querySelector('input[name="learning_mode"]:checked');
        if (selectedMode && selectedMode.value === 'F2F') {
            const systemFeeInput = document.getElementById('system_fee');
            if (systemFeeInput) {
                systemFeeInput.disabled = true;
                systemFeeInput.value = '0';
                systemFeeInput.style.backgroundColor = '#e9ecef';
            }
        }
    });
    // Initialize calculation on page load
    document.addEventListener('DOMContentLoaded', function () {
        calculateFees();

        // Add event listeners to all fee inputs to recalculate when any value changes
        const feeInputs = [
            'assessment_fee', 'tuition_fee', 'misc_fee', 'ojt_fee', 'system_fee',
            'uniform_fee', 'id_fee', 'book_fee', 'kit_fee', 'initial_payment', 'reservation_fee'
        ];

        feeInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', calculateFees);
            }
        });

        // Handle learning mode changes
        const learningModeRadios = document.querySelectorAll('input[name="learning_mode"]');
        learningModeRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                if (this.checked && this.value === 'F2F') {
                    const systemFeeInput = document.getElementById('system_fee');
                    if (systemFeeInput) {
                        systemFeeInput.disabled = true;
                        systemFeeInput.value = '0';
                        systemFeeInput.style.backgroundColor = '#e9ecef';
                    }
                } else if (this.checked && this.value === 'Online') {
                    const systemFeeInput = document.getElementById('system_fee');
                    if (systemFeeInput) {
                        systemFeeInput.disabled = false;
                        systemFeeInput.style.backgroundColor = '';
                    }
                }
                calculateFees();
            });
        });

        // Set initial state for system fee based on learning mode
        const selectedMode = document.querySelector('input[name="learning_mode"]:checked');
        if (selectedMode && selectedMode.value === 'F2F') {
            const systemFeeInput = document.getElementById('system_fee');
            if (systemFeeInput) {
                systemFeeInput.disabled = true;
                systemFeeInput.value = '0';
                systemFeeInput.style.backgroundColor = '#e9ecef';
            }
        }
    });

</script>

<script>
    // Enhanced Promo Management Script with Selection Types 1-4
    let promoCounter = 0;
    let promos = [];

    // Elements
    const packageInput = document.getElementById('packageInput');
    const enrollmentFeeInput = document.getElementById('enrollmentFeeInput');
    const percentageInput = document.getElementById('percentageInput');
    const customInitialPaymentInput = document.getElementById('customInitialPaymentInput');
    const promoTypeInput = document.getElementById('promoTypeInput');
    const addPromoButton = document.getElementById('addPromo');
    const promoTableBody = document.getElementById('promoTableBody');
    const promoMessageContainer = document.getElementById('promoMessageContainer');
    const promoHiddenInputs = document.getElementById('promoHiddenInputs');
    const promoSelectionRadios = document.querySelectorAll('input[name="promo_selection"]');
    const percentageSection = document.getElementById('percentageSection');
    const customPaymentSection = document.getElementById('customPaymentSection');

    // Helper: Show promo messages
    function showPromoMessage(message, type = 'error') {
        if (promoMessageContainer) {
            const messageClass = type === 'success' ? 'text-green-600' : 'text-red-600';
            promoMessageContainer.innerHTML = `<p class="${messageClass} text-sm font-medium">${message}</p>`;
            setTimeout(() => {
                promoMessageContainer.innerHTML = '';
            }, 5000);
        }
    }

    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }

    // Handle selection type changes
    function handleSelectionTypeChange() {
        const selectedType = document.querySelector('input[name="promo_selection"]:checked').value;

        if (selectedType <= 2) {
            // Options 1-2: Show percentage, hide custom payment
            percentageSection.classList.remove('hidden');
            customPaymentSection.classList.add('hidden');
            customInitialPaymentInput.value = '';
        } else {
            // Options 3-4: Hide percentage, show custom payment
            percentageSection.classList.add('hidden');
            customPaymentSection.classList.remove('hidden');
            percentageInput.value = '';
            enrollmentFeeInput.value = '';
        }
    }

    // Get total tuition fee from inputs or hidden field
    function getTotalTuitionFee() {
        const totalTuitionHidden = document.getElementById('total_tuition_hidden');
        if (totalTuitionHidden) {
            return parseFloat(totalTuitionHidden.value) || 0;
        }

        // Fallback: sum individual fees
        const assessmentFee = parseFloat(document.getElementById('assessment_fee')?.value) || 0;
        const tuitionFee = parseFloat(document.getElementById('tuition_fee')?.value) || 0;
        const miscFee = parseFloat(document.getElementById('misc_fee')?.value) || 0;
        const ojtFee = parseFloat(document.getElementById('ojt_fee')?.value) || 0;
        const systemFee = parseFloat(document.getElementById('system_fee')?.value) || 0;
        const uniformFee = parseFloat(document.getElementById('uniform_fee')?.value) || 0;
        const idFee = parseFloat(document.getElementById('id_fee')?.value) || 0;
        const bookFee = parseFloat(document.getElementById('book_fee')?.value) || 0;
        const kitFee = parseFloat(document.getElementById('kit_fee')?.value) || 0;

        return assessmentFee + tuitionFee + miscFee + ojtFee + systemFee + uniformFee + idFee + bookFee + kitFee;
    }

    // Calculate discount and Upon Enrollment based on percentage input (for options 1-2)
    function calculateUponEnrollment() {
        const selectedType = document.querySelector('input[name="promo_selection"]:checked').value;

        if (selectedType <= 2) {
            const percentage = parseFloat(percentageInput.value) || 0;
            const totalTuition = getTotalTuitionFee();

            if (percentage > 0 && totalTuition > 0) {
                // Calculate discount amount (e.g., 10% of 1000 = 100)
                const discountAmount = (totalTuition * percentage) / 100;

                // Calculate amount due upon enrollment (total tuition - discount)
                const uponEnrollment = totalTuition - discountAmount;

                // Update enrollment fee input (auto-fill with discount amount)
                enrollmentFeeInput.value = discountAmount.toFixed(2);

                return { discountAmount, uponEnrollment };
            } else {
                // Reset if invalid
                enrollmentFeeInput.value = '';
                return { discountAmount: 0, uponEnrollment: 0 };
            }
        }

        return { discountAmount: 0, uponEnrollment: 0 };
    }

    function validatePromoForm() {
        if (!packageInput || !promoTypeInput) {
            return false;
        }

        const packageName = packageInput.value.trim();
        const promoType = promoTypeInput.value.trim();
        const selectedType = parseInt(document.querySelector('input[name="promo_selection"]:checked').value);
        const totalTuition = getTotalTuitionFee();

        // Check if all fields are filled
        if (!packageName || !promoType) {
            showPromoMessage('Please fill in Package Name and Promo Type.');
            return false;
        }

        if (totalTuition <= 0) {
            showPromoMessage('Please ensure the total tuition fee is calculated first.');
            return false;
        }

        if (selectedType <= 2) {
            // Options 1-2: Percentage validation
            const percentage = parseFloat(percentageInput.value);
            const enrollmentFee = parseFloat(enrollmentFeeInput.value);

            if (isNaN(percentage) || percentage < 0 || percentage > 100) {
                showPromoMessage('Please enter a valid percentage between 0 and 100.');
                return false;
            }
        } else {
            // Options 3-4: Custom payment validation
            const customPayment = parseFloat(customInitialPaymentInput.value);

            if (isNaN(customPayment) || customPayment <= 0) {
                showPromoMessage('Please enter a valid required initial payment amount.');
                return false;
            }
        }

        // Check for duplicate package names
        if (promos.some(promo => promo.packageName.toLowerCase() === packageName.toLowerCase())) {
            showPromoMessage('A promo with this package name already exists.');
            return false;
        }

        return true;
    }

    function createPromoTextbox(name, value) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = name;
        input.value = value;
        input.readOnly = true;
        input.style.display = 'none';
        return input;
    }

    function addPromoRowToTable(promo) {
        if (!promoTableBody) return;

        const row = document.createElement('tr');
        row.id = `promo_row_${promo.id}`;
        row.className = 'bg-blue-50 hover:bg-blue-100 transition-colors';

        // Determine what to display based on selection type
        const selectionDisplay = `Option ${promo.selectionType}`;

        let discountDisplay, packageDisplay;

        if (promo.selectionType <= 2) {
            // Options 1-2: Show percentage discount
            discountDisplay = promo.percentage > 0
                ? `<div><span class="font-semibold">${promo.percentage}% OFF</span></div>
                   <div class="text-xs text-gray-600">${formatCurrency(promo.enrollmentFee)}</div>`
                : `                <span class="font-semibold">${formatCurrency(promo.enrollmentFee)}</span>`;
        } else {
            // Options 3-4: Show custom initial payment
            discountDisplay = `<span class="font-semibold">${formatCurrency(promo.customInitialPayment || 0)}</span>
                               <div class="text-xs text-green-600">Required Payment</div>`;
        }

        // Add required payment note for selection types 3-4
        const packageNoteDisplay = promo.selectionType > 2 && promo.customInitialPayment
            ? `<div class="text-xs text-green-600 mt-1">Manual: ${formatCurrency(promo.customInitialPayment)}</div>`
            : '';

        packageDisplay = `<div>${promo.packageName}</div>${packageNoteDisplay}`;

        row.innerHTML = `
            <td class="px-3 py-3 align-middle">
                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                    ${promo.selectionType <= 2 ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                    ${selectionDisplay}
                </span>
            </td>
            <td class="px-3 py-3 align-middle">${packageDisplay}</td>
            <td class="px-3 py-3 align-middle">${discountDisplay}</td>
            <td class="px-3 py-3 align-middle">${promo.promoType}</td>
            <td class="px-3 py-3 text-center">
                <button type="button" onclick="removePromo(${promo.id})" 
                    class="bg-red-500 hover:bg-red-600 text-white text-xs rounded px-2 py-1 transition-colors">
                    <i class="fas fa-trash-alt mr-1"></i> Remove
                </button>
            </td>
        `;

        promoTableBody.appendChild(row);

        // Create hidden inputs
        if (promoHiddenInputs) {
            const hiddenContainer = document.createElement('div');
            hiddenContainer.id = `promo_hidden_container_${promo.id}`;
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][package_name]`, promo.packageName));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][enrollment_fee]`, promo.enrollmentFee?.toString() || '0'));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][percentage]`, promo.percentage?.toString() || '0'));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][promo_type]`, promo.promoType));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][selection_type]`, promo.selectionType?.toString() || '1'));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][custom_initial_payment]`, promo.customInitialPayment?.toString() || ''));
            promoHiddenInputs.appendChild(hiddenContainer);
        }
    }

    function addPromoToTable(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!validatePromoForm()) return;

        promoCounter++;

        const totalTuition = getTotalTuitionFee();
        const selectedType = parseInt(document.querySelector('input[name="promo_selection"]:checked').value);
        const packageName = packageInput.value.trim();
        const promoType = promoTypeInput.value.trim();

        let promo = {
            id: promoCounter,
            packageName: packageName,
            selectionType: selectedType,
            promoType: promoType,
            totalTuition: totalTuition
        };

        if (selectedType <= 2) {
            // Options 1-2: Percentage calculation
            const percentage = parseFloat(percentageInput.value) || 0;
            const discountAmount = (totalTuition * percentage) / 100;
            const uponEnrollment = totalTuition - discountAmount;

            promo = {
                ...promo,
                percentage: percentage,
                enrollmentFee: discountAmount, // This is the discount amount
                discountAmount: discountAmount,
                uponEnrollment: uponEnrollment,
                customInitialPayment: null
            };
        } else {
            // Options 3-4: Custom initial payment
            const customPayment = parseFloat(customInitialPaymentInput.value) || 0;

            promo = {
                ...promo,
                percentage: 0,
                enrollmentFee: 0,
                discountAmount: 0,
                uponEnrollment: totalTuition,
                customInitialPayment: customPayment
            };
        }

        // Add to promos array
        promos.push(promo);
        addPromoRowToTable(promo);

        // Clear form
        packageInput.value = '';
        percentageInput.value = '';
        enrollmentFeeInput.value = '';
        customInitialPaymentInput.value = '';
        promoTypeInput.value = '';

        showPromoMessage('Promo added successfully!', 'success');
    }

    function removePromo(id) {
        // Remove from promos array
        promos = promos.filter(promo => promo.id !== id);

        // Remove table row
        const row = document.getElementById(`promo_row_${id}`);
        if (row) row.remove();

        // Remove hidden inputs
        const hiddenContainer = document.getElementById(`promo_hidden_container_${id}`);
        if (hiddenContainer) hiddenContainer.remove();

        showPromoMessage('Promo removed successfully!', 'success');
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function () {
        // Set up promo selection type handling
        promoSelectionRadios.forEach(radio => {
            radio.addEventListener('change', handleSelectionTypeChange);
        });

        // Calculate discount based on percentage
        if (percentageInput) {
            percentageInput.addEventListener('input', calculateUponEnrollment);
        }

        // Add promo button handler
        if (addPromoButton) {
            addPromoButton.addEventListener('click', addPromoToTable);
        }

        // Initialize selection display
        handleSelectionTypeChange();
    });

    // Make functions globally available
    window.removePromo = removePromo;
</script>

<script>
    const form = document.getElementById('myForm');

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Stop default form submit

        // Check that at least one schedule is added
        if (schedules.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Schedule',
                text: 'Please add at least one schedule to continue.',
                confirmButtonColor: '#10b981'
            });
            return;
        }

        // Confirm submission
        Swal.fire({
            title: 'Create Program',
            text: "Are you ready to create this program?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, create it!',
            cancelButtonText: 'No, keep editing',
            customClass: {
                confirmButton: 'rounded-lg px-4 py-2',
                cancelButton: 'rounded-lg px-4 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Submit form
            }
        });
    });
</script>