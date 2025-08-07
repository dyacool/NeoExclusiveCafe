<?php
// Suppress any warnings or notices that might output HTML
error_reporting(0);
ini_set('display_errors', 0);

require_once "../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Ensure no output before JSON
ob_clean();

header('Content-Type: application/json');

try {
    // Handle POST request to mark order as completed
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Order ID is required'
            ]);
            exit;
        }
        
        $orderId = $input['order_id'];
        
        // Update the order status to completed (removed completed_date since column doesn't exist)
        $updateQuery = "UPDATE orders SET status = 'Completed' WHERE order_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $orderId);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Order marked as completed successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found or already completed'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating order: ' . $stmt->error
            ]);
        }
        
        $stmt->close();
        exit;
    }
    
    // Handle GET request to fetch completed orders
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
              ORDER BY o.order_date DESC";

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
        'error' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 