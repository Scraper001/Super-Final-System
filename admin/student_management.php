<?php
include '../connection/connection.php';
$conn = con();

$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;


$totalResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM student_info_tbl");
$totalRow = mysqli_fetch_assoc($totalResult);
$total = $totalRow['total'];
$pages = ceil($total / $limit);


$query = "SELECT * FROM student_info_tbl ORDER BY entry_date DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

$todayPrefix = date("Ymd") . "A";

$sql_get_student = "SELECT student_number FROM student_info_tbl ORDER BY id DESC LIMIT 1";
$result_student_number = $conn->query($sql_get_student);
$row_stud_id = $result_student_number->fetch_assoc();

if ($result_student_number->num_rows > 0) {
    $lastNumber = substr($row_stud_id['student_number'], -4);
    $nextNumber = str_pad((int) $lastNumber + 1, 4, '0', STR_PAD_LEFT);

    $newStudentNumber = $todayPrefix . $nextNumber;
} else {
    $newStudentNumber = "20250506A0001";
}




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
                    <a href="announcement.php" lass="text-gray-500 hover:text-primary focus:outline-none cursor-pointer"
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
        <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
            <!-- Dashboard Overview -->
            <h2 class="font-semibold my-2 text-xs">
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> >Student Management</a>

            </h2>
            <button
                class="cubic py-2 px-5 rounded-xl border-2 hover:bg-green-500 my-4 button-show-reg font-bold hover:text-white  hover:border-green-800 cursor-pointer"
                id="showRegBtn">
                Add Student
            </button>

            <?php include "form/registration_student.php" ?>
            <div class="w-full my-2 h-auto px-5 py-2">
                <div class="bg-white  w-full h-full border-1 rounded p-2 flex flex-col">
                    <label for="website-admin" class="block mb-2 text-sm font-medium text-gray-900 ">Search Here</label>
                    <div class="flex">
                        <span
                            class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border rounded-e-0 border-gray-300 border-e-0 rounded-s-md ">
                            <i class="fa-brands fa-searchengin text-2xl"></i>
                        </span>
                        <input type="text" id="searchInput"
                            class="rounded-none rounded-e-lg bg-gray-50 border text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm border-gray-300 p-2.5  "
                            placeholder="Eg: 202020A001 or John doe, of even Birth Day">
                    </div>
                    <span class="italic text-gray-400 text-sm">You can Search Anything here from ID to other
                        details</span>
                </div>
            </div>
            <section class="h-[70%] py-2 px-5 w-full flex flex-row">
                <div class="w-[60%] bg-white rounded shadow-md">
                    <div class="overflow-x-auto h-full">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Entry Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Classification</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Civil Status</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>

                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100" id="studentTableBody">
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="#" class="text-primary hover:underline text-sm viewStudentBtn"
                                                data-id="<?= $row['id'] ?>">View</a>

                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <p class="text-sm font-medium">
                                                    <?= $row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name'] ?>
                                                </p>
                                                <p class="text-xs text-gray-500">#<?= $row['student_number'] ?></p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <p class="text-sm"><?= date('F j, Y', strtotime($row['entry_date'])) ?></p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full"><?= $row['classification'] ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full"><?= $row['civil_status'] ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status = (!empty($row['enrollment_status']) && $row['enrollment_status'] != 'Not Enrolled' && $row['enrollment_status'] != 'No Reservation')
                                                ? $row['enrollment_status']
                                                : 'Not Being Processed';

                                            switch ($status) {
                                                case 'Enrolled':
                                                    $badgeClass = 'bg-green-50 text-green-700';
                                                    break;
                                                case 'Pending':
                                                    $badgeClass = 'bg-yellow-50 text-yellow-700';
                                                    break;
                                                case 'Cancelled':
                                                    $badgeClass = 'bg-red-50 text-red-700';
                                                    break;
                                                case 'Not Being Processed':
                                                    $badgeClass = 'bg-gray-200 text-gray-600';
                                                    break;
                                                default:
                                                    $badgeClass = 'bg-blue-50 text-blue-700';
                                                    break;
                                            }
                                            ?>

                                            <span class="<?= $badgeClass ?> text-xs px-2 py-1 rounded-full">
                                                <?= $status ?>
                                            </span>


                                        </td>

                                    </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>
                    </div>
                    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500">Showing <?= min($limit, $total - $start) ?> of <?= $total ?>
                            students</p>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>"
                                    class="px-3 py-1 rounded border border-gray-200 text-sm">Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <a href="?page=<?= $i ?>"
                                    class="px-3 py-1 rounded border <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200' ?> text-sm"><?= $i ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $pages): ?>
                                <a href="?page=<?= $page + 1 ?>"
                                    class="px-3 py-1 rounded border border-gray-200 text-sm">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div class="w-[40%] bg-white rounded shadow-md mx-2 p-2 overflow-y-auto">


                    <span>Student Elaborated Data</span>
                    <div id="studentDetailsContent" class="p-4 text-sm text-gray-700"></div>

                    <?php include "form/modal_edit.php" ?>

                </div>
            </section>
        </main>

        <?php include "includes/footer.php" ?>
    </div>
</div>

<script src="../js/registration.js"> </script>