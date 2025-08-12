<?php
/**
 * Auto Truncate Available Today Cart API
 * 
 * This script is called automatically when business hours are closed
 * to clear expired cart_availToday data. It can be called via AJAX
 * or integrated into the business hours checking system.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Manila');

// Set JSON response header
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

// Log file for tracking operations
$logFile = __DIR__ . "/logs/auto-cart-truncation.log";
$logDir = dirname($logFile);

// Create logs directory if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * Log message to file
 */
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Get business hours from database
 */
function getBusinessHours($conn) {
    try {
        // Check if business_hours table exists
        $checkTableQuery = "SHOW TABLES LIKE 'business_hours'";
        $tableExists = $conn->query($checkTableQuery);
        
        if ($tableExists->num_rows == 0) {
            return [
                'opening_time' => '08:00',
                'closing_time' => '17:00'
            ];
        }
        
        // Get business hours
        $query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return [
                'opening_time' => '08:00',
                'closing_time' => '17:00'
            ];
        }
    } catch (Exception $e) {
        logMessage("Error getting business hours: " . $e->getMessage());
        return [
            'opening_time' => '08:00',
            'closing_time' => '17:00'
        ];
    }
}

/**
 * Check if current time is after closing time
 */
function isAfterClosingTime($closingTime) {
    $currentTime = date('H:i');
    $closingTimeObj = DateTime::createFromFormat('H:i', $closingTime);
    $currentTimeObj = DateTime::createFromFormat('H:i', $currentTime);
    
    if (!$closingTimeObj || !$currentTimeObj) {
        return false;
    }
    
    return $currentTimeObj > $closingTimeObj;
}

/**
 * Get cart statistics before truncation
 */
function getCartStatistics($conn) {
    try {
        // Get total cart items
        $totalQuery = "SELECT COUNT(*) as total_items, COUNT(DISTINCT user_id) as unique_users FROM cart_availToday";
        $totalResult = $conn->query($totalQuery);
        $totalStats = $totalResult->fetch_assoc();
        
        // Get cart items by user
        $userQuery = "SELECT user_id, COUNT(*) as item_count, SUM(quantity * price) as total_value 
                     FROM cart_availToday 
                     GROUP BY user_id";
        $userResult = $conn->query($userQuery);
        $userStats = [];
        while ($row = $userResult->fetch_assoc()) {
            $userStats[] = $row;
        }
        
        return [
            'total_items' => $totalStats['total_items'],
            'unique_users' => $totalStats['unique_users'],
            'user_details' => $userStats
        ];
    } catch (Exception $e) {
        logMessage("Error getting cart statistics: " . $e->getMessage());
        return null;
    }
}

/**
 * Truncate expired cart data
 */
function truncateExpiredCart($conn) {
    try {
        // Get cart statistics before truncation
        $stats = getCartStatistics($conn);
        
        if ($stats) {
            logMessage("Cart statistics before truncation:");
            logMessage("- Total items: {$stats['total_items']}");
            logMessage("- Unique users: {$stats['unique_users']}");
        }
        
        // Truncate the cart_availToday table
        $truncateQuery = "TRUNCATE TABLE cart_availToday";
        $result = $conn->query($truncateQuery);
        
        if ($result) {
            logMessage("Successfully truncated cart_availToday table");
            
            // Verify truncation
            $verifyQuery = "SELECT COUNT(*) as remaining_items FROM cart_availToday";
            $verifyResult = $conn->query($verifyQuery);
            $verifyData = $verifyResult->fetch_assoc();
            
            if ($verifyData['remaining_items'] == 0) {
                logMessage("Verification: All cart items have been removed");
            }
            
            return [
                'success' => true,
                'message' => 'Cart truncated successfully',
                'statistics' => $stats
            ];
        } else {
            logMessage("Error truncating cart table: " . $conn->error);
            return [
                'success' => false,
                'message' => 'Error truncating cart table',
                'error' => $conn->error
            ];
        }
    } catch (Exception $e) {
        logMessage("Error during cart truncation: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Exception during cart truncation',
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Main execution function
 */
function main() {
    global $conn;
    
    try {
        // Get business hours
        $businessHours = getBusinessHours($conn);
        $closingTime = $businessHours['closing_time'];
        
        // Check if we're after closing time
        if (isAfterClosingTime($closingTime)) {
            logMessage("Auto-truncation triggered: Current time is after closing time");
            
            // Truncate expired cart
            $result = truncateExpiredCart($conn);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart auto-truncated successfully',
                    'truncated_at' => date('Y-m-d H:i:s'),
                    'business_hours' => $businessHours,
                    'statistics' => $result['statistics']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cart auto-truncation failed',
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No action needed - business hours still open',
                'current_time' => date('H:i'),
                'closing_time' => $closingTime,
                'business_hours' => $businessHours
            ]);
        }
        
    } catch (Exception $e) {
        logMessage("Fatal error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error occurred',
            'error' => $e->getMessage()
        ]);
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
}

// Run the main function
main();
?>
