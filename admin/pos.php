<?php include "../connection/connection.php";
include "includes/header.php";
$conn = con(); ?>

<?php
// Pagination settings
$records_per_page = 6; // Reduced for card layout
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';

if (!empty($search)) {
    $escaped_search = mysqli_real_escape_string($conn, $search); // Avoid SQL injection
    $search_condition = " AND (
        CONCAT(si.first_name, ' ', si.last_name) LIKE '%$escaped_search%' OR 
        si.id LIKE '%$escaped_search%' OR
        p.program_name LIKE '%$escaped_search%' OR
        pt.payment_type LIKE '%$escaped_search%' OR 
        pt.status LIKE '%$escaped_search%' OR 
        pt.learning_mode LIKE '%$escaped_search%'
    )";
}

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total 
FROM pos_transactions pt
LEFT JOIN student_info_tbl si ON pt.student_id = si.id
LEFT JOIN program p ON pt.program_id = p.id
$search_condition";

$count_result = mysqli_query($conn, $count_query);
if (!$count_result) {
    die("Error in count query: " . mysqli_error($conn));
}

$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Enhanced main query with JOINs to get all necessary data
$query = "SELECT 
    si.id AS student_number,
    si.first_name,
    si.middle_name,
    si.last_name,
    si.user_email,
    si.user_contact,
    si.photo_path,
    COUNT(pt.id) as transaction_count,
    
    -- FIXED: Calculate actual balance considering promo discounts
    SUM(pt.total_amount) as total_amount_sum,
    SUM(pt.cash_received) as total_paid,
    SUM(CASE 
        WHEN pt.promo_discount > 0 THEN pt.promo_discount 
        ELSE 0 
    END) as total_promo_discount,
    
    -- FIXED: Calculate remaining balance = (Total Amount - Promo Discount) - Total Paid
    (SUM(pt.total_amount) - SUM(CASE WHEN pt.promo_discount > 0 THEN pt.promo_discount ELSE 0 END)) - SUM(pt.cash_received) as actual_balance,
    
    GROUP_CONCAT(
        CONCAT(
            pt.id, '|',
            pt.transaction_date, '|',
            pt.program_id, '|',
            pt.learning_mode, '|',
            IFNULL(pt.package_name, 'Regular'), '|',
            pt.payment_type, '|',
            pt.selected_schedules, '|',
            IFNULL(pt.subtotal, 0), '|',
            IFNULL(pt.promo_discount, 0), '|',
            IFNULL(pt.system_fee, 0), '|',
            pt.total_amount, '|',
            pt.cash_received, '|',
            IFNULL(pt.change_amount, 0), '|',
            IFNULL(pt.balance, 0), '|',
            pt.status, '|',
            pt.enrollment_status, '|',
            IFNULL(p.program_name, ''), '|',
            IFNULL(p.total_tuition, '0'), '|',
            -- FIXED: Add calculated balance after promo
            (pt.total_amount - IFNULL(pt.promo_discount, 0)) - pt.cash_received
        ) 
        ORDER BY pt.transaction_date DESC 
        SEPARATOR ';;'
    ) as transactions_data,
    MAX(pt.transaction_date) as latest_transaction_date,
    GROUP_CONCAT(DISTINCT pt.status ORDER BY pt.transaction_date DESC) as all_statuses,
    
    -- FIXED: Get the original program total and promo info for proper calculation
    MAX(p.total_tuition) as program_total_tuition,
    SUM(DISTINCT CASE WHEN pt.promo_discount > 0 THEN pt.promo_discount ELSE 0 END) as max_promo_discount
    
FROM student_info_tbl si
INNER JOIN pos_transactions pt ON si.id = pt.student_id
LEFT JOIN program p ON pt.program_id = p.id
WHERE 1=1
$search_condition
GROUP BY si.id, si.first_name, si.middle_name, si.last_name, si.user_email, si.user_contact, si.photo_path
ORDER BY MAX(pt.transaction_date) DESC
LIMIT $records_per_page OFFSET $offset";

$result = mysqli_query($conn, $query);
// if (!$result) {
//     die("Error in main query: " . mysqli_error($conn));
// }

// Summary statistics
$summary_query = "SELECT 
    COUNT(*) as total_transactions,
    SUM(pt.cash_received) as total_revenue,
    MIN(pt.balance) as total_balance,
    COUNT(CASE WHEN pt.status = 'Active' THEN 1 END) as active_transactions
FROM pos_transactions pt
LEFT JOIN student_info_tbl si ON pt.student_id = si.id
LEFT JOIN program p ON pt.program_id = p.id
$search_condition";

$summary_result = mysqli_query($conn, $summary_query);
if (!$summary_result) {
    die("Error in summary query: " . mysqli_error($conn));
}

$summary = mysqli_fetch_assoc($summary_result);

// Function to get schedules for a transaction
// FIXED: Function to parse transaction data with promo discount calculation
function parseTransactionData($transactions_data)
{
    if (empty($transactions_data))
        return [];

    $transactions = explode(';;', $transactions_data);
    $parsed = [];

    foreach ($transactions as $transaction) {
        $parts = explode('|', $transaction);
        if (count($parts) >= 19) { // Updated count for new field
            $total_amount = floatval($parts[10]);
            $promo_discount = floatval($parts[8]);
            $cash_received = floatval($parts[11]);

            // FIXED: Calculate actual balance after promo discount
            $actual_total_after_promo = $total_amount - $promo_discount;
            $calculated_balance = max(0, $actual_total_after_promo - $cash_received);

            $parsed[] = [
                'id' => $parts[0],
                'transaction_date' => $parts[1],
                'program_id' => $parts[2],
                'learning_mode' => $parts[3],
                'package_name' => $parts[4],
                'payment_type' => $parts[5],
                'selected_schedules' => $parts[6],
                'subtotal' => $parts[7],
                'promo_discount' => $promo_discount,
                'system_fee' => $parts[9],
                'total_amount' => $total_amount,
                'cash_received' => $cash_received,
                'change_amount' => $parts[12],
                'balance' => $parts[13], // Original balance from DB
                'calculated_balance' => $calculated_balance, // FIXED: Calculated balance after promo
                'actual_total_after_promo' => $actual_total_after_promo, // FIXED: Amount after promo discount
                'status' => $parts[14],
                'enrollment_status' => $parts[15],
                'program_name' => $parts[16],
                'total_tuition' => $parts[17],
                'is_fully_paid' => $calculated_balance <= 0.01 // FIXED: Check if actually paid
            ];
        }
    }

    return $parsed;
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
        <main class="flex-1 overflow-y-auto no-scrollbar bg-gray-50 p-6">
            <!-- Dashboard Overview -->
            <h2 class="font-semibold my-2 text-sm">
                <a href="index.php" class="text-gray-400">Dashboard | </a>POS - Student Payments
            </h2>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Transactions</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo number_format($summary['total_transactions']); ?>
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fa-solid fa-receipt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                            <p class="text-2xl font-bold text-green-600">
                                ₱<?php echo number_format($summary['total_revenue'], 2); ?></p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fa-solid fa-peso-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Outstanding Balance</p>
                            <p class="text-2xl font-bold text-orange-600">
                                ₱<?php echo number_format($summary['total_balance'], 2); ?></p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-full">
                            <i class="fa-solid fa-clock text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Transactions</p>
                            <p class="text-2xl font-bold text-purple-600">
                                <?php echo number_format($summary['active_transactions']); ?>
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fa-solid fa-check-circle text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions and Search Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Student Payment Records</h3>
                        <p class="text-sm text-gray-600">Comprehensive view of all student transactions and schedules
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="search_student.php"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fa-solid fa-plus mr-2"></i>Add POS Record
                        </a>
                    </div>
                </div>

                <!-- Search Form -->
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <form method="GET" class="flex items-center space-x-2">
                        <div class="relative flex-1 max-w-md">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search by student name, program, or transaction details..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i
                                class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Search
                        </button>
                        <?php if (!empty($search)): ?>
                                <a href="?"
                                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                    Clear
                                </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Student Cards Grid -->
            <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                $transactions = parseTransactionData($row['transactions_data']);
                                $primary_status = explode(',', $row['all_statuses'])[0]; // Get most recent status
                        
                                // Determine overall status styling
                                $status_classes = [
                                    'Active' => 'bg-green-100 text-green-800 border-green-200',
                                    'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'Cancelled' => 'bg-red-100 text-red-800 border-red-200',
                                    'Reserved' => 'bg-blue-100 text-blue-800 border-blue-200'
                                ];
                                $status_class = $status_classes[$primary_status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                ?>

                                <div
                                    class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                                    <!-- Student Header (same as before) -->
                                    <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($row['photo_path']) && file_exists("functions/" . $row['photo_path'])): ?>
                                                        <img src="functions/<?php echo $row['photo_path']; ?>" alt="Student Photo"
                                                            class="h-16 w-16 rounded-full object-cover border-4 border-white shadow-sm">
                                                <?php else: ?>
                                                        <div
                                                            class="h-16 w-16 rounded-full bg-blue-500 flex items-center justify-center border-4 border-white shadow-sm">
                                                            <span class="text-xl font-bold text-white">
                                                                <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-lg font-semibold text-gray-900 truncate">
                                                    <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                                                </h3>
                                                <p class="text-sm text-gray-600">ID: <?php echo $row['student_number']; ?></p>
                                                <div class="flex items-center mt-1 space-x-2">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $status_class; ?>">
                                                        <?php echo $primary_status; ?>
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <?php echo $row['transaction_count']; ?>
                                                        Transaction<?php echo $row['transaction_count'] > 1 ? 's' : ''; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary Information -->
                                    <div class="p-6">
                                        <div class="grid grid-cols-2 gap-4 mb-4">

                                        </div>

                                        <!-- Recent Transactions -->
                                        <div class="border-t pt-4">
                                            <div class="flex justify-between items-center mb-3">
                                                <span class="text-sm font-medium text-gray-700">Recent Transactions</span>
                                                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                                    onclick="toggleTransactions('student_<?php echo $row['student_number']; ?>')">
                                                    View All
                                                </button>
                                            </div>

                                            <!-- Show only first 2 transactions by default -->
                                            <div class="space-y-2">
                                                <?php foreach (array_slice($transactions, 0, 2) as $transaction): ?>
                                                        <div class="bg-gray-50 rounded-lg p-3">
                                                            <div class="flex justify-between items-start">
                                                                <div class="flex-1">
                                                                    <p class="text-md font-medium text-gray-900">
                                                                        <?php echo $transaction['program_name']; ?> <span
                                                                            class="text-sm px-2 py-1 rounded-xl bg-sky-500 text-white">
                                                                            <?php echo $transaction['learning_mode']; ?>
                                                                        </span>
                                                                    </p>
                                                                    <p class="text-xs text-gray-500">
                                                                        <?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?>
                                                                        •
                                                                        <?php echo $transaction['payment_type']; ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-right">
                                                                    <p class="text-sm font-medium text-gray-900">
                                                                        ₱<?php echo number_format($transaction['total_amount'], 2); ?></p>
                                                                    <?php if ($transaction['balance'] > 0): ?>
                                                                            <p class="text-xs text-red-600">Balance:
                                                                                ₱<?php echo number_format($transaction['balance'], 2); ?></p>
                                                                    <?php else: ?>
                                                                            <p class="text-xs text-green-600">Paid</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- Hidden detailed transactions -->
                                            <div id="student_<?php echo $row['student_number']; ?>" class="hidden mt-3 space-y-2">
                                                <?php foreach (array_slice($transactions, 2) as $transaction): ?>
                                                        <div class="bg-gray-50 rounded-lg p-3">
                                                            <div class="flex justify-between items-start">
                                                                <div class="flex-1">
                                                                    <p class="text-sm font-medium text-gray-900">
                                                                        <?php echo $transaction['program_name']; ?>
                                                                    </p>
                                                                    <p class="text-xs text-gray-500">
                                                                        <?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?>
                                                                        •
                                                                        <?php echo $transaction['learning_mode']; ?> •
                                                                        <?php echo $transaction['payment_type']; ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-right">
                                                                    <p class="text-sm font-medium text-gray-900">
                                                                        ₱<?php echo number_format($transaction['total_amount'], 2); ?></p>
                                                                    <span
                                                                        class="text-xs px-2 py-1 rounded-full <?php echo $status_classes[$transaction['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                                        <?php echo $transaction['status']; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Actions (modified) -->
                                    <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                                        <div class="flex items-center space-x-2">
                                            <a href="pos_adding.php?student_id=<?php echo $row['student_number'] ?>"
                                                class="text-blue-600 hover:text-blue-800 transition-colors p-2 rounded-full hover:bg-blue-100"
                                                title="View All Transactions">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>

                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Last: <?php echo date('M d, Y', strtotime($row['latest_transaction_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                        <?php endwhile; ?>
                    </div>
            <?php else: ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fa-solid fa-receipt text-gray-300 text-6xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions found</h3>
                            <p class="text-gray-500 mb-6">
                                <?php echo !empty($search) ? 'Try adjusting your search criteria.' : 'Get started by creating your first transaction.'; ?>
                            </p>
                            <a href="search_student.php"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fa-solid fa-plus mr-2"></i>Add First Transaction
                            </a>
                        </div>
                    </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                                    <span
                                        class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span>
                                    of
                                    <span class="font-medium"><?php echo $total_records; ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i == $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
            <?php endif; ?>
        </main>
        <?php include "includes/footer.php" ?>
    </div>
</div>
<script>
    function toggleTransactions(studentId) {
        const element = document.getElementById(studentId);
        const button = element.previousElementSibling.querySelector('button');

        if (element.classList.contains('hidden')) {
            element.classList.remove('hidden');
            button.textContent = 'Show Less';
        } else {
            element.classList.add('hidden');
            button.textContent = 'View All';
        }
    }
</script>
<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>