<?php
/**
 * Cloudinary Upload Debug Test
 * Access this at: https://admin.neocafe.shop/test-upload-debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cloudinary Upload Debug</h1>";
echo "<pre>";

// Test 1: Check vendor/autoload.php
echo "=== Test 1: Vendor Autoload ===\n";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✓ vendor/autoload.php EXISTS\n";
    require_once $autoloadPath;
} else {
    echo "✗ vendor/autoload.php NOT FOUND\n";
    echo "Path checked: $autoloadPath\n";
    die("Cannot proceed without Composer dependencies\n");
}

// Test 2: Check Cloudinary Config
echo "\n=== Test 2: Cloudinary Config ===\n";
try {
    require_once __DIR__ . '/config/cloudinary-config.php';
    $config = CloudinaryConfig::getInstance();
    echo "✓ CloudinaryConfig loaded\n";
    
    $testConn = $config->testConnection();
    if ($testConn['success']) {
        echo "✓ Cloudinary connection SUCCESSFUL\n";
        echo "Cloud Name: " . $testConn['cloud_name'] . "\n";
    } else {
        echo "✗ Cloudinary connection FAILED\n";
        echo "Error: " . $testConn['message'] . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Test 3: Check cloudinary-helper.php
echo "\n=== Test 3: Cloudinary Helper ===\n";
try {
    require_once __DIR__ . '/backend/includes/cloudinary-helper.php';
    echo "✓ cloudinary-helper.php loaded\n";
    
    if (function_exists('uploadToCloudinary')) {
        echo "✓ uploadToCloudinary() function exists\n";
    } else {
        echo "✗ uploadToCloudinary() function NOT FOUND\n";
    }
} catch (Exception $e) {
    echo "✗ Error loading helper: " . $e->getMessage() . "\n";
}

// Test 4: Test actual upload with a simple image
echo "\n=== Test 4: Test Upload ===\n";
if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
    echo "File uploaded: " . $_FILES['test_image']['name'] . "\n";
    echo "Temp path: " . $_FILES['test_image']['tmp_name'] . "\n";
    echo "File exists: " . (file_exists($_FILES['test_image']['tmp_name']) ? 'YES' : 'NO') . "\n";
    echo "File size: " . filesize($_FILES['test_image']['tmp_name']) . " bytes\n";
    
    try {
        $result = uploadToCloudinary(
            $_FILES['test_image']['tmp_name'],
            'neocafe/test',
            'test_upload_' . time()
        );
        
        echo "\nUpload Result:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        if ($result['success']) {
            echo "\n✓ UPLOAD SUCCESSFUL!\n";
            echo "URL: " . $result['url'] . "\n";
            echo "\nImage Preview:\n";
            echo "<img src='{$result['url']}' style='max-width: 300px;'>\n";
        } else {
            echo "\n✗ UPLOAD FAILED\n";
            echo "Error: " . ($result['error'] ?? 'Unknown') . "\n";
            echo "Details: " . ($result['error_details'] ?? 'None') . "\n";
        }
    } catch (Exception $e) {
        echo "\n✗ EXCEPTION: " . $e->getMessage() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "No file uploaded. Use the form below to test:\n";
}

echo "</pre>";

// Upload form
?>
<form method="POST" enctype="multipart/form-data">
    <h2>Test Image Upload</h2>
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Upload Test Image</button>
</form>
