<?php include '../connection/connection.php';
include "includes/header.php";
$conn = con();

// Get program_id from URL
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;

if ($program_id <= 0) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Invalid Program ID',
            text: 'The program ID provided is not valid.',
            confirmButtonColor: '#d33'
        }).then((result) => {
            window.location.href = 'program_management.php';
        });
    </script>";
    exit;
}

// Fetch program data
$program_query = "SELECT * FROM `program` WHERE `id` = ?";
$program_stmt = $conn->prepare($program_query);
$program_stmt->bind_param("i", $program_id);
$program_stmt->execute();
$program_result = $program_stmt->get_result();
$program_data = $program_result->fetch_assoc();

if ($program_result->num_rows < 0) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Program Not Found',
            text: 'The requested program could not be found.',
            confirmButtonColor: '#d33'
        }).then((result) => {
            window.location.href = 'program_management.php';
        });
    </script>";
    exit;
}

// Fetch schedules data
$schedule_query = "SELECT * FROM `schedules` WHERE `program_id` = ?";
$schedule_stmt = $conn->prepare($schedule_query);
$schedule_stmt->bind_param("i", $program_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedules_data = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedules_data[] = $row;
}

// Fetch promos data with enhanced fields
$promo_query = "SELECT * FROM `promo` WHERE `program_id` = ?";
$promo_stmt = $conn->prepare($promo_query);
$promo_stmt->bind_param("i", $program_id);
$promo_stmt->execute();
$promo_result = $promo_stmt->get_result();
$promos_data = [];
while ($row = $promo_result->fetch_assoc()) {
    // Set default values for potentially missing columns
    if (!isset($row['selection_type'])) {
        $row['selection_type'] = 1; // Default to option 1 for old records
    }
    if (!isset($row['custom_initial_payment'])) {
        $row['custom_initial_payment'] = null;
    }
    if (!isset($row['required_initial_payment'])) {
        $row['required_initial_payment'] = null;
    }
    $promos_data[] = $row;
}

// Check if students are enrolled in this program
$enrolled_query = "SELECT COUNT(*) as enrolled_count FROM student_enrollments WHERE program_id = ?";
$enrolled_stmt = $conn->prepare($enrolled_query);
$enrolled_stmt->bind_param("i", $program_id);
$enrolled_stmt->execute();
$enrolled_result = $enrolled_stmt->get_result();
$enrolled_data = $enrolled_result->fetch_assoc();
$has_enrolled_students = ($enrolled_data['enrolled_count'] > 0);

// Handle UPDATE form submission
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // Update program table
    if (!isset($_POST['system_fee'])) {
        $system_fee = 0;
    } else {
        $system_fee = $_POST['system_fee'];
    }

    $update_program = "UPDATE `program` SET 
        `program_name`=?, `learning_mode`=?, `assesment_fee`=?, `tuition_fee`=?, 
        `misc_fee`=?, `ojt_fee`=?, `system_fee`=?, `uniform_fee`=?, `id_fee`=?, 
        `book_fee`=?, `kit_fee`=?, `total_tuition`=?, `demo1_fee_hidden`=?, 
        `demo2_fee_hidden`=?, `demo3_fee_hidden`=?, `demo4_fee_hidden`=?, 
        `reservation_fee`=?, `initial_fee`=? WHERE `id`=?";

    $update_stmt = $conn->prepare($update_program);
    $update_stmt->bind_param(
        "ssidddddddddddddddi",
        $_POST['program_name'],
        $_POST['learning_mode'],
        $_POST['assessment_fee'],
        $_POST['tuition_fee'],
        $_POST['misc_fee'],
        $_POST['ojt_fee'],
        $system_fee,
        $_POST['uniform_fee'],
        $_POST['id_fee'],
        $_POST['book_fee'],
        $_POST['kit_fee'],
        $_POST['total_tuition'],
        $_POST['demo1_fee_hidden'],
        $_POST['demo2_fee_hidden'],
        $_POST['demo3_fee_hidden'],
        $_POST['demo4_fee_hidden'],
        $_POST['reservation_fee'],
        $_POST['initial_fee'],
        $program_id
    );

    if ($update_stmt->execute()) {
        date_default_timezone_set('Asia/Manila');
        $date_created2 = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Edited Program: " . $_POST['program_name'] . "', '$date_created2' )");

        // Delete existing schedules and insert new ones
        $delete_schedules = "DELETE FROM `schedules` WHERE `program_id` = ?";
        $delete_stmt = $conn->prepare($delete_schedules);
        $delete_stmt->bind_param("i", $program_id);
        $delete_stmt->execute();

        // Insert updated schedules
        if (isset($_POST['schedule']) && is_array($_POST['schedule'])) {
            foreach ($_POST['schedule'] as $index => $details) {
                $weekDescription = !empty($details['week_description']) ? $details['week_description'] : null;
                $trainingDate = !empty($details['training_date']) ? $details['training_date'] : null;
                $start_time = !empty($details['start_time']) ? $details['start_time'] : null;
                $end_time = !empty($details['end_time']) ? $details['end_time'] : null;
                $day_of_week = !empty($details['day_of_week']) ? $details['day_of_week'] : null;
                $day_value = !empty($details['day_value']) ? $details['day_value'] : null;

                if ($weekDescription && $trainingDate && $start_time && $day_of_week && $day_value) {
                    $sql_schedule = "INSERT INTO `schedules`(`program_id`, `week_description`, `training_date`, `start_time`,`end_time`, `day_of_week`, `day_value`) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql_schedule);
                    $stmt->bind_param("issssss", $program_id, $weekDescription, $trainingDate, $start_time, $end_time, $day_of_week, $day_value);
                    $stmt->execute();
                }
            }
        }

        // Delete existing promos and insert new ones
        $delete_promos = "DELETE FROM `promo` WHERE `program_id` = ?";
        $delete_promo_stmt = $conn->prepare($delete_promos);
        $delete_promo_stmt->bind_param("i", $program_id);
        $delete_promo_stmt->execute();

        // Insert updated promos with new fields
        if (isset($_POST['promos']) && is_array($_POST['promos'])) {
            foreach ($_POST['promos'] as $index => $promo_details) {
                $package_name = !empty($promo_details['package_name']) ? $promo_details['package_name'] : null;
                $enrollment_fee = !empty($promo_details['enrollment_fee']) ? floatval($promo_details['enrollment_fee']) : 0;
                $percentage = !empty($promo_details['percentage']) ? floatval($promo_details['percentage']) : 0;
                $promo_type = !empty($promo_details['promo_type']) ? $promo_details['promo_type'] : null;
                $selection_type = !empty($promo_details['selection_type']) ? intval($promo_details['selection_type']) : 1;
                $custom_initial_payment = !empty($promo_details['custom_initial_payment']) ? floatval($promo_details['custom_initial_payment']) : null;

                if ($package_name && $promo_type) {
                    $stmt = $conn->prepare("INSERT INTO `promo` 
                        (`program_id`, `package_name`, `enrollment_fee`, `percentage`, `promo_type`, `selection_type`, `custom_initial_payment`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isddsis", $program_id, $package_name, $enrollment_fee, $percentage, $promo_type, $selection_type, $custom_initial_payment);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Program updated successfully!',
                confirmButtonColor: '#28a745',
                timer: 2000,
                timerProgressBar: true
            }).then((result) => {
                window.location.href = 'program_management.php';
            });
        </script>";
    } else {
        echo
            "<script>
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: 'There was an error updating the program. Please try again.',
                confirmButtonColor: '#d33'
            });
        </script>";
    }
}
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
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer" id="logout">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
            <h2 class="font-semibold my-2 text-xs">
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> >
                <a href="program_management.php" class="hover:text-green-400 text-gray-400">Program Management</a> >
                Edit Program
            </h2>

            <form id="updateForm" class="w-full min-h-screen p-4 bg-white rounded-xl shadow-md flex flex-col"
                method="POST">
                <div class="w-full mb-6 px-4 border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold">Edit Program</h1>
                            <p class="text-gray-600 italic">Update program details, schedules, and promotions</p>
                        </div>

                        <?php if ($has_enrolled_students): ?>
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm">This program has enrolled students. Some changes may affect
                                            current enrollments.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="w-full">
                    <div class="w-full p-2">
                        <div class="mb-6">
                            <label for="program_name" class="block text-sm font-medium text-gray-700 mb-1">Program
                                Name</label>
                            <input type="text" class="border-2 outline-none py-2 px-5 text-xl w-full rounded-lg"
                                id="program_name" name="program_name"
                                value="<?php echo htmlspecialchars($program_data['program_name']); ?>">
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
                                                    <!-- Existing schedules will be populated here via JavaScript -->
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
                                                    class="form-radio h-4 w-4 text-green-600" <?php echo ($program_data['learning_mode'] ?? '') == 'F2F' ? 'checked' : ''; ?>>
                                                <span class="ml-2">Face to Face</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="learning_mode" value="Online"
                                                    class="form-radio h-4 w-4 text-green-600" <?php echo ($program_data['learning_mode'] ?? '') == 'Online' ? 'checked' : ''; ?>>
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
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['assesment_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tuition
                                                Fee</label>
                                            <input type="number" name="tuition_fee" id="tuition_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['tuition_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Miscellaneous
                                                Fee</label>
                                            <input type="number" name="misc_fee" id="misc_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['misc_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">OJT Fee &
                                                Medical</label>
                                            <input type="number" name="ojt_fee" id="ojt_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['ojt_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">System
                                                Fee</label>
                                            <input type="number" name="system_fee" id="system_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['system_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Uniform
                                                Fee</label>
                                            <input type="number" name="uniform_fee" id="uniform_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['uniform_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">ID Fee</label>
                                            <input type="number" name="id_fee" id="id_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['id_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Books
                                                Fee</label>
                                            <input type="number" name="book_fee" id="book_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['book_fee']; ?>">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Kit Fee</label>
                                            <input type="number" name="kit_fee" id="kit_fee"
                                                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                                value="<?php echo $program_data['kit_fee']; ?>">
                                        </div>
                                    </div>

                                    <div class="mt-6 bg-green-50 p-4 rounded-lg">
                                        <h4 class="font-medium text-gray-800 mb-2">Total Tuition:</h4>
                                        <p id="total_amount" class="text-2xl font-bold text-green-600">
                                            ₱<?php echo number_format($program_data['total_tuition'], 2); ?>
                                        </p>
                                        <input type="hidden" name="total_tuition" id="total_tuition_hidden"
                                            value="<?php echo $program_data['total_tuition']; ?>">
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

                                                </div>
                                                <input type="number" name="reservation_fee" id="reservation_fee"
                                                    class="w-full border rounded-lg pl-8 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="0.00" onkeyup="calculateFees()"
                                                    value="<?php echo $program_data['reservation_fee']; ?>">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial
                                                Payment</label>
                                            <div class="relative mt-1 rounded-md shadow-sm">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">

                                                </div>
                                                <input type="number" name="initial_fee" id="initial_payment"
                                                    class="w-full border rounded-lg pl-8 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="0.00" onkeyup="calculateFees()"
                                                    value="<?php echo $program_data['initial_fee']; ?>">
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
                                                    placeholder="0.00" readonly
                                                    value="<?php echo $program_data['demo1_fee_hidden']; ?>">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Demo
                                                    2</label>
                                                <input type="number" name="demo2_fee" id="demo2_fee"
                                                    class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                                                    placeholder="0.00" readonly
                                                    value="<?php echo $program_data['demo2_fee_hidden']; ?>">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Demo
                                                    3</label>
                                                <input type="number" name="demo3_fee" id="demo3_fee"
                                                    class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                                                    placeholder="0.00" readonly
                                                    value="<?php echo $program_data['demo3_fee_hidden']; ?>">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Demo
                                                    4</label>
                                                <input type="number" name="demo4_fee" id="demo4_fee"
                                                    class="w-full border rounded-lg px-3 py-2 bg-gray-100"
                                                    placeholder="0.00" readonly
                                                    value="<?php echo $program_data['demo4_fee_hidden']; ?>">
                                            </div>
                                        </div>

                                        <!-- Hidden inputs for demo fees -->
                                        <input type="hidden" name="demo1_fee_hidden" id="demo1_fee_hidden"
                                            value="<?php echo $program_data['demo1_fee_hidden']; ?>">
                                        <input type="hidden" name="demo2_fee_hidden" id="demo2_fee_hidden"
                                            value="<?php echo $program_data['demo2_fee_hidden']; ?>">
                                        <input type="hidden" name="demo3_fee_hidden" id="demo3_fee_hidden"
                                            value="<?php echo $program_data['demo3_fee_hidden']; ?>">
                                        <input type="hidden" name="demo4_fee_hidden" id="demo4_fee_hidden"
                                            value="<?php echo $program_data['demo4_fee_hidden']; ?>">
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
                                                    <!-- Existing promos will be populated here via JavaScript -->
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
                    <button type="submit" name="update_process"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition-colors shadow-md flex-grow">
                        <i class="fas fa-save mr-2"></i> Update Program
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
                        systemFeeInput.value = '0';
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
</script>

<script>
    // Initialize existing data
    const existingSchedules = <?php echo json_encode($schedules_data); ?>;
    const existingPromos = <?php echo json_encode($promos_data); ?>;

    // Global variables
    let scheduleCounter = 0;
    let schedules = [];
    let promoCounter = 0;
    let promos = [];

    // Day options
    const weekdayOptions = [
        { value: 'Weekday 1', text: 'Weekday 1' },
        { value: 'Weekday 2', text: 'Weekday 2' },
        { value: 'Weekday 3', text: 'Weekday 3' },
        { value: 'Weekday 4', text: 'Weekday 4' }
    ];

    const weekendOptions = [
        { value: 'Weekend 1', text: 'Weekend 1' },
        { value: 'Weekend 2', text: 'Weekend 2' }
    ];

    // DOM Elements
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

    // Promo Elements
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

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Load existing schedules
        existingSchedules.forEach(schedule => {
            scheduleCounter++;
            const scheduleObj = {
                id: scheduleCounter,
                weekDescription: schedule.week_description,
                trainingDate: schedule.training_date,
                startTime: schedule.start_time,
                endTime: schedule.end_time || '',
                dayOfWeek: schedule.day_of_week,
                dayValue: schedule.day_value
            };
            schedules.push(scheduleObj);
            addScheduleRowToTable(scheduleObj);
        });

        // Load existing promos
        existingPromos.forEach(promo => {
            promoCounter++;
            const promoObj = {
                id: promoCounter,
                packageName: promo.package_name,
                enrollmentFee: parseFloat(promo.enrollment_fee),
                percentage: parseFloat(promo.percentage),
                promoType: promo.promo_type,
                selectionType: parseInt(promo.selection_type) || 1,
                customInitialPayment: parseFloat(promo.custom_initial_payment) || null
            };
            promos.push(promoObj);
            addPromoRowToTable(promoObj);
        });

        calculateFees();

        // Setup event listeners
        setupEventListeners();
    });

    // Setup Event Listeners
    function setupEventListeners() {
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

        if (addPromoButton) {
            addPromoButton.addEventListener('click', addPromoToTable);
        }

        // Add event listeners for promo selection type
        promoSelectionRadios.forEach(radio => {
            radio.addEventListener('change', handleSelectionTypeChange);
        });

        // Calculate discount based on percentage input
        if (percentageInput) {
            percentageInput.addEventListener('input', calculateUponEnrollment);
        }

        // Add enter key support for promo inputs
        [packageInput, enrollmentFeeInput, percentageInput, customInitialPaymentInput, promoTypeInput].forEach(input => {
            if (input) {
                input.addEventListener('keypress', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        addPromoToTable(event);
                    }
                });
            }
        });
    }

    // Fee Calculation Functions
    function calculateFees() {
        // Get all fee input values (excluding demo fees)
        const assessmentFee = parseFloat(document.getElementById('assessment_fee').value) || 0;
        const tuitionFee = parseFloat(document.getElementById('tuition_fee').value) || 0;
        const miscFee = parseFloat(document.getElementById('misc_fee').value) || 0;
        const ojtFee = parseFloat(document.getElementById('ojt_fee').value) || 0;
        const systemFee = parseFloat(document.getElementById('system_fee').value) || 0;
        const uniformFee = parseFloat(document.getElementById('uniform_fee').value) || 0;
        const idFee = parseFloat(document.getElementById('id_fee').value) || 0;
        const bookFee = parseFloat(document.getElementById('book_fee').value) || 0;
        const kitFee = parseFloat(document.getElementById('kit_fee').value) || 0;

        // Get the initial payment amount
        const initialPayment = parseFloat(document.getElementById('initial_payment').value) || 0;
        const reservation_fee = parseFloat(document.getElementById('reservation_fee').value) || 0;

        // Calculate total sum (not including demos)
        const totalTuition = assessmentFee + tuitionFee + miscFee + ojtFee + systemFee +
            uniformFee + idFee + bookFee + kitFee;

        // Calculate demo fee using your new formula: (Total_tuition - initial) / 4
        const remainingAmount = totalTuition;
        const demoFee = remainingAmount / 4;

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

        // If there's an active percentage input, recalculate promo discount
        if (document.getElementById('percentageInput').value) {
            calculateUponEnrollment();
        }
    }

    // Helper Functions
    function showMessage(message, type = 'error') {
        if (messageContainer) {
            const messageClass = type === 'success' ? 'text-green-600' : 'text-red-600';
            messageContainer.innerHTML = `<p class="${messageClass} text-sm font-medium">${message}</p>`;
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
        }
    }

    function showPromoMessage(message, type = 'error') {
        if (promoMessageContainer) {
            const messageClass = type === 'success' ? 'text-green-600' : 'text-red-600';
            promoMessageContainer.innerHTML = `<p class="${messageClass} text-sm font-medium">${message}</p>`;
            setTimeout(() => {
                promoMessageContainer.innerHTML = '';
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

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
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

    // Input Creation Functions
    function createTextbox(name, value) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = name;
        input.value = value;
        input.readOnly = true;
        input.style.display = 'none';
        return input;
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

    // Schedule Management Functions
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
                : `<span class="font-semibold">${formatCurrency(promo.enrollmentFee)}</span>`;
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

    // Utility Functions
    function getAllSchedules() {
        return schedules;
    }

    function getFormData() {
        const formData = new FormData();
        if (openingClasses) formData.append('opening_date', openingClasses.value);
        if (endingClasses) formData.append('ending_date', endingClasses.value);

        schedules.forEach((schedule, index) => {
            formData.append(`schedules[${index}][week_description]`, schedule.weekDescription);
            formData.append(`schedules[${index}][training_date]`, schedule.trainingDate);
            formData.append(`schedules[${index}][start_time]`, schedule.startTime);
            formData.append(`schedules[${index}][end_time]`, schedule.endTime || '');
            formData.append(`schedules[${index}][day_of_week]`, schedule.dayOfWeek);
            formData.append(`schedules[${index}][day_value]`, schedule.dayValue);
        });

        return formData;
    }

    function getAllPromos() {
        return promos;
    }

    function getPromoFormData() {
        const formData = new FormData();

        promos.forEach((promo, index) => {
            formData.append(`promos[${index}][package_name]`, promo.packageName);
            formData.append(`promos[${index}][enrollment_fee]`, promo.enrollmentFee?.toString() || '0');
            formData.append(`promos[${index}][percentage]`, promo.percentage?.toString() || '0');
            formData.append(`promos[${index}][promo_type]`, promo.promoType);
            formData.append(`promos[${index}][selection_type]`, promo.selectionType?.toString() || '1');
            formData.append(`promos[${index}][custom_initial_payment]`, promo.customInitialPayment?.toString() || '');
        });

        return formData;
    }

    // Make functions available globally
    window.getAllSchedules = getAllSchedules;
    window.getFormData = getFormData;
    window.removeSchedule = removeSchedule;
    window.getAllPromos = getAllPromos;
    window.getPromoFormData = getPromoFormData;
    window.removePromo = removePromo;
    window.calculateFees = calculateFees;
</script>

<!-- Add this script tag for form submission confirmation -->
<script>
    const form = document.getElementById('updateForm');

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Stop default form submit

        Swal.fire({
            title: 'Update Program',
            text: "Are you sure you want to save these changes?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-danger'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Submit form to same page
            }
        });
    });
</script>