
<?php
class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db; // mysqli connection
    }

    // Create a new notification aligned with schema (no link/order_id columns)
    public function create($userId, $type, $title, $message, $imageUrl = null) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, image_url, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $safeTitle = trim($title);
        $safeMessage = trim($message);
        $safeImage = $imageUrl ? trim($imageUrl) : null;
        $stmt->bind_param("issss", $userId, $type, $safeTitle, $safeMessage, $safeImage);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Create a notification for promotions using products table for image
    public function createPromotionNotification($userId, $productId, $promotionType) {
        $productName = null;
        $imageUrl = '/NeoExclusiveCafe/assets/images/default-product.png';
        if (!empty($productId)) {
            $q = "SELECT name, image_url FROM products WHERE id = ? LIMIT 1";
            $ps = $this->db->prepare($q);
            $ps->bind_param("i", $productId);
            $ps->execute();
            $res = $ps->get_result();
            if ($row = $res->fetch_assoc()) {
                $productName = $row['name'];
                if (!empty($row['image_url'])) {
                    $imageUrl = '/' . ltrim($row['image_url'], '/');
                }
            }
<<<<<<< HEAD
            $ps->close();
=======
            $imgStmt->close();
            $link = "../../pages/users/product-details.php?product_id=" . $productId;
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1
        }
        $productName = $productName ?: 'Product';
        $title = "Promotion: {$productName}";
        $message = "{$productName} has been marked as {$promotionType}.";
        return $this->create($userId, 'promotion', $title, $message, $imageUrl);
    }

    // Create a notification for system updates
    public function createWelcomeNotification($userId) {
<<<<<<< HEAD
        $title = 'Welcome to NeoExclusiveCafe';
        $message = 'Your account has been verified.';
        return $this->create($userId, 'system', $title, $message, null);
=======
        $message = "Welcome to NeoExclusiveCafe! Your account has been verified.";
        $this->create($userId, 'system_alert', $message);
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1
    }

    // Create a notification for order status updates with image from products table
    public function createOrderNotification($orderId, $status) {
        try {
            // Get order with user reference
            $orderQuery = "SELECT o.id AS order_id, o.user_id, o.customer_email FROM orders o WHERE o.id = ? LIMIT 1";
            $stmt = $this->db->prepare($orderQuery);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $orderRes = $stmt->get_result();
            $order = $orderRes->fetch_assoc();
            $stmt->close();
            if (!$order) return false;

            // Resolve user id (prefer orders.user_id; fallback by email)
            $userId = null;
            if (!empty($order['user_id'])) {
                $userId = (int)$order['user_id'];
            } elseif (!empty($order['customer_email'])) {
                $us = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $us->bind_param("s", $order['customer_email']);
                $us->execute();
                $uRes = $us->get_result();
                if ($u = $uRes->fetch_assoc()) { $userId = (int)$u['id']; }
                $us->close();
            }
            if (empty($userId)) return false;

            // Only verified users can receive order notifications
            $vs = $this->db->prepare("SELECT verified FROM users WHERE id = ? LIMIT 1");
            $vs->bind_param("i", $userId);
            $vs->execute();
            $vRes = $vs->get_result();
            $verifiedRow = $vRes->fetch_assoc();
            $vs->close();
            if (!$verifiedRow || (int)$verifiedRow['verified'] !== 1) {
                return false;
            }

            // Get one product image from products table through order_items
            $imageUrl = '/NeoExclusiveCafe/assets/images/default-product.png';
            $pi = $this->db->prepare("SELECT p.image_url FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? LIMIT 1");
            $pi->bind_param("i", $orderId);
            $pi->execute();
            $piRes = $pi->get_result();
            if ($row = $piRes->fetch_assoc()) {
                if (!empty($row['image_url'])) {
                    $imageUrl = '/' . ltrim($row['image_url'], '/');
                }
            }
            $pi->close();

<<<<<<< HEAD
            $safeStatus = trim($status);
            $title = "Order #{$orderId} Status Update";
            $message = "Your order #{$orderId} has been {$safeStatus}.";
=======
            // Prepare notification details
            $title = "Order Status Update";
            $message = "Your order #$orderId have been updated to $status. Click here to view order details.";
            $link = "../../pages/cart/order-details.php?order_id=" . $orderId;
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1

            return $this->create($userId, 'order', $title, $message, $imageUrl);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Fetch unread notifications for a user, hiding order notifications if user not verified
    public function getUnreadNotifications($userId) {
        $isVerified = 0;
        $vs = $this->db->prepare("SELECT verified FROM users WHERE id = ? LIMIT 1");
        $vs->bind_param("i", $userId);
        $vs->execute();
        $vRes = $vs->get_result();
        if ($row = $vRes->fetch_assoc()) { $isVerified = (int)$row['verified']; }
        $vs->close();

        if ($isVerified === 1) {
            $stmt = $this->db->prepare("SELECT id, user_id, type, title, message, image_url, is_read, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $this->db->prepare("SELECT id, user_id, type, title, message, image_url, is_read, created_at FROM notifications WHERE user_id = ? AND is_read = 0 AND type <> 'order' ORDER BY created_at DESC");
            $stmt->bind_param("i", $userId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) { $notifications[] = $row; }
        $stmt->close();
        return $notifications;
    }

    // Fetch all notifications for a user (respect verified rule for orders)
    public function getAllNotifications($userId) {
        $isVerified = 0;
        $vs = $this->db->prepare("SELECT verified FROM users WHERE id = ? LIMIT 1");
        $vs->bind_param("i", $userId);
        $vs->execute();
        $vRes = $vs->get_result();
        if ($row = $vRes->fetch_assoc()) { $isVerified = (int)$row['verified']; }
        $vs->close();

        if ($isVerified === 1) {
            $stmt = $this->db->prepare("SELECT id, user_id, type, title, message, image_url, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $this->db->prepare("SELECT id, user_id, type, title, message, image_url, is_read, created_at FROM notifications WHERE user_id = ? AND type <> 'order' ORDER BY created_at DESC");
            $stmt->bind_param("i", $userId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) { $notifications[] = $row; }
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
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return (int)($row['cnt'] ?? 0);
    }

<<<<<<< HEAD
    // Mark a single notification as read by notification ID
    public function markAsRead($notificationId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
=======

    // Mark a single notification as read by notification ID (with user validation)
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notificationId, $userId);
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
<<<<<<< HEAD
=======
        return $affectedRows > 0;
    }

    // Fetch notification details by ID for modal display
    public function getNotificationDetails($notificationId, $userId) {
        $stmt = $this->db->prepare("
            SELECT id, user_id, type, title, message, image_url, is_read, created_at, order_id 
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
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1
    }
}
