<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();
require_once '../../../backend/pages/admin-includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login/user/login-signup.php');
    exit();
}

$user_id = $_SESSION['user_id'];
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

// Fetch order items with item_id
$items_query = "SELECT item_id, product_name, quantity, price FROM order_items WHERE order_id = ? ORDER BY item_id ASC";
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
    $refund_query = "SELECT * FROM order_refunds WHERE order_id = ? AND user_id = ? LIMIT 1";
    $refund_stmt = $conn->prepare($refund_query);
    if ($refund_stmt) {
        $refund_stmt->bind_param('ii', $order_id, $user_id);
        $refund_stmt->execute();
        $refund_result = $refund_stmt->get_result();
        $refund = $refund_result->fetch_assoc();
        $refund_stmt->close();
    }
}

// Determine if order is eligible for refund
$status_lower = strtolower($order['status']);
$can_request_refund = ($status_lower === 'delivered' || $status_lower === 'picked-up' || $status_lower === 'picked up') && !$refund;

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
            justify-content: center;
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

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="../profile/profile.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
            
            <?php if ($refund): ?>
            <button onclick="openRefundDetailsModal()" class="btn btn-primary">
                <i class="fas fa-eye"></i> View Refund Request
            </button>
            <?php elseif ($can_request_refund): ?>
            <button onclick="openRefundModal()" class="btn btn-primary">
                <i class="fas fa-undo"></i> Request Refund
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
                                       name="refund_items[]" 
                                       value='<?php echo json_encode(['item_id' => $item['item_id'], 'product_name' => $item['product_name'], 'quantity' => $item['quantity'], 'price' => $item['price']]); ?>'
                                       onchange="updateRefundSummary()">
                                <div class="refund-item-info">
                                    <div class="refund-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="refund-item-details">
                                        Quantity: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?> = 
                                        ₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
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

                    <!-- Proof Image Upload -->
                    <div class="refund-form-group">
                        <label>Upload Proof Image *</label>
                        <div class="refund-file-upload" onclick="document.getElementById('proof_image').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #0f5132; margin-bottom: 8px;"></i>
                            <p style="margin: 0; color: #666;">Click to upload proof image</p>
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #999;">JPG, PNG, GIF (Max 5MB)</p>
                            <input type="file" 
                                   id="proof_image" 
                                   name="proof_image" 
                                   accept="image/*" 
                                   required
                                   onchange="previewProofImage(event)">
                        </div>
                        <div id="proofImagePreview" class="refund-file-preview" style="display: none;"></div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="refund-form-group">
                        <label for="refund_note">Additional Details (Optional)</label>
                        <textarea id="refund_note" 
                                  name="refund_note" 
                                  rows="4" 
                                  placeholder="Please provide any additional details about the issue..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="refund-submit-btn" id="refundSubmitBtn" disabled>
                        <i class="fas fa-paper-plane"></i> Submit Refund Request
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

        function updateRefundSummary() {
            const checkboxes = document.querySelectorAll('input[name="refund_items[]"]:checked');
            const submitBtn = document.getElementById('refundSubmitBtn');
            const summary = document.getElementById('refundSummary');
            
            if (checkboxes.length > 0) {
                let totalAmount = 0;
                checkboxes.forEach(cb => {
                    const data = JSON.parse(cb.value);
                    totalAmount += parseFloat(data.price) * parseInt(data.quantity);
                });
                
                document.getElementById('selectedItemsCount').textContent = checkboxes.length;
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
            const preview = document.getElementById('proofImagePreview');
            const submitBtn = document.getElementById('refundSubmitBtn');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Proof Preview">';
                    preview.style.display = 'block';
                    
                    // Enable submit button if items also selected
                    const checkboxes = document.querySelectorAll('input[name="refund_items[]"]:checked');
                    if (checkboxes.length > 0) {
                        submitBtn.disabled = false;
                    }
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                submitBtn.disabled = true;
            }
        }

        document.getElementById('refundForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('refundSubmitBtn');
            
            // Get selected items
            const selectedItems = [];
            const checkboxes = document.querySelectorAll('input[name="refund_items[]"]:checked');
            checkboxes.forEach(cb => {
                selectedItems.push(JSON.parse(cb.value));
            });
            
            formData.delete('refund_items[]');
            formData.append('refund_items', JSON.stringify(selectedItems));
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
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
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Refund Request';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while submitting the refund request. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Refund Request';
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
                <h2><i class="fas fa-receipt"></i> Refund Request Details</h2>
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

                <?php if (!empty($refund['proof_image'])): ?>
                <div class="refund-form-group">
                    <label>Proof Image:</label>
                    <div style="text-align: center; margin-top: 12px;">
                        <a href="../../../<?php echo htmlspecialchars($refund['proof_image']); ?>" target="_blank" style="display: inline-block;">
                            <img src="../../../<?php echo htmlspecialchars($refund['proof_image']); ?>" 
                                 alt="Refund Proof" 
                                 style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;"
                                 onmouseover="this.style.transform='scale(1.02)'"
                                 onmouseout="this.style.transform='scale(1)'">
                        </a>
                        <p style="margin-top: 8px; font-size: 13px; color: #666;">
                            <i class="fas fa-expand"></i> Click image to view full size
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <button type="button" class="refund-submit-btn" onclick="closeRefundDetailsModal()">
                    <i class="fas fa-times"></i> Close
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
</body>
</html>
