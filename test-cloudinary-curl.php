<?php
/**
 * Test Cloudinary Connection - cURL version
 * This script tests if the Cloudinary configuration is working without Composer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cloudinary cURL Connection Test</h1>";

// Load the cURL-based config
require_once __DIR__ . '/backend/config/cloudinary-config-curl.php';

echo "<h2>Configuration Loaded</h2>";

try {
    $config = CloudinaryConfig::getInstance();
    
    echo "<p><strong>Cloud Name:</strong> " . $config->getCloudName() . "</p>";
    echo "<p><strong>API Key:</strong> " . $config->getApiKey() . "</p>";
    echo "<p><strong>Moderation Enabled:</strong> " . ($config->isModerationEnabled() ? 'Yes' : 'No') . "</p>";
    
    echo "<h2>Testing Connection...</h2>";
    
    $testResult = $config->testConnection();
    
    if ($testResult['success']) {
        echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> " . $testResult['message'] . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ FAILED:</strong> " . $testResult['error'] . "</p>";
        if (isset($testResult['response'])) {
            echo "<pre>Response: " . htmlspecialchars($testResult['response']) . "</pre>";
        }
    }
    
    echo "<h2>cURL Information</h2>";
    if (function_exists('curl_version')) {
        $curlVersion = curl_version();
        echo "<p><strong>cURL Version:</strong> " . $curlVersion['version'] . "</p>";
        echo "<p><strong>SSL Version:</strong> " . $curlVersion['ssl_version'] . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ cURL is not enabled!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
