<?php
include '../connection/connection.php';
$conn = con();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    switch ($action) {
        case 'add':

            $nttc = mysqli_real_escape_string($conn, $_POST['nttc']);
            $program = mysqli_real_escape_string($conn, $_POST['program'] ?? '');

            $query = "INSERT INTO teachers (fullname, nttc, program, created_at) VALUES ('$fullname', '$nttc', '$program', NOW())";
            if (mysqli_query($conn, $query)) {


                date_default_timezone_set('Asia/Manila');
                $date_created2 = date('Y-m-d H:i:s');
                $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Added new Teacher: $fullname', '$date_created2' )");


                echo json_encode(['success' => true, 'message' => 'Teacher added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding teacher: ' . mysqli_error($conn)]);
            }
            exit;

        case 'edit':
            $id = (int) $_POST['id'];
            $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
            $nttc = mysqli_real_escape_string($conn, $_POST['nttc']);
            $program = mysqli_real_escape_string($conn, $_POST['program'] ?? '');

            $query = "UPDATE teachers SET fullname='$fullname', nttc='$nttc', program='$program' WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                echo json_encode(['success' => true, 'message' => 'Teacher updated successfully!']);
                date_default_timezone_set('Asia/Manila');
                $date_created2 = date('Y-m-d H:i:s');
                $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Edited Teacher: $fullname', '$date_created2' )");


            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating teacher: ' . mysqli_error($conn)]);
            }
            exit;

        case 'delete':
            $id = (int) $_POST['id'];
            $query = "DELETE FROM teachers WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                echo json_encode(['success' => true, 'message' => 'Teacher deleted successfully!']);

                date_default_timezone_set('Asia/Manila');
                $date_created2 = date('Y-m-d H:i:s');
                $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('" . $_SESSION['user_id'] . "','Deleted Teacher: $fullname', '$date_created2' )");

            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting teacher: ' . mysqli_error($conn)]);
            }
            exit;

        case 'get_teacher':
            $id = (int) $_POST['id'];
            $query = "SELECT * FROM teachers WHERE id=$id";
            $result = mysqli_query($conn, $query);
            if ($row = mysqli_fetch_assoc($result)) {
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Teacher not found']);
            }
            exit;
    }
}

// Pagination and search
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$start = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where_clause = '';
if (!empty($search)) {
    $where_clause = "WHERE fullname LIKE '%$search%' OR nttc LIKE '%$search%' OR program LIKE '%$search%'";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM teachers $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total = mysqli_fetch_assoc($count_result)['total'];
$pages = ceil($total / $limit);

// Get teachers data
$query = "SELECT * FROM teachers $where_clause ORDER BY created_at DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Get available programs

$result_program = $conn->query("SELECT * FROM program");
$row_result = $result_program->fetch_assoc();

$conn->close();





?>

<?php include "includes/header.php" ?>

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
                    <button class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer" id="logout">
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
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> > Teacher Management
            </h2>

            <button id="addTeacherBtn"
                class="cubic py-2 px-5 rounded-xl border-2 hover:bg-green-500 my-4 font-bold hover:text-white hover:border-green-800 cursor-pointer">
                <i class="fa-solid fa-plus mr-2"></i>Add Teacher
            </button>

            <!-- Search Section -->
            <div class="w-full my-2 h-auto px-5 py-2">
                <div class="bg-white w-full h-full border-1 rounded p-2 flex flex-col">
                    <label for="searchInput" class="block mb-2 text-sm font-medium text-gray-900">Search Here</label>
                    <div class="flex">
                        <span
                            class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border rounded-e-0 border-gray-300 border-e-0 rounded-s-md">
                            <i class="fa-brands fa-searchengin text-2xl"></i>
                        </span>
                        <input type="text" id="searchInput"
                            class="rounded-none rounded-e-lg bg-gray-50 border text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm border-gray-300 p-2.5"
                            placeholder="Search by name, NTTC, or program" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <span class="italic text-gray-400 text-sm">You can search anything here from NTTC to other
                        details</span>
                </div>
            </div>

            <!-- Teachers Table Section -->
            <section class="h-[70%] py-2 px-5 w-full flex flex-row">
                <div class="w-[60%] bg-white rounded shadow-md">
                    <div class="overflow-x-auto h-full">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Teacher Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Entry Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        NTTC</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Program</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100" id="teacherTableBody">
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($row['fullname']) ?></p>
                                                    <p class="text-xs text-gray-500">#<?= htmlspecialchars($row['nttc']) ?></p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <p class="text-sm"><?= date('F j, Y', strtotime($row['created_at'])) ?></p>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full"><?= htmlspecialchars($row['nttc']) ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full"><?= htmlspecialchars($row['program'] ?? 'Not Assigned') ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex space-x-2">
                                                    <button class="text-blue-600 hover:underline text-sm viewTeacherBtn"
                                                        data-id="<?= $row['id'] ?>">
                                                        <i class="fa-solid fa-eye"></i> View
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No teachers found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500">Showing <?= min($limit, $total - $start) ?> of <?= $total ?>
                            teachers</p>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 rounded border border-gray-200 text-sm">Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 rounded border <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200' ?> text-sm"><?= $i ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $pages): ?>
                                <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 rounded border border-gray-200 text-sm">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Teacher Details Panel -->
                <div class="w-[40%] bg-white rounded shadow-md mx-2 p-2 overflow-y-auto">
                    <span class="font-semibold text-lg">Teacher Details</span>
                    <div id="teacherDetailsContent" class="p-4 text-sm text-gray-700">
                        <p class="text-center text-gray-400 mt-8">Select a teacher to view details</p>
                    </div>
                </div>
            </section>
        </main>

        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- Add/Edit Teacher Modal -->
<div id="teacherModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Add Teacher</h3>
            <form id="teacherForm">
                <input type="hidden" id="teacherId" name="id">
                <input type="hidden" id="formAction" name="action" value="add">

                <div class="mb-4">
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" id="fullname" name="fullname" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="nttc" class="block text-sm font-medium text-gray-700 mb-2">NTTC</label>
                    <input type="text" id="nttc" name="nttc" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="program" class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                    <select id="program" name="program"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Program</option>

                        <?php if ($result_program->num_rows > 0) { ?>
                            <?php do { ?>
                                <option value="<?php echo $row_result['program_name'] ?>">
                                    <?php echo $row_result['program_name'] ?>
                                </option>
                            <?php } while ($row_result = $result_program->fetch_assoc()) ?>
                        <?php } ?>
                    </select>
                </div>

                <div class=" flex justify-end space-x-3">
                    <button type="button" id="cancelBtn"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" id="submitBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('teacherModal');
        const modalTitle = document.getElementById('modalTitle');
        const teacherForm = document.getElementById('teacherForm');
        const addBtn = document.getElementById('addTeacherBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const searchInput = document.getElementById('searchInput');

        // Show modal function
        function showModal(title, action = 'add', teacherData = null) {
            modalTitle.textContent = title;
            document.getElementById('formAction').value = action;

            if (teacherData) {
                document.getElementById('teacherId').value = teacherData.id;
                document.getElementById('fullname').value = teacherData.fullname;
                document.getElementById('nttc').value = teacherData.nttc;
                document.getElementById('program').value = teacherData.program || '';
            } else {
                teacherForm.reset();
                document.getElementById('teacherId').value = '';
            }

            modal.classList.remove('hidden');
        }

        // Hide modal function
        function hideModal() {
            modal.classList.add('hidden');
            teacherForm.reset();
        }

        // Add teacher button
        addBtn.addEventListener('click', function () {
            showModal('Add Teacher', 'add');
        });

        // Cancel button
        cancelBtn.addEventListener('click', hideModal);

        // Close modal on outside click
        modal.addEventListener('click', function (e) {
            if (e.target === modal) hideModal();
        });

        // Edit teacher buttons
        document.addEventListener('click', function (e) {
            if (e.target.closest('.editTeacherBtn')) {
                const btn = e.target.closest('.editTeacherBtn');
                const teacherId = btn.dataset.id;

                // Fetch teacher data
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_teacher&id=${teacherId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showModal('Edit Teacher', 'edit', data.data);
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Failed to fetch teacher data', 'error');
                    });
            }
        });

        // Delete teacher buttons
        document.addEventListener('click', function (e) {
            if (e.target.closest('.deleteTeacherBtn')) {
                const btn = e.target.closest('.deleteTeacherBtn');
                const teacherId = btn.dataset.id;
                const teacherName = btn.dataset.name;

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete ${teacherName}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=delete&id=${teacherId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', data.message, 'success').then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', 'Failed to delete teacher', 'error');
                            });
                    }
                });
            }
        });

        // View teacher buttons
        document.addEventListener('click', function (e) {
            if (e.target.closest('.viewTeacherBtn')) {
                const btn = e.target.closest('.viewTeacherBtn');
                const teacherId = btn.dataset.id;

                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_teacher&id=${teacherId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const teacher = data.data;
                            const detailsHtml = `
                        <div class="space-y-4">
                            <div class="border-b pb-2">
                                <h4 class="font-semibold text-lg text-gray-800">${teacher.fullname}</h4>
                                <p class="text-sm text-gray-500">#${teacher.nttc}</p>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <label class="font-medium text-gray-600">NTTC:</label>
                                    <p class="text-gray-800">${teacher.nttc}</p>
                                </div>
                                
                                <div>
                                    <label class="font-medium text-gray-600">Program:</label>
                                    <p class="text-gray-800">${teacher.program || 'Not Assigned'}</p>
                                </div>
                                
                                <div>
                                    <label class="font-medium text-gray-600">Entry Date:</label>
                                    <p class="text-gray-800">${new Date(teacher.created_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            })}</p>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t">
                                <div class="flex space-x-2">
                                    <button class="editTeacherBtn px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700" data-id="${teacher.id}">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </button>
                                    <button class="deleteTeacherBtn px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700" data-id="${teacher.id}" data-name="${teacher.fullname}">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                            document.getElementById('teacherDetailsContent').innerHTML = detailsHtml;
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Failed to fetch teacher details', 'error');
                    });
            }
        });

        // Form submission
        teacherForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(teacherForm);

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        hideModal();
                        Swal.fire('Success', data.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Operation failed', 'error');
                });
        });

        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchValue = this.value.trim();
                const url = new URL(window.location);
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('page'); // Reset to first page
                window.location.href = url.toString();
            }, 500);
        });

        // Logout functionality
        document.getElementById('logout').addEventListener('click', function () {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to logout page or perform logout action
                    window.location.href = 'logout.php';
                }
            });
        });
    });
</script>