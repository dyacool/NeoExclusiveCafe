<?php
header('Content-Type: application/json');

// Include database connection
require_once '../pages/admin-includes/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get order ID from request
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit();
}

try {
    // Fetch order details with all necessary information
    $query = "SELECT 
                o.id,
                o.user_id,
                o.order_type,
                o.status,
                o.total_amount,
                o.shipping_method,
                o.delivery_date,
                o.delivery_time,
                o.pickup_date,
                o.pickup_time,
                o.created_at,
                CONCAT(u.firstname, ' ', u.lastname) as customer_name,
                u.email as customer_email,
                u.phone as customer_phone,
                o.complete_address,
                dl.municipality,
                dl.city,
                dl.postal_code,
                o.payment_method,
                o.payment_status
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              LEFT JOIN delivery_locations dl ON o.delivery_location_id = dl.delivery_id
              WHERE o.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    $order = $result->fetch_assoc();
    $stmt->close();
    
    // Format the response
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    error_log("Get order details error: " . $e->getMessage());
}
