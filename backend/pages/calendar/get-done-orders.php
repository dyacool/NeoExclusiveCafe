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
                // Broadcast order status update event
                try {
                    require_once __DIR__ . '/../../api/event-broadcaster.php';
                    
                    // Get customer_id for the order
                    $customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
                    $customer_stmt = $conn->prepare($customer_query);
                    $customer_stmt->bind_param("i", $orderId);
                    $customer_stmt->execute();
                    $customer_result = $customer_stmt->get_result();
                    $customer_data = $customer_result->fetch_assoc();
                    $customer_stmt->close();
                    
                    if ($customer_data) {
                        EventBroadcaster::broadcastOrderStatus(
                            $orderId,
                            'Completed',
                            $customer_data['customer_id']
                        );
                    }
                } catch (Exception $e) {
                    error_log("Failed to broadcast order completion: " . $e->getMessage());
                }
                
                // Send notification and email to customer (reuse flow in update-status.php)
                require_once __DIR__ . '/../admin-includes/mailer.php';
                require_once __DIR__ . '/../../frontend/pages/notifications/class-notif.php';

                // Create in-app notification
                $notification = new Notification($conn);
                $notification->createOrderNotification($orderId, 'Completed');

                // Email the customer
                try {
                    $emailStmt = $conn->prepare("SELECT customer_email FROM orders WHERE order_id = ? LIMIT 1");
                    $emailStmt->bind_param("i", $orderId);
                    $emailStmt->execute();
                    $emailStmt->bind_result($customer_email);
                    $emailStmt->fetch();
                    $emailStmt->close();

                    if (!empty($customer_email) && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
                        $subject = "Order #{$orderId} Status Update: Completed";
                        $base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $fullLink = $base . $host . "/NeoCafe/frontend/pages/cart/order-details.php?order_id=" . $orderId;
                        $body = "<!DOCTYPE html><html><body style='font-family: Arial, sans-serif; color:#333'>"
                              . "<h2>Order #{$orderId} Status Update</h2>"
                              . "<p>Your order has been <strong>Completed</strong>.</p>"
                              . "<p><a href='" . $fullLink . "' style='background:#667eea;color:#fff;padding:10px 16px;border-radius:4px;text-decoration:none;'>View Order Details</a></p>"
                              . "<p style='font-size:12px;color:#777'>If the button doesn't work, copy and paste this URL:<br>" . $fullLink . "</p>"
                              . "<p>Thank you,<br>Neo Exclusive Cafe</p>"
                              . "</body></html>";
                        sendEmail($customer_email, $subject, $body, true);
                    }
                } catch (Exception $e) {
                    error_log('Done orders email send failed: ' . $e->getMessage());
                }

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