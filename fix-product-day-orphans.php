<?php
require_once __DIR__ . '/config/database-config.php';
$conn = getDatabaseConnection();

echo "=== FINDING VALID PRODUCT IDs ===\n\n";

// Get all product IDs from products table
$result = $conn->query("SELECT id, sku, name, status_id FROM products WHERE deleted_at IS NULL ORDER BY id");
echo "Valid Product IDs in products table:\n";
$validIds = [];
while ($row = $result->fetch_assoc()) {
    $validIds[] = $row['id'];
    echo "  ID: {$row['id']} - SKU: {$row['sku']} - {$row['name']} (Status: {$row['status_id']})\n";
}

echo "\n=== ORPHANED RECORDS IN product_day ===\n\n";

// Find orphaned records
$result = $conn->query("
    SELECT pd.*, COUNT(*) as day_count
    FROM product_day pd
    LEFT JOIN products p ON pd.product_id = p.id
    WHERE p.id IS NULL
    GROUP BY pd.product_id
");

echo "Product IDs in product_day that DON'T exist in products table:\n";
$orphanedIds = [];
while ($row = $result->fetch_assoc()) {
    $orphanedIds[] = $row['product_id'];
    echo "  Product ID: {$row['product_id']} - {$row['day_count']} day records\n";
}

echo "\n=== CLEANUP PLAN ===\n\n";
echo "Total orphaned product IDs: " . count($orphanedIds) . "\n";
echo "Total valid product IDs: " . count($validIds) . "\n\n";

if (!empty($orphanedIds)) {
    $orphanedIdsList = implode(', ', $orphanedIds);
    echo "SQL to clean up orphaned records:\n";
    echo "DELETE FROM product_day WHERE product_id IN ($orphanedIdsList);\n\n";
    
    echo "Do you want to execute this cleanup? (This will be done manually)\n";
}

$conn->close();
?>
