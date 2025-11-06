<?php
// Set timezone to Philippines (Asia/Manila)
// This overrides the server's default timezone (Europe/Berlin)
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Fix Windows session path permission issues
    $session_path = sys_get_temp_dir();
    if (is_writable($session_path)) {
        session_save_path($session_path);
    }
    @session_start(); // Suppress warnings for permission issues
}

// Check if headers have already been sent
if (headers_sent()) {
    // Only output debug info if not in an API call
    if (!isset($suppress_db_debug) && 
        !strpos($_SERVER['SCRIPT_NAME'], '/admin/get-pending-orders.php') && 
        !strpos($_SERVER['SCRIPT_NAME'], '/admin/accept-order.php') &&
        !strpos($_SERVER['SCRIPT_NAME'], '/admin/decline-order.php') &&
        !strpos($_SERVER['SCRIPT_NAME'], '/admin/minimal-orders.php') &&
        !strpos($_SERVER['SCRIPT_NAME'], '/products/update-product.php') &&
        !strpos($_SERVER['SCRIPT_NAME'], '/products/get-product-images.php') &&
        !strpos($_SERVER['SCRIPT_NAME'], '/products/remove-product-image.php') &&
        !strpos($_SERVER['SCRIPT_NAME'], '/products/upload-product-image.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/products/upload-temp-image.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/products/cleanup-temp-images.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/products/move-temp-to-permanent.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/products/remove-individual-image.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/products/restore-removed-images.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/products/delete-removed-images.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/calendar/update-limit.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/cart/availtoday-cart-api.php')) {
        
        echo "<!-- Headers already sent, could not set content type -->";
    }
} else {
    // Set content type for API endpoints
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/get-pending-orders.php') || 
        strpos($_SERVER['SCRIPT_NAME'], '/admin/accept-order.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/admin/decline-order.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/admin/minimal-orders.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/update-product.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/get-product-images.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/remove-product-image.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/upload-product-image.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/upload-temp-image.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/cleanup-temp-images.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/move-temp-to-permanent.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/remove-individual-image.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/restore-removed-images.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/delete-removed-images.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/calendar/update-limit.php') ||
        strpos($_SERVER['SCRIPT_NAME'], '/cart/availtoday-cart-api.php')) {
        
        // For API endpoints, force JSON content type
        header('Content-Type: application/json');
    }
}

// Database connection parameters
$db_params = array(
    'hostname' => 'mysql-neoexclusivecafe.alwaysdata.net',
    'username' => '429123',
    'password' => 'NeoCafe123',
    'database' => 'neoexclusivecafe_crud'
);

// Store the debug info in a variable instead of outputting directly
$debug_info = "<!-- Connected to database: " . $db_params['database'] . " -->"
            . "<!-- Debug: Database connection parameters = " . print_r($db_params, true) . " -->";

// Only output debug info if not in an API call
if (!isset($suppress_db_debug) && 
    !strpos($_SERVER['SCRIPT_NAME'], '/admin/get-pending-orders.php') && 
    !strpos($_SERVER['SCRIPT_NAME'], '/admin/accept-order.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/admin/decline-order.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/admin/minimal-orders.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/products/update-product.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/calendar/update-limit.php') &&
    !strpos($_SERVER['SCRIPT_NAME'], '/cart/availtoday-cart-api.php')) {
    // echo $debug_info;
}

// Use the parameters from the array to avoid duplication
$host = $db_params['hostname'];
$dbname = $db_params['database'];
$username = $db_params['username'];
$password = $db_params['password'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error but don't display it
    error_log("PDO Database Connection Error: " . $e->getMessage());
    // Don't die here - let mysqli connection attempt to work
    $pdo = null;
}

// Create mysqli connection for legacy code
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("MySQLi Database Connection Error: " . $conn->connect_error);
    
    // Check if this is an API call that expects JSON
    $is_api_call = (
        strpos($_SERVER['SCRIPT_NAME'], '/admin/get-pending-orders.php') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/admin/accept-order.php') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/admin/decline-order.php') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/admin/minimal-orders.php') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/products/update-product.php') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/calendar/update-limit.php') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/cart/availtoday-cart-api.php') !== false
    );
    
    if ($is_api_call) {
        die(json_encode(['success' => false, 'error' => 'Database connection failed']));
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set character set to utf8mb4 for proper hash storage
$conn->set_charset("utf8mb4");

// Set MySQL timezone to Philippines
$conn->query("SET time_zone = '+08:00'");

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
// No closing PHP tag to prevent accidental whitespace output
