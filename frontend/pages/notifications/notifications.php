<?php
require_once '../../../backend/pages/admin-includes/database.php';
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
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <p>You have no new notifications.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-card <?= $notif['is_read'] ? 'read' : 'unread' ?>" 
                         onclick="handleNotificationClick(<?= $notif['id'] ?>)" 
                         style="cursor: pointer;">
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
                        
                        <div class="notification-action">
                            <button class="view-details-btn" onclick="event.stopPropagation(); handleNotificationClick(<?= $notif['id'] ?>)">
                                View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mark-all-container">
                <button id="markAllRead" class="mark-all-btn">Mark All as Read</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notification Details Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="notification-details">
                        <div class="notification-image-container" id="notificationImageContainer" style="display: none;">
                            <img id="notificationImage" src="" alt="Notification Image" class="img-fluid rounded">
                        </div>
                        <div class="notification-content">
                            <h6 id="notificationTitle" class="notification-title"></h6>
                            <p id="notificationMessage" class="notification-message"></p>
                            <small id="notificationTimestamp" class="text-muted"></small>
                        </div>
                        <div id="orderDetailsContainer" class="order-details" style="display: none;">
                            <hr>
                            <h6>Order Details</h6>
                            <div id="orderDetails"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
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

        // Handle notification click - mark as read and show modal
        function handleNotificationClick(notificationId) {
            // Mark notification as read
            fetch('mark-notif.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Fetch notification details and show modal
                    fetchNotificationDetails(notificationId);
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                // Still try to show the modal even if marking as read fails
                fetchNotificationDetails(notificationId);
            });
        }

        // Fetch notification details and show modal
        function fetchNotificationDetails(notificationId) {
            fetch('notif.php?action=details&id=' + notificationId)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotificationModal(data.notification);
                } else {
                    console.error('Error fetching notification details:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching notification details:', error);
            });
        }

        // Show notification modal with details
        function showNotificationModal(notification) {
            // Update modal content
            document.getElementById('notificationTitle').textContent = notification.title;
            document.getElementById('notificationMessage').textContent = notification.message;
            document.getElementById('notificationTimestamp').textContent = 
                'Received: ' + new Date(notification.created_at).toLocaleString();

            // Handle image
            const imageContainer = document.getElementById('notificationImageContainer');
            const image = document.getElementById('notificationImage');
            if (notification.image_url) {
                image.src = notification.image_url;
                imageContainer.style.display = 'block';
            } else {
                imageContainer.style.display = 'none';
            }

            // Handle order details
            const orderDetailsContainer = document.getElementById('orderDetailsContainer');
            const orderDetails = document.getElementById('orderDetails');
            if (notification.type === 'order' && notification.order_details) {
                const order = notification.order_details;
                orderDetails.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order ID:</strong> #${order.id}</p>
                            <p><strong>Customer:</strong> ${order.customer_name}</p>
                            <p><strong>Email:</strong> ${order.customer_email}</p>
                            <p><strong>Phone:</strong> ${order.customer_phone}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <span class="badge bg-primary">${order.status}</span></p>
                            <p><strong>Total Amount:</strong> ₱${parseFloat(order.total_amount).toFixed(2)}</p>
                            <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <p><strong>Items:</strong> ${order.items}</p>
                            <p><strong>Delivery Address:</strong> ${order.delivery_address}</p>
                        </div>
                    </div>
                `;
                orderDetailsContainer.style.display = 'block';
            } else {
                orderDetailsContainer.style.display = 'none';
            }

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
            modal.show();
        }
    </script>
</body>
</html>