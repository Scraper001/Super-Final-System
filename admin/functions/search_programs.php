<?php
include "../../connection/connection.php";

$conn = con();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';

if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);

    $whereClause = "WHERE 
    `program_name` LIKE '%$searchEscaped%' OR
    `trainor_id` LIKE '%$searchEscaped%' OR 
    `class_schedule` LIKE '%$searchEscaped%' OR 
    `OpeningClasses` LIKE '%$searchEscaped%' OR 
    `ClossingClasses` LIKE '%$searchEscaped%' 
";


}

$sql = "SELECT * FROM programs $whereClause ORDER BY id ASC LIMIT 50";
$result = mysqli_query($conn, $sql);

if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)):
        $id = $row['id'];
        $program_name = $row['program_name'];
        $trainor_id = $row['trainor_id'];
        $class_schedule = $row['class_schedule'];
        $OpenningClasses = $row['OpeningClasses'];
        $ClossingClasses = $row['ClossingClasses'];
        ?>

        <tr class="">

            <td class="px-4 py-2  "><?php echo $program_name ?></td>
            <td class="px-4 py-2  "><?php echo $trainor_id ?></td>
            <td class="px-4 py-2  "><?php echo $class_schedule ?></td>
            <td class="px-4 py-2  "><?php echo $OpenningClasses ?></td>
            <td class="px-4 py-2  "><?php echo $ClossingClasses ?></td>
            <td class="px-4 py-2 flex flex-row">
                <button class="btn-warning mx-2 text-sm" onclick="openEditModal(this)"
                    data-program-id="<?php echo htmlspecialchars($id) ?>"
                    data-program-name=" <?php echo htmlspecialchars($program_name) ?>"
                    data-trainor-id="<?php echo htmlspecialchars($trainor_id) ?>"
                    data-class-schedule="<?php echo htmlspecialchars($class_schedule) ?>"
                    data-openning-classes="<?php echo htmlspecialchars($OpenningClasses) ?>"
                    data-clossing-classes="<?php echo htmlspecialchars($ClossingClasses) ?>">
                    Edit Program
                </button>

                <button class="btn-danger mx-2 text-sm" onclick="openDeleteModal(this)"
                    data-program-id="<?php echo htmlspecialchars($row['id']) ?>"
                    data-program-name=" <?php echo htmlspecialchars($row['program_name']) ?>">
                    Delete Program
                </button>
            </td>
        </tr>

    <?php endwhile;
} else { ?>
    <tr>
        <td class="px-6 py-4 whitespace-nowrap text-center" colspan="6">
            <span>We couldn't find anything matching your search.</span>
            <i class="fa-regular fa-face-sad-tear"></i>
        </td>
    </tr>
<?php } ?>