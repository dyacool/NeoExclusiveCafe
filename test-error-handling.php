<?php
/**
 * Test Error Handling for Cloudinary Integration
 * 
 * This script tests the comprehensive error handling implementation
 * for both upload and display operations.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database-config.php';
require_once __DIR__ . '/backend/includes/cloudinary-helper.php';
require_once __DIR__ . '/backend/includes/cloudinary-image-fetcher.php';

echo "<h1>Cloudinary Error Handling Tests</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; color: #155724; }
    .error { background-color: #f8d7da; color: #721c24; }
    .info { background-color: #d1ecf1; color: #0c5460; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Test 1: Upload Error Handling - File Not Found
echo "<div class='test-section'>";
echo "<h2>Test 1: Upload Error Handling - File Not Found</h2>";
$result = uploadToCloudinary('/nonexistent/file.jpg', 'neocafe/products', 'test_image');
if (!$result['success']) {
    echo "<div class='success'>✓ Correctly handled missing file</div>";
    echo "<pre>Error: " . htmlspecialchars($result['error']) . "</pre>";
    echo "<pre>Error Code: " . htmlspecialchars($result['error_code']) . "</pre>";
} else {
    echo "<div class='error'>✗ Should have failed for missing file</div>";
}
echo "</div>";

// Test 2: Upload Error Handling - Invalid Image
echo "<div class='test-section'>";
echo "<h2>Test 2: Upload Error Handling - Invalid Image</h2>";
// Create a temporary non-image file
$tempFile = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($tempFile, 'This is not an image');
$result = uploadToCloudinary($tempFile, 'neocafe/products', 'test_image');
unlink($tempFile);
if (!$result['success']) {
    echo "<div class='success'>✓ Correctly handled invalid image</div>";
    echo "<pre>Error: " . htmlspecialchars($result['error']) . "</pre>";
    echo "<pre>Error Code: " . htmlspecialchars($result['error_code']) . "</pre>";
} else {
    echo "<div class='error'>✗ Should have failed for invalid image</div>";
}
echo "</div>";

// Test 3: Display Error Handling - Invalid Product ID
echo "<div class='test-section'>";
echo "<h2>Test 3: Display Error Handling - Invalid Product ID</h2>";
try {
    $conn = getDatabaseConnection();
    
    // Check if cloudinary_url column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'cloudinary_url'");
    if ($checkColumn->num_rows == 0) {
        echo "<div class='info'>ℹ Skipping test - cloudinary_url column not yet migrated</div>";
        echo "<pre>Run add-cloudinary-columns.php to add the required columns</pre>";
    } else {
        $fetcher = new CloudinaryImageFetcher($conn);
        $result = $fetcher->fetchProductImage(999999); // Non-existent product
        echo "<div class='error'>✗ Should have thrown exception for invalid product</div>";
    }
} catch (Exception $e) {
    echo "<div class='success'>✓ Correctly threw exception for invalid product</div>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
echo "</div>";

// Test 4: Display Error Handling - Safe Fetch with Placeholder
echo "<div class='test-section'>";
echo "<h2>Test 4: Display Error Handling - Safe Fetch with Placeholder</h2>";
try {
    $conn = getDatabaseConnection();
    
    // Check if cloudinary_url column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'cloudinary_url'");
    if ($checkColumn->num_rows == 0) {
        echo "<div class='info'>ℹ Skipping test - cloudinary_url column not yet migrated</div>";
        echo "<pre>Run add-cloudinary-columns.php to add the required columns</pre>";
    } else {
        $fetcher = new CloudinaryImageFetcher($conn);
        $result = $fetcher->fetchProductImageSafe(999999); // Non-existent product
        if ($result['source'] === 'placeholder') {
            echo "<div class='success'>✓ Correctly returned placeholder for invalid product</div>";
            echo "<pre>Placeholder URL: " . htmlspecialchars($result['url']) . "</pre>";
            echo "<pre>Error: " . htmlspecialchars($result['error']) . "</pre>";
        } else {
            echo "<div class='error'>✗ Should have returned placeholder</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Should not have thrown exception (should return placeholder)</div>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
echo "</div>";

// Test 5: Display Error Handling - Batch Fetch with Skip Missing
echo "<div class='test-section'>";
echo "<h2>Test 5: Display Error Handling - Batch Fetch with Skip Missing</h2>";
try {
    $conn = getDatabaseConnection();
    
    // Check if cloudinary_url column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'cloudinary_url'");
    if ($checkColumn->num_rows == 0) {
        echo "<div class='info'>ℹ Skipping test - cloudinary_url column not yet migrated</div>";
        echo "<pre>Run add-cloudinary-columns.php to add the required columns</pre>";
    } else {
        $fetcher = new CloudinaryImageFetcher($conn);
        
        // Get some real product IDs
        $sql = "SELECT id FROM products WHERE deleted_at IS NULL LIMIT 3";
        $result = $conn->query($sql);
        $productIds = [];
        while ($row = $result->fetch_assoc()) {
            $productIds[] = $row['id'];
        }
        
        // Add some invalid IDs
        $productIds[] = 999998;
        $productIds[] = 999999;
        
        $images = $fetcher->fetchMultipleProductImages($productIds, ['width' => 300], true);
        echo "<div class='success'>✓ Successfully fetched images with skipMissing=true</div>";
        echo "<pre>Requested " . count($productIds) . " products, got " . count($images) . " images</pre>";
        echo "<pre>Product IDs with images: " . implode(', ', array_keys($images)) . "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Should not have thrown exception with skipMissing=true</div>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
echo "</div>";

// Test 6: Delete Error Handling - Empty Public ID
echo "<div class='test-section'>";
echo "<h2>Test 6: Delete Error Handling - Empty Public ID</h2>";
$result = deleteFromCloudinary('');
if (!$result['success']) {
    echo "<div class='success'>✓ Correctly handled empty public ID</div>";
    echo "<pre>Error: " . htmlspecialchars($result['error']) . "</pre>";
    echo "<pre>Error Code: " . htmlspecialchars($result['error_code']) . "</pre>";
} else {
    echo "<div class='error'>✗ Should have failed for empty public ID</div>";
}
echo "</div>";

// Test 7: Cloudinary Status Check
echo "<div class='test-section'>";
echo "<h2>Test 7: Cloudinary Status Check</h2>";
try {
    $conn = getDatabaseConnection();
    $fetcher = new CloudinaryImageFetcher($conn);
    $status = $fetcher->getCloudinaryStatus();
    if ($status['connected']) {
        echo "<div class='success'>✓ Cloudinary connection is active</div>";
    } else {
        echo "<div class='error'>✗ Cloudinary connection failed</div>";
    }
    echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT) . "</pre>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking Cloudinary status</div>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
echo "</div>";

// Test 8: Cache Statistics
echo "<div class='test-section'>";
echo "<h2>Test 8: Cache Statistics</h2>";
try {
    $conn = getDatabaseConnection();
    $fetcher = new CloudinaryImageFetcher($conn);
    $stats = $fetcher->getCacheStats();
    echo "<div class='info'>Cache Statistics</div>";
    echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Error getting cache stats</div>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
echo "</div>";

echo "<h2>All Tests Complete</h2>";
echo "<p>Check the error log for detailed logging information.</p>";
?>
