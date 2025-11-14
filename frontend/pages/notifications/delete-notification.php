<?php
// Start output buffering to prevent any HTML/errors before JSON
ob_start();

// Log start of request
error_log('Delete notification request started');

require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clean output buffer and set JSON header
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json');

// Log session info
error_log('Session user logged in: ' . (SessionManager::isUserLoggedIn() ? 'yes' : 'no'));

// Check if user is logged in and has proper role
if (!SessionManager::isUserLoggedIn()) {
    error_log('Authorization failed');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get user ID
$user_id = SessionManager::getUserId();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Wrong request method: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
error_log('Request input: ' . print_r($input, true));

if (!isset($input['notification_id']) || !is_numeric($input['notification_id'])) {
    error_log('Invalid notification_id in input');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

$notification_id = (int)$input['notification_id'];
error_log('Processing delete for notification_id: ' . $notification_id . ', user_id: ' . $user_id);

try {
    // Check if database connection exists
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection not available');
    }
    
    // Delete the notification (only if it belongs to the current user)
    $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Database execute failed: ' . mysqli_stmt_error($stmt));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    error_log('Delete query executed, affected_rows: ' . $affected_rows);
    
    if ($affected_rows > 0) {
        error_log('Notification deleted successfully');
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
    } else {
        error_log('No rows affected - notification not found or access denied');
        echo json_encode(['success' => false, 'message' => 'Notification not found or access denied']);
    }
    
} catch (Exception $e) {
    error_log('Delete notification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}
?>