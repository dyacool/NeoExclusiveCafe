<?php
require_once "../../php/includes/database.php";

function checkTable($conn, $tableName, $requiredColumns) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($result->num_rows === 0) {
        echo "Table '$tableName' does not exist\n";
        return false;
    }

    $result = $conn->query("DESCRIBE $tableName");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $missingColumns = array_diff($requiredColumns, $columns);
    if (!empty($missingColumns)) {
        echo "Table '$tableName' is missing columns: " . implode(', ', $missingColumns) . "\n";
        return false;
    }

    echo "Table '$tableName' exists and has all required columns\n";
    return true;
}

// Check orders table
$ordersColumns = [
    'order_id',
    'order_date',
    'delivery_method',
    'customer_name',
    'status',
    'pickup_date',
    'delivery_date',
    'pickup_time',
    'delivery_time',
    'total_amount',
    'customer_id'
];

// Check customers table
$customersColumns = [
    'customer_id',
    'contact',
    'address'
];

$ordersOk = checkTable($conn, 'orders', $ordersColumns);
$customersOk = checkTable($conn, 'customers', $customersColumns);

if ($ordersOk && $customersOk) {
    echo "All required tables and columns exist\n";
} else {
    echo "Some tables or columns are missing\n";
}
?> 