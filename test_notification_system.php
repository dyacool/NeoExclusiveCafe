<?php
// Test file for the notification system
session_start();

// Simulate a logged-in user
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'user';

echo "<h2>Testing Notification System</h2>";

// Test 1: Fetch latest 5 notifications for dropdown
echo "<h3>Test 1: Fetch Latest 5 Notifications (Dropdown)</h3>";
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/fetch-notif.php?dropdown=true';
$response = file_get_contents($url);
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 2: Fetch unread notifications (existing functionality)
echo "<h3>Test 2: Fetch Unread Notifications (Existing)</h3>";
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/fetch-notif.php';
$response = file_get_contents($url);
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 3: Test notification details (if notifications exist)
echo "<h3>Test 3: Fetch Notification Details (ID: 1)</h3>";
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/notif.php?action=details&id=1';
$response = file_get_contents($url);
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 4: Test mark individual notification as read
echo "<h3>Test 4: Mark Individual Notification as Read (ID: 1)</h3>";
$postdata = http_build_query(['notification_id' => 1]);
$opts = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    ]
];
$context = stream_context_create($opts);
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/mark-notif.php';
$response = file_get_contents($url, false, $context);
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 5: Test mark all notifications as read
echo "<h3>Test 5: Mark All Notifications as Read</h3>";
$opts = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => ''
    ]
];
$context = stream_context_create($opts);
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/mark-notif.php';
$response = file_get_contents($url, false, $context);
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<p><strong>Note:</strong> Make sure you have notifications in your database and a user with ID 1 is logged in.</p>";
echo "<p><strong>Usage:</strong> Run this file in your browser to test all notification endpoints.</p>";
?>
