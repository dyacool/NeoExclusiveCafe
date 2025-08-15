<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();
require_once '../../user-includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login/user/login-signup.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    die("Invalid order ID.");
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
    die("Order not found or access denied.");
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

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="../../css/users/profile.css">
    <link rel="stylesheet" href="../../css/users/order-details.css" />
    <style>
        .order-details-container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; }
        .order-details-container h2 { margin-bottom: 20px; }
        .order-info p { margin: 6px 0; }
        .order-items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .order-items-table th, .order-items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .order-items-table th { background: #f2f2f2; }
        .back-link { display: inline-block; margin-top: 24px; color: #007bff; text-decoration: underline; }
        .back-link:hover { color: #0056b3; }
        .neo-modal-error { color: red; text-align: center; padding: 10px; }
    </style>
</head>
<body>
<?php include '../../user-includes/navbar/customer-navigation.php'; ?>
<div class="order-details-container">
    <h2>Order Details - #<?php echo htmlspecialchars($order['order_id']); ?></h2>
    <div class="order-info">
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
        <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
        <p><strong>Delivery Method:</strong> <?php echo htmlspecialchars($order['delivery_method'] ?? 'N/A'); ?></p>
        <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
        <?php if (!empty($order['pickup_date'])): ?>
            <p><strong>Pickup Date:</strong> <?php echo htmlspecialchars($order['pickup_date']); ?></p>
        <?php endif; ?>
        <?php if (!empty($order['delivery_date'])): ?>
            <p><strong>Delivery Date:</strong> <?php echo htmlspecialchars($order['delivery_date']); ?></p>
        <?php endif; ?>
        <?php if (!empty($order['pickup_time'])): ?>
            <p><strong>Pickup/Delivery Time:</strong> <?php echo htmlspecialchars($order['pickup_time']); ?></p>
        <?php endif; ?>
        <?php if (!empty($order['notes'])): ?>
            <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
        <?php endif; ?>
    </div>
    <h3>Order Items</h3>
    <table class="order-items-table neo-modal-table">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach ($items as $item): 
                $price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    <td>₱<?php echo number_format($itemTotal, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: 600;">Subtotal:</td>
                <td style="font-weight: 600;">₱<?php echo number_format($subtotal, 2); ?></td>
            </tr>
        </tfoot>
    </table>
    <a href="../profile/profile.php" class="back-link">&larr; Back to Profile</a>
</div>
</body>
</html>
