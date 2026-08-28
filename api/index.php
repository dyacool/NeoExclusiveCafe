<?php
// Simple test to verify Vercel PHP is working
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>NeoCafe Test</title></head><body>";
echo "<h1>Vercel PHP is working!</h1>";
echo "<p>Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</p>";
echo "<p>URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "</p>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Test if config file exists
$config_path = __DIR__ . '/../config/domain-config.php';
if (file_exists($config_path)) {
    echo "<p style='color: green;'>✓ Config file found</p>";
} else {
    echo "<p style='color: red;'>✗ Config file NOT found at: $config_path</p>";
}

// List directory contents
echo "<h2>Directory Structure:</h2>";
echo "<pre>";
echo "Current dir: " . __DIR__ . "\n";
echo "Parent dir exists: " . (is_dir(__DIR__ . '/..') ? 'YES' : 'NO') . "\n";
echo "</pre>";

echo "</body></html>";
?>