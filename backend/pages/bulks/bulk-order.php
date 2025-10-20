<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Get the order ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: bulk-order-lists.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Create bulk_orders table if it doesn't exist (match form structure)
// Ensure table exists with id PK and all statuses
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

// Create bulk_order_items table if it doesn't exist
// Items table aligned with integer bulk_order_id FK
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

// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['new_status'] ?? '';
    $is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : $order_id;
    $allowed_statuses = ['pending','approved','payment_received','payment_rejected','ready_for_delivery','cancelled','rejected','completed'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE bulk_orders SET status = ?, admin_updated = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $target_id);
        $ok = mysqli_stmt_execute($update_stmt);
        $err = mysqli_error($conn);
        mysqli_stmt_close($update_stmt);
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed')]);
            exit();
        } else {
            if ($ok) {
                $success_message = "Order status updated successfully to " . ucfirst(str_replace('_', ' ', $new_status)) . "!";
            } else {
                $error_message = "Error updating order status: " . $err;
            }
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit();
        }
        $error_message = "Invalid status selected.";
    }
}

// AJAX: Save editable sections for admin
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_customer_info') {
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : $order_id;
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $billing = trim($_POST['billing_address'] ?? '');
    $sql = "UPDATE bulk_orders SET name = ?, contact = ?, email = ?, billing_address = ?, admin_updated = NOW() WHERE id = ?"; 
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $contact, $email, $billing, $target_id);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed')]);
    exit();
}

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_order_details') {
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : $order_id;
    $purpose = trim($_POST['purpose'] ?? '');
    $date_needed = $_POST['date_needed'] ?? null;
    $time_needed = $_POST['time_needed'] ?? null;
    $delivery = trim($_POST['delivery_address'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $sql = "UPDATE bulk_orders SET purpose = ?, date_needed = ?, time_needed = ?, delivery_address = ?, admin_notes = ?, admin_updated = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $purpose, $date_needed, $time_needed, $delivery, $admin_notes, $target_id);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed')]);
    exit();
}

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_all') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $billing = trim($_POST['billing_address'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $date_needed = $_POST['date_needed'] ?? null;
    $time_needed = $_POST['time_needed'] ?? null;
    $delivery = trim($_POST['delivery_address'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $ok = false; $err='';
    if ($orderId) {
        $stmt = $conn->prepare("UPDATE bulk_orders SET name=?, contact=?, email=?, billing_address=?, purpose=?, date_needed=?, time_needed=?, delivery_address=?, admin_notes=?, admin_updated=NOW(), updated_at=NOW() WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('sssssssssi', $name, $contact, $email, $billing, $purpose, $date_needed, $time_needed, $delivery, $admin_notes, $orderId);
            $ok = $stmt->execute();
            if (!$ok) { $err = $stmt->error; }
            $stmt->close();
        } else { $err = $conn->error; }
    } else { $err = 'Invalid order id'; }
    header('Content-Type: application/json');
    echo json_encode(['success'=>$ok,'error'=>$err ?: null]);
    exit();
}

// Fetch bulk order details
$order_sql = "SELECT bo.*, u.firstname, u.lastname, u.username, u.email as user_email 
              FROM bulk_orders bo
              LEFT JOIN users u ON bo.user_id = u.id 
              WHERE bo.id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "i", $order_id);
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
mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
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
                <div class="header-actions">
                    <button class="btn btn-secondary btn-xs" id="toggleAllEdit"><i class="fas fa-pen"></i> Edit</button>
                    <button class="btn btn-primary btn-xs" id="saveAllBtn" disabled><i class="fas fa-save"></i> Save</button>
                    <span id="all-saved" style="display:none; color:#16a34a; margin-left:8px;"><i class="fas fa-check"></i> Saved</span>
                </div>
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
            <div class="status-form">
                <div class="form-group">
                    <label for="new_status">Change Status:</label>
                    <select id="new_status" class="status-select" data-order-id="<?php echo (int)$order['id']; ?>">
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
                    <span id="status-saved" style="display:none; color:#16a34a; margin-left:8px;"><i class="fas fa-check"></i> Saved</span>
                </div>
            </div>
        </div>

        <!-- Order Information Grid -->
        <div class="order-info-grid">
            <!-- Customer Information -->
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <!-- View mode -->
                <div class="info-grid view-mode">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value" id="view_name">
                            <?php echo htmlspecialchars($order['name']); ?>
                            <?php if (!empty($order['username'])): ?>
                                <br><small>@<?php echo htmlspecialchars($order['username']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value" id="view_contact"><?php echo htmlspecialchars($order['contact']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value" id="view_email"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Billing Address</div>
                        <div class="info-value" id="view_billing"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></div>
                    </div>
                </div>
                <!-- Edit mode -->
                <div class="info-grid edit-mode hidden">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><input class="editable-input" id="cust_name" type="text" value="<?php echo htmlspecialchars($order['name']); ?>"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value"><input class="editable-input" id="cust_contact" type="text" value="<?php echo htmlspecialchars($order['contact']); ?>"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><input class="editable-input" id="cust_email" type="email" value="<?php echo htmlspecialchars($order['email']); ?>"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Billing Address</div>
                        <div class="info-value"><textarea class="editable-input" id="cust_billing" rows="3"><?php echo htmlspecialchars($order['billing_address']); ?></textarea></div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="info-card">
                <h3><i class="fas fa-clipboard-list"></i> Order Details</h3>
                <!-- View mode -->
                <div class="info-grid two-column view-mode">
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
                            <div class="info-value" id="view_delivery"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-label">Date Submitted</div>
                            <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date Needed</div>
                            <div class="info-value" id="view_date_needed"><?php echo date('F j, Y', strtotime($order['date_needed'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Time Needed</div>
                            <div class="info-value" id="view_time_needed"><?php echo date('g:i A', strtotime($order['time_needed'])); ?></div>
                        </div>
                    </div>
                    
                    <!-- Second Column -->
                    <div class="column-right">
                        <div class="info-item">
                            <div class="info-label">Purpose of Order</div>
                            <div class="info-value" id="view_purpose"><?php echo nl2br(htmlspecialchars($order['purpose'])); ?></div>
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
                <!-- Edit mode -->
                <div class="info-grid two-column edit-mode hidden">
                    <div class="column-left">
                        <div class="info-item">
                            <div class="info-label">Order Type</div>
                            <div class="info-value">
                                <span class="order-type-badge <?php echo $order['order_type']; ?>"><?php echo ucfirst($order['order_type']); ?></span>
                            </div>
                        </div>
                        <?php if ($order['order_type'] == 'delivery'): ?>
                        <div class="info-item">
                            <div class="info-label">Delivery Address</div>
                            <div class="info-value"><textarea class="editable-input" id="order_delivery" rows="3"><?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></textarea></div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-label">Date Needed</div>
                            <div class="info-value"><input class="editable-input" id="order_date_needed" type="date" value="<?php echo htmlspecialchars($order['date_needed']); ?>"></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Time Needed</div>
                            <div class="info-value"><input class="editable-input" id="order_time_needed" type="time" value="<?php echo htmlspecialchars(substr($order['time_needed'],0,5)); ?>"></div>
                        </div>
                    </div>
                    <div class="column-right">
                        <div class="info-item">
                            <div class="info-label">Purpose of Order</div>
                            <div class="info-value"><textarea class="editable-input" id="order_purpose" rows="3"><?php echo htmlspecialchars($order['purpose']); ?></textarea></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Admin Notes</div>
                            <div class="info-value"><textarea class="editable-input" id="order_admin_notes" rows="3" placeholder="Notes visible to the customer (optional)"><?php echo htmlspecialchars($order['admin_notes'] ?? ''); ?></textarea></div>
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

        <!-- Payment Proofs -->
        <?php if ($order['status'] == 'approved'): ?>
        <div class="payment-status">
            <h3><i class="fas fa-credit-card"></i> Payment Proofs</h3>
            <?php 
            $proofs = [];
            if (!empty($order['proof_of_payment'])) {
                $decoded = json_decode($order['proof_of_payment'], true);
                if (is_array($decoded)) {
                    $proofs = $decoded;
                } else {
                    $proofs = [[
                        'filename' => $order['proof_of_payment'],
                        'type' => 'full',
                        'uploaded_at' => 'Unknown',
                        'original_name' => $order['proof_of_payment']
                    ]];
                }
            }
            ?>
            <?php if (!empty($proofs)): ?>
                <div class="proofs-grid">
                    <?php foreach ($proofs as $pf): 
                        $file = "../../../assets/bulk_payments/" . $pf['filename'];
                        $ext = strtolower(pathinfo($pf['filename'], PATHINFO_EXTENSION));
                    ?>
                    <div class="proof-item" title="<?php echo htmlspecialchars(ucfirst($pf['type']).' • '.$pf['uploaded_at']); ?>">
                        <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                            <img src="<?php echo $file; ?>" alt="Proof of payment" class="proof-thumb" onclick="openImageModal(this.src)">
                        <?php else: ?>
                            <a class="btn btn-secondary" href="<?php echo $file; ?>" target="_blank"><i class="fas fa-file-pdf"></i> View PDF</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="payment-info">
                    <div class="payment-icon payment-awaiting"><i class="fas fa-clock"></i></div>
                    <div><strong>No payment proof submitted</strong></div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <style>
        .editable-input { border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; width: 100%; background: #fffdf7; }
        .edit-actions { margin-top: 10px; }
        .card-header-flex { display:flex; align-items:center; justify-content:space-between; gap: 10px; }
        .btn-xs { padding: 4px 8px; font-size: 12px; }
        .header-actions { display:flex; gap:8px; align-items:center; }
        .hidden { display: none !important; }
        .proofs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
        .proof-thumb { width: 100%; height: 120px; object-fit: cover; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .image-modal { display:none; position: fixed; z-index: 9999; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.75); align-items:center; justify-content:center; }
        .image-modal img { max-width: 90%; max-height: 90%; border-radius: 8px; }
        .image-modal .close { position:absolute; top:20px; right:24px; font-size:28px; color:#fff; cursor:pointer; }
    </style>
    <div id="imgModal" class="image-modal" onclick="closeImageModal(event)">
        <span class="close" onclick="closeImageModal(event)">&times;</span>
        <img id="imgModalContent" src="" alt="Preview" />
    </div>
    <script>
        // Status auto-save
        (function(){
            const statusSelect = document.getElementById('new_status');
            const statusSaved = document.getElementById('status-saved');
            if (statusSelect) {
                statusSelect.addEventListener('change', async () => {
                    const orderId = statusSelect.getAttribute('data-order-id');
                    const form = new FormData();
                    form.append('action', 'update_status');
                    form.append('is_ajax', '1');
                    form.append('order_id', orderId);
                    form.append('new_status', statusSelect.value);
                    try {
                        const res = await fetch('', { method: 'POST', body: form });
                        const data = await res.json();
                        if (data.success) {
                            statusSaved.style.display = 'inline-flex';
                            setTimeout(() => { statusSaved.style.display = 'none'; }, 1500);
                            const badge = document.querySelector('.status-badge');
                            if (badge) {
                                badge.textContent = statusSelect.options[statusSelect.selectedIndex].text;
                                badge.className = 'status-badge status-' + statusSelect.value;
                            }
                        } else {
                            alert('Failed to update status: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Request failed. Please try again.');
                    }
                });
            }
        })();

        // Single toggle for both sections
        (function(){
            const toggle = document.getElementById('toggleAllEdit');
            const saveBtn = document.getElementById('saveAllBtn');
            const savedIndicator = document.getElementById('all-saved');
            const viewBlocks = document.querySelectorAll('.view-mode');
            const editBlocks = document.querySelectorAll('.edit-mode');
            let editing = false;
            if (toggle) {
                toggle.addEventListener('click', () => {
                    editing = !editing;
                    viewBlocks.forEach(b => b.classList.toggle('hidden', editing));
                    editBlocks.forEach(b => b.classList.toggle('hidden', !editing));
                    saveBtn.disabled = !editing;
                    toggle.innerHTML = editing ? '<i class="fas fa-ban"></i> Cancel' : '<i class="fas fa-pen"></i> Edit';
                });
            }
            if (saveBtn) {
                saveBtn.addEventListener('click', async () => {
                    const form = new FormData();
                    form.append('action', 'save_all');
                    form.append('is_ajax', '1');
                    form.append('order_id', '<?php echo (int)$order['id']; ?>');
                    // Customer
                    form.append('name', document.getElementById('cust_name').value);
                    form.append('contact', document.getElementById('cust_contact').value);
                    form.append('email', document.getElementById('cust_email').value);
                    form.append('billing_address', document.getElementById('cust_billing').value);
                    // Order
                    const deliveryEl = document.getElementById('order_delivery');
                    form.append('delivery_address', deliveryEl ? deliveryEl.value : '');
                    form.append('date_needed', document.getElementById('order_date_needed').value);
                    form.append('time_needed', document.getElementById('order_time_needed').value);
                    form.append('purpose', document.getElementById('order_purpose').value);
                    form.append('admin_notes', document.getElementById('order_admin_notes').value);
                    try {
                        const res = await fetch('', { method: 'POST', body: form });
                        const data = await res.json();
                        if (data.success) {
                            // Update view text
                            const nameV = document.getElementById('view_name');
                            const contactV = document.getElementById('view_contact');
                            const emailV = document.getElementById('view_email');
                            const billingV = document.getElementById('view_billing');
                            const deliveryV = document.getElementById('view_delivery');
                            const dateV = document.getElementById('view_date_needed');
                            const timeV = document.getElementById('view_time_needed');
                            const purposeV = document.getElementById('view_purpose');
                            if (nameV) {
                                const nameVal = document.getElementById('cust_name').value;
                                const existingUsername = nameV.querySelector('small');
                                nameV.innerHTML = nameVal;
                                if (existingUsername) nameV.appendChild(document.createElement('br')).after(existingUsername);
                            }
                            if (contactV) contactV.textContent = document.getElementById('cust_contact').value;
                            if (emailV) emailV.textContent = document.getElementById('cust_email').value;
                            if (billingV) billingV.innerHTML = (document.getElementById('cust_billing').value || '').replace(/\n/g,'<br>');
                            if (deliveryV && deliveryEl) deliveryV.innerHTML = (deliveryEl.value || '').replace(/\n/g,'<br>');
                            // Format date/time for view
                            const dateStr = document.getElementById('order_date_needed').value;
                            const timeStr = document.getElementById('order_time_needed').value;
                            function formatDate(str){ try { const d = new Date(str+'T00:00:00'); return d.toLocaleDateString(undefined, { month:'long', day:'numeric', year:'numeric' }); } catch(e){return str;} }
                            function formatTime(str){ try { const [h,m] = str.split(':'); const d = new Date(); d.setHours(h, m||'0'); return d.toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'}); } catch(e){return str;} }
                            if (dateV && dateStr) dateV.textContent = formatDate(dateStr);
                            if (timeV && timeStr) timeV.textContent = formatTime(timeStr);
                            if (purposeV) purposeV.innerHTML = (document.getElementById('order_purpose').value || '').replace(/\n/g,'<br>');
                            // Admin notes block
                            const adminNotesVal = document.getElementById('order_admin_notes').value;
                            const adminSection = document.querySelector('.admin-notes-section');
                            if (adminSection) {
                                const content = adminSection.querySelector('.notes-content');
                                if (adminNotesVal && adminNotesVal.trim()) {
                                    if (content) content.innerHTML = adminNotesVal.replace(/\n/g,'<br>');
                                    adminSection.style.display = '';
                                } else {
                                    adminSection.style.display = 'none';
                                }
                            }
                            // Toggle back to view mode
                            editing = false;
                            viewBlocks.forEach(b => b.classList.remove('hidden'));
                            editBlocks.forEach(b => b.classList.add('hidden'));
                            saveBtn.disabled = true;
                            if (toggle) toggle.innerHTML = '<i class="fas fa-pen"></i> Edit';
                            savedIndicator.style.display = 'inline-flex';
                            setTimeout(()=> savedIndicator.style.display = 'none', 1500);
                        } else {
                            alert('Save failed: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) { alert('Request failed.'); }
                });
            }
        })();

        // Image modal
        function openImageModal(src) {
            const modal = document.getElementById('imgModal');
            const img = document.getElementById('imgModalContent');
            img.src = src;
            modal.style.display = 'flex';
        }
        function closeImageModal(e) {
            const modal = document.getElementById('imgModal');
            if (!e || e.target === modal || (e.target && e.target.classList && e.target.classList.contains('close'))) {
                modal.style.display = 'none';
                document.getElementById('imgModalContent').src = '';
            }
        }
        window.openImageModal = openImageModal;
        window.closeImageModal = closeImageModal;
    </script>
</body>
</html>
