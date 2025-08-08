<?php
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
    !strpos($_SERVER['SCRIPT_NAME'], '/calendar/update-limit.php')) {
        
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
        strpos($_SERVER['SCRIPT_NAME'], '/calendar/update-limit.php')) {
        
        // For API endpoints, force JSON content type
        header('Content-Type: application/json');
    }
}

// Database connection parameters
$db_params = array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'crud'
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
    !strpos($_SERVER['SCRIPT_NAME'], '/calendar/update-limit.php')) {
    // echo $debug_info;
}

try {
    $host = 'localhost';
    $dbname = 'crud';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error but don't display it
    error_log("Database Connection Error: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]));
}

// Create mysqli connection for legacy code
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
