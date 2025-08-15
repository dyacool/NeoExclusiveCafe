<?php
require_once __DIR__ . "/../../user-includes/database.php";

echo "=== Cart Truncation Test ===\n";
echo "Current time: " . date('H:i:s') . "\n";

// Get business hours
$business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
$business_hours_result = $conn->query($business_hours_query);

if (!$business_hours_result) {
    echo "ERROR: Failed to get business hours: " . $conn->error . "\n";
    exit;
}

if ($business_hours_result->num_rows === 0) {
    $opening_time = '08:00';
    $closing_time = '17:00';
    echo "No business hours set, using defaults: $opening_time - $closing_time\n";
} else {
    $business_hours = $business_hours_result->fetch_assoc();
    $opening_time = $business_hours['opening_time'];
    $closing_time = $business_hours['closing_time'];
    echo "Business hours: $opening_time - $closing_time\n";
}

// Check current cart count
$count_query = "SELECT COUNT(*) as cart_count FROM cart_availtoday";
$count_result = $conn->query($count_query);

if ($count_result) {
    $count_data = $count_result->fetch_assoc();
    $cart_count = $count_data['cart_count'];
    echo "Cart currently has $cart_count items\n";
    
    if ($cart_count > 0) {
        // Force truncate for testing
        echo "FORCING cart truncation for testing...\n";
        $truncate_query = "TRUNCATE TABLE cart_availtoday";
        $truncate_result = $conn->query($truncate_query);
        
        if ($truncate_result) {
            echo "SUCCESS: Cart truncated successfully - $cart_count items removed\n";
        } else {
            echo "ERROR: Failed to truncate cart: " . $conn->error . "\n";
        }
    } else {
        echo "Cart is already empty\n";
    }
} else {
    echo "ERROR: Failed to count cart items: " . $conn->error . "\n";
}

$conn->close();
?>
