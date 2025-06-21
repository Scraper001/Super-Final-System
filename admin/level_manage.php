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
                $level_name = mysqli_real_escape_string($conn, $_POST['level_name']);

                $sql = "INSERT INTO levels (level_name) VALUES ('$level_name')";
                if (mysqli_query($conn, $sql)) {
                    $new_level_id = mysqli_insert_id($conn);
                    // Log the activity
                    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('Scraper001','Added Level: " . $level_name . " (ID: " . $new_level_id . ")', '$date_created' )");

                    echo "<script>
                        Swal.fire({
                            title: 'Success!',
                            text: 'Level added successfully!',
                            icon: 'success'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error adding level: " . mysqli_error($conn) . "',
                            icon: 'error'
                        });
                    </script>";
                }
                break;

            case 'edit':
                $id = mysqli_real_escape_string($conn, $_POST['id']);
                $level_name = mysqli_real_escape_string($conn, $_POST['level_name']);
                $old_name = mysqli_real_escape_string($conn, $_POST['old_name']);

                $sql = "UPDATE levels SET level_name='$level_name' WHERE id='$id'";
                if (mysqli_query($conn, $sql)) {
                    // Log the activity
                    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('Scraper001','Updated Level: " . $old_name . " to " . $level_name . " (ID: " . $id . ")', '$date_created' )");

                    echo "<script>
                        Swal.fire({
                            title: 'Success!',
                            text: 'Level updated successfully!',
                            icon: 'success'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error updating level: " . mysqli_error($conn) . "',
                            icon: 'error'
                        });
                    </script>";
                }
                break;

            case 'delete':
                $id = mysqli_real_escape_string($conn, $_POST['id']);
                $level_name = mysqli_real_escape_string($conn, $_POST['level_name']);

                $sql = "DELETE FROM levels WHERE id='$id'";
                if (mysqli_query($conn, $sql)) {
                    // Log the activity
                    $conn->query("INSERT INTO `logs`(`user_id`, `activity`, `dnt`) VALUES ('Scraper001','Deleted Level: " . $level_name . " (ID: " . $id . ")', '$date_created' )");

                    echo "<script>
                        Swal.fire({
                            title: 'Success!',
                            text: 'Level deleted successfully!',
                            icon: 'success'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error deleting level: " . mysqli_error($conn) . "',
                            icon: 'error'
                        });
                    </script>";
                }
                break;
        }
    }
}

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = '';
if (!empty($search)) {
    $where_clause = "WHERE level_name LIKE '%$search%'";
}

// Fetch levels
$sql = "SELECT * FROM levels $where_clause ORDER BY created_at DESC";
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

    .level-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .level-avatar {
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

    .level-details h3 {
        font-weight: 600;
        font-size: 1.1rem;
        margin: 0;
        color: #1f2937;
    }

    .level-details p {
        font-size: 0.85rem;
        color: #6b7280;
        margin: 0.25rem 0 0 0;
    }

    .id-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        font-family: 'Courier New', monospace;
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
    .level-modal {
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
    }

    .level-modal.show .modal-content {
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

    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-input:focus {
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

        .level-info {
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
        <main class="flex-1 overflow-y-auto no-scrollbar p-4">
            <h2 class="font-semibold my-2 text-sm">
                <a href="index.php" class="text-gray-400">Dashboard | </a>Level Management
            </h2>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" id="totalLevels"><?php echo mysqli_num_rows($result); ?></div>
                    <div class="stat-label">Total Levels</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="visibleLevels"><?php echo mysqli_num_rows($result); ?></div>
                    <div class="stat-label">Visible Levels</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <i class="fa-solid fa-layer-group" style="color: #6366f1;"></i>
                    </div>
                    <div class="stat-label">Active Levels</div>
                </div>
            </div>

            <!-- Modern Table Container -->
            <div class="modern-table-container">
                <div class="table-header">
                    <h1 class="table-title">Level Directory</h1>
                    <button onclick="openAddModal()" class="add-teacher-btn">
                        <i class="fa-solid fa-plus"></i>
                        Add New Level
                    </button>
                </div>

                <!-- Search Container -->
                <div class="search-container">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search levels by name...">
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
                            id="totalCount"><?php echo mysqli_num_rows($result); ?></span> levels</span>
                </div>

                <?php if (mysqli_num_rows($result) > 0) { ?>
                    <table class="modern-table" id="levelsTable">
                        <thead>
                            <tr>
                                <th>Level ID</th>
                                <th>Information</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="levelsTableBody">
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr class="level-row"
                                    data-name="<?php echo strtolower(htmlspecialchars($row['level_name'])); ?>">
                                    <td>
                                        <span class="id-badge level-id">
                                            <?php echo htmlspecialchars($row['id']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="level-info">
                                            <div class="level-avatar">
                                                <?php echo strtoupper(substr($row['level_name'], 0, 2)); ?>
                                            </div>
                                            <div class="level-details">
                                                <h3 class="level-name"><?php echo htmlspecialchars($row['level_name']); ?></h3>
                                                <p>System Level</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span style="font-size: 0.85rem; color: #6b7280;">
                                            <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span style="font-size: 0.85rem; color: #6b7280;">
                                            <?php echo date('M j, Y g:i A', strtotime($row['updated_at'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                class="edit-btn">
                                                <i class="fa-solid fa-edit"></i>
                                                Edit
                                            </button>
                                            <button
                                                onclick="deleteLevel(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['level_name']); ?>')"
                                                class="delete-btn">
                                                <i class="fa-solid fa-trash"></i>
                                                Delete
                                            </button>
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
                        <h3>No levels found</h3>
                        <p>Try adjusting your search terms or <button class="clear-search-btn show"
                                onclick="clearSearch()">clear the search</button></p>
                    </div>
                <?php } else { ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h3>No Levels Found</h3>
                        <p>Get started by adding your first level to the system.</p>
                        <button onclick="openAddModal()" class="add-teacher-btn"
                            style="display: inline-flex; margin-top: 1rem;">
                            <i class="fa-solid fa-plus"></i>
                            Add First Level
                        </button>
                    </div>
                <?php } ?>
            </div>
        </main>

        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- Add/Edit Level Modal -->
<div id="levelModal" class="level-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" class="modal-title">Add New Level</h3>
            <button onclick="closeModal()" class="close-btn">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <form id="levelForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="levelId">
            <input type="hidden" name="old_name" id="oldName">

            <div class="form-group">
                <label for="level_name" class="form-label">Level Name *</label>
                <input type="text" name="level_name" id="level_name" required class="form-input"
                    placeholder="Enter level name">
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn-secondary">
                    Cancel
                </button>
                <button type="submit" class="btn-primary">
                    <span id="submitText">Add Level</span>
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
        const visibleLevelsCount = document.getElementById('visibleLevels');
        const levelsTable = document.getElementById('levelsTable');
        const noResultsState = document.getElementById('noResultsState');
        const levelRows = document.querySelectorAll('.level-row');
        const totalLevels = levelRows.length;

        // Search functionality
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            if (searchTerm === '') {
                // Show all rows
                levelRows.forEach(row => {
                    row.style.display = '';
                    clearHighlights(row);
                });
                visibleCount = totalLevels;
                hideSearchInfo();
                if (levelsTable) {
                    levelsTable.style.display = 'table';
                    noResultsState.style.display = 'none';
                }
            } else {
                // Filter rows based on search term
                levelRows.forEach(row => {
                    const name = row.getAttribute('data-name');
                    const nameMatch = name.includes(searchTerm);

                    if (nameMatch) {
                        row.style.display = '';
                        highlightMatches(row, searchTerm);
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                        clearHighlights(row);
                    }
                });

                showSearchInfo(visibleCount);

                if (levelsTable) {
                    if (visibleCount === 0) {
                        levelsTable.style.display = 'none';
                        noResultsState.style.display = 'block';
                    } else {
                        levelsTable.style.display = 'table';
                        noResultsState.style.display = 'none';
                    }
                }
            }

            // Update visible levels count
            if (visibleLevelsCount) {
                visibleLevelsCount.textContent = visibleCount;
            }

            // Show/hide clear button
            if (searchTerm !== '') {
                clearSearchBtn.classList.add('show');
            } else {
                clearSearchBtn.classList.remove('show');
            }
        }

        function highlightMatches(row, searchTerm) {
            const nameElement = row.querySelector('.level-name');

            // Clear previous highlights
            clearHighlights(row);

            // Get original text
            const originalName = nameElement.textContent;

            // Highlight matches in name
            if (originalName.toLowerCase().includes(searchTerm)) {
                const highlightedName = highlightText(originalName, searchTerm);
                nameElement.innerHTML = highlightedName;
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
            const nameElement = row.querySelector('.level-name');

            // Store original text if not already stored
            if (!nameElement.hasAttribute('data-original')) {
                nameElement.setAttribute('data-original', nameElement.textContent);
            }

            // Restore original text
            nameElement.innerHTML = nameElement.getAttribute('data-original');
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
        document.getElementById('modalTitle').textContent = 'Add New Level';
        document.getElementById('formAction').value = 'add';
        document.getElementById('submitText').textContent = 'Add Level';
        document.getElementById('levelForm').reset();
        document.getElementById('levelModal').classList.add('show');
        document.getElementById('levelModal').style.display = 'flex';
        setTimeout(() => {
            document.getElementById('level_name').focus();
        }, 100);
    }

    function openEditModal(level) {
        document.getElementById('modalTitle').textContent = 'Edit Level';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('submitText').textContent = 'Update Level';
        document.getElementById('levelId').value = level.id;
        document.getElementById('oldName').value = level.level_name;
        document.getElementById('level_name').value = level.level_name;
        document.getElementById('levelModal').classList.add('show');
        document.getElementById('levelModal').style.display = 'flex';
        setTimeout(() => {
            document.getElementById('level_name').focus();
        }, 100);
    }

    function closeModal() {
        document.getElementById('levelModal').classList.remove('show');
        setTimeout(() => {
            document.getElementById('levelModal').style.display = 'none';
        }, 300);
    }

    function deleteLevel(id, name) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete the level "${name}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create and submit form for deletion
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="level_name" value="${name}">
            `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Close modal when clicking outside
    document.getElementById('levelModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>