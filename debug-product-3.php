<?php
require_once __DIR__ . '/config/database-config.php';
$conn = getDatabaseConnection();

echo "=== DEBUGGING PRODUCT ID 3 ===\n\n";

// Check product table
$result = $conn->query("SELECT id, sku, name, status_id FROM products WHERE id = 3");
if ($row = $result->fetch_assoc()) {
    echo "Product Table:\n";
    echo "  ID: {$row['id']}\n";
    echo "  SKU: {$row['sku']}\n";
    echo "  Name: {$row['name']}\n";
    echo "  Status ID: {$row['status_id']}\n\n";
}

// Check product_day table
echo "Product Day Table:\n";
$result = $conn->query("SELECT * FROM product_day WHERE product_id = 3");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID: {$row['id']}, Product ID: {$row['product_id']}, Day: {$row['day_of_week']}\n";
    }
} else {
    echo "  No records found for product_id = 3\n";
}

echo "\n=== CHECKING FOREIGN KEY ===\n\n";
$result = $conn->query("
    SELECT 
        CONSTRAINT_NAME,
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
        echo "  Column: product_day.{$row['COLUMN_NAME']}\n";
        echo "  References: {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "No foreign keys found\n";
}

echo "\n=== ALL PRODUCTS IN product_day ===\n\n";
$result = $conn->query("
    SELECT pd.*, p.sku, p.name, p.status_id
    FROM product_day pd
    LEFT JOIN products p ON pd.product_id = p.id
    ORDER BY pd.product_id, pd.day_of_week
");

while ($row = $result->fetch_assoc()) {
    echo "Product ID {$row['product_id']} ({$row['sku']}) - Status: {$row['status_id']} - Day: {$row['day_of_week']}\n";
}

$conn->close();
?>
