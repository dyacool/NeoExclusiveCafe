<?php
require_once __DIR__ . '/config/database-config.php';
$conn = getDatabaseConnection();

echo "=== CHECKING product_day TABLE STRUCTURE ===\n\n";

// Show table structure
$result = $conn->query("SHOW CREATE TABLE product_day");
if ($result) {
    $row = $result->fetch_assoc();
    echo "CREATE TABLE Statement:\n";
    echo $row['Create Table'] . "\n\n";
}

// Show columns
$result = $conn->query("DESCRIBE product_day");
echo "Columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Key']}\n";
}

echo "\n=== CHECKING FOREIGN KEY CONSTRAINTS ===\n\n";

// Check foreign keys
$result = $conn->query("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'product_day'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Foreign Key: {$row['CONSTRAINT_NAME']}\n";
        echo "  From: {$row['TABLE_NAME']}.{$row['COLUMN_NAME']}\n";
        echo "  To: {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n\n";
    }
} else {
    echo "No foreign key constraints found.\n";
}

echo "\n=== SAMPLE DATA ===\n\n";

// Show sample data
$result = $conn->query("SELECT * FROM product_day LIMIT 5");
echo "Sample records from product_day:\n";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID: {$row['id']}, Product ID: {$row['product_id']}, Day: {$row['day_of_week']}\n";
    }
} else {
    echo "  No records found.\n";
}

// Check if those product_ids exist in products table
echo "\n=== CHECKING IF product_day.product_id MATCHES products.id ===\n\n";
$result = $conn->query("
    SELECT 
        pd.product_id,
        COUNT(*) as count_in_product_day,
        EXISTS(SELECT 1 FROM products p WHERE p.id = pd.product_id) as exists_in_products,
        EXISTS(SELECT 1 FROM product_images pi WHERE pi.product_id = pd.product_id) as exists_in_product_images
    FROM product_day pd
    GROUP BY pd.product_id
    LIMIT 10
");

if ($result && $result->num_rows > 0) {
    echo "Product IDs in product_day:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  Product ID {$row['product_id']}: ";
        echo "Days: {$row['count_in_product_day']}, ";
        echo "In products table: " . ($row['exists_in_products'] ? 'YES' : 'NO') . ", ";
        echo "In product_images table: " . ($row['exists_in_product_images'] ? 'YES' : 'NO') . "\n";
    }
}

$conn->close();
?>
