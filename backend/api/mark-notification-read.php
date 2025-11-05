<?php
/**
 * Mark Notification as Read API
 * 
 * Marks one or more notifications as read for the authenticated user
 */

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = intval($_SESSION['user_id']);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Check if marking all as read
$markAll = isset($input['mark_all']) && $input['mark_all'] === true;

// Connect to database
require_once '../pages/admin-includes/database.php';

try {
    if ($markAll) {
        // Mark all notifications as read for this user
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            
            echo json_encode([
                'success' => true,
                'marked_count' => $affectedRows,
                'message' => "Marked $affectedRows notifications as read"
            ]);
            
            error_log("[MarkNotificationRead] Marked all notifications as read for user $userId");
        } else {
            throw new Exception('Failed to mark notifications as read: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } else {
        // Mark specific notification(s) as read
        if (!isset($input['notification_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing notification_id']);
            exit();
        }
        
        $notificationIds = is_array($input['notification_id']) 
            ? $input['notification_id'] 
            : [$input['notification_id']];
        
        // Validate and sanitize IDs
        $notificationIds = array_map('intval', $notificationIds);
        $notificationIds = array_filter($notificationIds, function($id) { return $id > 0; });
        
        if (empty($notificationIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid notification_id']);
            exit();
        }
        
        // Create placeholders for prepared statement
        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        
        // Prepare statement with dynamic placeholders
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND id IN ($placeholders) AND is_read = FALSE";
        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        $types = 'i' . str_repeat('i', count($notificationIds));
        $params = array_merge([$userId], $notificationIds);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            
            echo json_encode([
                'success' => true,
                'marked_count' => $affectedRows,
                'notification_ids' => $notificationIds,
                'message' => "Marked $affectedRows notification(s) as read"
            ]);
            
            error_log("[MarkNotificationRead] Marked notifications " . implode(',', $notificationIds) . " as read for user $userId");
        } else {
            throw new Exception('Failed to mark notifications as read: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("[MarkNotificationRead] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark notifications as read']);
}

$conn->close();
