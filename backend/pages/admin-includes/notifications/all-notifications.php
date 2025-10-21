<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: /backend/login/admin/index.php');
    exit;
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/notification.php';

$handler = new NotificationHandler($conn);

// Handle pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get all notifications with pagination
$notifications = $handler->getAll($offset, $limit);
$unread_count = $handler->getUnreadCount();

// Get total count for pagination
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin_notifications");
$total_row = mysqli_fetch_assoc($total_result);
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $limit);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - NeoCafe Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="notifications.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--gray-50);
            color: var(--gray-900);
        }
        
        .notifications-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: var(--gray-600);
            margin: 5px 0 0;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: white;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .back-btn:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }
        
        .bulk-actions {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .bulk-actions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .bulk-actions-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .bulk-actions-controls {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .select-all-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .select-all-container input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        
        .bulk-btn {
            padding: 8px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            background: white;
            color: var(--gray-700);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .bulk-btn:hover {
            background: var(--gray-50);
        }
        
        .bulk-btn.primary {
            background: var(--green-600);
            border-color: var(--green-600);
            color: white;
        }
        
        .bulk-btn.primary:hover {
            background: var(--green-700);
        }
        
        .bulk-btn.danger {
            background: var(--red-600);
            border-color: var(--red-600);
            color: white;
        }
        
        .bulk-btn.danger:hover {
            background: var(--red-700);
        }
        
        .notifications-table {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table-header {
            background: var(--gray-50);
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .notification-row {
            display: grid;
            grid-template-columns: 40px 60px 1fr 120px 40px;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-100);
            align-items: center;
            transition: background-color 0.2s ease;
        }
        
        .notification-row:hover {
            background: var(--gray-50);
        }
        
        .notification-row.unread {
            background: var(--blue-50);
            border-left: 4px solid var(--blue-500);
        }
        
        .notification-checkbox {
            width: 16px;
            height: 16px;
        }
        
        .notification-type-icon {
            font-size: 24px;
            text-align: center;
        }
        
        .notification-content {
            min-width: 0;
        }
        
        .notification-title {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .notification-message {
            color: var(--gray-600);
            font-size: 14px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .notification-time {
            font-size: 12px;
            color: var(--gray-500);
            text-align: center;
            white-space: nowrap;
        }
        
        .notification-actions {
            text-align: center;
        }
        
        .action-link {
            color: var(--green-600);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        
        .action-link:hover {
            color: var(--green-700);
            text-decoration: underline;
        }
        
        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-600);
        }
        
        .no-notifications svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .pagination-btn:hover {
            background: var(--gray-50);
        }
        
        .pagination-btn.current {
            background: var(--green-600);
            border-color: var(--green-600);
            color: white;
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-info {
            color: var(--gray-600);
            font-size: 14px;
            margin: 0 10px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .notifications-page {
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .notification-row {
                grid-template-columns: 30px 40px 1fr 80px;
                gap: 10px;
                padding: 12px 15px;
            }
            
            .notification-actions {
                display: none;
            }
            
            .bulk-actions-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            
            .pagination {
                gap: 5px;
            }
            
            .pagination-btn {
                padding: 6px 10px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .notification-row {
                grid-template-columns: 25px 35px 1fr;
                gap: 8px;
            }
            
            .notification-time {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="notifications-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">All Notifications</h1>
                <p class="page-subtitle"><?php echo $total_notifications; ?> total notifications, <?php echo $unread_count; ?> unread</p>
            </div>
            <div class="header-actions">
                <a href="javascript:history.back()" class="back-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        <?php if ($total_notifications > 0): ?>
        <div class="bulk-actions">
            <div class="bulk-actions-header">
                <h3 class="bulk-actions-title">Bulk Actions</h3>
                <div class="bulk-actions-controls">
                    <div class="select-all-container">
                        <input type="checkbox" id="selectAll">
                        <label for="selectAll">Select All</label>
                    </div>
                    <button type="button" id="markSelectedRead" class="bulk-btn primary">Mark as Read</button>
                    <button type="button" id="deleteSelected" class="bulk-btn danger">Delete</button>
                </div>
            </div>
        </div>

        <div class="notifications-table">
            <div class="table-header">
                Notifications
            </div>
            
            <?php foreach ($notifications as $notif): ?>
            <div class="notification-row <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                <input type="checkbox" class="notification-checkbox" value="<?php echo $notif['notif_id']; ?>">
                
                <div class="notification-type-icon">
                    <?php echo $handler->getIcon($notif['notif_type']); ?>
                </div>
                
                <div class="notification-content">
                    <div class="notification-title"><?php echo htmlspecialchars($notif['notif_title']); ?></div>
                    <div class="notification-message"><?php echo htmlspecialchars($notif['notif_message']); ?></div>
                </div>
                
                <div class="notification-time">
                    <?php echo $handler->timeAgo($notif['created_at']); ?>
                </div>
                
                <div class="notification-actions">
                    <?php if ($notif['notif_link']): ?>
                    <a href="<?php echo htmlspecialchars($notif['notif_link']); ?>" class="action-link">View</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">Previous</a>
            <?php endif; ?>
            
            <?php
            // Show page numbers
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<a href="?page=1" class="pagination-btn">1</a>';
                if ($start_page > 2) {
                    echo '<span class="pagination-btn disabled">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                $class = ($i == $page) ? 'pagination-btn current' : 'pagination-btn';
                echo '<a href="?page=' . $i . '" class="' . $class . '">' . $i . '</a>';
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="pagination-btn disabled">...</span>';
                }
                echo '<a href="?page=' . $total_pages . '" class="pagination-btn">' . $total_pages . '</a>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">Next</a>
            <?php endif; ?>
            
            <div class="pagination-info">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="notifications-table">
            <div class="no-notifications">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3>No notifications yet</h3>
                <p>When you receive notifications, they'll appear here.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="notifications.js"></script>
</body>
</html>