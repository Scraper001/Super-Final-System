<?php

include "../connection/connection.php";
include "includes/header.php";
$conn = con();

// List all your tables in an array
$tables = [
    'announcement',
    'logs',
    'pos_transactions',
    'program',
    'promo',
    'schedules',
    'schedule_locks',
    'student_enrollments',
    'student_info_tbl',
    'student_schedules',
    'teachers',
    'tuitionfee',
    'users'
];

// Initialize an array to hold totals
$totals = [];

// Loop through tables and get row counts
foreach ($tables as $table) {
    $sql = "SELECT COUNT(*) AS total FROM `$table`";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $totals[$table] = $row['total'];
    } else {
        $totals[$table] = 0;
    }
}

// Now you can access totals like this:
// $totals['announcement'], $totals['logs'], etc.

// Example: display all totals


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


            <div class="w-full border-2 min-h-screen">

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