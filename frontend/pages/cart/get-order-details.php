<?php
require_once '../../../backend/pages/admin-includes/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate order_id parameter
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$orderId = (int)$_GET['order_id'];
$userId = (int)$_SESSION['user_id'];

try {
    // Get user email
    $userQuery = "SELECT email FROM users WHERE id = ?";
    $userStmt = mysqli_prepare($conn, $userQuery);
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $user = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Fetch order details - verify it belongs to the user
    $orderQuery = "SELECT order_id, customer_name, customer_email, customer_phone, 
                          delivery_address, delivery_method, total_amount, status, 
                          order_date, total_items
                   FROM orders 
                   WHERE order_id = ? AND customer_email = ?";
    
    $orderStmt = mysqli_prepare($conn, $orderQuery);
    mysqli_stmt_bind_param($orderStmt, "is", $orderId, $user['email']);
    mysqli_stmt_execute($orderStmt);
    $orderResult = mysqli_stmt_get_result($orderStmt);
    $order = mysqli_fetch_assoc($orderResult);
    mysqli_stmt_close($orderStmt);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to you']);
        exit();
    }

    // Fetch order items
    $itemsQuery = "SELECT product_name, quantity, price 
                   FROM order_items 
                   WHERE order_id = ?";
    
    $itemsStmt = mysqli_prepare($conn, $itemsQuery);
    mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
    mysqli_stmt_execute($itemsStmt);
    $itemsResult = mysqli_stmt_get_result($itemsStmt);
    
    $items = [];
    while ($item = mysqli_fetch_assoc($itemsResult)) {
        $items[] = $item;
    }
    mysqli_stmt_close($itemsStmt);

    // Return success response
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);

} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
