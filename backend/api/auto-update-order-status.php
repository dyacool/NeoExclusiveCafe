<?php
/**
 * Automatic Order Status Update Cron Job
 * 
 * This script automatically updates order statuses based on delivery/pickup dates
 * and respects business hours for "due today" transitions.
 * 
 * Schedule: Run hourly during business hours (e.g., 6 AM - 11 PM)
 * 
 * Status Transition Rules:
 * - Pickup Orders:
 *   - Due tomorrow: Confirmed → Preparing
 *   - Due today (during business hours): Preparing → Ready for Pick-up
 * 
 * - Delivery Orders:
 *   - Due tomorrow: Confirmed → Preparing
 *   - Due today (during business hours): Preparing → Ready for Delivery
 */

// Allow execution from command line or web (with proper authentication)
if (php_sapi_name() !== 'cli') {
    session_start();
    // For web access, require admin authentication
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized access'
        ]);
        exit();
    }
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../pages/admin-includes/activity-logger.php';

// Initialize response
$response = [
    'success' => false,
    'updated_count' => 0,
    'orders_updated' => [],
    'errors' => [],
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Step 1: Check if auto-status is enabled
    $check_sql = "SELECT auto_status_enabled FROM order_status_settings 
                  WHERE admin_id IS NULL ORDER BY updated_at DESC LIMIT 1";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (!$check_result || mysqli_num_rows($check_result) == 0) {
        $response['message'] = 'Auto-status setting not found. Defaulting to disabled.';
        echo json_encode($response);
        exit();
    }
    
    $setting = mysqli_fetch_assoc($check_result);
    if (!$setting['auto_status_enabled']) {
        $response['message'] = 'Auto-status is disabled. No updates performed.';
        echo json_encode($response);
        exit();
    }
    
    // Step 2: Get business hours
    $business_hours_sql = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $business_hours_result = mysqli_query($conn, $business_hours_sql);
    
    $opening_time = '08:00:00'; // Default
    $closing_time = '21:00:00'; // Default
    
    if ($business_hours_result && mysqli_num_rows($business_hours_result) > 0) {
        $business_hours = mysqli_fetch_assoc($business_hours_result);
        $opening_time = $business_hours['opening_time'];
        $closing_time = $business_hours['closing_time'];
    }
    
    $response['business_hours'] = [
        'opening' => $opening_time,
        'closing' => $closing_time
    ];
    
    // Step 3: Check if we're currently within business hours
    $current_time = date('H:i:s');
    $is_business_hours = ($current_time >= $opening_time && $current_time <= $closing_time);
    $response['is_business_hours'] = $is_business_hours;
    $response['current_time'] = $current_time;
    
    // Step 4: Get dates for comparison
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // Step 5: Update orders due tomorrow to "Preparing"
    // This happens regardless of business hours
    
    // Pickup orders due tomorrow
    $sql = "SELECT order_id, customer_email, customer_name FROM orders 
            WHERE delivery_method = 'Pick-up' 
            AND pickup_date = ? 
            AND status = 'Confirmed'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tomorrow);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($order = mysqli_fetch_assoc($result)) {
        if (updateOrderStatus($conn, $order['order_id'], 'Preparing', $order['customer_email'], $order['customer_name'])) {
            $response['orders_updated'][] = [
                'order_id' => $order['order_id'],
                'new_status' => 'Preparing',
                'reason' => 'Pickup due tomorrow'
            ];
            $response['updated_count']++;
        }
    }
    mysqli_stmt_close($stmt);
    
    // Delivery orders due tomorrow
    $sql = "SELECT order_id, customer_email, customer_name FROM orders 
            WHERE delivery_method = 'Delivery' 
            AND delivery_date = ? 
            AND status = 'Confirmed'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tomorrow);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($order = mysqli_fetch_assoc($result)) {
        if (updateOrderStatus($conn, $order['order_id'], 'Preparing', $order['customer_email'], $order['customer_name'])) {
            $response['orders_updated'][] = [
                'order_id' => $order['order_id'],
                'new_status' => 'Preparing',
                'reason' => 'Delivery due tomorrow'
            ];
            $response['updated_count']++;
        }
    }
    mysqli_stmt_close($stmt);
    
    // Step 6: Update orders due today to "Ready" status
    // ONLY if we're within business hours
    if ($is_business_hours) {
        // Pickup orders due today
        $sql = "SELECT order_id, customer_email, customer_name FROM orders 
                WHERE delivery_method = 'Pick-up' 
                AND pickup_date = ? 
                AND status = 'Preparing'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($order = mysqli_fetch_assoc($result)) {
            if (updateOrderStatus($conn, $order['order_id'], 'Ready for Pick-up', $order['customer_email'], $order['customer_name'])) {
                $response['orders_updated'][] = [
                    'order_id' => $order['order_id'],
                    'new_status' => 'Ready for Pick-up',
                    'reason' => 'Pickup due today (business hours active)'
                ];
                $response['updated_count']++;
            }
        }
        mysqli_stmt_close($stmt);
        
        // Delivery orders due today
        $sql = "SELECT order_id, customer_email, customer_name FROM orders 
                WHERE delivery_method = 'Delivery' 
                AND delivery_date = ? 
                AND status = 'Preparing'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($order = mysqli_fetch_assoc($result)) {
            if (updateOrderStatus($conn, $order['order_id'], 'Ready for Delivery', $order['customer_email'], $order['customer_name'])) {
                $response['orders_updated'][] = [
                    'order_id' => $order['order_id'],
                    'new_status' => 'Ready for Delivery',
                    'reason' => 'Delivery due today (business hours active)'
                ];
                $response['updated_count']++;
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['skipped_today_updates'] = 'Outside business hours - orders due today not updated to Ready status';
    }
    
    $response['success'] = true;
    $response['message'] = "Auto-update completed. {$response['updated_count']} orders updated.";
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    $response['message'] = 'Auto-update failed with error';
    error_log('Auto-update order status error: ' . $e->getMessage());
}

// Output response
echo json_encode($response, JSON_PRETTY_PRINT);

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}

/**
 * Update order status and send notifications
 * 
 * @param mysqli $conn Database connection
 * @param int $order_id Order ID to update
 * @param string $new_status New status value
 * @param string $customer_email Customer email for notification
 * @param string $customer_name Customer name for notification
 * @return bool Success status
 */
function updateOrderStatus($conn, $order_id, $new_status, $customer_email, $customer_name) {
    try {
        // Update order status
        $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Failed to update order $order_id: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        mysqli_stmt_close($stmt);
        
        // Log the activity
        logAdminActivity($conn, 'AUTO-UPDATE', "Auto-changed order #$order_id status to '$new_status'", 'orders', $order_id);
        
        // Send in-app notification
        if (file_exists(__DIR__ . '/../../frontend/pages/notifications/class-notif.php')) {
            require_once __DIR__ . '/../../frontend/pages/notifications/class-notif.php';
            $notification = new Notification($conn);
            $notification->createOrderNotification($order_id, $new_status);
        }
        
        // Send email notification
        if (!empty($customer_email) && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            sendEmailNotification($order_id, $new_status, $customer_email, $customer_name);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error updating order $order_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification to customer
 * 
 * @param int $order_id Order ID
 * @param string $status New status
 * @param string $email Customer email
 * @param string $name Customer name
 */
function sendEmailNotification($order_id, $status, $email, $name) {
    try {
        if (file_exists(__DIR__ . '/../pages/admin-includes/mailer.php')) {
            require_once __DIR__ . '/../pages/admin-includes/mailer.php';
            
            $subject = "Order #{$order_id} Status Update: {$status}";
            $base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $fullLink = $base . $host . "/NeoCafe/frontend/pages/cart/order-details.php?order_id=" . $order_id;
            
            $body = "<!DOCTYPE html><html><body style='font-family: Arial, sans-serif; color:#333'>"
                  . "<h2>Hello " . htmlspecialchars($name) . ",</h2>"
                  . "<p>Your order #{$order_id} status has been automatically updated to <strong>{$status}</strong>.</p>"
                  . "<p><a href='" . $fullLink . "' style='background:#667eea;color:#fff;padding:10px 16px;border-radius:4px;text-decoration:none;'>View Order Details</a></p>"
                  . "<p style='font-size:12px;color:#777'>If the button doesn't work, copy and paste this URL:<br>" . $fullLink . "</p>"
                  . "<p>Thank you,<br>Neo Exclusive Cafe</p>"
                  . "</body></html>";
            
            sendEmail($email, $subject, $body, true);
        }
    } catch (Exception $e) {
        error_log('Email notification failed for order ' . $order_id . ': ' . $e->getMessage());
    }
}
?>
