<?php
/**
 * Card Payment Page
 * Handles PayMongo card payment using Payment Intents
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';
require_once 'paymongo-config.php';

// Require user login
SessionManager::requireUserLogin('../../login/user/login-signup.php');

// Get payment intent ID from URL
$payment_intent_id = $_GET['payment_intent_id'] ?? '';
$url_client_secret = $_GET['client_secret'] ?? ''; // Backup from URL
$url_order_type = $_GET['order_type'] ?? 'regular'; // Backup from URL

if (empty($payment_intent_id)) {
    die('Invalid payment intent ID');
}

// Get pending payment from session or database
$pending_payment = null;
$client_secret = '';
$order_type = 'regular';

error_log("[CARD-PAYMENT] Payment Intent ID: $payment_intent_id");
error_log("[CARD-PAYMENT] Session ID: " . session_id());
error_log("[CARD-PAYMENT] Session pending_payment exists: " . (isset($_SESSION['pending_payment']) ? 'YES' : 'NO'));
error_log("[CARD-PAYMENT] URL client_secret exists: " . (!empty($url_client_secret) ? 'YES' : 'NO'));

// Try to get from URL parameters first (most reliable)
if (!empty($url_client_secret)) {
    $client_secret = $url_client_secret;
    $order_type = $url_order_type;
    error_log("[CARD-PAYMENT] ✓ Got client_secret from URL parameters");
}
// Then try session
elseif (isset($_SESSION['pending_payment'])) {
    $pending_payment = $_SESSION['pending_payment'];
    $client_secret = $pending_payment['client_secret'] ?? '';
    $order_type = $pending_payment['order_type'] ?? 'regular';
    error_log("[CARD-PAYMENT] Got from session - client_secret: " . substr($client_secret, 0, 20) . "...");
} else {
    error_log("[CARD-PAYMENT] Session not found, attempting database recovery...");
    // Try to recover from database
    $user_id = SessionManager::getUserId();
    error_log("[CARD-PAYMENT] User ID for recovery: $user_id");
    
    if ($user_id && !empty($payment_intent_id)) {
        // First check if the record exists at all
        $check_sql = "SELECT * FROM pending_payments WHERE payment_id = ? AND user_id = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param("si", $payment_intent_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                error_log("[CARD-PAYMENT] Found pending payment in database");
                $recover_row = $check_result->fetch_assoc();
                error_log("[CARD-PAYMENT] Database record: " . print_r($recover_row, true));
                
                $order_type = $recover_row['order_type'];
                $order_data = json_decode($recover_row['order_data'], true);
                
                // We need to get the client_secret from PayMongo API
                try {
                    $paymongo = new PayMongoAPI();
                    $intent_result = $paymongo->getPaymentIntent($payment_intent_id);
                    
                    error_log("[CARD-PAYMENT] PayMongo API response: " . print_r($intent_result, true));
                    
                    if (isset($intent_result['data']['attributes']['client_key'])) {
                        $client_secret = $intent_result['data']['attributes']['client_key'];
                        
                        // Restore to session
                        $_SESSION['pending_payment'] = [
                            'payment_intent_id' => $payment_intent_id,
                            'client_secret' => $client_secret,
                            'order_type' => $order_type,
                            'amount' => floatval($recover_row['amount']),
                            'payment_method' => $recover_row['payment_method'],
                            'order_data' => $order_data
                        ];
                        
                        error_log("[CARD-PAYMENT] ✓ Successfully recovered payment session from database");
                    } else {
                        error_log("[CARD-PAYMENT] ✗ No client_key in PayMongo response");
                    }
                } catch (Exception $e) {
                    error_log("[CARD-PAYMENT] ✗ Error calling PayMongo API: " . $e->getMessage());
                }
            } else {
                error_log("[CARD-PAYMENT] ✗ No pending payment found in database for payment_id=$payment_intent_id, user_id=$user_id");
            }
            $check_stmt->close();
        } else {
            error_log("[CARD-PAYMENT] ✗ Failed to prepare check query: " . $conn->error);
        }
    } else {
        error_log("[CARD-PAYMENT] ✗ Cannot recover - missing user_id or payment_intent_id");
    }
}

if (empty($client_secret)) {
    error_log("[CARD-PAYMENT] ERROR: No client_secret found");
    error_log("[CARD-PAYMENT] Session data: " . print_r($_SESSION, true));
    
    // Show detailed error page with options
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Session Error - Neo Cafe</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 500px;
                width: 100%;
                padding: 40px;
                text-align: center;
            }
            .error-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                color: #d32f2f;
                margin-bottom: 15px;
                font-size: 24px;
            }
            .message {
                color: #666;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .actions {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .btn {
                padding: 15px 30px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s;
            }
            .btn-primary {
                background: #256035;
                color: white;
            }
            .btn-primary:hover {
                background: #1a4a28;
                transform: translateY(-2px);
            }
            .btn-secondary {
                background: #f0f0f0;
                color: #333;
            }
            .btn-secondary:hover {
                background: #e0e0e0;
            }
            .debug-info {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                font-size: 12px;
                color: #999;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1>Payment Session Lost</h1>
            <p class="message">
                Your payment session could not be found. This may happen if:<br>
                • Your session expired<br>
                • Cookies are disabled<br>
                • You opened the payment link in a new browser<br><br>
                Please return to checkout and try again.
            </p>
            <div class="actions">
                <a href="checkout.php" class="btn btn-primary">Return to Checkout</a>
                <a href="../products/product-dashboard.php" class="btn btn-secondary">Back to Shopping</a>
            </div>
            <div class="debug-info">
                Payment ID: <?= htmlspecialchars($payment_intent_id) ?><br>
                Session ID: <?= session_id() ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

error_log("[CARD-PAYMENT] Success - client_secret: " . substr($client_secret, 0, 20) . "...");

$page_title = "Card Payment";
$payment_intent_id_js = htmlspecialchars($payment_intent_id, ENT_QUOTES, 'UTF-8');
$client_secret_js = htmlspecialchars($client_secret, ENT_QUOTES, 'UTF-8');
$order_type_js = htmlspecialchars($order_type, ENT_QUOTES, 'UTF-8');

// Check if in live mode
require_once 'paymongo-config.php';
$is_live_mode = isPayMongoLiveMode();
$mode_display = getPayMongoModeDisplay();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Neo Cafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f7f9fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .payment-wrapper {
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .payment-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 32px;
        }
        .test-cards-panel {
            background: #fff8e6;
            border: 2px solid #ffd54f;
            border-radius: 8px;
            padding: 24px;
            height: fit-content;
        }
        .mode-banner {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .mode-banner.test {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        h1 {
            color: #1a1a1a;
            margin-bottom: 8px;
            font-size: 24px;
            font-weight: 600;
        }
        .subtitle {
            color: #666;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .test-cards-title {
            color: #856404;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .test-card-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .test-card-item:hover {
            border-color: #ffd54f;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-card-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        .test-card-number {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .test-card-details {
            font-size: 11px;
            color: #999;
            font-family: 'Courier New', monospace;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-size: 13px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.2s;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-group input::placeholder {
            color: #9ca3af;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        #card-errors {
            color: #dc2626;
            font-size: 13px;
            margin-bottom: 16px;
            padding: 10px 12px;
            background: #fee2e2;
            border-radius: 6px;
            border-left: 3px solid #dc2626;
            display: none;
        }
        #card-errors:not(:empty) {
            display: block;
        }
        .btn-pay {
            width: 100%;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-pay:hover:not(:disabled) {
            background: #2563eb;
        }
        .btn-pay:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 16px;
        }
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .powered-by {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #9ca3af;
        }
        .powered-by img {
            height: 16px;
            vertical-align: middle;
            margin-left: 4px;
        }
        @media (max-width: 768px) {
            .payment-wrapper {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="payment-wrapper">
        <div class="payment-container">
            <span class="mode-banner test">🧪 Test Mode</span>
            
            <h1>Card Payment</h1>
            <p class="subtitle">Enter test card details to complete payment</p>
            
            <form id="payment-form">
                <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" id="card_number" placeholder="4343 4343 4343 4345" maxlength="19" autocomplete="off" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="exp_month">Month</label>
                        <input type="text" id="exp_month" placeholder="12" maxlength="2" autocomplete="off" required>
                    </div>
                    <div class="form-group">
                        <label for="exp_year">Year</label>
                        <input type="text" id="exp_year" placeholder="25" maxlength="2" autocomplete="off" required>
                    </div>
                    <div class="form-group">
                        <label for="cvc">CVC</label>
                        <input type="text" id="cvc" placeholder="123" maxlength="4" autocomplete="off" required>
                    </div>
                </div>
                
                <div id="card-errors"></div>
                <button type="submit" id="submit-button" class="btn-pay">Pay Now</button>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="margin-top: 10px; color: #666; font-size: 14px;">Processing payment...</p>
            </div>
            
            <div class="powered-by">
                Secured by <strong>PayMongo</strong>
            </div>
        </div>
        
        <div class="test-cards-panel">
            <div class="test-cards-title">
                💳 PayMongo Test Cards
            </div>
            <p style="font-size: 13px; color: #856404; margin-bottom: 16px;">
                Click a test card below to auto-fill the form
            </p>
            
            <div class="test-card-item" onclick="fillTestCard('4343434343434345', '12', '25', '123')">
                <div class="test-card-label">✅ Payment Success</div>
                <div class="test-card-number">4343 4343 4343 4345</div>
                <div class="test-card-details">Exp: 12/25 | CVC: 123</div>
            </div>
            
            <div class="test-card-item" onclick="fillTestCard('4571736000000075', '12', '25', '123')">
                <div class="test-card-label">❌ Card Declined</div>
                <div class="test-card-number">4571 7360 0000 0075</div>
                <div class="test-card-details">Exp: 12/25 | CVC: 123</div>
            </div>
            
            <div class="test-card-item" onclick="fillTestCard('4120000000000007', '12', '25', '123')">
                <div class="test-card-label">🔒 3DS Authentication</div>
                <div class="test-card-number">4120 0000 0000 0007</div>
                <div class="test-card-details">Exp: 12/25 | CVC: 123</div>
            </div>
            
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0;">
                <p style="font-size: 12px; color: #856404; line-height: 1.5;">
                    <strong>Note:</strong> These are test cards provided by PayMongo. No real charges will be made.
                </p>
                <p style="font-size: 11px; color: #999; margin-top: 8px;">
                    Learn more: <a href="https://developers.paymongo.com/docs/testing" target="_blank" style="color: #3b82f6;">PayMongo Testing Guide</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const paymentIntentId = '<?= $payment_intent_id_js ?>';
        const clientSecret = '<?= $client_secret_js ?>';
        const orderType = '<?= $order_type_js ?>';
        
        console.log('[CARD-PAYMENT] Payment Intent ID:', paymentIntentId);
        console.log('[CARD-PAYMENT] Order type:', orderType);
        
        // Test card auto-fill function
        function fillTestCard(cardNumber, month, year, cvc) {
            // Format card number with spaces
            const formatted = cardNumber.match(/.{1,4}/g).join(' ');
            document.getElementById('card_number').value = formatted;
            document.getElementById('exp_month').value = month;
            document.getElementById('exp_year').value = year;
            document.getElementById('cvc').value = cvc;
            
            // Add visual feedback
            const inputs = [document.getElementById('card_number'), document.getElementById('exp_month'), 
                          document.getElementById('exp_year'), document.getElementById('cvc')];
            inputs.forEach(input => {
                input.style.borderColor = '#10b981';
                setTimeout(() => input.style.borderColor = '', 1000);
            });
        }
        
        // Format card number with spaces
        const cardNumberInput = document.getElementById('card_number');
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
        
        // Only allow numbers
        ['card_number', 'exp_month', 'exp_year', 'cvc'].forEach(id => {
            document.getElementById(id).addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        });
        
        // Handle form submission
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const loading = document.getElementById('loading');
        const errors = document.getElementById('card-errors');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            console.log('[CARD-PAYMENT] Form submitted');
            
            // Get card details
            const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            const expMonth = parseInt(document.getElementById('exp_month').value);
            const expYear = parseInt(document.getElementById('exp_year').value);
            const cvc = document.getElementById('cvc').value;
            
            // Convert 2-digit year to 4-digit year
            const fullYear = expYear < 100 ? 2000 + expYear : expYear;
            
            console.log('[CARD-PAYMENT] Card details:', {
                cardNumber: '**** **** **** ' + cardNumber.slice(-4),
                expMonth: expMonth,
                expYear: fullYear,
                cvc: '***'
            });
            
            // Validate
            if (!cardNumber || cardNumber.length < 13) {
                errors.textContent = 'Please enter a valid card number';
                return;
            }
            if (!expMonth || expMonth < 1 || expMonth > 12) {
                errors.textContent = 'Please enter a valid expiry month (01-12)';
                return;
            }
            if (!expYear || expYear < 0) {
                errors.textContent = 'Please enter a valid expiry year';
                return;
            }
            if (!cvc || cvc.length < 3) {
                errors.textContent = 'Please enter a valid CVC';
                return;
            }
            
            submitButton.disabled = true;
            loading.style.display = 'block';
            errors.textContent = '';
            
            try {
                console.log('[CARD-PAYMENT] Creating payment method...');
                
                // Step 1: Create payment method via our backend
                const pmResponse = await fetch('create-payment-method.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        card_number: cardNumber,
                        exp_month: expMonth,
                        exp_year: fullYear,  // Use 4-digit year
                        cvc: cvc
                    })
                });
                
                const pmData = await pmResponse.json();
                console.log('[CARD-PAYMENT] Payment method response:', pmData);
                
                if (!pmData.success) {
                    throw new Error(pmData.message || 'Failed to create payment method');
                }
                
                // Step 2: Attach payment method to payment intent
                console.log('[CARD-PAYMENT] Attaching payment method to intent...');
                const attachResponse = await fetch('attach-payment-method.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        payment_intent_id: paymentIntentId,
                        payment_method_id: pmData.payment_method_id,
                        client_key: clientSecret
                    })
                });
                
                const attachData = await attachResponse.json();
                console.log('[CARD-PAYMENT] Attach response:', attachData);
                
                if (!attachData.success) {
                    // Check if 3D Secure is required
                    if (attachData.requires_action && attachData.redirect_url) {
                        console.log('[CARD-PAYMENT] 3D Secure required, redirecting...');
                        window.location.href = attachData.redirect_url;
                        return;
                    }
                    throw new Error(attachData.message || 'Failed to process payment');
                }
                
                // Payment successful or processing - redirect to return page with status
                console.log('[CARD-PAYMENT] Payment successful!');
                window.location.href = `payment-return.php?status=success&type=${orderType}&payment_intent_id=${paymentIntentId}`;
                
            } catch (error) {
                console.error('[CARD-PAYMENT] Payment error:', error);
                errors.textContent = error.message || 'Payment failed. Please try again.';
                submitButton.disabled = false;
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>
