<?php
session_start();
require_once "../../../backend/pages/admin-includes/database.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/auth/login-signup.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: ../profile/profile.php');
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Handle proof of payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === 0) {
        $upload_dir = '../../../assets/bulk_payments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = 'bulk_payment_' . $order_id . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $filepath)) {
                $update_sql = "UPDATE bulk_orders SET proof_of_payment = ? WHERE id = ? AND user_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "sii", $filename, $order_id, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success_message = "Proof of payment uploaded successfully!";
                } else {
                    $error_message = "Error updating database.";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $error_message = "Error uploading file.";
            }
        } else {
            $error_message = "Invalid file type. Please upload JPG, PNG, or PDF files only.";
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}

// Fetch order details with user verification
$order_sql = "SELECT * FROM bulk_orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header('Location: ../profile/profile.php');
    exit();
}

// Fetch order items
$items_sql = "SELECT * FROM bulk_order_items WHERE bulk_order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);

// Debug: Check if items were found
$items_count = count($items);
if ($items_count == 0) {
    $debug_message = "No items found for bulk order ID: " . $order_id;
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
    
    <div class="container">
        <div class="header">
            <h1>Bulk Order Details</h1>
            <div class="order-info">
                <span class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                <span class="order-status status-<?php echo $order['status']; ?>">
                    <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                </span>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($debug_message)): ?>
            <div class="alert alert-warning"><?php echo $debug_message; ?></div>
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
                        <label>Total Amount:</label>
                        <span class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="details-section">
            <h2>Order Items</h2>
            <div class="items-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
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
                                <td><?php echo $item['quantity']; ?></td>
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
                            <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($order['note']): ?>
        <div class="details-section">
            <h2>Additional Notes</h2>
            <p><?php echo nl2br(htmlspecialchars($order['note'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Proof of Payment Section -->
        <?php if ($order['status'] === 'approved'): ?>
        <div class="details-section">
            <h2>Proof of Payment</h2>
            <?php if ($order['proof_of_payment']): ?>
                <div class="proof-display">
                    <p><strong>Proof of payment has been uploaded.</strong></p>
                    <img src="../../../assets/bulk_payments/<?php echo $order['proof_of_payment']; ?>" alt="Proof of Payment" style="max-width: 300px; height: auto;">
                </div>
            <?php else: ?>
                <div class="proof-upload">
                    <p>Your order has been approved. Please upload your proof of payment.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="proof_file">Select proof of payment file (JPG, PNG, PDF):</label>
                            <input type="file" name="proof_file" id="proof_file" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                        <button type="submit" name="upload_proof" class="btn btn-primary">Upload Proof</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="actions">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='../profile/profile.php'">Back to Profile</button>
            <button type="button" class="btn btn-primary" onclick="window.print()">Print Order</button>
        </div>
    </div>
</body>
</html>
