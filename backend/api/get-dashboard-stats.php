<?php
// Suppress database debug output for API responses
$suppress_db_debug = true;

session_start();

// Authentication check
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit();
}

require_once __DIR__ . '/../pages/admin-includes/database.php';

// Set JSON header
header('Content-Type: application/json');

try {
    $since_timestamp = isset($_GET['since']) ? $_GET['since'] : null;
    
    // Get today's date for calculations
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $last_month_start = date('Y-m-d', strtotime('-30 days'));
    
    // Initialize stats array
    $stats = [];
    
    // Today's Income
    $today_income_query = "SELECT SUM(total_amount) as today_income FROM orders 
                          WHERE DATE(order_date) = ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $today_income_query);
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['today_income'] = $row['today_income'] ?? 0;
    
    // Net Income (last 30 days)
    $net_income_query = "SELECT SUM(total_amount) as net_income FROM orders 
                        WHERE DATE(order_date) >= ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $net_income_query);
    mysqli_stmt_bind_param($stmt, "s", $last_month_start);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['net_income'] = $row['net_income'] ?? 0;
    
    // Total Products
    $products_query = "SELECT COUNT(*) as total_products FROM products WHERE deleted_at IS NULL";
    $result = mysqli_query($conn, $products_query);
    $row = mysqli_fetch_assoc($result);
    $stats['total_products'] = $row['total_products'] ?? 0;
    
    // Pending Orders
    $pending_query = "SELECT COUNT(*) as pending_orders FROM orders 
                     WHERE status NOT IN ('Delivered', 'Picked-up')";
    $result = mysqli_query($conn, $pending_query);
    $row = mysqli_fetch_assoc($result);
    $stats['pending_orders'] = $row['pending_orders'] ?? 0;
    
    // Total Orders
    $total_orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $result = mysqli_query($conn, $total_orders_query);
    $row = mysqli_fetch_assoc($result);
    $stats['total_orders'] = $row['total_orders'] ?? 0;
    
    // Bulk Orders
    $bulk_orders_query = "SELECT COUNT(*) as bulk_orders FROM bulk_orders";
    $result = mysqli_query($conn, $bulk_orders_query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['bulk_orders'] = $row['bulk_orders'] ?? 0;
    }
    
    // Check for new orders since timestamp
    // Only check the flag, not the timestamp comparison
    // This prevents false positives from existing orders
    $has_new_orders = false;
    
    // Check for new order flag (within last 10 seconds)
    $flag_check_sql = "SELECT id FROM order_update_flags 
                       WHERE flag_type = 'new_order' 
                       AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
                       LIMIT 1";
    $flag_result = mysqli_query($conn, $flag_check_sql);
    $has_new_order_flag = mysqli_num_rows($flag_result) > 0;
    
    // Return successful response
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'has_new_orders' => $has_new_orders,
        'has_new_order_flag' => $has_new_order_flag
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    error_log('Dashboard stats API error: ' . $e->getMessage());
}
?>
