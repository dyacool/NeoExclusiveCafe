<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log start of request
error_log('Delete notification request started');

require_once '../../../backend/pages/admin-includes/database.php';

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log session info
error_log('Session user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log('Session user_role: ' . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'not set'));

// Check if user is logged in and has proper role
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user") {
    error_log('Authorization failed');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate user_id is numeric and positive
$user_id = (int)$_SESSION['user_id'];
if ($user_id <= 0) {
    error_log('Invalid user_id: ' . $user_id);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit();
}

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