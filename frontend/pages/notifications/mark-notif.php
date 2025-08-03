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

require_once '../../user-includes/database.php'; // Include the database connection
require_once 'class-notif.php'; // Include the Notification class

try {
    $userId = $_SESSION['user_id']; // Get the logged-in user's ID
    $notification = new Notification($conn); // Initialize the Notification class

    // Mark all notifications as read using the Notification class
    $notification->markAllAsRead($userId);

    echo json_encode(["status" => "success", "message" => "All notifications marked as read"]);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["status" => "error", "message" => "Failed to mark notifications as read"]);
    error_log("Database error: " . $e->getMessage());
}
?>
