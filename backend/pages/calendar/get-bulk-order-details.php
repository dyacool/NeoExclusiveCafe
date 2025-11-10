<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once __DIR__ . "/../admin-includes/database.php";
}
require_once __DIR__ . "/../../../includes/session-manager.php";

// Check admin authentication
if (!SessionManager::isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// Get bulk order details
$query = "SELECT 
            b.id,
            b.unique_order_id,
            b.name as customer_name,
            b.contact as customer_contact,
            b.email as customer_email,
            b.billing_address,
            b.order_type,
            b.delivery_address,
            b.purpose,
            b.date_needed,
            b.time_needed,
            b.note,
            b.total_amount,
            b.total_items,
            b.status,
            b.created_at as order_date
          FROM bulk_orders b
          WHERE b.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($order = mysqli_fetch_assoc($result)) {
    // Get order items
    $items_query = "SELECT product_name, quantity, product_price, discount_price, subtotal 
                    FROM bulk_order_items 
                    WHERE bulk_order_id = ?";
    $items_stmt = mysqli_prepare($conn, $items_query);
    mysqli_stmt_bind_param($items_stmt, "i", $id);
    mysqli_stmt_execute($items_stmt);
    $items_result = mysqli_stmt_get_result($items_stmt);
    
    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = [
            'product_name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'price' => $item['product_price'],
            'discount_price' => $item['discount_price'] ?? null,
            'subtotal' => $item['subtotal']
        ];
    }
    
    mysqli_stmt_close($items_stmt);
    
    // Format the response
    $order['order_id'] = $order['id'];
    $order['customer_address'] = !empty($order['delivery_address']) 
        ? $order['delivery_address'] 
        : $order['billing_address'];
    $order['pickup_date'] = $order['date_needed'];
    $order['delivery_date'] = $order['date_needed'];
    $order['pickup_time'] = $order['time_needed'];
    $order['items'] = $items;
    $order['is_bulk'] = true;
    
    echo json_encode($order);
} else {
    echo json_encode(['error' => 'Order not found']);
}

mysqli_stmt_close($stmt);
?>
