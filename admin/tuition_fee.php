<?php
include '../connection/connection.php';
$conn = con();

$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;


$totalResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM programs");
$totalRow = mysqli_fetch_assoc($totalResult);
$total = $totalRow['total'];
$pages = ceil($total / $limit);




$query2 = "SELECT * FROM tuitionfee ORDER BY id DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query2);

$row = $result->fetch_assoc();

$query = "SELECT * FROM programs ORDER BY id DESC LIMIT $start, $limit";
$result2 = mysqli_query($conn, $query);
$result3 = mysqli_query($conn, $query);
$result4 = mysqli_query($conn, $query);
$row2 = $result2->fetch_assoc();
$row3 = $result3->fetch_assoc();
$row4 = $result4->fetch_assoc();


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
                <a href="index.php" class="hover:text-green-400 text-gray-400">Dashboard</a> >Management Zone | Tuition
                Fee
                Management</a>

            </h2>


            <!-- Button to open modal -->


            <button
                class="cubic py-2 px-5 rounded-xl border-2 hover:bg-green-500 my-4 button-show-reg font-bold hover:text-white hover:-translate-y-2 hover:border-green-800 cursor-pointer"
                id="showTuitionModal">
                Add Tuition Fee
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
                        <input type="text" x-model="searchTuition" id="searchTuition"
                            class="rounded-none rounded-e-lg bg-gray-50 border text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm border-gray-300 p-2.5  "
                            placeholder="Eg: 2 or NSTP, of even total Tuition" @input="searchPrograms()">
                    </div>
                    <span class="italic text-gray-400 text-sm">You can Search Anything here from Program ID to other
                        details</span>
                </div>
            </div>
            <section class="h-[70%] py-2 px-5 w-full flex flex-row">
                <div class="w-full bg-white rounded shadow-md">
                    <section class="bg-white rounded-lg shadow-sm overflow-hidden">


                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700 border">
                                <thead class="text-xs uppercase bg-gray-200 text-gray-600">
                                    <tr>
                                        <th class="px-4 py-3">Program Name</th>
                                        <th class="px-4 py-3">Details</th>
                                        <th class="px-4 py-3">Demo Cost</th>
                                        <th class="px-4 py-3">Total Tuition Cost</th>
                                        <th class="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-300" id="tuitionTable">
                                    <?php if ($result->num_rows > 0) { ?>
                                        <?php do { ?>
                                            <tr>
                                                <td class="px-4 py-3 font-semibold w-[180px]"><?php echo $row['program_name'] ?>
                                                </td>

                                                <td class="px-4 py-3">

                                                    <div class='flex justify-between'><span class='font-medium w-40'>Package
                                                            Number</span><span
                                                            class='italic text-right'><?php echo $row['package_number'] ?></span>
                                                    </div>

                                                    <?php
                                                    $details = [

                                                        'Tuition Fee' => $row['tuition Fee'],
                                                        'Miscellaneous Fee' => $row['misc Fee'],
                                                        'OJT Fee and Medical' => $row['ojtmedical'],
                                                        'System Fee' => $row['system Fee'],
                                                        'Assessment Fee' => $row['assessment Fee'],
                                                        'Uniform' => $row['uniform'],
                                                        'ID Fee' => $row['IDfee'],
                                                        'Book Cost' => $row['books'],
                                                        'Kits' => $row['kit'],
                                                        'Required Reservation Fee' => $row['reservation_fee']
                                                    ];
                                                    foreach ($details as $label => $value) {
                                                        echo "<div class='flex justify-between'><span class='font-medium w-40'>$label:</span><span class='italic text-right'>₱" . number_format($value, 2) . "</span></div>";
                                                    }
                                                    ?>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <?php
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $demoKey = 'demo' . $i;
                                                        echo "<div class='flex justify-between'><span class='font-medium'>Demo $i:</span><span class='italic'>₱" . number_format($row[$demoKey], 2) . "</span></div>";
                                                    }
                                                    ?>
                                                </td>

                                                <td class="px-4 py-3 text-right font-semibold text-green-700">
                                                    ₱<?php echo number_format($row['totalbasedtuition'], 2); ?>
                                                </td>

                                                <td class="px-4 py-3 flex gap-2 flex-wrap">
                                                    <button
                                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs"
                                                        onclick="openTuitionEdit(this)" data-program-id="<?= $row['id'] ?>"
                                                        data-program-name="<?= $row['program_name'] ?>"
                                                        data-package-number="<?= $row['package_number'] ?>"
                                                        data-tuition-fee="<?= $row['tuition Fee'] ?>"
                                                        data-misc-fee="<?= $row['misc Fee'] ?>"
                                                        data-ojtmedical="<?= $row['ojtmedical'] ?>"
                                                        data-system-fee="<?= $row['system Fee'] ?>"
                                                        data-assessment-fee="<?= $row['assessment Fee'] ?>"
                                                        data-uniform="<?= $row['uniform'] ?>" data-idfee="<?= $row['IDfee'] ?>"
                                                        data-books="<?= $row['books'] ?>" data-kit="<?= $row['kit'] ?>"
                                                        data-demo1="<?= $row['demo1'] ?>" data-demo2="<?= $row['demo2'] ?>"
                                                        data-demo3="<?= $row['demo3'] ?>" data-demo4="<?= $row['demo4'] ?>"
                                                        data-reservation="<?= $row['reservation_fee'] ?>"
                                                        data-demo4="<?= $row['demo4'] ?>"
                                                        data-total="<?= $row['totalbasedtuition'] ?>">
                                                        Edit
                                                    </button>


                                                    <button
                                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs"
                                                        onclick="openDeleteTuition(this)"
                                                        data-program-id="<?php echo htmlspecialchars($row['id']) ?>"
                                                        data-program-name="<?php echo htmlspecialchars($row['program_name']) ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } while ($row = $result->fetch_assoc()) ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 text-center text-gray-500">No Data found.
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>


                        <!-- Pagination -->
                        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                            <p class="text-sm text-gray-500">Showing <?= min($limit, $total - $start) ?> of
                                <?= $total ?>
                                students
                            </p>
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
                    </section>
                    <?php include "form/modal_tuition.php" ?>
                </div>
            </section>
        </main>


        <?php include "includes/footer.php" ?>
    </div>
</div>
<?php include "form/modal_program.php" ?>

<script src="../js/tuitionfee.js"></script>