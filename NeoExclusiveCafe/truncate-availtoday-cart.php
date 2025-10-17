<?php

// Detect if running from CLI or web
$isCLI = (php_sapi_name() === 'cli');
$isForceMode = isset($_GET['force']) && $_GET['force'] == '1';

// Set content type for web requests
if (!$isCLI) {
    header('Content-Type: application/json');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', $isCLI ? 1 : 0);
ini_set('log_errors', 1);

// Log file for tracking
$log_file = __DIR__ . '/cart-truncation.log';

// Output buffer for different modes
$output = [
    'success' => false,
    'message' => '',
    'timestamp' => date('Y-m-d H:i:s'),
    'mode' => $isForceMode ? 'force' : 'auto',
    'action' => 'none'
];

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function outputMessage($message, $isCLI) {
    if ($isCLI) {
        echo $message . "\n";
    }
    writeLog($message);
}

try {
    outputMessage("=== AvailToday Cart Truncation Check Started ===", $isCLI);
    
    require_once __DIR__ . "/../backend/pages/admin-includes/database.php";
    
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
        outputMessage("ERROR: $error", $isCLI);
        $output['error'] = $error;
        throw new Exception($error);
    }
    
    outputMessage("Database connection: OK", $isCLI);
    
    // Check if availtoday_cart table exists
    $checkTableQuery = "SHOW TABLES LIKE 'availtoday_cart'";
    $tableExists = $conn->query($checkTableQuery);
    
    if (!$tableExists || $tableExists->num_rows == 0) {
        $error = 'availtoday_cart table does not exist';
        outputMessage("ERROR: $error", $isCLI);
        $output['error'] = $error;
        throw new Exception($error);
    }
    
    // Get current time
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    
    outputMessage("Current time: $current_time, Date: $current_date", $isCLI);
    $output['current_time'] = $current_time;
    $output['current_date'] = $current_date;
    
    // Get business hours
    $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $business_hours_result = $conn->query($business_hours_query);
    
    if (!$business_hours_result) {
        $error = 'Failed to get business hours: ' . $conn->error;
        outputMessage("ERROR: $error", $isCLI);
        $output['error'] = $error;
        throw new Exception($error);
    }
    
    // Set default business hours if none configured
    if ($business_hours_result->num_rows === 0) {
        $opening_time = '08:00:00';
        $closing_time = '17:00:00';
        outputMessage("No business hours configured, using defaults: $opening_time - $closing_time", $isCLI);
    } else {
        $business_hours = $business_hours_result->fetch_assoc();
        $opening_time = $business_hours['opening_time'];
        $closing_time = $business_hours['closing_time'];
        outputMessage("Business hours: $opening_time - $closing_time", $isCLI);
    }
    
    $output['opening_time'] = $opening_time;
    $output['closing_time'] = $closing_time;
    
    // Determine if business is closed
    $is_closed = false;
    
    if ($isForceMode) {
        // Force mode bypasses time check
        $is_closed = true;
        outputMessage("FORCE MODE: Bypassing time check", $isCLI);
    } else {
        // Convert times to minutes for proper comparison
        $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
        $closing_minutes = (intval(substr($closing_time, 0, 2)) * 60) + intval(substr($closing_time, 3, 2));
        
        // Handle midnight crossing - if current time is much earlier than closing time, we're past midnight
        if ($current_minutes < $closing_minutes && $current_minutes < 600) { 
            // If current time is before 10 AM, assume we're past midnight
            $is_closed = true;
            outputMessage("Time check: Past midnight, business is CLOSED", $isCLI);
        } else if ($current_minutes >= $closing_minutes) {
            // Normal case - current time is at or after closing time
            $is_closed = true;
            outputMessage("Time check: Current time >= Closing time, business is CLOSED", $isCLI);
        } else {
            outputMessage("Time check: Business is still OPEN", $isCLI);
        }
        
        $output['debug_info'] = [
            'current_minutes' => $current_minutes,
            'closing_minutes' => $closing_minutes,
            'is_closed' => $is_closed,
            'midnight_crossing' => ($current_minutes < $closing_minutes && $current_minutes < 600)
        ];
    }
    
    if ($is_closed) {
        outputMessage("Business is CLOSED - proceeding with cart truncation", $isCLI);
        
        // Count items in cart before truncating
        $count_query = "SELECT COUNT(*) as cart_count FROM availtoday_cart";
        $count_result = $conn->query($count_query);
        
        if (!$count_result) {
            $error = 'Failed to count cart items: ' . $conn->error;
            outputMessage("ERROR: $error", $isCLI);
            $output['error'] = $error;
            throw new Exception($error);
        }
        
        $count_data = $count_result->fetch_assoc();
        $cart_count = $count_data['cart_count'];
        outputMessage("Cart currently has $cart_count items", $isCLI);
        $output['items_before'] = $cart_count;
        
        if ($cart_count > 0) {
            outputMessage("Executing TRUNCATE TABLE availtoday_cart...", $isCLI);
            
            // Truncate the availtoday_cart table
            $truncate_query = "TRUNCATE TABLE availtoday_cart";
            $truncate_result = $conn->query($truncate_query);
            
            if ($truncate_result) {
                outputMessage("✓ TRUNCATE query executed successfully!", $isCLI);
                outputMessage("SUCCESS: Cart truncated successfully - $cart_count items removed", $isCLI);
                
                // Verify truncation
                $verify_query = "SELECT COUNT(*) as cart_count FROM availtoday_cart";
                $verify_result = $conn->query($verify_query);
                if ($verify_result) {
                    $verify_data = $verify_result->fetch_assoc();
                    $new_count = (int)$verify_data['cart_count']; // Convert to integer
                    outputMessage("Verification: Cart now has $new_count items", $isCLI);
                    
                    if ($new_count === 0) {
                        outputMessage("✓ CONFIRMED: Cart is now empty!", $isCLI);
                        $output['items_after'] = $new_count;
                    } else {
                        outputMessage("⚠ WARNING: Cart still has $new_count items after truncation!", $isCLI);
                        $output['items_after'] = $new_count;
                        $output['warning'] = "Cart not fully emptied";
                    }
                }
                
                $output['success'] = true;
                $output['message'] = 'Cart truncated successfully - business hours closed';
                $output['action'] = 'truncated';
                $output['items_removed'] = $cart_count;
                $output['sql_executed'] = $truncate_query;
                
            } else {
                $error = 'Failed to truncate cart: ' . $conn->error;
                outputMessage("ERROR: $error", $isCLI);
                outputMessage("SQL Query: $truncate_query", $isCLI);
                $output['error'] = $error;
                $output['failed_query'] = $truncate_query;
                throw new Exception($error);
            }
        } else {
            outputMessage("Cart is already empty - no action needed", $isCLI);
            $output['success'] = true;
            $output['message'] = 'Cart already empty - no action needed';
            $output['action'] = 'none';
            $output['items_removed'] = 0;
        }
        
    } else {
        outputMessage("Business is still OPEN - no action needed", $isCLI);
        $output['success'] = true;
        $output['message'] = 'Business still open - no action needed';
        $output['action'] = 'none';
    }
    
    outputMessage("=== Truncation Check Completed Successfully ===", $isCLI);
    
} catch (Exception $e) {
    $error = 'Exception occurred: ' . $e->getMessage();
    outputMessage("ERROR: $error", $isCLI);
    $output['success'] = false;
    $output['error'] = $error;
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// Output response based on mode
if (!$isCLI) {
    // Web request - output JSON
    echo json_encode($output, JSON_PRETTY_PRINT);
} else {
    // CLI - already outputted messages, just exit with proper code
    exit($output['success'] ? 0 : 1);
}
?>

