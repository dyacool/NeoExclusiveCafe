<?php
/**
 * Auto-truncate cart_availtoday when business hours are closed
 * This script can be run by a cron job every minute or at specific times
 * 
 * Usage examples:
 * - Run every minute: every minute
 * - Run every 5 minutes: every 5 minutes  
 * - Run at specific times: 18:00, 19:00, 20:00, 21:00, 22:00, 23:00
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log file for tracking
$log_file = __DIR__ . '/cart-truncation.log';

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

try {
    writeLog("Starting auto-truncate cart check");
    
    require_once __DIR__ . "/../user-includes/database.php";
    
    // Get current time
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    
    writeLog("Current time: $current_time, Date: $current_date");
    
    // Get business hours
    $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $business_hours_result = $conn->query($business_hours_query);
    
    if (!$business_hours_result) {
        writeLog("ERROR: Failed to get business hours: " . $conn->error);
        exit(1);
    }
    
    if ($business_hours_result->num_rows === 0) {
        // No business hours set, use default
        $opening_time = '08:00';
        $closing_time = '17:00';
        writeLog("No business hours set, using defaults: $opening_time - $closing_time");
    } else {
        $business_hours = $business_hours_result->fetch_assoc();
        $opening_time = $business_hours['opening_time'];
        $closing_time = $business_hours['closing_time'];
        writeLog("Business hours: $opening_time - $closing_time");
    }
    
    // Check if current time is after closing time
    // Convert times to minutes for proper comparison (handles midnight crossing)
    $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
    $closing_minutes = (intval(substr($closing_time, 0, 2)) * 60) + intval(substr($closing_time, 3, 2));
    
    // Handle midnight crossing - if current time is much earlier than closing time, we're past midnight
    $is_closed = false;
    if ($current_minutes < $closing_minutes && $current_minutes < 600) { // If current time is before 10 AM
        // We're past midnight, so business is closed
        $is_closed = true;
    } else if ($current_minutes > $closing_minutes) {
        // Normal case - current time is after closing time
        $is_closed = true;
    }
    
    if ($is_closed) {
        writeLog("Business is CLOSED - proceeding with cart truncation");
        
        // Check if cart has items before truncating
        $count_query = "SELECT COUNT(*) as cart_count FROM cart_availtoday";
        $count_result = $conn->query($count_query);
        
        if ($count_result) {
            $count_data = $count_result->fetch_assoc();
            $cart_count = $count_data['cart_count'];
            writeLog("Cart currently has $cart_count items");
            
            if ($cart_count > 0) {
                // Truncate the cart_availtoday table
                $truncate_query = "TRUNCATE TABLE cart_availtoday";
                $truncate_result = $conn->query($truncate_query);
                
                if ($truncate_result) {
                    writeLog("SUCCESS: Cart truncated successfully - $cart_count items removed");
                    
                    // Also clear any related session data or cache if needed
                    // You can add additional cleanup here
                    
                } else {
                    writeLog("ERROR: Failed to truncate cart: " . $conn->error);
                    exit(1);
                }
            } else {
                writeLog("Cart is already empty - no action needed");
            }
        } else {
            writeLog("ERROR: Failed to count cart items: " . $conn->error);
            exit(1);
        }
        
    } else {
        writeLog("Business is still OPEN - no action needed");
    }
    
    writeLog("Auto-truncate cart check completed successfully");
    
} catch (Exception $e) {
    writeLog("ERROR: Exception occurred: " . $e->getMessage());
    exit(1);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// Exit successfully
exit(0);
?>
