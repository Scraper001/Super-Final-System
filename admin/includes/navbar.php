<!-- Navigation -->
<nav class="flex-1 overflow-y-auto no-scrollbar px-3 py-4 min-h-screen ">
    <ul class="space-y-1">
        <li>
            <a href="index.php"
                class="flex items-center px-10 py-3 text-primary border-2 text-black rounded-lg font-medium justify-start <?php echo ($currentPage == 'index.php') ? 'bg-emerald-600 text-white' : ''; ?>">

                <i class='icons fa-solid fa-house text-md -mx-2 mr-3'></i>
                <span class="text">
                    Dashboard
                </span>

            </a>
        </li>
        <li>
            <a href="student_management.php"
                class=" flex items-center px-10 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'student_management.php') ? 'bg-emerald-600 text-white' : ''; ?>">

                <i class='fa-solid fa-book-open-reader text-md -mx-2 mr-3'></i>
                <span class="text-sm">
                    Student Management
                </span>

            </a>
        </li>
        <li>
            <a href="pos.php"
                class=" flex items-center px-10 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'pos.php') ? 'bg-emerald-600 text-white' : ''; ?>">


                <i class='fa-solid fa-coins -mx-2 mr-3'></i>
                <span class="text">
                    POS
                </span>

            </a>
        </li>

        <li class="border-2 border-dashed p-2 space-y-2" id="management-zone">
            <div class="flex flex-row ">
                <i class=" fa-solid fa-gears mx-2"></i>
                <span class="">Management Zone</span>
            </div>

            <a href="program_management.php"
                class=" flex items-center px-5 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'program_management.php') ? 'bg-emerald-600 text-white' : ''; ?>">


                <i class='fa-solid fa-graduation-cap -mx-2 mr-3 '></i>
                <span class="text-xs">
                    Program Management
                </span>

            </a>
            <a href="reports.php"
                class=" flex items-center px-5 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'reports.php') ? 'bg-emerald-600 text-white' : ''; ?>">


                <i class='fa-solid fa-file -mx-2 mr-3 '></i>
                <span class="text-xs">
                    Reports
                </span>

            </a>

            <!-- <a href="enrollment_details.php"
                class=" flex items-center px-5 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'enrollment_details.php') ? 'bg-emerald-600 text-white' : ''; ?>">


                <i class='fa-solid fa-circle-info -mx-2 mr-3'></i>

                <span class="text-xs">
                    Package Details
                </span

            </a> -->

            <a href="teacher_management.php"
                class=" flex items-center px-5 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'teacher_management.php') ? 'bg-emerald-600 text-white' : ''; ?>">


                <i class='fa-solid fa-users-gear -mx-2 mr-3'></i>
                <span class="text-xs">
                    Instructor Management
                </span>

            </a>
            <?php if ($_SESSION['level'] == "Admin") { ?>
                <a href="user_management.php"
                    class=" flex items-center px-5 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'user_management.php') ? 'bg-emerald-600 text-white' : ''; ?>">


                    <i class='fa-solid fa-users-gear -mx-2 mr-3'></i>
                    <span class="text-xs">
                        Users Management
                    </span>

                </a>
                <a href="level_manage.php"
                    class=" flex items-center px-5 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'level_manage.php') ? 'bg-emerald-600 text-white' : ''; ?>">


                    <i class='fa-solid fa-users-gear -mx-2 mr-3'></i>
                    <span class="text-xs">
                        Manage Level
                    </span>

                </a>

                <a href="logs.php"
                    class=" flex items-center px-5 py-3 text-primary border-2 rounded-lg font-medium justify-start <?php echo ($currentPage == 'logs.php') ? 'bg-emerald-600 text-white' : ''; ?>">



                    <i class="fa-solid fa-list -mx-2 mr-3"></i>
                    <span class="text-xs">
                        System logs
                    </span>

                </a>
            <?php } ?>


        </li>



    </ul>
</nav>

<!-- User Profile -->
<div class="px-5 py-3 border-t border-gray-100">
    <div class="flex items-center">
        <img src="/api/placeholder/40/40" class="w-10 h-10 rounded-full" alt="User" />
        <div class="ml-3">
            <p class="text-sm font-medium">Admin Number <?php echo $_SESSION['user_id'] ?></p>
            <p class="text-xs text-gray-500">Administrator</p>
        </div>
    </div>
</div>