<?php
/**
 * Cloudinary Status Checker
 * Check if Cloudinary is working and if products have cloudinary_url
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cloudinary Status Check</h1>";

// Test 1: Check if vendor/autoload.php exists
echo "<h2>1. Checking vendor/autoload.php</h2>";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✓ vendor/autoload.php EXISTS<br>";
    require_once $autoloadPath;
} else {
    echo "✗ vendor/autoload.php NOT FOUND at: $autoloadPath<br>";
    echo "<strong>ACTION NEEDED: Run 'composer install'</strong><br>";
    exit;
}

// Test 2: Check Cloudinary config
echo "<h2>2. Checking Cloudinary Configuration</h2>";
try {
    require_once __DIR__ . '/config/cloudinary-config.php';
    echo "✓ cloudinary-config.php loaded<br>";
    
    $cloudinaryConfig = CloudinaryConfig::getInstance();
    echo "✓ CloudinaryConfig instance created<br>";
    
    $testResult = $cloudinaryConfig->testConnection();
    if ($testResult['success']) {
        echo "✓ Cloudinary connection SUCCESSFUL<br>";
        echo "Cloud Name: " . $testResult['cloud_name'] . "<br>";
    } else {
        echo "✗ Cloudinary connection FAILED<br>";
        echo "Error: " . $testResult['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading Cloudinary: " . $e->getMessage() . "<br>";
}

// Test 3: Check database connection
echo "<h2>3. Checking Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database-config.php';
    $conn = getDatabaseConnection();
    echo "✓ Database connected<br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 4: Check if products table has cloudinary_url column
echo "<h2>4. Checking Products Table Structure</h2>";
$result = $conn->query("SHOW COLUMNS FROM products LIKE 'cloudinary_url'");
if ($result->num_rows > 0) {
    echo "✓ cloudinary_url column EXISTS in products table<br>";
} else {
    echo "✗ cloudinary_url column NOT FOUND in products table<br>";
    echo "<strong>ACTION NEEDED: Run database migration to add cloudinary_url column</strong><br>";
}

// Test 5: Check how many products have Cloudinary URLs
echo "<h2>5. Checking Product Images</h2>";
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL");
$total = $result->fetch_assoc()['total'];
echo "Total products: $total<br>";

$result = $conn->query("SELECT COUNT(*) as with_cloudinary FROM products WHERE deleted_at IS NULL AND cloudinary_url IS NOT NULL AND cloudinary_url != ''");
$withCloudinary = $result->fetch_assoc()['with_cloudinary'];
echo "Products with Cloudinary URL: $withCloudinary<br>";

$result = $conn->query("SELECT COUNT(*) as without_cloudinary FROM products WHERE deleted_at IS NULL AND (cloudinary_url IS NULL OR cloudinary_url = '')");
$withoutCloudinary = $result->fetch_assoc()['without_cloudinary'];
echo "Products WITHOUT Cloudinary URL: $withoutCloudinary<br>";

if ($withoutCloudinary > 0) {
    echo "<br><strong>⚠️ WARNING: $withoutCloudinary products don't have Cloudinary URLs!</strong><br>";
    echo "These products will show placeholder images.<br>";
    echo "<strong>ACTION NEEDED: Run image migration script to upload images to Cloudinary</strong><br>";
}

// Test 6: Show sample products
echo "<h2>6. Sample Products (first 5)</h2>";
$result = $conn->query("SELECT id, name, cloudinary_url FROM products WHERE deleted_at IS NULL LIMIT 5");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Cloudinary URL</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    $status = !empty($row['cloudinary_url']) ? '✓ Has URL' : '✗ No URL';
    $url = !empty($row['cloudinary_url']) ? htmlspecialchars(substr($row['cloudinary_url'], 0, 50)) . '...' : 'NULL';
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>$url</td><td>$status</td></tr>";
}
echo "</table>";

// Test 7: Test CloudinaryImageFetcher
echo "<h2>7. Testing CloudinaryImageFetcher</h2>";
try {
    require_once __DIR__ . '/backend/includes/cloudinary-image-fetcher.php';
    $fetcher = new CloudinaryImageFetcher($conn);
    echo "✓ CloudinaryImageFetcher instantiated<br>";
    
    $status = $fetcher->getCloudinaryStatus();
    if ($status['connected']) {
        echo "✓ Cloudinary API connection working<br>";
    } else {
        echo "✗ Cloudinary API connection failed: " . $status['error'] . "<br>";
    }
} catch (Exception $e) {
    echo "✗ Error with CloudinaryImageFetcher: " . $e->getMessage() . "<br>";
}

echo "<h2>Summary</h2>";
echo "<p>Check the results above to identify any issues.</p>";
echo "<p>If products don't have Cloudinary URLs, you need to run the migration script.</p>";

$conn->close();
?>
