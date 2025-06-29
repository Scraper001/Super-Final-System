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
                    $start_time,
                    $day_of_week,
                    $day_value
                );
                $stmt->execute();
            }
        }

        date_default_timezone_set('Asia/Manila');
        $date_created2 = date('Y-m-d H:i:s');
        $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Added Program: $program_name', '$date_created2' )");

        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                    title: 'Success!',
                    text: 'Program Successfuly Addded',
                    icon: 'success',
                    confirmButtonText: 'OK'
                    }).then(() => {
                    window.location.href = 'program_management.php'; // 🔁 Redirect target
                    });
                });
              </script>";

    } else {
        // echo "No schedule data provided.<br>";
    }

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

            // Debug: Log the processed values
            error_log("Processed values - Package: $package_name, Enrollment: $enrollment_fee, Percentage: $percentage, Type: $promo_type, Selection: $selection_type");

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
            <!-- Dashboard Overview -->
            <h2 class="font-semibold my-2 text-xs">
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> >Management Zone |
                Program
                Adding</a>

            </h2>
            <form method="POST" class="w-full min-h-screen p-2 bg-white rounded-xl border-2 flex flex-col" id="myForm">
                <div class="w-full mb-6 px-4">
                    <h1 class="text-2xl font-bold">Care Pro Program Builder</h1>
                    <p class="italic">Build Program with Ease</p>
                </div>
                <div class="w-full  min-h-screen">
                    <div class="w-full p-2">
                        <span class="text-2xl font-semibold italic">Enter Program</span>
                        <input type="text" class=" border-2 outline-none py-2 px-5 text-xl" name="program_name"
                            value="">

                        <div class="w-full flex flex-row mt-4">
                            <div class="w-[40%] border-dashed py-4 px-2">
                                <span class="font-semibold italic">Class Schedule and Duration</span>
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
                                    <button type="button" id="addSchedule" class="btn-success w-full">Add</button>
                                </div>

                                <div id="messageContainer" class="mt-2"></div>
                            </div>

                            <div class="w-[60%] border-dashed py-4 px-2 mx-2">
                                <span class="font-semibold italic">Schedule</span>
                                <div class="border-2 h-[330px] overflow-y-auto">
                                    <table class="w-full">
                                        <thead class="w-full bg-gray-50">
                                            <tr>
                                                <th class="text-md border-2 p-2">Week Description</th>
                                                <th class="text-md border-2 p-2">Training Date</th>
                                                <th class="text-md border-2 p-2">Start Time</th>
                                                <th class="text-md border-2 p-2">End Time</th>
                                                <th class="text-md border-2 p-2">Day</th>
                                                <th class="text-md border-2 p-2">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="scheduleTableBody" class="border-2">
                                            <!-- Schedule rows will be added here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden container for database storage -->
                        <div id="hiddenInputs" style="" class="hidden">
                            <!-- Hidden inputs will be added here for database storage -->
                        </div>



                        <div class="w-full flex-row  flex mt-10">

                            <div class="w-[50%]">
                                <div class="w-full">
                                    <span class="text-2xl font-semibold italic mb-3 block">CHARGES FEE</span>
                                    <div class="w-full grid grid-cols-2 gap-4">
                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">Learning Mode</span>
                                            <div class="mx-2 mb-1">
                                                <input type="radio" name="learning_mode" value="F2F" class="mr-2"
                                                    checked>
                                                <span>F2F</span>
                                            </div>
                                            <div class="mx-2">
                                                <input type="radio" name="learning_mode" value="Online" class="mr-2">
                                                <span>Online</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">Assessment Fee</span>
                                            <input type="number" name="assessment_fee" id="assessment_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">Tuition Fee</span>
                                            <input type="number" name="tuition_fee" id="tuition_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">Misc Fee</span>
                                            <input type="number" name="misc_fee" id="misc_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">OJT Fee and Medical</span>
                                            <input type="number" name="ojt_fee" id="ojt_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">System Fee</span>
                                            <input type="number" name="system_fee" id="system_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">Uniform (Scrub and Polo Shirt)</span>
                                            <input type="number" name="uniform_fee" id="uniform_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">ID</span>
                                            <input type="number" name="id_fee" id="id_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">Books</span>
                                            <input type="number" name="book_fee" id="book_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>

                                        <div class="flex flex-col mx-3">
                                            <span class="font-semibold mb-2">Kit</span>
                                            <input type="number" name="kit_fee" id="kit_fee"
                                                class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500"
                                                placeholder="0.00" step="0.01" onkeyup="calculateFees()">
                                        </div>
                                        <div
                                            class="grid grid-cols-2 w-full border-2 bg-gray-200 px-4 border-dashed mt-10 py-5">
                                            <div class="flex flex-col mx-3">
                                                <span class="font-semibold mb-2">Demo 1</span>
                                                <input type="number" name="demo1_fee" id="demo1_fee"
                                                    class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                                    placeholder="0.00" step="0.01" readonly>
                                            </div>

                                            <div class="flex flex-col mx-3">
                                                <span class="font-semibold mb-2">Demo 2</span>
                                                <input type="number" name="demo2_fee" id="demo2_fee"
                                                    class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                                    placeholder="0.00" step="0.01" readonly>
                                            </div>

                                            <div class="flex flex-col mx-3">
                                                <span class="font-semibold mb-2">Demo 3</span>
                                                <input type="number" name="demo3_fee" id="demo3_fee"
                                                    class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                                    placeholder="0.00" step="0.01" readonly>
                                            </div>

                                            <div class="flex flex-col mx-3">
                                                <span class="font-semibold mb-2">Demo 4</span>
                                                <input type="number" name="demo4_fee" id="demo4_fee"
                                                    class="px-4 p-2 border-2 outline-none rounded focus:border-blue-500 bg-gray-100"
                                                    placeholder="0.00" step="0.01" readonly>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="w-full my-6 space-y-2 bg-gray-50 p-4 rounded-lg">
                                        <div class="text-xl font-semibold">
                                            Total Tuition: <span id="total_amount" class="text-green-600">₱0.00</span>
                                        </div>
                                        <!-- Hidden inputs for form submission -->
                                        <input type="hidden" name="total_tuition" id="total_tuition_hidden" value="0">
                                        <input type="hidden" name="demo1_fee_hidden" id="demo1_fee_hidden" value="0">
                                        <input type="hidden" name="demo2_fee_hidden" id="demo2_fee_hidden" value="0">
                                        <input type="hidden" name="demo3_fee_hidden" id="demo3_fee_hidden" value="0">
                                        <input type="hidden" name="demo4_fee_hidden" id="demo4_fee_hidden" value="0">
                                    </div>
                                </div>
                            </div>


                            <div class="w-[50%]">
                                <span class="text-2xl font-semibold italic mb-3">PAYMENT</span>
                                <div class="w-full border-2 p-2 flex items-center justify-center flex-col">
                                    <div class=" flex flex-row  items-center my-2">
                                        <span class="mx-2">Reservation Fee</span>
                                        <input type="number" name="reservation_fee" id="reservation_payment" onkeyup="
                                            calculateFees()" class="py-2 px-4 border-2 outline-none">

                                    </div>
                                    <div class=" flex flex-row  items-center my-2">
                                        <span class="mx-2">Initial Payment</span>
                                        <input type="number" name="initial_fee" onkeyup="calculateFees()"
                                            id="initial_payment" class="py-2 px-4 border-2 outline-none">
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div class="w-full flex flex-col">
                            <h1 class="text-2xl font-bold mb-6 text-center">Promo Management System</h1>

                            <!-- Message Container -->
                            <div id="promoMessageContainer" class="mb-4 text-center min-h-[24px]"></div>

                            <div class="w-full flex flex-row mt-4">
                                <!-- Input Form Section -->
                                <div class="w-[40%] border-dashed py-4 px-2">
                                    <span class="font-semibold italic">Promo Details</span>
                                    <div class="flex flex-col mt-4 w-full border-2 py-2 border-dashed p-2 space-y-4">

                                        <!-- Selection Type -->
                                        <div class="flex flex-col items-start justify-between w-full">
                                            <span class="font-medium mb-2">Promo Selection (1-4)</span>
                                            <div class="grid grid-cols-2 gap-2 w-full">
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

                                            <!-- Specification Text -->
                                            <div class="mt-2 p-2 bg-blue-50 rounded text-xs">
                                                <p><strong>Options 1-2:</strong> Percentage calculation will be applied
                                                    automatically</p>
                                                <p><strong>Options 3-4:</strong> You need to calculate and declare your
                                                    initial payment amount</p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-start justify-between w-full">
                                            <span class="font-medium mb-1">Package</span>
                                            <input type="text" id="packageInput" placeholder="Enter package name"
                                                class="border-2 outline-none px-4 py-2 w-full rounded focus:border-blue-500">
                                        </div>

                                        <!-- Percentage Input (for options 1-2) -->
                                        <div class="flex flex-col items-start justify-between w-full"
                                            id="percentageSection">
                                            <span class="font-medium mb-1">Percentage (%)</span>
                                            <input type="number" id="percentageInput"
                                                placeholder="Enter discount percentage" step="0.01" min="0" max="100"
                                                class="border-2 outline-none px-4 py-2 w-full rounded focus:border-blue-500">
                                        </div>

                                        <!-- Custom Initial Payment (for options 3-4) -->
                                        <div class="flex flex-col items-start justify-between w-full hidden"
                                            id="customPaymentSection">
                                            <span class="font-medium mb-1">Required Initial Payment (PHP)</span>
                                            <input type="number" id="customInitialPaymentInput"
                                                placeholder="Enter required initial payment" step="0.01" min="0"
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

                                <!-- Table Section -->
                                <div class="w-[60%] border-dashed py-4 px-2 mx-2">
                                    <span class="font-semibold italic">Promo List</span>
                                    <div class="border-2 h-[400px] overflow-y-auto">
                                        <table class="w-full">
                                            <thead class="w-full bg-gray-50 sticky top-0">
                                                <tr>
                                                    <th class="text-sm border-2 p-2 font-semibold">Selection</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Package</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Percentage</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Enrollment Fee</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Total Discount</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Promo Type</th>
                                                    <th class="text-sm border-2 p-2 font-semibold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="promoTableBody" class="border-2">
                                                <!-- Promos will be dynamically added here -->
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Additional Actions -->
                                    <div class="mt-4 flex space-x-2 hidden">
                                        <button onclick="clearAllPromos()"
                                            class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            Clear All
                                        </button>
                                        <button onclick="showPromoSummary()"
                                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                            Show Summary
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden inputs container for form submission -->
                            <div id="promoHiddenInputs" style="display: none;"></div>

                            <!-- Demo: Show current promos data -->
                            <div class="mt-6 p-4 bg-gray-50 rounded hidden">
                                <h3 class="font-bold mb-2">Current Promos (JSON):</h3>
                                <pre id="promoJsonDisplay"
                                    class="bg-white p-2 rounded border text-xs overflow-auto max-h-32">[]</pre>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="w-full flex items-center">
                    <button type="submit" name="process"
                        class="px-4 py-2 rounded bg-green-400 w-full font-semibold">Submit</button>

                </div>
            </form>
        </main>


        <?php include "includes/footer.php" ?>
    </div>
</div>
<?php include "form/modal_program.php" ?>


<script>
    // Replace your existing JavaScript with this fixed version:

    let scheduleCounter = 0;
    let schedules = [];

    // Day options - Fixed to show proper day names
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
        messageContainer.innerHTML = `<span class="${type === 'success' ? 'success-message' : 'error-message'}">${message}</span>`;
        setTimeout(() => {
            messageContainer.innerHTML = '';
        }, 5000);
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
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function populateDayOptions() {
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
        const opening = openingClasses.value;
        const ending = endingClasses.value;
        const training = trainingDate.value;
        const day = daySelect.value;
        const start = startTime.value;
        const end = endTime.value;

        // Reset error message
        dateError.classList.add('hidden');
        dateError.textContent = '';

        // Check if all fields are filled
        if (!opening || !ending || !training || !day || !start || !end) {
            showMessage('Please fill in all fields.');
            return false;
        }

        // Validate training date is within class period
        if (training < opening || training > ending) {
            dateError.textContent = 'Training date must be between class opening and ending dates.';
            dateError.classList.remove('hidden');
            showMessage('Training date is outside the class period.');
            return false;
        }

        // Validate start time is before end time
        if (start >= end) {
            showMessage('Start time must be before end time.');
            return false;
        }

        return true;
    }

    function createHiddenInput(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        input.id = `hidden_${name}_${scheduleCounter}`;
        return input;
    }

    function createTextbox(name, value) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = name;
        input.value = value;
        input.readOnly = true; // Optional: prevent user edits
        input.style.display = 'flex'; // Hides the textbox like a hidden field
        input.id = `textbox_${name}_${scheduleCounter}`;
        return input;
    }


    function addScheduleToTable(event) {
        // Prevent form submission
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!validateForm()) return;

        scheduleCounter++;
        const weekDesc = getWeekDescription(trainingDate.value, openingClasses.value);
        const selectedDayText = daySelect.options[daySelect.selectedIndex].text;

        // Create schedule object
        const schedule = {
            id: scheduleCounter,
            weekDescription: weekDesc,
            trainingDate: trainingDate.value,
            startTime: startTime.value,
            endTime: endTime.value,
            dayOfWeek: selectedDayText,
            dayValue: daySelect.value
        };

        schedules.push(schedule);

        // Determine background color based on day type
        const isWeekendDay = isWeekend(trainingDate.value);
        const rowBgClass = isWeekendDay ? 'bg-yellow-100' : 'bg-blue-100';

        // Add row to table
        const row = document.createElement('tr');
        row.id = `schedule_row_${scheduleCounter}`;
        row.className = rowBgClass;
        row.innerHTML = `
        <td class="border-2 p-2 text-center">${weekDesc}</td>
        <td class="border-2 p-2 text-center">${formatDate(trainingDate.value)}</td>
        <td class="border-2 p-2 text-center">${formatTime(startTime.value)}</td>
        <td class="border-2 p-2 text-center">${formatTime(endTime.value)}</td>
        <td class="border-2 p-2 text-center">${selectedDayText}</td>
        <td class="border-2 p-2 text-center">
            <button type="button" onclick="removeSchedule(${scheduleCounter})" class="btn-danger">Remove</button>
        </td>
    `;
        tableBody.appendChild(row);

        // Create hidden inputs for database storage
        const hiddenContainer = document.createElement('div');
        hiddenContainer.id = `hidden_container_${scheduleCounter}`;
        hiddenContainer.appendChild(createTextbox(`schedule[${scheduleCounter}][week_description]`, weekDesc));
        hiddenContainer.appendChild(createTextbox(`schedule[${scheduleCounter}][training_date]`, trainingDate.value));
        hiddenContainer.appendChild(createTextbox(`schedule[${scheduleCounter}][start_time]`, startTime.value));
        hiddenContainer.appendChild(createTextbox(`schedule[${scheduleCounter}][end_time]`, endTime.value));
        hiddenContainer.appendChild(createTextbox(`schedule[${scheduleCounter}][day_of_week]`, selectedDayText));
        hiddenContainer.appendChild(createTextbox(`schedule[${scheduleCounter}][day_value]`, daySelect.value));
        hiddenInputs.appendChild(hiddenContainer);

        // Clear form (except class dates)
        trainingDate.value = '';
        daySelect.innerHTML = '<option value="">Select a day</option>';
        startTime.value = '';
        endTime.value = '';

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

    // Event listeners - FIXED VERSION
    trainingDate.addEventListener('change', populateDayOptions);

    // Fix the add button event listener to prevent form submission
    addButton.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent form submission
        event.stopPropagation(); // Stop event bubbling
        addScheduleToTable(event);
    });

    // Function to get all schedules (for database submission)
    function getAllSchedules() {
        return schedules;
    }

    // Function to get form data as FormData object (for AJAX submission)
    function getFormData() {
        const formData = new FormData();
        formData.append('opening_date', openingClasses.value);
        formData.append('ending_date', endingClasses.value);

        schedules.forEach((schedule, index) => {
            formData.append(`schedules[${index}][week_description]`, schedule.weekDescription);
            formData.append(`schedules[${index}][training_date]`, schedule.trainingDate);
            formData.append(`schedules[${index}][start_time]`, schedule.startTime);
            formData.append(`schedules[${index}][end_time]`, schedule.endTime);
            formData.append(`schedules[${index}][day_of_week]`, schedule.dayOfWeek);
            formData.append(`schedules[${index}][day_value]`, schedule.dayValue);
        });

        return formData;
    }

    // Make functions available globally for external use
    window.getAllSchedules = getAllSchedules;
    window.getFormData = getFormData;
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
        const reservation_payment = parseFloat(document.getElementById('reservation_payment').value) || 0;

        // Calculate total sum (not including demos)
        const totalTuition = assessmentFee + tuitionFee + miscFee + ojtFee + systemFee +
            uniformFee + idFee + bookFee + kitFee;

        // Calculate demo fee using your new formula: (Total_tuition - initial) / 4
        const remainingAmount = totalTuition - initialPayment - reservation_payment;
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
    const promoJsonDisplay = document.getElementById('promoJsonDisplay');

    // Selection type elements
    const percentageSection = document.getElementById('percentageSection');
    const customPaymentSection = document.getElementById('customPaymentSection');
    const promoSelectionRadios = document.querySelectorAll('input[name="promo_selection"]');

    // Helper: Show promo messages
    function showPromoMessage(message, type = 'error') {
        promoMessageContainer.innerHTML = `<span class="${type === 'success' ? 'success-message' : 'error-message'}">${message}</span>`;
        setTimeout(() => {
            promoMessageContainer.innerHTML = '';
        }, 5000);
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

    // Format number as PHP currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }

    // Validate form inputs before adding promo
    function validatePromoForm() {
        const packageName = packageInput.value.trim();
        const promoType = promoTypeInput.value.trim();
        const selectedType = parseInt(document.querySelector('input[name="promo_selection"]:checked').value);
        const totalTuition = getTotalTuitionFee();

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

            if (isNaN(percentage) || percentage <= 0 || percentage > 100) {
                showPromoMessage('Please enter a valid percentage between 0 and 100.');
                return false;
            }
            if (isNaN(enrollmentFee) || enrollmentFee <= 0) {
                showPromoMessage('Please ensure enrollment fee is calculated properly.');
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

        if (promos.some(promo => promo.packageName.toLowerCase() === packageName.toLowerCase())) {
            showPromoMessage('A promo with this package name already exists.');
            return false;
        }

        return true;
    }

    // Create readonly input for hidden promo data
    function createPromoTextbox(name, value) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = name;
        input.value = value;
        input.readOnly = true;
        input.style.display = 'none';
        return input;
    }

    // Add promo to table and promos array
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
            const percentage = parseFloat(percentageInput.value);
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
            const customPayment = parseFloat(customInitialPaymentInput.value);

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

        // Create table row
        const tr = document.createElement('tr');
        tr.setAttribute('data-id', promo.id);

        const selectionDisplay = `Option ${selectedType}`;
        const percentageDisplay = selectedType <= 2 ? `${promo.percentage}%` : 'N/A';
        const enrollmentDisplay = selectedType <= 2 ? formatCurrency(promo.uponEnrollment) : formatCurrency(totalTuition);
        const discountDisplay = selectedType <= 2 ? formatCurrency(promo.discountAmount) : 'Manual';
        const customPaymentNote = selectedType > 2 ? `Required: ${formatCurrency(promo.customInitialPayment)}` : '';

        tr.innerHTML = `
        <td class="px-2 py-2 bg-blue-100 border-blue-800 border-2">${selectionDisplay}</td>
        <td class="px-2 py-2 bg-blue-100 border-blue-800 border-2">${promo.packageName}</td>
        <td class="px-2 py-2 bg-blue-100 border-blue-800 border-2">${percentageDisplay}</td>
        <td class="px-2 py-2 bg-blue-100 border-blue-800 border-2">${enrollmentDisplay}</td>
        <td class="px-2 py-2 bg-blue-100 border-blue-800 border-2">${discountDisplay}</td>
        <td class="px-2 py-2 bg-blue-100 border-blue-800 border-2">${promo.promoType}${customPaymentNote ? '<br><small class="text-red-600">' + customPaymentNote + '</small>' : ''}</td>
        <td class="px-2 py-2 bg-blue-100 border-blue-800 border-2">
            <button type="button" class="px-4 py-2 rounded-xl shadow-lg bg-red-400 remove-btn" data-id="${promo.id}">Remove</button>
        </td>
    `;

        promoTableBody.appendChild(tr);

        // Create hidden inputs with correct values
        const hiddenContainer = document.createElement('div');
        hiddenContainer.id = `promo_container_${promoCounter}`;

        hiddenContainer.appendChild(createPromoTextbox(`promos[${promoCounter}][package_name]`, promo.packageName));
        hiddenContainer.appendChild(createPromoTextbox(`promos[${promoCounter}][percentage]`, promo.percentage.toString()));
        hiddenContainer.appendChild(createPromoTextbox(`promos[${promoCounter}][enrollment_fee]`, promo.enrollmentFee.toString()));
        hiddenContainer.appendChild(createPromoTextbox(`promos[${promoCounter}][promo_type]`, promo.promoType));
        hiddenContainer.appendChild(createPromoTextbox(`promos[${promoCounter}][selection_type]`, promo.selectionType.toString()));
        hiddenContainer.appendChild(createPromoTextbox(`promos[${promoCounter}][custom_initial_payment]`, (promo.customInitialPayment || '').toString()));

        promoHiddenInputs.appendChild(hiddenContainer);

        // Clear input fields
        packageInput.value = '';
        percentageInput.value = '';
        enrollmentFeeInput.value = '';
        customInitialPaymentInput.value = '';
        promoTypeInput.value = '';

        showPromoMessage('Promo added successfully!', 'success');
    }

    // Remove promo functionality
    promoTableBody.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-btn')) {
            const idToRemove = parseInt(event.target.getAttribute('data-id'));

            // Remove from promos array
            promos = promos.filter(promo => promo.id !== idToRemove);

            // Remove row from table
            const rowToRemove = promoTableBody.querySelector(`tr[data-id="${idToRemove}"]`);
            if (rowToRemove) {
                promoTableBody.removeChild(rowToRemove);
            }

            // Remove hidden inputs container
            const hiddenContainer = document.getElementById(`promo_container_${idToRemove}`);
            if (hiddenContainer) {
                hiddenContainer.remove();
            }

            showPromoMessage('Promo removed successfully!', 'success');
        }
    });

    // Event listeners
    promoSelectionRadios.forEach(radio => {
        radio.addEventListener('change', handleSelectionTypeChange);
    });

    percentageInput.addEventListener('input', calculateUponEnrollment);
    addPromoButton.addEventListener('click', addPromoToTable);

    // Initialize
    handleSelectionTypeChange();
    calculateUponEnrollment();
</script>

<script>
    const form = document.getElementById('myForm');

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