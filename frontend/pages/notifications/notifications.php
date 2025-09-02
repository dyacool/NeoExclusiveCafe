<?php
require_once '../../user-includes/database.php';
require_once 'class-notif.php'; 

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header('Location: ../../login/user/login-signup.php');
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
    <link rel="stylesheet" href="notifications.css" />
     
    <!-- Include user navigation -->
     <?php include "../../user-includes/user-header.php"; ?>

</head>
<body>
    
<!-- Navigation -->
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    
    <div class="neo-profile-container">
        <h2 class="section-title">Notifications</h2>
        <button class="cta" onclick="window.location.href='/frontend/pages/blog/blog-dashboard.php'">
            <svg
                id="arrow-horizontal"
                xmlns="http://www.w3.org/2000/svg"
                width="30"
                height="10"
                viewBox="0 0 46 16"
            >
                <path
                id="Path_10"
                data-name="Path 10"
                d="M38,0,39.455,1.455,33.949,6.961H76V9.039H33.949l5.506,5.506L38,16l-8-8Z"
                transform="translate(-25)"
                ></path>
            </svg>
            <span class="hover-underline-animation"> Go Back </span>
        </button>
        
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
                fetch("mark-notif.php", { method: 'POST' })
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