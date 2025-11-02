<?php
/**
 * Test script for auto-update-order-status.php
 * This simulates the cron job execution
 */

session_start();
$_SESSION["is_admin"] = true; // Simulate admin session

echo "=== Testing Auto-Update Order Status ===\n\n";

// Execute the auto-update script
ob_start();
include 'auto-update-order-status.php';
$output = ob_get_clean();

// Parse and display the JSON response
$response = json_decode($output, true);

if ($response) {
    echo "Timestamp: {$response['timestamp']}\n";
    echo "Success: " . ($response['success'] ? 'Yes' : 'No') . "\n";
    echo "Message: {$response['message']}\n\n";
    
    if (isset($response['business_hours'])) {
        echo "Business Hours:\n";
        echo "  Opening: {$response['business_hours']['opening']}\n";
        echo "  Closing: {$response['business_hours']['closing']}\n";
        echo "  Current Time: {$response['current_time']}\n";
        echo "  Is Business Hours: " . ($response['is_business_hours'] ? 'Yes' : 'No') . "\n\n";
    }
    
    echo "Orders Updated: {$response['updated_count']}\n";
    
    if (!empty($response['orders_updated'])) {
        echo "\nUpdated Orders:\n";
        foreach ($response['orders_updated'] as $order) {
            echo "  - Order #{$order['order_id']}: {$order['new_status']} ({$order['reason']})\n";
        }
    }
    
    if (isset($response['skipped_today_updates'])) {
        echo "\nNote: {$response['skipped_today_updates']}\n";
    }
    
    if (!empty($response['errors'])) {
        echo "\nErrors:\n";
        foreach ($response['errors'] as $error) {
            echo "  - $error\n";
        }
    }
} else {
    echo "Failed to parse response:\n";
    echo $output;
}

echo "\n=== Test Complete ===\n";
?>
