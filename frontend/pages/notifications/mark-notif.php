<?php

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

// Check if user is logged in and has proper role
if (!SessionManager::isUserLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

// Get user ID
$userId = SessionManager::getUserId();
require_once 'class-notif.php'; // Include the Notification class

header('Content-Type: application/json');

try {
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
        
        // Mark notification as read (with user validation)
        $success = $notification->markAsRead($notificationId, $userId);
        if ($success) {
            echo json_encode(["status" => "success", "message" => "Notification marked as read"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Notification not found or access denied"]);
        }
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
