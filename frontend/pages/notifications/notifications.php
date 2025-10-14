<?php
require_once '../../../backend/pages/admin-includes/database.php';
require_once 'class-notif.php'; 

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has proper role
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user") {
    header('Location: ../../login/user/login-signup.php');
    exit();
}

// Validate user_id is numeric and positive
$user_id = (int)$_SESSION['user_id'];
if ($user_id <= 0) {
    header('Location: ../../login/user/login-signup.php');
    exit();
}

// Initialize Notification class
$notification = new Notification($conn); 

// Fetch all notifications
$notifications_data = $notification->getAllNotifications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="/frontend/pages/notifications/notifications.css" />
    <!-- Bootstrap CSS for modal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     
    <!-- Include user navigation -->
     <?php include "../../user-includes/user-header.php"; ?>

</head>
<body>
    
<!-- Navigation -->
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    
    <div class="neo-profile-container">
        <h2 class="section-title">Notifications</h2>
        <button class="cta" onclick="window.location.href='/frontend/pages/home/user-dashboard.php'">
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
        
        <!-- User info display -->
        <div class="notification-summary" id="notificationCount">
            Loading notifications...
        </div>
        
        <?php if (empty($notifications_data)): ?>
            <div class="empty-state">
                <p>You have no notifications.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>" 
                         data-notification-id="<?= $notif['id'] ?>">
                        <div class="notification-content">
                            <div class="notification-header">
                                <h4 class="notification-title"><?= htmlspecialchars($notif['title'] ?? $notif['message']) ?></h4>
                                <span class="notification-time"><?= date("M j, Y g:i a", strtotime($notif['created_at'])) ?></span>
                            </div>
                            <div class="notification-preview">
                                <p class="notification-preview-text"><?= htmlspecialchars(substr($notif['message'], 0, 100)) ?><?= strlen($notif['message']) > 100 ? '...' : '' ?></p>
                            </div>
                            <div class="notification-footer">
                                <span class="status-badge <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                                    <?= $notif['is_read'] ? 'Read' : 'New' ?>
                                </span>
                                <button class="view-details-btn" onclick="handleNotificationClick(<?= $notif['id'] ?>)">
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <div class="notifications-list" id="notificationList">
                <!-- Notifications will be loaded here by notifications.js -->
                <p>Loading...</p>
            </div>
            
            <div class="mark-all-container">
                <button id="markAllRead" class="mark-all-btn">Mark All as Read</button>
            </div>
            <div id="notificationPagination" style="display:flex;gap:8px;align-items:center;margin-top:12px;"></div>
        <?php endif; ?>
    </div>    

    <!-- Global Notification Details Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="notification-details">
                        <div class="notification-image-container" id="notificationImageContainer" style="display: none;">
                            <img id="notificationImage" src="" alt="Notification Image" class="img-fluid rounded mb-3">
                        </div>
                        <div class="notification-content-modal">
                            <h6 id="notificationTitle" class="notification-title-modal"></h6>
                            <p id="notificationMessage" class="notification-message-modal"></p>
                            <small id="notificationTimestamp" class="text-muted"></small>
                        </div>
                        <div id="orderDetailsContainer" class="order-details-modal" style="display: none;">
                            <hr>
                            <h6>Order Details</h6>
                            <div id="orderDetails"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS for modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Include the global notification JavaScript -->
    <script src="/frontend/pages/notifications/notifications.js"></script>
</body>
</html>