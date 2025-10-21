<?php
// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has proper role
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user") {
    // Always return JSON for AJAX requests, even when not logged in
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "User not logged in", "count" => 0]);
    exit();
}

// Validate user_id is numeric and positive
$userId = (int)$_SESSION['user_id'];
if ($userId <= 0) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid user session", "count" => 0]);
    exit();
}

require_once __DIR__ . '/../../../backend/pages/admin-includes/database.php'; // Include the database connection
require_once __DIR__ . '/class-notif.php'; // Include the Notification class

try {
    $notification = new Notification($conn);
    
    // Check if this is a request for specific notification details
    if (isset($_GET['id'])) {
        $notificationId = (int)$_GET['id'];
        $notificationDetails = $notification->getNotificationDetails($notificationId, $userId);
        
        if ($notificationDetails) {
            echo json_encode([
                "status" => "success", 
                "notification" => $notificationDetails
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "Notification not found"
            ]);
        }
        exit();
    }
    
    // Check if this is a request for dropdown (latest 5) or all notifications
    $isDropdown = isset($_GET['dropdown']) && $_GET['dropdown'] === 'true';
    
    if ($isDropdown) {
        // Fetch latest 5 notifications for dropdown
        $stmt = $conn->prepare("
            SELECT id, user_id, type, title, message, image_url, is_read, created_at, order_id, link 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
    } else {
        // Pagination for notifications page
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
        $offset = ($page - 1) * $perPage;

        $stmt = $conn->prepare("
            SELECT id, user_id, type, title, message, image_url, is_read, created_at, order_id, link
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $userId, $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();

        // Determine if there are more rows for next page (cheap check)
        $hasMore = count($notifications) === $perPage;
    }

    $response = [];
    foreach ($notifications as $n) {
        $title = !empty($n['title']) ? $n['title'] : $n['message'];
        $msg = $n['message'];
        $img = !empty($n['image_url']) ? $n['image_url'] : '/NeoExclusiveCafe/assets/images/default-product.png';

        $response[] = [
            'id' => (int)$n['id'],
            'type' => $n['type'],
            'title' => $title,
            'message' => $msg,
            'image_url' => $img,
            'is_read' => (int)$n['is_read'],
            'created_at' => $n['created_at'],
            'order_id' => $n['order_id'] ?? null,
            'link' => $n['link'] ?? null
        ];
    }

    $payload = [
        "status" => "success",
        "count" => count($response),
        "notifications" => $response
    ];
    if (!$isDropdown && !isset($_GET['id'])) {
        $payload['page'] = $page;
        $payload['per_page'] = $perPage;
        $payload['has_more'] = isset($hasMore) ? $hasMore : false;
    }
    echo json_encode($payload);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["status" => "error", "message" => "Failed to fetch notifications"]);
    error_log("Database error: " . $e->getMessage());
}
?>
