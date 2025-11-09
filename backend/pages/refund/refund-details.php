<?php ob_start();


// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";
require_once __DIR__ . '/../admin-includes/mailer.php';

// Get refund request ID from query string
$refund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($refund_id <= 0) {
    die('Invalid refund request ID.');
}

// Fetch refund request details from order_refunds with voucher information
$stmt = $conn->prepare('SELECT r.*, o.customer_name, o.customer_email, o.status as order_status, o.order_id as order_id, o.total_amount, u.firstname, u.lastname, u.username, rv.voucher_code, rv.amount as voucher_amount, rv.expiry_date as voucher_expiry, rv.status as voucher_status FROM order_refunds r LEFT JOIN orders o ON r.order_id = o.order_id LEFT JOIN users u ON r.user_id = u.id LEFT JOIN refund_vouchers rv ON r.refund_id = rv.refund_id WHERE r.refund_id = ?');
$stmt->bind_param('i', $refund_id);
$stmt->execute();
$result = $stmt->get_result();
$refund = $result->fetch_assoc();
if (!$refund) {
    die('Refund request not found.');
}

// Parse refund items to count total items
$refund_items_array = json_decode($refund['refund_items'], true);
$total_items = 0;
if (is_array($refund_items_array)) {
    foreach ($refund_items_array as $item) {
        $total_items += isset($item['quantity']) ? intval($item['quantity']) : 1;
    }
}

$voucher_modal_data = null;
$voucher_sent = false;
$success_message = null;
$error_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_status'])) {
        $new_status = $_POST['new_status'];
        $allowed = ['pending', 'approved', 'rejected', 'completed'];
        $current = $refund['refund_status'];
        // Only allow valid transitions
        $valid = false;
        if ($current === 'pending' && in_array($new_status, ['approved', 'rejected'])) {
            $valid = true;
        } elseif ($current === 'approved' && $new_status === 'completed') {
            $valid = true;
        }
        if (!$valid) {
            $error_message = 'Invalid status transition.';
        } elseif ($current === 'approved' && $new_status === 'completed' && !isset($_POST['send_voucher'])) {
            // Prepare voucher modal data
            $voucher_code = 'VCHR-' . strtoupper(bin2hex(random_bytes(4)));
            $voucher_amount = $refund['refund_amount'];
            $voucher_expiry = date('Y-m-d', strtotime('+1 month'));
            $voucher_modal_data = [
                'voucher_code' => $voucher_code,
                'amount' => $voucher_amount,
                'expiry' => $voucher_expiry,
                'customer_email' => $refund['customer_email'],
                'customer_name' => (!empty($refund['customer_name']) ? $refund['customer_name'] : ($refund['firstname'] . ' ' . $refund['lastname'])),
            ];
        } elseif (isset($_POST['send_voucher']) && $_POST['send_voucher'] === '1') {
            // Actually send voucher and update DB
            $voucher_code = $_POST['voucher_code'];
            $voucher_amount = floatval($_POST['voucher_amount']);
            $voucher_expiry = $_POST['voucher_expiry'];
            $customer_id = $refund['user_id'];
            // Insert into refund_vouchers
            $stmtV = $conn->prepare('INSERT INTO refund_vouchers (refund_id, customer_id, voucher_code, amount, status, expiry_date, created_at) VALUES (?, ?, ?, ?, "active", ?, NOW())');
            $stmtV->bind_param('iisds', $refund_id, $customer_id, $voucher_code, $voucher_amount, $voucher_expiry);
            $stmtV->execute();
            // Insert notification
            $notif_title = 'You received a refund voucher!';
            $notif_message = 'Your refund has been processed. Voucher code: ' . $voucher_code . ' (₱' . number_format($voucher_amount, 2) . ', expires ' . $voucher_expiry . ')';
            $notif_type = 'promotion';
            $notif_link = null;
            $notif_stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message, type, order_id, link) VALUES (?, ?, ?, ?, ?, ?)');
            $oid = $refund['order_id'] ?? null;
            $notif_stmt->bind_param('isssis', $customer_id, $notif_title, $notif_message, $notif_type, $oid, $notif_link);
            $notif_stmt->execute();
            // Send email
            $to = $refund['customer_email'];
            $subject = 'Your Refund Voucher from NeoCafe';
            $body = '<h2>Your Refund Voucher</h2>' .
                '<p>Dear ' . htmlspecialchars($voucher_modal_data['customer_name'] ?? '') . ',</p>' .
                '<p>Your refund has been processed. Here is your voucher code:</p>' .
                '<b>Voucher Code:</b> ' . htmlspecialchars($voucher_code) . '<br>' .
                '<b>Amount:</b> ₱' . number_format($voucher_amount, 2) . '<br>' .
                '<b>Expiry Date:</b> ' . htmlspecialchars($voucher_expiry) . '<br>' .
                '<p>Thank you for choosing NeoCafe!</p>';
            sendEmail($to, $subject, $body, true);
            // Update refund status
            $update = $conn->prepare('UPDATE order_refunds SET refund_status = "completed", updated_at = NOW() WHERE refund_id = ?');
            $update->bind_param('i', $refund_id);
            $update->execute();
            logAdminActivity($conn, 'UPDATE', "Changed refund request #$refund_id status to 'completed' and sent voucher", 'order_refunds', $refund_id);
            $voucher_sent = true;
            // Show success message and close modal
            header('Location: refund-details.php?id=' . $refund_id . '&voucher_sent=1&voucher_success=1');
            exit;
        } elseif ($current === 'pending' && in_array($new_status, ['approved', 'rejected'])) {
            // Send email for approve/reject
            $to = $refund['customer_email'];
            $customer_name = (!empty($refund['customer_name']) ? $refund['customer_name'] : ($refund['firstname'] . ' ' . $refund['lastname']));
            $subject = 'Your Refund Request Update from NeoCafe';
            if ($new_status === 'approved') {
                $body = '<h2>Refund Approved</h2>' .
                    '<p>Dear ' . htmlspecialchars($customer_name) . ',</p>' .
                    '<p>Your refund request has been <b style="color:#16a34a">approved</b>. We will process your refund soon.</p>';
            } else {
                $body = '<h2>Refund Rejected</h2>' .
                    '<p>Dear ' . htmlspecialchars($customer_name) . ',</p>' .
                    '<p>Your refund request has been <b style="color:#dc2626">rejected</b>. If you have questions, please contact support.</p>';
            }
            sendEmail($to, $subject, $body, true);
            // Update status
            $update = $conn->prepare('UPDATE order_refunds SET refund_status = ?, updated_at = NOW() WHERE refund_id = ?');
            $update->bind_param('si', $new_status, $refund_id);
            $update->execute();
            logAdminActivity($conn, 'UPDATE', "Changed refund request #$refund_id status to '$new_status' and sent email", 'order_refunds', $refund_id);
            $success_message = 'Refund status updated and email sent.';
            header('Location: refund-details.php?id=' . $refund_id . '&success=1');
            exit;
        }
    }
}

// UI starts here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Request Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="/assets/public/admin.css">
    <link rel="stylesheet" href="refund-details.css">
</head>
<body>
    <?php include __DIR__ . '/../admin-includes/navbar/navbar.php'; ?>
    <?php include __DIR__ . '/../admin-includes/breadcrumbs/admin-breadcrumb.php'; ?>

    <div class="refund-details-container">
        <?php if ($success_message): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="refund-details-header">
            Refund Request #RF-<?php echo str_pad($refund['refund_id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
        
        <div class="refund-info-grid">
            <!-- Basic Information Section -->
            <div class="refund-info-section">
                <h3>Basic Information</h3>
                <div class="info-item">
                    <span class="info-label">Ticket Number:</span>
                    <span class="info-value">#RF-<?php echo str_pad($refund['refund_id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Order ID:</span>
                    <span class="info-value">#<?php echo htmlspecialchars($refund['order_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Customer Name:</span>
                    <span class="info-value"><?php 
                        if (!empty($refund['customer_name'])) {
                            echo htmlspecialchars($refund['customer_name']);
                        } elseif (!empty($refund['firstname']) && !empty($refund['lastname'])) {
                            echo htmlspecialchars($refund['firstname'] . ' ' . $refund['lastname']);
                        } elseif (!empty($refund['username'])) {
                            echo htmlspecialchars($refund['username']);
                        } else {
                            echo 'Guest User';
                        }
                    ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($refund['customer_email'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date Requested:</span>
                    <span class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($refund['created_at'])); ?></span>
                </div>
            </div>
            
            <!-- Refund Details Section -->
            <div class="refund-info-section">
                <h3>Refund Details</h3>
                <div class="info-item">
                    <span class="info-label">Total Items:</span>
                    <span class="info-value"><?php echo $total_items; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Refund Amount:</span>
                    <span class="info-value">₱<?php echo number_format($refund['refund_amount'], 2); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php echo strtolower($refund['refund_status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($refund['refund_status'])); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Reason:</span>
                    <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($refund['refund_reason']))); ?></span>
                </div>
                <?php if (!empty($refund['refund_note'])): ?>
                <div class="info-item">
                    <span class="info-label">Customer Notes:</span>
                    <span class="info-value"><?php echo nl2br(htmlspecialchars($refund['refund_note'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($refund['voucher_code'])): ?>
        <div class="voucher-info">
            <h3>Refund Voucher Information</h3>
            <div class="voucher-code">
                <?php echo htmlspecialchars($refund['voucher_code']); ?>
            </div>
            <div class="voucher-details">
                <div class="voucher-detail">
                    <strong>Amount:</strong> ₱<?php echo number_format($refund['voucher_amount'], 2); ?>
                </div>
                <div class="voucher-detail">
                    <strong>Expires:</strong> <?php echo date('F j, Y', strtotime($refund['voucher_expiry'])); ?>
                </div>
                <div class="voucher-detail">
                    <strong>Status:</strong> 
                    <span class="voucher-status-badge">
                        <?php echo ucfirst(htmlspecialchars($refund['voucher_status'])); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($refund['proof_image'])): ?>
        <div class="proof-image-section">
            <h3>Proof Image</h3>
            <div class="proof-image-container">
                <a href="../../../<?php echo htmlspecialchars($refund['proof_image']); ?>" target="_blank">
                    <img src="../../../<?php echo htmlspecialchars($refund['proof_image']); ?>" 
                         alt="Refund Proof" 
                         class="proof-image">
                </a>
                <p class="proof-image-caption">
                    <i class="fas fa-expand"></i> Click image to view full size
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (is_array($refund_items_array) && !empty($refund_items_array)): ?>
        <div class="refunded-items-section">
            <h3>Refunded Items</h3>
            <table class="refunded-items-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($refund_items_array as $item): ?>
                    <tr>
                        <td data-label="Product Name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td data-label="Quantity"><?php echo intval($item['quantity']); ?></td>
                        <td data-label="Price">₱<?php echo number_format(floatval($item['price']), 2); ?></td>
                        <td data-label="Subtotal">₱<?php echo number_format(floatval($item['price']) * intval($item['quantity']), 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total Refund Amount:</td>
                        <td>₱<?php echo number_format($refund['refund_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
        <?php $current = $refund['refund_status']; ?>
        <?php if ($current === 'pending' || $current === 'approved'): ?>

        <div class="status-update-section">
            <button type="button" class="change-status-btn" onclick="openStatusModal()">
                <i class="fas fa-edit"></i>
                Update Status
            </button>
        </div>
        
        <!-- Status Change Modal -->
        <div id="statusModal" class="modal-overlay" style="display: none;">
            <div class="modal-container">
                <div class="modal-header">
                    <h2>Change Refund Status</h2>
                </div>
                <form method="post" id="modalStatusForm">
                    <div class="modal-form-group">
                        <label for="modal_new_status">Next Status:</label>
                        <select name="new_status" id="modal_new_status">
                            <?php if ($current === 'pending'): ?>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            <?php elseif ($current === 'approved'): ?>
                                <option value="completed">Completed</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-btn-secondary" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="modal-btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        function openStatusModal() {
            document.getElementById('statusModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        // Modal form submit: copy value to hidden form and submit
        document.addEventListener('DOMContentLoaded', function() {
            var modalForm = document.getElementById('modalStatusForm');
            if (modalForm) {
                modalForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // Create a hidden form and submit
                    var status = document.getElementById('modal_new_status').value;
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'new_status';
                    input.value = status;
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        });
        </script>
        <?php endif; ?>

        <?php if ($voucher_modal_data): ?>
        <!-- Voucher Modal -->
        <div id="voucherModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h2>Refund Voucher</h2>
                </div>
                <div class="voucher-modal-content">
                    <div class="voucher-modal-code">
                        <?php echo htmlspecialchars($voucher_modal_data['voucher_code']); ?>
                    </div>
                    <div class="voucher-modal-details">
                        <p><strong>Amount:</strong> ₱<?php echo number_format($voucher_modal_data['amount'], 2); ?></p>
                        <p><strong>Expiry Date:</strong> <?php echo htmlspecialchars($voucher_modal_data['expiry']); ?></p>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($voucher_modal_data['customer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($voucher_modal_data['customer_email']); ?></p>
                    </div>
                </div>
                <form method="post" style="margin-top:24px;">
                    <input type="hidden" name="new_status" value="completed">
                    <input type="hidden" name="send_voucher" value="1">
                    <input type="hidden" name="voucher_code" value="<?php echo htmlspecialchars($voucher_modal_data['voucher_code']); ?>">
                    <input type="hidden" name="voucher_amount" value="<?php echo htmlspecialchars($voucher_modal_data['amount']); ?>">
                    <input type="hidden" name="voucher_expiry" value="<?php echo htmlspecialchars($voucher_modal_data['expiry']); ?>">
                    <div class="modal-actions">
                        <button type="button" class="modal-btn-secondary" onclick="document.getElementById('voucherModal').style.display='none';document.body.style.overflow='auto';">Cancel</button>
                        <button type="submit" class="modal-btn-primary">Send Voucher & Complete</button>
                    </div>
                </form>
            </div>
        </div>
        <script>document.body.style.overflow = 'hidden';</script>
        <?php endif; ?>

        <?php if (isset($_GET['voucher_sent']) && isset($_GET['voucher_success'])): ?>
        <script>
        if (document.getElementById('voucherModal')) {
            document.getElementById('voucherModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        </script>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            <strong>Voucher sent, notification created, and refund marked as completed!</strong>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
