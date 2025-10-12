<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin - use correct session variable
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/notification.php';

header('Content-Type: application/json');

$handler = new NotificationHandler($conn);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_recent':
        $notifications = $handler->getRecent(10);
        $unread_count = $handler->getUnreadCount();
        
        // Format notifications for display
        $formatted = array_map(function($notif) use ($handler) {
            return [
                'id' => $notif['notif_id'],
                'type' => $notif['notif_type'],
                'title' => $notif['notif_title'],
                'message' => $notif['notif_message'],
                'link' => $notif['notif_link'],
                'is_read' => (bool)$notif['is_read'],
                'icon' => $handler->getIcon($notif['notif_type']),
                'time_ago' => $handler->timeAgo($notif['created_at']),
                'timestamp' => $notif['created_at']
            ];
        }, $notifications);
        
        echo json_encode([
            'success' => true,
            'notifications' => $formatted,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'mark_read':
        $notif_ids = json_decode($_POST['notif_ids'] ?? '[]', true);
        if (!is_array($notif_ids)) {
            echo json_encode(['error' => 'Invalid notification IDs']);
            break;
        }
        
        $result = $handler->markAsRead($notif_ids);
        echo json_encode([
            'success' => $result,
            'unread_count' => $handler->getUnreadCount()
        ]);
        break;
        
    case 'mark_all_read':
        $result = $handler->markAllAsRead();
        echo json_encode([
            'success' => $result,
            'unread_count' => 0
        ]);
        break;
        
    case 'delete':
        $notif_ids = json_decode($_POST['notif_ids'] ?? '[]', true);
        if (!is_array($notif_ids)) {
            echo json_encode(['error' => 'Invalid notification IDs']);
            break;
        }
        
        $result = $handler->delete($notif_ids);
        echo json_encode([
            'success' => $result,
            'unread_count' => $handler->getUnreadCount()
        ]);
        break;
        
    case 'get_unread_count':
        echo json_encode([
            'success' => true,
            'unread_count' => $handler->getUnreadCount()
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>