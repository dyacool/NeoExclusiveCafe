<?php
require_once __DIR__ . '/config/database-config.php';
$conn = getDatabaseConnection();

echo "=== FINAL VERIFICATION ===\n\n";

// Show all products with their current status and available days
$result = $conn->query("
    SELECT 
        p.id,
        p.sku,
        p.name,
        p.status_id,
        ps.name as status_name,
        GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days
    FROM products p
    LEFT JOIN product_statuses ps ON p.status_id = ps.id
    LEFT JOIN product_day pd ON p.id = pd.product_id
    WHERE p.deleted_at IS NULL
    GROUP BY p.id
    ORDER BY p.id
");

echo "Current Products Status:\n";
echo str_repeat("-", 100) . "\n";
printf("%-5s %-12s %-40s %-20s %-30s\n", "ID", "SKU", "Product Name", "Status", "Available Days");
echo str_repeat("-", 100) . "\n";

while ($row = $result->fetch_assoc()) {
    $days = $row['available_days'] ?: 'N/A';
    printf("%-5s %-12s %-40s %-20s %-30s\n", 
        $row['id'], 
        $row['sku'], 
        substr($row['name'], 0, 38), 
        $row['status_name'],
        $days
    );
}

echo str_repeat("-", 100) . "\n\n";

// Check data integrity
echo "Data Integrity Check:\n";

$result = $conn->query("
    SELECT 
        CASE 
            WHEN p.status_id IN (1, 2, 3) AND pd.product_id IS NULL THEN 'WARNING'
            WHEN p.status_id = 4 AND pd.product_id IS NOT NULL THEN 'ERROR'
            ELSE 'OK'
        END as status,
        p.id,
        p.sku,
        p.status_id,
        CASE WHEN pd.product_id IS NOT NULL THEN 'Has Days' ELSE 'No Days' END as has_days
    FROM products p
    LEFT JOIN product_day pd ON p.id = pd.product_id
    WHERE p.deleted_at IS NULL
    GROUP BY p.id
    HAVING status != 'OK'
");

if ($result && $result->num_rows > 0) {
    echo "⚠ Issues found:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['status']}: Product {$row['id']} ({$row['sku']}) - Status: {$row['status_id']}, {$row['has_days']}\n";
    }
} else {
    echo "✓ All products have correct available days configuration\n";
    echo "  - Pre-Order products (status 1, 2, 3): Can have available days\n";
    echo "  - Same Day Order products (status 4): Should have NO available days\n";
}

echo "\n=== READY TO TEST ===\n\n";
echo "You can now test update-product.php:\n";
echo "1. Edit a Pre-Order product → Available days should be saved\n";
echo "2. Change a Pre-Order product to Same Day Order → Available days should be deleted\n";
echo "3. Change a Same Day Order product to Pre-Order → Available days should be saved\n";

$conn->close();
?>
