<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration for AlwaysData
$db_host = 'mysql-neoexclusivecafe.alwaysdata.net';
$db_user = '429123';
$db_pass = 'NeoCafe123';
$db_name = 'neoexclusivecafe_crud';

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
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            // Check if connection is still open
            if ($conn->thread_id !== null) {
                mysqli_close($conn);
            }
        } catch (Exception $e) {
            // Silently ignore connection close errors
            error_log("Database connection close error: " . $e->getMessage());
        }
    }
}

// Note: Removed automatic shutdown function to prevent conflicts
// Connections will be closed automatically by PHP when the script ends
?> 