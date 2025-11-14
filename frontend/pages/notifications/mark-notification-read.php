<?php
// Start output buffering to prevent any HTML/errors before JSON
ob_start();

// Include database.php first - it handles session configuration
require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

// Clean output buffer and set JSON header
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json');

// Check if user is logged in
if (!SessionManager::isUserLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id']) || !is_numeric($input['notification_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit();
}

$user_id = SessionManager::getUserId();
$notification_id = (int)$input['notification_id'];

try {
    // Update notification as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update notification']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$conn->close();
?>