<?php
/**
 * Database preparation and migration for Cloudinary integration
 * 
 * This script verifies that the product_images table has the necessary
 * Cloudinary columns and creates indexes for optimal performance.
 */

require_once __DIR__ . '/config/database-config.php';

$conn = getDatabaseConnection();

echo "=== Cloudinary Database Preparation ===\n\n";

// Check if product_images table exists
$checkTableSql = "SHOW TABLES LIKE 'product_images'";
$result = $conn->query($checkTableSql);

if ($result->num_rows === 0) {
    echo "✗ Error: product_images table does not exist\n";
    echo "Please create the product_images table first.\n";
    $conn->close();
    exit(1);
}

echo "✓ product_images table exists\n\n";

// Check existing columns in product_images table
echo "Checking product_images table structure...\n";
$describeSql = "DESCRIBE product_images";
$result = $conn->query($describeSql);

$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

// Required Cloudinary columns
$requiredColumns = [
    'cloud_public_id' => "VARCHAR(255) NULL COMMENT 'Cloudinary public ID'",
    'cloud_provider' => "VARCHAR(50) NULL DEFAULT 'cloudinary' COMMENT 'Cloud storage provider'",
    'cloud_url' => "TEXT NULL COMMENT 'Full Cloudinary URL'"
];

$columnsAdded = 0;
$columnsExist = 0;

foreach ($requiredColumns as $columnName => $columnDef) {
    if (in_array($columnName, $existingColumns)) {
        echo "✓ Column '$columnName' already exists\n";
        $columnsExist++;
    } else {
        // Add missing column
        $sql = "ALTER TABLE product_images ADD COLUMN $columnName $columnDef";
        if ($conn->query($sql)) {
            echo "✓ Added column '$columnName'\n";
            $columnsAdded++;
        } else {
            echo "✗ Error adding column '$columnName': " . $conn->error . "\n";
        }
    }
}

echo "\n";

// Check and create indexes for performance
echo "Checking indexes...\n";

// Check if index on cloud_public_id exists
$indexCheckSql = "SHOW INDEX FROM product_images WHERE Key_name = 'idx_cloud_public_id'";
$result = $conn->query($indexCheckSql);

if ($result->num_rows === 0) {
    $sql = "CREATE INDEX idx_cloud_public_id ON product_images(cloud_public_id)";
    if ($conn->query($sql)) {
        echo "✓ Created index on cloud_public_id\n";
    } else {
        echo "✗ Error creating index on cloud_public_id: " . $conn->error . "\n";
    }
} else {
    echo "✓ Index on cloud_public_id already exists\n";
}

// Check if index on product_id exists
$indexCheckSql = "SHOW INDEX FROM product_images WHERE Key_name = 'idx_product_id'";
$result = $conn->query($indexCheckSql);

if ($result->num_rows === 0) {
    $sql = "CREATE INDEX idx_product_id ON product_images(product_id)";
    if ($conn->query($sql)) {
        echo "✓ Created index on product_id\n";
    } else {
        echo "✗ Error creating index on product_id: " . $conn->error . "\n";
    }
} else {
    echo "✓ Index on product_id already exists\n";
}

// Check if composite index on product_id and is_primary exists
$indexCheckSql = "SHOW INDEX FROM product_images WHERE Key_name = 'idx_product_primary'";
$result = $conn->query($indexCheckSql);

if ($result->num_rows === 0) {
    $sql = "CREATE INDEX idx_product_primary ON product_images(product_id, is_primary)";
    if ($conn->query($sql)) {
        echo "✓ Created composite index on product_id and is_primary\n";
    } else {
        echo "✗ Error creating composite index: " . $conn->error . "\n";
    }
} else {
    echo "✓ Composite index on product_id and is_primary already exists\n";
}

echo "\n";

// Verify final table structure
echo "Final product_images table structure:\n";
echo str_repeat("-", 80) . "\n";
printf("%-30s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
echo str_repeat("-", 80) . "\n";

$describeSql = "DESCRIBE product_images";
$result = $conn->query($describeSql);

while ($row = $result->fetch_assoc()) {
    printf("%-30s %-20s %-10s %-10s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key']
    );
}

echo str_repeat("-", 80) . "\n\n";

// Summary
echo "=== Migration Summary ===\n";
echo "Columns already existing: $columnsExist\n";
echo "Columns added: $columnsAdded\n";
echo "\n✅ Database preparation complete!\n\n";

echo "Next steps:\n";
echo "1. Verify the table structure above matches requirements\n";
echo "2. Update product upload code to use Cloudinary columns\n";
echo "3. Update product display code to fetch from Cloudinary\n";

$conn->close();
?>
