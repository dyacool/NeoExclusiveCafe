<?php
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../admin-includes/database.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: ../login/admin/admin-login.php");
    exit();
}
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
$per_page = 50;
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

    <div class="main-container">
        <div class="notifications-container">
            <!-- Email-style Header -->
            <div class="notif-email-header">
                <div class="notif-header-left">
                <span class="notif-total-count">
                    <?php echo $total_notifications; ?> Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-unread-badge"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>                </div>
                <div class="notif-header-right">
                    <button onclick="location.reload()" class="notif-icon-btn" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="notif-btn-mark-all" <?php echo $unread_count == 0 ? 'disabled' : ''; ?>>
                            Mark All as Read
                        </button>
                    </form>
                    <button type="button" id="deleteSelectedBtn" class="notif-icon-btn" disabled title="Delete Selected">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Email-style Table -->
            <div class="notif-email-table-container">
                <?php if (empty($notifications)): ?>
                    <div class="notif-empty-state">
                        <div class="notif-empty-icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>
                        <h3>No notifications yet</h3>
                        <p>You'll see notifications here when new orders or events occur.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" id="notificationsForm">
                        <table class="notif-email-table">
                            <thead>
                                <tr>
                                    <th class="notif-th-checkbox">
                                        <input type="checkbox" id="selectAllCheckbox" title="Select All">
                                    </th>
                                    <th class="notif-th-title">Subject</th>
                                    <th class="notif-th-date">Date & Time</th>
                                    <th class="notif-th-type">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $notif): ?>
                                    <tr class="notif-email-row <?php echo !$notif['is_read'] ? 'notif-unread' : ''; ?>" 
                                        data-notif-id="<?php echo $notif['notif_id']; ?>"
                                        data-link="<?php echo htmlspecialchars($notif['notif_link'] ?? ''); ?>">
                                        
                                        <td class="notif-td-checkbox">
                                            <input type="checkbox" name="selected_ids[]" value="<?php echo $notif['notif_id']; ?>" class="notif-checkbox">
                                        </td>
                                        
                                        <td class="notif-td-title">
                                            <div class="notif-title-content">
                                                <?php if (!$notif['is_read']): ?>
                                                    <div class="notif-unread-dot"></div>
                                                <?php endif; ?>
                                                <div class="notif-content-wrapper">
                                                    <span class="notif-subject">
                                                        <?php echo htmlspecialchars($notif['notif_title']); ?>
                                                    </span>
                                                    <div class="notif-message-preview">
                                                        <?php echo htmlspecialchars($notif['notif_message']); ?>
                                                    </div>
                                                    <!-- Inline date for mobile (425px and below) -->
                                                    <div class="notif-inline-date">
                                                        <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="notif-td-date">
                                            <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?>
                                        </td>
                                        
                                        <td class="notif-td-type">
                                            <span class="type-badge type-<?php echo $notif['notif_type']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $notif['notif_type'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
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
        </div>
    </div>

    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
        });

        function setupEventListeners() {
            // Select all checkbox functionality
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.notif-checkbox');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const notificationsForm = document.getElementById('notificationsForm');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateDeleteButton();
                });
            }

            // Update delete button state when individual checkboxes change
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function(e) {
                    e.stopPropagation(); // Prevent row click
                    updateDeleteButton();
                    
                    // Update select all checkbox state
                    if (selectAllCheckbox) {
                        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                        const someChecked = Array.from(checkboxes).some(cb => cb.checked);
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = someChecked && !allChecked;
                    }
                });
            });

            // Delete selected button
            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', function() {
                    const selectedIds = Array.from(checkboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.value);
                    
                    if (selectedIds.length === 0) return;
                    
                    if (confirm(`Are you sure you want to delete ${selectedIds.length} notification(s)? This action cannot be undone.`)) {
                        // Add hidden input for delete_selected action
                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = 'delete_selected';
                        deleteInput.value = '1';
                        notificationsForm.appendChild(deleteInput);
                        
                        notificationsForm.submit();
                    }
                });
            }

            // Row click to view details or mark as read
            document.querySelectorAll('.notif-email-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking checkbox
                    if (e.target.type === 'checkbox' || e.target.closest('.notif-td-checkbox')) {
                        return;
                    }
                    
                    const notifId = this.getAttribute('data-notif-id');
                    const link = this.getAttribute('data-link');
                    const isUnread = this.classList.contains('notif-unread');
                    
                    // Mark as read if unread
                    if (isUnread) {
                        markAsRead(notifId, this);
                    }
                    
                    // Redirect if link exists
                    if (link && link.trim() !== '') {
                        setTimeout(() => {
                            window.location.href = link;
                        }, 300);
                    }
                });
            });
        }

        function updateDeleteButton() {
            const checkboxes = document.querySelectorAll('.notif-checkbox');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            if (deleteSelectedBtn) {
                deleteSelectedBtn.disabled = !anyChecked;
            }
        }

        function markAsRead(notifId, rowElement) {
            fetch('../admin-includes/notifications/notification.php?action=mark_as_read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: [parseInt(notifId)] })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && rowElement) {
                    rowElement.classList.remove('notif-unread');
                    const unreadDot = rowElement.querySelector('.notif-unread-dot');
                    if (unreadDot) {
                        unreadDot.remove();
                    }
                    
                    // Update unread count
                    const unreadBadge = document.querySelector('.notif-unread-badge');
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
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
    </script>
</body>
</html>
