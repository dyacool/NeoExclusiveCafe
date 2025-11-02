<?php
require_once __DIR__ . '/../pages/admin-includes/database.php';

echo "=== Verifying POD Orders Migration ===\n\n";

// Check if table exists
echo "1. Checking pod_orders table...\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'pod_orders'");
if (mysqli_num_rows($result) > 0) {
    echo "✓ Table exists\n\n";
    
    // Show table structure
    echo "2. Table structure:\n";
    $result = mysqli_query($conn, "DESCRIBE pod_orders");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   - {$row['Field']}: {$row['Type']} (Null: {$row['Null']}, Key: {$row['Key']}, Default: {$row['Default']})\n";
    }
    
    // Check indexes
    echo "\n3. Checking indexes...\n";
    $result = mysqli_query($conn, "SHOW INDEX FROM pod_orders");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   - {$row['Key_name']} on column {$row['Column_name']}\n";
    }
    
    // Check foreign key
    echo "\n4. Checking foreign key constraint...\n";
    $result = mysqli_query($conn, "
        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'pod_orders'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "   ✓ {$row['CONSTRAINT_NAME']}: References {$row['REFERENCED_TABLE_NAME']}({$row['REFERENCED_COLUMN_NAME']})\n";
        }
    } else {
        echo "   ✗ No foreign key found\n";
    }
} else {
    echo "✗ Table does not exist\n";
}

// Check upload directory
echo "\n5. Checking upload directory...\n";
$upload_dir = __DIR__ . '/../../uploads/delivery-proofs';
if (file_exists($upload_dir)) {
    echo "✓ Directory exists: $upload_dir\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "\n";
    
    // Check .htaccess
    if (file_exists($upload_dir . '/.htaccess')) {
        echo "✓ .htaccess file exists\n";
    } else {
        echo "✗ .htaccess file missing\n";
    }
} else {
    echo "✗ Directory does not exist\n";
}

echo "\n=== Verification Complete ===\n";
?>
