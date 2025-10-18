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
$notifications = $notifications_data; // Use consistent variable name
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - NeoExclusiveCafe</title>
    
    <!-- Exact same structure as profile.php -->
    <link rel="stylesheet" href="../../user-includes/user-header.css">
    <link rel="stylesheet" href="../profile/profile.css">
    <link rel="stylesheet" href="notifications.css" />
    <link rel="stylesheet" href="notifications-extension.css">
</head>
<body>


    <?php include "../../user-includes/user-header.php"; ?>
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
<div class = "wrapper">
    <?php include "../../user-includes/bread-crumb/bread-crumb.php"; ?>

    <!-- Main Container - Using exact same structure as profile.php -->
    <div class="neo-profile-container">
        <!-- Header Card - Same structure as profile.php -->
        <div class="neo-profile-header-card">
            <div class="neo-profile-header-content">
                <div class="neo-profile-info">
                    <h1 class="neo-profile-name">My Notifications</h1>
                </div>
            </div>
            
            <div class="neo-profile-actions">
                <button id="markAllRead" class="neo-btn neo-btn-primary" <?= $unread_count == 0 ? 'disabled' : '' ?>>
                    Mark All Read
                </button>
                <button id="refreshNotifications" class="neo-btn neo-btn-secondary">
                    Refresh
                </button>
            </div>
        </div>

        <!-- Stats Cards - Same structure as profile.php -->
        <div class="neo-profile-stats">
            <div class="neo-stat-card">
                <div class="neo-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor"/>
                        <path d="M12 8V12L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="neo-stat-content">
                    <div class="neo-stat-number" id="totalNotifications"><?= count($notifications) ?></div>
                    <div class="neo-stat-label">Total Notifications</div>
                </div>
            </div>
            
            <div class="neo-stat-card">
                <div class="neo-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 8A6 6 0 0 0 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" fill="currentColor"/>
                        <path d="M13.73 21A2 2 0 0 1 10.27 21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="neo-stat-content">
                    <div class="neo-stat-number unread-highlight" id="unreadCount">
                        <?php 
                        $unread_count = 0;
                        foreach ($notifications as $notif) {
                            if (!$notif['is_read']) $unread_count++;
                        }
                        echo $unread_count;
                        ?>
                    </div>
                    <div class="neo-stat-label">Unread</div>
                </div>
            </div>
        </div>
        <!-- Notifications Section - Exact same structure as profile.php -->
        <div class="neo-profile-section">
            <div class="neo-section-header">
                <h2 class="neo-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 8A6 6 0 0 0 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" fill="currentColor"/>
                        <path d="M13.73 21A2 2 0 0 1 10.27 21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Recent Notifications
                </h2>
            </div>
            
            <div class="neo-section-content">
                <?php if (empty($notifications)): ?>
                    <div class="neo-empty-state">
                        <div class="neo-empty-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 8A6 6 0 0 0 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" fill="currentColor"/>
                                <path d="M13.73 21A2 2 0 0 1 10.27 21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3>No Notifications Yet</h3>
                        <p>You're all caught up! Check back later for new updates.</p>
                    </div>
                <?php else: ?>
                    <div class="neo-notifications-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="neo-notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" 
                                 data-notification-id="<?= $notification['id'] ?>"
                                 data-notification='<?= htmlspecialchars(json_encode($notification), ENT_QUOTES, 'UTF-8') ?>'>
                                <div class="neo-notification-icon">
                                    <?php if ($notification['type'] === 'order_confirmation'): ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    <?php elseif ($notification['type'] === 'order_ready'): ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor"/>
                                            <path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    <?php elseif ($notification['type'] === 'order_cancelled'): ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                            <path d="M15 9L9 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    <?php elseif ($notification['type'] === 'promotion'): ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z" fill="currentColor"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                            <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="neo-notification-content">
                                    <div class="neo-notification-header">
                                        <h4 class="neo-notification-title"><?= htmlspecialchars($notification['title']) ?></h4>
                                        <span class="neo-notification-time"><?= date('M j, Y \a\t g:i A', strtotime($notification['created_at'])) ?></span>
                                    </div>
                                    <p class="neo-notification-message">
                                        <?= htmlspecialchars(substr($notification['message'], 0, 150)) ?>
                                        <?= strlen($notification['message']) > 150 ? '...' : '' ?>
                                    </p>
                                    
                                    <div class="neo-notification-actions">
                                        <button class="neo-btn neo-btn-sm neo-btn-primary view-details-btn" 
                                                onclick="viewNotificationDetails(<?= $notification['id'] ?>)">
                                            View Details
                                        </button>
                                        
                                        <?php if (!$notification['is_read']): ?>
                                            <button class="neo-btn neo-btn-sm mark-read-btn" 
                                                    onclick="markAsRead(<?= $notification['id'] ?>)">
                                                Mark as Read
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!$notification['is_read']): ?>
                                    <div class="neo-notification-indicator"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>    

    
    <!-- Notification Details Modal - Using neo styles -->
    <div class="neo-modal-overlay" id="notificationModal" style="display: none;">
        <div class="neo-modal">
            <div class="neo-modal-header">
                <h3 class="neo-modal-title" id="notificationModalTitle">Notification Details</h3>
                <button class="neo-modal-close" onclick="closeNotificationModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            
            <div class="neo-modal-body">
                <div class="neo-notification-details">
                    <div class="neo-notification-content-modal">
                        <h4 id="modalNotificationTitle" class="neo-notification-title-modal"></h4>
                        <p id="modalNotificationMessage" class="neo-notification-message-modal"></p>
                        <small id="modalNotificationTimestamp" class="neo-text-muted"></small>
                    </div>
                    <div id="orderDetailsContainer" class="neo-order-details-modal" style="display: none;">
                        <hr class="neo-divider">
                        <h6>Order Details</h6>
                        <div id="orderDetails"></div>
                    </div>
                </div>
            </div>
            
            <div class="neo-modal-footer">
                <button class="neo-btn neo-btn-secondary" onclick="closeNotificationModal()">Close</button>
            </div>
        </div>
    </div>
    <script>
        // Global variables for performance
        let currentNotifications = <?= json_encode($notifications) ?>;
        let isLoading = false;
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeNotifications();
        });
        
        function initializeNotifications() {
            // Mark all as read button
            const markAllBtn = document.getElementById('markAllRead');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', handleMarkAllAsRead);
            }
            
            // Refresh button
            const refreshBtn = document.getElementById('refreshNotifications');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', refreshNotifications);
            }
        }
        
        // Fast mark as read function - no reload needed
        function markAsRead(notificationId) {
            if (isLoading) return;
            isLoading = true;
            
            const notificationCard = document.querySelector(`[data-notification-id="${notificationId}"]`);
            const button = notificationCard?.querySelector('.mark-read-btn');
            
            if (button) {
                button.innerHTML = 'Marking...';
                button.disabled = true;
            }
            
            // Optimistic update - update UI immediately
            updateNotificationVisually(notificationId, true);
            
            // Send request to server
            fetch('mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert if failed
                    updateNotificationVisually(notificationId, false);
                    alert('Failed to mark notification as read');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                updateNotificationVisually(notificationId, false);
                alert('Error marking notification as read');
            })
            .finally(() => {
                isLoading = false;
            });
        }
        
        // Fast mark all as read
        function handleMarkAllAsRead() {
            if (isLoading) return;
            if (!confirm('Mark all notifications as read?')) return;
            
            isLoading = true;
            const markAllBtn = document.getElementById('markAllRead');
            markAllBtn.innerHTML = 'Marking All...';
            markAllBtn.disabled = true;
            
            // Optimistic update - mark all as read visually
            const unreadCards = document.querySelectorAll('.neo-notification-item.unread');
            unreadCards.forEach(card => {
                const id = card.dataset.notificationId;
                updateNotificationVisually(id, true);
            });
            
            // Send request
            fetch('mark-all-notifications-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUnreadCount(0);
                    markAllBtn.style.display = 'none';
                } else {
                    location.reload(); // Fallback reload
                }
            })
            .catch(error => {
                console.error('Error:', error);
                location.reload(); // Fallback reload
            })
            .finally(() => {
                isLoading = false;
            });
        }
        
        // View notification details with cached data
        function viewNotificationDetails(notificationId) {
            const notificationCard = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (!notificationCard) return;
            
            // Get notification data from DOM (already embedded)
            const notificationData = JSON.parse(notificationCard.dataset.notification);
            
            // Mark as read if unread
            if (!notificationData.is_read) {
                markAsRead(notificationId);
            }
            
            // Populate modal with cached data (instant load)
            document.getElementById('modalNotificationTitle').textContent = notificationData.title || 'Notification';
            document.getElementById('modalNotificationMessage').textContent = notificationData.message || '';
            document.getElementById('modalNotificationTimestamp').textContent = new Date(notificationData.created_at).toLocaleString();
            
            // Show modal
            document.getElementById('notificationModal').style.display = 'flex';
        }
        
        // Close notification modal
        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }
        
        // Update notification visually without page reload
        function updateNotificationVisually(notificationId, isRead) {
            const card = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (!card) return;
            
            const markReadBtn = card.querySelector('.mark-read-btn');
            const indicator = card.querySelector('.neo-notification-indicator');
            
            if (isRead) {
                card.classList.remove('unread');
                
                if (markReadBtn) {
                    markReadBtn.remove();
                }
                
                if (indicator) {
                    indicator.remove();
                }
                
                // Update unread count
                const currentUnread = parseInt(document.getElementById('unreadCount').textContent);
                updateUnreadCount(Math.max(0, currentUnread - 1));
            } else {
                card.classList.add('unread');
            }
        }
        
        // Update unread count display
        function updateUnreadCount(newCount) {
            const unreadElement = document.getElementById('unreadCount');
            const markAllBtn = document.getElementById('markAllRead');
            
            unreadElement.textContent = newCount;
            
            if (newCount === 0) {
                markAllBtn.disabled = true;
                markAllBtn.style.opacity = '0.5';
            } else {
                markAllBtn.disabled = false;
                markAllBtn.style.opacity = '1';
            }
        }
        
        // Refresh notifications
        function refreshNotifications() {
            location.reload();
        }
        
        // Close modal when clicking outside
        document.getElementById('notificationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNotificationModal();
            }
        });
    </script>
</body>
</html>