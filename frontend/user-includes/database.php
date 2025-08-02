<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'crud';

// Create connection with error handling
try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    // Set charset to utf8mb4
    if (!mysqli_set_charset($conn, "utf8mb4")) {
        throw new Exception("Error setting charset: " . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly message
    die("We're experiencing technical difficulties. Please try again later.");
}

// Function to safely close the database connection
function closeConnection() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli && !empty($conn->connect_errno) === false) {
        try {
            mysqli_close($conn);
        } catch (Exception $e) {
            error_log("Error closing database connection: " . $e->getMessage());
        }
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('closeConnection');
?> 