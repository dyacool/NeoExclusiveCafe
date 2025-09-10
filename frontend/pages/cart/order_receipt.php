<?php
session_start();
require_once '../../user-includes/database.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$order_id = intval($_GET['order_id']);

// Fetch order details
$order_sql = "SELECT o.*, c.contact as customer_contact, c.address as customer_address 
              FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.customer_id 
              WHERE o.order_id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Fetch order items
$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - Neo Exclusive Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .receipt-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .receipt-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #2f603c;
            margin-bottom: 30px;
        }
        .receipt-header h1 {
            color: #2f603c;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #2f603c;
            color: white;
        }
        .total-section {
            border-top: 2px solid #dee2e6;
            margin-top: 20px;
            padding-top: 20px;
        }
        .print-button {
            margin-top: 30px;
        }
        @media print {
            .print-button {
                display: none;
            }
            .receipt-container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>Neo Exclusive Cafe</h1>
            <p class="mb-0">Order Receipt</p>
            <p class="mb-0">Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></p>
            <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></p>
        </div>

        <div class="order-info">
            <h4 class="mb-3">Customer Information</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_contact']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Delivery Method:</strong> <?php echo $order['delivery_method']; ?></p>
                    <?php if ($order['delivery_method'] === 'Delivery'): ?>
                        <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>
                        <p><strong>Delivery Date:</strong> <?php echo date('F j, Y', strtotime($order['delivery_date'])); ?></p>
                        <p><strong>Delivery Time:</strong> <?php echo date('g:i A', strtotime($order['delivery_time'])); ?></p>
                    <?php else: ?>
                        <p><strong>Pickup Date:</strong> <?php echo date('F j, Y', strtotime($order['pickup_date'])); ?></p>
                        <p><strong>Pickup Time:</strong> <?php echo date('g:i A', strtotime($order['pickup_time'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <h4 class="mb-3">Order Details</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-end">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end">₱<?php echo number_format($order['total_amount'] - ($order['delivery_method'] === 'Delivery' ? 50 : 0), 2); ?></td>
                        </tr>
                        <?php if ($order['delivery_method'] === 'Delivery'): ?>
                        <tr>
                            <td><strong>Delivery Fee:</strong></td>
                            <td class="text-end">₱50.00</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-end"><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Payment Method:</strong></td>
                            <td class="text-end"><?php echo $order['payment_method']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($order['notes'])): ?>
        <div class="order-info mt-4">
            <h4 class="mb-3">Order Notes</h4>
            <p><?php echo htmlspecialchars($order['notes']); ?></p>
        </div>
        <?php endif; ?>

        <div class="text-center print-button">
            <button class="btn btn-success" onclick="window.print()">Print Receipt</button>
            <a href="../products/product-dashboard.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 