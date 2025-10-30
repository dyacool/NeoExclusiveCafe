<?php
/**
 * Domain-based Router for NeoCafe
 * Handles routing based on domain/subdomain
 */

// Configuration
$config = [
    'admin_domain' => 'admin.neocafe.shop',
    'main_domain' => 'neocafe.shop',
    'admin_path' => '/backend/login/admin/admin-login.php',
    'user_path' => '/frontend/login/user/login-signup.php',
    'default_path' => '/frontend/pages/home/user-dashboard.php'
];

// Get current domain
$current_domain = $_SERVER['HTTP_HOST'];
$request_uri = $_SERVER['REQUEST_URI'];

// Function to check if current domain matches admin domain
function isAdminDomain($current_domain, $admin_domain) {
    return $current_domain === $admin_domain || 
           strpos($current_domain, $admin_domain) !== false ||
           preg_match('/^admin\./', $current_domain);
}

// Function to get appropriate base path
function getBasePath($current_domain, $config) {
    if (isAdminDomain($current_domain, $config['admin_domain'])) {
        return '/backend';
    } else {
        return '/frontend';
    }
}
// Check if user(neocafe.cafe) trying to access admin domain
if (isAdminDomain($current_domain, $config['admin_domain'])) {
    // Admin domain routing
    if ($request_uri === '/' || $request_uri === '') {
        // Root admin domain - redirect to admin login
        safeRedirect($config['admin_path']);
    }
}
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
if (isAdminDomain($current_domain, $config['admin_domain'])) {
    // Admin domain routing
    if ($request_uri === '/' || $request_uri === '') {
        // Root admin domain - redirect to admin login
        safeRedirect($config['admin_path']);
    } else {
        // Admin subdomain with specific path - let it pass through
        // The backend files will handle their own routing
        return;
    }
} else {
    // User/main domain routing
    if ($request_uri === '/' || $request_uri === '') {
        // Root user domain - redirect to user dashboard
        safeRedirect($config['user_path']);
    } else {
        // User domain with specific path - let it pass through
        // The frontend files will handle their own routing
        return;
    }
}
?> 