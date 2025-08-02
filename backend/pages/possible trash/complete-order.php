<?php
// Prevent any output before headers
ob_start();

// Disable error reporting for output
error_reporting(0);
ini_set('display_errors', 0);

// Include necessary files
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/admin-auth.php";

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '', $data = null) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Prepare response
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data) $response['data'] = $data;
    
    // Send response
    echo json_encode($response);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['order_id'])) {
        sendJsonResponse(false, 'Order ID is required');
    }
    
    $order_id = intval($input['order_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, add completion_date column if it doesn't exist
        $alter_table_sql = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS completion_date DATETIME DEFAULT NULL";
        if (!$conn->query($alter_table_sql)) {
            throw new Exception("Failed to add completion_date column: " . $conn->error);
        }
        
        // Update order status to Completed
        $update_sql = "UPDATE orders SET 
            status = CASE 
                WHEN delivery_method = 'Pick-up' THEN 'Picked-up'
                WHEN delivery_method = 'Delivery' THEN 'Delivered'
                ELSE status 
            END,
            completion_date = NOW() 
            WHERE order_id = ? AND status = 'Pending'";
        $stmt = $conn->prepare($update_sql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update order: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Order not found or already completed");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Send success response
        sendJsonResponse(true, 'Order marked as completed successfully');
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Send error response
    sendJsonResponse(false, $e->getMessage());
} 