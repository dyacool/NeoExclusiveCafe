<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";

try {
    // Get business hours
    $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $business_hours_result = $conn->query($business_hours_query);
    
    if (!$business_hours_result || $business_hours_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Business hours not configured'
        ]);
        exit;
    }
    
    $business_hours = $business_hours_result->fetch_assoc();
    $current_time = date('H:i:s');
    
    // Convert times to minutes for comparison
    $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
    $closing_minutes = (intval(substr($business_hours['closing_time'], 0, 2)) * 60) + intval(substr($business_hours['closing_time'], 3, 2));
    $opening_minutes = (intval(substr($business_hours['opening_time'], 0, 2)) * 60) + intval(substr($business_hours['opening_time'], 3, 2));
    
    // Check if business is closed
    $is_closed = false;
    
    // Handle midnight crossing
    if ($closing_minutes > 1200 && $current_minutes < 600) {
        // Past midnight, business is closed
        $is_closed = true;
    } else if ($current_minutes > $closing_minutes) {
        // After closing time
        $is_closed = true;
    }
    
    if ($is_closed) {
        // Check if there are items in the cart before truncating
        $count_query = "SELECT COUNT(*) as cart_count FROM availtoday_cart";
        $count_result = $conn->query($count_query);
        
        if ($count_result) {
            $count_data = $count_result->fetch_assoc();
            $cart_count = $count_data['cart_count'];
            
            if ($cart_count > 0) {
                // Truncate the availtoday_cart table
                $truncate_query = "TRUNCATE TABLE availtoday_cart";
                $truncate_result = $conn->query($truncate_query);
                
                if ($truncate_result) {
                    error_log("Auto-truncate: Cleared $cart_count items from availtoday_cart (business hours closed)");
                    echo json_encode([
                        'success' => true,
                        'action' => 'truncated',
                        'message' => "Cart cleared: $cart_count items removed",
                        'items_removed' => $cart_count
                    ]);
                } else {
                    error_log("Auto-truncate: Failed to truncate availtoday_cart: " . $conn->error);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to truncate cart: ' . $conn->error
                    ]);
                }
            } else {
                // Cart is already empty
                echo json_encode([
                    'success' => true,
                    'action' => 'already_empty',
                    'message' => 'Cart is already empty'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to count cart items: ' . $conn->error
            ]);
        }
    } else {
        // Business is open, no action needed
        echo json_encode([
            'success' => true,
            'action' => 'business_open',
            'message' => 'Business is currently open, no truncation needed'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Auto-truncate error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
