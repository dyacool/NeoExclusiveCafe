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

if (empty($payment_intent_id)) {
    die('Invalid payment intent ID');
}

// Get pending payment from session or database
$pending_payment = null;
$client_secret = '';
$order_type = 'regular';

error_log("[CARD-PAYMENT] Payment Intent ID: $payment_intent_id");
error_log("[CARD-PAYMENT] Session pending_payment exists: " . (isset($_SESSION['pending_payment']) ? 'YES' : 'NO'));

if (isset($_SESSION['pending_payment'])) {
    $pending_payment = $_SESSION['pending_payment'];
    $client_secret = $pending_payment['client_secret'] ?? '';
    $order_type = $pending_payment['order_type'] ?? 'regular';
    error_log("[CARD-PAYMENT] Got from session - client_secret: " . substr($client_secret, 0, 20) . "...");
} else {
    error_log("[CARD-PAYMENT] Session not found, attempting database recovery...");
    // Try to recover from database
    $user_id = SessionManager::getUserId();
    if ($user_id && !empty($payment_intent_id)) {
        $recover_sql = "SELECT * FROM pending_payments 
                        WHERE payment_id = ? 
                        AND user_id = ? 
                        AND expires_at > NOW()
                        LIMIT 1";
        $recover_stmt = $conn->prepare($recover_sql);
        if ($recover_stmt) {
            $recover_stmt->bind_param("si", $payment_intent_id, $user_id);
            $recover_stmt->execute();
            $recover_result = $recover_stmt->get_result();
            
            if ($recover_row = $recover_result->fetch_assoc()) {
                $order_type = $recover_row['order_type'];
                $order_data = json_decode($recover_row['order_data'], true);
                
                // We need to get the client_secret from PayMongo API
                $paymongo = new PayMongoAPI();
                $intent_result = $paymongo->getPaymentIntent($payment_intent_id);
                
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
                }
            }
            $recover_stmt->close();
        }
    }
}

if (empty($client_secret)) {
    error_log("[CARD-PAYMENT] ERROR: No client_secret found");
    error_log("[CARD-PAYMENT] Session data: " . print_r($_SESSION, true));
    die('Invalid payment session. Please try again from checkout.');
}

error_log("[CARD-PAYMENT] Success - client_secret: " . substr($client_secret, 0, 20) . "...");

$page_title = "Card Payment";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Neo Cafe</title>
    <script src="https://js.paymongo.com/v1"></script>
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
        .payment-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        #card-element {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        #card-errors {
            color: #d32f2f;
            font-size: 14px;
            margin-bottom: 20px;
            min-height: 20px;
        }
        .btn-pay {
            width: 100%;
            background: #256035;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-pay:hover:not(:disabled) {
            background: #1a4a28;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 96, 53, 0.4);
        }
        .btn-pay:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #256035;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1>💳 Card Payment</h1>
        <p class="subtitle">Enter your card details to complete payment</p>
        
        <form id="payment-form">
            <div id="card-element"></div>
            <div id="card-errors"></div>
            <button type="submit" id="submit-button" class="btn-pay">Pay Now</button>
        </form>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 10px; color: #666;">Processing payment...</p>
        </div>
    </div>

    <script>
        const paymongo = PaymongoPlugin('pk_test_1XUMJ3yMs8QZugdq3uWr8vYU');
        const clientSecret = '<?= $client_secret ?>';
        const orderType = '<?= $order_type ?>';
        
        // Mount card element
        const cardElement = paymongo.elements.create('card');
        cardElement.mount('#card-element');
        
        // Handle form submission
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const loading = document.getElementById('loading');
        const errors = document.getElementById('card-errors');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            submitButton.disabled = true;
            loading.style.display = 'block';
            errors.textContent = '';
            
            try {
                const result = await paymongo.confirmPayment(clientSecret, {
                    payment_method: {
                        type: 'card',
                        card: cardElement
                    }
                });
                
                if (result.error) {
                    errors.textContent = result.error.message;
                    submitButton.disabled = false;
                    loading.style.display = 'none';
                } else {
                    // Payment successful - redirect to return page
                    window.location.href = `payment-return.php?type=${orderType}&payment_intent_id=${result.paymentIntent.id}`;
                }
            } catch (error) {
                errors.textContent = 'Payment failed: ' + error.message;
                submitButton.disabled = false;
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>
