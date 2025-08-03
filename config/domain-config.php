<?php
/**
 * Domain Configuration for NeoCafe
 * This file contains all domain-related configurations
 */

// Domain Configuration
$domain_config = [
    // Production domains
    'production' => [
        'admin_domain' => 'admin.neocafe.cafe',
        'main_domain' => 'neocafe.cafe',
        'admin_path' => '/backend/login/admin/admin-login.php',
        'user_path' => '/frontend/pages/home/user-dashboard.php',
        'default_path' => '/frontend/pages/home/user-dashboard.php'
    ],
    
    // Development domains (for local testing)
    'development' => [
        'admin_domain' => 'admin.localhost',
        'main_domain' => 'localhost',
        'admin_path' => '/backend/login/admin/admin-login.php',
        'user_path' => '/frontend/pages/home/user-dashboard.php',
        'default_path' => '/frontend/pages/home/user-dashboard.php'
    ],
    
    // XAMPP local development
    'xampp' => [
        'admin_domain' => 'admin.neocafe.local',
        'main_domain' => 'neocafe.local',
        'admin_path' => '/backend/login/admin/admin-login.php',
        'user_path' => '/frontend/pages/home/user-dashboard.php',
        'default_path' => '/frontend/pages/home/user-dashboard.php'
    ]
];

// Environment detection
function getEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
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
    return $domain === $current_config['admin_domain'] || 
           strpos($domain, $current_config['admin_domain']) !== false ||
           preg_match('/^admin\./', $domain);
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

// Export configuration for use in other files
return $current_config;
?> 