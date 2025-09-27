<?php

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // If the request is an AJAX request, return a 401 response
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "User not logged in"]);
    } else {
        // Otherwise, redirect to the login page
        header("Location: /frontend/login/user/login-signup.php");
    }
    exit();
}

require_once '../../../backend/pages/admin-includes/database.php'; // Include the database connection
require_once 'class-notif.php'; // Include the Notification class

header('Content-Type: application/json');

try {
    $userId = $_SESSION['user_id'];
    $notification = new Notification($conn);
    
    // Check if marking individual notification or all notifications
    $notificationId = $_POST['notification_id'] ?? null;
    $markAll = isset($_POST['mark_all']) && $_POST['mark_all'] === 'true';
    
    if ($notificationId) {
        // Mark individual notification as read
        // Verify the notification belongs to the current user
        $stmt = $conn->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notificationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Notification not found"]);
            exit();
        }
        $stmt->close();
        
        // Mark notification as read
        $notification->markAsRead($notificationId);
        echo json_encode(["status" => "success", "message" => "Notification marked as read"]);
    } elseif ($markAll) {
        // Mark all notifications as read using the Notification class
        $notification->markAllAsRead($userId);
        echo json_encode(["status" => "success", "message" => "All notifications marked as read"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request"]);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["status" => "error", "message" => "Failed to mark notifications as read"]);
    error_log("Database error: " . $e->getMessage());
}
?>
