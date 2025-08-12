<?php
/**
 * Truncate Expired Available Today Cart
 * 
 * This script automatically clears the cart_availToday table when business hours have passed
 * and users haven't placed their orders. It can be run manually or set up as a cron job.
 * 
 * Usage:
 * - Manual: Run this script directly
 * - Cron: Set up to run every hour or at specific times
 * - Example cron: 0 * * * * php /path/to/truncate-expired-availtoday-cart.php
 */

// Enable error reporting for debuggingz
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Manila');

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

// Log file for tracking operations
$logFile = __DIR__ . "/logs/cart-truncation.log";
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
    
    // Also output to console if running from command line
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
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
            logMessage("Business hours table not found, using default hours (08:00-17:00)");
            return [
                'opening_time' => '08:00',
                'closing_time' => '17:00'
            ];
        }
        
        // Get business hours
        $query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $businessHours = $result->fetch_assoc();
            logMessage("Business hours loaded: {$businessHours['opening_time']} - {$businessHours['closing_time']}");
            return $businessHours;
        } else {
            logMessage("No business hours found, using default hours (08:00-17:00)");
            return [
                'opening_time' => '08:00',
                'closing_time' => '17:00'
            ];
        }
    } catch (Exception $e) {
        logMessage("Error getting business hours: " . $e->getMessage());
        // Return default hours on error
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
        logMessage("Error parsing time format");
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
            
            if ($stats['user_details']) {
                logMessage("User cart details:");
                foreach ($stats['user_details'] as $user) {
                    logMessage("  User ID {$user['user_id']}: {$user['item_count']} items, Total: ₱{$user['total_value']}");
                }
            }
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
            } else {
                logMessage("Warning: {$verifyData['remaining_items']} items still remain in cart");
            }
            
            return true;
        } else {
            logMessage("Error truncating cart table: " . $conn->error);
            return false;
        }
    } catch (Exception $e) {
        logMessage("Error during cart truncation: " . $e->getMessage());
        return false;
    }
}

/**
 * Main execution function
 */
function main() {
    global $conn;
    
    logMessage("=== Starting Available Today Cart Truncation Process ===");
    
    try {
        // Get business hours
        $businessHours = getBusinessHours($conn);
        $closingTime = $businessHours['closing_time'];
        
        logMessage("Current time: " . date('H:i'));
        logMessage("Closing time: $closingTime");
        
        // Check if we're after closing time
        if (isAfterClosingTime($closingTime)) {
            logMessage("Current time is after closing time. Proceeding with cart truncation...");
            
            // Truncate expired cart
            $success = truncateExpiredCart($conn);
            
            if ($success) {
                logMessage("Cart truncation completed successfully");
            } else {
                logMessage("Cart truncation failed");
            }
        } else {
            logMessage("Current time is before closing time. No action needed.");
        }
        
    } catch (Exception $e) {
        logMessage("Fatal error: " . $e->getMessage());
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
    
    logMessage("=== Available Today Cart Truncation Process Completed ===" . PHP_EOL);
}

// Run the main function
main();
?>
