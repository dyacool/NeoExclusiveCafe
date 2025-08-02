<?php
require_once '../../user-includes/database.php';
require_once '../../php/includes/class-notif.php'; 

session_start();
if (!isset($_SESSION["user_id"])) {
    header('Location: ../../pages/auth/login-signup.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize Notification class
$notification = new Notification($conn); 

// Fetch all notifications
$notifications = $notification->getAllNotifications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="../../css/users/notifications.css" />
</head>
<body>

    <?php include '../../user-includes/user-header.php'; ?>
    
    <div class="neo-profile-container">
        <h2 class="section-title">Notifications</h2>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <p>You have no new notifications.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-card <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                        <div class="notification-info">
                            <div class="notification-label">Date</div>
                            <div class="notification-value"><?= date("F j, Y, g:i a", strtotime($notif['created_at'])) ?></div>
                        </div>
                        
                        <div class="notification-info">
                            <div class="notification-label">Title</div>
                            <div class="notification-value notification-title"><?= htmlspecialchars($notif['title'] ?? $notif['message']) ?></div>
                        </div>
                        
                        <div class="notification-info">
                            <div class="notification-label">Status</div>
                            <div class="notification-value">
                                <span class="status-badge <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                                    <?= $notif['is_read'] ? 'Read' : 'New' ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($notif['description']) && $notif['description'] !== ($notif['title'] ?? $notif['message'])): ?>
                            <div class="notification-info">
                                <div class="notification-label">Description</div>
                                <div class="notification-value"><?= htmlspecialchars($notif['description']) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($notif['link'])): ?>
                            <div class="notification-action">
                                <a href="<?= htmlspecialchars($notif['link']) ?>" class="view-details-btn">View Details</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mark-all-container">
                <button id="markAllRead" class="mark-all-btn">Mark All as Read</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function() {
                fetch("../../php/users/mark-notif.php", { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Reload the page or refresh notifications list
                            location.reload();
                        } else {
                            alert('Failed to mark all notifications as read.');
                        }
                    })
                    .catch(error => {
                        console.error('Error marking all notifications as read:', error);
                        alert('Error marking all notifications as read.');
                    });
            });
        }
    </script>
</body>
</html>