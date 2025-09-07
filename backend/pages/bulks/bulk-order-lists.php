<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bulk_order_id = (int)$_POST['bulk_order_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    $allowed_statuses = ['pending', 'approved', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE bulk_orders SET status = ?, admin_updated = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $bulk_order_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Order status updated successfully!";
        } else {
            $error_message = "Error updating order status: " . mysqli_error($conn);
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $error_message = "Invalid status selected.";
    }
}

// Create bulk_orders table if it doesn't exist
$create_table_query = "
    CREATE TABLE IF NOT EXISTS `bulk_orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `unique_order_id` varchar(20) DEFAULT NULL,
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
        `status` enum('pending','approved','payment_received','ready_for_delivery','cancelled','completed') NOT NULL DEFAULT 'pending',
        `proof_of_payment` varchar(500) DEFAULT NULL,
        `admin_updated` timestamp NULL DEFAULT NULL,
        `admin_notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_order_id` (`unique_order_id`),
        KEY `user_id` (`user_id`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
mysqli_query($conn, $create_table_query);

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
               COALESCE(bo.unique_order_id, CONCAT('BO', LPAD(bo.id, 6, '0'))) as unique_order_id,
               bo.name, bo.contact, bo.email, 
               bo.billing_address, bo.order_type, bo.delivery_address, bo.purpose,
               bo.date_needed, bo.time_needed, bo.note, 
               COALESCE(bo.total_amount, 0) as total_amount, 
               COALESCE(bo.total_items, 0) as total_items,
               bo.created_at, bo.status, bo.proof_of_payment, bo.admin_updated,
               u.firstname, u.lastname, u.username
        FROM bulk_orders bo
        LEFT JOIN users u ON bo.user_id = u.id
        ORDER BY bo.created_at DESC";

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
        // Use the stored total_items and total_amount from the main table
        $bulk_order_totals[$row['id']] = [
            'total_items' => $row['total_items'] ?? 0,
            'total_amount' => $row['total_amount'] ?? 0
        ];
    }
    mysqli_data_seek($result, 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_orders; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $approved_orders; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed_orders; ?></div>
                <div class="stat-label">Completed</div>
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
                            <th>Total Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $totals = isset($bulk_order_totals[$order['id']]) ? $bulk_order_totals[$order['id']] : ['total_items' => 0, 'total_amount' => 0];
                            $user_name = $order['firstname'] && $order['lastname'] 
                                ? $order['firstname'] . ' ' . $order['lastname'] 
                                : ($order['username'] ?: 'Guest User');
                            $order_id_display = $order['unique_order_id'] ? $order['unique_order_id'] : str_pad($order['id'], 6, '0', STR_PAD_LEFT);
                            ?>
                            <tr onclick="window.location.href='bulk-order.php?id=<?php echo $order['id']; ?>'">
                                <td>
                                    <div class="order-id">#<?php echo htmlspecialchars($order_id_display); ?></div>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                                        <?php if ($order['username']): ?>
                                            <div class="username">@<?php echo htmlspecialchars($order['username']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <div class="date-main"><?php echo date("M j, Y", strtotime($order['created_at'])); ?></div>
                                        <div class="date-time"><?php echo date("g:i A", strtotime($order['created_at'])); ?></div>
                                    </div>
                                </td>
                                <td><?php echo number_format($totals['total_items']); ?></td>
                                <td>₱<?php echo number_format($totals['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
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
</body>
</html>
