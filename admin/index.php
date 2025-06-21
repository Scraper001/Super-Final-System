<?php include "../connection/connection.php";
include "includes/header.php";
$conn = con();

// Define the function to get recent announcements
function getRecentAnnouncements($limit = 3)
{
    $conn = con();
    $query = "SELECT announcement_id, user_no, `group`, message 
              FROM announcement
              ORDER BY announcement_id DESC
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    $result = $stmt->get_result();
    $announcements = [];

    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $announcements;
}

// Function to get monthly revenue
function getMonthlyRevenue()
{
    $conn = con();
    $query = "SELECT SUM(cash_received) as monthly FROM pos_transactions WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['monthly'] ?? 0.00;
    }
    return 0.00;
}

// Function to get program statistics
function getProgramStats()
{
    $conn = con();
    $query = "SELECT COUNT(*) as total_programs FROM program";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['total_programs'] ?? 0;
    }
    return 0;
}

// Function to get pending enrollments
function getPendingEnrollments()
{
    $conn = con();
    $query = "SELECT COUNT(*) as pending FROM student_info_tbl WHERE enrollment_status = ''";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['pending'] ?? 0;
    }
    return 0;
}

// Function to get completed students
function getCompletedStudents()
{
    $conn = con();
    $query = "SELECT COUNT(*) as completed FROM student_info_tbl WHERE enrollment_status = 'Completed'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['completed'] ?? 0;
    }
    return 0;
}

// Function to get dropped students
function getDroppedStudents()
{
    $conn = con();
    $query = "SELECT COUNT(*) as dropped FROM student_info_tbl WHERE enrollment_status = 'Dropped'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['dropped'] ?? 0;
    }
    return 0;
}

// Function to get active teachers
function getActiveTeachers()
{
    $conn = con();
    $query = "SELECT COUNT(*) as total_teachers FROM teachers";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['total_teachers'] ?? 0;
    }
    return 0;
}

// Function to get recent transactions
function getRecentTransactions($limit = 5)
{
    $conn = con();
    $query = "SELECT pt.*, si.first_name, si.last_name, p.program_name 
              FROM pos_transactions pt
              LEFT JOIN student_info_tbl si ON pt.student_id = si.id
              LEFT JOIN program p ON pt.program_id = p.id
              ORDER BY pt.transaction_date DESC
              LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    $result = $stmt->get_result();
    $transactions = [];

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $transactions;
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


                    <a href="announcement.php"
                        class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer">
                        <i class="fa-solid fa-bullhorn mr-1"></i>
                        Create Announcements
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
        <main class="flex-1 overflow-y-auto no-scrollbar bg-gray-50 p-6">
            <!-- Dashboard Overview -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Dashboard Overview</h2>
                <div class="text-sm text-gray-500">
                    Last updated: <?php echo date('F j, Y - g:i A'); ?>
                </div>
            </div>

            <!-- Student Statistics Section -->
            <section id="student-stats" class="mb-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Student Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Enrolled Students -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Enrolled Students</p>
                                <h3 class="text-3xl font-bold text-green-600">
                                    <?php
                                    $enrolled_query = "SELECT COUNT(*) as count FROM student_info_tbl WHERE enrollment_status = 'Enrolled'";
                                    $enrolled_result = mysqli_query($conn, $enrolled_query);
                                    $enrolled_count = mysqli_fetch_assoc($enrolled_result);
                                    echo $enrolled_count['count'];
                                    ?>
                                </h3>
                                <p class="text-xs text-green-500 mt-2 flex items-center">
                                    <i class='bx bx-up-arrow-alt'></i>
                                    <span>Active Learners</span>
                                </p>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <i class="fa-solid fa-user-graduate text-2xl text-green-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Reserved Students -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Reserved Students</p>
                                <h3 class="text-3xl font-bold text-blue-600">
                                    <?php
                                    $reserved_query = "SELECT COUNT(*) as count FROM student_info_tbl WHERE enrollment_status = 'Reserved'";
                                    $reserved_result = mysqli_query($conn, $reserved_query);
                                    $reserved_count = mysqli_fetch_assoc($reserved_result);
                                    echo $reserved_count['count'];
                                    ?>
                                </h3>
                                <p class="text-xs text-blue-500 mt-2 flex items-center">
                                    <i class='bx bx-time'></i>
                                    <span>Pending Start</span>
                                </p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <i class="fa-solid fa-clock text-2xl text-blue-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Students -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Completed Students</p>
                                <h3 class="text-3xl font-bold text-purple-600">
                                    <?php echo getCompletedStudents(); ?>
                                </h3>
                                <p class="text-xs text-purple-500 mt-2 flex items-center">
                                    <i class='bx bx-medal'></i>
                                    <span>Graduates</span>
                                </p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg">
                                <i class="fa-solid fa-certificate text-2xl text-purple-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Enrollments -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-orange-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Pending Enrollments</p>
                                <h3 class="text-3xl font-bold text-orange-600">
                                    <?php echo getPendingEnrollments(); ?>
                                </h3>
                                <p class="text-xs text-orange-500 mt-2 flex items-center">
                                    <i class='bx bx-hourglass'></i>
                                    <span>Awaiting Action</span>
                                </p>
                            </div>
                            <div class="bg-orange-50 p-3 rounded-lg">
                                <i class="fa-solid fa-user-plus text-2xl text-orange-500"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Financial & System Statistics -->
            <section id="financial-stats" class="mb-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Financial & System Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Daily Revenue -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Daily Revenue</p>
                                <h3 class="text-2xl font-bold text-green-600">
                                    ₱<?php
                                    $rev = "SELECT SUM(cash_received) as daily FROM pos_transactions WHERE DATE(transaction_date) = CURDATE()";
                                    $rev_result = mysqli_query($conn, $rev);
                                    if ($rev_result && mysqli_num_rows($rev_result) > 0) {
                                        $rev_count = mysqli_fetch_assoc($rev_result);
                                        echo number_format($rev_count['daily'] ?? 0, 2);
                                    } else {
                                        echo "0.00";
                                    }
                                    ?>
                                </h3>
                                <p class="text-xs text-green-500 mt-2">Today's Earnings</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <i class="fa-solid fa-coins text-2xl text-green-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Revenue -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Monthly Revenue</p>
                                <h3 class="text-2xl font-bold text-blue-600">
                                    ₱<?php echo number_format(getMonthlyRevenue(), 2); ?>
                                </h3>
                                <p class="text-xs text-blue-500 mt-2">This Month</p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <i class="fa-solid fa-chart-line text-2xl text-blue-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Programs -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-indigo-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Total Programs</p>
                                <h3 class="text-3xl font-bold text-indigo-600">
                                    <?php echo getProgramStats(); ?>
                                </h3>
                                <p class="text-xs text-indigo-500 mt-2">Available Courses</p>
                            </div>
                            <div class="bg-indigo-50 p-3 rounded-lg">
                                <i class="fa-solid fa-book text-2xl text-indigo-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Active Teachers -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-teal-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Active Teachers</p>
                                <h3 class="text-3xl font-bold text-teal-600">
                                    <?php echo getActiveTeachers(); ?>
                                </h3>
                                <p class="text-xs text-teal-500 mt-2">Faculty Members</p>
                            </div>
                            <div class="bg-teal-50 p-3 rounded-lg">
                                <i class="fa-solid fa-chalkboard-teacher text-2xl text-teal-500"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Stats Overview -->
            <section id="quick-overview" class="mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Quick Overview</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-800">
                                <?php
                                $total_students = "SELECT COUNT(*) as total FROM student_info_tbl";
                                $total_result = mysqli_query($conn, $total_students);
                                $total_count = mysqli_fetch_assoc($total_result);
                                echo $total_count['total'];
                                ?>
                            </div>
                            <div class="text-sm text-gray-500">Total Students</div>
                        </div>

                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-800">
                                <?php
                                $users_query = "SELECT COUNT(*) as count FROM users";
                                $users_result = mysqli_query($conn, $users_query);
                                $users_count = mysqli_fetch_assoc($users_result);
                                echo $users_count['count'];
                                ?>
                            </div>
                            <div class="text-sm text-gray-500">System Users</div>
                        </div>

                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-800">
                                <?php echo getDroppedStudents(); ?>
                            </div>
                            <div class="text-sm text-gray-500">Dropped Students</div>
                        </div>

                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-800">
                                <?php
                                $transactions_query = "SELECT COUNT(*) as total FROM pos_transactions";
                                $transactions_result = mysqli_query($conn, $transactions_query);
                                $transactions_count = mysqli_fetch_assoc($transactions_result);
                                echo $transactions_count['total'];
                                ?>
                            </div>
                            <div class="text-sm text-gray-500">Total Transactions</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Activity Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Recent Announcements -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Recent Announcements</h3>
                        <button class="text-sm text-primary hover:underline" data-bs-toggle="modal"
                            data-bs-target="#viewAnnouncementsModal">
                            View All
                        </button>
                    </div>

                    <?php
                    $announcements = getRecentAnnouncements(3);
                    if (empty($announcements)):
                        ?>
                        <div class="text-center py-6 text-gray-500">
                            <i class="fa-solid fa-bullhorn text-4xl mb-2 text-gray-300"></i>
                            <p class="mb-3">No announcements yet</p>
                            <button class="px-4 py-2 bg-primary text-white rounded hover:bg-opacity-90"
                                data-bs-toggle="modal" data-bs-target="#announcementModal">
                                Create First Announcement
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="border-l-4 border-blue-500 bg-blue-50 p-4 rounded">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="text-sm font-semibold text-gray-800">
                                            Announcement #<?php echo htmlspecialchars($announcement['announcement_id']); ?>
                                        </h4>
                                        <span class="text-xs text-gray-500">
                                            User: <?php echo htmlspecialchars($announcement['user_no']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-700 mb-2">
                                        <?php echo htmlspecialchars(substr($announcement['message'], 0, 100)) . (strlen($announcement['message']) > 100 ? '...' : ''); ?>
                                    </p>
                                    <div class="text-xs text-gray-500">
                                        Group: <?php echo htmlspecialchars($announcement['group']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Recent Transactions</h3>
                        <a href="#" class="text-sm text-primary hover:underline">View All</a>
                    </div>

                    <?php
                    $recent_transactions = getRecentTransactions(5);
                    if (empty($recent_transactions)):
                        ?>
                        <div class="text-center py-6 text-gray-500">
                            <i class="fa-solid fa-receipt text-4xl mb-2 text-gray-300"></i>
                            <p>No recent transactions</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                    <div>
                                        <div class="font-medium text-sm">
                                            <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo htmlspecialchars($transaction['program_name'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-green-600">
                                            ₱<?php echo number_format($transaction['total_amount'], 2); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo ucfirst($transaction['enrollment_status']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>