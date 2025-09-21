<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . "/../backend/pages/admin-includes/database.php";
    
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')
        ]);
        exit;
    }
    
    // Get current time
    $current_time = date('H:i:s');
    
    // Get business hours
    $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $business_hours_result = $conn->query($business_hours_query);
    
    if (!$business_hours_result) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to get business hours: ' . $conn->error
        ]);
        exit;
    }
    
    if ($business_hours_result->num_rows === 0) {
        // No business hours set, use default
        $opening_time = '08:00';
        $closing_time = '17:00';
    } else {
        $business_hours = $business_hours_result->fetch_assoc();
        $opening_time = $business_hours['opening_time'];
        $closing_time = $business_hours['closing_time'];
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
    
    // Debug output
    $debug_info = [
        'current_time' => $current_time,
        'closing_time' => $closing_time,
        'current_minutes' => $current_minutes,
        'closing_minutes' => $closing_minutes,
        'is_closed' => $is_closed,
        'midnight_crossing' => ($current_minutes < $closing_minutes && $current_minutes < 600)
    ];
    
    if ($is_closed) {
        // Business is closed, truncate the cart_availtoday table
        $truncate_query = "TRUNCATE TABLE cart_availtoday";
        $truncate_result = $conn->query($truncate_query);
        
        if ($truncate_result) {
            echo json_encode([
                'success' => true,
                'message' => 'Cart truncated successfully - business hours closed',
                'current_time' => $current_time,
                'closing_time' => $closing_time,
                'action' => 'truncated',
                'debug_info' => $debug_info
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to truncate cart: ' . $conn->error
            ]);
        }
    } else {
        // Business is still open
        echo json_encode([
            'success' => true,
            'message' => 'Business still open - no action needed',
            'current_time' => $current_time,
            'closing_time' => $closing_time,
            'action' => 'none',
            'debug_info' => $debug_info
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
