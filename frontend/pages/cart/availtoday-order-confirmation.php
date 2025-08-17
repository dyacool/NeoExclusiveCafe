<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Require login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

// Check if order confirmation data exists
if (!isset($_SESSION['order_confirmation'])) {
    header("Location: ../products/product-dashboard.php");
    exit();
}

$order_data = $_SESSION['order_confirmation'];

$page_title = "Order Confirmation";
$additional_css = [
    "checkout.css"
];

require_once "../../user-includes/user-header.php";
?>

<div class="confirmation-container">
    <div class="confirmation-header">
        <div class="success-icon">✅</div>
        <h1>Order Confirmed!</h1>
        <p class="order-number">Order #<?php echo $order_data['order_id']; ?></p>
    </div>

    <div class="confirmation-content">
        <div class="order-summary-section">
            <h2>Order Summary</h2>
            
            <div class="order-details">
                <div class="detail-row">
                    <span class="label">Order Number:</span>
                    <span class="value">#<?php echo $order_data['order_id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Order Type:</span>
                    <span class="value">Available Today</span>
                </div>
                <div class="detail-row">
                    <span class="label">Total Amount:</span>
                    <span class="value">₱<?php echo number_format($order_data['total_amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-pending">Pending</span>
                </div>
            </div>

            <div class="order-items">
                <h3>Items Ordered</h3>
                <?php foreach ($order_data['items'] as $item): ?>
                    <div class="confirmation-item">
                        <div class="item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p class="item-method">
                                <?php if ($item['availtoday_status_id']): ?>
                                    <strong><?php echo htmlspecialchars($item['availtoday_status_name']); ?></strong>
                                <?php else: ?>
                                    <strong><?php echo $item['status_id'] == 1 ? 'Pick Up' : 'Delivery'; ?></strong>
                                    <span class="auto-assigned">(Auto-assigned)</span>
                                <?php endif; ?>
                            </p>
                            <p class="item-quantity">Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div class="item-price">
                            ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="customer-info-section">
            <h2>Customer Information</h2>
            <div class="info-card">
                <h3>Contact Details</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order_data['customer_info']['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order_data['customer_info']['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order_data['customer_info']['phone']); ?></p>
            </div>

            <?php if (!empty($order_data['customer_info']['address'])): ?>
            <div class="info-card">
                <h3>Delivery Address</h3>
                <p><?php echo htmlspecialchars($order_data['customer_info']['address']); ?></p>
                <p><?php echo htmlspecialchars($order_data['customer_info']['city']); ?>
                   <?php if (!empty($order_data['customer_info']['postal_code'])): ?>
                       <?php echo htmlspecialchars($order_data['customer_info']['postal_code']); ?>
                   <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="next-steps">
        <h2>What Happens Next?</h2>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Order Processing</h3>
                    <p>We'll review your order and begin preparation immediately.</p>
                </div>
            </div>
            
            <?php if ($order_data['has_mixed_status']): ?>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Mixed Order Handling</h3>
                        <p>Your order contains both pickup and delivery items. We'll coordinate both methods for you.</p>
                    </div>
                </div>
            <?php elseif ($order_data['shipping_method'] === 'delivery'): ?>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Delivery Preparation</h3>
                        <p>Your order will be prepared and delivered to your specified address.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Pickup Preparation</h3>
                        <p>Your order will be ready for pickup. We'll notify you when it's ready.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Notification</h3>
                    <p>We'll send you updates via email and SMS about your order status.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="confirmation-actions">
        <a href="../products/product-dashboard.php" class="btn btn-primary">Continue Shopping</a>
        <a href="../profile/order-history.php" class="btn btn-secondary">View Order History</a>
    </div>
</div>

<style>
.confirmation-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.confirmation-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    border-radius: 12px;
}

.success-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.confirmation-header h1 {
    margin: 10px 0;
    font-size: 32px;
}

.order-number {
    font-size: 18px;
    opacity: 0.9;
    margin: 0;
}

.confirmation-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.order-summary-section,
.customer-info-section {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
}

.order-summary-section h2,
.customer-info-section h2 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
}

.order-details {
    margin-bottom: 25px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.detail-row:last-child {
    border-bottom: none;
}

.label {
    font-weight: 500;
    color: #666;
}

.value {
    font-weight: 600;
    color: #333;
}

.status-pending {
    color: #ff9800;
    background: #fff3e0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.confirmation-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 15px 0;
    border-bottom: 1px solid #ddd;
}

.confirmation-item:last-child {
    border-bottom: none;
}

.item-info h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.item-method {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
}

.auto-assigned {
    color: #999;
    font-size: 12px;
    font-style: italic;
}

.item-quantity {
    margin: 5px 0 0 0;
    font-size: 14px;
    color: #666;
}

.item-price {
    font-weight: 600;
    color: #333;
    font-size: 16px;
}

.info-card {
    background: white;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
    border: 1px solid #e0e0e0;
}

.info-card h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
}

.info-card p {
    margin: 5px 0;
    color: #666;
}

.next-steps {
    background: #f0f8ff;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.next-steps h2 {
    margin-top: 0;
    color: #333;
    text-align: center;
}

.steps {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.step-number {
    background: #4CAF50;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h3 {
    margin: 0 0 5px 0;
    color: #333;
}

.step-content p {
    margin: 0;
    color: #666;
}

.confirmation-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #4CAF50;
    color: white;
}

.btn-primary:hover {
    background: #45a049;
}

.btn-secondary {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #e9ecef;
}

@media (max-width: 768px) {
    .confirmation-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .confirmation-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .item-price {
        align-self: flex-end;
    }
}
</style>

<?php
// Clear the order confirmation data after displaying
unset($_SESSION['order_confirmation']);

require_once "../../user-includes/user-footer.php";
?>
