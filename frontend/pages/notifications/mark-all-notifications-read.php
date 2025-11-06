<?php
header('Content-Type: application/json');
// Include database.php first - it handles session configuration
require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

// Check if user is logged in
if (!SessionManager::isUserLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = SessionManager::getUserId();

try {
    // Mark all notifications as read for this user
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        echo json_encode([
            'success' => true, 
            'message' => "Marked $affected_rows notifications as read"
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update notifications']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$conn->close();
?>