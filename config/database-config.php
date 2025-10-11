<?php
/**
 * Centralized Database Configuration for NeoCafe
 * This file contains database connection settings for all environments
 */

// Database configuration
function getDatabaseConnection() {
    // Online production database
    $servername = "mysql-neoexclusivecafe.alwaysdata.net";
    $username = "429123";
    $password = "NeoCafe123";
    $dbname = "neoexclusivecafe_crud";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8 for proper character handling
    $conn->set_charset("utf8");
    
    return $conn;
}

// Alternative function for backward compatibility
function getDBConnection() {
    return getDatabaseConnection();
}

// Database configuration array (for cases where array is needed)
$db_config = [
    'host' => 'mysql-neoexclusivecafe.alwaysdata.net',
    'username' => '429123',
    'password' => 'NeoCafe123',
    'database' => 'neoexclusivecafe_crud',
    'charset' => 'utf8'
];
?>