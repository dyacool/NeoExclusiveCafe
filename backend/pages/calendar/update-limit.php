<?php
// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Include necessary files
require_once "../admin-includes/database.php";
require_once "../../login/admin/admin-auth.php";

// Admin authentication is handled by admin-auth.php include

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
    // Debug: Log the raw input
    $raw_input = file_get_contents('php://input');
    error_log("Raw input: " . $raw_input);
    
    $data = json_decode($raw_input, true);
    
    // Debug: Log the received data
    error_log("Received data: " . print_r($data, true));
    
    if (!isset($data['type']) || !isset($data['limit'])) {
        handleError('Missing required parameters');
    }

    // Validate limit
    $limit = intval($data['limit']);
    if ($limit < 0) {
        handleError('Limit cannot be negative');
    }

    // Start transaction
    error_log("Starting database transaction");
    $conn->begin_transaction();

    try {
        if ($data['type'] === 'daily') {
            // Update the existing row with id=1 (do not insert new rows)
            $query = "UPDATE order_limits SET default_limit = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("i", $limit);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update daily limit: " . $stmt->error);
            }
            
            // Check if the row was updated
            if ($stmt->affected_rows === 0) {
                // Row doesn't exist, create it
                $insert_query = "INSERT INTO order_limits (id, default_limit, created_at, updated_at) VALUES (1, ?, NOW(), NOW())";
                $insert_stmt = $conn->prepare($insert_query);
                if (!$insert_stmt) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }
                $insert_stmt->bind_param("i", $limit);
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to insert daily limit: " . $insert_stmt->error);
                }
                $insert_stmt->close();
                error_log("Inserted new daily limit row with id=1, limit=$limit");
            } else {
                error_log("Updated daily limit to: $limit (affected rows: " . $stmt->affected_rows . ")");
            }

        } else if ($data['type'] === 'date' && isset($data['date'])) {
            $date = $data['date'];
            
            // Validate that the date is not in the past
            $currentDate = date('Y-m-d');
            error_log("Comparing dates: input=$date, current=$currentDate");
            if ($date < $currentDate) {
                handleError("Cannot set limits for past dates. Input date: $date, Current date: $currentDate. Please select today or a future date.");
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