<?php
require_once __DIR__ . '/config/cloudinary-config.php';

echo "Testing Cloudinary Connection...\n\n";

try {
    $config = CloudinaryConfig::getInstance();
    $result = $config->testConnection();
    
    if ($result['success']) {
        echo "✅ SUCCESS: " . $result['message'] . "\n";
        echo "Cloud Name: " . $result['cloud_name'] . "\n";
    } else {
        echo "❌ FAILED: " . $result['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
