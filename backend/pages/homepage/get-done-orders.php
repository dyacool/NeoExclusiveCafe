<?php
require_once "../includes/db-connection.php";
require_once "../includes/admin-auth.php";

header('Content-Type: application/json');

try {
    // Get all completed orders
    $query = "SELECT o.*, 
                     c.name as customer_name,
                     c.contact as customer_contact,
                     c.address as customer_address,
                     oi.product_id,
                     oi.quantity,
                     oi.price,
                     p.name as product_name
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.customer_id
              LEFT JOIN order_items oi ON o.order_id = oi.order_id
              LEFT JOIN products p ON oi.product_id = p.product_id
              WHERE o.status = 'Completed'
              ORDER BY o.completed_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    $currentOrder = null;

    while ($row = $result->fetch_assoc()) {
        if (!$currentOrder || $currentOrder['order_id'] != $row['order_id']) {
            if ($currentOrder) {
                $orders[] = $currentOrder;
            }
            
            $currentOrder = [
                'order_id' => $row['order_id'],
                'order_type' => $row['order_type'],
                'order_date' => $row['order_date'],
                'pickup_date' => $row['pickup_date'],
                'pickup_time' => $row['pickup_time'],
                'delivery_date' => $row['delivery_date'],
                'status' => $row['status'],
                'payment_method' => $row['payment_method'],
                'total_amount' => $row['total_amount'],
                'notes' => $row['notes'],
                'completed_date' => $row['completed_date'],
                'customer_name' => $row['customer_name'],
                'customer_contact' => $row['customer_contact'],
                'customer_address' => $row['customer_address'],
                'items' => []
            ];
        }

        if ($row['product_id']) {
            $currentOrder['items'][] = [
                'product_id' => $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['price']
            ];
        }
    }

    if ($currentOrder) {
        $orders[] = $currentOrder;
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching completed orders: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 