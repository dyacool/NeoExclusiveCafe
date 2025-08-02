<?php
// Start output buffering to catch any unexpected output
ob_start();

// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Include necessary files
require_once "../../php/includes/database.php";
require_once "../../php/includes/mailer.php";

// Function to validate token
function validateOrderToken($orderId, $token) {
    $expectedToken = generateOrderToken($orderId);
    return hash_equals($expectedToken, $token);
}

// Function to handle errors with a user-friendly message
function showError($message) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - NeoExclusiveCafe</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; text-align: center; }
            .container { max-width: 600px; margin: 50px auto; padding: 20px; }
            .error-box { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 5px; }
            .back-link { margin-top: 20px; }
            .back-link a { color: #007bff; text-decoration: none; }
            .back-link a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-box">
                <h1>Error</h1>
                <p>' . htmlspecialchars($message) . '</p>
            </div>
            <div class="back-link">
                <a href="/NeoExclusiveCafe/pages/admin/admin-homepage.php">Back to Admin Dashboard</a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Function to show success message
function showSuccess($orderId) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Accepted - NeoExclusiveCafe</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; text-align: center; }
            .container { max-width: 600px; margin: 50px auto; padding: 20px; }
            .success-box { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 5px; }
            .back-link { margin-top: 20px; }
            .back-link a { color: #007bff; text-decoration: none; }
            .back-link a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success-box">
                <h1>Order Accepted</h1>
                <p>Order #' . htmlspecialchars($orderId) . ' has been successfully accepted.</p>
            </div>
            <div class="back-link">
                <a href="/NeoExclusiveCafe/pages/admin/admin-homepage.php">Back to Admin Dashboard</a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

try {
    // Get parameters from the URL
    $orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    
    // Validate order ID
    if ($orderId <= 0) {
        showError('Invalid order ID.');
    }
    
    // Validate token
    if (empty($token) || !validateOrderToken($orderId, $token)) {
        showError('Invalid or expired token. Please try accepting the order from the admin dashboard.');
    }
    
    // Check if order exists and is still pending
    $check_sql = "SELECT * FROM orders WHERE order_id = ? AND LOWER(status) LIKE LOWER('pending')";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        showError('Failed to prepare database query.');
    }
    
    $check_stmt->bind_param("i", $orderId);
    if (!$check_stmt->execute()) {
        showError('Failed to execute database query.');
    }
    
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        showError('Order not found or already processed.');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check daily order limit (simplified for email acceptance)
        $today = date('Y-m-d');
        $limit_sql = "SELECT COUNT(*) as accepted_count FROM orders WHERE DATE(order_date) = ? AND LOWER(status) LIKE LOWER('accepted')";
        $limit_stmt = $conn->prepare($limit_sql);
        
        $limit_stmt->bind_param("s", $today);
        $limit_stmt->execute();
        
        $limit_result = $limit_stmt->get_result();
        $row = $limit_result->fetch_assoc();
        $accepted_count = $row['accepted_count'];
        
        // Get the daily limit (default to 50 for email acceptance)
        $daily_limit = 50;
        
        if ($accepted_count >= $daily_limit) {
            $conn->rollback();
            showError('Daily order limit reached. Cannot accept more orders today.');
        }
        
        // Update order status to accepted
        $update_sql = "UPDATE orders SET status = 'Accepted', accepted_at = NOW() WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $orderId);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update order status.");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Show success message
        showSuccess($orderId);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        showError('An error occurred: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    showError('An unexpected error occurred: ' . $e->getMessage());
}
?> 