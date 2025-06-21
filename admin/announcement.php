<?php include "../connection/connection.php";
include "includes/header.php";
$conn = con();

$sql = "SELECT * FROM announcement";
$result = $conn->query($sql);
$row = $result->fetch_assoc();


if (isset($_POST['delete'])) {

    $conn->query("DELETE FROM announcement WHERE announcement_id = '" . $_POST['id'] . "'");
    header("location: announcement.php");
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
        <main class="flex-1 overflow-y-auto no-scrollbar p-4 ">

            <h2 class=" font-semibold my-2 text-sm"><a href="index.php" class="text-gray-400">Dashboard |
                </a>Announcement</h2>
            <div class="w-full flex flex-row">
                <a href="add_announcement.php" class="btn-success">Add Announcement</a>
            </div>
            <div class="overflow-x-auto mt-10">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Announcement ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Announcement To
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Message
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                action
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <?php if ($result->num_rows > 0) { ?>
                            <?php do { ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <!-- <img src="" class="w-8 h-8 rounded-full" alt="Student" /> -->
                                            <div class="ml-3">

                                                <h1 class="text-2xl"> <?php echo $row['announcement_id'] ?></h1>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <!-- <img src="" class="w-8 h-8 rounded-full" alt="Student" /> -->
                                            <div class="ml-3">

                                                <h1 class="text-2xl"> <?php echo $row['group'] ?></h1>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <!-- <img src="" class="w-8 h-8 rounded-full" alt="Student" /> -->
                                            <div class="ml-3">

                                                <h1 class="text-sm"> <?php echo $row['message'] ?></h1>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <!-- <img src="" class="w-8 h-8 rounded-full" alt="Student" /> -->
                                            <form class="ml-3" method="POST" action="#">
                                                <input type="text" name="id" value="<?php echo $row['announcement_id'] ?>">
                                                <button class="btn-danger" type="submit" name="delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>


                                </tr>
                            <?php } while ($row = $result->fetch_assoc()); ?>
                        <?php } ?>
                    </tbody>

                </table>
            </div>
        </main>
        <?php include "includes/footer.php" ?>
    </div>
</div>

<!-- Include Bootstrap for modals -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
    const select = document.getElementById('select');
    const programInput = document.getElementById('programInput');
    const packageInput = document.getElementById('packageInput');

    select.addEventListener('change', function () {
        // Hide and remove name
        programInput.classList.add('hidden');
        packageInput.classList.add('hidden');
        programInput.removeAttribute('name');
        packageInput.removeAttribute('name');

        if (this.value === 'program') {
            programInput.classList.remove('hidden');
            programInput.setAttribute('name', 'group'); // only visible input has name
        } else if (this.value === 'package') {
            packageInput.classList.remove('hidden');
            packageInput.setAttribute('name', 'group');
        }
    });
</script>