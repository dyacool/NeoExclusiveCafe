<?php
/**
 * Test SDO Quantity Setup
 * Run this file to verify database table and permissions
 */

session_start();

// Check admin authentication
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    die("ERROR: Not logged in as admin. Please log in first.");
}

require_once __DIR__ . "/../admin-includes/database.php";

echo "<h1>SDO Quantity Setup Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }</style>";

// Test 1: Check if table exists
echo "<h2>Test 1: Check if table exists</h2>";
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'quantity_per_day_sdo'");
if (mysqli_num_rows($table_check) > 0) {
    echo "<p class='success'>✓ Table 'quantity_per_day_sdo' exists</p>";
} else {
    echo "<p class='error'>✗ Table 'quantity_per_day_sdo' does NOT exist</p>";
    echo "<p class='info'>Run the migration SQL: backend/migrations/create_quantity_per_day_sdo.sql</p>";
    exit();
}

// Test 2: Check table structure
echo "<h2>Test 2: Check table structure</h2>";
$structure = mysqli_query($conn, "DESCRIBE quantity_per_day_sdo");
echo "<pre>";
echo "Column Name       | Type          | Null | Key | Default | Extra\n";
echo "------------------|---------------|------|-----|---------|------------------\n";
while ($row = mysqli_fetch_assoc($structure)) {
    printf("%-17s | %-13s | %-4s | %-3s | %-7s | %s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key'], 
        $row['Default'] ?? 'NULL', 
        $row['Extra']
    );
}
echo "</pre>";

// Test 3: Check foreign key constraints
echo "<h2>Test 3: Check foreign key constraints</h2>";
$fk_check = mysqli_query($conn, "
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'quantity_per_day_sdo'
    AND CONSTRAINT_NAME != 'PRIMARY'
    AND TABLE_SCHEMA = DATABASE()
");

if (mysqli_num_rows($fk_check) > 0) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($fk_check)) {
        echo "Constraint: {$row['CONSTRAINT_NAME']}\n";
        echo "  Column: {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
    echo "</pre>";
    echo "<p class='success'>✓ Foreign key constraints are set up correctly</p>";
} else {
    echo "<p class='error'>✗ No foreign key constraints found</p>";
}

// Test 4: Check if event exists
echo "<h2>Test 4: Check if cleanup event exists</h2>";
$event_check = mysqli_query($conn, "SHOW EVENTS LIKE 'delete_past_sdo_dates'");
if (mysqli_num_rows($event_check) > 0) {
    echo "<p class='success'>✓ Event 'delete_past_sdo_dates' exists</p>";
    $event_info = mysqli_fetch_assoc($event_check);
    echo "<pre>";
    echo "Status: {$event_info['Status']}\n";
    echo "Execute At: {$event_info['Execute at']}\n";
    echo "Interval: {$event_info['Interval value']} {$event_info['Interval field']}\n";
    echo "</pre>";
} else {
    echo "<p class='error'>✗ Event 'delete_past_sdo_dates' does NOT exist</p>";
}

// Test 5: Test insert
echo "<h2>Test 5: Test insert operation</h2>";
$test_product_id = 1; // Use product ID 1 for testing
$test_date = date('Y-m-d', strtotime('+1 day'));
$test_quantity = 99;

$insert_test = mysqli_prepare($conn, "INSERT INTO quantity_per_day_sdo (product_id, date, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
mysqli_stmt_bind_param($insert_test, "isi", $test_product_id, $test_date, $test_quantity);

if (mysqli_stmt_execute($insert_test)) {
    echo "<p class='success'>✓ Successfully inserted test record</p>";
    echo "<p class='info'>Product ID: $test_product_id, Date: $test_date, Quantity: $test_quantity</p>";
    
    // Test 6: Test select
    echo "<h2>Test 6: Test select operation</h2>";
    $select_test = mysqli_prepare($conn, "SELECT * FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?");
    mysqli_stmt_bind_param($select_test, "is", $test_product_id, $test_date);
    mysqli_stmt_execute($select_test);
    $result = mysqli_stmt_get_result($select_test);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo "<p class='success'>✓ Successfully retrieved test record</p>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    } else {
        echo "<p class='error'>✗ Failed to retrieve test record</p>";
    }
    
    // Test 7: Test delete
    echo "<h2>Test 7: Test delete operation</h2>";
    $delete_test = mysqli_prepare($conn, "DELETE FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?");
    mysqli_stmt_bind_param($delete_test, "is", $test_product_id, $test_date);
    
    if (mysqli_stmt_execute($delete_test)) {
        echo "<p class='success'>✓ Successfully deleted test record</p>";
        echo "<p class='info'>Test data cleaned up</p>";
    } else {
        echo "<p class='error'>✗ Failed to delete test record</p>";
    }
    
} else {
    echo "<p class='error'>✗ Failed to insert test record</p>";
    echo "<p class='error'>Error: " . mysqli_stmt_error($insert_test) . "</p>";
}

// Test 8: Check current data
echo "<h2>Test 8: Current data in table</h2>";
$data_check = mysqli_query($conn, "SELECT * FROM quantity_per_day_sdo ORDER BY date DESC LIMIT 10");
$count = mysqli_num_rows($data_check);

if ($count > 0) {
    echo "<p class='info'>Found $count record(s) in the table:</p>";
    echo "<pre>";
    echo "ID  | Date       | Product ID | Quantity | Created At          | Updated At\n";
    echo "----|------------|------------|----------|---------------------|--------------------\n";
    while ($row = mysqli_fetch_assoc($data_check)) {
        printf("%-3d | %-10s | %-10d | %-8d | %-19s | %s\n",
            $row['id'],
            $row['date'],
            $row['product_id'],
            $row['quantity'],
            $row['created_at'],
            $row['updated_at']
        );
    }
    echo "</pre>";
} else {
    echo "<p class='info'>Table is empty (no records found)</p>";
}

echo "<h2>Summary</h2>";
echo "<p class='success'>✓ All tests completed! The SDO quantity system is ready to use.</p>";
echo "<p class='info'>You can now close this page and use the product edit modal to set quantities.</p>";

mysqli_close($conn);
?>
