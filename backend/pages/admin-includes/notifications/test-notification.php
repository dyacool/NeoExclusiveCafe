<?php
// Quick test to create a sample notification
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/notification.php';

$handler = new NotificationHandler($conn);

// Create a test notification
$result = $handler->createOrderNotification(
    999, // test order_id
    'order_new',
    'Test Customer',
    'testuser',
    null,
    'Delivery',
    date('Y-m-d', strtotime('+1 day')),
    '14:00:00'
);

if ($result) {
    echo "✅ Test notification created successfully!<br>";
    echo "Unread count: " . $handler->getUnreadCount() . "<br>";
    echo "<a href='../dashboard/dashboard.php'>Go to Dashboard</a>";
} else {
    echo "❌ Failed to create test notification";
}
?>
