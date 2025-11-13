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
        
        // Send notification to customer about status change
        require_once __DIR__ . '/../admin-includes/mailer.php';
        
        try {
            // Get customer email
            $emailStmt = mysqli_prepare($conn, "SELECT email FROM bulk_orders WHERE id = ? LIMIT 1");
            
            if ($emailStmt) {
                mysqli_stmt_bind_param($emailStmt, "i", $order_id);
                mysqli_stmt_execute($emailStmt);
                mysqli_stmt_bind_result($emailStmt, $customer_email);
                mysqli_stmt_fetch($emailStmt);
                mysqli_stmt_close($emailStmt);

                if (!empty($customer_email) && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
                    $status_display = ucfirst(str_replace('_', ' ', $status));
                    $subject = "Bulk Order Status Update: {$status_display}";
                    $base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $fullLink = $base . $host . "/NeoCafe/frontend/pages/bulk/bulk-order-details.php?order_id=" . $order_id;
                    $body = "<!DOCTYPE html><html><body style='font-family: Arial, sans-serif; color:#333'>"
                          . "<h2>Bulk Order Status Update</h2>"
                          . "<p>Your bulk order has been <strong>{$status_display}</strong>.</p>"
                          . "<p><a href='" . $fullLink . "' style='background:#667eea;color:#fff;padding:10px 16px;border-radius:4px;text-decoration:none;'>View Order Details</a></p>"
                          . "<p style='font-size:12px;color:#777'>If the button doesn't work, copy and paste this URL:<br>" . $fullLink . "</p>"
                          . "<p>Thank you,<br>Neo Exclusive Cafe</p>"
                          . "</body></html>";
                    sendEmail($customer_email, $subject, $body, true);
                }
            }
        } catch (Exception $e) {
            error_log('Bulk order status email send failed: ' . $e->getMessage());
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
