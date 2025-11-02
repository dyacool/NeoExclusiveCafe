<?php
/**
 * Domain Configuration for NeoCafe
 * This file contains all domain-related configurations
 */

// Domain Configuration
$domain_config = [
    // Production domains
    'production' => [
        'admin_domain' => 'admin.neocafe.shop',
        'rider_domain' => 'rider.neocafe.shop',
        'main_domain' => 'neocafe.shop',
        'admin_path' => '/backend/login/admin/admin-login.php',
        'rider_path' => '/rider/orders.php',
        'user_path' => '/frontend/login/user/login-signup.php',
        'default_path' => '/frontend/pages/home/user-dashboard.php'
    ],
    
    // Development domains (for local testing with neocafe.cafe:8080)
    'development' => [
        'admin_domain' => 'admin.neocafe.cafe:8080',
        'rider_domain' => 'rider.neocafe.cafe:8080',
        'main_domain' => 'neocafe.cafe:8080',
        'admin_path' => '/backend/login/admin/admin-login.php',
        'rider_path' => '/rider/orders.php',
        'user_path' => '/frontend/login/user/login-signup.php',
        'default_path' => '/frontend/pages/home/user-dashboard.php'
    ],
    
    // XAMPP local development
    'xampp' => [
        'admin_domain' => 'admin.neocafe.local',
        'rider_domain' => 'rider.neocafe.local',
        'main_domain' => 'neocafe.local',
        'admin_path' => '/backend/login/admin/admin-login.php',
        'rider_path' => '/rider/orders.php',
        'user_path' => '/frontend/login/user/login-signup.php',
        'default_path' => '/frontend/pages/home/user-dashboard.php'
    ]
];

// Environment detection
function getEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    // Check for neocafe.cafe with port 8080 (local development)
    if (strpos($host, 'neocafe.cafe') !== false && strpos($host, ':8080') !== false) {
        return 'development';
    }
    
    // Check for neocafe.cafe without port (also local development)
    if (strpos($host, 'neocafe.cafe') !== false) {
        return 'development';
    }
    
    // Check for localhost or local development
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return 'development';
    }
    
    // Check for XAMPP local development
    if (strpos($host, '.local') !== false) {
        return 'xampp';
    }
    
    // Default to production
    return 'production';
}

// Get current configuration based on environment
$current_config = $domain_config[getEnvironment()];

// Helper functions
function isAdminDomain($domain) {
    global $current_config;
    // Strip port for comparison if present
    $domain_without_port = preg_replace('/:\d+$/', '', $domain);
    $config_domain_without_port = preg_replace('/:\d+$/', '', $current_config['admin_domain']);
    
    // Exact match with or without port
    if ($domain === $current_config['admin_domain'] || $domain_without_port === $config_domain_without_port) {
        return true;
    }
    
    // Check if domain starts with "admin."
    if (preg_match('/^admin\./', $domain)) {
        return true;
    }
    
    return false;
}

function isRiderDomain($domain) {
    global $current_config;
    // Strip port for comparison if present
    $domain_without_port = preg_replace('/:\d+$/', '', $domain);
    $config_domain_without_port = preg_replace('/:\d+$/', '', $current_config['rider_domain']);
    
    // Exact match with or without port
    if ($domain === $current_config['rider_domain'] || $domain_without_port === $config_domain_without_port) {
        return true;
    }
    
    // Check if domain starts with "rider."
    if (preg_match('/^rider\./', $domain)) {
        return true;
    }
    
    return false;
}

function getAdminPath() {
    global $current_config;
    return $current_config['admin_path'];
}

function getUserPath() {
    global $current_config;
    return $current_config['user_path'];
}

function getDefaultPath() {
    global $current_config;
    return $current_config['default_path'];
}

function getRiderPath() {
    global $current_config;
    return $current_config['rider_path'];
}

// Export configuration for use in other files
return $current_config;
?> 