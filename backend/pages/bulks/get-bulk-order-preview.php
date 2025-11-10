<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once __DIR__ . "/../admin-includes/database.php";
}
require_once __DIR__ . "/../../../includes/session-manager.php";

// Check admin authentication
if (!SessionManager::isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get order ID
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit();
}

// Fetch order details with user information
$sql = "SELECT bo.*, 
               u.firstname, u.lastname, u.username
        FROM bulk_orders bo
        LEFT JOIN users u ON bo.user_id = u.id
        WHERE bo.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit();
}

$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Fetch order items
$items_sql = "SELECT * FROM bulk_order_items WHERE bulk_order_id = ? ORDER BY id ASC";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}
mysqli_stmt_close($items_stmt);

// Add items to order array
$order['items'] = $items;

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'order' => $order
]);
?>
