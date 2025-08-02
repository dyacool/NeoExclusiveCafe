<?php
// Start output buffering to catch any unexpected output
ob_start();

// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Let database.php know we're in an API call
$suppress_db_debug = true;

// Set content type to JSON before any output
header('Content-Type: application/json');

// Create a function to handle errors
function handleError($message, $code = 400, $debug = []) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set content type
    header('Content-Type: application/json');
    
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'debug' => $debug
    ]);
    exit;
}

try {
    // Include files inside the try block to catch any errors
    require_once "../../php/includes/database.php";
    require_once "../../php/includes/admin-auth.php";

    // Get the JSON data sent by the client
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['order_id'])) {
        handleError('Order ID is required', 400);
    }

    $order_id = intval($data['order_id']);

    // Start transaction
    $conn->begin_transaction();
    
    // Check if order exists and is pending
    $check_sql = "SELECT * FROM orders WHERE order_id = ? AND LOWER(status) LIKE LOWER('pending')";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        handleError("Failed to prepare check statement: " . $conn->error, 500);
    }
    
    $check_stmt->bind_param("i", $order_id);
    if (!$check_stmt->execute()) {
        $conn->rollback();
        handleError("Failed to execute check query: " . $check_stmt->error, 500);
    }
    
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        handleError('Order not found or already processed', 404);
    }
    
    // Check daily order limit
    $today = date('Y-m-d');
    $limit_sql = "SELECT COUNT(*) as accepted_count FROM orders WHERE DATE(order_date) = ? AND LOWER(status) LIKE LOWER('accepted')";
    $limit_stmt = $conn->prepare($limit_sql);
    if (!$limit_stmt) {
        $conn->rollback();
        handleError("Failed to prepare limit statement: " . $conn->error, 500);
    }
    
    $limit_stmt->bind_param("s", $today);
    if (!$limit_stmt->execute()) {
        $conn->rollback();
        handleError("Failed to execute limit query: " . $limit_stmt->error, 500);
    }
    
    $limit_result = $limit_stmt->get_result();
    $row = $limit_result->fetch_assoc();
    $accepted_count = $row['accepted_count'];
    
    // Get the daily limit (default to 10 if not set)
    $daily_limit = 10; // You can adjust this or retrieve from a settings table
    
    if ($accepted_count >= $daily_limit) {
        $conn->rollback();
        handleError('Daily order limit reached. Cannot accept more orders today.', 400);
    }
    
    // Update order status to accepted
    $update_sql = "UPDATE orders SET status = 'Accepted', accepted_at = NOW() WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        $conn->rollback();
        handleError("Failed to prepare update statement: " . $conn->error, 500);
    }
    
    $update_stmt->bind_param("i", $order_id);
    if (!$update_stmt->execute()) {
        $conn->rollback();
        handleError("Failed to execute update query: " . $update_stmt->error, 500);
    }
    
    if ($update_stmt->affected_rows === 0) {
        $conn->rollback();
        handleError('Failed to update order status', 500);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Order accepted successfully']);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    handleError('Database error: ' . $e->getMessage(), 500, ['trace' => $e->getTraceAsString()]);
}
?> 