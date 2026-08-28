<?php
// Vercel Serverless Function Entry Point
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Check if config file exists
$config_path = __DIR__ . '/../config/domain-config.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    die("Error: Configuration file not found at: " . $config_path);
}

// Include domain configuration
try {
    $config = require_once $config_path;
} catch (Exception $e) {
    http_response_code(500);
    die("Error loading configuration: " . $e->getMessage());
}

// Domain-based routing system
$current_domain = $_SERVER['HTTP_HOST'] ?? '';
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

// Function to redirect with proper error handling
function safeRedirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo '<script>window.location.href = "' . $url . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
        exit;
    }
}

// Check if required functions exist
if (!function_exists('isAdminDomain')) {
    http_response_code(500);
    die("Error: isAdminDomain() function not found in config");
}

// Main routing logic
try {
    if (isAdminDomain($current_domain)) {
        // Admin domain - redirect to admin login
        safeRedirect($config['admin_path']);
    } else {
        // User/main domain - redirect to user dashboard
        safeRedirect($config['user_path']);
    }
} catch (Exception $e) {
    http_response_code(500);
    die("Routing error: " . $e->getMessage());
}
?>