<?php
// Start output buffering to catch any unexpected output
ob_start();

// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Let database.php know we're in an API call
$suppress_db_debug = true;

// Include necessary files
require_once __DIR__ . "/../admin-includes/database.php";

// Function to handle errors and return valid JSON
function handleError($message, $debug = []) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set content type
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => $message,
        'debug' => $debug
    ]);
    exit;
}

try {
    // Get order ID from query parameters
    $order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($order_id <= 0) {
        handleError("Invalid order ID");
    }
    
    // Query to get order details
    $query = "SELECT 
                order_id,
                order_date,
                customer_name,
                customer_contact,
                customer_email,
                customer_address,
                payment_method,
                delivery_method as order_type,
                pickup_date,
                delivery_date,
                delivery_time as pickup_time,
                notes,
                total_items,
                total_amount,
                status
              FROM orders 
              WHERE order_id = ?
              LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        handleError("Failed to prepare statement: " . mysqli_error($conn), ['query' => $query]);
    }
    
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        handleError("Failed to execute query: " . mysqli_stmt_error($stmt), ['query' => $query]);
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        handleError("Query execution failed: " . mysqli_error($conn), ['query' => $query]);
    }
    
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        handleError("Order not found", ['order_id' => $order_id]);
    }
    
    // Format dates and times
    if (isset($order['order_date']) && $order['order_date'] && $order['order_date'] != '0000-00-00') {
        $orderDate = new DateTime($order['order_date']);
        $order['order_date'] = $orderDate->format('F d, Y');
    } else {
        $order['order_date'] = 'N/A';
    }
    
    if (isset($order['pickup_date']) && $order['pickup_date'] && $order['pickup_date'] != '0000-00-00') {
        $pickupDate = new DateTime($order['pickup_date']);
        $order['pickup_date'] = $pickupDate->format('F d, Y');
    } else {
        $order['pickup_date'] = 'N/A';
    }
    
    if (isset($order['delivery_date']) && $order['delivery_date'] && $order['delivery_date'] != '0000-00-00') {
        $deliveryDate = new DateTime($order['delivery_date']);
        $order['delivery_date'] = $deliveryDate->format('F d, Y');
    } else {
        $order['delivery_date'] = 'N/A';
    }
    
    if (isset($order['pickup_time']) && $order['pickup_time'] && $order['pickup_time'] != '00:00:00') {
        $pickupTime = new DateTime($order['pickup_time']);
        $order['pickup_time'] = $pickupTime->format('h:i A');
    } else {
        $order['pickup_time'] = 'N/A';
    }
    
    // Get order items
    $items_query = "SELECT 
                    item_id,
                    product_name,
                    quantity,
                    price
                  FROM order_items
                  WHERE order_id = ?
                  ORDER BY item_id ASC";
    
    $items_stmt = mysqli_prepare($conn, $items_query);
    if ($items_stmt) {
        mysqli_stmt_bind_param($items_stmt, "i", $order_id);
        mysqli_stmt_execute($items_stmt);
        $items_result = mysqli_stmt_get_result($items_stmt);
        
        $order['items'] = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $order['items'][] = $item;
        }
    }
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set content type header
    header('Content-Type: application/json');
    
    // Output JSON response
    echo json_encode($order);
} catch (Exception $e) {
    handleError("Database error: " . $e->getMessage(), [
        'query' => $query ?? 'No query executed',
        'trace' => $e->getTraceAsString()
    ]);
}
?> 