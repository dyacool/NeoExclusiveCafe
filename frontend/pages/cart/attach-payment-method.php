<?php
/**
 * Attach Payment Method to Payment Intent
 * Completes the card payment process
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';
require_once 'paymongo-config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log("[ATTACH-PM] Received request: " . print_r($input, true));
    
    // Validate input
    if (empty($input['payment_intent_id']) || empty($input['payment_method_id'])) {
        throw new Exception('Missing required parameters');
    }
    
    // Create PayMongo API instance
    $paymongo = new PayMongoAPI();
    
    // Construct return URL - handle both localhost and production
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // For localhost, use the full path
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $return_url = $protocol . '://' . $host . '/frontend/pages/cart/payment-return.php';
    } else {
        $return_url = $protocol . '://' . $host . '/frontend/pages/cart/payment-return.php';
    }
    
    error_log("[ATTACH-PM] Return URL: $return_url");
    
    // Attach payment method to payment intent
    $result = $paymongo->attachPaymentMethod(
        $input['payment_intent_id'],
        $input['payment_method_id'],
        $return_url
    );
    
    error_log("[ATTACH-PM] PayMongo response: " . print_r($result, true));
    
    // Check for errors
    if (isset($result['error'])) {
        $errorMsg = is_array($result['error']) ? json_encode($result['error']) : $result['error'];
        throw new Exception('PayMongo error: ' . $errorMsg);
    }
    
    if (isset($result['errors'])) {
        // PayMongo returns errors in a specific format
        if (is_array($result['errors']) && isset($result['errors'][0])) {
            $firstError = $result['errors'][0];
            $errorMsg = $firstError['detail'] ?? json_encode($firstError);
        } else {
            $errorMsg = json_encode($result['errors']);
        }
        throw new Exception('PayMongo validation errors: ' . $errorMsg);
    }
    
    // Check payment status
    $status = $result['data']['attributes']['status'] ?? 'unknown';
    
    error_log("[ATTACH-PM] Payment status: $status");
    
    if ($status === 'succeeded') {
        echo json_encode([
            'success' => true,
            'status' => 'succeeded',
            'payment_intent_id' => $result['data']['id']
        ]);
    } elseif ($status === 'awaiting_next_action') {
        // 3D Secure required
        $next_action = $result['data']['attributes']['next_action'] ?? null;
        if ($next_action && isset($next_action['redirect'])) {
            echo json_encode([
                'success' => true,
                'requires_action' => true,
                'redirect_url' => $next_action['redirect']['url'],
                'message' => 'Redirecting to 3D Secure verification...'
            ]);
        } else {
            throw new Exception('Payment requires additional action but no redirect URL provided');
        }
    } elseif ($status === 'processing') {
        echo json_encode([
            'success' => true,
            'status' => 'processing',
            'message' => 'Payment is being processed'
        ]);
    } else {
        throw new Exception('Payment failed with status: ' . $status);
    }
    
} catch (Exception $e) {
    error_log("[ATTACH-PM] Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
