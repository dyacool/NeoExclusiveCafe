<?php
// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Include necessary files
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Set content type to JSON
header('Content-Type: application/json');

// Function to handle errors
function handleError($message, $debug = []) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'debug' => $debug
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['type']) || !isset($data['limit'])) {
        handleError('Missing required parameters');
    }

    // Validate limit
    $limit = intval($data['limit']);
    if ($limit < 0) {
        handleError('Limit cannot be negative');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        if ($data['type'] === 'daily') {
            // Update or insert default limit
            $query = "INSERT INTO order_limits (id, default_limit) VALUES (1, ?) 
                     ON DUPLICATE KEY UPDATE default_limit = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("ii", $limit, $limit);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update daily limit: " . $stmt->error);
            }
            error_log("Updated daily limit to: $limit");

        } else if ($data['type'] === 'date' && isset($data['date'])) {
            $date = $data['date'];
            
            // Validate that the date is not in the past
            $currentDate = date('Y-m-d');
            if ($date < $currentDate) {
                handleError('Cannot set limits for past dates');
            }
            
            // Update or insert date limit
            $query = "INSERT INTO date_limits (date, limit_value, not_accepting_orders) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     limit_value = VALUES(limit_value), 
                     not_accepting_orders = VALUES(not_accepting_orders)";
            
            $notAcceptingOrders = ($limit == 0);
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("sii", $date, $limit, $notAcceptingOrders);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update date limit: " . $stmt->error);
            }
            
            // Update orderdate_status
            $status = ($limit === 0) ? 'not_accepting' : 'accepting';
            $query = "INSERT INTO orderdate_status (date, status) 
                     VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE status = VALUES(status)";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("ss", $date, $status);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update date status: " . $stmt->error);
            }
            
            error_log("Updated date $date: limit=$limit, status=$status");

        } else {
            throw new Exception('Invalid update type');
        }

        // Commit transaction
        $conn->commit();

        // Clear any previous output
        if (ob_get_length()) ob_clean();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => $data['type'] === 'daily' 
                ? 'Daily limit updated successfully' 
                : ($limit === 0 ? 'Date set to not accept orders' : 'Date limit updated successfully'),
            'debug' => [
                'type' => $data['type'],
                'limit' => $limit,
                'date' => $data['date'] ?? null,
                'status' => $limit === 0 ? 'not_accepting' : 'accepting'
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    handleError($e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
} 