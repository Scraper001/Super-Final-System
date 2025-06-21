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

// Fetch promos data
$promo_query = "SELECT * FROM `promo` WHERE `program_id` = ?";
$promo_stmt = $conn->prepare($promo_query);
$promo_stmt->bind_param("i", $program_id);
$promo_stmt->execute();
$promo_result = $promo_stmt->get_result();
$promos_data = [];
while ($row = $promo_result->fetch_assoc()) {
    $promos_data[] = $row;
}

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

        // Insert updated promos
        if (isset($_POST['promos']) && is_array($_POST['promos'])) {
            foreach ($_POST['promos'] as $index => $promo_details) {
                $package_name = !empty($promo_details['package_name']) ? $promo_details['package_name'] : null;
                $enrollment_fee = !empty($promo_details['enrollment_fee']) ? $promo_details['enrollment_fee'] : null;
                $percentage = !empty($promo_details['percentage']) ? $promo_details['percentage'] : null;
                $promo_type = !empty($promo_details['promo_type']) ? $promo_details['promo_type'] : null;

                if ($package_name && $enrollment_fee && $percentage && $promo_type) {
                    $stmt = $conn->prepare("INSERT INTO `promo` (`program_id`, `package_name`, `enrollment_fee`, `percentage`, `promo_type`) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issds", $program_id, $package_name, $enrollment_fee, $percentage, $promo_type);
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
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> > Management Zone | Program
                Editing
            </h2>

            <form id="updateForm" class="w-full min-h-screen p-2 bg-white rounded-xl border-2 flex flex-col"
                method="POST">
                <div class="w-full mb-6 px-4">
                    <h1 class="text-2xl font-bold">Edit Care Pro Program</h1>
                    <p class="italic">Update Program with Ease</p>
                </div>

                <div class="w-full min-h-screen">
                    <div class="w-full p-2">
                        <span class="text-2xl font-semibold italic">Program Name</span>
                        <input type="text" class="border-2 outline-none py-2 px-5 text-xl" name="program_name"
                            value="<?php echo htmlspecialchars($program_data['program_name']); ?>">

                        <!-- Schedule Section -->
                        <div class="w-full flex flex-row mt-4">
                            <div class="w-[40%] border-dashed py-4 px-2">
                                <span class="font-semibold italic">Class Schedule and Duration</span>
                                <!-- Schedule form inputs (same as original) -->
                                <div class="flex flex-row mt-4 w-full border-2 py-2 border-dashed">
                                    <div class="flex flex-col items-center justify-between w-full">
                                        <span>Opening of Class</span>
                                        <input type="date" id="openingClasses" class="border-2 outline-none px-4 py-2">
                                    </div>
                                    <div class="flex flex-col items-center justify-between w-full">
                                        <span>Ending of Class</span>
                                        <input type="date" id="endingClasses" class="border-2 outline-none px-4 py-2">
                                    </div>
                                </div>

                                <div class="mt-2 bg-gray-200 p-2 border-2 border-dashed">
                                    <span class="font-semibold">Training Date</span>
                                    <input type="date" id="trainingDate"
                                        class="border-2 outline-none px-4 py-2 bg-white w-full">
                                    <span id="dateError" class="error-message hidden"></span>
                                </div>

                                <div class="mt-2 bg-gray-200 p-2 border-2 border-dashed">
                                    <span class="font-semibold">Choose a day</span>
                                    <select id="daySelect" class="border-2 outline-none px-4 py-2 bg-white w-full">
                                        <option value="">Select a day</option>
                                    </select>
                                </div>

                                <div class="flex flex-row mt-4 w-full border-2 py-2 border-dashed">
                                    <div class="flex flex-col items-center justify-between w-full">
                                        <span>Start of Training</span>
                                        <input type="time" id="startTime" class="border-2 outline-none px-4 py-2">
                                    </div>
                                    <div class="flex flex-col items-center justify-between w-full">
                                        <span>End of Training</span>
                                        <input type="time" id="endTime" class="border-2 outline-none px-4 py-2">
                                    </div>
                                </div>

                                <div class="w-full border-dashed py-2 items-center justify-center flex">
                                    <button type="button" id="addSchedule" class="btn-success w-full">Add
                                        Schedule</button>
                                </div>
                                <div id="messageContainer" class="mt-2"></div>
                            </div>

                            <div class="w-[60%] border-dashed py-4 px-2 mx-2">
                                <span class="font-semibold italic">Current Schedules</span>
                                <div class="border-2 h-[330px] overflow-y-auto">
                                    <table class="w-full">
                                        <thead class="w-full bg-gray-50">
                                            <tr>
                                                <th class="text-md border-2 p-2">Week Description</th>
                                                <th class="text-md border-2 p-2">Training Date</th>
                                                <th class="text-md border-2 p-2">Start Time</th>
                                                <th class="text-md border-2 p-2">End Time</th>
                                                <th class="text-md border-2 p-2">Day of Week</th>
                                                <th class="text-md border-2 p-2">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="scheduleTableBody" class="border-2">
                                            <!-- Existing schedules will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="hiddenInputs" style="display: none;"></div>

                        <!-- Fees Section -->
                        <div class="w-full flex-row flex mt-10">
                            <div class="w-[40%] mx-4">
                                <span class="text-2xl font-semibold italic mb-3 block">CHARGES FEE</span>
                                <div class="w-full grid grid-cols-2 gap-4">
                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Learning Mode</span>
                                        <div class="mx-2 mb-1">
                                            <input type="radio" name="learning_mode" value="F2F" class="mr-2" <?php echo ($program_data['learning_mode'] ?? '') == 'F2F' ? 'checked' : ''; ?>>
                                            <span>F2F</span>
                                        </div>
                                        <div class="mx-2">
                                            <input type="radio" name="learning_mode" value="Online" class="mr-2" <?php echo ($program_data['learning_mode'] ?? '') == 'Online' ? 'checked' : ''; ?>>
                                            <span>Online</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Assessment Fee</span>
                                        <input type="number" name="assessment_fee" id="assessment_fee"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()"
                                            value="<?php echo $program_data['assesment_fee']; ?>">
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Tuition Fee</span>
                                        <input type="number" name="tuition_fee" id="tuition_fee"
                                            value="<?php echo $program_data['tuition_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>

                                    <!-- Continue with other fee fields... -->
                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Misc Fee</span>
                                        <input type="number" name="misc_fee" id="misc_fee"
                                            value="<?php echo $program_data['misc_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>


                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">OJT Fee and Medical</span>
                                        <input type="number" name="ojt_fee" id="ojt_fee"
                                            value="<?php echo $program_data['ojt_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">System Fee</span>
                                        <input type="number" name="system_fee" id="system_fee"
                                            value="<?php echo $program_data['system_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Uniform (Scrub and Polo Shirt)</span>
                                        <input type="number" name="uniform_fee" id="uniform_fee"
                                            value="<?php echo $program_data['uniform_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">ID</span>
                                        <input type="number" name="id_fee" id="id_fee"
                                            value="<?php echo $program_data['id_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Books</span>
                                        <input type="number" name="book_fee" id="book_fee"
                                            value="<?php echo $program_data['book_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Kit</span>
                                        <input type="number" name="kit_fee" id="kit_fee"
                                            value="<?php echo $program_data['kit_fee']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                            placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                    </div>

                                    <!-- Add all other fee fields similarly -->
                                </div>
                                <div class="grid grid-cols-2 w-full border-2 bg-gray-200 px-4 border-dashed mt-10 py-5">
                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Demo 1</span>
                                        <input type="number" name="demo1_fee" id="demo1_fee"
                                            value="<?php echo $program_data['demo1_fee_hidden']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                            placeholder="0.00" step="0.01" readonly>
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Demo 2</span>
                                        <input type="number" name="demo2_fee" id="demo2_fee"
                                            value="<?php echo $program_data['demo2_fee_hidden']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                            placeholder="0.00" step="0.01" readonly>
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Demo 3</span>
                                        <input type="number" name="demo3_fee" id="demo3_fee"
                                            value="<?php echo $program_data['demo3_fee_hidden']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                            placeholder="0.00" step="0.01" readonly>
                                    </div>

                                    <div class="flex flex-col mx-3">
                                        <span class="font-semibold mb-2">Demo 4</span>
                                        <input type="number" name="demo4_fee" id="demo4_fee"
                                            value="<?php echo $program_data['demo4_fee_hidden']; ?>"
                                            class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                            placeholder="0.00" step="0.01" readonly>
                                    </div>
                                </div>

                                <div class="w-full my-6 space-y-2 bg-gray-50 p-4 rounded-lg">
                                    <div class="text-xl font-semibold">
                                        Total Tuition: <span id="total_amount"
                                            class="text-green-600">₱<?php echo number_format($program_data['total_tuition'], 2); ?></span>
                                    </div>
                                    <input type="hidden" name="total_tuition" id="total_tuition_hidden"
                                        value="<?php echo $program_data['total_tuition']; ?>">
                                    <input type="hidden" name="demo1_fee_hidden" id="demo1_fee_hidden"
                                        value="<?php echo $program_data['demo1_fee_hidden']; ?>">
                                    <input type="hidden" name="demo2_fee_hidden" id="demo2_fee_hidden"
                                        value="<?php echo $program_data['demo2_fee_hidden']; ?>">
                                    <input type="hidden" name="demo3_fee_hidden" id="demo3_fee_hidden"
                                        value="<?php echo $program_data['demo3_fee_hidden']; ?>">
                                    <input type="hidden" name="demo4_fee_hidden" id="demo4_fee_hidden"
                                        value="<?php echo $program_data['demo4_fee_hidden']; ?>">
                                </div>
                            </div>

                            <div class="w-[50%]">
                                <span class="text-2xl font-semibold italic mb-3">PAYMENT</span>
                                <div class="w-full border-2 p-2 flex items-center justify-center flex-col">
                                    <div class="flex flex-row items-center my-2">
                                        <span class="mx-2">Reservation Fee</span>
                                        <input type="number" name="reservation_fee"
                                            value="<?php echo $program_data['reservation_fee']; ?>"
                                            class="py-2 px-4 border-2 outline-none" id="reservation_fee"
                                            onkeyup="calculateFees()">
                                    </div>
                                    <div class="flex flex-row items-center my-2">
                                        <span class="mx-2">Initial Payment</span>
                                        <input type="number" name="initial_fee" id="initial_payment"
                                            value="<?php echo $program_data['initial_fee']; ?>"
                                            onkeyup="calculateFees()" class="py-2 px-4 border-2 outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Promo Section -->
                        <div class="w-full flex flex-col">
                            <h1 class="text-2xl font-bold mb-6 text-center">Edit Promo Management</h1>
                            <div id="promoMessageContainer" class="mb-4 text-center min-h-[24px]"></div>

                            <div class="w-full flex flex-row mt-4">
                                <div class="w-[40%] border-dashed py-4 px-2">
                                    <span class="font-semibold italic">Promo Details</span>
                                    <div class="flex flex-col mt-4 w-full border-2 py-2 border-dashed p-2 space-y-4">
                                        <div class="flex flex-col items-start justify-between w-full">
                                            <span class="font-medium mb-1">Package</span>
                                            <input type="text" id="packageInput" placeholder="Enter package name"
                                                class="border-2 outline-none px-4 py-2 w-full rounded focus:border-blue-500">


                                        </div>
                                        <div class="flex flex-col items-start justify-between w-full">
                                            <span class="font-medium mb-1">Percentage (%)</span>
                                            <input type="number" id="percentageInput"
                                                placeholder="Enter discount percentage" step="0.01" min="0" max="100"
                                                class="border-2 outline-none px-4 py-2 w-full rounded focus:border-blue-500">
                                        </div>


                                        <div class="flex flex-col items-start justify-between w-full">
                                            <span class="font-medium mb-1">Total Discount (PHP)</span>
                                            <input type="number" id="enrollmentFeeInput"
                                                placeholder="Enter enrollment fee" step="0.01" min="0"
                                                class="border-2 outline-none px-4 py-2 w-full rounded focus:border-blue-500">
                                        </div>


                                        <div class="flex flex-col items-start justify-between w-full">
                                            <span class="font-medium mb-1">Promo Type</span>
                                            <input type="text" id="promoTypeInput" placeholder="Enter promo type"
                                                class="border-2 outline-none px-4 py-2 w-full rounded focus:border-blue-500">
                                        </div>
                                    </div>

                                    <div class="w-full border-dashed py-2 items-center justify-center flex">
                                        <button type="button" id="addPromo" class="btn-success w-full">Add
                                            Promo</button>
                                    </div>
                                </div>

                                <div class="w-[60%] border-dashed py-4 px-2 mx-2">
                                    <span class="font-semibold italic">Current Promos</span>
                                    <div class="border-2 h-[400px] overflow-y-auto">
                                        <table class="w-full">
                                            <thead class="w-full bg-gray-50 sticky top-0">
                                                <tr>
                                                    <th class="text-sm border-2 p-2 font-semibold">Package</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Enrollment Fee</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Percentage</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Promo Type</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="promoTableBody" class="border-2">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div id="promoHiddenInputs" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                <div class="w-full flex items-center space-x-4">
                    <button type="submit" name="update_process"
                        class="px-4 py-2 rounded bg-blue-500 text-white w-full font-semibold">
                        Update Program
                    </button>
                    <a href="program_management.php"
                        class="px-4 py-2 rounded bg-gray-500 text-white w-full font-semibold text-center">
                        Cancel
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
    const promoTypeInput = document.getElementById('promoTypeInput');
    const addPromoButton = document.getElementById('addPromo');
    const promoTableBody = document.getElementById('promoTableBody');
    const promoMessageContainer = document.getElementById('promoMessageContainer');
    const promoHiddenInputs = document.getElementById('promoHiddenInputs');

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
                promoType: promo.promo_type
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

        // Add enter key support for promo inputs
        [packageInput, enrollmentFeeInput, percentageInput, promoTypeInput].forEach(input => {
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

    // Helper Functions
    function showMessage(message, type = 'error') {
        if (messageContainer) {
            messageContainer.innerHTML = `<span class="${type === 'success' ? 'success-message' : 'error-message'}">${message}</span>`;
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
        }
    }

    function showPromoMessage(message, type = 'error') {
        if (promoMessageContainer) {
            promoMessageContainer.innerHTML = `<span class="${type === 'success' ? 'success-message' : 'error-message'}">${message}</span>`;
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

        // Check for duplicate training dates
        // if (schedules.some(schedule => schedule.trainingDate === training)) {
        //     showMessage('A training session is already scheduled for this date.');
        //     return false;
        // }

        return true;
    }

    function addScheduleRowToTable(schedule) {
        if (!tableBody) return;

        const isWeekendDay = isWeekend(schedule.trainingDate);
        const rowBgClass = isWeekendDay ? 'bg-yellow-100' : 'bg-blue-100';

        const row = document.createElement('tr');
        row.id = `schedule_row_${schedule.id}`;
        row.className = rowBgClass;

        const endTimeCell = schedule.endTime ?
            `<td class="border-2 p-2 text-center">${formatTime(schedule.endTime)}</td>` :
            '<td class="border-2 p-2 text-center">-</td>';

        row.innerHTML = `
        <td class="border-2 p-2 text-center">${schedule.weekDescription}</td>
        <td class="border-2 p-2 text-center">${formatDate(schedule.trainingDate)}</td>
        <td class="border-2 p-2 text-center">${formatTime(schedule.startTime)}</td>
        ${endTimeCell}
        <td class="border-2 p-2 text-center">${schedule.dayOfWeek}</td>
        <td class="border-2 p-2 text-center">
            <button type="button" onclick="removeSchedule(${schedule.id})" class="btn-danger">Remove</button>
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

    // Promo Management Functions
    function validatePromoForm() {
        if (!packageInput || !enrollmentFeeInput || !percentageInput || !promoTypeInput) {
            return false;
        }

        const packageName = packageInput.value.trim();
        const enrollmentFee = enrollmentFeeInput.value.trim();
        const percentage = percentageInput.value.trim();
        const promoType = promoTypeInput.value.trim();

        // Check if all fields are filled
        if (!packageName || !enrollmentFee || !percentage || !promoType) {
            showPromoMessage('Please fill in all fields.');
            return false;
        }

        // Validate percentage is a number between 0 and 100
        const percentageValue = parseFloat(percentage);
        if (isNaN(percentageValue) || percentageValue < 0 || percentageValue > 100) {
            showPromoMessage('Please enter a valid percentage between 0 and 100.');
            return false;
        }

        // Validate enrollment fee is a positive number
        const feeValue = parseFloat(enrollmentFee);
        if (isNaN(feeValue) || feeValue < 0) {
            showPromoMessage('Please enter a valid enrollment fee.');
            return false;
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
        row.className = 'bg-green-50 hover:bg-green-100';
        row.innerHTML = `
        <td class="border-2 p-2 text-center">${promo.packageName}</td>
        <td class="border-2 p-2 text-center">${formatCurrency(promo.enrollmentFee)}</td>
        <td class="border-2 p-2 text-center">${promo.percentage}%</td>
        <td class="border-2 p-2 text-center">${promo.promoType}</td>
        <td class="border-2 p-2 text-center">
            <button type="button" onclick="removePromo(${promo.id})" class="btn-danger">Remove</button>
        </td>
    `;
        promoTableBody.appendChild(row);

        // Create hidden inputs
        if (promoHiddenInputs) {
            const hiddenContainer = document.createElement('div');
            hiddenContainer.id = `promo_hidden_container_${promo.id}`;
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][package_name]`, promo.packageName));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][enrollment_fee]`, promo.enrollmentFee.toString()));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][percentage]`, promo.percentage.toString()));
            hiddenContainer.appendChild(createPromoTextbox(`promos[${promo.id}][promo_type]`, promo.promoType));
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

        const promo = {
            id: promoCounter,
            packageName: packageInput.value.trim(),
            enrollmentFee: parseFloat(enrollmentFeeInput.value.trim()),
            percentage: parseFloat(percentageInput.value.trim()),
            promoType: promoTypeInput.value.trim()
        };

        promos.push(promo);
        addPromoRowToTable(promo);

        // Clear form
        packageInput.value = '';
        enrollmentFeeInput.value = '';
        percentageInput.value = '';
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
            formData.append(`promos[${index}][enrollment_fee]`, promo.enrollmentFee.toString());
            formData.append(`promos[${index}][percentage]`, promo.percentage.toString());
            formData.append(`promos[${index}][promo_type]`, promo.promoType);
        });

        return formData;
    }

    function getPromoSummary() {
        return {
            totalPromos: promos.length,
            averageDiscount: promos.length > 0 ? promos.reduce((sum, promo) => sum + promo.percentage, 0) / promos.length : 0,
            totalEnrollmentFees: promos.reduce((sum, promo) => sum + promo.enrollmentFee, 0)
        };
    }

    function clearAllPromos() {
        if (promos.length === 0) {
            showPromoMessage('No promos to clear.');
            return;
        }

        if (confirm('Are you sure you want to clear all promos?')) {
            promos = [];
            if (promoTableBody) promoTableBody.innerHTML = '';
            if (promoHiddenInputs) promoHiddenInputs.innerHTML = '';
            promoCounter = 0;
            showPromoMessage('All promos cleared!', 'success');
        }
    }

    function showPromoSummary() {
        if (promos.length === 0) {
            showPromoMessage('No promos available for summary.');
            return;
        }

        const summary = getPromoSummary();
        const message = `Total Promos: ${summary.totalPromos} | Avg Discount: ${summary.averageDiscount.toFixed(1)}% | Total Enrollment Fees: ${formatCurrency(summary.totalEnrollmentFees)}`;
        showPromoMessage(message, 'success');
    }

    // Make functions available globally
    window.getAllSchedules = getAllSchedules;
    window.getFormData = getFormData;
    window.removeSchedule = removeSchedule;
    window.getAllPromos = getAllPromos;
    window.getPromoFormData = getPromoFormData;
    window.removePromo = removePromo;
    window.clearAllPromos = clearAllPromos;
    window.getPromoSummary = getPromoSummary;
    window.showPromoSummary = showPromoSummary;
    window.calculateFees = calculateFees;
</script>

<script>
    // Add this JavaScript code to fix the promo calculation

    // Add event listener for percentage input to auto-calculate enrollment fee
    document.addEventListener('DOMContentLoaded', function () {
        const percentageInput = document.getElementById('percentageInput');
        const enrollmentFeeInput = document.getElementById('enrollmentFeeInput');

        if (percentageInput && enrollmentFeeInput) {
            percentageInput.addEventListener('input', function () {
                calculatePromoDiscount();
            });

            // Also trigger calculation when total tuition changes
            const feeInputs = [
                'assessment_fee', 'tuition_fee', 'misc_fee', 'ojt_fee',
                'system_fee', 'uniform_fee', 'id_fee', 'book_fee', 'kit_fee'
            ];

            feeInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('input', function () {
                        calculateFees(); // Calculate total first
                        calculatePromoDiscount(); // Then calculate promo discount
                    });
                }
            });
        }
    });

    // Function to calculate promo discount based on percentage
    function calculatePromoDiscount() {
        const percentageInput = document.getElementById('percentageInput');
        const enrollmentFeeInput = document.getElementById('enrollmentFeeInput');
        const totalTuitionElement = document.getElementById('total_tuition_hidden');

        if (!percentageInput || !enrollmentFeeInput || !totalTuitionElement) {
            return;
        }

        const percentage = parseFloat(percentageInput.value) || 0;
        const totalTuition = parseFloat(totalTuitionElement.value) || 0;

        if (percentage > 0 && totalTuition > 0) {
            const discountAmount = (totalTuition * percentage) / 100;
            enrollmentFeeInput.value = discountAmount.toFixed(2);
        } else {
            enrollmentFeeInput.value = '';
        }
    }

    // Update the existing calculateFees function to also trigger promo calculation
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

        // Get the initial payment amount (you'll need to specify which field this is)
        // Replace 'initial_payment' with the actual ID of your initial payment field


        const initialPayment = parseFloat(document.getElementById('initial_payment').value) || 0;
        const reservation_fee = parseFloat(document.getElementById('reservation_fee').value) || 0;


        // Calculate total sum (not including demos)
        const totalTuition = assessmentFee + tuitionFee + miscFee + ojtFee + systemFee +
            uniformFee + idFee + bookFee + kitFee;

        // Calculate demo fee using your new formula: (Total_tuition - initial) / 4
        const remainingAmount = totalTuition - initialPayment - reservation_fee;
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

        // Optional: Display remaining amount for verification
        console.log('Total Tuition:', totalTuition);
        console.log('Initial Payment:', initialPayment);
        console.log('Remaining Amount:', remainingAmount);
        console.log('Demo Fee (each):', demoFee);
    }

    // Initialize calculation on page load
    document.addEventListener('DOMContentLoaded', function () {
        calculateFees();

        // Add event listener to initial payment field to recalculate when it changes
        const initialPaymentField = document.getElementById('initial_payment');
        if (initialPaymentField) {
            initialPaymentField.addEventListener('input', calculateFees);
        }
    });
    // Initialize calculation on page load
    document.addEventListener('DOMContentLoaded', function () {
        calculateFees();
    });

</script>


<!-- Add this script tag in your HTML head section or before closing body tag -->
<script>
    const form = document.getElementById('updateForm');

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Stop default form submit

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to submit this form?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, submit it!',
            cancelButtonText: 'No, cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Submit form to same page
            }
        });
    });
</script>