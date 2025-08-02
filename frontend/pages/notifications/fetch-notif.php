<?php
session_start(); // Start the session to access session variables

if (!isset($_SESSION["user_id"])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // If the request is an AJAX request, return a 401 response
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "User not logged in"]);
    } else {
        // Otherwise, redirect to the login page
        header("Location: /NeoExclusiveCafe/login/user/login.php");
    }
    exit();
}

require_once __DIR__ . '/../../user-includes/database.php'; // Include the database connection
require_once __DIR__ . '/class-notif.php'; // Include the Notification class

try {
    $userId = $_SESSION['user_id']; // Get the logged-in user's ID
    $notification = new Notification($conn); // Initialize the Notification class

    // Fetch unread notifications using the Notification class
    $notifications = $notification->getUnreadNotifications($userId);

    // Enrich notifications with product image, title, description, order info
    $enrichedNotifications = [];
    foreach ($notifications as $notif) {
        $notifData = $notif;

        // Use image_url and title from notifications table directly
        if (!empty($notif['order_id'])) {
            $notifData['product_image'] = !empty($notif['image_url']) ? $notif['image_url'] : "/assets/images/default-product.png";
            $notifData['title'] = !empty($notif['title']) ? $notif['title'] : ("Order #" . $notif['order_id'] . " Status Update");
            $notifData['description'] = $notif['message'];
            $notifData['link'] = !empty($notif['link']) ? $notif['link'] : ("/frontend/pages/profile/my-orders.php?order_id=" . $notif['order_id']);
        } else {
            $notifData['title'] = !empty($notif['title']) ? $notif['title'] : $notif['message'];
            $notifData['description'] = "";
            $notifData['product_image'] = !empty($notif['image_url']) ? $notif['image_url'] : "/assets/images/default-product.png";
            $notifData['link'] = !empty($notif['link']) ? $notif['link'] : null;
        }

        $enrichedNotifications[] = $notifData;
    }

    echo json_encode(["status" => "success", "notifications" => $enrichedNotifications]);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["status" => "error", "message" => "Failed to fetch notifications"]);
    error_log("Database error: " . $e->getMessage());
}
?>
