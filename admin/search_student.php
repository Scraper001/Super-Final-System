<?php include "../connection/connection.php";
include "includes/header.php";
$conn = con(); ?>

<?php
// Handle AJAX search request
if (isset($_GET['search_ajax'])) {
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

    $sql = "SELECT id, student_number, CONCAT(first_name, ' ', last_name) as full_name, 
        user_email, user_contact, programs, enrollment_status 
        FROM student_info_tbl 
        WHERE (student_number LIKE ? OR 
               CONCAT(first_name, ' ', last_name) LIKE ? OR 
               user_email LIKE ? OR 
               programs LIKE ?) 
        AND (enrollment_status IS NULL OR enrollment_status = '')
        ORDER BY created_at DESC 
        LIMIT 10";


    $search_param = "%$search_term%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($students);
    exit;
}

// Get initial student list (recent 10 students)
$sql = "SELECT id, student_number, CONCAT(first_name, ' ', last_name) as full_name, 
        user_email, user_contact, programs, enrollment_status 
        FROM student_info_tbl 
        WHERE enrollment_status IS NULL OR enrollment_status = ''
        ORDER BY created_at DESC 
        LIMIT 10";

$result = $conn->query($sql);
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
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
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#announcementModal">
                        <i class="fa-solid fa-bullhorn mr-1"></i>
                        New Announcement
                    </button>

                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#viewAnnouncementsModal">
                        <i class="fa-solid fa-envelope mr-1"></i>
                        View Announcements
                    </button>

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

            <!-- Table Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Back Button -->
                <div class="p-6">
                    <a type="submit" href="pos.php"
                        class="cursor-pointer px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-2"></i>Back
                    </a>
                </div>

                <!-- Table Header with Search -->
                <div class="p-6 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Search Student</h3>
                            <p class="text-sm text-gray-600">Find students to manage payment transactions</p>
                        </div>

                        <!-- Real-time Search Box -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="studentSearch"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Search by name, student number, email, or program..." autocomplete="off">
                            <!-- Loading indicator -->
                            <div id="searchLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2 hidden">
                                <i class="fa-solid fa-spinner fa-spin text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student Number
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Full Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody" class="bg-white divide-y divide-gray-200">
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($student['student_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($student['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($student['user_email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($student['user_contact']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($student['programs']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($student['enrollment_status'] == 'Enrolled'): ?>
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Enrolled
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Dont have Record
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onclick="addPaymentRecord(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>', '<?php echo htmlspecialchars($student['student_number']); ?>')"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                            <i class="fa-solid fa-plus mr-1"></i>
                                            Add Record
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- No results message -->
                    <div id="noResults" class="hidden text-center py-8">
                        <i class="fa-solid fa-search text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No students found matching your search criteria.</p>
                    </div>

                    <!-- Loading state -->
                    <div id="tableLoading" class="hidden text-center py-8">
                        <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500">Searching students...</p>
                    </div>
                </div>
            </div>
        </main>
        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Real-time search functionality
    let searchTimeout;
    const searchInput = document.getElementById('studentSearch');
    const tableBody = document.getElementById('studentTableBody');
    const noResults = document.getElementById('noResults');
    const tableLoading = document.getElementById('tableLoading');
    const searchLoading = document.getElementById('searchLoading');

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const searchTerm = this.value.trim();

        // Show loading indicator
        searchLoading.classList.remove('hidden');

        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300); // Debounce for 300ms
    });

    function performSearch(searchTerm) {
        // Show table loading state
        tableLoading.classList.remove('hidden');
        tableBody.style.opacity = '0.5';
        noResults.classList.add('hidden');

        fetch(`?search_ajax=1&search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                updateTable(data);
                searchLoading.classList.add('hidden');
                tableLoading.classList.add('hidden');
                tableBody.style.opacity = '1';
            })
            .catch(error => {
                console.error('Search error:', error);
                searchLoading.classList.add('hidden');
                tableLoading.classList.add('hidden');
                tableBody.style.opacity = '1';
            });
    }

    function updateTable(students) {
        if (students.length === 0) {
            tableBody.innerHTML = '';
            noResults.classList.remove('hidden');
            return;
        }

        noResults.classList.add('hidden');

        let html = '';
        students.forEach(student => {
            const statusClass = student.enrollment_status === 'Enrolled' ?
                'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';

            html += `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${escapeHtml(student.student_number)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${escapeHtml(student.full_name)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${escapeHtml(student.user_email)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${escapeHtml(student.user_contact)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        ${escapeHtml(student.programs)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                        ${escapeHtml(student.enrollment_status)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="addPaymentRecord(${student.id}, '${escapeHtml(student.full_name)}', '${escapeHtml(student.student_number)}')"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fa-solid fa-plus mr-1"></i>
                        Add Record
                    </button>
                </td>
            </tr>
        `;
        });

        tableBody.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function addPaymentRecord(studentId, studentName, studentNumber) {
        // You can redirect to payment form or show a modal
        // Example: redirect to payment form with student ID
        window.location.href = `pos_adding.php?student_id=${studentId}`;

        // Alternative: Show confirmation dialog
        // if (confirm(`Add payment record for ${studentName} (${studentNumber})?`)) {
        //     window.location.href = `add_payment.php?student_id=${studentId}`;
        // }
    }
</script>