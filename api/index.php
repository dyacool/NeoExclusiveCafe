<?php
// Include domain configuration
$config = require_once __DIR__ . '/../config/domain-config.php';

// Domain-based routing system
$current_domain = $_SERVER['HTTP_HOST'];
$request_uri = $_SERVER['REQUEST_URI'];

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

// Main routing logic
if (isAdminDomain($current_domain)) {
    // Admin domain - redirect to admin login
    safeRedirect($config['admin_path']);
} else {
    // User/main domain - redirect to user dashboard
    safeRedirect($config['user_path']);
}
?>