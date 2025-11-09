<?php
/**
 * Test script to manually trigger due orders check
 * Access this file via browser to test the notification system
 */

require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../admin-includes/database.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: ../login/admin/admin-login.php");
    exit();
}
require_once __DIR__ . "/../admin-includes/notifications/notification.php";

// Force run the due orders check
try {
    $notificationHandler = new NotificationHandler($conn);
    
    echo "<h2>Testing Due Orders Notification System</h2>";
    echo "<p>Running due orders check...</p>";
    
    // Run the check
    $notificationHandler->checkDueAndOverdueOrders();
    
    echo "<p style='color: green;'>✓ Due orders check completed successfully!</p>";
    
    // Show recent notifications
    echo "<h3>Recent Notifications:</h3>";
    $recent = $notificationHandler->getRecent(10);
    
    if (empty($recent)) {
        echo "<p>No notifications found.</p>";
    } else {
        echo "<ul>";
        foreach ($recent as $notif) {
            $time_ago = $notificationHandler->timeAgo($notif['created_at']);
            echo "<li><strong>{$notif['notif_type']}</strong>: {$notif['notif_title']} - {$time_ago}</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><a href='/backend/pages/notifications/all-notifications.php'>View All Notifications</a></p>";
    echo "<p><a href='/backend/pages/dashboard/dashboard.php'>Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>