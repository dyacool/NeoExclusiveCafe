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
    // Get order ID from query parameters (can be unique_order_id like "BO000031")
    $order_id = isset($_GET['id']) ? trim($_GET['id']) : '';
    
    if (empty($order_id)) {
        handleError("Invalid order ID");
    }
    
    // Query to get bulk order details
    $query = "SELECT 
                id,
                unique_order_id as order_id,
                created_at as order_date,
                name as customer_name,
                contact as customer_contact,
                email as customer_email,
                billing_address as customer_address,
                delivery_address,
                order_type,
                purpose,
                date_needed as pickup_date,
                time_needed as pickup_time,
                note as notes,
                total_items,
                total_amount,
                discount_total,
                status,
                'Bulk Order' as payment_method
              FROM bulk_orders 
              WHERE unique_order_id = ?
              LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        handleError("Failed to prepare statement: " . mysqli_error($conn), ['query' => $query]);
    }
    
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        handleError("Failed to execute query: " . mysqli_stmt_error($stmt), ['query' => $query]);
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        handleError("Query execution failed: " . mysqli_error($conn), ['query' => $query]);
    }
    
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        handleError("Bulk order not found", ['order_id' => $order_id]);
    }
    
    // Add delivery method field to match regular orders structure
    $order['delivery_method'] = $order['order_type'];
    
    // Format dates and times
    if (isset($order['order_date']) && $order['order_date'] && $order['order_date'] != '0000-00-00 00:00:00') {
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
    
    if (isset($order['pickup_time']) && $order['pickup_time'] && $order['pickup_time'] != '00:00:00') {
        $pickupTime = new DateTime($order['pickup_time']);
        $order['pickup_time'] = $pickupTime->format('h:i A');
    } else {
        $order['pickup_time'] = 'N/A';
    }
    
    // Get bulk order items
    $items_query = "SELECT 
                    id as item_id,
                    product_name,
                    quantity,
                    price
                  FROM bulk_order_items
                  WHERE bulk_order_id = ?
                  ORDER BY id ASC";
    
    $items_stmt = mysqli_prepare($conn, $items_query);
    if ($items_stmt) {
        mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
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
