<?php
// Include database first - it handles session configuration
require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

// Check if payment success data exists
if (!isset($_SESSION['payment_success'])) {
    header("Location: ../products/product-dashboard.php");
    exit();
}

$payment_data = $_SESSION['payment_success'];
$order_type = $_GET['type'] ?? 'regular';
require_once '../../../backend/pages/user-page-content/database-config.php';

// Record coupon usage if a coupon was applied
error_log("=== PAYMENT SUCCESS COUPON RECORDING CHECK ===");
$applied_coupon = $_SESSION['applied_coupon'] ?? null;
error_log("applied_coupon exists: " . (isset($applied_coupon) ? 'YES' : 'NO'));
error_log("applied_coupon data: " . json_encode($applied_coupon));

if ($applied_coupon && isset($applied_coupon['id']) && intval($applied_coupon['id']) > 0) {
    error_log("=== RECORDING COUPON USAGE (PAYMENT SUCCESS) ===");
    $user_id = $_SESSION['user_id'] ?? null;
    $coupon_id = intval($applied_coupon['id']);
    $order_id = $payment_data['order_id'] ?? null;
    
    if ($user_id && $order_id) {
        error_log("User ID: $user_id, Coupon ID: $coupon_id, Order ID: $order_id");
        
        // Record the usage
        if (recordCouponUsage($conn, $user_id, $coupon_id, $order_id)) {
            error_log("✓ Coupon usage recorded successfully: User $user_id used coupon $coupon_id on order $order_id");
            
            // Update global used_count
            $update_sql = "UPDATE promotions SET used_count = used_count + 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param("i", $coupon_id);
                if ($update_stmt->execute()) {
                    error_log("✓ Global used_count updated for coupon $coupon_id");
                } else {
                    error_log("✗ Failed to update global used_count: " . $update_stmt->error);
                }
                $update_stmt->close();
            } else {
                error_log("✗ Failed to prepare used_count update statement");
            }
            
            // Clear the applied coupon from session after successful recording
            unset($_SESSION['applied_coupon']);
            error_log("✓ Cleared applied_coupon from session");
        } else {
            error_log("✗ Failed to record coupon usage for order $order_id");
        }
    } else {
        if (!$user_id) {
            error_log("✗ No user_id in session, cannot record coupon usage");
        }
        if (!$order_id) {
            error_log("✗ No order_id in payment_data, cannot record coupon usage");
        }
    }
} else {
    error_log("=== NO COUPON TO RECORD (PAYMENT SUCCESS) ===");
    if ($applied_coupon) {
        error_log("Coupon ID issue: " . (isset($applied_coupon['id']) ? "ID = " . $applied_coupon['id'] : "ID not set"));
    }
}

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
            <a href="../profile/profile.php" class="btn btn-secondary">View Orders</a>
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
