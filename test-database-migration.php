<?php
/**
 * Test database migration for Cloudinary integration
 * Verifies all requirements from 5.1 to 5.5
 */

require_once __DIR__ . '/config/database-config.php';

$conn = getDatabaseConnection();

echo "=== Testing Database Migration ===\n\n";

$allTestsPassed = true;

// Test 5.1: Verify cloud_url column exists for primary images
echo "Test 5.1: Verify cloud_url column exists...\n";
$sql = "SHOW COLUMNS FROM product_images LIKE 'cloud_url'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $column = $result->fetch_assoc();
    if (strtolower($column['Type']) === 'text') {
        echo "✓ PASS: cloud_url column exists with correct type (TEXT)\n";
    } else {
        echo "✗ FAIL: cloud_url column has wrong type: " . $column['Type'] . "\n";
        $allTestsPassed = false;
    }
} else {
    echo "✗ FAIL: cloud_url column does not exist\n";
    $allTestsPassed = false;
}
echo "\n";

// Test 5.2: Verify cloud_public_id column exists for additional images
echo "Test 5.2: Verify cloud_public_id column exists...\n";
$sql = "SHOW COLUMNS FROM product_images LIKE 'cloud_public_id'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $column = $result->fetch_assoc();
    echo "✓ PASS: cloud_public_id column exists with type: " . $column['Type'] . "\n";
} else {
    echo "✗ FAIL: cloud_public_id column does not exist\n";
    $allTestsPassed = false;
}
echo "\n";

// Test 5.3: Verify cloud_provider column exists
echo "Test 5.3: Verify cloud_provider column exists...\n";
$sql = "SHOW COLUMNS FROM product_images LIKE 'cloud_provider'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $column = $result->fetch_assoc();
    echo "✓ PASS: cloud_provider column exists with type: " . $column['Type'] . "\n";
} else {
    echo "✗ FAIL: cloud_provider column does not exist\n";
    $allTestsPassed = false;
}
echo "\n";

// Test 5.4: Verify indexes exist for performance
echo "Test 5.4: Verify performance indexes exist...\n";
$sql = "SHOW INDEX FROM product_images WHERE Key_name IN ('idx_cloud_public_id', 'idx_product_id', 'idx_product_primary')";
$result = $conn->query($sql);
$indexes = [];
while ($row = $result->fetch_assoc()) {
    $indexes[] = $row['Key_name'];
}
$indexes = array_unique($indexes);

$requiredIndexes = ['idx_cloud_public_id', 'idx_product_id', 'idx_product_primary'];
$missingIndexes = array_diff($requiredIndexes, $indexes);

if (empty($missingIndexes)) {
    echo "✓ PASS: All required indexes exist:\n";
    foreach ($indexes as $index) {
        echo "  - $index\n";
    }
} else {
    echo "✗ FAIL: Missing indexes: " . implode(', ', $missingIndexes) . "\n";
    $allTestsPassed = false;
}
echo "\n";

// Test 5.5: Test querying products with Cloudinary URLs
echo "Test 5.5: Test querying products with Cloudinary columns...\n";
$sql = "SELECT p.id, p.name, pi.cloud_url, pi.cloud_public_id, pi.cloud_provider 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.deleted_at IS NULL
        LIMIT 5";
$result = $conn->query($sql);

if ($result) {
    echo "✓ PASS: Successfully queried products with Cloudinary columns\n";
    echo "Sample data (first 5 products):\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-5s %-30s %-40s %-15s\n", "ID", "Name", "Cloud URL", "Provider");
    echo str_repeat("-", 100) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        $cloudUrl = $row['cloud_url'] ? substr($row['cloud_url'], 0, 37) . '...' : 'NULL';
        $provider = $row['cloud_provider'] ?? 'NULL';
        printf("%-5s %-30s %-40s %-15s\n", 
            $row['id'], 
            substr($row['name'], 0, 28),
            $cloudUrl,
            $provider
        );
    }
    echo str_repeat("-", 100) . "\n";
} else {
    echo "✗ FAIL: Error querying products: " . $conn->error . "\n";
    $allTestsPassed = false;
}
echo "\n";

// Additional test: Check data types are correct
echo "Additional Test: Verify all column data types...\n";
$sql = "DESCRIBE product_images";
$result = $conn->query($sql);

$columnTypes = [];
while ($row = $result->fetch_assoc()) {
    $columnTypes[$row['Field']] = $row['Type'];
}

$expectedTypes = [
    'cloud_url' => 'text',
    'cloud_public_id' => 'varchar(255)',
    'cloud_provider' => 'varchar(50)'
];

$typeErrors = [];
foreach ($expectedTypes as $column => $expectedType) {
    if (isset($columnTypes[$column])) {
        if (strtolower($columnTypes[$column]) !== strtolower($expectedType)) {
            $typeErrors[] = "$column: expected $expectedType, got " . $columnTypes[$column];
        }
    }
}

if (empty($typeErrors)) {
    echo "✓ PASS: All column data types are correct\n";
} else {
    echo "✗ FAIL: Data type mismatches:\n";
    foreach ($typeErrors as $error) {
        echo "  - $error\n";
    }
    $allTestsPassed = false;
}
echo "\n";

// Final summary
echo "=== Test Summary ===\n";
if ($allTestsPassed) {
    echo "✅ ALL TESTS PASSED - Database migration successful!\n";
    echo "\nThe database is ready for Cloudinary integration:\n";
    echo "- All required columns exist with correct data types\n";
    echo "- Performance indexes are in place\n";
    echo "- Queries work correctly\n";
} else {
    echo "❌ SOME TESTS FAILED - Please review errors above\n";
}

$conn->close();
?>
