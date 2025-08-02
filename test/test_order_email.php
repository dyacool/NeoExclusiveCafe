<?php
require_once "php/includes/mailer.php";
require_once "php/includes/database.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sample order details for testing
$testOrder = [
    'order_id' => 'TEST123',
    'customer_name' => 'Test Customer',
    'user_email' => 'ainepascua4@gmail.com',
    'customer_contact' => '1234567890',
    'customer_address' => '123 Test Street, Test City',
    'delivery_method' => 'Pick-up',
    'pickup_date' => date('Y-m-d'),
    'pickup_time' => '14:00:00',
    'payment_method' => 'Cash',
    'order_date' => date('Y-m-d H:i:s'),
    'cart_items' => [
        [
            'name' => 'Test Product 1',
            'quantity' => 2,
            'price' => 150.00
        ],
        [
            'name' => 'Test Product 2',
            'quantity' => 1,
            'price' => 200.00
        ]
    ],
    'cart_total' => 500.00,
    'shipping_fee' => 0,
    'total_amount' => 500.00
];

echo "Starting email test...\n";

// Test email configuration
echo "Testing email configuration...\n";
$result = testEmailConfiguration();
echo "Email configuration test result: " . ($result ? "Success" : "Failed") . "\n";

// Test order notification
echo "\nTesting order notification email...\n";
$notificationResult = sendOrderNotificationEmail($testOrder);
echo "Order notification test result: " . ($notificationResult ? "Success" : "Failed") . "\n";

// Display error log
echo "\nError Log:\n";
if (function_exists('error_get_last')) {
    print_r(error_get_last());
}
?> 