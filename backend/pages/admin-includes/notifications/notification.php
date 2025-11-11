<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database.php';

class NotificationHandler {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->createTableIfNotExists();
    }
    
    // Create table if it doesn't exist
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS `admin_notifications` (
            `notif_id` int(11) NOT NULL AUTO_INCREMENT,
            `notif_type` enum('order_new','order_status','order_warning','order_due','order_overdue','bulk_new','bulk_status','bulk_payment','refund_new','refund_status') NOT NULL,
            `notif_title` varchar(255) NOT NULL,
            `notif_message` text NOT NULL,
            `notif_link` varchar(500) DEFAULT NULL,
            `notif_reference_id` int(11) DEFAULT NULL,
            `is_read` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`notif_id`),
            KEY `notif_type` (`notif_type`),
            KEY `is_read` (`is_read`),
            KEY `created_at` (`created_at`),
            KEY `notif_reference_id` (`notif_reference_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        mysqli_query($this->conn, $sql);
    }
    
    // Create a new notification
    public function create($type, $title, $message, $link = null, $reference_id = null) {
        $stmt = mysqli_prepare($this->conn, "
            INSERT INTO admin_notifications (notif_type, notif_title, notif_message, notif_link, notif_reference_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "ssssi", $type, $title, $message, $link, $reference_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    
    // Helper method to create order notifications
    public function createOrderNotification($order_id, $type, $customer_name, $username = null, $status = null, $delivery_method = null, $delivery_date = null, $delivery_time = null) {
        $base_link = "/backend/pages/orders/view-orders.php?order_id=" . $order_id;
        $user_display = $username ? "@{$username}" : $customer_name;
        
        switch ($type) {
            case 'order_new':
                $delivery_info = $delivery_method === 'Delivery' ? 'delivery' : 'pickup';
                $date_time = '';
                if ($delivery_date && $delivery_date !== '0000-00-00') {
                    $date_time = ' on ' . date('m/d/y', strtotime($delivery_date));
                    if ($delivery_time && $delivery_time !== '00:00:00') {
                        $date_time .= ' at ' . date('g:i a', strtotime($delivery_time));
                    }
                }
                $title = "Order #{$order_id} - New Order Placed";
                $message = "User {$user_display} placed an order for {$delivery_info}{$date_time}";
                break;
                
            case 'order_status':
                $title = "Order #{$order_id} - Status Updated";
                $message = "User {$user_display} order status has been updated to {$status}";
                break;
                
            case 'order_warning':
                $delivery_info = $delivery_method === 'Delivery' ? 'delivery' : 'pickup';
                $date_time = '';
                if ($delivery_date && $delivery_date !== '0000-00-00') {
                    $date_time = ' on ' . date('m/d/y', strtotime($delivery_date));
                    if ($delivery_time && $delivery_time !== '00:00:00') {
                        $date_time .= ' at ' . date('g:i a', strtotime($delivery_time));
                    }
                }
                $title = "Order #{$order_id} - Delivery Alert";
                $message = "Heads up! User {$user_display} placed an order for {$delivery_info}{$date_time} — that's tomorrow. Make sure everything is ready in time.";
                break;
                
            case 'order_due':
                $delivery_info = $delivery_method === 'Delivery' ? 'delivery' : 'pickup';
                $date_time = '';
                if ($delivery_date && $delivery_date !== '0000-00-00') {
                    $date_time = ' on ' . date('m/d/y', strtotime($delivery_date));
                    if ($delivery_time && $delivery_time !== '00:00:00') {
                        $date_time .= ' at ' . date('g:i a', strtotime($delivery_time));
                    }
                }
                $title = "Order #{$order_id} - Due Today";
                $message = "Order from {$user_display} is due for {$delivery_info} today{$date_time}. Please ensure it's ready!";
                break;
                
            case 'order_overdue':
                $delivery_info = $delivery_method === 'Delivery' ? 'delivery' : 'pickup';
                $date_time = '';
                if ($delivery_date && $delivery_date !== '0000-00-00') {
                    $date_time = ' on ' . date('m/d/y', strtotime($delivery_date));
                    if ($delivery_time && $delivery_time !== '00:00:00') {
                        $date_time .= ' at ' . date('g:i a', strtotime($delivery_time));
                    }
                }
                $title = "Order #{$order_id} -  OVERDUE";
                $message = "URGENT: Order from {$user_display} is overdue for {$delivery_info}{$date_time}. Please take immediate action!";
                break;
                
            default:
                return false;
        }
        
        return $this->create($type, $title, $message, $base_link, $order_id);
    }
    
    // Helper method to create bulk order notifications
    public function createBulkOrderNotification($bulk_order_id, $type, $customer_name, $username = null, $status = null) {
        $base_link = "/backend/pages/bulks/bulk-order.php?id=" . $bulk_order_id;
        $user_display = $username ? "@{$username}" : $customer_name;
        
        switch ($type) {
            case 'bulk_new':
                $title = "Bulk Order #{$bulk_order_id} - New Request";
                $message = "User {$user_display} submitted a bulk order request for review.";
                break;
                
            case 'bulk_status':
                $title = "Bulk Order #{$bulk_order_id} - Status Updated";
                $message = "User {$user_display} bulk order status has been updated to {$status}";
                break;
                
            case 'bulk_payment':
                $title = "Bulk Order #{$bulk_order_id} - Payment Submitted";
                $message = "User {$user_display} uploaded proof of payment. Please verify the details.";
                break;
                
            default:
                return false;
        }
        
        return $this->create($type, $title, $message, $base_link, $bulk_order_id);
    }
    
    // Helper method to create refund notifications
    public function createRefundNotification($refund_id, $order_id, $type, $customer_name, $username = null, $status = null, $refund_amount = null) {
        $base_link = "/backend/pages/refund/refund-request-lists.php";
        $user_display = $username ? "@{$username}" : $customer_name;
        
        switch ($type) {
            case 'refund_new':
                $amount_text = $refund_amount ? ' of ₱' . number_format($refund_amount, 2) : '';
                $title = "Refund Request #{$refund_id} - New Request";
                $message = "User {$user_display} submitted a refund request{$amount_text} for Order #{$order_id}. Please review and take action.";
                break;
                
            case 'refund_status':
                $title = "Refund Request #{$refund_id} - Status Updated";
                $message = "Refund request from {$user_display} for Order #{$order_id} has been {$status}.";
                break;
                
            default:
                return false;
        }
        
        return $this->create($type, $title, $message, $base_link, $refund_id);
    }
    
    // Check for due and overdue orders and create notifications
    public function checkDueAndOverdueOrders() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Check for orders due today (that haven't been completed yet)
        $due_sql = "SELECT order_id, customer_name, delivery_method, delivery_date, pickup_date, delivery_time, pickup_time 
                    FROM orders 
                    WHERE ((delivery_method = 'Delivery' AND delivery_date = ?) OR 
                           (delivery_method = 'Pick-up' AND pickup_date = ?))
                    AND status NOT IN ('Delivered', 'Picked-up', 'Completed', 'Cancelled')
                    AND order_id NOT IN (
                        SELECT notif_reference_id FROM admin_notifications 
                        WHERE notif_type = 'order_due' 
                        AND DATE(created_at) = ?
                    )";
        
        $due_stmt = mysqli_prepare($this->conn, $due_sql);
        mysqli_stmt_bind_param($due_stmt, "sss", $today, $today, $today);
        mysqli_stmt_execute($due_stmt);
        $due_result = mysqli_stmt_get_result($due_stmt);
        
        while ($order = mysqli_fetch_assoc($due_result)) {
            $delivery_date = $order['delivery_method'] === 'Delivery' ? $order['delivery_date'] : $order['pickup_date'];
            $delivery_time = $order['delivery_method'] === 'Delivery' ? $order['delivery_time'] : $order['pickup_time'];
            
            $this->createOrderNotification(
                $order['order_id'], 
                'order_due', 
                $order['customer_name'], 
                null, 
                null, 
                $order['delivery_method'], 
                $delivery_date, 
                $delivery_time
            );
        }
        mysqli_stmt_close($due_stmt);
        
        // Check for overdue orders (past due date and not completed)
        $overdue_sql = "SELECT order_id, customer_name, delivery_method, delivery_date, pickup_date, delivery_time, pickup_time 
                        FROM orders 
                        WHERE ((delivery_method = 'Delivery' AND delivery_date < ?) OR 
                               (delivery_method = 'Pick-up' AND pickup_date < ?))
                        AND status NOT IN ('Delivered', 'Picked-up', 'Completed', 'Cancelled')
                        AND order_id NOT IN (
                            SELECT notif_reference_id FROM admin_notifications 
                            WHERE notif_type = 'order_overdue' 
                            AND DATE(created_at) = ?
                        )";
        
        $overdue_stmt = mysqli_prepare($this->conn, $overdue_sql);
        mysqli_stmt_bind_param($overdue_stmt, "sss", $today, $today, $today);
        mysqli_stmt_execute($overdue_stmt);
        $overdue_result = mysqli_stmt_get_result($overdue_stmt);
        
        while ($order = mysqli_fetch_assoc($overdue_result)) {
            $delivery_date = $order['delivery_method'] === 'Delivery' ? $order['delivery_date'] : $order['pickup_date'];
            $delivery_time = $order['delivery_method'] === 'Delivery' ? $order['delivery_time'] : $order['pickup_time'];
            
            $this->createOrderNotification(
                $order['order_id'], 
                'order_overdue', 
                $order['customer_name'], 
                null, 
                null, 
                $order['delivery_method'], 
                $delivery_date, 
                $delivery_time
            );
        }
        mysqli_stmt_close($overdue_stmt);
    }
    
    // Get recent notifications (limit 10 for dropdown)
    public function getRecent($limit = 10) {
        $stmt = mysqli_prepare($this->conn, "
            SELECT * FROM admin_notifications 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $notifications;
    }
    
    // Get all notifications with pagination
    public function getAll($offset = 0, $limit = 50) {
        $stmt = mysqli_prepare($this->conn, "
            SELECT * FROM admin_notifications 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $notifications;
    }
    
    // Get unread count
    public function getUnreadCount() {
        $result = mysqli_query($this->conn, "SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
        $row = mysqli_fetch_assoc($result);
        return $row['count'];
    }
    
    // Mark as read
    public function markAsRead($notif_ids) {
        if (empty($notif_ids)) return false;
        
        $ids = implode(',', array_map('intval', $notif_ids));
        return mysqli_query($this->conn, "UPDATE admin_notifications SET is_read = 1 WHERE notif_id IN ($ids)");
    }
    
    // Mark all as read
    public function markAllAsRead() {
        return mysqli_query($this->conn, "UPDATE admin_notifications SET is_read = 1");
    }
    
    // Delete notifications
    public function delete($notif_ids) {
        if (empty($notif_ids)) return false;
        
        $ids = implode(',', array_map('intval', $notif_ids));
        return mysqli_query($this->conn, "DELETE FROM admin_notifications WHERE notif_id IN ($ids)");
    }
    
    // Get notification icon based on type
    public function getIcon($type) {
        // Return empty string - no icons
        return '';
    }
    
    // Format time ago
    public function timeAgo($timestamp) {
        $time = strtotime($timestamp);
        $diff = time() - $time;
        
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        return date('M j, Y', $time);
    }
}

// ============================================
// API ENDPOINT HANDLER
// ============================================
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $handler = new NotificationHandler($conn);
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch ($action) {
        case 'get_notifications':
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $notifications = $handler->getRecent($limit);
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;
            
        case 'get_unread_count':
            $count = $handler->getUnreadCount();
            echo json_encode(['success' => $count]);
            break;
            
        case 'mark_as_read':
            $ids = json_decode(file_get_contents('php://input'), true)['ids'] ?? [];
            $result = $handler->markAsRead($ids);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_all_as_read':
            $result = $handler->markAllAsRead();
            echo json_encode(['success' => $result]);
            break;
            
        case 'delete':
            $ids = json_decode(file_get_contents('php://input'), true)['ids'] ?? [];
            $result = $handler->delete($ids);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// ============================================
// ALLOWED PAGES FOR AUTO-CHECKING ORDERS
// ============================================
$current_file = basename($_SERVER['PHP_SELF']);
$allowed_auto_check_pages = [
    'dashboard.php',
    'order-list.php',
    'bulk-order-lists.php',
    'refund-request-lists.php'
];

// Only check for due/overdue orders on specific pages
if (in_array($current_file, $allowed_auto_check_pages)) {
    $handler = new NotificationHandler($conn);
    $handler->checkDueAndOverdueOrders();
}

// Only render notification bell UI on dashboard page
// The NotificationHandler class is still available for creating notifications on all admin pages
// But the UI (bell button and dropdown) only appears on dashboard
$is_dashboard_page = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && 
                     (basename($_SERVER['PHP_SELF']) === 'dashboard.php' || 
                      strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false);

if (!isset($NOTIFICATION_BELL_RENDERED) && $is_dashboard_page) {
    $NOTIFICATION_BELL_RENDERED = true;
    $handler = new NotificationHandler($conn);
    $unreadCount = $handler->getUnreadCount();
?>
<!-- Notification Bell Icon -->
<div class="notification-bell-container">
    <button class="notification-bell-btn" id="notificationBellBtn" aria-label="Notifications">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge" id="notificationBadge"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
    </button>
    
    <!-- Dropdown -->
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-dropdown-header">
            <h3>Notifications</h3>
            <button class="mark-all-read-btn" id="markAllReadBtn">Mark all as read</button>
        </div>
        
        <div class="notification-list" id="notificationList">
            <div class="notification-loading">Loading...</div>
        </div>
        
        <div class="notification-dropdown-footer">
            <a href="/backend/pages/notifications/all-notifications.php" class="view-all-btn">View all notifications</a>
        </div>
    </div>
</div>
<?php
}
?>
