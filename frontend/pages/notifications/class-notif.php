<?php
class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db; // Database connection (MySQLi instance)
    }

    // Create a new notification
    public function create($userId, $type, $message, $title = null) {
        // If no title provided, generate one based on type
        if ($title === null) {
            switch ($type) {
                case 'order_confirmation':
                    $title = "Order Confirmed";
                    break;
                case 'order_ready':
                    $title = "Order Ready";
                    break;
                case 'system_alert':
                    $title = "System Alert";
                    break;
                default:
                    $title = "Notification";
                    break;
            }
        }
        
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $safeMessage = htmlspecialchars($message);
        $safeTitle = htmlspecialchars($title);
        $stmt->bind_param("isss", $userId, $type, $safeTitle, $safeMessage);
        $stmt->execute();
        $stmt->close();
    }

    // Create a notification for promotions
    public function createPromotionNotification($userId, $productName, $promotionType, $productId = null) {
        $message = "$productName has been marked as $promotionType.";
        $title = "Promotion: $productName";
        $imageUrl = '/NeoExclusiveCafe/assets/images/default-product.png';
        $link = null;
        if ($productId) {
            $imgStmt = $this->db->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
            $imgStmt->bind_param("i", $productId);
            $imgStmt->execute();
            $imgResult = $imgStmt->get_result();
            if ($imgRow = $imgResult->fetch_assoc()) {
                $imageUrl = '/' . ltrim($imgRow['image_url'], '/');
            }
            $imgStmt->close();
            $link = "../../pages/users/product-details.php?product_id=" . $productId;
        }
        $notifQuery = "INSERT INTO notifications (user_id, type, title, message, image_url, link, created_at, is_read) 
                       VALUES (?, 'promotion', ?, ?, ?, ?, NOW(), 0)";
        $stmt = $this->db->prepare($notifQuery);
        $stmt->bind_param("issss", $userId, $title, $message, $imageUrl, $link);
        $stmt->execute();
        $stmt->close();
    }

    // Create a notification for system updates
    public function createWelcomeNotification($userId) {
        $title = "Welcome to NeoExclusiveCafe!";
        $message = "Welcome to NeoExclusiveCafe! Your account has been verified.";
        $notifQuery = "INSERT INTO notifications (user_id, type, title, message, created_at, is_read) 
                       VALUES (?, 'system_alert', ?, ?, NOW(), 0)";
        $stmt = $this->db->prepare($notifQuery);
        $stmt->bind_param("iss", $userId, $title, $message);
        $stmt->execute();
        $stmt->close();
    }

    // Create a notification for order status updates with image and order_id
    public function createOrderNotification($orderId, $status) {
        try {
            // First, get the order details with customer email
            $orderQuery = "SELECT o.customer_id, o.order_id, o.status, o.customer_email 
                          FROM orders o 
                          WHERE o.order_id = ?";
            
            $stmt = $this->db->prepare($orderQuery);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();

            if (!$order) {
                file_put_contents(__DIR__ . '/../../logs/order_errors.log', "[Notif Error] No order found for orderId: $orderId\n", FILE_APPEND);
                return false;
            }

            // Get user by customer_email from users table
            $userQuery = "SELECT id FROM users WHERE email = ?";
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bind_param("s", $order['customer_email']);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $user = $userResult->fetch_assoc();
            $userStmt->close();

            if (!$user) {
                file_put_contents(__DIR__ . '/../../logs/order_errors.log', 
                    "[Notif Error] User not found for Order #$orderId (Email: {$order['customer_email']})\n", 
                    FILE_APPEND
                );
                return false;
            }

            // Get the product image if available
            $imageQuery = "SELECT pi.image_url 
                          FROM order_items oi 
                          LEFT JOIN products p ON p.name = oi.product_name
                          LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
                          WHERE oi.order_id = ? 
                          LIMIT 1";
            
            $imgStmt = $this->db->prepare($imageQuery);
            $imgStmt->bind_param("i", $orderId);
            $imgStmt->execute();
            $imgResult = $imgStmt->get_result();
            $imageUrl = '/NeoExclusiveCafe/assets/images/default-product.png'; // Default image
            
            if ($imgRow = $imgResult->fetch_assoc()) {
                if (!empty($imgRow['image_url'])) {
                    $imageUrl = '/' . ltrim($imgRow['image_url'], '/');
                }
            }
            $imgStmt->close();

            // Prepare notification details
            $title = "Order Status Update";
            $message = "Your order #$orderId have been updated to $status.";
            $link = "../../pages/cart/order_details.php?order_id=" . $orderId; // Link to order details page

            // Insert the notification
            $notifQuery = "INSERT INTO notifications (user_id, type, title, message, image_url, order_id, link, created_at, is_read)
                          VALUES (?, 'order_update', ?, ?, ?, ?, ?, NOW(), 0)";
            
            $notifStmt = $this->db->prepare($notifQuery);
            if (!$notifStmt) {
                throw new Exception("Failed to prepare notification insert: " . $this->db->error);
            }

            $notifStmt->bind_param("isssis", 
                $user['id'],      // user_id
                $title,           // title
                $message,         // message
                $imageUrl,        // image_url
                $orderId,         // order_id
                $link            // link
            );

            if (!$notifStmt->execute()) {
                throw new Exception("Failed to insert notification: " . $notifStmt->error);
            }

            $notifStmt->close();
            return true;

        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../../logs/order_errors.log', 
                "[Notif Error] Failed to create notification for Order #$orderId: " . $e->getMessage() . "\n", 
                FILE_APPEND
            );
            return false;
        }
    }

    // Fetch unread notifications for a user
    public function getUnreadNotifications($userId) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        return $notifications;
    }

    // Fetch all notifications for a user
    public function getAllNotifications($userId) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        return $notifications;
    }

    // Mark all notifications as read for a user
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Get the count of unread notifications for a user
    public function getUnreadCount($userId) {
        $count = 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count;
    }


    // Mark a single notification as read by notification ID (with user validation)
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notificationId, $userId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows > 0;
    }

    // Fetch notification details by ID for modal display
    public function getNotificationDetails($notificationId, $userId) {
        $stmt = $this->db->prepare("
            SELECT id, user_id, type, title, message, image_url, is_read, created_at, order_id, link 
            FROM notifications 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $notificationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $notification = $result->fetch_assoc();
        $stmt->close();
        
        // If it's an order notification, fetch order details
        if ($notification['type'] === 'order' && !empty($notification['order_id'])) {
            $orderId = $notification['order_id'];
            
            // Fetch order details
            $orderStmt = $this->db->prepare("
                SELECT o.id, o.customer_name, o.customer_email, o.customer_phone, 
                       o.delivery_address, o.total_amount, o.status, o.created_at as order_date,
                       GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE o.id = ?
                GROUP BY o.id
            ");
            $orderStmt->bind_param("i", $orderId);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            
            if ($orderResult->num_rows > 0) {
                $orderDetails = $orderResult->fetch_assoc();
                $notification['order_details'] = [
                    'id' => (int)$orderDetails['id'],
                    'customer_name' => $orderDetails['customer_name'],
                    'customer_email' => $orderDetails['customer_email'],
                    'customer_phone' => $orderDetails['customer_phone'],
                    'delivery_address' => $orderDetails['delivery_address'],
                    'total_amount' => $orderDetails['total_amount'],
                    'status' => $orderDetails['status'],
                    'order_date' => $orderDetails['order_date'],
                    'items' => $orderDetails['items']
                ];
            }
            $orderStmt->close();
        }
        
        return $notification;
    }
}
