<?php
// This script automatically checks if the required tables for the date limits feature exist
// and creates them if they don't. It is designed to run silently without user interaction.

// Prevent errors from being displayed
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to prevent any output
ob_start();

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";

// Log file for debugging
$log_file = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/logs/table_setup.log";
$log_dir = dirname($log_file);

// Create logs directory if it doesn't exist
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Function to log messages
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

log_message("Starting table setup check");

// Tables required for date limits functionality
$required_tables = [
    'order_limits' => "
        CREATE TABLE IF NOT EXISTS order_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            default_limit INT NOT NULL DEFAULT 10,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
    'date_limits' => "
        CREATE TABLE IF NOT EXISTS date_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            date DATE NOT NULL,
            limit_value INT NOT NULL DEFAULT 0,
            not_accepting_orders BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_date (date)
        )",
    'orderdate_status' => "
        CREATE TABLE IF NOT EXISTS orderdate_status (
            id INT PRIMARY KEY AUTO_INCREMENT,
            date DATE NOT NULL,
            status ENUM('accepting', 'not_accepting') NOT NULL DEFAULT 'accepting',
            reason VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_date (date)
        )"
];

// Create tables if they don't exist
foreach ($required_tables as $table => $create_query) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    
    if ($result->num_rows == 0) {
        log_message("Table '$table' doesn't exist - creating it");
        
        if ($conn->query($create_query)) {
            log_message("Successfully created '$table' table");
            
            // Insert default limit if this is the order_limits table
            if ($table === 'order_limits') {
                $conn->query("INSERT IGNORE INTO order_limits (id, default_limit) VALUES (1, 10)");
                log_message("Inserted default order limit of 10");
            }
        } else {
            log_message("Failed to create '$table' table: " . $conn->error);
        }
    } else {
        log_message("Table '$table' already exists");
        
        // For date_limits table, check if not_accepting_orders column exists
        if ($table === 'date_limits') {
            // Check if the not_accepting_orders column exists
            $column_check = $conn->query("SHOW COLUMNS FROM date_limits LIKE 'not_accepting_orders'");
            if ($column_check->num_rows == 0) {
                log_message("not_accepting_orders column doesn't exist - adding it");
                if ($conn->query("ALTER TABLE date_limits ADD COLUMN not_accepting_orders BOOLEAN DEFAULT FALSE")) {
                    log_message("Successfully added not_accepting_orders column to date_limits table");
                } else {
                    log_message("Failed to add not_accepting_orders column: " . $conn->error);
                }
            }
        }
    }
}

// Check if orders table exists and has required columns
$result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($result->num_rows > 0) {
    log_message("Orders table exists, checking for required columns");
    
    $required_columns = [
        'pickup_date' => 'DATE',
        'delivery_date' => 'DATE',
        'status' => 'VARCHAR(50)'
    ];
    
    $columns_result = $conn->query("SHOW COLUMNS FROM orders");
    $existing_columns = [];
    
    while ($column = $columns_result->fetch_assoc()) {
        $existing_columns[] = $column['Field'];
    }
    
    foreach ($required_columns as $column => $type) {
        if (!in_array($column, $existing_columns)) {
            log_message("Column '$column' is missing - adding it");
            $alter_query = "ALTER TABLE orders ADD COLUMN $column $type";
            
            if ($conn->query($alter_query)) {
                log_message("Successfully added column '$column'");
            } else {
                log_message("Failed to add column '$column': " . $conn->error);
            }
        }
    }
} else {
    log_message("Orders table doesn't exist - assuming it will be created elsewhere");
}

// Create chatbot_knowledge table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS chatbot_knowledge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

try {
    $conn->query($sql);
    error_log("Chatbot knowledge table checked/created successfully");
} catch (PDOException $e) {
    error_log("Error creating chatbot knowledge table: " . $e->getMessage());
}

log_message("Completed table setup check");

// Clear any output
ob_end_clean();
?> 