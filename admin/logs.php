<?php
include "../connection/connection.php";
include "includes/header.php";
// Initialize variables
$conn = con();
$logs = [];
$error_message = '';
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 10;
$offset = ($current_page - 1) * $records_per_page;

try {
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM logs";
    $count_result = $conn->query($count_sql);
    $total_records = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch logs with pagination and ordering
    $sql = "SELECT user_id, activity, dnt FROM logs ORDER BY dnt DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    $stmt->close();

} catch (Exception $e) {
    $error_message = "Error fetching logs: " . $e->getMessage();
}

$conn->close();
?>

<div class="flex h-screen overflow-hidden flex-row">
    <!-- Sidebar -->
    <div id="sidebar"
        class="bg-white shadow-xl transition-all duration-300 w-[20%] flex flex-col z-10 md:flex hidden fixed min-h-screen border-r border-gray-200">
        <!-- Logo -->
        <div class="px-6 py-5 flex items-center bg-gradient-to-r from-blue-50 to-indigo-50">
            <h1 class="text-xl font-bold text-primary flex items-center">
                <i class='bx bx-plus-medical text-2xl mr-2 text-blue-600'></i>
                <span class="text-gray-800">CarePro</span>
            </h1>
        </div>
        <?php include "includes/navbar.php" ?>
    </div>

    <!-- Main Content -->
    <div id="content" class="flex-1 flex flex-col w-[80%] md:ml-[20%] ml-0 transition-all duration-300 bg-gray-50">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="toggleSidebar"
                        class="md:hidden flex text-gray-500 hover:text-blue-600 focus:outline-none mr-4 transition-colors">
                        <i class='bx bx-menu text-2xl'></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800">Activity Logs</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="announcement.php"
                        lass="text-gray-500 hover:text-primary focus:outline-none cursor-pointer">
                        <i class="fa-solid fa-bullhorn mr-1"></i>
                        Create Announcement
                    </a>

                    <button
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200"
                        id="logout" data-swal-template="#my-template">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i>
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Breadcrumb -->
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 text-sm text-gray-600">
                    <li><a href="index.php" class="hover:text-blue-600 transition-colors">Dashboard</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li class="text-gray-800 font-medium">Activity Logs</li>
                </ol>
            </nav>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Activities</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_records); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Current Page</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $current_page; ?> of
                                <?php echo $total_pages; ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-alt text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Records per Page</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $records_per_page; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Activity Logs Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Activity Logs</h3>
                    <p class="text-sm text-gray-600 mt-1">Monitor system activities and user interactions</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-user text-gray-500"></i>
                                        <span>User ID</span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-activity text-gray-500"></i>
                                        <span>Activity</span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-clock text-gray-500"></i>
                                        <span>Date & Time</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No activities found</h3>
                                            <p class="text-gray-600">There are no activity logs to display at this time.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $index => $log): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                                    <?php echo strtoupper(substr(htmlspecialchars($log['user_id']), 0, 2)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($log['user_id']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($log['activity']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php
                                                $date = new DateTime($log['dnt']);
                                                echo $date->format('M j, Y');
                                                ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $date->format('g:i A'); ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <?php echo (($current_page - 1) * $records_per_page) + 1; ?> to
                                <?php echo min($current_page * $records_per_page, $total_records); ?> of
                                <?php echo $total_records; ?> results
                            </div>

                            <nav class="flex items-center space-x-2">
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?>"
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-chevron-left mr-1"></i>Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>"
                                        class="px-3 py-2 text-sm font-medium <?php echo $i == $current_page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg transition-colors">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?>"
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        Next<i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    /* Custom scrollbar */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Smooth transitions */
    * {
        transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }

    /* Enhanced hover effects */
    tr:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Card hover effects */
    .bg-white:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Button hover animations */
    button:hover {
        transform: translateY(-1px);
    }

    /* Loading animation for empty states */
    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>