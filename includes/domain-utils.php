<?php
/**
 * Domain Utilities for NeoCafe
 * Provides domain detection and routing utilities
 */

// Include domain configuration
$domain_config = require_once __DIR__ . '/../config/domain-config.php';

/**
 * Check if current request is from admin domain
 */
function isAdminRequest() {
    global $domain_config;
    $current_domain = $_SERVER['HTTP_HOST'] ?? '';
    
    return $current_domain === $domain_config['admin_domain'] || 
           strpos($current_domain, $domain_config['admin_domain']) !== false ||
           preg_match('/^admin\./', $current_domain);
}

/**
 * Check if current request is from user domain
 */
function isUserRequest() {
    return !isAdminRequest();
}

/**
 * Get the appropriate base path for current domain
 */
function getBasePath() {
    return isAdminRequest() ? '/backend' : '/frontend';
}

/**
 * Get the appropriate assets path for current domain
 */
function getAssetsPath() {
    return '/assets';
}

/**
 * Get the appropriate login path for current domain
 */
function getLoginPath() {
    global $domain_config;
    
    if (isAdminRequest()) {
        return $domain_config['admin_path'];
    } else {
        return $domain_config['user_path'];
    }
}

/**
 * Get the appropriate dashboard path for current domain
 */
function getDashboardPath() {
    if (isAdminRequest()) {
        return '/backend/pages/homepage/admin-homepage.php';
    } else {
        return '/frontend/pages/home/user-dashboard.php';
    }
}

/**
 * Redirect to appropriate login based on domain
 */
function redirectToLogin() {
    $login_path = getLoginPath();
    
    if (!headers_sent()) {
        header("Location: " . $login_path);
        exit;
    } else {
        echo '<script>window.location.href = "' . $login_path . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . $login_path . '">';
        exit;
    }
}

/**
 * Redirect to appropriate dashboard based on domain
 */
function redirectToDashboard() {
    $dashboard_path = getDashboardPath();
    
    if (!headers_sent()) {
        header("Location: " . $dashboard_path);
        exit;
    } else {
        echo '<script>window.location.href = "' . $dashboard_path . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . $dashboard_path . '">';
        exit;
    }
}

/**
 * Get current domain configuration
 */
function getDomainConfig() {
    global $domain_config;
    return $domain_config;
}

/**
 * Check if user is accessing the correct domain for their role
 */
function validateDomainAccess($user_role) {
    if ($user_role === 'admin' && !isAdminRequest()) {
        // Admin user accessing user domain - redirect to admin
        redirectToLogin();
    } elseif ($user_role === 'user' && isAdminRequest()) {
        // User accessing admin domain - redirect to user
        redirectToLogin();
    }
}

/**
 * Get the appropriate logout path for current domain
 */
function getLogoutPath() {
    if (isAdminRequest()) {
        return '/backend/login/admin/logout.php';
    } else {
        return '/frontend/login/user/logout.php';
    }
}
?> 