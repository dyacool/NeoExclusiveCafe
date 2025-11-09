<?php
/**
 * Create Notification API
 * 
 * Creates a new notification in the database and broadcasts it via realtime system
 */

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/session-manager.php';

// Check authentication (only admins can create notifications for now)
$userData = SessionManager::getUserData();
if (!SessionManager::isUserLoggedIn() || $userData['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['user_id']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: user_id, message']);
    exit();
}

$userId = intval($input['user_id']);
$message = trim($input['message']);
$type = isset($input['type']) ? $input['type'] : 'info';

// Validate type
$validTypes = ['info', 'warning', 'success', 'error'];
if (!in_array($type, $validTypes)) {
    $type = 'info';
}

// Validate message
if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit();
}

// Connect to database
require_once '../pages/admin-includes/database.php';

try {
    // Insert notification into database
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (?, ?, ?, FALSE, NOW())");
    $stmt->bind_param("iss", $userId, $message, $type);
    
    if ($stmt->execute()) {
        $notificationId = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'notification_id' => $notificationId,
            'message' => 'Notification created successfully'
        ]);
        
        error_log("[CreateNotification] Created notification ID $notificationId for user $userId");
    } else {
        throw new Exception('Failed to insert notification: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("[CreateNotification] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create notification']);
}

$conn->close();
