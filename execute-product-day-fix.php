<?php
require_once __DIR__ . '/config/database-config.php';
$conn = getDatabaseConnection();

echo "=== FIXING product_day TABLE ===\n\n";

// Step 1: Clean up orphaned records
echo "Step 1: Cleaning up orphaned records...\n";
$result = $conn->query("
    DELETE pd FROM product_day pd
    LEFT JOIN products p ON pd.product_id = p.id
    WHERE p.id IS NULL
");

if ($result) {
    echo "✓ Deleted " . $conn->affected_rows . " orphaned records\n\n";
} else {
    echo "✗ Error: " . $conn->error . "\n\n";
}

// Step 2: Add foreign key constraint
echo "Step 2: Adding foreign key constraint...\n";

// First, check if constraint already exists
$result = $conn->query("
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'product_day'
    AND CONSTRAINT_NAME = 'fk_product_day_product'
");

if ($result && $result->num_rows > 0) {
    echo "⚠ Foreign key constraint already exists, dropping it first...\n";
    $conn->query("ALTER TABLE product_day DROP FOREIGN KEY fk_product_day_product");
}

// Add the foreign key
$result = $conn->query("
    ALTER TABLE product_day
    ADD CONSTRAINT fk_product_day_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
");

if ($result) {
    echo "✓ Foreign key constraint added successfully\n";
    echo "  - When a product is deleted, its available days will be automatically deleted\n";
    echo "  - When a product ID is updated, the reference will be automatically updated\n\n";
} else {
    echo "✗ Error adding foreign key: " . $conn->error . "\n\n";
}

// Step 3: Verify the fix
echo "Step 3: Verifying the fix...\n";

$result = $conn->query("
    SELECT 
        pd.product_id,
        p.sku,
        p.name,
        p.status_id,
        GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as days
    FROM product_day pd
    INNER JOIN products p ON pd.product_id = p.id
    WHERE p.deleted_at IS NULL
    GROUP BY pd.product_id
    ORDER BY p.id
");

echo "Current valid records in product_day:\n";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = ($row['status_id'] == 4) ? 'Same Day Order' : 'Pre-Order';
        echo "  Product ID {$row['product_id']} ({$row['sku']}) - {$row['name']}\n";
        echo "    Status: $status\n";
        echo "    Days: {$row['days']}\n\n";
    }
} else {
    echo "  No records found (all products might be Same Day Order only)\n\n";
}

// Step 4: Check for Same Day Order products that shouldn't have days
echo "Step 4: Checking for Same Day Order products with available days...\n";
$result = $conn->query("
    SELECT p.id, p.sku, p.name, COUNT(pd.id) as day_count
    FROM products p
    INNER JOIN product_day pd ON p.id = pd.product_id
    WHERE p.status_id = 4
    AND p.deleted_at IS NULL
    GROUP BY p.id
");

if ($result && $result->num_rows > 0) {
    echo "⚠ WARNING: Found Same Day Order products with available days (these should be removed):\n";
    $idsToClean = [];
    while ($row = $result->fetch_assoc()) {
        echo "  Product ID {$row['id']} ({$row['sku']}) - {$row['name']} has {$row['day_count']} days\n";
        $idsToClean[] = $row['id'];
    }
    
    if (!empty($idsToClean)) {
        echo "\nCleaning up Same Day Order products...\n";
        $placeholders = implode(',', array_fill(0, count($idsToClean), '?'));
        $stmt = $conn->prepare("DELETE FROM product_day WHERE product_id IN ($placeholders)");
        $types = str_repeat('i', count($idsToClean));
        $stmt->bind_param($types, ...$idsToClean);
        
        if ($stmt->execute()) {
            echo "✓ Deleted " . $stmt->affected_rows . " day records from Same Day Order products\n\n";
        } else {
            echo "✗ Error: " . $stmt->error . "\n\n";
        }
        $stmt->close();
    }
} else {
    echo "✓ No Same Day Order products have available days (correct!)\n\n";
}

echo "=== FIX COMPLETE ===\n\n";
echo "Summary:\n";
echo "- Orphaned records removed\n";
echo "- Foreign key constraint added for data integrity\n";
echo "- Same Day Order products cleaned up\n";
echo "- update-product.php should now work correctly\n";

$conn->close();
?>
