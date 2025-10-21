<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

$error_message = $_SESSION['payment_error'] ?? 'Payment was unsuccessful. Please try again.';
$order_type = $_GET['type'] ?? 'regular';

$page_title = "Payment Failed";
$additional_css = ["checkout.css"];

require_once "../../user-includes/user-header.php";
?>

<div class="payment-result-container">
    <div class="error-card">
        <div class="error-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#f44336" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        
        <h1>Payment Failed</h1>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        
        <div class="error-details">
            <h3>What happened?</h3>
            <p>Your payment could not be processed. This might be due to:</p>
            <ul>
                <li>Insufficient funds in your account</li>
                <li>Network connectivity issues</li>
                <li>Payment was cancelled</li>
                <li>Invalid payment information</li>
            </ul>
        </div>
        
        <div class="next-steps">
            <h3>What can you do?</h3>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>Check Your Payment Method</strong><br>
                        Ensure your payment method has sufficient funds and is active.
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <strong>Try Again</strong><br>
                        Return to your cart and attempt the payment again.
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">
                        <strong>Contact Support</strong><br>
                        If the problem persists, please contact our customer support.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <?php if ($order_type === 'availtoday'): ?>
                <a href="../products/product-dashboard.php" class="btn btn-primary">Return to Available Today</a>
            <?php else: ?>
                <a href="cart.php" class="btn btn-primary">Return to Cart</a>
            <?php endif; ?>
            <a href="../products/product-dashboard.php" class="btn btn-secondary">Continue Shopping</a>
        </div>
        
        <div class="support-info">
            <p><strong>Need Help?</strong></p>
            <p>Contact us at <a href="mailto:support@neocafe.cafe">support@neocafe.cafe</a> or call us at +63 XXX XXX XXXX</p>
        </div>
    </div>
</div>

<style>
.payment-result-container {
    max-width: 600px;
    margin: 50px auto;
    padding: 20px;
}

.error-card {
    background: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: 2px solid #f44336;
}

.error-icon {
    margin-bottom: 20px;
}

.error-card h1 {
    color: #f44336;
    margin-bottom: 10px;
    font-size: 32px;
}

.error-message {
    color: #666;
    font-size: 18px;
    margin-bottom: 30px;
}

.error-details {
    background: #fff5f5;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
    text-align: left;
    border-left: 4px solid #f44336;
}

.error-details h3 {
    color: #f44336;
    margin-bottom: 10px;
}

.error-details ul {
    margin: 15px 0;
    padding-left: 20px;
}

.error-details li {
    margin-bottom: 5px;
    color: #666;
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
    background: #f44336;
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
    margin: 30px 0;
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
    background: #f44336;
    color: white;
}

.btn-primary:hover {
    background: #d32f2f;
}

.btn-secondary {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #e9ecef;
}

.support-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    font-size: 14px;
    color: #666;
}

.support-info a {
    color: #f44336;
    text-decoration: none;
}

.support-info a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .payment-result-container {
        margin: 20px auto;
        padding: 10px;
    }
    
    .error-card {
        padding: 20px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php
// Clear payment error data
unset($_SESSION['payment_error']);

require_once "../../user-includes/user-footer.php";
?>
