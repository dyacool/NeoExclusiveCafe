<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/NeoExclusiveCafe/php/includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit();
}

// Fetch the order, ensuring it belongs to the logged-in user
$order_query = "SELECT * FROM orders WHERE order_id = ? AND (customer_id = ? OR customer_email = (SELECT email FROM users WHERE id = ?)) LIMIT 1";
$stmt = $conn->prepare($order_query);
$stmt->bind_param('iii', $order_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or access denied.']);
    exit();
}

// Fetch order items
$items_query = "SELECT product_name, quantity, price FROM order_items WHERE order_id = ? ORDER BY item_id ASC";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
$items_stmt->close();

// Format order data for frontend
$order_data = [
    'order_id' => $order['order_id'],
    'status' => $order['status'],
    'order_date' => $order['order_date'],
    'delivery_method' => $order['delivery_method'] ?? '',
    'total_amount' => $order['total_amount'],
    // Add more fields as needed
];

// Return JSON
echo json_encode([
    'success' => true,
    'order' => $order_data,
    'items' => $items
]);
