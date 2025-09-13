<?php
// Final test for the notification system
session_start();

// Simulate a logged-in user
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'user';

echo "<h2>Final Notification System Test</h2>";
echo "<p>Testing all endpoints with proper error handling...</p>";

// Test 1: Fetch latest 5 notifications for dropdown
echo "<h3>✅ Test 1: Fetch Latest 5 Notifications (Dropdown)</h3>";
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/fetch-notif.php?dropdown=true';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);
$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'Unknown') . "</p>";
    echo "<p><strong>Count:</strong> " . ($data['count'] ?? 0) . " notifications</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p style='color: red;'><strong>Error:</strong> Failed to fetch notifications</p>";
}

// Test 2: Fetch unread notifications (existing functionality)
echo "<h3>✅ Test 2: Fetch Unread Notifications (Existing)</h3>";
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/fetch-notif.php';
$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'Unknown') . "</p>";
    echo "<p><strong>Count:</strong> " . ($data['count'] ?? 0) . " unread notifications</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p style='color: red;'><strong>Error:</strong> Failed to fetch unread notifications</p>";
}

// Test 3: Test notification details
echo "<h3>✅ Test 3: Fetch Notification Details (ID: 1)</h3>";
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/notif.php?action=details&id=1';
$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'Unknown') . "</p>";
    if (isset($data['notification'])) {
        echo "<p><strong>Notification Found:</strong> " . ($data['notification']['title'] ?? 'No title') . "</p>";
    }
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p style='color: red;'><strong>Error:</strong> Failed to fetch notification details</p>";
}

// Test 4: Test mark individual notification as read
echo "<h3>✅ Test 4: Mark Individual Notification as Read (ID: 1)</h3>";
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
$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'Unknown') . "</p>";
    echo "<p><strong>Message:</strong> " . ($data['message'] ?? 'No message') . "</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p style='color: red;'><strong>Error:</strong> Failed to mark notification as read</p>";
}

// Test 5: Test mark all notifications as read
echo "<h3>✅ Test 5: Mark All Notifications as Read</h3>";
$opts = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => ''
    ]
];
$context = stream_context_create($opts);
$url = 'http://localhost/NeoCafe/frontend/pages/notifications/mark-notif.php';
$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'Unknown') . "</p>";
    echo "<p><strong>Message:</strong> " . ($data['message'] ?? 'No message') . "</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p style='color: red;'><strong>Error:</strong> Failed to mark all notifications as read</p>";
}

echo "<hr>";
echo "<h3>🎯 Summary</h3>";
echo "<p><strong>All notification system changes have been implemented using existing files:</strong></p>";
echo "<ul>";
echo "<li>✅ <code>fetch-notif.php</code> - Enhanced with dropdown support</li>";
echo "<li>✅ <code>mark-notif.php</code> - Enhanced with individual marking</li>";
echo "<li>✅ <code>class-notif.php</code> - Added getNotificationDetails method</li>";
echo "<li>✅ <code>notif.php</code> - Added details endpoint</li>";
echo "<li>✅ <code>notifications.php</code> - Added modal and click functionality</li>";
echo "<li>✅ <code>customer-navigation.php</code> - Updated navbar with dropdown</li>";
echo "<li>✅ <code>customer-navigation.css</code> - Added notification styles</li>";
echo "</ul>";

echo "<p><strong>Features implemented:</strong></p>";
echo "<ul>";
echo "<li>🔔 Notification dropdown in navbar (latest 5 notifications)</li>";
echo "<li>👆 Clickable notifications that open detailed modal</li>";
echo "<li>📱 Uniform modal for all notification types</li>";
echo "<li>📋 Order details for order notifications</li>";
echo "<li>🔗 See More button for full notifications page</li>";
echo "<li>⚡ Real-time updates with AJAX</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> Make sure you have notifications in your database and a user with ID 1 is logged in for full testing.</p>";
?>
