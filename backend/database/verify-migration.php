<?php
require_once __DIR__ . '/../pages/admin-includes/database.php';

echo "=== Verifying Migration ===\n\n";

// Check if table exists
echo "1. Checking order_status_settings table...\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'order_status_settings'");
if (mysqli_num_rows($result) > 0) {
    echo "✓ Table exists\n\n";
    
    // Show table structure
    echo "2. Table structure:\n";
    $result = mysqli_query($conn, "DESCRIBE order_status_settings");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   - {$row['Field']}: {$row['Type']} (Null: {$row['Null']}, Key: {$row['Key']}, Default: {$row['Default']})\n";
    }
    
    // Check default record
    echo "\n3. Checking default record...\n";
    $result = mysqli_query($conn, "SELECT * FROM order_status_settings WHERE admin_id IS NULL");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "✓ Default record exists\n";
        echo "   - auto_status_enabled: {$row['auto_status_enabled']}\n";
        echo "   - updated_at: {$row['updated_at']}\n";
    } else {
        echo "✗ Default record not found\n";
    }
} else {
    echo "✗ Table does not exist\n";
}

// Check indexes on orders table
echo "\n4. Checking indexes on orders table...\n";
$result = mysqli_query($conn, "SHOW INDEX FROM orders WHERE Key_name LIKE 'idx_%'");
$indexes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $indexes[] = $row['Key_name'];
}

$expected_indexes = [
    'idx_delivery_date',
    'idx_pickup_date',
    'idx_status',
    'idx_delivery_method_date_status',
    'idx_delivery_method_pickup_status'
];

foreach ($expected_indexes as $index) {
    if (in_array($index, $indexes)) {
        echo "✓ Index $index exists\n";
    } else {
        echo "✗ Index $index missing\n";
    }
}

echo "\n=== Verification Complete ===\n";
?>
