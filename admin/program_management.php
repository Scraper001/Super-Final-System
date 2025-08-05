<?php
include '../connection/connection.php';
include "includes/header.php";
$conn = con();

// Fetch programs with their schedules, promos and check for enrolled students
$query = "SELECT 
    p.*,
    GROUP_CONCAT(DISTINCT CONCAT(s.id, '|', s.week_description, '|', s.training_date, '|', s.start_time, '|', s.day_of_week, '|', s.day_value) SEPARATOR ';;') as schedules,
    GROUP_CONCAT(DISTINCT CONCAT(pr.id, '|', pr.package_name, '|', pr.enrollment_fee, '|', pr.percentage, '|', pr.promo_type) SEPARATOR ';;') as promos,
    (SELECT COUNT(*) FROM student_enrollments se WHERE se.program_id = p.id) as enrolled_students
FROM program p
LEFT JOIN schedules s ON p.id = s.program_id
LEFT JOIN promo pr ON p.id = pr.program_id
GROUP BY p.id ORDER BY id DESC";

$result = mysqli_query($conn, $query);
$programs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get counts for summary cards
$program_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM program"));
$schedule_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM schedules"));
$promo_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM promo"));
$enrolled_students_count = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT student_id FROM student_enrollments"));
?>

<!-- Add SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.js"></script>
<style>
    /* Add these to your existing style section */
    .active-tab {
        border-bottom-width: 2px;
    }

    .tab-content {
        transition: all 0.3s ease;
    }

    /* Custom scrollbar for tab content */
    .max-h-48 {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    .max-h-48::-webkit-scrollbar {
        width: 4px;
    }

    .max-h-48::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .max-h-48::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>
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
                    <a href="announcement.php"
                        class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer">
                        <i class="fa-solid fa-bullhorn mr-1"></i>
                        Create Announcement
                    </a>
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
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> > Management Zone | Program
                Management
            </h2>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                <!-- Total Programs -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <div class="p-5 flex items-center">
                        <div class="rounded-full bg-blue-100 p-3 mr-4">
                            <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Programs</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo $program_count; ?></p>
                        </div>
                    </div>
                    <div class="bg-blue-50 px-5 py-2">
                        <p class="text-xs text-blue-600">All training courses offered</p>
                    </div>
                </div>

                <!-- Active Schedules -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <div class="p-5 flex items-center">
                        <div class="rounded-full bg-green-100 p-3 mr-4">
                            <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Active Schedules</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo $schedule_count; ?></p>
                        </div>
                    </div>
                    <div class="bg-green-50 px-5 py-2">
                        <p class="text-xs text-green-600">Program training sessions</p>
                    </div>
                </div>

                <!-- Active Promos -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <div class="p-5 flex items-center">
                        <div class="rounded-full bg-yellow-100 p-3 mr-4">
                            <i class="fas fa-percentage text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Active Promos</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo $promo_count; ?></p>
                        </div>
                    </div>
                    <div class="bg-yellow-50 px-5 py-2">
                        <p class="text-xs text-yellow-600">Special offers & discounts</p>
                    </div>
                </div>

                <!-- Enrolled Students -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <div class="p-5 flex items-center">
                        <div class="rounded-full bg-purple-100 p-3 mr-4">
                            <i class="fas fa-users text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Enrolled Students</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo $enrolled_students_count; ?></p>
                        </div>
                    </div>
                    <div class="bg-purple-50 px-5 py-2">
                        <p class="text-xs text-purple-600">Active program enrollments</p>
                    </div>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 my-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Program Management</h1>
                    <p class="text-gray-600 mt-1">Manage your training programs, schedules, and promotions</p>
                </div>
                <a href="program_adding.php"
                    class="flex items-center bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-all transform hover:-translate-y-1 shadow-md hover:shadow-lg">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Add Program</span>
                </a>
            </div>

            <!-- Filter Options -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex items-center">
                        <input type="text" id="program-search" placeholder="Search programs..."
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div class="flex gap-4">
                        <select id="mode-filter"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">All Modes</option>
                            <option value="F2F">Face to Face</option>
                            <option value="Online">Online</option>
                        </select>

                        <select id="sort-option"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="name-asc">Name (A-Z)</option>
                            <option value="name-desc">Name (Z-A)</option>
                            <option value="fee-asc">Fee (Low to High)</option>
                            <option value="fee-desc">Fee (High to Low)</option>
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Programs Cards Grid -->
            <!-- Programs Cards Grid -->
            <div id="programs-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($programs as $program): ?>
                    <?php
                    $hasStudents = $program['enrolled_students'] > 0;
                    $mode = $program['learning_mode'];
                    $modeColor = ($mode === 'F2F') ? 'bg-sky-600' : 'bg-purple-600';

                    // Parse schedules
                    $schedulesList = [];
                    if ($program['schedules']) {
                        $schedules = explode(';;', $program['schedules']);
                        foreach ($schedules as $schedule) {
                            $schedule_data = explode('|', $schedule);
                            if (count($schedule_data) >= 6) {
                                $schedulesList[] = [
                                    'id' => $schedule_data[0],
                                    'week' => $schedule_data[1],
                                    'date' => $schedule_data[2],
                                    'time' => $schedule_data[3],
                                    'day' => $schedule_data[4]
                                ];
                            }
                        }
                    }

                    // Parse promos
                    $promosList = [];
                    if ($program['promos']) {
                        $promos = explode(';;', $program['promos']);
                        foreach ($promos as $promo) {
                            $promo_data = explode('|', $promo);
                            if (count($promo_data) >= 5) {
                                $promosList[] = [
                                    'id' => $promo_data[0],
                                    'name' => $promo_data[1],
                                    'fee' => $promo_data[2],
                                    'percentage' => $promo_data[3],
                                    'type' => $promo_data[4]
                                ];
                            }
                        }
                    }
                    ?>

                    <div class="program-card bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
                        data-name="<?php echo strtolower(htmlspecialchars($program['program_name'])); ?>"
                        data-mode="<?php echo $program['learning_mode']; ?>"
                        data-fee="<?php echo $program['total_tuition']; ?>" data-id="<?php echo $program['id']; ?>">

                        <!-- Card Header -->
                        <div class="relative h-24 bg-gradient-to-r from-green-500 to-green-600 p-4">
                            <div class="absolute top-3 right-3 flex space-x-2 mb-">
                                <!-- Edit Button -->
                                <a href="program_edit.php?program_id=<?php echo $program['id']; ?>"
                                    class="p-2 bg-white text-green-600 rounded-lg hover:bg-green-50 transition-all"
                                    title="Edit Program">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Delete Button - Disabled if has students -->
                                <button
                                    class="p-2 <?php echo $hasStudents ? 'bg-gray-300 cursor-not-allowed' : 'bg-white text-red-600 hover:bg-red-50'; ?> rounded-lg transition-all"
                                    title="<?php echo $hasStudents ? 'Cannot delete - has enrolled students' : 'Delete Program'; ?>"
                                    <?php echo $hasStudents ? 'disabled' : 'onclick="confirmDelete(' . $program['id'] . ', \'' . htmlspecialchars($program['program_name'], ENT_QUOTES) . '\')"'; ?>>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <div class="text-white pr-16">
                                <h3 class="text-xl font-bold truncate">
                                    <?php echo htmlspecialchars($program['program_name']); ?>
                                </h3>
                                <div class="flex items-center justify-between mt-1">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-xs font-medium <?php echo $modeColor; ?>">
                                        <?php echo $program['learning_mode']; ?>
                                    </span>

                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4">
                            <!-- Program Fee Summary -->
                            <div class="mb-4 p-3 bg-green-50 rounded-lg border-l-4 border-green-500">
                                <span class="text-black/70 text-xs italic">PROGRAM ID: <?php echo $program['id']; ?></span>
                                <div class="flex justify-between items-center">

                                    <span class="text-sm font-medium text-gray-700">Total Tuition</span>
                                    <span
                                        class="text-lg font-bold text-green-700">₱<?php echo number_format($program['total_tuition'], 2); ?></span>
                                </div>
                                <div class="mt-1 grid grid-cols-2 gap-2 text-xs text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-money-bill-wave mr-1 w-4 text-center text-green-500"></i>
                                        Initial: ₱<?php echo number_format($program['initial_fee'], 2); ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-bookmark mr-1 w-4 text-center text-green-500"></i>
                                        Reservation: ₱<?php echo number_format($program['reservation_fee'], 2); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabs Navigation -->
                            <div class="mb-3 flex border-b">
                                <button type="button"
                                    class="tab-button active-tab px-4 py-2 border-b-2 border-green-500 text-sm font-medium text-green-600"
                                    data-target="schedule-tab-<?php echo $program['id']; ?>">
                                    <i class="fas fa-calendar-alt mr-1"></i> Schedule
                                </button>
                                <button type="button"
                                    class="tab-button px-4 py-2 text-sm font-medium text-gray-500 hover:text-green-600"
                                    data-target="promo-tab-<?php echo $program['id']; ?>">
                                    <i class="fas fa-tags mr-1"></i> Promotions
                                </button>
                            </div>

                            <!-- Tab Content: Schedule -->
                            <div id="schedule-tab-<?php echo $program['id']; ?>" class="tab-content">
                                <?php if (!empty($schedulesList)): ?>
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        <?php foreach ($schedulesList as $index => $schedule): ?>
                                            <div class="bg-blue-50 border-l-3 border-blue-400 p-3 rounded-lg">
                                                <div class="flex justify-between items-center mb-1">
                                                    <span
                                                        class="font-medium text-blue-800 text-sm"><?php echo htmlspecialchars($schedule['week']); ?></span>
                                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                                                        <?php echo htmlspecialchars($schedule['day']); ?>
                                                    </span>
                                                </div>
                                                <div class="text-xs text-blue-700 grid grid-cols-2 gap-2">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-calendar-day mr-1 w-4 text-center"></i>
                                                        <?php echo date('M d, Y', strtotime($schedule['date'])); ?>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-clock mr-1 w-4 text-center"></i>
                                                        <?php echo date('g:i A', strtotime($schedule['time'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($index === 2 && count($schedulesList) > 3): ?>
                                                <button
                                                    class="w-full text-xs bg-blue-50 hover:bg-blue-100 text-blue-600 py-2 rounded-lg transition-colors toggle-more"
                                                    data-show-target="more-schedules-<?php echo $program['id']; ?>"
                                                    data-hide-text="Hide additional schedules"
                                                    data-show-text="Show <?php echo count($schedulesList) - 3; ?> more schedules">
                                                    Show <?php echo count($schedulesList) - 3; ?> more schedules
                                                </button>
                                                <div id="more-schedules-<?php echo $program['id']; ?>" class="hidden space-y-2">
                                                    <?php foreach (array_slice($schedulesList, 3) as $schedule): ?>
                                                        <div class="bg-blue-50 border-l-3 border-blue-400 p-3 rounded-lg">
                                                            <div class="flex justify-between items-center mb-1">
                                                                <span
                                                                    class="font-medium text-blue-800 text-sm"><?php echo htmlspecialchars($schedule['week']); ?></span>
                                                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                                                                    <?php echo htmlspecialchars($schedule['day']); ?>
                                                                </span>
                                                            </div>
                                                            <div class="text-xs text-blue-700 grid grid-cols-2 gap-2">
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-calendar-day mr-1 w-4 text-center"></i>
                                                                    <?php echo date('M d, Y', strtotime($schedule['date'])); ?>
                                                                </div>
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-clock mr-1 w-4 text-center"></i>
                                                                    <?php echo date('g:i A', strtotime($schedule['time'])); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php break; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col items-center justify-center py-4 bg-gray-50 rounded-lg">
                                        <i class="fas fa-calendar-times text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-sm text-gray-500">No schedules available</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Content: Promotions -->
                            <div id="promo-tab-<?php echo $program['id']; ?>" class="tab-content hidden">
                                <?php if (!empty($promosList)): ?>
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        <?php foreach ($promosList as $index => $promo): ?>
                                            <div class="bg-yellow-50 border-l-3 border-yellow-400 p-3 rounded-lg">
                                                <div class="flex justify-between items-center mb-1">
                                                    <span
                                                        class="font-medium text-yellow-800 text-sm"><?php echo htmlspecialchars($promo['name']); ?></span>
                                                    <?php if (intval($promo['percentage']) > 0): ?>
                                                        <span
                                                            class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-bold">
                                                            <?php echo $promo['percentage']; ?>% OFF
                                                        </span>
                                                    <?php else: ?>
                                                        <span
                                                            class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-bold">
                                                            ₱<?php echo number_format(floatval($promo['fee']), 2); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-yellow-700 grid grid-cols-2 gap-2">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-tag mr-1 w-4 text-center"></i>
                                                        <?php echo htmlspecialchars($promo['type']); ?>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-money-bill mr-1 w-4 text-center"></i>
                                                        <?php if (intval($promo['percentage']) > 0): ?>
                                                            ₱<?php echo number_format(floatval($promo['fee']), 2); ?> discount
                                                        <?php else: ?>
                                                            Required payment
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($index === 2 && count($promosList) > 3): ?>
                                                <button
                                                    class="w-full text-xs bg-yellow-50 hover:bg-yellow-100 text-yellow-600 py-2 rounded-lg transition-colors toggle-more"
                                                    data-show-target="more-promos-<?php echo $program['id']; ?>"
                                                    data-hide-text="Hide additional promotions"
                                                    data-show-text="Show <?php echo count($promosList) - 3; ?> more promotions">
                                                    Show <?php echo count($promosList) - 3; ?> more promotions
                                                </button>
                                                <div id="more-promos-<?php echo $program['id']; ?>" class="hidden space-y-2">
                                                    <?php foreach (array_slice($promosList, 3) as $promo): ?>
                                                        <div class="bg-yellow-50 border-l-3 border-yellow-400 p-3 rounded-lg">
                                                            <div class="flex justify-between items-center mb-1">
                                                                <span
                                                                    class="font-medium text-yellow-800 text-sm"><?php echo htmlspecialchars($promo['name']); ?></span>
                                                                <?php if (intval($promo['percentage']) > 0): ?>
                                                                    <span
                                                                        class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-bold">
                                                                        <?php echo $promo['percentage']; ?>% OFF
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span
                                                                        class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-bold">
                                                                        ₱<?php echo number_format(floatval($promo['fee']), 2); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="text-xs text-yellow-700 grid grid-cols-2 gap-2">
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-tag mr-1 w-4 text-center"></i>
                                                                    <?php echo htmlspecialchars($promo['type']); ?>
                                                                </div>
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-money-bill mr-1 w-4 text-center"></i>
                                                                    <?php if (intval($promo['percentage']) > 0): ?>
                                                                        ₱<?php echo number_format(floatval($promo['fee']), 2); ?> discount
                                                                    <?php else: ?>
                                                                        Required payment
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php break; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col items-center justify-center py-4 bg-gray-50 rounded-lg">
                                        <i class="fas fa-percentage text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-sm text-gray-500">No promotions available</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Enrollment Info Footer -->
                            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-gray-500">Students Enrolled</p>
                                    <p
                                        class="text-lg font-bold <?php echo $hasStudents ? 'text-purple-600' : 'text-gray-400'; ?>">
                                        <?php echo $program['enrolled_students']; ?>
                                    </p>
                                </div>

                                <a href="program_edit.php?program_id=<?php echo $program['id']; ?>"
                                    class="text-sm text-green-600 hover:text-green-800 font-medium">
                                    Manage Program <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Empty State - No Programs -->
                <?php if (empty($programs)): ?>
                    <div
                        class="col-span-full flex flex-col items-center justify-center bg-white rounded-xl shadow p-8 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-graduation-cap text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">No Programs Found</h3>
                        <p class="text-gray-600 mb-6">Get started by creating your first training program</p>
                        <a href="program_adding.php"
                            class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-all">
                            <i class="fas fa-plus mr-2"></i>
                            Add Program
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- No Results State (initially hidden) -->
            <div id="no-results"
                class="hidden flex flex-col items-center justify-center bg-white rounded-xl shadow p-8 text-center mt-4">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-search text-gray-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">No Matching Programs</h3>
                <p class="text-gray-600 mb-4">Try adjusting your search or filters</p>
                <button id="reset-filters" class="text-green-600 hover:text-green-800 font-medium">
                    Reset Filters
                </button>
            </div>
        </main>

        <?php include "includes/footer.php" ?>
    </div>
</div>

<script>
    // SweetAlert2 Delete Confirmation
    function confirmDelete(programId, programName) {
        Swal.fire({
            title: 'Delete Program',
            html: `Are you sure you want to delete <strong>${programName}</strong>?<br><br>This will permanently remove this program along with all its schedules and promotions.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'rounded-xl border-0',
                title: 'text-xl text-gray-800',
                confirmButton: 'rounded-lg px-5 py-2.5',
                cancelButton: 'rounded-lg px-5 py-2.5'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the program.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX call to delete program
                fetch(`functions/ajax/delete_program.php?id=${programId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                // Remove the card from the DOM or reload the page
                                document.querySelector(`[data-id="${programId}"]`).remove();

                                // Check if we need to show the empty state
                                const remainingCards = document.querySelectorAll('.program-card');
                                if (remainingCards.length === 0) {
                                    location.reload(); // Reload to show empty state
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Unable to delete program.',
                                icon: 'error',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An unexpected error occurred.',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    });
            }
        });
    }

    // Toggle fee details
    function toggleFeeDetails(programId) {
        const details = document.getElementById('fee-details-' + programId);
        const chevron = document.getElementById('chevron-' + programId);

        if (details.classList.contains('hidden')) {
            details.classList.remove('hidden');
            chevron.classList.add('transform', 'rotate-180');
        } else {
            details.classList.add('hidden');
            chevron.classList.remove('transform', 'rotate-180');
        }
    }

    // Toggle schedules
    function toggleSchedules(programId) {
        const moreSchedules = document.getElementById('more-schedules-' + programId);
        const toggleText = document.getElementById('schedule-toggle-text-' + programId);

        if (moreSchedules.classList.contains('hidden')) {
            moreSchedules.classList.remove('hidden');
            toggleText.innerText = 'Hide additional schedules';
        } else {
            moreSchedules.classList.add('hidden');
            const count = moreSchedules.querySelectorAll('.bg-blue-50').length;
            toggleText.innerText = `Show ${count} more schedules`;
        }
    }

    // Toggle promos
    function togglePromos(programId) {
        const morePromos = document.getElementById('more-promos-' + programId);
        const toggleText = document.getElementById('promo-toggle-text-' + programId);

        if (morePromos.classList.contains('hidden')) {
            morePromos.classList.remove('hidden');
            toggleText.innerText = 'Hide additional promotions';
        } else {
            morePromos.classList.add('hidden');
            const count = morePromos.querySelectorAll('.bg-yellow-50').length;
            toggleText.innerText = `Show ${count} more promotions`;
        }
    }

    // Search and filter functionality
    document.addEventListener('DOMContentLoaded', function () {

        const searchInput = document.getElementById('program-search');
        const modeFilter = document.getElementById('mode-filter');
        const sortOption = document.getElementById('sort-option');
        const programsContainer = document.getElementById('programs-container');
        const noResults = document.getElementById('no-results');
        const resetFilters = document.getElementById('reset-filters');
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function () {
                // Get the parent card
                const card = this.closest('.program-card');

                // Remove active class from all tabs in this card
                card.querySelectorAll('.tab-button').forEach(tab => {
                    tab.classList.remove('active-tab');
                    tab.classList.remove('border-green-500');
                    tab.classList.remove('text-green-600');
                    tab.classList.add('text-gray-500');
                });

                // Add active class to clicked tab
                this.classList.add('active-tab');
                this.classList.add('border-green-500');
                this.classList.add('text-green-600');
                this.classList.remove('text-gray-500');

                // Hide all tab content in this card
                card.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Show target tab content
                const targetId = this.getAttribute('data-target');
                card.querySelector('#' + targetId).classList.remove('hidden');
            });
        });

        document.querySelectorAll('.toggle-more').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-show-target');
                const target = document.getElementById(targetId);
                const showText = this.getAttribute('data-show-text');
                const hideText = this.getAttribute('data-hide-text');

                if (target.classList.contains('hidden')) {
                    target.classList.remove('hidden');
                    this.textContent = hideText;
                } else {
                    target.classList.add('hidden');
                    this.textContent = showText;
                }
            });
        });
        function filterAndSortPrograms() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const modeValue = modeFilter.value;
            const sortValue = sortOption.value;

            const programCards = document.querySelectorAll('.program-card');
            let visibleCount = 0;

            // Convert NodeList to Array for sorting
            const cardsArray = Array.from(programCards);

            // Sort the cards
            cardsArray.sort((a, b) => {
                const aName = a.getAttribute('data-name');
                const bName = b.getAttribute('data-name');
                const aFee = parseInt(a.getAttribute('data-fee'));
                const bFee = parseInt(b.getAttribute('data-fee'));
                const aId = parseInt(a.getAttribute('data-id'));
                const bId = parseInt(b.getAttribute('data-id'));

                switch (sortValue) {
                    case 'name-asc':
                        return aName.localeCompare(bName);
                    case 'name-desc':
                        return bName.localeCompare(aName);
                    case 'fee-asc':
                        return aFee - bFee;
                    case 'fee-desc':
                        return bFee - aFee;
                    case 'newest':
                        return bId - aId;
                    case 'oldest':
                        return aId - bId;
                    default:
                        return 0;
                }
            });

            // Remove all cards from container
            while (programsContainer.firstChild) {
                programsContainer.removeChild(programsContainer.firstChild);
            }

            // Add sorted cards back and apply filters
            cardsArray.forEach(card => {
                const cardName = card.getAttribute('data-name');
                const cardMode = card.getAttribute('data-mode');

                const matchesSearch = searchTerm === '' || cardName.includes(searchTerm);
                const matchesMode = modeValue === '' || cardMode === modeValue;

                if (matchesSearch && matchesMode) {
                    programsContainer.appendChild(card);
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            // Show/hide no results message
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }

        // Add event listeners
        searchInput?.addEventListener('input', filterAndSortPrograms);
        modeFilter?.addEventListener('change', filterAndSortPrograms);
        sortOption?.addEventListener('change', filterAndSortPrograms);

        // Reset filters
        resetFilters?.addEventListener('click', function () {
            searchInput.value = '';
            modeFilter.value = '';
            sortOption.value = 'name-asc';
            filterAndSortPrograms();
        });

        // Toggle sidebar for mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function () {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        });
    });
</script>

<style>
    /* Custom styles */
    .border-l-3 {
        border-left-width: 3px;
    }

    .text-primary {
        color: #10b981;
    }

    .hover\:text-primary:hover {
        color: #10b981;
    }

    /* Smooth transitions */
    .program-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .program-card:hover {
        transform: translateY(-4px);
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Animation for cards */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .program-card {
        animation: fadeIn 0.3s ease-out forwards;
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
        .program-card {
            animation: none;
        }
    }
</style>

<?php include "form/modal_program.php" ?>