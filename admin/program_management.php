<?php


include '../connection/connection.php';
include "includes/header.php";
$conn = con();

// Fetch programs with their schedules and promos
$query = "SELECT 
    p.*,
    GROUP_CONCAT(DISTINCT CONCAT(s.id, '|', s.week_description, '|', s.training_date, '|', s.start_time, '|', s.day_of_week, '|', s.day_value) SEPARATOR ';;') as schedules,
    GROUP_CONCAT(DISTINCT CONCAT(pr.id, '|', pr.package_name, '|', pr.enrollment_fee, '|', pr.percentage, '|', pr.promo_type) SEPARATOR ';;') as promos
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

?>


<!-- Add SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.js"></script>

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
                        lass="text-gray-500 hover:text-primary focus:outline-none cursor-pointer">
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
            <!-- Dashboard Overview -->
            <h2 class="font-semibold my-2 text-xs">
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> > Management Zone | Program
                Management
            </h2>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Programs</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $program_count; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Schedules</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $schedule_count; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Promos</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $promo_count; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-percentage text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6 mt-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Program Management</h1>
                    <p class="text-gray-600 mt-1">Manage your training programs, schedules, and promotions</p>
                </div>
                <a href="program_adding.php"
                    class="cubic py-2 px-5 rounded-xl border-2 hover:bg-green-500 my-4 button-show-reg font-bold hover:text-white hover:-translate-y-2 hover:border-green-800 cursor-pointer">
                    <i class="fas fa-plus mr-2"></i>
                    Add Program
                </a>
            </div>

            <!-- Programs Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-green-500 to-green-600 text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Program</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Fees Overview</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Schedule</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Promotions</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($programs)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg font-medium">No programs found</p>
                                        <p class="text-sm">Create your first program to get started</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($programs as $program): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <!-- Program Details -->
                                        <td class="px-6 py-6">
                                            <div class="flex items-center space-x-4">
                                                <div
                                                    class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                                    <i class="fas fa-graduation-cap text-white text-xl"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-bold text-gray-800 mb-1">
                                                        <?php echo htmlspecialchars($program['program_name']); ?>
                                                    </h3>
                                                    <p class="text-sm bg-sky-600 text-white mb-2 py-2 px-4 rounded-xl">

                                                        <?php echo $program['learning_mode']; ?>
                                                    </p>
                                                    <p class="text-sm text-gray-500 mb-2">ID: <?php echo $program['id']; ?></p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Fees Overview - Simplified -->
                                        <td class="px-6 py-6">
                                            <div class="space-y-3">
                                                <!-- Key Fees -->
                                                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span class="text-sm font-medium text-green-700">Total Tuition</span>
                                                        <span
                                                            class="text-lg font-bold text-green-800">₱<?php echo number_format($program['total_tuition']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span class="text-sm font-medium text-green-700">Initial Payment</span>
                                                        <span
                                                            class="text-base font-semibold text-green-800">₱<?php echo number_format($program['initial_fee']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-sm font-medium text-green-700">Reservation</span>
                                                        <span
                                                            class="text-base font-semibold text-green-800">₱<?php echo number_format($program['reservation_fee']); ?></span>
                                                    </div>
                                                </div>

                                                <!-- Expandable Details -->
                                                <button
                                                    class="w-full text-left text-sm text-blue-600 hover:text-blue-800 font-medium"
                                                    onclick="toggleFeeDetails(<?php echo $program['id']; ?>)">
                                                    <i class="fas fa-chevron-down mr-1"
                                                        id="chevron-<?php echo $program['id']; ?>"></i>
                                                    View Fee Breakdown
                                                </button>

                                                <div id="fee-details-<?php echo $program['id']; ?>"
                                                    class="hidden mt-3 bg-gray-50 p-3 rounded-lg">
                                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                                        <div class="flex justify-between">
                                                            <span>Assessment:</span><span>₱<?php echo number_format($program['assesment_fee']); ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span>Tuition:</span><span>₱<?php echo number_format($program['tuition_fee']); ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span>Miscellaneous:</span><span>₱<?php echo number_format($program['misc_fee']); ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span>OJT:</span><span>₱<?php echo number_format($program['ojt_fee']); ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span>System:</span><span>₱<?php echo number_format($program['system_fee']); ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span>Uniform:</span><span>₱<?php echo number_format($program['uniform_fee']); ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span>ID:</span><span>₱<?php echo number_format($program['id_fee']); ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span>Books:</span><span>₱<?php echo number_format($program['book_fee']); ?></span>
                                                        </div>
                                                        <div
                                                            class="flex justify-between col-span-2 border-t pt-2 mt-2 font-semibold">
                                                            <span>Kit:</span><span>₱<?php echo number_format($program['kit_fee']); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Schedules - Improved -->
                                        <td class="px-6 py-6">
                                            <div class="space-y-2">
                                                <?php if ($program['schedules']): ?>
                                                    <?php
                                                    $schedules = explode(';;', $program['schedules']);
                                                    $displayedSchedules = 0;
                                                    foreach ($schedules as $schedule):
                                                        if ($displayedSchedules >= 2)
                                                            break; // Show only first 2 schedules
                                                        $schedule_data = explode('|', $schedule);
                                                        if (count($schedule_data) >= 6):
                                                            $displayedSchedules++;
                                                            ?>
                                                            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded-lg">
                                                                <div class="flex items-center justify-between mb-1">
                                                                    <span class="font-semibold text-blue-800 text-sm">
                                                                        <?php echo htmlspecialchars($schedule_data[1]); ?>
                                                                    </span>
                                                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                                                        <?php echo htmlspecialchars($schedule_data[4]); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="text-xs text-blue-700 space-y-1">
                                                                    <div class="flex items-center">
                                                                        <i class="fas fa-calendar mr-2 w-3"></i>
                                                                        <?php echo date('M d, Y', strtotime($schedule_data[2])); ?>
                                                                    </div>
                                                                    <div class="flex items-center">
                                                                        <i class="fas fa-clock mr-2 w-3"></i>
                                                                        <?php echo date('g:i A', strtotime($schedule_data[3])); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php
                                                        endif;
                                                    endforeach;

                                                    if (count($schedules) > 2): ?>
                                                        <button
                                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium w-full text-center py-1">
                                                            +<?php echo count($schedules) - 2; ?> more schedules
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="text-center text-gray-400 py-4">
                                                        <i class="fas fa-calendar-times text-xl mb-1"></i>
                                                        <p class="text-xs">No schedules</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Promotions - Improved -->
                                        <td class="px-6 py-6">
                                            <div class="space-y-2">
                                                <?php if ($program['promos']): ?>
                                                    <?php
                                                    $promos = explode(';;', $program['promos']);
                                                    $displayedPromos = 0;
                                                    foreach ($promos as $promo):
                                                        if ($displayedPromos >= 2)
                                                            break; // Show only first 2 promos
                                                        $promo_data = explode('|', $promo);
                                                        if (count($promo_data) >= 5):
                                                            $displayedPromos++;
                                                            ?>
                                                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-lg">
                                                                <div class="flex items-center justify-between mb-1">
                                                                    <span class="font-semibold text-yellow-800 text-sm">
                                                                        <?php echo htmlspecialchars($promo_data[1]); ?>
                                                                    </span>
                                                                    <span
                                                                        class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full font-bold">
                                                                        <?php echo $promo_data[3]; ?>% OFF
                                                                    </span>
                                                                </div>
                                                                <div class="text-xs text-yellow-700 space-y-1">
                                                                    <div class="flex items-center">
                                                                        <i class="fas fa-tag mr-2 w-3"></i>
                                                                        <?php echo htmlspecialchars($promo_data[4]); ?>
                                                                    </div>
                                                                    <div class="flex items-center">
                                                                        <i class="fas fa-money-bill mr-2 w-3"></i>
                                                                        ₱<?php echo number_format($promo_data[2], 2); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php
                                                        endif;
                                                    endforeach;

                                                    if (count($promos) > 2): ?>
                                                        <button
                                                            class="text-xs text-yellow-600 hover:text-yellow-800 font-medium w-full text-center py-1">
                                                            +<?php echo count($promos) - 2; ?> more promos
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="text-center text-gray-400 py-4">
                                                        <i class="fas fa-percentage text-xl mb-1"></i>
                                                        <p class="text-xs">No promotions</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Actions - Improved -->
                                        <td class="px-6 py-6">
                                            <div class="flex justify-center">
                                                <div class="flex space-x-2">
                                                    <a href="program_edit.php?program_id=<?php echo $program['id']; ?>"
                                                        class="p-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg"
                                                        title="Edit Program">
                                                        <i class="fas fa-edit text-sm"></i>
                                                    </a>

                                                    <button
                                                        class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg"
                                                        title="Delete Program"
                                                        onclick="confirmDelete(<?php echo $program['id']; ?>, '<?php echo htmlspecialchars($program['program_name'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-trash text-sm"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?php include "includes/footer.php" ?>
    </div>
</div>

<script>
    // SweetAlert2 Delete Confirmation
    function confirmDelete(programId, programName) {
        Swal.fire({
            title: 'Are you sure?',
            html: `You want to delete the program: <br><strong>${programName}</strong><br><br>This will also delete all related schedules and promotions.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            background: '#fff',
            customClass: {
                popup: 'border-0 shadow-2xl',
                title: 'text-gray-800 font-bold',
                htmlContainer: 'text-gray-600',
                confirmButton: 'font-semibold px-6 py-2 rounded-lg',
                cancelButton: 'font-semibold px-6 py-2 rounded-lg'
            },
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(`functions/ajax/delete_program.php?id=${programId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value && result.value.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: result.value.message,
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        customClass: {
                            popup: 'border-0 shadow-2xl',
                            title: 'text-gray-800 font-bold',
                            confirmButton: 'font-semibold px-6 py-2 rounded-lg'
                        }
                    }).then(() => {
                        // Reload the page to reflect changes
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.value ? result.value.message : 'Something went wrong while deleting the program.',
                        icon: 'error',
                        confirmButtonColor: '#dc2626',
                        customClass: {
                            popup: 'border-0 shadow-2xl',
                            title: 'text-gray-800 font-bold',
                            confirmButton: 'font-semibold px-6 py-2 rounded-lg'
                        }
                    });
                }
            }
        });
    }

    function toggleFeeDetails(programId) {
        const details = document.getElementById('fee-details-' + programId);
        const chevron = document.getElementById('chevron-' + programId);

        if (details.classList.contains('hidden')) {
            details.classList.remove('hidden');
            chevron.classList.remove('fa-chevron-down');
            chevron.classList.add('fa-chevron-up');
        } else {
            details.classList.add('hidden');
            chevron.classList.remove('fa-chevron-up');
            chevron.classList.add('fa-chevron-down');
        }
    }

    // Toggle sidebar for mobile
    document.getElementById('toggleSidebar')?.addEventListener('click', function () {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('hidden');
    });
</script>

<style>
    .cubic {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .text-primary {
        color: #10b981;
    }

    .hover\:text-primary:hover {
        color: #10b981;
    }

    .hover\:bg-green-500:hover {
        background-color: #10b981;
    }

    .hover\:border-green-800:hover {
        border-color: #065f46;
    }

    /* Custom scrollbar for better table experience */
    .overflow-x-auto::-webkit-scrollbar {
        height: 8px;
    }

    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Custom SweetAlert2 styling */
    .swal2-popup {
        border-radius: 12px !important;
    }

    .swal2-title {
        font-size: 1.5rem !important;
    }

    .swal2-html-container {
        font-size: 1rem !important;
        line-height: 1.5 !important;
    }
</style>

<?php include "form/modal_program.php" ?>