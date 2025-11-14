<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
require_once '../admin-includes/activity-logger.php';

// Log the incoming request for debugging
error_log("=== UPDATE BULK STATUS REQUEST ===");
error_log("Method: " . $_SERVER["REQUEST_METHOD"]);
error_log("POST data: " . json_encode($_POST));

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    error_log("Order ID: $order_id, Status: $status");
    
    // Validate status against allowed values
    $allowed_statuses = ['pending', 'approved', 'payment_received', 'payment_rejected', 'ready_for_delivery', 'ready_for_pickup', 'cancelled', 'rejected', 'completed'];
    
    if (!in_array($status, $allowed_statuses)) {
        error_log("Invalid status attempted: " . $status);
        header("Location: bulk-order.php?order_id=$order_id&error=1");
        exit();
    }
    
    // Update the bulk order status
    $sql = "UPDATE bulk_orders SET status = ?, admin_updated = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        header("Location: bulk-order.php?order_id=$order_id&error=1");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Log the activity
        logAdminActivity($conn, 'UPDATE', "Changed bulk order #$order_id status to '$status'", 'bulk_orders', $order_id);
        
        // Send emails and notifications based on status change
        require_once __DIR__ . '/../admin-includes/mailer.php';
        require_once __DIR__ . '/../admin-includes/notifications/notification.php';
        
        try {
            // Get order details for emails and notifications
            $order_info_sql = "SELECT b.user_id, b.unique_order_id, b.name, b.email, u.username FROM bulk_orders b 
                              LEFT JOIN users u ON b.user_id = u.id 
                              WHERE b.id = ?";
            $order_info_stmt = $conn->prepare($order_info_sql);
            $order_info_stmt->bind_param("i", $order_id);
            $order_info_stmt->execute();
            $order_info_result = $order_info_stmt->get_result();
            $order_info = $order_info_result->fetch_assoc();
            
            if ($order_info) {
                $notificationHandler = new NotificationHandler($conn);
                
                // Send approval email to customer if status is approved
                if ($status === 'approved') {
                    try {
                        sendBulkOrderApprovalEmail($order_id, $conn);
                        error_log("✓ Approval email sent for bulk order #$order_id");
                    } catch (Exception $e) {
                        error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
                    }
                    
                    // Create user notification for approval
                    try {
                        $notificationHandler->createUserBulkOrderNotification(
                            $order_info['user_id'],
                            $order_id,
                            'bulk_approved',
                            $order_info['unique_order_id']
                        );
                        error_log("✓ User notification created for bulk order #$order_id approval");
                    } catch (Exception $e) {
                        error_log("Failed to create approval notification: " . $e->getMessage());
                    }
                }
                
                // Send payment received email to customer if status is payment_received
                if ($status === 'payment_received') {
                    try {
                        sendBulkOrderPaymentReceivedEmail($order_id, $conn);
                        error_log("✓ Payment received email sent for bulk order #$order_id");
                    } catch (Exception $e) {
                        error_log("Failed to send payment received email: " . $e->getMessage());
                    }
                    
                    // Create user notification for payment received
                    try {
                        $notificationHandler->createUserBulkOrderNotification(
                            $order_info['user_id'],
                            $order_id,
                            'bulk_payment_received',
                            $order_info['unique_order_id']
                        );
                        error_log("✓ User notification created for bulk order #$order_id payment received");
                    } catch (Exception $e) {
                        error_log("Failed to create payment received notification: " . $e->getMessage());
                    }
                }
                
                // Send payment rejection email to customer if status is payment_rejected
                if ($status === 'payment_rejected') {
                    try {
                        sendBulkOrderPaymentRejectedEmail($order_id, $conn);
                        error_log("✓ Payment rejection email sent for bulk order #$order_id");
                    } catch (Exception $e) {
                        error_log("Failed to send payment rejection email: " . $e->getMessage());
                    }
                    
                    // Create user notification for payment rejection
                    try {
                        $notificationHandler->createUserBulkOrderNotification(
                            $order_info['user_id'],
                            $order_id,
                            'bulk_payment_rejected',
                            $order_info['unique_order_id']
                        );
                        error_log("✓ User notification created for bulk order #$order_id payment rejection");
                    } catch (Exception $e) {
                        error_log("Failed to create payment rejection notification: " . $e->getMessage());
                    }
                }
                
                // Create admin notification for status change
                try {
                    $notificationHandler->createBulkOrderNotification(
                        $order_id,
                        'bulk_status',
                        $order_info['name'],
                        $order_info['username'],
                        ucfirst(str_replace('_', ' ', $status))
                    );
                    error_log("✓ Admin notification created for bulk order #$order_id status change");
                } catch (Exception $e) {
                    error_log("Failed to create admin status notification: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log('Bulk order status email/notification send failed: ' . $e->getMessage());
        }

        // Success - redirect back to the bulk order detail page
        header("Location: bulk-order.php?id=$order_id&status_updated=1");
        exit();
    } else {
        // Error - redirect with error message
        error_log("Execute failed: " . mysqli_error($conn));
        header("Location: bulk-order.php?id=$order_id&error=1");
        exit();
    }
} else {
    // Invalid request - redirect to bulk order list
    error_log("Missing order_id or status parameter. POST: " . json_encode($_POST));
    header("Location: bulk-order-lists.php");
    exit();
}
?>
