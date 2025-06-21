<?php
include "../../connection/connection.php";

$conn = con();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';

if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);

    $whereClause = "WHERE 
        CONCAT(last_name, ' ', first_name, ' ', middle_name) LIKE '%$searchEscaped%' OR
        student_number LIKE '%$searchEscaped%' OR
        classification LIKE '%$searchEscaped%' OR
        civil_status LIKE '%$searchEscaped%' OR
        birthdate LIKE '%$searchEscaped%' OR
        region LIKE '%$searchEscaped%' OR
        province LIKE '%$searchEscaped%' OR
        city LIKE '%$searchEscaped%' OR
        brgy LIKE '%$searchEscaped%' OR
        purok LIKE '%$searchEscaped%' OR
        enrollment_status LIKE '%$searchEscaped%'";


}

$sql = "SELECT * FROM student_info_tbl $whereClause ORDER BY last_name ASC LIMIT 50";
$result = mysqli_query($conn, $sql);

if ($result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)):
        $fullName = $row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name'];
        $entryDate = date('F j, Y', strtotime($row['entry_date']));
        $classification = $row['classification'];
        $civilStatus = $row['civil_status'];

        $status = (!empty($row['enrollment_status']) && $row['enrollment_status'] != 'Not Enrolled' && $row['enrollment_status'] != 'No Reservation')
            ? $row['enrollment_status'] : 'Not Being Processed';

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

        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div>
                    <p class="text-sm font-medium"><?= $fullName ?></p>
                    <p class="text-xs text-gray-500">#<?= $row['student_number'] ?></p>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <p class="text-sm"><?= $entryDate ?></p>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full"><?= $classification ?></span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full"><?= $civilStatus ?></span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="<?= $badgeClass ?> text-xs px-2 py-1 rounded-full"><?= $status ?></span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <a href="student_view.php?id=<?= $row['id'] ?>" class="text-primary hover:underline text-sm">View</a>
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