<?php
session_start();

// Check if user is admin
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../admin-includes/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save auto-status preference
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['enabled'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing enabled parameter']);
            exit();
        }
        
        $enabled = (int)$input['enabled'];
        
        // Use global setting (admin_id = NULL)
        $sql = "INSERT INTO order_status_settings (admin_id, auto_status_enabled) 
                VALUES (NULL, ?) 
                ON DUPLICATE KEY UPDATE auto_status_enabled = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $enabled, $enabled);
        
        if (mysqli_stmt_execute($stmt)) {
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
        // Get current auto-status preference
        $sql = "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL LIMIT 1";
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
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log('Toggle auto-status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while updating the setting'
    ]);
}

mysqli_close($conn);
?>
