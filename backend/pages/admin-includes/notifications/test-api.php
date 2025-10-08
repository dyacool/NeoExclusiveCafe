<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/notification.php';

header('Content-Type: application/json');

$handler = new NotificationHandler($conn);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_test':
        $type = $_POST['type'] ?? '';
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $link = $_POST['link'] ?? null;
        
        if (empty($type) || empty($title) || empty($message)) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }
        
        // Validate notification type
        $valid_types = ['order_new', 'order_status', 'order_warning', 'bulk_new', 'bulk_status', 'bulk_payment'];
        if (!in_array($type, $valid_types)) {
            echo json_encode(['error' => 'Invalid notification type']);
            break;
        }
        
        $result = $handler->create($type, $title, $message, $link, rand(1000, 9999));
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Notification created successfully' : 'Failed to create notification'
        ]);
        break;
        
    case 'clear_all':
        $result = mysqli_query($conn, "DELETE FROM admin_notifications");
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'All notifications cleared' : 'Failed to clear notifications'
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>