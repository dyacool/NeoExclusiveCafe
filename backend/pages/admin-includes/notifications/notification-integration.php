<?php
/**
 * Notification Integration Helper
 * 
 * This file provides helper functions to create notifications from order and bulk order systems.
 * Include this file in your order processing scripts to automatically generate notifications.
 */

    require_once __DIR__ . '/../admin-includes/notifications/notification.php';

class NotificationIntegration {
    private $handler;
    
    public function __construct($database_connection) {
        $this->handler = new NotificationHandler($database_connection);
    }
    
    /**
     * Create notification when a new order is placed
     */
    public function notifyNewOrder($order_id, $customer_name, $username = null, $delivery_method = 'Pickup', $delivery_date = null, $delivery_time = null) {
        // Check if delivery is for tomorrow (warning notification)
        $is_tomorrow = false;
        if ($delivery_date && $delivery_date !== '0000-00-00') {
            $delivery_timestamp = strtotime($delivery_date);
            $tomorrow_timestamp = strtotime('+1 day', strtotime(date('Y-m-d')));
            $is_tomorrow = date('Y-m-d', $delivery_timestamp) === date('Y-m-d', $tomorrow_timestamp);
        }
        
        $notification_type = $is_tomorrow ? 'order_warning' : 'order_new';
        
        return $this->handler->createOrderNotification(
            $order_id, 
            $notification_type, 
            $customer_name, 
            $username, 
            null, 
            $delivery_method, 
            $delivery_date, 
            $delivery_time
        );
    }
    
    /**
     * Create notification when order status changes
     */
    public function notifyOrderStatusChange($order_id, $customer_name, $username = null, $new_status = 'Updated') {
        return $this->handler->createOrderNotification(
            $order_id, 
            'order_status', 
            $customer_name, 
            $username, 
            $new_status
        );
    }
    
    /**
     * Create notification when a new bulk order is submitted
     */
    public function notifyNewBulkOrder($bulk_order_id, $customer_name, $username = null) {
        return $this->handler->createBulkOrderNotification(
            $bulk_order_id, 
            'bulk_new', 
            $customer_name, 
            $username
        );
    }
    
    /**
     * Create notification when bulk order status changes
     */
    public function notifyBulkOrderStatusChange($bulk_order_id, $customer_name, $username = null, $new_status = 'Updated') {
        return $this->handler->createBulkOrderNotification(
            $bulk_order_id, 
            'bulk_status', 
            $customer_name, 
            $username, 
            $new_status
        );
    }
    
    /**
     * Create notification when bulk order payment is submitted
     */
    public function notifyBulkOrderPayment($bulk_order_id, $customer_name, $username = null) {
        return $this->handler->createBulkOrderNotification(
            $bulk_order_id, 
            'bulk_payment', 
            $customer_name, 
            $username
        );
    }
}

// Example usage in your order processing files:

/*
// In your order creation script:
include_once __DIR__ . '/path/to/notification-integration.php';

$notifier = new NotificationIntegration($your_database_connection);

// When a new order is placed:
$notifier->notifyNewOrder(
    $order_id, 
    $customer_name, 
    $username, 
    $delivery_method, 
    $delivery_date, 
    $delivery_time
);

// When order status changes:
$notifier->notifyOrderStatusChange($order_id, $customer_name, $username, 'Confirmed');

// When a new bulk order is submitted:
$notifier->notifyNewBulkOrder($bulk_order_id, $customer_name, $username);

// When bulk order status changes:
$notifier->notifyBulkOrderStatusChange($bulk_order_id, $customer_name, $username, 'Approved');

// When bulk order payment is uploaded:
$notifier->notifyBulkOrderPayment($bulk_order_id, $customer_name, $username);
*/

?>

<?php
/**
 * INTEGRATION EXAMPLES
 * 
 * Below are examples of how to integrate the notification system into your existing order processing files.
 */

// Example 1: Integrate into order creation (place in your order submission script)
/*
if ($order_inserted_successfully) {
    // Create notification for new order
    $notifier = new NotificationIntegration($conn);
    $notifier->notifyNewOrder(
        $order_id,
        $customer_name,
        $username ?? null,
        $delivery_method,
        $delivery_date,
        $delivery_time
    );
}
*/

// Example 2: Integrate into order status updates (place in your admin order management script)
/*
if ($status_updated_successfully) {
    // Get customer info from order
    $order_query = "SELECT customer_name, username FROM orders WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if ($order) {
        $notifier = new NotificationIntegration($conn);
        $notifier->notifyOrderStatusChange(
            $order_id,
            $order['customer_name'],
            $order['username'] ?? null,
            $new_status
        );
    }
}
*/

// Example 3: Integrate into bulk order submission (place in your bulk order creation script)
/*
if ($bulk_order_inserted_successfully) {
    $notifier = new NotificationIntegration($conn);
    $notifier->notifyNewBulkOrder(
        $bulk_order_id,
        $customer_name,
        $username ?? null
    );
}
*/

// Example 4: Integrate into bulk order payment upload (place in your payment processing script)
/*
if ($payment_uploaded_successfully) {
    // Get customer info from bulk order
    $bulk_query = "SELECT customer_name, username FROM bulk_orders WHERE id = ?";
    $stmt = mysqli_prepare($conn, $bulk_query);
    mysqli_stmt_bind_param($stmt, "i", $bulk_order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bulk_order = mysqli_fetch_assoc($result);
    
    if ($bulk_order) {
        $notifier = new NotificationIntegration($conn);
        $notifier->notifyBulkOrderPayment(
            $bulk_order_id,
            $bulk_order['customer_name'],
            $bulk_order['username'] ?? null
        );
    }
}
*/

// Example 5: Quick notification creation for custom scenarios
/*
// Direct notification creation for special cases
require_once __DIR__ . '/notification.php';
$handler = new NotificationHandler($conn);

$handler->create(
    'order_warning',
    'Special Alert: Large Order Received',
    'A large order of 50+ items has been placed by John Doe. Please prioritize preparation.',
    '/backend/pages/orders/view-orders.php?order_id=123',
    123
);
*/
?>