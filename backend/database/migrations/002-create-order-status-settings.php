<?php
/**
 * Migration: Create order_status_settings table and add indexes
 * Purpose: Enable automatic order status management with toggle preference storage
 * Date: 2025-11-02
 */

// Prevent direct access
if (!defined('MIGRATION_RUNNER')) {
    // Allow execution from command line or if accessed directly with proper session
    session_start();
    if (php_sapi_name() !== 'cli' && (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true)) {
        die("Unauthorized access");
    }
}

require_once __DIR__ . '/../../pages/admin-includes/database.php';

echo "=== Order Status Settings Migration ===\n\n";

$errors = [];
$success = [];

// 1. Create order_status_settings table
echo "Creating order_status_settings table...\n";
$sql = "CREATE TABLE IF NOT EXISTS `order_status_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) DEFAULT NULL COMMENT 'NULL for global setting, or specific admin user ID',
  `auto_status_enabled` TINYINT(1) DEFAULT 0 COMMENT '0 = manual, 1 = automatic',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Stores auto-status toggle preferences for order management'";

if (mysqli_query($conn, $sql)) {
    echo "✓ order_status_settings table created successfully\n";
    $success[] = "order_status_settings table created";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'already exists') !== false) {
        echo "✓ order_status_settings table already exists\n";
        $success[] = "order_status_settings table exists";
    } else {
        echo "✗ Error creating order_status_settings table: " . $error . "\n";
        $errors[] = "Failed to create order_status_settings table: " . $error;
    }
}

// 2. Add indexes to orders table
echo "\nAdding indexes to orders table...\n";

$indexes = [
    'idx_delivery_date' => "CREATE INDEX `idx_delivery_date` ON `orders` (`delivery_date`)",
    'idx_pickup_date' => "CREATE INDEX `idx_pickup_date` ON `orders` (`pickup_date`)",
    'idx_status' => "CREATE INDEX `idx_status` ON `orders` (`status`)",
    'idx_delivery_method_date_status' => "CREATE INDEX `idx_delivery_method_date_status` ON `orders` (`delivery_method`, `delivery_date`, `status`)",
    'idx_delivery_method_pickup_status' => "CREATE INDEX `idx_delivery_method_pickup_status` ON `orders` (`delivery_method`, `pickup_date`, `status`)"
];

foreach ($indexes as $index_name => $sql) {
    // Check if index already exists
    $check_sql = "SHOW INDEX FROM `orders` WHERE Key_name = '$index_name'";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Index $index_name already exists\n";
        $success[] = "Index $index_name exists";
    } else {
        if (mysqli_query($conn, $sql)) {
            echo "✓ Index $index_name created successfully\n";
            $success[] = "Index $index_name created";
        } else {
            $error = mysqli_error($conn);
            echo "✗ Error creating index $index_name: " . $error . "\n";
            $errors[] = "Failed to create index $index_name: " . $error;
        }
    }
}

// 3. Insert default global setting
echo "\nInserting default global setting...\n";
$sql = "INSERT INTO `order_status_settings` (`admin_id`, `auto_status_enabled`) 
        VALUES (NULL, 0)
        ON DUPLICATE KEY UPDATE `auto_status_enabled` = `auto_status_enabled`";

if (mysqli_query($conn, $sql)) {
    echo "✓ Default global setting inserted/verified\n";
    $success[] = "Default setting configured";
} else {
    $error = mysqli_error($conn);
    echo "✗ Error inserting default setting: " . $error . "\n";
    $errors[] = "Failed to insert default setting: " . $error;
}

// Summary
echo "\n=== Migration Summary ===\n";
echo "Successful operations: " . count($success) . "\n";
echo "Failed operations: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n✗ Migration completed with errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
} else {
    echo "\n✓ Migration completed successfully!\n";
}

echo "\n=== End of Migration ===\n";
?>
