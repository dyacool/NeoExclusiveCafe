<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

require_once '../admin-includes/database.php';
require_once '../admin-includes/activity-logger.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    // Update the order status
    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'view-orders.php';
        if ($redirect_to === 'order-list.php') {
            header("Location: order-list.php?error=1");
        } else {
            header("Location: view-orders.php?order_id=$order_id&error=1");
        }
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
if (mysqli_stmt_execute($stmt)) {
    // Log the activity
    logAdminActivity($conn, 'UPDATE', "Changed order #$order_id status to '$status'", 'orders', $order_id);
    
    // Broadcast order status update event
    try {
        require_once '../../api/event-broadcaster.php';
        
        // Get customer_id for the order
        $customer_query = "SELECT customer_id FROM orders WHERE order_id = ?";
        $customer_stmt = mysqli_prepare($conn, $customer_query);
        mysqli_stmt_bind_param($customer_stmt, "i", $order_id);
        mysqli_stmt_execute($customer_stmt);
        $customer_result = mysqli_stmt_get_result($customer_stmt);
        $customer_data = mysqli_fetch_assoc($customer_result);
        mysqli_stmt_close($customer_stmt);
        
        if ($customer_data) {
            EventBroadcaster::broadcastOrderStatus(
                $order_id,
                $status,
                $customer_data['customer_id']
            );
        }
    } catch (Exception $e) {
        error_log("Failed to broadcast order status update: " . $e->getMessage());
    }
    
    // Create admin notification for status update
    try {
        require_once '../admin-includes/notifications/notification.php';
        $notificationHandler = new NotificationHandler($conn);
        
        // Get order and customer details
        $order_query = "SELECT customer_name, delivery_method, delivery_date, pickup_date, delivery_time, pickup_time,
                               u.username
                        FROM orders o
                        LEFT JOIN users u ON o.customer_id = u.id 
                        WHERE o.order_id = ?";
        $order_stmt = mysqli_prepare($conn, $order_query);
        
        if (!$order_stmt) {
            error_log("Order query prepare failed: " . mysqli_error($conn));
            throw new Exception("Failed to prepare order query");
        }
        
        mysqli_stmt_bind_param($order_stmt, "i", $order_id);
        mysqli_stmt_execute($order_stmt);
        $order_result = mysqli_stmt_get_result($order_stmt);
        $order_data = mysqli_fetch_assoc($order_result);
        mysqli_stmt_close($order_stmt);
        
        if ($order_data) {
            $customer_name = $order_data['customer_name'];
            $username = $order_data['username'];
            $delivery_method = $order_data['delivery_method'];
            $delivery_date = $order_data['delivery_date'];
            $pickup_date = $order_data['pickup_date'];
            $delivery_time = $order_data['delivery_time'];
            $pickup_time = $order_data['pickup_time'];
            
            // Use appropriate date and time based on delivery method
            $order_date = $delivery_method === 'Delivery' ? $delivery_date : $pickup_date;
            $order_time = $delivery_method === 'Delivery' ? $delivery_time : $pickup_time;
            
            $notificationHandler->createOrderNotification(
                $order_id,
                'order_status',
                $customer_name,
                $username,
                $status,
                $delivery_method,
                $order_date,
                $order_time
            );
        }
        
    } catch (Exception $e) {
        error_log("Failed to create order status notification: " . $e->getMessage());
    }
    
    // Revert to original in-app notification + email to customer
    require_once '../../../frontend/pages/notifications/class-notif.php';
    require_once __DIR__ . '/../admin-includes/mailer.php';

    $notification = new Notification($conn);
    // Create in-app notification based on order and new status
    $notification->createOrderNotification($order_id, $status);

    // Send email to customer about the status change
    try {
        $emailStmt = mysqli_prepare($conn, "SELECT customer_email FROM orders WHERE order_id = ? LIMIT 1");
        
        if (!$emailStmt) {
            error_log("Email query prepare failed: " . mysqli_error($conn));
            throw new Exception("Failed to prepare email query");
        }
        
        mysqli_stmt_bind_param($emailStmt, "i", $order_id);
        mysqli_stmt_execute($emailStmt);
        mysqli_stmt_bind_result($emailStmt, $customer_email);
        mysqli_stmt_fetch($emailStmt);
        mysqli_stmt_close($emailStmt);

        if (!empty($customer_email) && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $subject = "Order #{$order_id} Status Update: {$status}";
            $base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $fullLink = $base . $host . "/NeoCafe/frontend/pages/cart/order-details.php?order_id=" . $order_id;
            $body = "<!DOCTYPE html><html><body style='font-family: Arial, sans-serif; color:#333'>"
                  . "<h2>Order #{$order_id} Status Update</h2>"
                  . "<p>Your order has been <strong>{$status}</strong>.</p>"
                  . "<p><a href='" . $fullLink . "' style='background:#667eea;color:#fff;padding:10px 16px;border-radius:4px;text-decoration:none;'>View Order Details</a></p>"
                  . "<p style='font-size:12px;color:#777'>If the button doesn't work, copy and paste this URL:<br>" . $fullLink . "</p>"
                  . "<p>Thank you,<br>Neo Exclusive Cafe</p>"
                  . "</body></html>";
            sendEmail($customer_email, $subject, $body, true);
        }
    } catch (Exception $e) {
        error_log('Order status email send failed: ' . $e->getMessage());
    }

    // Success - redirect back to the appropriate page
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'view-orders.php';
    if ($redirect_to === 'order-list.php') {
        header("Location: order-list.php?status_updated=1");
    } else {
        header("Location: view-orders.php?order_id=$order_id&status_updated=1");
    }
    exit();
} else {
    // Error - redirect with error message
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'view-orders.php';
    if ($redirect_to === 'order-list.php') {
        header("Location: order-list.php?error=1");
    } else {
        header("Location: view-orders.php?order_id=$order_id&error=1");
    }
    exit();
}
} else {
    // Invalid request - redirect to order list
    header("Location: order-list.php");
    exit();
}
?>
