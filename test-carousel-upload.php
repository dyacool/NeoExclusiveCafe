<?php
// Simple test to check what's failing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing carousel upload dependencies...\n\n";

echo "1. Checking vendor autoload: ";
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    echo "✓ EXISTS\n";
    require_once $autoload;
    echo "   ✓ LOADED\n";
} else {
    echo "✗ MISSING at $autoload\n";
    exit();
}

echo "\n2. Checking cloudinary-config.php: ";
$cloudinaryConfig = __DIR__ . '/config/cloudinary-config.php';
if (file_exists($cloudinaryConfig)) {
    echo "✓ EXISTS\n";
    try {
        require_once $cloudinaryConfig;
        echo "   ✓ LOADED\n";
    } catch (Exception $e) {
        echo "   ✗ ERROR: " . $e->getMessage() . "\n";
        exit();
    }
} else {
    echo "✗ MISSING at $cloudinaryConfig\n";
    exit();
}

echo "\n3. Checking cloudinary-helper.php: ";
$helperPath = __DIR__ . '/backend/includes/cloudinary-helper.php';
if (file_exists($helperPath)) {
    echo "✓ EXISTS\n";
    try {
        require_once $helperPath;
        echo "   ✓ LOADED\n";
    } catch (Exception $e) {
        echo "   ✗ ERROR: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n";
        echo $e->getTraceAsString() . "\n";
        exit();
    }
} else {
    echo "✗ MISSING at $helperPath\n";
    exit();
}

echo "\n4. Testing Cloudinary connection: ";
try {
    $config = CloudinaryConfig::getInstance();
    echo "✓ Instance created\n";
    
    $cloudinary = $config->getCloudinary();
    echo "   ✓ Cloudinary object retrieved\n";
    
    echo "\n✅ ALL CHECKS PASSED!\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
