<?php
include "../../connection/connection.php";

$conn = con();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';

if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);

    $whereClause = "WHERE 
    `program_name` LIKE '%$searchEscaped%' OR
    `package_number` LIKE '%$searchEscaped%' OR
    `tuition Fee` LIKE '%$searchEscaped%' OR
    `misc Fee` LIKE '%$searchEscaped%' OR
    `ojtmedical` LIKE '%$searchEscaped%' OR
    `system Fee` LIKE '%$searchEscaped%' OR
    `assessment Fee` LIKE '%$searchEscaped%' OR
    `uniform` LIKE '%$searchEscaped%' OR
    `IDfee` LIKE '%$searchEscaped%' OR
    `books` LIKE '%$searchEscaped%' OR
    `kit` LIKE '%$searchEscaped%' OR
    `demo1` LIKE '%$searchEscaped%' OR
    `demo2` LIKE '%$searchEscaped%' OR
    `demo3` LIKE '%$searchEscaped%' OR
    `demo4` LIKE '%$searchEscaped%' OR
    `totalbasedtuition` LIKE '%$searchEscaped%' 
";




}

$sql = "SELECT * FROM tuitionfee $whereClause ORDER BY id ASC LIMIT 50";
$result = mysqli_query($conn, $sql);

if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)):
        $id = $row['id'];
        $program_name = $row['program_name'];
        $package_number = $row['package_number'];
        $tuition = $row['tuition Fee'];
        $misc = $row['misc Fee'];
        $ojtmedical = $row['ojtmedical'];
        $system = $row['system Fee'];
        $assessment = $row['assessment Fee'];
        $uniform = $row['uniform'];
        $idfee = $row['IDfee'];
        $books = $row['books'];
        $kit = $row['kit'];
        $demo1 = $row['demo1'];
        $demo2 = $row['demo2'];
        $demo3 = $row['demo3'];
        $demo4 = $row['demo4'];
        $totalbasedtuition = $row['totalbasedtuition'];


        ?>

        <tr>
            <td class="px-4 py-3 font-semibold w-[180px]"><?php echo $program_name; ?></td>

            <td class="px-4 py-3">
                <?php
                $details = [
                    'Package Number' => $package_number,
                    'Tuition Fee' => $tuition,
                    'Miscellaneous Fee' => $misc,
                    'OJT Fee and Medical' => $ojtmedical,
                    'System Fee' => $system,
                    'Assessment Fee' => $assessment,
                    'Uniform' => $uniform,
                    'ID Fee' => $idfee,
                    'Book Cost' => $books,
                    'Kits' => $kit
                ];
                foreach ($details as $label => $value) {
                    echo "<div class='flex justify-between'><span class='font-medium w-40'>$label:</span><span class='italic text-right'>₱" . number_format($value, 2) . "</span></div>";
                }
                ?>
            </td>

            <td class="px-4 py-3">
                <?php
                for ($i = 1; $i <= 4; $i++) {
                    $demoVar = ${"demo$i"};
                    echo "<div class='flex justify-between'><span class='font-medium'>Demo $i:</span><span class='italic'>₱" . number_format($demoVar, 2) . "</span></div>";
                }
                ?>
            </td>

            <td class="px-4 py-3 text-right font-semibold text-green-700">
                ₱<?php echo number_format($totalbasedtuition, 2); ?>
            </td>

            <td class="px-4 py-3 flex gap-2 flex-wrap">
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs"
                    onclick="openTuitionEdit(this)" data-program-id="<?= $id ?>" data-program-name="<?= $program_name ?>"
                    data-package-number="<?= $package_number ?>" data-tuition-fee="<?= $tuition ?>" data-misc-fee="<?= $misc ?>"
                    data-ojtmedical="<?= $ojtmedical ?>" data-system-fee="<?= $system ?>"
                    data-assessment-fee="<?= $assessment ?>" data-uniform="<?= $uniform ?>" data-idfee="<?= $idfee ?>"
                    data-books="<?= $books ?>" data-kit="<?= $kit ?>" data-demo1="<?= $demo1 ?>" data-demo2="<?= $demo2 ?>"
                    data-demo3="<?= $demo3 ?>" data-demo4="<?= $demo4 ?>" data-total="<?= $totalbasedtuition ?>">
                    Edit
                </button>

                <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs"
                    onclick="openDeleteTuition(this)" data-program-id="<?php echo htmlspecialchars($id) ?>"
                    data-program-name="<?php echo htmlspecialchars($program_name) ?>">
                    Delete
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