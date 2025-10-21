<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Get parameters from URL
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$period = $_GET['period'] ?? '30days';

// Set default date range if not provided
if (empty($start_date) || empty($end_date)) {
    $end_date = date('Y-m-d');
    switch ($period) {
        case '7days':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            break;
        case '30days':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            break;
        case '90days':
            $start_date = date('Y-m-d', strtotime('-90 days'));
            break;
        default:
            $start_date = date('Y-m-d', strtotime('-30 days'));
    }
}

// Build query
$sql = "SELECT o.order_id, o.order_date, o.customer_name, o.payment_method, o.total_amount, o.status, o.delivery_method as order_type,
        o.pickup_date, o.delivery_date, o.customer_contact, o.customer_address
        FROM orders o
        WHERE (o.status IN ('Delivered', 'Picked-up', 'Completed'))
        AND (DATE(o.order_date) BETWEEN ? AND ?)
        ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_' . $start_date . '_to_' . $end_date . '.csv"');

// Create file pointer
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Order ID',
    'Date',
    'Customer Name',
    'Payment Method',
    'Status',
    'Amount',
    'Delivery Method',
    'Customer Contact',
    'Customer Address'
]);

// Payment method mapping
$paymentMethods = [
    '0' => 'Cash on Delivery',
    '1' => 'GCash',
    '2' => 'PayMaya',
    '3' => 'Bank Transfer'
];

// Add data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['order_id'],
        date('M d, Y', strtotime($row['order_date'])),
        $row['customer_name'],
        $paymentMethods[$row['payment_method']] ?? $row['payment_method'],
        $row['status'],
        '₱' . number_format($row['total_amount'], 2),
        $row['order_type'],
        $row['customer_contact'],
        $row['customer_address']
    ]);
}

fclose($output);
exit();
?>