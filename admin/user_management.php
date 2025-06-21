<?php
include "../connection/connection.php";
include "includes/header.php";
$conn = con();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Set timezone to Asia/Manila for logging
        date_default_timezone_set('Asia/Manila');
        $date_created = date('Y-m-d H:i:s');

        switch ($_POST['action']) {
            case 'add':
                $username = mysqli_real_escape_string($conn, $_POST['username']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $level = mysqli_real_escape_string($conn, $_POST['level']);

                $sql = "INSERT INTO users (username, email, password, level) VALUES ('$username', '$email', '$password', '$level')";
                if (mysqli_query($conn, $sql)) {
                    $new_user_id = mysqli_insert_id($conn);
                    // Log the activity
                    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('Scraper001','Added User: " . $username . " (ID: " . $new_user_id . ")', '$date_created' )");

                    echo "<script>
                        Swal.fire({
                            title: 'Success!',
                            text: 'User added successfully!',
                            icon: 'success'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error adding user: " . mysqli_error($conn) . "',
                            icon: 'error'
                        });
                    </script>";
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['id']);
                $username = mysqli_real_escape_string($conn, $_POST['username']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $level = mysqli_real_escape_string($conn, $_POST['level']);
                $old_username = mysqli_real_escape_string($conn, $_POST['old_username']);

                $sql = "UPDATE users SET username='$username', email='$email', level='$level'";

                // Only update password if provided
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $sql .= ", password='$password'";
                }

                $sql .= " WHERE id='$id'";

                if (mysqli_query($conn, $sql)) {
                    // Log the activity
                    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('Scraper001','Updated User: " . $old_username . " to " . $username . " (ID: " . $id . ")', '$date_created' )");

                    echo "<script>
                        Swal.fire({
                            title: 'Success!',
                            text: 'User updated successfully!',
                            icon: 'success'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error updating user: " . mysqli_error($conn) . "',
                            icon: 'error'
                        });
                    </script>";
                }
                break;
        }
    }
}

// Handle delete (separate from other actions to match your style)
if (isset($_POST['delete'])) {
    date_default_timezone_set('Asia/Manila');
    $date_created2 = date('Y-m-d H:i:s');
    $user_id = $_POST['id'];
    $username = $_POST['username'];

    $conn->query("DELETE FROM users WHERE id = '$user_id'");
    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('Scraper001','Deleted User: " . $username . " (ID: " . $user_id . ")', '$date_created2' )");

    header("location: user_management.php");
}

// Fetch levels for dropdown
$levels_sql = "SELECT * FROM levels ORDER BY level_name ASC";
$levels_result = mysqli_query($conn, $levels_sql);

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = '';
if (!empty($search)) {
    $where_clause = "WHERE username LIKE '%$search%' OR email LIKE '%$search%' OR level LIKE '%$search%'";
}

// Fetch users
$sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
    /* Modern Table Styles */
    .modern-table-container {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        padding: 2rem;
        margin: 1.5rem 0;
        border: 1px solid rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid rgba(99, 102, 241, 0.1);
    }

    .table-title {
        font-size: 1.75rem;
        font-weight: 700;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .add-teacher-btn {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
    }

    .add-teacher-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        color: white;
        text-decoration: none;
    }

    /* Search Bar Styles */
    .search-container {
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-wrapper {
        position: relative;
        flex: 1;
        min-width: 300px;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: 2px solid rgba(99, 102, 241, 0.1);
        border-radius: 16px;
        font-size: 1rem;
        background: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .search-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2);
        transform: translateY(-1px);
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 1.1rem;
    }

    .search-results-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6b7280;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .results-count {
        font-weight: 600;
        color: #6366f1;
    }

    .clear-search-btn {
        background: #f3f4f6;
        color: #6b7280;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: none;
    }

    .clear-search-btn:hover {
        background: #e5e7eb;
        color: #374151;
    }

    .clear-search-btn.show {
        display: block;
    }

    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .modern-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .modern-table thead th {
        padding: 1.25rem 1.5rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.85rem;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        position: relative;
    }

    .modern-table thead th:first-child {
        border-top-left-radius: 16px;
    }

    .modern-table thead th:last-child {
        border-top-right-radius: 16px;
    }

    .modern-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .modern-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        transform: scale(1.01);
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.1);
    }

    .modern-table tbody tr:last-child {
        border-bottom: none;
    }

    .modern-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 16px;
    }

    .modern-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 16px;
    }

    .modern-table td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border: none;
    }

    .teacher-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .teacher-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.1rem;
        text-transform: uppercase;
    }

    .teacher-details h3 {
        font-weight: 600;
        font-size: 1.1rem;
        margin: 0;
        color: #1f2937;
    }

    .teacher-details p {
        font-size: 0.85rem;
        color: #6b7280;
        margin: 0.25rem 0 0 0;
    }

    .nttc-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        font-family: 'Courier New', monospace;
    }

    .level-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .edit-btn {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .edit-btn:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        color: white;
        text-decoration: none;
    }

    .delete-btn {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .delete-btn:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.8);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        color: #6b7280;
        font-size: 0.9rem;
        font-weight: 500;
        margin-top: 0.5rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6b7280;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    .no-results {
        text-align: center;
        padding: 3rem 2rem;
        color: #6b7280;
    }

    .no-results-icon {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    /* Highlight search matches */
    .highlight {
        background-color: #fef3c7;
        padding: 0.1rem 0.2rem;
        border-radius: 3px;
        font-weight: 600;
    }

    /* Modal Styles */
    .user-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        transform: scale(0.9);
        transition: transform 0.3s ease;
        max-height: 90vh;
        overflow-y: auto;
    }

    .user-modal.show .modal-content {
        transform: scale(1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #6b7280;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .close-btn:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
    }

    .btn-secondary {
        padding: 0.75rem 1.5rem;
        border: 2px solid #e5e7eb;
        background: white;
        color: #6b7280;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .btn-primary {
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #5b21b6, #7c3aed);
        transform: translateY(-1px);
    }

    .password-note {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    @media (max-width: 768px) {
        .modern-table-container {
            padding: 1rem;
            margin: 1rem 0;
        }

        .table-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .search-container {
            flex-direction: column;
            align-items: stretch;
        }

        .search-wrapper {
            min-width: auto;
        }

        .modern-table {
            font-size: 0.85rem;
        }

        .modern-table td,
        .modern-table th {
            padding: 0.75rem;
        }

        .teacher-info {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }

        .action-buttons {
            flex-direction: column;
            align-items: stretch;
        }
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
                        class="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#announcementModal">
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
        <main class="flex-1 overflow-y-auto no-scrollbar p-4">
            <h2 class="font-semibold my-2 text-sm">
                <a href="index.php" class="text-gray-400">Dashboard | </a>Users Management
            </h2>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" id="totalTeachers"><?php echo mysqli_num_rows($result); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="visibleTeachers"><?php echo mysqli_num_rows($result); ?></div>
                    <div class="stat-label">Visible Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <i class="fa-solid fa-users" style="color: #6366f1;"></i>
                    </div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>

            <!-- Modern Table Container -->
            <div class="modern-table-container">
                <div class="table-header">
                    <h1 class="table-title">Users Directory</h1>
                    <button onclick="openAddModal()" class="add-teacher-btn">
                        <i class="fa-solid fa-plus"></i>
                        Add New User
                    </button>
                </div>

                <!-- Search Container -->
                <div class="search-container">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input"
                            placeholder="Search users by name, email or level...">
                    </div>
                    <button class="clear-search-btn" id="clearSearch">
                        <i class="fa-solid fa-times"></i>
                        Clear
                    </button>
                </div>

                <!-- Search Results Info -->
                <div class="search-results-info" id="searchResultsInfo" style="display: none;">
                    <i class="fa-solid fa-info-circle"></i>
                    <span>Showing <span class="results-count" id="resultsCount">0</span> of <span
                            id="totalCount"><?php echo mysqli_num_rows($result); ?></span> users</span>
                </div>

                <?php if (mysqli_num_rows($result) > 0) { ?>
                    <table class="modern-table" id="teachersTable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Information</th>
                                <th>Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teachersTableBody">
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr class="teacher-row"
                                    data-name="<?php echo strtolower(htmlspecialchars($row['username'])); ?>"
                                    data-email="<?php echo strtolower(htmlspecialchars($row['email'])); ?>"
                                    data-level="<?php echo strtolower(htmlspecialchars($row['level'])); ?>">
                                    <td>
                                        <span class="nttc-badge teacher-nttc">
                                            <?php echo htmlspecialchars($row['id']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="teacher-info">
                                            <div class="teacher-avatar">
                                                <?php echo strtoupper(substr($row['username'], 0, 2)); ?>
                                            </div>
                                            <div class="teacher-details">
                                                <h3 class="teacher-name"><?php echo htmlspecialchars($row['username']); ?></h3>
                                                <p class="teacher-email"><?php echo htmlspecialchars($row['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="level-badge teacher-level">
                                            <?php echo htmlspecialchars($row['level']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                class="edit-btn">
                                                <i class="fa-solid fa-edit"></i>
                                                Edit
                                            </button>
                                            <form method="POST" action="#" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="username"
                                                    value="<?php echo htmlspecialchars($row['username']); ?>">
                                                <button class="delete-btn" type="submit" name="delete"
                                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <!-- No Results State -->
                    <div class="no-results" id="noResultsState" style="display: none;">
                        <div class="no-results-icon">
                            <i class="fa-solid fa-search"></i>
                        </div>
                        <h3>No users found</h3>
                        <p>Try adjusting your search terms or <button class="clear-search-btn show"
                                onclick="clearSearch()">clear the search</button></p>
                    </div>
                <?php } else { ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                        <h3>No Users Found</h3>
                        <p>Get started by adding your first user to the system.</p>
                        <button onclick="openAddModal()" class="add-teacher-btn"
                            style="display: inline-flex; margin-top: 1rem;">
                            <i class="fa-solid fa-plus"></i>
                            Add First User
                        </button>
                    </div>
                <?php } ?>
            </div>
        </main>
        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="user-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" class="modal-title">Add New User</h3>
            <button onclick="closeModal()" class="close-btn">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <form id="userForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="userId">
            <input type="hidden" name="old_username" id="oldUsername">

            <div class="form-group">
                <label for="username" class="form-label">Username *</label>
                <input type="text" name="username" id="username" required class="form-input"
                    placeholder="Enter username">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email *</label>
                <input type="email" name="email" id="email" required class="form-input"
                    placeholder="Enter email address">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password <span id="passwordRequired">*</span></label>
                <input type="password" name="password" id="password" class="form-input" placeholder="Enter password">
                <div class="password-note" id="passwordNote" style="display: none;">
                    Leave blank to keep current password
                </div>
            </div>

            <div class="form-group">
                <label for="level" class="form-label">Level *</label>
                <select name="level" id="level" required class="form-select">
                    <option value="">Select Level</option>
                    <?php
                    // Reset levels result for dropdown
                    mysqli_data_seek($levels_result, 0);
                    while ($level_row = mysqli_fetch_assoc($levels_result)) {
                        echo "<option value='" . htmlspecialchars($level_row['level_name']) . "'>" . htmlspecialchars($level_row['level_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn-secondary">
                    Cancel
                </button>
                <button type="submit" class="btn-primary">
                    <span id="submitText">Add User</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Search Functionality Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearch');
        const searchResultsInfo = document.getElementById('searchResultsInfo');
        const resultsCount = document.getElementById('resultsCount');
        const visibleTeachersCount = document.getElementById('visibleTeachers');
        const teachersTable = document.getElementById('teachersTable');
        const noResultsState = document.getElementById('noResultsState');
        const teacherRows = document.querySelectorAll('.teacher-row');
        const totalTeachers = teacherRows.length;

        // Search functionality
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            if (searchTerm === '') {
                // Show all rows
                teacherRows.forEach(row => {
                    row.style.display = '';
                    clearHighlights(row);
                });
                visibleCount = totalTeachers;
                hideSearchInfo();
                if (teachersTable) {
                    teachersTable.style.display = 'table';
                    noResultsState.style.display = 'none';
                }
            } else {
                // Filter rows based on search term
                teacherRows.forEach(row => {
                    const name = row.getAttribute('data-name');
                    const email = row.getAttribute('data-email');
                    const level = row.getAttribute('data-level');

                    const nameMatch = name.includes(searchTerm);
                    const emailMatch = email.includes(searchTerm);
                    const levelMatch = level.includes(searchTerm);

                    if (nameMatch || emailMatch || levelMatch) {
                        row.style.display = '';
                        highlightMatches(row, searchTerm);
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                        clearHighlights(row);
                    }
                });

                showSearchInfo(visibleCount);

                if (teachersTable) {
                    if (visibleCount === 0) {
                        teachersTable.style.display = 'none';
                        noResultsState.style.display = 'block';
                    } else {
                        teachersTable.style.display = 'table';
                        noResultsState.style.display = 'none';
                    }
                }
            }

            // Update visible users count
            if (visibleTeachersCount) {
                visibleTeachersCount.textContent = visibleCount;
            }

            // Show/hide clear button
            if (searchTerm !== '') {
                clearSearchBtn.classList.add('show');
            } else {
                clearSearchBtn.classList.remove('show');
            }
        }

        function highlightMatches(row, searchTerm) {
            const nameElement = row.querySelector('.teacher-name');
            const emailElement = row.querySelector('.teacher-email');
            const levelElement = row.querySelector('.teacher-level');

            // Clear previous highlights
            clearHighlights(row);

            // Get original text
            const originalName = nameElement.textContent;
            const originalEmail = emailElement.textContent;
            const originalLevel = levelElement.textContent;

            // Highlight matches
            if (originalName.toLowerCase().includes(searchTerm)) {
                const highlightedName = highlightText(originalName, searchTerm);
                nameElement.innerHTML = highlightedName;
            }

            if (originalEmail.toLowerCase().includes(searchTerm)) {
                const highlightedEmail = highlightText(originalEmail, searchTerm);
                emailElement.innerHTML = highlightedEmail;
            }

            if (originalLevel.toLowerCase().includes(searchTerm)) {
                const highlightedLevel = highlightText(originalLevel, searchTerm);
                levelElement.innerHTML = highlightedLevel;
            }
        }

        function highlightText(text, searchTerm) {
            const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        }

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function clearHighlights(row) {
            const nameElement = row.querySelector('.teacher-name');
            const emailElement = row.querySelector('.teacher-email');
            const levelElement = row.querySelector('.teacher-level');

            // Store original text if not already stored
            if (!nameElement.hasAttribute('data-original')) {
                nameElement.setAttribute('data-original', nameElement.textContent);
            }
            if (!emailElement.hasAttribute('data-original')) {
                emailElement.setAttribute('data-original', emailElement.textContent);
            }
            if (!levelElement.hasAttribute('data-original')) {
                levelElement.setAttribute('data-original', levelElement.textContent);
            }

            // Restore original text
            nameElement.innerHTML = nameElement.getAttribute('data-original');
            emailElement.innerHTML = emailElement.getAttribute('data-original');
            levelElement.innerHTML = levelElement.getAttribute('data-original');
        }

        function showSearchInfo(count) {
            if (resultsCount) {
                resultsCount.textContent = count;
                searchResultsInfo.style.display = 'flex';
            }
        }

        function hideSearchInfo() {
            if (searchResultsInfo) {
                searchResultsInfo.style.display = 'none';
            }
        }

        function clearSearch() {
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        }

        // Event listeners
        if (searchInput) {
            searchInput.addEventListener('input', performSearch);
            searchInput.addEventListener('keyup', function (e) {
                if (e.key === 'Escape') {
                    clearSearch();
                }
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', clearSearch);
        }

        // Make clearSearch function global for inline onclick
        window.clearSearch = clearSearch;

        // Focus search input on Ctrl+F or Cmd+F
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });
    });

    // Modal functions
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('formAction').value = 'add';
        document.getElementById('submitText').textContent = 'Add User';
        document.getElementById('passwordRequired').textContent = '*';
        document.getElementById('password').required = true;
        document.getElementById('passwordNote').style.display = 'none';
        document.getElementById('userForm').reset();
        document.getElementById('userModal').classList.add('show');
        document.getElementById('userModal').style.display = 'flex';
        setTimeout(() => {
            document.getElementById('username').focus();
        }, 100);
    }

    function openEditModal(user) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('submitText').textContent = 'Update User';
        document.getElementById('passwordRequired').textContent = '';
        document.getElementById('password').required = false;
        document.getElementById('passwordNote').style.display = 'block';
        document.getElementById('userId').value = user.id;
        document.getElementById('oldUsername').value = user.username;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email;
        document.getElementById('level').value = user.level;
        document.getElementById('password').value = '';
        document.getElementById('userModal').classList.add('show');
        document.getElementById('userModal').style.display = 'flex';
        setTimeout(() => {
            document.getElementById('username').focus();
        }, 100);
    }

    function closeModal() {
        document.getElementById('userModal').classList.remove('show');
        setTimeout(() => {
            document.getElementById('userModal').style.display = 'none';
        }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('userModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>