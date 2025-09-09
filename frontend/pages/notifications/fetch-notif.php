<?php
// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["status" => "error", "message" => "User not logged in", "count" => 0]);
    exit();
}

require_once __DIR__ . '/../../user-includes/database.php';
require_once __DIR__ . '/class-notif.php';

try {
    $userId = $_SESSION['user_id'];
    $notification = new Notification($conn);

    // Fetch unread notifications using the Notification class (order hidden if not verified)
    $notifications = $notification->getUnreadNotifications($userId);

    $response = [];
    foreach ($notifications as $n) {
        $title = !empty($n['title']) ? $n['title'] : $n['message'];
        $msg = $n['message'];
        $img = !empty($n['image_url']) ? $n['image_url'] : '/NeoExclusiveCafe/assets/images/default-product.png';
        $link = null;

        // Compute View Details link for order notifications
        if (isset($n['type']) && $n['type'] === 'order') {
            $m = [];
            if (preg_match('/Order\s*#(\d+)/i', $title . ' ' . $msg, $m)) {
                $orderId = (int)$m[1];
                if ($orderId > 0) {
                    $link = "/NeoExclusiveCafe/pages/users/order-details.php?order_id=" . $orderId;
                }
            }
        }

        $response[] = [
            'id' => (int)$n['id'],
            'type' => $n['type'],
            'title' => $title,
            'message' => $msg,
            'image_url' => $img,
            'is_read' => (int)$n['is_read'],
            'created_at' => $n['created_at'],
            'link' => $link
        ];
    }

    echo json_encode(["status" => "success", "count" => count($response), "notifications" => $response]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch notifications"]);
}
?>
