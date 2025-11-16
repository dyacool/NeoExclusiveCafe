<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once '../../../backend/pages/admin-includes/database.php';
}
require_once '../../../includes/session-manager.php';

// Require user login
SessionManager::requireUserLogin('../../login/user/login-signup.php');

$user_id = SessionManager::getUserId();
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    die("Invalid order ID.");
}

// Fetch the order, ensuring it belongs to the logged-in user
$order_query = "SELECT * FROM orders WHERE order_id = ? AND (customer_id = ? OR customer_email = (SELECT email FROM users WHERE id = ?)) LIMIT 1";
$stmt = $conn->prepare($order_query);
$stmt->bind_param('iii', $order_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found or access denied.");
}

// Fetch order items with item_id and try to get product_id by matching product name
$items_query = "SELECT oi.item_id, oi.product_name, oi.quantity, oi.price, 
                p.id as product_id
                FROM order_items oi
                LEFT JOIN products p ON oi.product_name = p.name AND p.deleted_at IS NULL
                WHERE oi.order_id = ? 
                ORDER BY oi.item_id ASC";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
$items_stmt->close();

// Check if refund request exists (check if table exists first)
$refund = null;
$table_check = "SHOW TABLES LIKE 'order_refunds'";
$table_result = $conn->query($table_check);

if ($table_result && $table_result->num_rows > 0) {
    $refund_query = "SELECT r.*, rv.voucher_code as refund_coupon_code, rv.amount as refund_coupon_amount, rv.expiry_date as refund_coupon_expiry 
                     FROM order_refunds r 
                     LEFT JOIN refund_vouchers rv ON r.refund_id = rv.refund_id 
                     WHERE r.order_id = ? AND r.user_id = ? 
                     LIMIT 1";
    $refund_stmt = $conn->prepare($refund_query);
    if ($refund_stmt) {
        $refund_stmt->bind_param('ii', $order_id, $user_id);
        $refund_stmt->execute();
        $refund_result = $refund_stmt->get_result();
        $refund = $refund_result->fetch_assoc();
        $refund_stmt->close();
    }
}

// Determine if order is eligible for refund and review
$status_lower = strtolower(trim($order['status']));
// Normalize status variations (handle spaces, hyphens, underscores)
$status_normalized = preg_replace('/[\s\-_]+/', '-', $status_lower);

$can_request_refund = in_array($status_normalized, ['delivered', 'picked-up']) && !$refund;

// Allow reviews for completed orders - check multiple status variations
$reviewable_statuses = ['delivered', 'picked-up', 'pickedup', 'completed', 'picked_up'];
$can_write_review = in_array($status_normalized, $reviewable_statuses) || 
                    in_array($status_lower, $reviewable_statuses) ||
                    strpos($status_lower, 'delivered') !== false ||
                    strpos($status_lower, 'picked') !== false ||
                    strpos($status_lower, 'completed') !== false;

// Get proof of delivery if exists
$pod = null;
$pod_query = "SELECT * FROM pod_orders WHERE order_id = ?";
$pod_stmt = $conn->prepare($pod_query);
if ($pod_stmt) {
    $pod_stmt->bind_param('i', $order_id);
    $pod_stmt->execute();
    $pod_result = $pod_stmt->get_result();
    $pod = $pod_result->fetch_assoc();
    $pod_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - NeoCafe</title>
    <link rel="stylesheet" href="../bulk/bulk-order-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .refund-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .refund-modal.show {
            display: flex !important;
        }

        .refund-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .refund-modal-header {
            background: linear-gradient(135deg, #0f5132, #2d5a27);
            color: white;
            padding: 24px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .refund-modal-header h2 {
            margin: 0;
            font-family: 'Spectral', serif;
            font-size: 24px;
        }

        .refund-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .refund-modal-close:hover {
            transform: scale(1.1);
        }

        .refund-modal-body {
            padding: 24px;
        }

        .refund-form-group {
            margin-bottom: 20px;
        }

        .refund-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f5132;
            font-family: 'Spectral', serif;
        }

        .refund-form-group select,
        .refund-form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #cbd5c0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .refund-form-group select:focus,
        .refund-form-group textarea:focus {
            outline: none;
            border-color: #0f5132;
        }

        .refund-items-list {
            border: 2px solid #cbd5c0;
            border-radius: 8px;
            padding: 16px;
            max-height: 250px;
            overflow-y: auto;
        }

        #qty_input{
            display: flex;
            gap: 12px;
            align-items: center;

        }

        .refund-item-checkbox {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .refund-item-checkbox:last-child {
            border-bottom: none;
        }

        .refund-item-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            cursor: pointer;
        }

        .refund-item-info {
            flex: 1;
        }

        .refund-item-name {
            font-weight: 600;
            color: #333;
        }

        .refund-item-details {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }

        .refund-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .refund-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .refund-summary-row:last-child {
            margin-bottom: 0;
            font-weight: 700;
            font-size: 16px;
            color: #0f5132;
            padding-top: 8px;
            border-top: 2px solid #cbd5c0;
        }

        .refund-file-upload {
            border: 2px dashed #cbd5c0;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .refund-file-upload:hover {
            border-color: #0f5132;
            background: #f8f9fa;
        }

        .refund-file-upload input[type="file"] {
            display: none;
        }

        .refund-file-preview {
            margin-top: 12px;
        }

        .refund-file-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
        }

        .refund-submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0f5132, #2d5a27);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .refund-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(15, 81, 50, 0.3);
        }

        .refund-submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .refund-status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .refund-status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .refund-status-approved {
            background: #d1e7dd;
            color: #0f5132;
        }

        .refund-status-rejected {
            background: #f8d7da;
            color: #842029;
        }

        .refund-status-completed {
            background: #d1e7dd;
            color: #0a3622;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin: 24px 0;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0f5132, #2d5a27);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(15, 81, 50, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #0f5132;
            border: 2px solid #0f5132;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .refund-details-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-top: 24px;
        }

        .refund-details-section h2 {
            color: #0f5132;
            margin-bottom: 20px;
            font-family: 'Spectral', serif;
        }

        .refund-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .refund-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .refund-info-item label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
        }

        .refund-info-item span {
            color: #333;
            font-size: 15px;
        }

        .refund-proof-image {
            margin-top: 16px;
        }

        .refund-proof-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .voucher-info-container {
            background: #f8f9fa;
            border: 2px solid #0f5132;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }

        .voucher-code-display {
            font-size: 18px;
            font-weight: 700;
            color: #0f5132;
            font-family: 'Monaco', monospace;
            text-align: center;
            margin-bottom: 12px;
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px dashed #0f5132;
        }

        .voucher-amount-display {
            text-align: center;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

    <div class="container">
        <div class="header">
            <h1>Order Details</h1>
            <div class="order-info">
                <span class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></span>
                <span class="order-status status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                    <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                </span>
            </div>
        </div>

        <div class="details-grid">
            <!-- Order Information -->
            <div class="details-section">
                <h2>Order Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Order Date:</label>
                        <span><?php echo date('F j, Y, g:i A', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Delivery Method:</label>
                        <span class="order-type-badge <?php echo strtolower($order['delivery_method'] ?? 'pickup'); ?>">
                            <?php echo htmlspecialchars($order['delivery_method'] ?? 'Pickup'); ?>
                        </span>
                    </div>
                    <?php if (!empty($order['pickup_date'])): ?>
                    <div class="info-item">
                        <label>Pickup Date:</label>
                        <span><?php echo date('F j, Y', strtotime($order['pickup_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['delivery_date'])): ?>
                    <div class="info-item">
                        <label>Delivery Date:</label>
                        <span><?php echo date('F j, Y', strtotime($order['delivery_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['pickup_time'])): ?>
                    <div class="info-item">
                        <label>Pickup/Delivery Time:</label>
                        <span><?php echo date('g:i A', strtotime($order['pickup_time'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <label>Total Amount:</label>
                        <span class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="details-section">
                <h2>Customer Information</h2>
                <div class="info-grid">
                    <?php if (!empty($order['customer_name'])): ?>
                    <div class="info-item">
                        <label>Name:</label>
                        <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['customer_email'])): ?>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($order['customer_email']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['customer_phone'])): ?>
                    <div class="info-item">
                        <label>Contact:</label>
                        <span><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['delivery_address'])): ?>
                    <div class="info-item">
                        <label>Delivery Address:</label>
                        <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
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
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($items as $item): 
                            $price = floatval($item['price']);
                            $quantity = intval($item['quantity']);
                            $itemTotal = $price * $quantity;
                            $subtotal += $itemTotal;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td>₱<?php echo number_format($price, 2); ?></td>
                                <td><?php echo $quantity; ?></td>
                                <td>₱<?php echo number_format($itemTotal, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Total Amount:</strong></td>
                            <td><strong>₱<?php echo number_format($subtotal, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <?php if (!empty($order['notes'])): ?>
        <div class="details-section full-width">
            <h2>Additional Notes</h2>
            <div class="info-grid">
                <p style="padding: 0 12px;"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Proof of Delivery -->
        <?php if ($pod && !empty($pod['proof_image_path'])): 
            // Check if it's a Cloudinary URL or local path
            $proof_url = (strpos($pod['proof_image_path'], 'http') === 0) 
                ? $pod['proof_image_path'] 
                : '../../../' . $pod['proof_image_path'];
        ?>
        <div class="details-section full-width">
            <h2>Proof of Delivery</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Delivered On:</label>
                    <span><?php echo date('F j, Y, g:i A', strtotime($pod['submitted_at'])); ?></span>
                </div>
                <div class="info-item">
                    <label>Submitted By:</label>
                    <span><?php echo htmlspecialchars($pod['submitted_by'] ?? 'Delivery Rider'); ?></span>
                </div>
            </div>
            <div style="margin-top: 20px; text-align: center;">
                <a href="<?php echo htmlspecialchars($proof_url); ?>" target="_blank" style="display: inline-block;">
                    <img src="<?php echo htmlspecialchars($proof_url); ?>" 
                         alt="Proof of Delivery" 
                         style="max-width: 100%; max-height: 500px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; transition: transform 0.2s;"
                         onmouseover="this.style.transform='scale(1.02)'"
                         onmouseout="this.style.transform='scale(1)'"
                         onerror="this.src='https://res.cloudinary.com/dvdccumbs/image/upload/c_fill,w_400,h_400,g_center/e_blur:1000,co_rgb:cccccc,b_rgb:f0f0f0/sample.jpg'">
                </a>
                <p style="margin-top: 12px; font-size: 14px; color: #666;">
                    <i class="fas fa-search-plus"></i> Click image to view full size
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            
            <?php 
            // Debug: Check status values (view page source to see)
            echo "<!-- Debug: Order Status = " . htmlspecialchars($order['status']) . " -->";
            echo "<!-- Debug: Status Lower = " . htmlspecialchars($status_lower) . " -->";
            echo "<!-- Debug: Status Normalized = " . htmlspecialchars($status_normalized) . " -->";
            echo "<!-- Debug: Can Write Review = " . ($can_write_review ? 'true' : 'false') . " -->";
            
            if ($can_write_review): ?>
            <button onclick="openReviewModal()" class="btn btn-primary" style="background-color: #1a4a28; margin-right: 10px;">
                <i class="fas fa-star"></i> Write a Review
            </button>
            <?php endif; ?>
            
            <?php if ($refund): ?>
            <button onclick="openRefundDetailsModal()" class="btn btn-primary">
                View Refund Request
            </button>
            <?php elseif ($can_request_refund): ?>
            <button onclick="openRefundModal()" class="btn btn-primary">
                Request Refund
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Refund Modal -->
    <div id="refundModal" class="refund-modal">
        <div class="refund-modal-content">
            <div class="refund-modal-header">
                <h2>Request Refund</h2>
                <button type="button" class="refund-modal-close" onclick="closeRefundModal()">&times;</button>
            </div>
            <div class="refund-modal-body">
                <form id="refundForm" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    
                    <!-- Refund Reason -->
                    <div class="refund-form-group">
                        <label for="refund_reason">Reason for Refund *</label>
                        <select id="refund_reason" name="refund_reason" required>
                            <option value="">Select a reason</option>
                            <option value="spoiled">Product Spoiled</option>
                            <option value="wrong_item">Wrong Item Received</option>
                            <option value="damaged">Product Damaged During Delivery</option>
                            <option value="other">Other</option>

                        </select>
                    </div>

                    <!-- Select Items -->
                    <div class="refund-form-group">
                        <label>Select Items to Refund *</label>
                        <div class="refund-items-list" id="refundItemsList">
                            <?php foreach ($items as $item): ?>
                            <div class="refund-item-checkbox">
                                <input type="checkbox" 
                                       id="item_<?php echo $item['item_id']; ?>" 
                                       name="refund_items_check[]" 
                                       value="<?php echo $item['item_id']; ?>"
                                       data-item-id="<?php echo $item['item_id']; ?>"
                                       data-product-name="<?php echo htmlspecialchars($item['product_name']); ?>"
                                       data-max-qty="<?php echo $item['quantity']; ?>"
                                       data-price="<?php echo $item['price']; ?>"
                                       onchange="toggleQuantityInput(<?php echo $item['item_id']; ?>); updateRefundSummary();">
                                <div class="refund-item-info">
                                    <div class="refund-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="refund-item-details">
                                        Ordered Quantity: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?> = 
                                        ₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                                    </div>
                                    <div class="refund-qty-input" id="qty_input_<?php echo $item['item_id']; ?>" style="display: none; margin-top: 8px; gap: 10px; align-items: center;">
                                        <label style="font-size: 13px; color: #666; margin-bottom: 4px; display: block;">Quantity to Refund:</label>
                                        <input type="number" 
                                               id="refund_qty_<?php echo $item['item_id']; ?>"
                                               name="refund_qty[<?php echo $item['item_id']; ?>]"
                                               min="1" 
                                               max="<?php echo $item['quantity']; ?>" 
                                               value="<?php echo $item['quantity']; ?>"
                                               style="width: 80px; padding: 3px; border: 2px solid #cbd5c0; border-radius: 6px; font-size: 14px;"
                                               onchange="updateRefundSummary()"
                                               oninput="validateQuantity(<?php echo $item['item_id']; ?>, <?php echo $item['quantity']; ?>)">
                                        <span style="font-size: 12px; color: #666; margin-left: 8px;">Max: <?php echo $item['quantity']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Proof Image Upload -->
                    <div class="refund-form-group">
                        <label>Upload Proof Image *</label>
                        <div class="refund-file-upload" id="fileUploadArea" onclick="document.getElementById('proof_image').click()">
                            <p style="margin: 0; color: #666;">Click to upload proof image</p>
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #999;">JPG, PNG, GIF (Max 5MB)</p>
                        </div>
                        <input type="file" 
                               id="proof_image" 
                               name="proof_image" 
                               accept="image/*" 
                               required
                               style="display: none;"
                               onchange="previewProofImage(event)">
                    </div>

                    <!-- Additional Notes -->
                    <div class="refund-form-group">
                        <label for="refund_note">Additional Details (Optional)</label>
                        <textarea id="refund_note" 
                                  name="refund_note" 
                                  rows="4" 
                                  placeholder="Please provide any additional details about the issue..."></textarea>
                    </div>

                    <!-- Refund Summary -->
                    <div class="refund-summary" id="refundSummary" style="display: none;">
                        <div class="refund-summary-row">
                            <span>Selected Items:</span>
                            <span id="selectedItemsCount">0</span>
                        </div>
                        <div class="refund-summary-row">
                            <span>Total Refund Amount:</span>
                            <span id="totalRefundAmount">₱0.00</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="refund-submit-btn" id="refundSubmitBtn" disabled>
                        Submit Refund Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRefundModal() {
            document.getElementById('refundModal').classList.add('show');
        }

        function closeRefundModal() {
            document.getElementById('refundModal').classList.remove('show');
            document.getElementById('refundForm').reset();
            updateRefundSummary();
        }

        function toggleQuantityInput(itemId) {
            const checkbox = document.getElementById('item_' + itemId);
            const qtyInput = document.getElementById('qty_input_' + itemId);
            
            if (checkbox.checked) {
                qtyInput.style.display = 'flex';
            } else {
                qtyInput.style.display = 'none';
            }
        }

        function validateQuantity(itemId, maxQty) {
            const qtyInput = document.getElementById('refund_qty_' + itemId);
            let value = parseInt(qtyInput.value);
            
            if (value < 1) {
                qtyInput.value = 1;
            } else if (value > maxQty) {
                qtyInput.value = maxQty;
            }
            
            updateRefundSummary();
        }

        function updateRefundSummary() {
            const checkboxes = document.querySelectorAll('input[name="refund_items_check[]"]:checked');
            const submitBtn = document.getElementById('refundSubmitBtn');
            const summary = document.getElementById('refundSummary');
            
            if (checkboxes.length > 0) {
                let totalAmount = 0;
                let totalItems = 0;
                
                checkboxes.forEach(cb => {
                    const itemId = cb.dataset.itemId;
                    const price = parseFloat(cb.dataset.price);
                    const qtyInput = document.getElementById('refund_qty_' + itemId);
                    const qty = parseInt(qtyInput.value) || 1;
                    
                    totalAmount += price * qty;
                    totalItems++;
                });
                
                document.getElementById('selectedItemsCount').textContent = totalItems;
                document.getElementById('totalRefundAmount').textContent = '₱' + totalAmount.toFixed(2);
                summary.style.display = 'block';
                
                // Enable submit button if items selected and proof image uploaded
                const proofImage = document.getElementById('proof_image');
                if (proofImage.files.length > 0) {
                    submitBtn.disabled = false;
                }
            } else {
                summary.style.display = 'none';
                submitBtn.disabled = true;
            }
        }

        function previewProofImage(event) {
            const file = event.target.files[0];
            const uploadArea = document.getElementById('fileUploadArea');
            const submitBtn = document.getElementById('refundSubmitBtn');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    uploadArea.innerHTML = `
                        <div style="position: relative;">
                            <img src="${e.target.result}" alt="Proof Preview" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
                            <button type="button" 
                                    id="removeImageBtn"
                                    style="position: absolute; top: 8px; right: 8px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                ×
                            </button>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #666; text-align: center;">
                                Image uploaded successfully
                            </p>
                        </div>
                    `;
                    uploadArea.style.cursor = 'default';
                    uploadArea.onclick = null;
                    
                    // Add event listener to remove button
                    document.getElementById('removeImageBtn').addEventListener('click', function(e) {
                        e.stopPropagation();
                        removeProofImage();
                    });
                    
                    // Enable submit button if items also selected
                    const checkboxes = document.querySelectorAll('input[name="refund_items_check[]"]:checked');
                    if (checkboxes.length > 0) {
                        submitBtn.disabled = false;
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function removeProofImage() {
            const uploadArea = document.getElementById('fileUploadArea');
            const proofInput = document.getElementById('proof_image');
            const submitBtn = document.getElementById('refundSubmitBtn');
            
            // Reset file input
            proofInput.value = '';
            
            // Restore original upload area
            uploadArea.innerHTML = `
                <p style="margin: 0; color: #666;">Click to upload proof image</p>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #999;">JPG, PNG, GIF (Max 5MB)</p>
            `;
            uploadArea.style.cursor = 'pointer';
            uploadArea.onclick = function() { document.getElementById('proof_image').click(); };
            
            // Disable submit button
            submitBtn.disabled = true;
        }

        document.getElementById('refundForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('refundSubmitBtn');
            
            // Get selected items with their refund quantities
            const selectedItems = [];
            const checkboxes = document.querySelectorAll('input[name="refund_items_check[]"]:checked');
            checkboxes.forEach(cb => {
                const itemId = cb.dataset.itemId;
                const qtyInput = document.getElementById('refund_qty_' + itemId);
                const refundQty = parseInt(qtyInput.value) || 1;
                
                selectedItems.push({
                    item_id: itemId,
                    product_name: cb.dataset.productName,
                    quantity: refundQty,
                    price: parseFloat(cb.dataset.price)
                });
            });
            
            // Remove checkbox values and add JSON data
            formData.delete('refund_items_check[]');
            for (let key of formData.keys()) {
                if (key.startsWith('refund_qty[')) {
                    formData.delete(key);
                }
            }
            formData.append('refund_items', JSON.stringify(selectedItems));
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Submitting...';
            
            try {
                const response = await fetch('../../../backend/pages/cart/submit-refund.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Refund request submitted successfully! We will review your request and get back to you soon.');
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Refund Request';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while submitting the refund request. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Refund Request';
            }
        });

        // Close modal when clicking outside
        document.getElementById('refundModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRefundModal();
            }
        });
    </script>

    <!-- Refund Details Modal -->
    <?php if ($refund): ?>
    <div id="refundDetailsModal" class="refund-modal">
        <div class="refund-modal-content">
            <div class="refund-modal-header">
                <h2>Refund Request Details</h2>
                <button type="button" class="refund-modal-close" onclick="closeRefundDetailsModal()">&times;</button>
            </div>
            <div class="refund-modal-body">
                <div class="refund-info-grid" style="margin-bottom: 24px;">
                    <div class="refund-info-item">
                        <label>Status:</label>
                        <span class="refund-status-badge refund-status-<?php echo htmlspecialchars($refund['refund_status']); ?>">
                            <?php echo htmlspecialchars(ucfirst($refund['refund_status'])); ?>
                        </span>
                    </div>
                    <div class="refund-info-item">
                        <label>Reason:</label>
                        <span><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $refund['refund_reason']))); ?></span>
                    </div>
                    <div class="refund-info-item">
                        <label>Refund Amount:</label>
                        <span style="font-weight: 700; color: #0f5132;">₱<?php echo number_format($refund['refund_amount'], 2); ?></span>
                    </div>
                    <div class="refund-info-item">
                        <label>Submitted On:</label>
                        <span><?php echo date('F j, Y, g:i A', strtotime($refund['created_at'])); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($refund['refund_note'])): ?>
                <div class="refund-form-group">
                    <label>Your Note:</label>
                    <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; color: #333;">
                        <?php echo nl2br(htmlspecialchars($refund['refund_note'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($refund['admin_notes'])): ?>
                <div class="refund-form-group">
                    <label>Admin Response:</label>
                    <div style="padding: 12px; background: #f0fdf4; border: 2px solid #0f5132; border-radius: 8px; color: #0f5132;">
                        <?php echo nl2br(htmlspecialchars($refund['admin_notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($refund['refund_coupon_code'])): ?>
                <div class="refund-form-group">
                    <label>Refund Voucher Information:</label>
                    <div class="voucher-info-container">
                        <div class="voucher-code-display">
                            <?php echo htmlspecialchars($refund['refund_coupon_code']); ?>
                        </div>
                        <div class="voucher-amount-display">
                            <strong>Amount:</strong> ₱<?php echo number_format($refund['refund_coupon_amount'], 2); ?>
                        </div>
                        <?php if (!empty($refund['refund_coupon_expiry'])): ?>
                        <div class="voucher-amount-display" style="margin-top: 8px; font-size: 14px; color: #666;">
                            <strong>Expires:</strong> <?php echo date('F j, Y', strtotime($refund['refund_coupon_expiry'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                

                <div class="refund-form-group">
                    <label>Items Requested for Refund:</label>
                    <div class="refund-items-list" style="max-height: 200px;">
                        <?php 
                        $refund_items = json_decode($refund['refund_items'], true);
                        if (is_array($refund_items)):
                            foreach ($refund_items as $item): 
                        ?>
                        <div class="refund-item-checkbox" style="border-bottom: 1px solid #eee; padding: 12px;">
                            <div class="refund-item-info">
                                <div class="refund-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="refund-item-details">
                                    Quantity: <?php echo intval($item['quantity']); ?> × ₱<?php echo number_format(floatval($item['price']), 2); ?> = 
                                    ₱<?php echo number_format(floatval($item['price']) * intval($item['quantity']), 2); ?>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </div>
                </div>

                <?php 
                // Check for refund proof image - prioritize cloud_url
                $refund_proof_url = '';
                if (!empty($refund['cloud_url'])) {
                    $refund_proof_url = $refund['cloud_url'];
                } elseif (!empty($refund['proof_image'])) {
                    // Fallback to local image for backward compatibility
                    $refund_proof_url = '../../../' . $refund['proof_image'];
                }
                
                if (!empty($refund_proof_url)): 
                ?>
                <div class="refund-form-group">
                    <label>Proof Image:</label>
                    <div style="text-align: center; margin-top: 12px;">
                        <a href="<?php echo htmlspecialchars($refund_proof_url); ?>" target="_blank" style="display: inline-block;">
                            <img src="<?php echo htmlspecialchars($refund_proof_url); ?>" 
                                 alt="Refund Proof" 
                                 style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;"
                                 onmouseover="this.style.transform='scale(1.02)'"
                                 onmouseout="this.style.transform='scale(1)'">
                        </a>
                        <p style="margin-top: 8px; font-size: 13px; color: #666;">
                            Click image to view full size
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <button type="button" class="refund-submit-btn" onclick="closeRefundDetailsModal()">
                     Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function openRefundDetailsModal() {
            document.getElementById('refundDetailsModal').classList.add('show');
        }

        function closeRefundDetailsModal() {
            document.getElementById('refundDetailsModal').classList.remove('show');
        }

        // Close modal when clicking outside
        document.getElementById('refundDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRefundDetailsModal();
            }
        });

        // Auto-open refund details modal if hash is present
        if (window.location.hash === '#view-refund') {
            openRefundDetailsModal();
        }
    </script>
    <?php endif; ?>

    <!-- Review Modal -->
    <?php if ($can_write_review): ?>
    <div id="reviewModal" class="refund-modal">
        <div class="refund-modal-content" style="max-width: 800px;">
            <div class="refund-modal-header">
                <h2>Write a Review</h2>
                <button type="button" class="refund-modal-close" onclick="closeReviewModal()">&times;</button>
            </div>
            <div class="refund-modal-body">
                <p style="margin-bottom: 20px; color: #666;">Share your experience with the products from this order:</p>
                
                <div id="reviewProductsList">
                    <?php 
                    $reviewable_items = array_filter($items, function($item) {
                        return !empty($item['product_id']);
                    });
                    
                    if (empty($reviewable_items)): ?>
                        <p style="text-align: center; color: #999; padding: 20px;">
                            No products available for review at this time.
                        </p>
                    <?php else: ?>
                        <?php foreach ($reviewable_items as $item): 
                            // Check if user has already reviewed this product
                            $review_check = $conn->prepare("SELECT id, rating, review_text FROM product_reviews WHERE user_id = ? AND product_id = ?");
                            $review_check->bind_param("ii", $user_id, $item['product_id']);
                            $review_check->execute();
                            $review_result = $review_check->get_result();
                            $existing_review = $review_result->fetch_assoc();
                            $review_check->close();
                        ?>
                        <div class="review-product-item" data-product-id="<?php echo $item['product_id']; ?>" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #f9fafb;">
                            <h3 style="margin-top: 0; margin-bottom: 15px; color: #1a4a28;">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </h3>
                            
                            <div class="review-form" data-product-id="<?php echo $item['product_id']; ?>">
                                <div class="rating-input" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Rating:</label>
                                    <div class="star-rating" style="display: flex; gap: 5px; cursor: pointer;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star" data-rating="<?php echo $i; ?>" style="font-size: 28px; color: #e5e7eb; transition: color 0.2s; user-select: none;">☆</span>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" class="review-rating-input" name="rating" value="<?php echo $existing_review ? $existing_review['rating'] : '0'; ?>">
                                </div>
                                
                                <div class="review-text-input" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Your Review (optional):</label>
                                    <textarea class="review-text-input-field" rows="4" placeholder="Share your experience with this product..." maxlength="2000" style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical;"><?php echo $existing_review ? htmlspecialchars($existing_review['review_text']) : ''; ?></textarea>
                                    <span class="char-count" style="display: block; text-align: right; font-size: 12px; color: #6b7280; margin-top: 5px;">
                                        <span class="char-count-value"><?php echo $existing_review ? strlen($existing_review['review_text']) : '0'; ?></span>/2000
                                    </span>
                                </div>
                                
                                <button type="button" class="submit-review-btn" onclick="submitProductReview(<?php echo $item['product_id']; ?>, <?php echo $order_id; ?>)" style="background-color: #1a4a28; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.3s;">
                                    <?php echo $existing_review ? 'Update Review' : 'Submit Review'; ?>
                                </button>
                                
                                <?php if ($existing_review): ?>
                                <span style="margin-left: 10px; color: #6b7280; font-size: 12px;">
                                    <i class="fas fa-check-circle"></i> You've already reviewed this product
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Review Modal Functions
        function openReviewModal() {
            document.getElementById('reviewModal').classList.add('show');
            initializeReviewForms();
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('show');
        }

        function initializeReviewForms() {
            // Initialize star ratings
            document.querySelectorAll('.star-rating').forEach(ratingContainer => {
                const stars = ratingContainer.querySelectorAll('.star');
                const ratingInput = ratingContainer.parentElement.querySelector('.review-rating-input');
                const currentRating = parseInt(ratingInput.value) || 0;
                
                // Set initial rating
                updateStarDisplay(stars, currentRating);
                
                stars.forEach(star => {
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.dataset.rating);
                        ratingInput.value = rating;
                        updateStarDisplay(stars, rating);
                    });
                    
                    star.addEventListener('mouseenter', function() {
                        const rating = parseInt(this.dataset.rating);
                        updateStarDisplay(stars, rating);
                    });
                });
                
                ratingContainer.addEventListener('mouseleave', function() {
                    const currentRating = parseInt(ratingInput.value) || 0;
                    updateStarDisplay(stars, currentRating);
                });
            });
            
            // Initialize character counters
            document.querySelectorAll('.review-text-input-field').forEach(textarea => {
                const charCount = textarea.parentElement.querySelector('.char-count-value');
                updateCharCount(textarea, charCount);
                
                textarea.addEventListener('input', function() {
                    updateCharCount(this, charCount);
                });
            });
        }

        function updateStarDisplay(stars, rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.textContent = '★';
                    star.style.color = '#ffc107';
                } else {
                    star.textContent = '☆';
                    star.style.color = '#e5e7eb';
                }
            });
        }

        function updateCharCount(textarea, charCountElement) {
            charCountElement.textContent = textarea.value.length;
        }

        async function submitProductReview(productId, orderId) {
            const reviewForm = document.querySelector(`.review-form[data-product-id="${productId}"]`);
            const ratingInput = reviewForm.querySelector('.review-rating-input');
            const reviewText = reviewForm.querySelector('.review-text-input-field').value.trim();
            const submitBtn = reviewForm.querySelector('.submit-review-btn');
            
            const rating = parseInt(ratingInput.value);
            
            if (rating === 0) {
                alert('Please select a rating (1-5 stars)');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            try {
                const response = await fetch('../../api/submit-review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        rating: rating,
                        review_text: reviewText,
                        order_id: orderId
                    })
                });
                
                // Check if response is ok
                let data;
                try {
                    const responseText = await response.text();
                    console.log('API Response Text:', responseText);
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse JSON response:', parseError);
                    throw new Error('Invalid response from server. Please check console for details.');
                }
                
                if (!response.ok) {
                    console.error('API Error Response:', data);
                    throw new Error(data.message || 'Server error: ' + response.status);
                }
                
                if (data.success) {
                    alert('Review submitted successfully!');
                    submitBtn.textContent = 'Review Updated';
                    submitBtn.style.backgroundColor = '#22c55e';
                    
                    // Update the form to show it's been reviewed
                    const reviewItem = reviewForm.closest('.review-product-item');
                    if (!reviewItem.querySelector('.reviewed-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'reviewed-badge';
                        badge.style.cssText = 'margin-left: 10px; color: #22c55e; font-size: 12px;';
                        badge.innerHTML = '<i class="fas fa-check-circle"></i> Review submitted';
                        submitBtn.parentElement.appendChild(badge);
                    }
                } else {
                    const errorMsg = data.message || 'Failed to submit review';
                    console.error('Review submission failed:', data);
                    alert('Error: ' + errorMsg + (data.error_code ? ' (Error code: ' + data.error_code + ')' : ''));
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.textContent.includes('Update') ? 'Update Review' : 'Submit Review';
                }
            } catch (error) {
                console.error('Error:', error);
                console.error('Error details:', error.message);
                alert('An error occurred while submitting your review: ' + error.message + '. Please check the console for details.');
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.textContent.includes('Update') ? 'Update Review' : 'Submit Review';
            }
        }

        // Close modal when clicking outside
        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReviewModal();
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>