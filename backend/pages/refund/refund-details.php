<?php ob_start();


session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";
require_once __DIR__ . '/../admin-includes/mailer.php';

// Get refund request ID from query string
$refund_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($refund_id <= 0) {
    die('Invalid refund request ID.');
}

// Fetch refund request details from order_refunds
$stmt = $conn->prepare('SELECT r.*, o.customer_name, o.customer_email, o.status as order_status, o.order_id as order_id, o.total_amount, u.firstname, u.lastname, u.username FROM order_refunds r LEFT JOIN orders o ON r.order_id = o.order_id LEFT JOIN users u ON r.user_id = u.id WHERE r.refund_id = ?');
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
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="/assets/public/admin.css">
    <style>
        body { background: var(--gray-50); }
        .refund-details-container { background: #fff; border-radius: 12px; padding: 2.5rem 2rem; margin: 2.5rem auto; max-width: 800px; box-shadow: 0 2px 16px #e5e7eb; font-family: 'Inter', sans-serif; }
        .refund-details-header { font-size: 2.2rem; font-weight: 700; margin-bottom: 2rem; color: var(--green-800); letter-spacing: -1px; }
        .refund-info-table { width: 100%; border-collapse: collapse; margin-bottom: 2.5rem; font-size: 1.08rem; }
        .refund-info-table td { padding: 14px 10px; border-bottom: 1px solid #f3f4f6; }
        .refund-info-table tr:last-child td { border-bottom: none; }
        .status-badge { padding: 4px 18px; border-radius: 12px; font-size: 1rem; font-weight: 500; display: inline-block; }
        .status-pending { background: #fff9db; color: #bfa100; }
        .status-approved { background: #e6ffed; color: #1a7f37; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .status-completed { background: #e6f7ff; color: #096dd9; }
        .change-status-form { margin-top: 24px; display: flex; align-items: center; gap: 1rem; }
        .change-status-btn { background: var(--green-700); color: #fff; border: none; padding: 10px 28px; border-radius: 6px; font-size: 1.08rem; cursor: pointer; font-weight: 600; transition: background 0.2s; }
        .change-status-btn:hover { background: var(--green-800); }
        .alert-success { background: #e6ffed; color: #1a7f37; padding: 16px 24px; border-radius: 8px; margin: 24px 0; font-weight: 600; }
        .alert-error { background: #fef2f2; color: #dc2626; padding: 16px 24px; border-radius: 8px; margin: 24px 0; font-weight: 600; }
        .back-link { display: inline-block; margin-top: 2.5rem; color: var(--green-700); text-decoration: underline; font-size: 1.05rem; }
        @media (max-width: 600px) {
            .refund-details-container { padding: 1.2rem 0.5rem; }
            .refund-details-header { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../admin-includes/navbar/navbar.php'; ?>
    <div class="refund-details-container">
        <div class="refund-details-header">Refund Request Details</div>
        <?php if ($success_message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <table class="refund-info-table">
            <tr><td><b>Ticket Number:</b></td><td>#RF-<?php echo str_pad($refund['refund_id'], 6, '0', STR_PAD_LEFT); ?></td></tr>
            <tr><td><b>Order ID:</b></td><td>#<?php echo htmlspecialchars($refund['order_id']); ?></td></tr>
            <tr><td><b>Customer Name:</b></td><td><?php 
                if (!empty($refund['customer_name'])) {
                    echo htmlspecialchars($refund['customer_name']);
                } elseif (!empty($refund['firstname']) && !empty($refund['lastname'])) {
                    echo htmlspecialchars($refund['firstname'] . ' ' . $refund['lastname']);
                } elseif (!empty($refund['username'])) {
                    echo htmlspecialchars($refund['username']);
                } else {
                    echo 'Guest User';
                }
            ?></td></tr>
            <tr><td><b>Email:</b></td><td><?php echo htmlspecialchars($refund['customer_email'] ?? ''); ?></td></tr>
            <tr><td><b>Total Items:</b></td><td><?php echo $total_items; ?></td></tr>
            <tr><td><b>Refund Amount:</b></td><td>₱<?php echo number_format($refund['refund_amount'], 2); ?></td></tr>
            <tr><td><b>Status:</b></td><td>
                <span class="status-badge status-<?php echo strtolower($refund['refund_status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($refund['refund_status'])); ?>
                </span>
            </td></tr>
            <tr><td><b>Reason:</b></td><td><?php echo nl2br(htmlspecialchars($refund['refund_reason'])); ?></td></tr>
            <tr><td><b>Date Requested:</b></td><td><?php echo htmlspecialchars($refund['created_at']); ?></td></tr>
        </table>
        <?php $current = $refund['refund_status']; ?>
        <?php if ($current === 'pending' || $current === 'approved'): ?>

        <button type="button" class="change-status-btn" onclick="openStatusModal()">Update Status</button>
        <!-- Status Change Modal -->
        <div id="statusModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;padding:32px 24px;border-radius:10px;max-width:400px;width:100%;box-shadow:0 2px 16px #0002;position:relative;">
                <h2>Change Refund Status</h2>
                <form method="post" id="modalStatusForm">
                    <div style="margin-bottom:1.2rem;">
                        <label for="modal_new_status"><b>Next Status:</b></label><br>
                        <select name="new_status" id="modal_new_status" style="margin-top:8px;padding:8px 12px;border-radius:6px;border:1px solid #e5e7eb;font-size:1rem;">
                            <?php if ($current === 'pending'): ?>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            <?php elseif ($current === 'approved'): ?>
                                <option value="completed">Completed</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:1rem;">
                        <button type="button" onclick="closeStatusModal()" style="background:#e5e7eb;color:#374151;padding:8px 20px;border-radius:6px;border:none;font-size:1rem;">Cancel</button>
                        <button type="submit" class="change-status-btn">Confirm</button>
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
        <div id="voucherModal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:9999;">
            <div style="background:#fff;padding:32px 24px;border-radius:10px;max-width:400px;width:100%;box-shadow:0 2px 16px #0002;">
                <h2>Refund Voucher</h2>
                <p><b>Voucher Code:</b> <span style="font-family:monospace;font-size:1.2em;"><?php echo htmlspecialchars($voucher_modal_data['voucher_code']); ?></span></p>
                <p><b>Amount:</b> ₱<?php echo number_format($voucher_modal_data['amount'], 2); ?></p>
                <p><b>Expiry Date:</b> <?php echo htmlspecialchars($voucher_modal_data['expiry']); ?></p>
                <form method="post" style="margin-top:24px;">
                    <input type="hidden" name="new_status" value="completed">
                    <input type="hidden" name="send_voucher" value="1">
                    <input type="hidden" name="voucher_code" value="<?php echo htmlspecialchars($voucher_modal_data['voucher_code']); ?>">
                    <input type="hidden" name="voucher_amount" value="<?php echo htmlspecialchars($voucher_modal_data['amount']); ?>">
                    <input type="hidden" name="voucher_expiry" value="<?php echo htmlspecialchars($voucher_modal_data['expiry']); ?>">
                    <button type="submit" class="change-status-btn">Send Voucher & Complete</button>
                    <button type="button" onclick="document.getElementById('voucherModal').style.display='none';document.body.style.overflow='auto';" style="margin-left:8px;">Cancel</button>
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
        <div style="background:#e6ffed;color:#1a7f37;padding:16px 24px;border-radius:8px;margin:24px 0;">
            <b>Voucher sent, notification created, and refund marked as completed!</b>
        </div>
        <?php endif; ?>
    <a href="refund-request-lists.php" class="back-link">&larr; Back to Refund Requests</a>
    </div>
</body>
</html>
