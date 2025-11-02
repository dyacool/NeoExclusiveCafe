<?php
/**
 * Migration: Create pod_orders table for proof of delivery
 * Purpose: Store photographic proof of delivery for completed orders
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

echo "=== POD Orders Table Migration ===\n\n";

$errors = [];
$success = [];

// 1. Create pod_orders table
echo "Creating pod_orders table...\n";
$sql = "CREATE TABLE IF NOT EXISTS `pod_orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `proof_image_path` VARCHAR(255) NOT NULL COMMENT 'Relative path to proof image',
  `submitted_by` VARCHAR(100) NULL COMMENT 'Rider name or ID who submitted proof',
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image_size` INT(11) NULL COMMENT 'File size in bytes',
  `notes` TEXT NULL COMMENT 'Optional notes from rider',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order` (`order_id`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Stores proof of delivery images for completed orders'";

if (mysqli_query($conn, $sql)) {
    echo "✓ pod_orders table created successfully\n";
    $success[] = "pod_orders table created";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'already exists') !== false) {
        echo "✓ pod_orders table already exists\n";
        $success[] = "pod_orders table exists";
    } else {
        echo "✗ Error creating pod_orders table: " . $error . "\n";
        $errors[] = "Failed to create pod_orders table: " . $error;
    }
}

// 2. Add foreign key constraint
echo "\nAdding foreign key constraint...\n";

// Check if foreign key already exists
$check_fk_sql = "SELECT CONSTRAINT_NAME 
                 FROM information_schema.TABLE_CONSTRAINTS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'pod_orders' 
                 AND CONSTRAINT_NAME = 'fk_pod_order_id'";
$fk_result = mysqli_query($conn, $check_fk_sql);

if (mysqli_num_rows($fk_result) > 0) {
    echo "✓ Foreign key constraint already exists\n";
    $success[] = "Foreign key exists";
} else {
    $fk_sql = "ALTER TABLE `pod_orders` 
               ADD CONSTRAINT `fk_pod_order_id` 
               FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE";
    
    if (mysqli_query($conn, $fk_sql)) {
        echo "✓ Foreign key constraint added successfully\n";
        $success[] = "Foreign key added";
    } else {
        $error = mysqli_error($conn);
        echo "✗ Error adding foreign key: " . $error . "\n";
        $errors[] = "Failed to add foreign key: " . $error;
    }
}

// 3. Create uploads directory
echo "\nCreating uploads directory...\n";
$upload_dir = __DIR__ . '/../../../uploads/delivery-proofs';

if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "✓ Directory created: $upload_dir\n";
        $success[] = "Upload directory created";
    } else {
        echo "✗ Failed to create directory: $upload_dir\n";
        $errors[] = "Failed to create upload directory";
    }
} else {
    echo "✓ Directory already exists: $upload_dir\n";
    $success[] = "Upload directory exists";
}

// 4. Create .htaccess for security
echo "\nCreating .htaccess file...\n";
$htaccess_path = $upload_dir . '/.htaccess';
$htaccess_content = "# Prevent PHP execution in upload directory\n";
$htaccess_content .= "php_flag engine off\n";
$htaccess_content .= "AddType text/plain .php .php3 .php4 .php5 .phtml\n";

if (!file_exists($htaccess_path)) {
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        echo "✓ .htaccess file created for security\n";
        $success[] = ".htaccess created";
    } else {
        echo "⚠ Warning: Could not create .htaccess file\n";
    }
} else {
    echo "✓ .htaccess file already exists\n";
    $success[] = ".htaccess exists";
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
