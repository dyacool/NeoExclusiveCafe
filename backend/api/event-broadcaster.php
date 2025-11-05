<?php
/**
 * Event Broadcasting Service
 * 
 * Provides a simple API for broadcasting realtime notification events
 * to all connected clients via the event queue system
 */

require_once __DIR__ . '/event-queue.php';

class EventBroadcaster {
    
    /**
     * Broadcast an event to all connected clients
     * 
     * @param string $channel Event channel (order_status, product_inventory, new_order, notification)
     * @param array $data Event payload data
     * @param array $filters Optional filters (user_id, role, etc.)
     * @return int|false Event ID on success, false on failure
     */
    public static function broadcast($channel, $data, $filters = []) {
        // Validate channel
        $validChannels = ['order_status', 'product_inventory', 'new_order', 'notification', 'delivery_assignment'];
        if (!in_array($channel, $validChannels)) {
            error_log("[EventBroadcaster] Invalid channel: $channel");
            return false;
        }
        
        // Validate data
        if (empty($data) || !is_array($data)) {
            error_log("[EventBroadcaster] Invalid data provided for channel: $channel");
            return false;
        }
        
        // Add timestamp if not present
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = date('Y-m-d H:i:s');
        }
        
        // Add event to queue
        $eventId = EventQueue::addEvent($channel, $data, $filters);
        
        if ($eventId !== false) {
            error_log("[EventBroadcaster] Broadcasted event ID $eventId to channel '$channel'");
        } else {
            error_log("[EventBroadcaster] Failed to broadcast event to channel '$channel'");
        }
        
        return $eventId;
    }
    
    /**
     * Broadcast an event to a specific user
     * 
     * @param int $userId User ID to send the event to
     * @param string $channel Event channel
     * @param array $data Event payload data
     * @return int|false Event ID on success, false on failure
     */
    public static function broadcastToUser($userId, $channel, $data) {
        if (empty($userId) || !is_numeric($userId)) {
            error_log("[EventBroadcaster] Invalid user ID: $userId");
            return false;
        }
        
        $filters = ['user_id' => intval($userId)];
        
        error_log("[EventBroadcaster] Broadcasting to user $userId on channel '$channel'");
        return self::broadcast($channel, $data, $filters);
    }
    
    /**
     * Broadcast an event to users with a specific role
     * 
     * @param string $role User role (admin, user, rider, etc.)
     * @param string $channel Event channel
     * @param array $data Event payload data
     * @return int|false Event ID on success, false on failure
     */
    public static function broadcastToRole($role, $channel, $data) {
        if (empty($role)) {
            error_log("[EventBroadcaster] Invalid role provided");
            return false;
        }
        
        $filters = ['role' => $role];
        
        error_log("[EventBroadcaster] Broadcasting to role '$role' on channel '$channel'");
        return self::broadcast($channel, $data, $filters);
    }
    
    /**
     * Broadcast an order status update
     * 
     * @param int $orderId Order ID
     * @param string $status New order status
     * @param int $customerId Customer ID who owns the order
     * @param array $additionalData Optional additional data
     * @return int|false Event ID on success, false on failure
     */
    public static function broadcastOrderStatus($orderId, $status, $customerId, $additionalData = []) {
        $data = array_merge([
            'order_id' => intval($orderId),
            'status' => $status,
            'customer_id' => intval($customerId)
        ], $additionalData);
        
        // Broadcast to the specific customer
        return self::broadcastToUser($customerId, 'order_status', $data);
    }
    
    /**
     * Broadcast a new order notification to admins
     * 
     * @param int $orderId Order ID
     * @param string $customerName Customer name
     * @param string $orderType Order type (delivery/pickup)
     * @param float $total Order total amount
     * @param array $additionalData Optional additional data
     * @return int|false Event ID on success, false on failure
     */
    public static function broadcastNewOrder($orderId, $customerName, $orderType, $total, $additionalData = []) {
        $data = array_merge([
            'order_id' => intval($orderId),
            'customer_name' => $customerName,
            'order_type' => $orderType,
            'total' => floatval($total)
        ], $additionalData);
        
        // Broadcast to all admins
        return self::broadcastToRole('admin', 'new_order', $data);
    }
    
    /**
     * Broadcast a product inventory update
     * 
     * @param int $productId Product ID
     * @param int $quantity New quantity
     * @param string $productName Product name
     * @param array $additionalData Optional additional data
     * @return int|false Event ID on success, false on failure
     */
    public static function broadcastProductInventory($productId, $quantity, $productName, $additionalData = []) {
        $data = array_merge([
            'product_id' => intval($productId),
            'quantity' => intval($quantity),
            'available' => intval($quantity) > 0,
            'product_name' => $productName
        ], $additionalData);
        
        // Broadcast to all users (no filter)
        return self::broadcast('product_inventory', $data);
    }
    
    /**
     * Broadcast a delivery assignment to a rider
     * 
     * @param int $riderId Rider ID
     * @param int $orderId Order ID
     * @param string $customerAddress Customer delivery address
     * @param string $deliveryTime Delivery time
     * @param array $additionalData Optional additional data
     * @return int|false Event ID on success, false on failure
     */
    public static function broadcastDeliveryAssignment($riderId, $orderId, $customerAddress, $deliveryTime, $additionalData = []) {
        $data = array_merge([
            'order_id' => intval($orderId),
            'customer_address' => $customerAddress,
            'delivery_time' => $deliveryTime
        ], $additionalData);
        
        // Broadcast to specific rider
        return self::broadcastToUser($riderId, 'delivery_assignment', $data);
    }
    
    /**
     * Send a notification to a specific user
     * 
     * @param int $userId User ID
     * @param string $message Notification message
     * @param string $type Notification type (info, warning, success, error)
     * @param int|null $notificationId Optional notification ID from database
     * @param array $additionalData Optional additional data
     * @return int|false Event ID on success, false on failure
     */
    public static function sendNotification($userId, $message, $type = 'info', $notificationId = null, $additionalData = []) {
        $validTypes = ['info', 'warning', 'success', 'error'];
        if (!in_array($type, $validTypes)) {
            $type = 'info';
        }
        
        $data = array_merge([
            'message' => $message,
            'type' => $type,
            'read' => false
        ], $additionalData);
        
        if ($notificationId !== null) {
            $data['notification_id'] = intval($notificationId);
        }
        
        // Broadcast to specific user
        return self::broadcastToUser($userId, 'notification', $data);
    }
    
    /**
     * Check if event broadcasting is available
     * 
     * @return bool True if event queue is initialized and writable
     */
    public static function isAvailable() {
        return EventQueue::init();
    }
}
