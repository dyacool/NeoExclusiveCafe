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
    $allowed_statuses = ['pending','approved','payment_received','payment_rejected','ready_for_delivery','cancelled','rejected','completed'];
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

// Create bulk_orders table if it doesn't exist
// Ensure table exists with extended statuses
$create_table_query = "
    CREATE TABLE IF NOT EXISTS `bulk_orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `unique_order_id` varchar(20) UNIQUE,
        `user_id` int(11) DEFAULT NULL,
        `name` varchar(255) NOT NULL,
        `contact` varchar(20) NOT NULL,
        `email` varchar(255) NOT NULL,
        `billing_address` text NOT NULL,
        `order_type` enum('delivery','pickup') NOT NULL,
        `delivery_address` text DEFAULT NULL,
        `purpose` text NOT NULL,
        `date_needed` date NOT NULL,
        `time_needed` time NOT NULL,
        `note` text DEFAULT NULL,
        `total_amount` decimal(10,2) NOT NULL,
        `total_items` int(11) NOT NULL DEFAULT 0,
        `status` enum('pending','approved','payment_received','payment_rejected','ready_for_delivery','cancelled','rejected','completed') NOT NULL DEFAULT 'pending',
        `proof_of_payment` varchar(500) DEFAULT NULL,
        `admin_updated` timestamp NULL DEFAULT NULL,
        `admin_notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `user_id` (`user_id`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
mysqli_query($conn, $create_table_query);

// Ensure status enum includes new values
$desired_statuses = ['pending','approved','payment_received','payment_rejected','ready_for_delivery','cancelled','rejected','completed'];
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
                WHEN LOWER(TRIM(bo.status)) IN ('completed', 'cancelled', 'rejected', 'payment_rejected') THEN 2
                ELSE 1
            END ASC,
            CASE 
                WHEN LOWER(TRIM(bo.status)) IN ('completed', 'cancelled', 'rejected', 'payment_rejected') THEN bo.created_at
                ELSE COALESCE(
                    NULLIF(bo.date_needed, '0000-00-00'),
                    bo.created_at
                )
            END ASC,
            CASE 
                WHEN LOWER(TRIM(bo.status)) NOT IN ('completed', 'cancelled', 'rejected', 'payment_rejected') THEN 
                    COALESCE(
                        NULLIF(bo.time_needed, '00:00:00')
                    )
                ELSE NULL
            END ASC,
            bo.created_at ASC";

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
                            <tr tabindex="0" onkeydown="if(event.key==='Enter'){showBulkOrderPreview(<?php echo $order['id']; ?>);}" class="order-row" data-status="<?php echo strtolower($order['status']); ?>">
                                <td onclick="showBulkOrderPreview(<?php echo $order['id']; ?>)" style="cursor:pointer;">
                                    <div class="order-id">#<?php echo htmlspecialchars($order_id_display); ?></div>
                                </td>
                                <td onclick="showBulkOrderPreview(<?php echo $order['id']; ?>)" style="cursor:pointer;">
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                                        <?php if ($order['username']): ?>
                                            <div class="username">@<?php echo htmlspecialchars($order['username']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td onclick="showBulkOrderPreview(<?php echo $order['id']; ?>)" style="cursor:pointer;">
                                    <div class="date-info">
                                        <div class="date-main"><?php echo date("M j, Y", strtotime($order['created_at'])); ?></div>
                                        <div class="date-time"><?php echo date("g:i A", strtotime($order['created_at'])); ?></div>
                                    </div>
                                </td>

                                <td onclick="showBulkOrderPreview(<?php echo $order['id']; ?>)" style="cursor:pointer;">
                                    <div class="date-info">
                                        <div class="date-main"><?php echo date("M j, Y", strtotime($order['date_needed'])); ?></div>
                                        <div class="date-time"><?php echo date("g:i A", strtotime($order['time_needed'])); ?></div>
                                    </div>
                                </td>
                                
                                <td onclick="showBulkOrderPreview(<?php echo $order['id']; ?>)" style="cursor:pointer;">
                                    <?php echo number_format($totals['total_items']); ?>
                                </td>
                                <td onclick="showBulkOrderPreview(<?php echo $order['id']; ?>)" style="cursor:pointer;">
                                    ₱<?php echo number_format($totals['total_amount'], 2); ?>
                                </td>
                                <td onclick="showBulkOrderPreview(<?php echo $order['id']; ?>)" style="cursor:pointer;">
                                    <?php if ($totals['discount_total'] && $totals['discount_total'] > 0): ?>
                                        <span style="color: #047857; font-weight: 600;">₱<?php echo number_format($totals['discount_total'], 2); ?></span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-style: italic;">No discount</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select class="status-select-list status-badge status-<?php echo strtolower($order['status']); ?>" data-order-id="<?php echo (int)$order['id']; ?>" style="margin-left:8px;">
                                        <?php $statuses = [
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'payment_received' => 'Payment Received',
                                            'payment_rejected' => 'Payment Rejected',
                                            'ready_for_delivery' => 'Ready for Delivery',
                                            'cancelled' => 'Cancelled',
                                            'rejected' => 'Rejected',
                                            'completed' => 'Completed',
                                        ];
                                        foreach ($statuses as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php echo ($order['status'] === $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="saved-indicator" style="display:none; color:#16a34a; margin-left:6px;"><i class="fas fa-check"></i> Saved</span>
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

    <!-- Bulk Order Preview Modal -->
    <div id="bulkOrderPreviewModal" class="preview-modal" style="display: none;">
        <div class="preview-modal-overlay" onclick="closePreviewModal()"></div>
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <h2>Bulk Order Preview</h2>
                <button class="preview-modal-close" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="preview-modal-body" id="previewModalBody">
                <div class="preview-loader">
                    <div class="spinner"></div>
                    <p>Loading order details...</p>
                </div>
            </div>
            <div class="preview-modal-footer">
                <button class="btn-secondary" onclick="closePreviewModal()">Close</button>
                <button class="btn-primary" id="viewFullDetailsBtn" onclick="viewFullDetails()">View Full Details</button>
            </div>
        </div>
    </div>

    <style>
        .preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .preview-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }
        
        .preview-modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        
        .preview-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .preview-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #1f2937;
        }
        
        .preview-modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .preview-modal-close:hover {
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .preview-modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }
        
        .preview-loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .preview-loader .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #8B4513;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .preview-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .preview-modal-footer button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-primary {
            background: #8B4513;
            color: white;
        }
        
        .btn-primary:hover {
            background: #6d3410;
        }
        
        .preview-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .preview-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .preview-info-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .preview-info-value {
            font-size: 1rem;
            color: #1f2937;
            font-weight: 500;
        }
        
        .preview-section {
            margin-bottom: 24px;
        }
        
        .preview-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .preview-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .preview-items-table th {
            background: #f9fafb;
            padding: 10px 12px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .preview-items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        
        .preview-status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-payment_received {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
    </style>

    <script>
        let currentPreviewOrderId = null;
        
        // Show bulk order preview modal
        function showBulkOrderPreview(orderId) {
            currentPreviewOrderId = orderId;
            const modal = document.getElementById('bulkOrderPreviewModal');
            const body = document.getElementById('previewModalBody');
            
            // Show modal with loader
            body.innerHTML = `
                <div class="preview-loader">
                    <div class="spinner"></div>
                    <p>Loading order details...</p>
                </div>
            `;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Fetch order details
            fetch(`get-bulk-order-preview.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayPreviewData(data.order);
                    } else {
                        body.innerHTML = `
                            <div class="preview-error">
                                <p style="color: #dc2626; text-align: center;">
                                    <i class="fas fa-exclamation-circle"></i> Failed to load order details
                                </p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    body.innerHTML = `
                        <div class="preview-error">
                            <p style="color: #dc2626; text-align: center;">
                                <i class="fas fa-exclamation-circle"></i> Error loading order details
                            </p>
                        </div>
                    `;
                });
        }
        
        // Display preview data
        function displayPreviewData(order) {
            const body = document.getElementById('previewModalBody');
            const orderIdDisplay = order.unique_order_id || String(order.id).padStart(6, '0');
            const userName = order.firstname && order.lastname 
                ? `${order.firstname} ${order.lastname}`
                : (order.username || 'Guest User');
            
            let html = `
                <div class="preview-section">
                    <div class="preview-info-grid">
                        <div class="preview-info-item">
                            <span class="preview-info-label">Order ID</span>
                            <span class="preview-info-value">#${orderIdDisplay}</span>
                        </div>
                        <div class="preview-info-item">
                            <span class="preview-info-label">Customer</span>
                            <span class="preview-info-value">${userName}</span>
                        </div>
                        <div class="preview-info-item">
                            <span class="preview-info-label">Contact</span>
                            <span class="preview-info-value">${order.contact}</span>
                        </div>
                        <div class="preview-info-item">
                            <span class="preview-info-label">Email</span>
                            <span class="preview-info-value">${order.email}</span>
                        </div>
                        <div class="preview-info-item">
                            <span class="preview-info-label">Date Needed</span>
                            <span class="preview-info-value">${new Date(order.date_needed + ' ' + order.time_needed).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })}</span>
                        </div>
                        <div class="preview-info-item">
                            <span class="preview-info-label">Order Type</span>
                            <span class="preview-info-value">${order.order_type.charAt(0).toUpperCase() + order.order_type.slice(1)}</span>
                        </div>
                        <div class="preview-info-item">
                            <span class="preview-info-label">Status</span>
                            <span class="preview-status-badge status-${order.status}">${order.status.replace(/_/g, ' ')}</span>
                        </div>
                        <div class="preview-info-item">
                            <span class="preview-info-label">Total Amount</span>
                            <span class="preview-info-value">₱${parseFloat(order.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                        </div>
                    </div>
                </div>
            `;
            
            if (order.purpose) {
                html += `
                    <div class="preview-section">
                        <h3 class="preview-section-title">Purpose</h3>
                        <p style="color: #374151; line-height: 1.6;">${order.purpose}</p>
                    </div>
                `;
            }
            
            if (order.items && order.items.length > 0) {
                html += `
                    <div class="preview-section">
                        <h3 class="preview-section-title">Order Items (${order.items.length})</h3>
                        <table class="preview-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th style="text-align: center;">Quantity</th>
                                    <th style="text-align: right;">Price</th>
                                    <th style="text-align: right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                order.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.product_name}</td>
                            <td style="text-align: center;">${item.quantity}</td>
                            <td style="text-align: right;">₱${parseFloat(item.product_price).toFixed(2)}</td>
                            <td style="text-align: right;">₱${parseFloat(item.subtotal).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            body.innerHTML = html;
        }
        
        // Close preview modal
        function closePreviewModal() {
            const modal = document.getElementById('bulkOrderPreviewModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            currentPreviewOrderId = null;
        }
        
        // View full details
        function viewFullDetails() {
            if (currentPreviewOrderId) {
                window.location.href = `bulk-order.php?id=${currentPreviewOrderId}`;
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreviewModal();
            }
        });
        
        // Auto-save status changes from list
        (function(){
            function onChange(e){
                const select = e.target;
                if (!select.classList.contains('status-select-list')) return;
                const orderId = select.getAttribute('data-order-id');
                const row = select.closest('tr');
                const saved = row ? row.querySelector('.saved-indicator') : null;
                const form = new FormData();
                form.append('action', 'update_status');
                form.append('is_ajax', '1');
                form.append('bulk_order_id', orderId);
                form.append('new_status', select.value);
                fetch('', { method: 'POST', body: form }).then(r => r.json()).then(data => {
                    if (data && data.success) {
                        if (saved) { saved.style.display = 'inline-flex'; setTimeout(()=> saved.style.display='none', 1500); }
                        // Update select styling class to reflect status and row filter attribute
                        select.className = 'status-select-list status-badge status-' + select.value;
                        row.setAttribute('data-status', select.value);
                    } else {
                        alert('Failed to update status: ' + (data && data.error ? data.error : 'Unknown error'));
                    }
                }).catch(() => alert('Request failed. Please try again.'));
            }
            document.addEventListener('change', onChange);
        })();
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
