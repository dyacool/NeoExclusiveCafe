<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once "../../../backend/pages/admin-includes/database.php";
}
require_once "../../../includes/session-manager.php";
require_once "../../../backend/pages/admin-includes/notifications/notification.php";

// Require user login
SessionManager::requireUserLogin('../../pages/auth/login-signup.php');

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: ../profile/profile.php');
    exit();
}

$order_id = trim($_GET['id']); // Keep as string for unique_order_id
$user_id = SessionManager::getUserId();

// Fetch order details with user verification first
$order_sql = "SELECT * FROM bulk_orders WHERE unique_order_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "si", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header('Location: ../profile/profile.php');
    exit();
}

// Handle proof of payment upload to Cloudinary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === 0) {
        // Load Cloudinary helper
        require_once __DIR__ . '/../../../backend/includes/cloudinary-helper.php';
        
        $file_extension = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $payment_type = isset($_POST['payment_type']) ? $_POST['payment_type'] : 'full';
            $timestamp = time();
            
            // Generate unique public ID
            $publicId = 'bulk_payment_' . $payment_type . '_' . $order_id . '_' . $timestamp;
            
            // Upload to Cloudinary
            $result = uploadToCloudinary($_FILES['proof_file']['tmp_name'], 'neocafe/bulk_payments', $publicId);
            
            if ($result['success']) {
                // Get existing proofs
                $existing_proofs = !empty($order['proof_of_payment']) ? json_decode($order['proof_of_payment'], true) : [];
                if (!is_array($existing_proofs)) {
                    $existing_proofs = [];
                }
                
                // Add new proof with Cloudinary info
                $new_proof = [
                    'filename' => basename($result['url']), // For backward compatibility
                    'cloud_url' => $result['url'],
                    'cloud_public_id' => $result['public_id'],
                    'cloud_provider' => 'cloudinary',
                    'type' => $payment_type,
                    'uploaded_at' => date('Y-m-d H:i:s'),
                    'original_name' => $_FILES['proof_file']['name']
                ];
                
                $existing_proofs[] = $new_proof;
                $proofs_json = json_encode($existing_proofs);
                
                $update_sql = "UPDATE bulk_orders SET proof_of_payment = ? WHERE unique_order_id = ? AND user_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ssi", $proofs_json, $order_id, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['flash_success'] = ucfirst($payment_type) . " payment proof uploaded successfully!";
                    // Refresh order data to show new proof
                    $order['proof_of_payment'] = $proofs_json;
                    
                    // Create admin notification for payment proof upload
                    try {
                        // Get order details for notification
                        $order_info_sql = "SELECT b.id, b.name, u.username FROM bulk_orders b 
                                          LEFT JOIN users u ON b.user_id = u.id 
                                          WHERE b.unique_order_id = ? AND b.user_id = ?";
                        $order_info_stmt = $conn->prepare($order_info_sql);
                        $order_info_stmt->bind_param("si", $order_id, $user_id);
                        $order_info_stmt->execute();
                        $order_info_result = $order_info_stmt->get_result();
                        
                        if ($order_info_row = $order_info_result->fetch_assoc()) {
                            $notificationHandler = new NotificationHandler($conn);
                            $notificationHandler->createBulkOrderNotification(
                                $order_info_row['id'],
                                'bulk_payment',
                                $order_info_row['name'],
                                $order_info_row['username']
                            );
                            error_log("✓ Admin notification created for bulk order payment proof upload");
                        }
                    } catch (Exception $notif_error) {
                        error_log("Failed to create payment proof notification: " . $notif_error->getMessage());
                    }
                    
                    // Redirect to prevent resubmission
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                } else {
                    $_SESSION['flash_error'] = "Error updating database.";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $_SESSION['flash_error'] = "Error uploading to cloud: " . $result['error'];
            }
        } else {
            $_SESSION['flash_error'] = "Invalid file type. Please upload JPG, PNG, or PDF files only.";
        }
    } else {
        $_SESSION['flash_error'] = "Please select a file to upload.";
    }
}

// Fetch order items from the bulk_order_items table
$items_sql = "SELECT boi.product_id, boi.product_name, boi.product_price, boi.quantity, boi.subtotal 
              FROM bulk_order_items boi 
              INNER JOIN bulk_orders bo ON boi.bulk_order_id = bo.id 
              WHERE bo.unique_order_id = ? AND bo.user_id = ?
              ORDER BY boi.id";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "si", $order_id, $user_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);

// Handle flash messages
$success_message = '';
$error_message = '';
if (isset($_SESSION['flash_success'])) {
    $success_message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error_message = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Details - NeoCafe</title>
    <link rel="stylesheet" href="bulk-order-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

    <div class="container">
        <div class="header">
            <h1>Bulk Order Details</h1>
            <div class="order-info">
                <span class="order-id">Order #<?php echo htmlspecialchars($order['unique_order_id']); ?></span>
                <span class="order-status status-<?php echo $order['status']; ?>">
                    <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($order['admin_updated']): ?>
            <div class="alert alert-info">
                <strong>Notice:</strong> This order has been updated by the administrator.
                <?php if ($order['admin_notes']): ?>
                    <br><strong>Admin Notes:</strong> <?php echo htmlspecialchars($order['admin_notes']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="details-grid">
            <!-- Customer Information -->
            <div class="details-section">
                <h2>Customer Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Name:</label>
                        <span><?php echo htmlspecialchars($order['name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Contact:</label>
                        <span><?php echo htmlspecialchars($order['contact']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Billing Address:</label>
                        <span><?php echo htmlspecialchars($order['billing_address']); ?></span>
                    </div>
                    <?php if ($order['delivery_address']): ?>
                    <div class="info-item">
                        <label>Delivery Address:</label>
                        <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Information -->
            <div class="details-section">
                <h2>Order Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Order Type:</label>
                        <span class="order-type-badge <?php echo $order['order_type']; ?>">
                            <?php echo ucfirst($order['order_type']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Purpose:</label>
                        <span><?php echo htmlspecialchars($order['purpose']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Date Needed:</label>
                        <span><?php echo date('F j, Y', strtotime($order['date_needed'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Time Needed:</label>
                        <span><?php echo date('g:i A', strtotime($order['time_needed'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Date Submitted:</label>
                        <span><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Total Items:</label>
                        <span><?php echo number_format($order['total_items']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Regular Total:</label>
                        <span class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <?php if ($order['discount_total'] && $order['discount_total'] > 0): ?>
                    <div class="info-item discount-total">
                        <label>Discounted Total:</label>
                        <span class="discount-amount">₱<?php echo number_format($order['discount_total'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <!-- Order Items -->
        <div class="details-section full-width">
            <h2>Order Items</h2>
            <div class="items-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Retail Price</th>
                            <?php 
                            // Check if any item has discount pricing
                            $has_discounts = false;
                            foreach ($items as $item) {
                                if (isset($item['discount_price']) && $item['discount_price'] && $item['discount_price'] > 0) {
                                    $has_discounts = true;
                                    break;
                                }
                            }
                            if ($has_discounts): ?>
                            <th>Discount Price</th>
                            <?php endif; ?>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <?php if ($has_discounts): ?>
                            <th>Discounted Subtotal</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td>₱<?php echo number_format($item['product_price'], 2); ?></td>
                                <?php if ($has_discounts): ?>
                                <td>
                                    <?php if (isset($item['discount_price']) && $item['discount_price'] && $item['discount_price'] > 0): ?>
                                        <span class="discount-price">₱<?php echo number_format($item['discount_price'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                <?php if ($has_discounts): ?>
                                <td>
                                    <?php if (isset($item['discount_price']) && $item['discount_price'] && $item['discount_price'] > 0): ?>
                                        <span class="discount-subtotal">₱<?php echo number_format($item['discount_price'] * $item['quantity'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $has_discounts ? '6' : '4'; ?>" style="text-align: center; color: #666;">No items found for this order</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="<?php echo $has_discounts ? '4' : '3'; ?>"><strong>Regular Total:</strong></td>
                            <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <?php if ($has_discounts): ?>
                            <td><span class="text-muted">-</span></td>
                            <?php endif; ?>
                        </tr>
                        <?php if ($order['discount_total'] && $order['discount_total'] > 0): ?>
                        <tr class="discount-total-row">
                            <td colspan="<?php echo $has_discounts ? '4' : '3'; ?>"><strong>Discounted Total:</strong></td>
                            <td><span class="text-muted">-</span></td>
                            <?php if ($has_discounts): ?>
                            <td><strong class="discount-total-amount">₱<?php echo number_format($order['discount_total'], 2); ?></strong></td>
                            <?php endif; ?>
                        </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($order['note']): ?>
        <div class="details-section full-width">
            <h2>Additional Notes</h2>
            <p><?php echo nl2br(htmlspecialchars($order['note'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Proof of Payment Section -->
        <?php if ($order['status'] === 'approved'): ?>
        <div class="details-section full-width">
            <h2>Proof of Payment</h2>
            <?php 
            $proofs = [];
            if (!empty($order['proof_of_payment'])) {
                $decoded_proofs = json_decode($order['proof_of_payment'], true);
                if (is_array($decoded_proofs)) {
                    $proofs = $decoded_proofs;
                } else {
                    // Handle old single file format
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
                <div class="proof-display">
                    <h3>Uploaded Proofs</h3>
                    <?php foreach ($proofs as $index => $proof): ?>
                        <div class="proof-item">
                            <div class="proof-info">
                                <p><strong>Type:</strong> <?php echo ucfirst($proof['type']); ?> Payment</p>
                                <p><strong>Uploaded:</strong> <?php echo $proof['uploaded_at']; ?></p>
                            </div>
                            <div class="proof-preview">
                                <?php 
                                // Prioritize cloud_url over legacy filename
                                if (!empty($proof['cloud_url'])) {
                                    $file_path = $proof['cloud_url'];
                                } else {
                                    // Fallback to local file for backward compatibility
                                    $file_path = "../../../assets/bulk_payments/" . $proof['filename'];
                                }
                                $file_extension = strtolower(pathinfo($proof['filename'], PATHINFO_EXTENSION));
                                ?>
                                <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
                                    <img src="<?php echo htmlspecialchars($file_path); ?>" alt="Proof of Payment">
                                <?php else: ?>
                                    <p><a href="<?php echo htmlspecialchars($file_path); ?>" target="_blank" class="btn btn-secondary">View PDF</a></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="additional-upload">
                    <h4>Upload Additional Proof</h4>
                    <p>You can upload additional proofs or replace incorrect ones.</p>
            <?php else: ?>
                <div class="proof-upload">
                    <p>Your order has been approved. Please upload your proof of payment.</p>
            <?php endif; ?>
            
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="payment_type">Payment Type:</label>
                            <select name="payment_type" id="payment_type" required>
                                <option value="">Select payment type</option>
                                <option value="full">Full Payment</option>
                                <option value="downpayment">Downpayment</option>
                                <option value="remaining">Remaining Balance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="proof_file">Select proof of payment file (JPG, PNG, PDF):</label>
                            <input type="file" name="proof_file" id="proof_file" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                        <button type="submit" name="upload_proof" class="btn btn-primary">Upload Proof</button>
                    </form>
            <?php if (!empty($proofs)): ?>
                </div>
            <?php else: ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
