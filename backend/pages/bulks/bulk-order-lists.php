<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once __DIR__ . "/../admin-includes/database.php";
}
require_once __DIR__ . "/../../../includes/session-manager.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: ../../login/admin/admin-login.php");
    exit();
}
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Handle status updates (approve/reject from list)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bulk_order_id = (int)$_POST['bulk_order_id'];
    $new_status = $_POST['new_status'] ?? '';
    $is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    // Allow all statuses
    $allowed_statuses = ['pending','approved','payment_received','payment_rejected','ready_for_delivery','ready_for_pickup','cancelled','rejected','completed'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE bulk_orders SET status = ?, admin_updated = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $bulk_order_id);
        $ok = mysqli_stmt_execute($update_stmt);
        $err = mysqli_error($conn);
        mysqli_stmt_close($update_stmt);
        
        // Log the activity
        if ($ok) {
            logAdminActivity($conn, 'UPDATE', "Changed bulk order #$bulk_order_id status to '$new_status'", 'bulk_orders', $bulk_order_id);
            
            // Send emails and create notifications based on status
            require_once __DIR__ . '/../../api/notification.php';
            require_once __DIR__ . '/../../../config/cloudinary-config.php';
            require_once __DIR__ . '/../../includes/mailer.php';
            
            // Get order details for notifications
            $order_info_sql = "SELECT user_id, unique_order_id FROM bulk_orders WHERE id = ?";
            $order_info_stmt = mysqli_prepare($conn, $order_info_sql);
            mysqli_stmt_bind_param($order_info_stmt, "i", $bulk_order_id);
            mysqli_stmt_execute($order_info_stmt);
            $order_info_result = mysqli_stmt_get_result($order_info_stmt);
            $order_info = mysqli_fetch_assoc($order_info_result);
            mysqli_stmt_close($order_info_stmt);
            
            if ($order_info) {
                $notificationHandler = new NotificationHandler($conn);
                
                try {
                    switch ($new_status) {
                        case 'approved':
                            sendBulkOrderApprovalEmail($bulk_order_id, $conn);
                            $notificationHandler->createUserBulkOrderNotification(
                                $order_info['user_id'],
                                $bulk_order_id,
                                'bulk_approved',
                                $order_info['unique_order_id']
                            );
                            break;
                            
                        case 'payment_received':
                            sendBulkOrderPaymentReceivedEmail($bulk_order_id, $conn);
                            $notificationHandler->createUserBulkOrderNotification(
                                $order_info['user_id'],
                                $bulk_order_id,
                                'bulk_payment_received',
                                $order_info['unique_order_id']
                            );
                            break;
                            
                        case 'payment_rejected':
                            sendBulkOrderPaymentRejectedEmail($bulk_order_id, $conn);
                            $notificationHandler->createUserBulkOrderNotification(
                                $order_info['user_id'],
                                $bulk_order_id,
                                'bulk_payment_rejected',
                                $order_info['unique_order_id']
                            );
                            break;
                            
                        case 'cancelled':
                            sendBulkOrderAutoCancelledEmail($bulk_order_id, $conn);
                            $notificationHandler->createUserBulkOrderNotification(
                                $order_info['user_id'],
                                $bulk_order_id,
                                'bulk_cancelled',
                                $order_info['unique_order_id']
                            );
                            break;
                            
                        case 'rejected':
                            sendBulkOrderAutoRejectedEmail($bulk_order_id, $conn);
                            $notificationHandler->createUserBulkOrderNotification(
                                $order_info['user_id'],
                                $bulk_order_id,
                                'bulk_rejected',
                                $order_info['unique_order_id']
                            );
                            break;
                    }
                } catch (Exception $e) {
                    error_log("Failed to send bulk order email/notification: " . $e->getMessage());
                }
            }
        }
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed')]);
            exit();
        }
        if ($ok) { $success_message = "Order status updated successfully!"; } else { $error_message = "Error updating order status: " . $err; }
    } else {
        if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'Invalid status']); exit(); }
        $error_message = "Invalid status selected.";
    }
}


// Ensure status enum includes new values
$desired_statuses = ['pending','approved','payment_received','payment_rejected','ready_for_delivery','ready_for_pickup','cancelled','rejected','completed'];
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM `bulk_orders` LIKE 'status'");
if ($colRes && mysqli_num_rows($colRes) > 0) {
    $colInfo = mysqli_fetch_assoc($colRes);
    if (isset($colInfo['Type']) && stripos($colInfo['Type'], "enum(") === 0) {
        preg_match_all("/'([^']+)'/", $colInfo['Type'], $matches);
        $current = $matches[1] ?? [];
        $missing = array_diff($desired_statuses, $current);
        if (!empty($missing)) {
            $enumList = "'" . implode("','", $desired_statuses) . "'";
            @mysqli_query($conn, "ALTER TABLE `bulk_orders` MODIFY COLUMN `status` enum($enumList) NOT NULL DEFAULT 'pending'");
        }
    }
}

// Add missing columns if they don't exist
$add_columns_queries = [
    "ALTER TABLE `bulk_orders` ADD COLUMN IF NOT EXISTS `unique_order_id` varchar(20) DEFAULT NULL AFTER `id`",
    "ALTER TABLE `bulk_orders` ADD COLUMN IF NOT EXISTS `total_items` int(11) NOT NULL DEFAULT 0 AFTER `total_amount`",
    "ALTER TABLE `bulk_orders` ADD COLUMN IF NOT EXISTS `admin_updated` timestamp NULL DEFAULT NULL AFTER `proof_of_payment`",
    "ALTER TABLE `bulk_orders` ADD COLUMN IF NOT EXISTS `admin_notes` text DEFAULT NULL AFTER `admin_updated`"
];

foreach ($add_columns_queries as $query) {
    mysqli_query($conn, $query);
}

// Check if columns exist and add them if they don't (for older MySQL versions that don't support IF NOT EXISTS)
$check_columns = [
    'unique_order_id' => "ALTER TABLE `bulk_orders` ADD COLUMN `unique_order_id` varchar(20) DEFAULT NULL AFTER `id`",
    'total_items' => "ALTER TABLE `bulk_orders` ADD COLUMN `total_items` int(11) NOT NULL DEFAULT 0 AFTER `total_amount`",
    'admin_updated' => "ALTER TABLE `bulk_orders` ADD COLUMN `admin_updated` timestamp NULL DEFAULT NULL AFTER `proof_of_payment`",
    'admin_notes' => "ALTER TABLE `bulk_orders` ADD COLUMN `admin_notes` text DEFAULT NULL AFTER `admin_updated`"
];

foreach ($check_columns as $column => $alter_query) {
    $check_sql = "SHOW COLUMNS FROM `bulk_orders` LIKE '$column'";
    $check_result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_result) == 0) {
        mysqli_query($conn, $alter_query);
    }
}

// Create bulk_order_items table if it doesn't exist
$create_items_table_query = "
    CREATE TABLE IF NOT EXISTS `bulk_order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bulk_order_id` int(11) NOT NULL,
        `product_id` int(11) DEFAULT NULL,
        `product_name` varchar(255) NOT NULL,
        `product_price` decimal(10,2) NOT NULL,
        `quantity` int(11) NOT NULL,
        `subtotal` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `bulk_order_id` (`bulk_order_id`),
        CONSTRAINT `bulk_order_items_ibfk_1` FOREIGN KEY (`bulk_order_id`) REFERENCES `bulk_orders` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
mysqli_query($conn, $create_items_table_query);

// Auto-update pending orders to rejected if 72 hours have passed since creation
$now = date('Y-m-d H:i:s');
$time_72hrs_ago = date('Y-m-d H:i:s', strtotime('-72 hours'));

// Get orders that will be rejected
$get_rejected_sql = "SELECT id, user_id, unique_order_id, name, email FROM bulk_orders 
                     WHERE status = 'pending' 
                     AND created_at < ?";
$get_rejected_stmt = mysqli_prepare($conn, $get_rejected_sql);
mysqli_stmt_bind_param($get_rejected_stmt, "s", $time_72hrs_ago);
mysqli_stmt_execute($get_rejected_stmt);
$rejected_orders_result = mysqli_stmt_get_result($get_rejected_stmt);
$rejected_orders = [];
while ($row = mysqli_fetch_assoc($rejected_orders_result)) {
    $rejected_orders[] = $row;
}
mysqli_stmt_close($get_rejected_stmt);

// Update them to rejected
$overdue_update_sql = "UPDATE bulk_orders 
                       SET status = 'rejected', admin_updated = NOW() 
                       WHERE status = 'pending' 
                       AND created_at < ?";
$overdue_stmt = mysqli_prepare($conn, $overdue_update_sql);
mysqli_stmt_bind_param($overdue_stmt, "s", $time_72hrs_ago);
mysqli_stmt_execute($overdue_stmt);
mysqli_stmt_close($overdue_stmt);

// Send emails and notifications for rejected orders
if (!empty($rejected_orders)) {
    require_once __DIR__ . "/../admin-includes/mailer.php";
    require_once __DIR__ . "/../admin-includes/notifications/notification.php";
    $notificationHandler = new NotificationHandler($conn);
    
    foreach ($rejected_orders as $order) {
        // Send email
        try {
            sendBulkOrderAutoRejectedEmail($order['id'], $conn);
        } catch (Exception $e) {
            error_log("Failed to send auto-rejection email for order {$order['id']}: " . $e->getMessage());
        }
        
        // Create user notification
        try {
            $notificationHandler->createUserBulkOrderNotification(
                $order['user_id'],
                $order['id'],
                'bulk_rejected',
                $order['unique_order_id']
            );
        } catch (Exception $e) {
            error_log("Failed to create auto-rejection notification for order {$order['id']}: " . $e->getMessage());
        }
    }
}

// Auto-cancel approved orders without payment proof after 7 days
$time_7days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

// Get orders that will be cancelled
$get_cancelled_sql = "SELECT id, user_id, unique_order_id, name, email FROM bulk_orders 
                      WHERE status = 'approved' 
                      AND (proof_of_payment IS NULL OR proof_of_payment = '') 
                      AND updated_at < ?";
$get_cancelled_stmt = mysqli_prepare($conn, $get_cancelled_sql);
mysqli_stmt_bind_param($get_cancelled_stmt, "s", $time_7days_ago);
mysqli_stmt_execute($get_cancelled_stmt);
$cancelled_orders_result = mysqli_stmt_get_result($get_cancelled_stmt);
$cancelled_orders = [];
while ($row = mysqli_fetch_assoc($cancelled_orders_result)) {
    $cancelled_orders[] = $row;
}
mysqli_stmt_close($get_cancelled_stmt);

// Update them to cancelled
$cancel_update_sql = "UPDATE bulk_orders 
                      SET status = 'cancelled', admin_updated = NOW() 
                      WHERE status = 'approved' 
                      AND (proof_of_payment IS NULL OR proof_of_payment = '') 
                      AND updated_at < ?";
$cancel_stmt = mysqli_prepare($conn, $cancel_update_sql);
mysqli_stmt_bind_param($cancel_stmt, "s", $time_7days_ago);
mysqli_stmt_execute($cancel_stmt);
mysqli_stmt_close($cancel_stmt);

// Send emails and notifications for cancelled orders
if (!empty($cancelled_orders)) {
    if (!isset($notificationHandler)) {
        require_once __DIR__ . "/../admin-includes/mailer.php";
        require_once __DIR__ . "/../admin-includes/notifications/notification.php";
        $notificationHandler = new NotificationHandler($conn);
    }
    
    foreach ($cancelled_orders as $order) {
        // Send email
        try {
            sendBulkOrderAutoCancelledEmail($order['id'], $conn);
        } catch (Exception $e) {
            error_log("Failed to send auto-cancellation email for order {$order['id']}: " . $e->getMessage());
        }
        
        // Create user notification
        try {
            $notificationHandler->createUserBulkOrderNotification(
                $order['user_id'],
                $order['id'],
                'bulk_cancelled',
                $order['unique_order_id']
            );
        } catch (Exception $e) {
            error_log("Failed to create auto-cancellation notification for order {$order['id']}: " . $e->getMessage());
        }
    }
}

// Send warning emails for orders that will be cancelled in 2 days (5 days after approval)
$time_5days_ago = date('Y-m-d H:i:s', strtotime('-5 days'));
$time_5days_1min_ago = date('Y-m-d H:i:s', strtotime('-5 days -1 minute'));

// Get orders that need warnings (approved 5 days ago, no payment, warning not sent today)
$get_warning_sql = "SELECT id, user_id, unique_order_id, name, email FROM bulk_orders 
                    WHERE status = 'approved' 
                    AND (proof_of_payment IS NULL OR proof_of_payment = '') 
                    AND updated_at BETWEEN ? AND ?
                    AND id NOT IN (
                        SELECT notif_reference_id FROM user_notifications 
                        WHERE notif_type = 'bulk_warning' 
                        AND DATE(created_at) = CURDATE()
                    )";
$get_warning_stmt = mysqli_prepare($conn, $get_warning_sql);
mysqli_stmt_bind_param($get_warning_stmt, "ss", $time_5days_1min_ago, $time_5days_ago);
mysqli_stmt_execute($get_warning_stmt);
$warning_orders_result = mysqli_stmt_get_result($get_warning_stmt);
$warning_orders = [];
while ($row = mysqli_fetch_assoc($warning_orders_result)) {
    $warning_orders[] = $row;
}
mysqli_stmt_close($get_warning_stmt);

// Send warning emails and notifications
if (!empty($warning_orders)) {
    if (!isset($notificationHandler)) {
        require_once __DIR__ . "/../admin-includes/mailer.php";
        require_once __DIR__ . "/../admin-includes/notifications/notification.php";
        $notificationHandler = new NotificationHandler($conn);
    }
    
    foreach ($warning_orders as $order) {
        // Send email
        try {
            sendBulkOrderCancellationWarningEmail($order['id'], $conn);
        } catch (Exception $e) {
            error_log("Failed to send warning email for order {$order['id']}: " . $e->getMessage());
        }
        
        // Create user notification
        try {
            $notificationHandler->createUserBulkOrderNotification(
                $order['user_id'],
                $order['id'],
                'bulk_warning',
                $order['unique_order_id']
            );
        } catch (Exception $e) {
            error_log("Failed to create warning notification for order {$order['id']}: " . $e->getMessage());
        }
    }
}

// Get today's date and date 5 days from now for due date warnings
$today = date('Y-m-d');
$five_days_later = date('Y-m-d', strtotime('+5 days'));

// Fetch all bulk orders with user information
$sql = "SELECT bo.id, 
               bo.unique_order_id,
               bo.name, bo.contact, bo.email, 
               bo.billing_address, bo.order_type, bo.delivery_address, bo.purpose,
               bo.date_needed, bo.time_needed, bo.note, 
               COALESCE(bo.total_amount, 0) as total_amount, 
               COALESCE(bo.total_items, 0) as total_items,
               bo.discount_total,
               bo.created_at, bo.status, bo.proof_of_payment, bo.admin_updated,
               u.firstname, u.lastname, u.username
        FROM bulk_orders bo
        LEFT JOIN users u ON bo.user_id = u.id
        ORDER BY 
            CASE 
                WHEN LOWER(TRIM(bo.status)) = 'pending' THEN 1
                WHEN LOWER(TRIM(bo.status)) = 'approved' THEN 2
                WHEN LOWER(TRIM(bo.status)) = 'payment_rejected' THEN 3
                WHEN LOWER(TRIM(bo.status)) = 'payment_received' THEN 4
                WHEN LOWER(TRIM(bo.status)) = 'ready_for_delivery' THEN 5
                WHEN LOWER(TRIM(bo.status)) = 'ready_for_pickup' THEN 6
                WHEN LOWER(TRIM(bo.status)) = 'completed' THEN 7
                WHEN LOWER(TRIM(bo.status)) = 'rejected' THEN 8
                WHEN LOWER(TRIM(bo.status)) = 'cancelled' THEN 9
                ELSE 10
            END ASC,
            bo.date_needed ASC,
            bo.time_needed ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    $error_message = "Error fetching bulk orders: " . mysqli_error($conn);
    $result = false;
}

// Calculate totals for each bulk order
$bulk_order_totals = [];
if ($result && mysqli_num_rows($result) > 0) {
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        // Use the stored total_items, total_amount, and discount_total from the main table
        $bulk_order_totals[$row['id']] = [
            'total_items' => $row['total_items'] ?? 0,
            'total_amount' => $row['total_amount'] ?? 0,
            'discount_total' => $row['discount_total'] ?? null
        ];
    }
    mysqli_data_seek($result, 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        .orders-table tbody tr {
            cursor: pointer;
            transition: background 0.15s;
        }
        .orders-table tbody tr:hover, .orders-table tbody tr:focus {
            background: #f3f4f6;
            outline: none;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Orders Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="bulk-order-lists.css">
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <div class="bulk-order-container">
        <div class="page-header">
            <div class="header-content">
                <p>Manage and track all bulk order requests submitted by customers</p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php
        // Calculate statistics
        $total_orders = 0;
        $pending_orders = 0;
        $approved_orders = 0;
        $completed_orders = 0;
        
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)) {
                $total_orders++;
                switch ($row['status']) {
                    case 'pending':
                        $pending_orders++;
                        break;
                    case 'approved':
                        $approved_orders++;
                        break;
                    case 'completed':
                        $completed_orders++;
                        break;
                }
            }
            mysqli_data_seek($result, 0);
        }
        ?>

        <!-- Statistics/Filter Buttons -->
        <div class="filter-section">
            <label class="filter-label">Filter by Status:</label>
            <div class="stats-grid">
                <button class="stat-card filter-btn active" onclick="filterOrders('all', this)" data-filter="all">
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </button>
                <button class="stat-card filter-btn" onclick="filterOrders('pending', this)" data-filter="pending">
                    <div class="stat-number"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">Pending</div>
                </button>
                <button class="stat-card filter-btn" onclick="filterOrders('approved', this)" data-filter="approved">
                    <div class="stat-number"><?php echo $approved_orders; ?></div>
                    <div class="stat-label">Approved</div>
                </button>
                <button class="stat-card filter-btn" onclick="filterOrders('completed', this)" data-filter="completed">
                    <div class="stat-number"><?php echo $completed_orders; ?></div>
                    <div class="stat-label">Completed</div>
                </button>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="orders-table-container">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date Submitted</th>
                            <th>Date Needed</th>
                            <th>Total Items</th>
                            <th>Regular Total</th>
                            <th>Discounted Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $totals = isset($bulk_order_totals[$order['id']]) ? $bulk_order_totals[$order['id']] : ['total_items' => 0, 'total_amount' => 0, 'discount_total' => null];
                            $user_name = $order['firstname'] && $order['lastname'] 
                                ? $order['firstname'] . ' ' . $order['lastname'] 
                                : ($order['username'] ?: 'Guest User');
                            $order_id_display = $order['unique_order_id'] ? $order['unique_order_id'] : str_pad($order['id'], 6, '0', STR_PAD_LEFT);
                            ?>
                            <tr tabindex="0" onkeydown="if(event.key==='Enter'){window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>';}" class="order-row" data-status="<?php echo strtolower($order['status']); ?>">
                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    <div class="order-id">#<?php echo htmlspecialchars($order_id_display); ?></div>
                                </td>
                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                                        <?php if ($order['username']): ?>
                                            <div class="username">@<?php echo htmlspecialchars($order['username']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    <div class="date-info">
                                        <div class="date-main"><?php echo date("M j, Y", strtotime($order['created_at'])); ?></div>
                                        <div class="date-time"><?php echo date("g:i A", strtotime($order['created_at'])); ?></div>
                                    </div>
                                </td>

                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    <div class="date-info">
                                        <div class="date-main"><?php echo date("M j, Y", strtotime($order['date_needed'])); ?></div>
                                        <div class="date-time"><?php echo date("g:i A", strtotime($order['time_needed'])); ?></div>
                                        <?php 
                                            // Only show warnings for active statuses (not rejected, completed, cancelled, payment_rejected)
                                            $active_statuses = ['pending', 'approved', 'payment_received', 'ready_for_delivery', 'ready_for_pickup'];
                                            if (in_array($order['status'], $active_statuses)) {
                                                $due_date = strtotime($order['date_needed']);
                                                $today_time = strtotime($today);
                                                $five_days_later_time = strtotime($five_days_later);
                                                
                                                if ($due_date == $today_time) {
                                                    echo '<span style="display: inline-block; margin-top: 4px; padding: 2px 8px; background: #dc2626; color: white; border-radius: 3px; font-size: 0.75rem; font-weight: 600;">DUE TODAY</span>';
                                                } elseif ($due_date > $today_time && $due_date <= $five_days_later_time) {
                                                    $days_left = ceil(($due_date - $today_time) / 86400);
                                                    echo '<span style="display: inline-block; margin-top: 4px; padding: 2px 8px; background: #f59e0b; color: white; border-radius: 3px; font-size: 0.75rem; font-weight: 600;">Due in ' . $days_left . ' day' . ($days_left > 1 ? 's' : '') . '</span>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </td>
                                
                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    <?php echo number_format($totals['total_items']); ?>
                                </td>
                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    ₱<?php echo number_format($totals['total_amount'], 2); ?>
                                </td>
                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    <?php if ($totals['discount_total'] && $totals['discount_total'] > 0): ?>
                                        <span style="color: #047857; font-weight: 600;">₱<?php echo number_format($totals['discount_total'], 2); ?></span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-style: italic;">No discount</span>
                                    <?php endif; ?>
                                </td>
                                <td onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                    <?php 
                                    $status_labels = [
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'payment_received' => 'Payment Received',
                                        'payment_rejected' => 'Payment Rejected',
                                        'ready_for_delivery' => 'Ready for Delivery',
                                        'ready_for_pickup' => 'Ready for Pickup',
                                        'cancelled' => 'Cancelled',
                                        'rejected' => 'Rejected',
                                        'completed' => 'Completed',
                                    ];
                                    $status_label = isset($status_labels[$order['status']]) ? $status_labels[$order['status']] : ucfirst($order['status']);
                                    ?>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($status_label); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No bulk orders found</h3>
                    <p>There are currently no bulk orders in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterOrders(status, buttonElement) {
            // Remove active class from all filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            buttonElement.classList.add('active');
            
            // Get all order rows
            const orderRows = document.querySelectorAll('.order-row');
            
            // Show/hide rows based on filter
            orderRows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update empty state visibility
            updateEmptyState();
        }
        
        function updateEmptyState() {
            const visibleRows = document.querySelectorAll('.order-row[style=""], .order-row:not([style])');
            const emptyState = document.querySelector('.empty-state');
            const ordersTable = document.querySelector('.orders-table');
            
            if (visibleRows.length === 0 && ordersTable) {
                if (!emptyState) {
                    // Create empty state if it doesn't exist
                    const tableContainer = document.querySelector('.orders-table-container');
                    tableContainer.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No orders found</h3>
                            <p>No orders match the selected filter.</p>
                        </div>
                    `;
                }
            } else if (emptyState && visibleRows.length > 0) {
                // Restore table if orders are visible
                location.reload(); // Simple approach to restore table
            }
        }
    </script>
</body>
</html>
