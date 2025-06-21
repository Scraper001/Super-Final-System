<?php
include "../../connection/connection.php";
$conn = con();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No receipt ID provided";
    exit;
}

$id = $_GET['id'];

// Fetch POS data
$sql = "SELECT * FROM pos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Receipt not found";
    exit;
}

$receipt = $result->fetch_assoc();

// Fetch tuition fee details
$tuition_sql = "SELECT * FROM tuitionfee WHERE program_name = ? AND package_number = ?";
$tuition_stmt = $conn->prepare($tuition_sql);
$tuition_stmt->bind_param("si", $receipt['program_name'], $receipt['package_number']);
$tuition_stmt->execute();
$tuition_result = $tuition_stmt->get_result();
$tuition_data = ($tuition_result->num_rows > 0) ? $tuition_result->fetch_assoc() : null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $receipt['id']; ?></title>

    <link rel="stylesheet" href="../../src/main.css">
    <link rel="stylesheet" href="../../src/output.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Anta&family=Bai+Jamjuree:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;1,200;1,300;1,400;1,500;1,600;1,700&family=Baloo+2:wght@400..800&family=Berkshire+Swash&family=Cal+Sans&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center p-5 font-sans">
    <div class="w-full max-w-4xl bg-white border shadow-lg p-6 rounded-md">
        <!-- Header -->
        <div class="flex items-center gap-4 border-2 border-green-500 p-4 rounded-xl text-green-800 font-bold italic">
            <img src="../../images/logo.png" class="w-20 h-20 object-contain" alt="Logo">
            <div>
                <h1 class="text-lg">CarePro</h1>
                <p class="text-sm">Official Receipt</p>
            </div>
        </div>

        <!-- Receipt Info -->
        <div class="border-y-2 border-dashed my-4 py-2">
            <p class="text-sm">Receipt #: <?php echo sprintf('%06d', $receipt['id']); ?></p>
            <p class="text-sm">Date: <?php echo date('F d, Y'); ?></p>
        </div>

        <!-- Student Info -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold mb-2">Student Information</h3>
            <table class="w-full text-sm border border-gray-300">
                <tr class="border-b">
                    <th class="p-2 text-left bg-gray-100">Student Name:</th>
                    <td class="p-2"><?php echo $receipt['student_name']; ?></td>
                    <th class="p-2 text-left bg-gray-100">Student Number:</th>
                    <td class="p-2"><?php echo $receipt['student_number']; ?></td>

                </tr>
                <tr>
                    <th class="p-2 text-left bg-gray-100">Program:</th>
                    <td class="p-2"><?php echo $receipt['program_name']; ?></td>
                    <th class="p-2 text-left bg-gray-100">Package Number:</th>
                    <td class="p-2"><?php echo $receipt['package_number']; ?></td>
                </tr>
                <tr>
                    <th class="p-2 text-left bg-gray-100">Learning Mode:</th>
                    <td class="p-2"><?php echo $receipt['program_name']; ?></td>
                    <th class="p-2 text-left bg-gray-100"></th>
                    <td class="p-2"></td>
                </tr>
            </table>
        </div>

        <!-- Payment Details -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold mb-2">Payment Details</h3>
            <table class="w-full text-sm border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left">Item</th>
                        <th class="p-2 text-left">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tuition_data): ?>
                        <tr>
                            <td class="p-2">Tuition Fee</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['tuition Fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">Miscellaneous Fee</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['misc Fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">OJT Medical</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['ojtmedical'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">System Fee</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['system Fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">Assessment Fee</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['assessment Fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">Uniform</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['uniform'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">ID Fee</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['IDfee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">Books</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['books'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="p-2">Kit</td>
                            <td class="p-2">₱<?php echo number_format($tuition_data['kit'], 2); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="font-semibold border-t">
                        <td class="p-2">Total Tuition</td>
                        <td class="p-2">
                            ₱<?php echo $tuition_data ? number_format($tuition_data['totalbasedtuition'], 2) : 'N/A'; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Current Payment -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold mb-2">Current Payment</h3>
            <table class="w-full text-sm border border-gray-300">
                <tr class="border-b">
                    <th class="p-2 text-left bg-gray-100">Payment Type:</th>
                    <td class="p-2"><?php echo $receipt['type']; ?></td>
                    <th class="p-2 text-left bg-gray-100">Payment Status:</th>
                    <td class="p-2"><?php echo $receipt['payment_status']; ?></td>
                </tr>
                <tr class="border-b">
                    <th class="p-2 text-left bg-gray-100">Demo 1:</th>
                    <td class="p-2">₱<?php echo number_format($receipt['demo1'], 2); ?></td>
                    <th class="p-2 text-left bg-gray-100">Demo 2:</th>
                    <td class="p-2">₱<?php echo number_format($receipt['demo2'], 2); ?></td>
                </tr>
                <tr class="border-b">
                    <th class="p-2 text-left bg-gray-100">Cash Amount:</th>
                    <td class="p-2">₱<?php echo number_format($receipt['cash'], 2); ?></td>
                    <th class="p-2 text-left bg-gray-100">Change:</th>
                    <td class="p-2">₱<?php echo number_format($receipt['change'], 2); ?></td>
                </tr>
                <tr>
                    <th class="p-2 text-left bg-gray-100">Discount:</th>
                    <td class="p-2"><?php echo $receipt['discount']; ?>%</td>
                    <th class="p-2 text-left bg-gray-100">Remaining Balance:</th>
                    <td class="p-2">₱<?php echo number_format($receipt['balance'], 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- Amount Paid -->
        <div class="text-right font-bold text-green-700 mb-4">
            <p>Amount Paid: ₱<?php echo number_format($receipt['cash'] - $receipt['change'], 2); ?></p>
        </div>

        <!-- Signature -->
        <div class="text-center mt-10">
            <p>_________________________</p>
            <p class="text-sm">Authorized Signature</p>
        </div>

        <!-- Buttons -->
        <div class="mt-6 flex justify-between">
            <button onclick="window.print()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Print
                Receipt</button>
            <a href="../pos.php" class="bg-gray-300 text-black px-4 py-2 rounded hover:bg-gray-400">Back</a>
        </div>
    </div>
</body>


</html>