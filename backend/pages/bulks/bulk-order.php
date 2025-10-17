<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Get the order ID from URL (now expecting unique_order_id)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: bulk-order-lists.php");
    exit();
}

$order_id = $_GET['id']; // This is now the unique_order_id like "BO000001"

// Create bulk_orders table with unique_order_id as primary key
$create_table_query = "
    CREATE TABLE IF NOT EXISTS `bulk_orders` (
        `unique_order_id` varchar(20) NOT NULL PRIMARY KEY,
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
        `status` enum('pending','approved','payment_received','ready_for_delivery','ready_for_pickup','cancelled','completed') NOT NULL DEFAULT 'pending',
        `proof_of_payment` varchar(500) DEFAULT NULL,
        `admin_updated` boolean DEFAULT FALSE,
        `admin_notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `user_id` (`user_id`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
mysqli_query($conn, $create_table_query);

// Create bulk_order_items table
$create_items_table_query = "
    CREATE TABLE IF NOT EXISTS `bulk_order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bulk_order_id` varchar(20) NOT NULL,
        `product_id` int(11) NOT NULL,
        `product_name` varchar(255) NOT NULL,
        `product_price` decimal(10,2) NOT NULL,
        `quantity` int(11) NOT NULL,
        `subtotal` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `bulk_order_id` (`bulk_order_id`),
        CONSTRAINT `bulk_order_items_ibfk_1` FOREIGN KEY (`bulk_order_id`) REFERENCES `bulk_orders` (`unique_order_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
mysqli_query($conn, $create_items_table_query);

// Check if columns exist and add them if they don't
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



// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['new_status'];
    
    // Validate status
    $allowed_statuses = ['pending', 'approved', 'payment_received', 'ready_for_delivery', 'ready_for_pickup', 'cancelled', 'completed'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE bulk_orders SET status = ?, admin_updated = TRUE WHERE unique_order_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ss", $new_status, $order_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Order status updated successfully to " . ucfirst(str_replace('_', ' ', $new_status)) . "!";
        } else {
            $error_message = "Error updating order status: " . mysqli_error($conn);
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $error_message = "Invalid status selected.";
    }
}

// Handle order content updates (quantities, notes, etc.)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    $customer_email = $_POST['customer_email'];
    $notes = $_POST['notes'];
    $admin_notes = $_POST['admin_notes'];
    
    // Update order details
    $update_order_sql = "UPDATE bulk_orders SET name = ?, contact = ?, email = ?, note = ?, admin_notes = ?, admin_updated = TRUE WHERE unique_order_id = ?";
    $update_order_stmt = mysqli_prepare($conn, $update_order_sql);
    mysqli_stmt_bind_param($update_order_stmt, "ssssss", $customer_name, $customer_phone, $customer_email, $notes, $admin_notes, $order_id);
    
    if (mysqli_stmt_execute($update_order_stmt)) {
        // Update order items
        $items_updated = true;
        foreach ($_POST['items'] as $item_id => $item_data) {
            $quantity = (int)$item_data['quantity'];
            $price = (float)$item_data['price'];
            $subtotal = $quantity * $price;
            
            if ($quantity > 0) {
                $update_item_sql = "UPDATE bulk_order_items SET quantity = ?, product_price = ?, subtotal = ? WHERE id = ? AND bulk_order_id = ?";
                $update_item_stmt = mysqli_prepare($conn, $update_item_sql);
                mysqli_stmt_bind_param($update_item_stmt, "iddis", $quantity, $price, $subtotal, $item_id, $order_id);
                
                if (!mysqli_stmt_execute($update_item_stmt)) {
                    $items_updated = false;
                }
                mysqli_stmt_close($update_item_stmt);
            }
        }
        
        if ($items_updated) {
            $success_message = "Order updated successfully! Customer will be notified of changes.";
        } else {
            $error_message = "Order details updated, but some items could not be updated.";
        }
    } else {
        $error_message = "Error updating order: " . mysqli_error($conn);
    }
    mysqli_stmt_close($update_order_stmt);
}

// Fetch bulk order details
$order_sql = "SELECT bo.*, u.firstname, u.lastname, u.username, u.email as user_email 
              FROM bulk_orders bo
              LEFT JOIN users u ON bo.user_id = u.id 
              WHERE bo.unique_order_id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "s", $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header("Location: bulk-order-lists.php?error=Order not found");
    exit();
}

// Fetch order items with IDs for editing
$items_sql = "SELECT * FROM bulk_order_items WHERE bulk_order_id = ? ORDER BY id";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "s", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

// Calculate totals
$total_items = 0;
$total_amount = 0;
$items = [];

while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
    $total_items += $item['quantity'];
    $total_amount += $item['subtotal'];
}

$user_name = $order['firstname'] && $order['lastname'] 
    ? $order['firstname'] . ' ' . $order['lastname'] 
    : ($order['username'] ?: 'Guest User');

$order_id_display = $order['unique_order_id'] ? $order['unique_order_id'] : str_pad($order['id'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Details - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="bulk-order.css">
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <div class="bulk-order-detail-container">
        <div class="page-header">
            <div class="header-content">
                <h1> Bulk Order ID: #<?php echo htmlspecialchars($order_id_display); ?></h1>
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

        <!-- Status Update Section -->
        <div class="status-update-section">
            <h3><i class="fas fa-edit"></i> Update Order Status</h3>
            <form method="POST" class="status-form">
                <input type="hidden" name="action" value="update_status">
                <div class="form-group">
                    <label for="new_status">Change Status:</label>
                    <select name="new_status" id="new_status" class="status-select" required>
                        <option value="">Select new status...</option>
                        <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($order['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="payment_received" <?php echo ($order['status'] == 'payment_received') ? 'selected' : ''; ?>>Payment Received</option>
                        <option value="ready_for_delivery" <?php echo ($order['status'] == 'ready_for_delivery') ? 'selected' : ''; ?>>Ready for Delivery</option>
                        <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>

        <!-- Order Information Grid -->
        <div class="order-info-grid">
            <!-- Customer Information -->
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($user_name); ?>
                            <?php if ($order['username']): ?>
                                <br><small>@<?php echo htmlspecialchars($order['username']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['contact']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Billing Address</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="info-card">
                <h3><i class="fas fa-clipboard-list"></i> Order Details</h3>
                <div class="info-grid two-column">
                    <!-- First Column -->
                    <div class="column-left">
                        <div class="info-item">
                            <div class="info-label">Order Type</div>
                            <div class="info-value">
                                <span class="order-type-badge <?php echo $order['order_type']; ?>">
                                    <?php echo ucfirst($order['order_type']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($order['order_type'] == 'delivery' && $order['delivery_address']): ?>
                        <div class="info-item">
                            <div class="info-label">Delivery Address</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-label">Date Submitted</div>
                            <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date Needed</div>
                            <div class="info-value"><?php echo date('F j, Y', strtotime($order['date_needed'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Time Needed</div>
                            <div class="info-value"><?php echo date('g:i A', strtotime($order['time_needed'])); ?></div>
                        </div>
                    </div>
                    
                    <!-- Second Column -->
                    <div class="column-right">
                        <div class="info-item">
                            <div class="info-label">Purpose of Order</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['purpose'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Items</div>
                            <div class="info-value"><?php echo number_format($total_items); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Amount</div>
                            <div class="info-value total-amount">₱<?php echo number_format($total_amount, 2); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current Status</div>
                            <div class="info-value">
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Notes -->
        <?php if (!empty($order['note'])): ?>
        <div class="notes-section">
            <div class="notes-header">
                <i class="fas fa-sticky-note"></i> Customer Notes
            </div>
            <div class="notes-content">
                <?php echo nl2br(htmlspecialchars($order['note'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin Notes -->
        <?php if (!empty($order['admin_notes'])): ?>
        <div class="notes-section admin-notes-section">
            <div class="notes-header">
                <i class="fas fa-user-shield"></i> Admin Notes
            </div>
            <div class="notes-content">
                <?php echo nl2br(htmlspecialchars($order['admin_notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Items -->
        <div class="items-table-container">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td>₱<?php echo number_format($item['product_price'], 2); ?></td>
                            <td><?php echo number_format($item['quantity']); ?></td>
                            <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">No items found for this order</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total Amount:</strong></td>
                        <td><strong>₱<?php echo number_format($total_amount, 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Payment Status -->
        <?php if ($order['status'] == 'approved'): ?>
        <div class="payment-status">
            <h3><i class="fas fa-credit-card"></i> Payment Status</h3>
            <div class="payment-info">
                <?php if (!empty($order['proof_of_payment'])): ?>
                    <div class="payment-icon payment-uploaded">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <strong>Customer has uploaded payment proof</strong>
                    </div>
                <?php else: ?>
                    <div class="payment-icon payment-awaiting">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <strong>Awaiting Payment Proof</strong><br>
                        <small>Customer has not yet uploaded payment proof</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
