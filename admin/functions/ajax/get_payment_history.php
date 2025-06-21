<?php
// functions/ajax/get_payment_history.php
include "../../../connection/connection.php";
$conn = con();

header('Content-Type: application/json');

try {
    $student_id = $_GET['student_id'] ?? null;
    $program_id = $_GET['program_id'] ?? null;

    if (!$student_id) {
        throw new Exception("Student ID is required");
    }

    // Build query based on parameters
    $query = "
        SELECT 
            pt.id,
            pt.transaction_date,
            p.program_name,
            pt.payment_type,
            pt.cash_received,
            pt.change_amount,
            pt.balance,
            pt.enrollment_status,
            pt.total_amount,
            CASE 
                WHEN pt.payment_type = 'balance_payment' THEN 'Balance Payment'
                WHEN pt.payment_type = 'full_payment' THEN 'Full Payment'
                WHEN pt.payment_type = 'initial_payment' THEN 'Initial Payment'
                WHEN pt.payment_type = 'demo_payment' THEN CONCAT('Demo Payment (', pt.demo_type, ')')
                WHEN pt.payment_type = 'reservation' THEN 'Reservation Fee'
                ELSE pt.payment_type
            END as payment_description
        FROM pos_transactions pt
        JOIN program p ON pt.program_id = p.id
        WHERE pt.student_id = ?
    ";

    $params = [$student_id];
    $types = "i";

    // Add program filter if specified
    if ($program_id) {
        $query .= " AND pt.program_id = ?";
        $params[] = $program_id;
        $types .= "i";
    }

    $query .= " ORDER BY pt.transaction_date DESC, pt.id DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $payment_history = [];
    $total_paid = 0;
    $current_balance = 0;

    while ($row = $result->fetch_assoc()) {
        $payment_history[] = [
            'transaction_id' => $row['id'],
            'date' => $row['transaction_date'],
            'program' => $row['program_name'],
            'payment_type' => $row['payment_description'],
            'amount_paid' => floatval($row['cash_received']),
            'change' => floatval($row['change_amount']),
            'balance_after' => floatval($row['balance']),
            'status' => $row['enrollment_status'],
            'total_amount' => floatval($row['total_amount'])
        ];

        $total_paid += floatval($row['cash_received']);
        $current_balance = floatval($row['balance']); // Will be the latest balance
    }

    // Get student info
    $student_stmt = $conn->prepare("SELECT CONCAT(fname, ' ', lname) as full_name FROM student_info_tbl WHERE id = ?");
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_info = $student_stmt->get_result()->fetch_assoc();

    // Get summary of outstanding balances by program
    $outstanding_stmt = $conn->prepare("
        SELECT 
            p.program_name,
            pt.balance,
            pt.total_amount
        FROM pos_transactions pt
        JOIN program p ON pt.program_id = p.id
        WHERE pt.student_id = ? AND pt.balance > 0
        GROUP BY pt.program_id
        HAVING pt.id = MAX(pt.id)
    ");
    $outstanding_stmt->bind_param("i", $student_id);
    $outstanding_stmt->execute();
    $outstanding_result = $outstanding_stmt->get_result();

    $outstanding_balances = [];
    $total_outstanding = 0;

    while ($row = $outstanding_result->fetch_assoc()) {
        $outstanding_balances[] = [
            'program' => $row['program_name'],
            'balance' => floatval($row['balance']),
            'total_amount' => floatval($row['total_amount'])
        ];
        $total_outstanding += floatval($row['balance']);
    }

    echo json_encode([
        'success' => true,
        'student_name' => $student_info['full_name'] ?? 'Unknown',
        'payment_history' => $payment_history,
        'summary' => [
            'total_payments_made' => $total_paid,
            'total_outstanding_balance' => $total_outstanding,
            'outstanding_by_program' => $outstanding_balances
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>