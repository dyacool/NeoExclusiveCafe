<?php
require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';
require_once 'class-notif.php'; 

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require user login
SessionManager::requireUserLogin('../../login/user/login-signup.php');

// Get user ID
$user_id = SessionManager::getUserId();

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

    
    <div class="main-notif-container">
        <!-- Email-style Header -->
        <div class="notif-email-header">
            <div class="notif-header-left">
                <h1 class="notif-page-title">My Notifications</h1>
            </div>
            <div class="notif-header-right">
                <button id="refreshNotifications" class="notif-icon-btn" title="Refresh">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 10C21 10 18.995 7.26822 17.3662 5.63824C15.7373 4.00827 13.4864 3 11 3C6.02944 3 2 7.02944 2 12C2 16.9706 6.02944 21 11 21C15.1031 21 18.5649 18.2543 19.6482 14.5M21 10V4M21 10H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button id="markAllRead" class="notif-btn-mark-all" <?= count($notifications) == 0 ? 'disabled' : '' ?>>
                    Mark All as Read
                </button>
            </div>
        </div>
        
        <!-- Notification Count -->
        <div class="notif-count-bar">
            <span class="notif-total-count"><?= count($notifications) ?> Notifications</span>
        </div>

        <!-- Email-style Table -->
        <div class="notif-email-table-container">
            <?php if (empty($notifications)): ?>
                <div class="notif-empty-state">
                    <div class="notif-empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8A6 6 0 0 0 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3>No Notifications Yet</h3>
                    <p>You're all caught up! Check back later for new updates.</p>
                </div>
            <?php else: ?>
                <table class="notif-email-table">
                    <thead>
                        <tr>
                            <th class="notif-th-title">Subject</th>
                            <th class="notif-th-date">Date & Time</th>
                            <th class="notif-th-delete">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                            <tr class="notif-email-row <?= !$notification['is_read'] ? 'notif-unread' : '' ?>" 
                                data-notification-id="<?= $notification['id'] ?>"
                                data-notification='<?= htmlspecialchars(json_encode($notification), ENT_QUOTES, 'UTF-8') ?>'>
                                
                                <td class="notif-td-title">
                                    <div class="notif-title-content">
                                        <?php if (!$notification['is_read']): ?>
                                            <div class="notif-unread-dot"></div>
                                        <?php endif; ?>
                                        <div class="notif-content-wrapper">
                                            <span class="notif-subject">
                                                <?php 
                                                // Display title if available, otherwise use type-based fallback
                                                $displayTitle = !empty($notification['title']) ? $notification['title'] : '';
                                                if (empty($displayTitle)) {
                                                    switch ($notification['type']) {
                                                        case 'order_confirmation':
                                                            $displayTitle = "Order Confirmed";
                                                            break;
                                                        case 'order_ready':
                                                            $displayTitle = "Order Ready";
                                                            break;
                                                        case 'system_alert':
                                                            $displayTitle = "Welcome to NeoExclusiveCafe!";
                                                            break;
                                                        default:
                                                            $displayTitle = "Notification";
                                                            break;
                                                    }
                                                }
                                                echo htmlspecialchars($displayTitle);
                                                ?>
                                            </span>
                                            <div class="notif-message-preview">
                                                <?php 
                                                // Display full message (no truncation, let CSS handle wrapping)
                                                $messagePreview = !empty($notification['message']) ? $notification['message'] : '';
                                                echo htmlspecialchars($messagePreview);
                                                ?>
                                            </div>
                                            <!-- Inline date for mobile (425px and below) -->
                                            <div class="notif-inline-date">
                                                <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="notif-td-date">
                                    <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                </td>
                                
                                <td class="notif-td-delete">
                                    <button class="notif-delete-btn" 
                                            data-notification-id="<?= $notification['id'] ?>"
                                            title="Delete notification">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="delete-modal-overlay" id="deleteModal" style="display: none;" role="dialog" aria-labelledby="deleteModalTitle" aria-describedby="deleteModalDesc" aria-modal="true">
        <div class="delete-modal-container">
            <div class="delete-modal-header">
                <h3 class="delete-modal-title" id="deleteModalTitle">Confirm Delete</h3>
                <button class="delete-modal-close" onclick="closeDeleteModal()" aria-label="Close dialog">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            
            <div class="delete-modal-body">
                <div class="delete-modal-icon" aria-hidden="true">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <p class="delete-modal-message" id="deleteModalDesc">Are you sure you want to delete this notification?</p>
                <p class="delete-modal-subtitle">This action cannot be undone.</p>
            </div>
            
            <div class="delete-modal-actions">
                <button class="delete-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-btn-confirm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>
    
    <script>
        /**
         * NOTIFICATIONS PAGE - JAVASCRIPT (Simplified)
         */
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
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
                // Delete button - check this first and stop propagation
                const deleteBtn = e.target.closest('.notif-delete-btn');
                if (deleteBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Delete button clicked');
                    const notificationId = deleteBtn.getAttribute('data-notification-id');
                    console.log('Delete notification ID:', notificationId);
                    deleteNotification(notificationId);
                    return;
                }
                
                // Row click for redirect to order details (only if delete button wasn't clicked)
                const row = e.target.closest('.notif-email-row');
                if (row) {
                    e.preventDefault();
                    const notificationId = row.getAttribute('data-notification-id');
                    console.log('Row clicked, ID:', notificationId);
                    
                    // Parse notification data to get order_id or other relevant info
                    try {
                        const notificationData = JSON.parse(row.dataset.notification);
                        console.log('Notification data:', notificationData);
                        console.log('Available fields:', Object.keys(notificationData));
                        console.log('order_id value:', notificationData.order_id);
                        console.log('link value:', notificationData.link);
                        
                        // Mark as read if unread
                        if (!notificationData.is_read) {
                            markAsRead(notificationId);
                        }
                        
                        // Redirect based on notification type or order_id
                        if (notificationData.order_id && notificationData.order_id !== null) {
                            // Redirect to order details page with order ID
                            console.log('Redirecting to order details with ID:', notificationData.order_id);
                            window.location.href = `/frontend/pages/cart/order-details.php?order_id=${notificationData.order_id}`;
                        } else if (notificationData.link && notificationData.link !== null) {
                            // Use the notification's link if available
                            console.log('Redirecting to notification link:', notificationData.link);
                            window.location.href = notificationData.link;
                        } else {
                            // Fallback: This is a simple notification like welcome message - it was already shown in modal
                            console.log('No order_id or link found - this is a simple notification');
                        }
                    } catch (error) {
                        console.error('Error parsing notification data:', error);
                        console.log('Raw notification dataset:', row.dataset.notification);
                        alert('Error processing notification data. Check console for details.');
                    }
                    
                    return;
                }
            });
            
            // ESC key to close delete modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    // Close delete modal if open
                    const deleteModal = document.getElementById('deleteModal');
                    if (deleteModal && deleteModal.style.display !== 'none') {
                        closeDeleteModal();
                    }
                }
            });
            
            // Click outside to close delete modal
            document.addEventListener('click', function(e) {
                const deleteModal = document.getElementById('deleteModal');
                if (deleteModal && deleteModal.style.display !== 'none') {
                    if (e.target === deleteModal) {
                        closeDeleteModal();
                    }
                }
            });
        }
        
        
        function deleteNotification(notificationId) {
            console.log('Delete notification called with ID:', notificationId);
            
            // Show custom delete modal instead of browser confirm
            showDeleteModal(notificationId);
        }
        
        function showDeleteModal(notificationId) {
            const deleteModal = document.getElementById('deleteModal');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            if (!deleteModal || !confirmBtn) {
                console.error('Delete modal elements not found');
                return;
            }
            
            // Store notification ID for confirmation
            confirmBtn.setAttribute('data-notification-id', notificationId);
            
            // Show modal with animation
            deleteModal.style.display = 'flex';
            setTimeout(() => {
                deleteModal.classList.add('show');
            }, 10);
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            
            // Set up confirm button click handler
            confirmBtn.onclick = function() {
                const notifId = this.getAttribute('data-notification-id');
                proceedWithDelete(notifId);
            };
        }
        
        function closeDeleteModal() {
            const deleteModal = document.getElementById('deleteModal');
            if (!deleteModal) return;
            
            deleteModal.classList.remove('show');
            
            setTimeout(() => {
                deleteModal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
        
        function proceedWithDelete(notificationId) {
            console.log('Proceeding with delete for notification ID:', notificationId);
            
            // Close the modal first
            closeDeleteModal();
            
            const row = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (!row) {
                console.error('Row not found for notification ID:', notificationId);
                return;
            }
            
            console.log('Found row for deletion:', row);
            
            // Add loading state to delete button
            const deleteBtn = row.querySelector('.notif-delete-btn');
            if (deleteBtn) {
                deleteBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
                deleteBtn.disabled = true;
                console.log('Delete button set to loading state');
            }
            
            console.log('Making fetch request to delete-notification.php');
            
            fetch('delete-notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    console.log('Delete successful, removing row');
                    // Remove row with animation
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(100%)';
                    
                    setTimeout(() => {
                        row.remove();
                        updateNotificationCount();
                        
                        // Check if table is now empty
                        const remainingRows = document.querySelectorAll('.notif-email-row');
                        if (remainingRows.length === 0) {
                            location.reload(); // Show empty state
                        }
                    }, 300);
                } else {
                    console.error('Delete failed:', data.message);
                    alert('Failed to delete notification: ' + (data.message || 'Unknown error'));
                    // Reset delete button
                    if (deleteBtn) {
                        deleteBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
                        deleteBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error deleting notification: ' + error.message);
                // Reset delete button
                if (deleteBtn) {
                    deleteBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
                    deleteBtn.disabled = false;
                }
            });
        }
        
        function markAsRead(notificationId) {
            const row = document.querySelector(`[data-notification-id="${notificationId}"]`);
            
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
                    console.error('Failed to mark notification as read:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        function updateNotificationVisually(notificationId) {
            const row = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (!row) return;
            
            // Remove unread styling
            row.classList.remove('notif-unread');
            
            // Remove unread dot
            const unreadDot = row.querySelector('.notif-unread-dot');
            if (unreadDot) {
                unreadDot.remove();
            }
            
            // Update notification count
            updateNotificationCount();
        }
        
        function updateNotificationCount() {
            const totalCountElement = document.querySelector('.notif-total-count');
            const rows = document.querySelectorAll('.notif-email-row');
            
            if (totalCountElement) {
                totalCountElement.textContent = `${rows.length} Notifications`;
            }
            
            // Update mark all button state
            const markAllBtn = document.getElementById('markAllRead');
            const unreadRows = document.querySelectorAll('.notif-email-row.notif-unread');
            
            if (markAllBtn) {
                markAllBtn.disabled = unreadRows.length === 0;
            }
        }
        
        function handleMarkAllAsRead() {
            const unreadRows = document.querySelectorAll('.notif-email-row.notif-unread');
            
            if (unreadRows.length === 0) {
                return;
            }
            
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
                    // Update all unread rows visually
                    unreadRows.forEach(row => {
                        row.classList.remove('notif-unread');
                        const unreadDot = row.querySelector('.notif-unread-dot');
                        if (unreadDot) {
                            unreadDot.remove();
                        }
                    });
                    
                    markAllBtn.textContent = 'All Marked!';
                    setTimeout(() => {
                        markAllBtn.textContent = originalText;
                        markAllBtn.disabled = true; // Keep disabled since all are now read
                    }, 2000);
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
    </script>
</body>
</html>