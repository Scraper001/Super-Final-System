<?php
include "../connection/connection.php";
include "includes/header.php";
$conn = con();

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$region_filter = $_GET['region'] ?? '';
$program_filter = $_GET['program'] ?? '';

// Income Report Query
$income_query = "SELECT 
    DATE(transaction_date) as date,
    SUM(cash_received) as daily_income,
    COUNT(*) as transactions
    FROM pos_transactions 
    WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(transaction_date)
    ORDER BY date";
$income_result = mysqli_query($conn, $income_query);

// Program Report Query
// Enhanced Program Report Query with Student Details and Error Handling
$program_query = "SELECT 
    p.program_name,
    p.learning_mode,
    COUNT(DISTINCT se.student_id) as enrolled_students,
    COALESCE(SUM(pt.cash_received), 0) as total_revenue,
    COALESCE(AVG(pt.cash_received), 0) as avg_payment,
    COUNT(pt.id) as total_transactions,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            COALESCE(si.first_name, ''), ' ', COALESCE(si.last_name, ''),
            ' | Guardian: ', COALESCE(si.emergency_name, 'N/A'),
            ' | Contact: ', COALESCE(si.emergency_contact, 'N/A')
        ) 
        ORDER BY si.last_name ASC 
        SEPARATOR '<br>'
    ) as student_details
    FROM program p
    LEFT JOIN student_enrollments se ON p.id = se.program_id
    LEFT JOIN student_info_tbl si ON se.student_id = si.id
    LEFT JOIN pos_transactions pt ON p.id = pt.program_id AND DATE(pt.transaction_date) BETWEEN '$date_from' AND '$date_to'
    GROUP BY p.id, p.program_name, p.learning_mode
    ORDER BY total_revenue DESC";

// Execute query with error handling
$program_result = mysqli_query($conn, $program_query);

// Check for SQL errors
if (!$program_result) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>";
    echo "<strong>SQL Error:</strong> " . mysqli_error($conn);
    echo "<br><strong>Query:</strong> " . htmlspecialchars($program_query);
    echo "</div>";
    // Use your original query as fallback
    $program_query = "SELECT 
        p.program_name,
        COUNT(DISTINCT se.student_id) as enrolled_students,
        SUM(pt.cash_received) as total_revenue,
        AVG(pt.cash_received) as avg_payment,
        COUNT(pt.id) as total_transactions
        FROM program p
        LEFT JOIN student_enrollments se ON p.id = se.program_id
        LEFT JOIN pos_transactions pt ON p.id = pt.program_id
        WHERE DATE(pt.transaction_date) BETWEEN '$date_from' AND '$date_to'
        GROUP BY p.id, p.program_name";
    $program_result = mysqli_query($conn, $program_query);
}


// Schedule Report Query
$schedule_query = "SELECT 
    s.week_description,
    s.training_date,
    s.start_time,
    s.end_time,
    COUNT(DISTINCT ss.student_id) as enrolled_count,
    p.program_name
    FROM schedules s
    LEFT JOIN student_schedules ss ON s.id = ss.schedule_id
    LEFT JOIN program p ON s.program_id = p.id
    WHERE s.training_date BETWEEN '$date_from' AND '$date_to'
    GROUP BY s.id
    ORDER BY s.training_date";
$schedule_result = mysqli_query($conn, $schedule_query);

// Location Report Query
$location_query = "SELECT 
    region,
    province,
    city,
    COUNT(*)                                   AS student_count,
    AVG(CASE WHEN se.status = 'Enrolled' 
             THEN 1 ELSE 0 END) * 100         AS enrollment_rate,

    /* --- bagong field --- */
    GROUP_CONCAT(
        DISTINCT CONCAT(
            COALESCE(si.first_name, ''), ' ', COALESCE(si.last_name, ''),
            ' | Guardian: ', COALESCE(si.emergency_name, 'N/A'),
            ' | Contact: ',  COALESCE(si.emergency_contact, 'N/A')
        )
        ORDER BY si.last_name ASC
        SEPARATOR '<br>'
    )                                          AS student_details
FROM student_info_tbl si
LEFT JOIN student_enrollments se ON si.id = se.student_id
WHERE si.created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'
      /* optional region filter */
      " . ($region_filter ? "AND region = '$region_filter'" : "") . "
GROUP BY region, province, city
ORDER BY student_count DESC;
";
$location_result = mysqli_query($conn, $location_query);

// Summary Statistics
$total_revenue_query = "SELECT SUM(cash_received) as total FROM pos_transactions WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to'";
$total_students_query = "SELECT COUNT(DISTINCT student_id) as total FROM student_enrollments WHERE DATE(enrollment_date) BETWEEN '$date_from' AND '$date_to'";
$total_programs_query = "SELECT COUNT(*) as total FROM program";
$avg_payment_query = "SELECT AVG(cash_received) as avg FROM pos_transactions WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to'";

$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, $total_revenue_query))['total'] ?? 0;
$total_students = mysqli_fetch_assoc(mysqli_query($conn, $total_students_query))['total'] ?? 0;
$total_programs = mysqli_fetch_assoc(mysqli_query($conn, $total_programs_query))['total'] ?? 0;
$avg_payment = mysqli_fetch_assoc(mysqli_query($conn, $avg_payment_query))['avg'] ?? 0;

// Get unique regions for filter
$regions_query = "SELECT DISTINCT region FROM student_info_tbl WHERE region != '' ORDER BY region";
$regions_result = mysqli_query($conn, $regions_query);
?>

<div class="flex h-screen overflow-hidden flex-row">
    <!-- Sidebar -->
    <div id="sidebar"
        class="bg-white shadow-lg transition-all duration-300 w-[20%] flex flex-col z-10 md:flex hidden fixed min-h-screen print:hidden">
        <div class="px-6 py-5 flex items-center border-b border-gray-100">
            <h1 class="text-xl font-bold text-primary flex items-center">
                <i class='bx bx-plus-medical text-2xl mr-2'></i>
                <span class="text"> CarePro</span>
            </h1>
        </div>
        <?php include "includes/navbar.php" ?>
    </div>

    <!-- Main Content -->
    <div id="content"
        class="flex-1 flex flex-col w-[80%] md:ml-[20%] ml-0 transition-all duration-300 print:w-full print:ml-0">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm print:hidden">
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
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        onclick="printReport()">
                        <i class="fas fa-print mr-1"></i> Print Report
                    </button>
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer" id="logout">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto no-scrollbar bg-gray-50 p-6 print:bg-white print:overflow-visible print:p-0"
            id="printable-content">
            <!-- Print Header (only visible when printing) -->
            <div class="hidden print:block print:mb-6">
                <div class="text-center border-b-2 border-gray-300 pb-4 mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">CarePro Analytics & Reports</h1>
                    <p class="text-gray-600">Report Period: <?= date('M d, Y', strtotime($date_from)) ?> -
                        <?= date('M d, Y', strtotime($date_to)) ?>
                    </p>
                    <p class="text-sm text-gray-500">Generated on: <?= date('M d, Y h:i A') ?></p>
                </div>
            </div>

            <div class="mb-6 print:hidden">
                <h2 class="font-semibold my-2 text-sm">
                    <a href="index.php" class="text-gray-400">Dashboard | </a> Reports
                </h2>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Analytics & Reports</h1>
                <p class="text-gray-600">Comprehensive overview with location and time filters</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6 print:hidden">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input type="date" name="date_from" value="<?= $date_from ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input type="date" name="date_to" value="<?= $date_to ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                        <select name="region"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Regions</option>
                            <?php while ($region = mysqli_fetch_assoc($regions_result)): ?>
                                <option value="<?= $region['region'] ?>" <?= $region_filter == $region['region'] ? 'selected' : '' ?>>
                                    <?= $region['region'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 print:gap-4 print:mb-6">
                <div
                    class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg print:bg-blue-600 print:shadow-none print:border print:border-blue-600 print:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm mb-1 print:text-white">Total Revenue</p>
                            <p class="text-2xl font-bold print:text-xl">₱<?= number_format($total_revenue, 2) ?></p>
                        </div>
                        <i class="fas fa-peso-sign text-3xl text-blue-200 print:text-white print:text-2xl"></i>
                    </div>
                </div>
                <div
                    class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg print:bg-green-600 print:shadow-none print:border print:border-green-600 print:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm mb-1 print:text-white">New Students</p>
                            <p class="text-2xl font-bold print:text-xl"><?= number_format($total_students) ?></p>
                        </div>
                        <i class="fas fa-user-graduate text-3xl text-green-200 print:text-white print:text-2xl"></i>
                    </div>
                </div>
                <div
                    class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg print:bg-purple-600 print:shadow-none print:border print:border-purple-600 print:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm mb-1 print:text-white">Active Programs</p>
                            <p class="text-2xl font-bold print:text-xl"><?= number_format($total_programs) ?></p>
                        </div>
                        <i class="fas fa-book text-3xl text-purple-200 print:text-white print:text-2xl"></i>
                    </div>
                </div>
                <div
                    class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg print:bg-orange-600 print:shadow-none print:border print:border-orange-600 print:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100 text-sm mb-1 print:text-white">Avg Payment</p>
                            <p class="text-2xl font-bold print:text-xl">₱<?= number_format($avg_payment, 2) ?></p>
                        </div>
                        <i class="fas fa-chart-line text-3xl text-orange-200 print:text-white print:text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Income Report -->
            <div
                class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8 print:shadow-none print:border print:border-gray-300 print:mb-6 print:break-inside-avoid">
                <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center print:text-lg print:mb-4">
                    <i class="fas fa-chart-area text-blue-500 mr-2"></i>Daily Income Report
                </h3>
                <div id="incomeChart" class="h-80 print:hidden"></div>

                <!-- Print version of chart data -->
                <div class="hidden print:block print:mb-4">
                    <p class="text-sm text-gray-600 mb-2">Income Summary for the period:</p>
                </div>

                <div class="mt-6 overflow-x-auto print:mt-0 print:overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 print:text-sm">
                        <thead class="bg-gray-50 print:bg-gray-100">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Income</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Transactions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php mysqli_data_seek($income_result, 0);
                            while ($row = mysqli_fetch_assoc($income_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 print:px-3 print:py-2">
                                        <?= date('M d, Y', strtotime($row['date'])) ?>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 print:px-3 print:py-2">
                                        ₱<?= number_format($row['daily_income'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 print:px-3 print:py-2">
                                        <?= $row['transactions'] ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Program Report -->
            <div
                class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8 print:shadow-none print:border print:border-gray-300 print:mb-6 print:break-inside-avoid">
                <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center print:text-lg print:mb-4">
                    <i class="fas fa-graduation-cap text-purple-500 mr-2"></i>Detailed Program Performance Report
                </h3>

                <div class="overflow-x-auto print:overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 print:text-sm">
                        <thead class="bg-gray-50 print:bg-gray-100">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Program Details</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Enrollment Stats</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Financial Performance</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Student Information</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            if ($program_result && mysqli_num_rows($program_result) > 0):
                                while ($row = mysqli_fetch_assoc($program_result)):
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <!-- Program Details Column -->
                                        <td class="px-4 py-4 text-sm print:px-3 print:py-2 align-top">
                                            <div class="font-medium text-gray-900 mb-1">
                                                <?= htmlspecialchars($row['program_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Mode: <?= htmlspecialchars($row['learning_mode']) ?>
                                            </div>
                                        </td>

                                        <!-- Enrollment Stats Column -->
                                        <td class="px-4 py-4 text-sm print:px-3 print:py-2 align-top">
                                            <div class="space-y-1">
                                                <div class="flex items-center">
                                                    <i class="fas fa-users text-blue-500 mr-2 text-xs"></i>
                                                    <span class="font-medium"><?= $row['enrolled_students'] ?></span>
                                                    <span class="text-xs text-gray-500 ml-1">students</span>
                                                </div>
                                                <div class="flex items-center">
                                                    <i class="fas fa-receipt text-green-500 mr-2 text-xs"></i>
                                                    <span class="font-medium"><?= $row['total_transactions'] ?></span>
                                                    <span class="text-xs text-gray-500 ml-1">transactions</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Financial Performance Column -->
                                        <td class="px-4 py-4 text-sm print:px-3 print:py-2 align-top">
                                            <div class="space-y-1">
                                                <div class="flex items-center">
                                                    <i class="fas fa-peso-sign text-green-600 mr-2 text-xs"></i>
                                                    <span class="font-bold text-green-600">
                                                        ₱<?= number_format($row['total_revenue'], 2) ?>
                                                    </span>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Avg: ₱<?= number_format($row['avg_payment'], 2) ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Student Information Column -->
                                        <td class="px-4 py-4 text-sm print:px-3 print:py-2 align-top max-w-md">
                                            <div
                                                class="text-xs text-gray-700 leading-relaxed max-h-40 overflow-y-auto space-y-2">
                                                <?php if ($row['student_details']): ?>
                                                    <?php
                                                    // Split the student_details by <br>
                                                    $students = explode('<br>', $row['student_details']);
                                                    foreach ($students as $student):
                                                        ?>
                                                        <div class="border border-gray-300 rounded-lg p-3 bg-white shadow-sm">
                                                            <?= $student ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic">No students enrolled in selected
                                                        period</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                    </tr>
                                    <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        No program data found for the selected date range.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Footer -->
                <div class="mt-6 pt-4 border-t border-gray-200 print:mt-4 print:pt-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm print:grid-cols-2 print:gap-2">
                        <?php
                        // Reset result pointer to calculate totals only if we have results
                        if ($program_result && mysqli_num_rows($program_result) > 0) {
                            mysqli_data_seek($program_result, 0);
                            $total_programs = 0;
                            $total_students = 0;
                            $total_revenue = 0;
                            $total_transactions = 0;

                            while ($row = mysqli_fetch_assoc($program_result)) {
                                $total_programs++;
                                $total_students += $row['enrolled_students'];
                                $total_revenue += $row['total_revenue'];
                                $total_transactions += $row['total_transactions'];
                            }
                        } else {
                            $total_programs = 0;
                            $total_students = 0;
                            $total_revenue = 0;
                            $total_transactions = 0;
                        }
                        ?>

                        <div class="text-center p-3 bg-blue-50 rounded-lg print:bg-gray-100">
                            <div class="font-bold text-blue-600 text-lg"><?= $total_programs ?></div>
                            <div class="text-xs text-gray-600">Active Programs</div>
                        </div>

                        <div class="text-center p-3 bg-purple-50 rounded-lg print:bg-gray-100">
                            <div class="font-bold text-purple-600 text-lg"><?= $total_students ?></div>
                            <div class="text-xs text-gray-600">Total Students</div>
                        </div>

                        <div class="text-center p-3 bg-green-50 rounded-lg print:bg-gray-100">
                            <div class="font-bold text-green-600 text-lg">₱<?= number_format($total_revenue, 0) ?></div>
                            <div class="text-xs text-gray-600">Total Revenue</div>
                        </div>

                        <div class="text-center p-3 bg-orange-50 rounded-lg print:bg-gray-100">
                            <div class="font-bold text-orange-600 text-lg"><?= $total_transactions ?></div>
                            <div class="text-xs text-gray-600">Transactions</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Report -->
            <!-- <div
                class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8 print:shadow-none print:border print:border-gray-300 print:mb-6 print:break-inside-avoid">
                <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center print:text-lg print:mb-4">
                    <i class="fas fa-calendar-alt text-indigo-500 mr-2"></i>Schedule Utilization Report
                </h3>
                <div class="overflow-x-auto print:overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 print:text-sm">
                        <thead class="bg-gray-50 print:bg-gray-100">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Program</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Week</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Time</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Enrolled</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = mysqli_fetch_assoc($schedule_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 print:px-3 print:py-2">
                                        <?= $row['program_name'] ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 print:px-3 print:py-2">
                                        <?= $row['week_description'] ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 print:px-3 print:py-2">
                                        <?= date('M d, Y', strtotime($row['training_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 print:px-3 print:py-2">
                                        <?= date('h:i A', strtotime($row['start_time'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 print:px-3 print:py-2">
                                        <?= $row['enrolled_count'] ?> students
                                    </td>
                                    <td class="px-6 py-4 print:px-3 print:py-2">
                                        <?php
                                        $status = $row['enrolled_count'] > 0 ? 'Active' : 'Available';
                                        $color = $row['enrolled_count'] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span
                                            class="px-2 py-1 text-xs font-semibold rounded-full <?= $color ?> print:bg-transparent print:border print:border-gray-400 print:text-gray-800"><?= $status ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div> -->

            <!-- Location-Based Report -->
            <div
                class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8 print:shadow-none print:border print:border-gray-300 print:mb-6 print:break-inside-avoid">
                <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center print:text-lg print:mb-4">
                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>Location-Based Student Distribution
                </h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 print:grid-cols-1 print:gap-4">
                    <!-- Your chart remains here -->
                    <div id="locationChart" class="h-80 print:hidden"></div>

                    <div class="overflow-x-auto print:overflow-visible h-[340px]">
                        <table class="min-w-full divide-y divide-gray-200 print:text-sm">
                            <thead class="bg-gray-50 print:bg-gray-100">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                        Region</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                        City</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                        Students</th>

                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase print:px-3 print:py-2">
                                        Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($location_result)): ?>
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-gray-900 print:px-3 print:py-2">
                                            <?= $row['region'] ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500 print:px-3 print:py-2">
                                            <?= $row['city'] ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900 print:px-3 print:py-2">
                                            <?= $row['student_count'] ?>
                                        </td>
                                        <td
                                            class="px-4 py-4 text-sm text-gray-700 align-top print:px-3 print:py-2 max-w-md">
                                            <?php
                                            $region = mysqli_real_escape_string($conn, $row['region']);
                                            $city = mysqli_real_escape_string($conn, $row['city']);
                                            $student_details_query = "
                                    SELECT 
                                        CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS full_name,
                                        COALESCE(emergency_name, 'N/A') AS guardian,
                                        COALESCE(emergency_contact, 'N/A') AS contact
                                    FROM student_info_tbl 
                                    WHERE region = '$region' AND city = '$city'
                                ";
                                            $details_result = mysqli_query($conn, $student_details_query);

                                            if (mysqli_num_rows($details_result) > 0): ?>
                                                <div
                                                    class="flex flex-col gap-2 max-h-40 overflow-y-auto text-xs leading-relaxed">
                                                    <?php while ($detail = mysqli_fetch_assoc($details_result)): ?>
                                                        <div class="border border-gray-300 rounded p-2 shadow-sm bg-gray-50">
                                                            <div><strong>Name:</strong>
                                                                <?= htmlspecialchars($detail['full_name']) ?></div>
                                                            <div><strong>Guardian:</strong>
                                                                <?= htmlspecialchars($detail['guardian']) ?></div>
                                                            <div><strong>Contact:</strong>
                                                                <?= htmlspecialchars($detail['contact']) ?></div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">No students found</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- Print Footer -->
            <div class="hidden print:block print:mt-8 print:pt-4 print:border-t print:border-gray-300">
                <div class="text-center text-sm text-gray-500">
                    <p>© <?= date('Y') ?> CarePro. All rights reserved.</p>
                    <p>This report was generated automatically and contains confidential information.</p>
                </div>
            </div>
        </main>
        <?php include "includes/footer.php" ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Income Chart
    <?php mysqli_data_seek($income_result, 0); ?>
    const incomeData = {
        series: [{
            name: 'Daily Income',
            data: [
                <?php while ($row = mysqli_fetch_assoc($income_result)): ?>
                                                                                                                                                                                                                                                { x: '<?= $row['date'] ?>', y: <?= $row['daily_income'] ?> },
                <?php endwhile; ?>
            ]
        }],
        chart: { type: 'area', height: 320, toolbar: { show: false } },
        colors: ['#3b82f6'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3 } },
        xaxis: { type: 'datetime' },
        yaxis: { labels: { formatter: val => '₱' + val.toLocaleString() } },
        tooltip: { y: { formatter: val => '₱' + val.toLocaleString() } }
    };
    new ApexCharts(document.querySelector("#incomeChart"), incomeData).render();

    // Program Chart
    <?php mysqli_data_seek($program_result, 0); ?>
    const programData = {
        series: [<?php while ($row = mysqli_fetch_assoc($program_result)): ?><?= $row['enrolled_students'] ?>, <?php endwhile; ?>],
        labels: [<?php mysqli_data_seek($program_result, 0);
        while ($row = mysqli_fetch_assoc($program_result)): ?>'<?= $row['program_name'] ?>', <?php endwhile; ?>],
        chart: { type: 'donut', height: 320 },
        colors: ['#8b5cf6', '#06b6d4', '#10b981', '#f59e0b'],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#programChart"), programData).render();

    // Location Chart
    <?php mysqli_data_seek($location_result, 0); ?>
    const locationData = {
        series: [{
            name: 'Students',
            data: [<?php while ($row = mysqli_fetch_assoc($location_result)): ?><?= $row['student_count'] ?>, <?php endwhile; ?>]
        }],
        chart: { type: 'bar', height: 320, toolbar: { show: false } },
        xaxis: {
            categories: [<?php mysqli_data_seek($location_result, 0);
            while ($row = mysqli_fetch_assoc($location_result)): ?>'<?= $row['city'] ?>', <?php endwhile; ?>]
        },
        colors: ['#ef4444'],
        plotOptions: { bar: { horizontal: true } }
    };
    new ApexCharts(document.querySelector("#locationChart"), locationData).render();

    // Print functionality
    // Enhanced Print Function for CarePro Reports
    function printReport() {
        // Create a copy of the printable content
        const printContent = document.getElementById('printable-content').cloneNode(true);

        // Remove print-hidden elements
        const printHiddenElements = printContent.querySelectorAll('.print\\:hidden');
        printHiddenElements.forEach(element => element.remove());

        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        // Enhanced print styles
        const printStyles = `
        <style>
            @media print {
                @page {
                    size: A4;
                    margin: 0.75in;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #000;
                    background: white;
                }
                
                .print-header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 15px;
                    margin-bottom: 20px;
                }
                
                .print-header h1 {
                    font-size: 24px;
                    font-weight: bold;
                    margin: 0 0 10px 0;
                    color: #333;
                }
                
                .print-header p {
                    margin: 5px 0;
                    color: #666;
                }
                
                .summary-cards {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 15px;
                    margin-bottom: 25px;
                }
                
                .summary-card {
                    border: 1px solid #333;
                    padding: 15px;
                    border-radius: 8px;
                    background: #f9f9f9;
                }
                
                .summary-card h3 {
                    font-size: 14px;
                    margin: 0 0 5px 0;
                    color: #333;
                }
                
                .summary-card .value {
                    font-size: 18px;
                    font-weight: bold;
                    color: #000;
                }
                
                .report-section {
                    margin-bottom: 25px;
                    break-inside: avoid;
                }
                
                .report-section h2 {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 0 0 15px 0;
                    color: #333;
                    border-bottom: 1px solid #333;
                    padding-bottom: 5px;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                    font-size: 11px;
                }
                
                th, td {
                    border: 1px solid #333;
                    padding: 8px;
                    text-align: left;
                }
                
                th {
                    background-color: #f0f0f0;
                    font-weight: bold;
                }
                
                .print-footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #333;
                    padding: 10px;
                    background: white;
                }
                
                .page-break {
                    page-break-after: always;
                }
                
                .no-break {
                    break-inside: avoid;
                }
            }
        </style>
    `;

        // Get current date and time
        const now = new Date();
        const dateOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        const currentDateTime = now.toLocaleDateString('en-US', dateOptions);

        // Get filter dates
        const dateFrom = document.querySelector('input[name="date_from"]')?.value || '';
        const dateTo = document.querySelector('input[name="date_to"]')?.value || '';

        // Format dates for display
        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        };

        // Create print document
        const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>CarePro Analytics Report</title>
            <meta charset="UTF-8">
            ${printStyles}
        </head>
        <body>
            <div class="print-header">
                <h1>CarePro Analytics & Reports</h1>
                <p><strong>Report Period:</strong> ${formatDate(dateFrom)} - ${formatDate(dateTo)}</p>
                <p><strong>Generated on:</strong> ${currentDateTime}</p>
            </div>
            
            <div class="print-content">
                ${printContent.innerHTML}
            </div>
            
            <div class="print-footer">
                <p>© ${new Date().getFullYear()} CarePro. All rights reserved. | This report contains confidential information.</p>
            </div>
        </body>
        </html>
    `;

        // Write content to print window
        printWindow.document.write(printHTML);
        printWindow.document.close();

        // Wait for content to load, then print
        printWindow.onload = function () {
            setTimeout(() => {
                printWindow.print();
                printWindow.onafterprint = function () {
                    printWindow.close();
                };
            }, 500);
        };
    }

    // Alternative simplified print function (if you prefer to keep it simple)
    function printReportSimple() {
        // Show loading indicator
        const loadingIndicator = document.createElement('div');
        loadingIndicator.innerHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 8px; z-index: 9999;">
            <i class="fas fa-spinner fa-spin"></i> Preparing report for printing...
        </div>
    `;
        document.body.appendChild(loadingIndicator);

        // Hide elements that shouldn't be printed
        const elementsToHide = document.querySelectorAll('.print\\:hidden');
        const sidebar = document.getElementById('sidebar');
        const header = document.querySelector('header');

        // Store original display values
        const originalDisplays = [];
        elementsToHide.forEach(el => {
            originalDisplays.push(el.style.display);
            el.style.display = 'none';
        });

        if (sidebar) {
            sidebar.style.display = 'none';
        }
        if (header) {
            header.style.display = 'none';
        }

        // Adjust content for printing
        const content = document.getElementById('content');
        if (content) {
            content.style.width = '100%';
            content.style.marginLeft = '0';
        }

        // Remove loading indicator
        setTimeout(() => {
            document.body.removeChild(loadingIndicator);

            // Print
            window.print();

            // Restore original styles after printing
            setTimeout(() => {
                elementsToHide.forEach((el, index) => {
                    el.style.display = originalDisplays[index];
                });

                if (sidebar) {
                    sidebar.style.display = '';
                }
                if (header) {
                    header.style.display = '';
                }
                if (content) {
                    content.style.width = '';
                    content.style.marginLeft = '';
                }
            }, 1000);
        }, 500);
    }

    // Export to PDF function (requires jsPDF library)
    function exportToPDF() {
        // Check if jsPDF is available
        if (typeof window.jsPDF === 'undefined') {
            alert('PDF export library not loaded. Please include jsPDF library.');
            return;
        }

        const { jsPDF } = window.jsPDF;
        const doc = new jsPDF();

        // Add title
        doc.setFontSize(20);
        doc.text('CarePro Analytics Report', 20, 20);

        // Add date range
        const dateFrom = document.querySelector('input[name="date_from"]')?.value || '';
        const dateTo = document.querySelector('input[name="date_to"]')?.value || '';
        doc.setFontSize(12);
        doc.text(`Report Period: ${dateFrom} - ${dateTo}`, 20, 35);
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 20, 45);

        // Get summary data
        const summaryCards = document.querySelectorAll('.summary-card, [class*="gradient"]');
        let yPosition = 60;

        summaryCards.forEach(card => {
            const label = card.querySelector('p')?.textContent || '';
            const value = card.querySelector('.text-2xl, .text-xl')?.textContent || '';

            if (label && value) {
                doc.text(`${label}: ${value}`, 20, yPosition);
                yPosition += 10;
            }
        });

        // Save the PDF
        doc.save('carepro-report.pdf');
    }

    // Add event listeners when document is ready
    document.addEventListener('DOMContentLoaded', function () {
        // Replace existing print button functionality
        const printButton = document.querySelector('[onclick="printReport()"]');
        if (printButton) {
            printButton.removeAttribute('onclick');
            printButton.addEventListener('click', printReport);
        }

        // Add keyboard shortcut for printing (Ctrl+P)
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printReport();
            }
        });
    });

    // Additional utility functions for better printing experience
    function showPrintPreview() {
        const previewWindow = window.open('', '_blank', 'width=800,height=600');
        const printContent = document.getElementById('printable-content').innerHTML;

        previewWindow.document.write(`
        <html>
        <head>
            <title>Print Preview - CarePro Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .preview-header { text-align: center; margin-bottom: 20px; }
                .preview-note { 
                    background: #f0f8ff; 
                    padding: 10px; 
                    border-left: 4px solid #0066cc; 
                    margin-bottom: 20px; 
                }
            </style>
        </head>
        <body>
            <div class="preview-header">
                <h2>Print Preview</h2>
                <button onclick="window.print()">Print This Preview</button>
                <button onclick="window.close()">Close Preview</button>
            </div>
            <div class="preview-note">
                <strong>Note:</strong> This preview shows how your report will look when printed.
            </div>
            ${printContent}
        </body>
        </html>
    `);
    }
    // Logout functionality
    document.getElementById('logout').addEventListener('click', function () {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = '../auth/logout.php';
        }
    });

    // Sidebar toggle
    document.getElementById('toggleSidebar').addEventListener('click', function () {
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        sidebar.classList.toggle('hidden');
        content.classList.toggle('ml-0');
        content.classList.toggle('ml-[20%]');
        content.classList.toggle('w-full');
        content.classList.toggle('w-[80%]');
    });
</script>