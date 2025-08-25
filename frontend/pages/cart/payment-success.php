<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Check if payment success data exists
if (!isset($_SESSION['payment_success'])) {
    header("Location: ../products/product-dashboard.php");
    exit();
}

$payment_data = $_SESSION['payment_success'];
$order_type = $_GET['type'] ?? 'regular';

$page_title = "Payment Successful";
$additional_css = ["checkout.css"];

require_once "../../user-includes/user-header.php";
?>

<div class="payment-result-container">
    <div class="success-card">
        <div class="success-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22,4 12,14.01 9,11.01"></polyline>
            </svg>
        </div>
        
        <h1>Payment Successful!</h1>
        <p class="success-message">Your payment has been processed successfully.</p>
        
        <div class="payment-details">
            <div class="detail-row">
                <span class="label">Order Number:</span>
                <span class="value">#<?php echo $payment_data['order_id']; ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Amount Paid:</span>
                <span class="value">₱<?php echo number_format($payment_data['amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Payment Method:</span>
                <span class="value"><?php echo ucfirst($payment_data['payment_method']); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Order Type:</span>
                <span class="value"><?php echo $order_type === 'availtoday' ? 'Available Today' : 'Regular Order'; ?></span>
            </div>
        </div>
        
        <div class="next-steps">
            <h3>What's Next?</h3>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>Order Confirmation</strong><br>
                        You'll receive an email confirmation shortly.
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <strong>Order Preparation</strong><br>
                        We'll start preparing your order immediately.
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">
                        <strong>
                            <?php echo $order_type === 'availtoday' ? 'Ready for Pickup/Delivery' : 'Scheduled Delivery/Pickup'; ?>
                        </strong><br>
                        <?php if ($order_type === 'availtoday'): ?>
                            Your order will be ready today as scheduled.
                        <?php else: ?>
                            Your order will be ready on your selected date and time.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="../products/product-dashboard.php" class="btn btn-primary">Continue Shopping</a>
            <a href="../profile/order-history.php" class="btn btn-secondary">View Orders</a>
        </div>
    </div>
</div>

<style>
.payment-result-container {
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
}

.success-card {
    background: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: 2px solid #4CAF50;
}

.success-icon {
    margin-bottom: 20px;
}

.success-card h1 {
    color: #4CAF50;
    margin-bottom: 10px;
    font-size: 32px;
}

.success-message {
    color: #666;
    font-size: 18px;
    margin-bottom: 30px;
}

.payment-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
    text-align: left;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.detail-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-row .label {
    font-weight: 500;
    color: #666;
}

.detail-row .value {
    font-weight: 600;
    color: #333;
}

.next-steps {
    margin: 30px 0;
    text-align: left;
}

.next-steps h3 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

.steps {
    display: flex;
    flex-direction: column;
    gap: 15px;
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

.step-text {
    flex: 1;
}

.step-text strong {
    color: #333;
    display: block;
    margin-bottom: 5px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
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
    .payment-result-container {
        margin: 20px auto;
        padding: 10px;
    }
    
    .success-card {
        padding: 20px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<?php
// Clear payment success data
unset($_SESSION['payment_success']);

require_once "../../user-includes/user-footer.php";
?>
