<?php
/**
 * Toggle Auto-Status API
 * 
 * Handles saving and retrieving the auto-status toggle preference
 * for automatic order status management.
 * 
 * Methods:
 * - POST: Save auto-status preference
 * - GET: Retrieve current auto-status preference
 */

session_start();

// Check admin authentication
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit();
}

require_once '../admin-includes/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST request - Save auto-status preference
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['enabled'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required parameter: enabled'
            ]);
            exit();
        }
        
        // Convert to integer (0 or 1)
        $enabled = $input['enabled'] ? 1 : 0;
        
        // Get the most recent record ID to update
        $check_sql = "SELECT id FROM order_status_settings WHERE admin_id IS NULL ORDER BY updated_at DESC LIMIT 1";
        $check_result = mysqli_query($conn, $check_sql);
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            // Update the most recent record
            $row = mysqli_fetch_assoc($check_result);
            $record_id = $row['id'];
            $sql = "UPDATE order_status_settings SET auto_status_enabled = ? WHERE id = ?";
        } else {
            // No record exists, we'll insert one
            $record_id = null;
            $sql = "INSERT INTO order_status_settings (admin_id, auto_status_enabled) VALUES (NULL, ?)";
        }
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
        }
        
        if ($record_id !== null) {
            // Updating existing record
            mysqli_stmt_bind_param($stmt, "ii", $enabled, $record_id);
        } else {
            // Inserting new record
            mysqli_stmt_bind_param($stmt, "i", $enabled);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the activity
            if (file_exists('../admin-includes/activity-logger.php')) {
                require_once '../admin-includes/activity-logger.php';
                $status_text = $enabled ? 'enabled' : 'disabled';
                logAdminActivity($conn, 'UPDATE', "Auto-status $status_text", 'order_status_settings', null);
            }
            
            echo json_encode([
                'success' => true,
                'enabled' => (bool)$enabled,
                'message' => 'Auto-status setting updated successfully'
            ]);
        } else {
            throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET request - Retrieve current auto-status preference
        // Get the most recent record in case there are duplicates
        $sql = "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL ORDER BY updated_at DESC LIMIT 1";
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            throw new Exception('Failed to query database: ' . mysqli_error($conn));
        }
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            echo json_encode([
                'success' => true,
                'enabled' => (bool)$row['auto_status_enabled']
            ]);
        } else {
            // Default to disabled if no setting exists
            echo json_encode([
                'success' => true,
                'enabled' => false
            ]);
        }
        
    } else {
        // Method not allowed
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use GET or POST.'
        ]);
    }
    
} catch (Exception $e) {
    // Handle errors
    error_log('Toggle Auto-Status Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing your request'
    ]);
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>
