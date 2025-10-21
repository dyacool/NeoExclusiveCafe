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
    <link rel="stylesheet" href="/frontend/user-includes/user-header.css">
    <link rel="stylesheet" href="notifications.css" />
</head>
<body>


    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    <?php include "../../user-includes/bread-crumb/bread-crumb.php"; ?>

    
    <div class="notifications-page-container">

        <!-- Page Header -->
        <div class="notifications-header">
            <div class="header-content">
                <h1 class="page-title">My Notifications</h1>
            </div>
            
            <div class="header-actions">
                <?php 
                $unread_count = 0;
                foreach ($notifications as $notif) {
                    if (!$notif['is_read']) $unread_count++;
                }
                ?>
                <button id="markAllRead" class="btn btn-primary" <?= $unread_count == 0 ? 'disabled' : '' ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M21 12C21 16.97 16.97 21 12 21C7.03 21 3 16.97 3 12C3 7.03 7.03 3 12 3C16.97 3 21 7.03 21 12Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Mark All Read
                </button>
                <button id="refreshNotifications" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 10C21 10 18.995 7.26822 17.3662 5.63824C15.7373 4.00827 13.4864 3 11 3C6.02944 3 2 7.02944 2 12C2 16.9706 6.02944 21 11 21C15.1031 21 18.5649 18.2543 19.6482 14.5M21 10V4M21 10H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Unread Count Card -->
        <div class="unread-count-card">
            <div class="count-content">
                <div class="count-number" id="unreadCount"><?= $unread_count ?></div>
                <div class="count-label">Unread Notifications</div>
            </div>
        </div>
        <!-- Notifications List -->
        <div class="notifications-section">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8A6 6 0 0 0 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3>No Notifications Yet</h3>
                    <p>You're all caught up! Check back later for new updates.</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" 
                             data-notification-id="<?= $notification['id'] ?>"
                             data-notification='<?= htmlspecialchars(json_encode($notification), ENT_QUOTES, 'UTF-8') ?>'>
                            
                            <?php if (!$notification['is_read']): ?>
                                <div class="unread-indicator"></div>
                            <?php endif; ?>
                            
                            <div class="notification-icons">
                                <?php if ($notification['type'] === 'order_confirmation'): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                <?php elseif ($notification['type'] === 'order_ready'): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" fill="currentColor"/>
                                        <path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                <?php else: ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            
                            <div class="notification-content">
                                <div class="notification-header">
                                    <h4 class="notification-title"><?= htmlspecialchars($notification['title']) ?></h4>
                                    <span class="notification-time"><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></span>
                                </div>
                                <p class="notification-message">
                                    <?= htmlspecialchars(substr($notification['message'], 0, 120)) ?>
                                    <?= strlen($notification['message']) > 120 ? '...' : '' ?>
                                </p>
                                
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-primary view-details-btn" 
                                            data-notification-id="<?= $notification['id'] ?>">
                                        View Details
                                    </button>
                                    
                                    <?php if (!$notification['is_read']): ?>
                                        <button class="btn btn-sm btn-secondary mark-read-btn" 
                                                data-notification-id="<?= $notification['id'] ?>">
                                            Mark as Read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Notification Details Modal -->
    <div class="modal-overlay" id="notificationModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title" id="notificationModalTitle">Notification Details</h3>
                <button class="modal-close" onclick="closeNotificationModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            
            <div class="modal-body">
                <h4 id="modalNotificationTitle" class="modal-notification-title"></h4>
                <p id="modalNotificationMessage" class="modal-notification-message"></p>
                <small id="modalNotificationTimestamp" class="modal-notification-time"></small>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>
    
    <script>
        /**
         * NOTIFICATIONS PAGE - JAVASCRIPT (Simplified)
         */
        
        // State management
        let isModalAnimating = false;
        let isModalOpen = false;
        
        // DOM elements
        let modal, modalTitle, modalMessage, modalTimestamp;
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Cache DOM elements
            modal = document.getElementById('notificationModal');
            modalTitle = document.getElementById('modalNotificationTitle');
            modalMessage = document.getElementById('modalNotificationMessage');
            modalTimestamp = document.getElementById('modalNotificationTimestamp');
            
            // Setup event listeners
            setupEventListeners();
            
            console.log('Notifications page initialized');
        });
        
        function setupEventListeners() {
            // Mark all button
            const markAllBtn = document.getElementById('markAllRead');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', handleMarkAllAsRead);
            }
            
            // Refresh button
            const refreshBtn = document.getElementById('refreshNotifications');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => location.reload());
            }
            
            // Event delegation for notification buttons
            document.addEventListener('click', function(e) {
                // View Details button
                const viewBtn = e.target.closest('.view-details-btn');
                if (viewBtn) {
                    e.preventDefault();
                    const notificationId = viewBtn.getAttribute('data-notification-id');
                    console.log('View button clicked, ID:', notificationId);
                    
                    // Find the parent notification item
                    const notificationItem = viewBtn.closest('.notification-item');
                    if (notificationItem) {
                        openNotificationModal(notificationItem, notificationId);
                    }
                    return;
                }
                
                // Mark as Read button
                const markBtn = e.target.closest('.mark-read-btn');
                if (markBtn) {
                    e.preventDefault();
                    const notificationId = markBtn.getAttribute('data-notification-id');
                    markAsRead(notificationId);
                    return;
                }
            });
            
            // Click outside to close modal
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeNotificationModal();
                    }
                });
            }
            
            // ESC key to close modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isModalOpen) {
                    closeNotificationModal();
                }
            });
        }
        
        function openNotificationModal(notificationItem, notificationId) {
            if (isModalAnimating) {
                console.log('Modal is animating, please wait');
                return;
            }
            
            console.log('Opening notification:', notificationId);
            
            try {
                // Parse notification data from the item's data attribute
                const notificationData = JSON.parse(notificationItem.dataset.notification);
                
                console.log('Notification data:', {
                    id: notificationData.id,
                    title: notificationData.title,
                    message: notificationData.message.substring(0, 50) + '...',
                    is_read: notificationData.is_read
                });
                
                // Populate modal
                modalTitle.textContent = notificationData.title || 'Notification';
                
                // Build message with link if available
                let messageHtml = notificationData.message || '';
                if (notificationData.link) {
                    messageHtml += `<br><br><a href="${notificationData.link}" class="notif-link btn btn-primary btn-sm" style="color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; display: inline-block; margin-top: 10px;">View Details</a>`;
                }
                modalMessage.innerHTML = messageHtml;
                
                const timestamp = new Date(notificationData.created_at);
                modalTimestamp.textContent = timestamp.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                
                // Show modal
                isModalAnimating = true;
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                
                setTimeout(() => {
                    isModalAnimating = false;
                    isModalOpen = true;
                    console.log('Modal opened successfully');
                }, 300);
                
                // Mark as read if unread
                if (!notificationData.is_read) {
                    markAsRead(notificationId);
                }
                
            } catch (error) {
                console.error('Error opening notification:', error);
                alert('Error opening notification details');
                isModalAnimating = false;
            }
        }
        
        function closeNotificationModal() {
            if (isModalAnimating) {
                console.log('Modal is animating, please wait');
                return;
            }
            
            isModalAnimating = true;
            modal.classList.remove('show');
            
            setTimeout(() => {
                document.body.style.overflow = '';
                isModalAnimating = false;
                isModalOpen = false;
                console.log('Modal closed');
            }, 300);
        }
        
        function markAsRead(notificationId) {
            const notificationCard = document.querySelector(`[data-notification-id="${notificationId}"]`).closest('.notification-item');
            const button = notificationCard?.querySelector('.mark-read-btn');
            
            if (button) {
                button.textContent = 'Marking...';
                button.disabled = true;
            }
            
            fetch('mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationVisually(notificationId);
                } else {
                    alert('Failed to mark notification as read');
                    if (button) {
                        button.textContent = 'Mark as Read';
                        button.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error marking notification as read');
                if (button) {
                    button.textContent = 'Mark as Read';
                    button.disabled = false;
                }
            });
        }
        
        function handleMarkAllAsRead() {
            if (!confirm('Mark all notifications as read?')) {
                return;
            }
            
            const markAllBtn = document.getElementById('markAllRead');
            const originalText = markAllBtn.textContent;
            markAllBtn.textContent = 'Marking All...';
            markAllBtn.disabled = true;
            
            fetch('mark-all-notifications-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to mark all notifications as read');
                    markAllBtn.textContent = originalText;
                    markAllBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error marking all notifications as read');
                markAllBtn.textContent = originalText;
                markAllBtn.disabled = false;
            });
        }
        
        function updateNotificationVisually(notificationId) {
            const card = document.querySelector(`[data-notification-id="${notificationId}"]`).closest('.notification-item');
            if (!card) return;
            
            // Update card appearance
            card.classList.remove('unread');
            
            // Remove unread elements
            const markReadBtn = card.querySelector('.mark-read-btn');
            const indicator = card.querySelector('.unread-indicator');
            
            if (markReadBtn) markReadBtn.remove();
            if (indicator) indicator.remove();
            
            // Update unread count
            const unreadElement = document.getElementById('unreadCount');
            const currentUnread = parseInt(unreadElement.textContent) || 0;
            const newUnread = Math.max(0, currentUnread - 1);
            unreadElement.textContent = newUnread;
            
            // Update mark all button
            const markAllBtn = document.getElementById('markAllRead');
            if (newUnread === 0 && markAllBtn) {
                markAllBtn.disabled = true;
            }
        }
    </script>
</body>
</html>