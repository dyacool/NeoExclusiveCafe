<?php
// Database configuration for promotions management
function getDBConnection() {
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "crud";
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Create promotions table if it doesn't exist
function createPromotionsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        code VARCHAR(10) UNIQUE NOT NULL,
        discount_type ENUM('shipping', 'percentage', 'fixed_amount') NOT NULL,
        discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
        min_spend DECIMAL(10,2) DEFAULT 0,
        applicable_to ENUM('delivery', 'pickup', 'all', 'special') NOT NULL DEFAULT 'all',
        usage_limit INT DEFAULT NULL,
        usage_limit_per_user INT DEFAULT NULL,
        used_count INT DEFAULT 0,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    return $conn->query($sql);
}
?>
