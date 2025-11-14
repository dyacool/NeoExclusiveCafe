<?php
// Suppress ALL errors immediately
error_reporting(0);
ini_set('display_errors', '0');

// Start output buffering BEFORE anything else
ob_start();

// Suppress session warnings
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);

// Load SessionManager for authentication
require_once __DIR__ . '/../../../../../includes/session-manager.php';

// Load database connection
$suppress_db_debug = true; // Prevent database.php from outputting debug info
try {
    require_once __DIR__ . '/../database.php';
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Check if connection exists
if (!isset($conn) || !$conn) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database not connected']);
    exit;
}

require_once __DIR__ . '/notification.php';

// Clean all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh buffer
ob_start();

// Set JSON header
if (!headers_sent()) {
    header('Content-Type: application/json');
}

try {
    // Suppress errors during handler initialization
    $old_error_reporting = error_reporting(0);
    $old_display_errors = ini_get('display_errors');
    ini_set('display_errors', '0');
    
    $handler = new NotificationHandler($conn);
    
    // Restore error settings
    error_reporting($old_error_reporting);
    ini_set('display_errors', $old_display_errors);
    
    // Verify handler was created successfully
    if (!$handler) {
        throw new Exception('Failed to create notification handler');
    }
    
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
            
            // Clear any output that might have been generated
            ob_clean();
            
            echo json_encode([
                'success' => true,
                'notifications' => $formatted,
                'unread_count' => $unread_count
            ]);
            break;
            
        case 'mark_read':
            $notif_ids = json_decode($_POST['notif_ids'] ?? '[]', true);
            if (!is_array($notif_ids)) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Invalid notification IDs']);
                break;
            }
            
            $result = $handler->markAsRead($notif_ids);
            ob_clean();
            echo json_encode([
                'success' => $result,
                'unread_count' => $handler->getUnreadCount()
            ]);
            break;
            
        case 'mark_all_read':
            $result = $handler->markAllAsRead();
            ob_clean();
            echo json_encode([
                'success' => $result,
                'unread_count' => 0
            ]);
            break;
            
        case 'delete':
            $notif_ids = json_decode($_POST['notif_ids'] ?? '[]', true);
            if (!is_array($notif_ids)) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Invalid notification IDs']);
                break;
            }
            
            $result = $handler->delete($notif_ids);
            ob_clean();
            echo json_encode([
                'success' => $result,
                'unread_count' => $handler->getUnreadCount()
            ]);
            break;
            
        case 'get_unread_count':
            ob_clean();
            echo json_encode([
                'success' => true,
                'unread_count' => $handler->getUnreadCount()
            ]);
            break;
            
        default:
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service error: ' . $e->getMessage()]);
}

// Flush the output buffer
if (ob_get_level()) {
    ob_end_flush();
}
exit;