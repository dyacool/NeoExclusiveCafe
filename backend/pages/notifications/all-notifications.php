<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/notifications/notification.php";

$notificationHandler = new NotificationHandler($conn);

// Handle actions
if ($_POST) {
    if (isset($_POST['mark_all_read'])) {
        $notificationHandler->markAllAsRead();
        header("Location: all-notifications.php?success=All notifications marked as read");
        exit();
    }
    
    if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $notificationHandler->delete($ids);
        header("Location: all-notifications.php?success=Selected notifications deleted");
        exit();
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get notifications
$notifications = $notificationHandler->getAll($offset, $per_page);

// Get total count for pagination
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_notifications");
$total_row = mysqli_fetch_assoc($total_result);
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get unread count
$unread_count = $notificationHandler->getUnreadCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="all-notifications.css">
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>

    <div class="notifications-container">
        <div class="page-header">
            <div class="header-left">
                <p class="subtitle">
                    <?php echo $total_notifications; ?> total notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="header-right">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="mark_all_read" class="btn btn-secondary">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </button>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="notificationsForm">
            <div class="notifications-actions">
                <button type="button" id="selectAllBtn" class="btn btn-sm btn-secondary">
                    <i class="far fa-square"></i> Select All
                </button>
                <button type="submit" name="delete_selected" class="btn btn-sm btn-danger" id="deleteBtn" disabled>
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>

            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications yet</h3>
                        <p>You'll see notifications here when new orders or events occur.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                             data-notif-id="<?php echo $notif['notif_id']; ?>">
                            <div class="notification-checkbox">
                                <input type="checkbox" name="selected_ids[]" value="<?php echo $notif['notif_id']; ?>" class="notif-checkbox">
                            </div>
                            
                            <div class="notification-content">
                                <div class="notification-header">
                                    <h3 class="notification-title"><?php echo htmlspecialchars($notif['notif_title']); ?></h3>
                                    <span class="notification-time">
                                        <?php echo $notificationHandler->timeAgo($notif['created_at']); ?>
                                    </span>
                                </div>
                                <p class="notification-message"><?php echo htmlspecialchars($notif['notif_message']); ?></p>
                                
                                <div class="notification-actions">
                                    <?php if ($notif['notif_link']): ?>
                                        <a href="<?php echo htmlspecialchars($notif['notif_link']); ?>" class="view-details-link">
                                            Click here to view details
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!$notif['is_read']): ?>
                                        <button type="button" class="btn btn-xs btn-secondary mark-read-btn" 
                                                data-id="<?php echo $notif['notif_id']; ?>">
                                            <i class="fas fa-check"></i> Mark as Read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="notification-type">
                                <span class="type-badge type-<?php echo $notif['notif_type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $notif['notif_type'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Select all functionality
        const selectAllBtn = document.getElementById('selectAllBtn');
        const checkboxes = document.querySelectorAll('.notif-checkbox');
        const deleteBtn = document.getElementById('deleteBtn');
        let allSelected = false;

        selectAllBtn.addEventListener('click', () => {
            allSelected = !allSelected;
            checkboxes.forEach(cb => cb.checked = allSelected);
            selectAllBtn.innerHTML = allSelected 
                ? '<i class="fas fa-check-square"></i> Deselect All' 
                : '<i class="far fa-square"></i> Select All';
            updateDeleteButton();
        });

        // Update delete button state
        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateDeleteButton);
        });

        function updateDeleteButton() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            deleteBtn.disabled = !anyChecked;
        }

        // Confirm delete
        const form = document.getElementById('notificationsForm');
        form.addEventListener('submit', (e) => {
            if (e.submitter && e.submitter.name === 'delete_selected') {
                const count = Array.from(checkboxes).filter(cb => cb.checked).length;
                if (!confirm(`Are you sure you want to delete ${count} notification(s)?`)) {
                    e.preventDefault();
                }
            }
        });

        // Mark as read functionality
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const notifId = this.getAttribute('data-id');
                const notifItem = this.closest('.notification-item');
                
                try {
                    const response = await fetch('../admin-includes/notifications/notification.php?action=mark_as_read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: [parseInt(notifId)] })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        notifItem.classList.remove('unread');
                        this.remove();
                        
                        // Update unread count
                        const unreadBadge = document.querySelector('.unread-badge');
                        if (unreadBadge) {
                            const currentCount = parseInt(unreadBadge.textContent);
                            const newCount = currentCount - 1;
                            if (newCount > 0) {
                                unreadBadge.textContent = newCount + ' unread';
                            } else {
                                unreadBadge.remove();
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            });
        });
    </script>
</body>
</html>
