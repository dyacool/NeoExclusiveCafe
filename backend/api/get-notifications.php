<?php
/**
 * Get Notifications API
 * 
 * Retrieves notifications for the authenticated user
 */

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/session-manager.php';

// Check authentication
if (!SessionManager::isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = SessionManager::getUserId();

// Get query parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

// Validate limit
if ($limit < 1 || $limit > 100) {
    $limit = 50;
}

// Connect to database
require_once '../pages/admin-includes/database.php';

try {
    // Build query
    $sql = "SELECT id, message, type, is_read, created_at 
            FROM notifications 
            WHERE user_id = ?";
    
    if ($unreadOnly) {
        $sql .= " AND is_read = FALSE";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => intval($row['id']),
            'message' => $row['message'],
            'type' => $row['type'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get unread count
    $countStmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $unreadCount = $countResult->fetch_assoc()['unread_count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => intval($unreadCount),
        'total' => count($notifications)
    ]);
    
    $stmt->close();
    $countStmt->close();
    
} catch (Exception $e) {
    error_log("[GetNotifications] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve notifications']);
}

$conn->close();
