<?php
/**
 * Cron job script to check for due and overdue orders
 * This script should be run periodically (e.g., every hour) to check for orders that are due today or overdue
 */

// Only allow this script to run from command line or localhost for security
if (isset($_SERVER['REQUEST_METHOD']) && 
    $_SERVER['REQUEST_METHOD'] === 'GET' && 
    !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    exit('Access denied');
}

require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../pages/admin-includes/notifications/notification.php';

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/due-orders-check.log');

try {
    $notificationHandler = new NotificationHandler($conn);
    
    // Log the check
    error_log("Running due orders check at " . date('Y-m-d H:i:s'));
    
    // Check for due and overdue orders
    $notificationHandler->checkDueAndOverdueOrders();
    
    // If this is called via web (for testing), return JSON response
    if (isset($_SERVER['REQUEST_METHOD'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Due orders check completed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo "Due orders check completed at " . date('Y-m-d H:i:s') . "\n";
    }
    
} catch (Exception $e) {
    error_log("Error checking due orders: " . $e->getMessage());
    
    if (isset($_SERVER['REQUEST_METHOD'])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>