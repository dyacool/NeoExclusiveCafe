<?php
/**
 * PayMongo Configuration - SANDBOX/TEST MODE ONLY
 * ================================================
 * This configuration uses PayMongo TEST keys for development and testing.
 * DO NOT use live/production keys here.
 * 
 * Test Mode Features:
 * - No real money transactions
 * - Test card numbers work
 * - Sandbox environment only
 * 
 * For production: Replace sk_test_* and pk_test_* with sk_live_* and pk_live_*
 */

// ⚠️ SANDBOX MODE - Test API Configuration
define('PAYMONGO_MODE', 'sandbox'); // 'sandbox' or 'live'
define('PAYMONGO_SECRET_KEY', 'sk_test_your_secret_key_here'); // Replace with your test secret key
define('PAYMONGO_PUBLIC_KEY', 'pk_test_your_public_key_here'); // Replace with your test public key
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');

// PayMongo Helper Class
class PayMongoAPI {
    private $secret_key;
    private $public_key;
    private $api_url;
    
    public function __construct() {
        // ⚠️ SANDBOX MODE - Using PayMongo TEST keys
        // These are test keys and will NOT process real payments
        // For production, replace with sk_live_* and pk_live_* keys
        $this->secret_key = 'sk_test_yb8pkZvUA3WjHP6T4FKhgudU'; // TEST SECRET KEY
        $this->public_key = 'pk_test_1XUMJ3yMs8QZugdq3uWr8vYU'; // TEST PUBLIC KEY
        $this->api_url = 'https://api.paymongo.com/v1';
        
        // Log that we're in sandbox mode
        error_log('[PAYMONGO] Running in SANDBOX/TEST mode - No real transactions');
    }
    
    /**
     * Create a payment intent
     */
    public function createPaymentIntent($amount, $currency = 'PHP', $description = '', $metadata = []) {
        $url = $this->api_url . '/payment_intents';
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount * 100, // PayMongo expects amount in cents
                    'payment_method_allowed' => ['gcash', 'paymaya', 'card'],
                    'payment_method_options' => [
                        'card' => [
                            'request_three_d_secure' => 'automatic'
                        ]
                    ],
                    'currency' => $currency,
                    'capture_type' => 'automatic',
                    'description' => $description,
                    'statement_descriptor' => 'NeoCafe Order',
                    'metadata' => $metadata
                ]
            ]
        ];
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    /**
     * Create a payment method
     */
    public function createPaymentMethod($type, $details = []) {
        $url = $this->api_url . '/payment_methods';
        
        $data = [
            'data' => [
                'attributes' => [
                    'type' => $type,
                    'details' => $details
                ]
            ]
        ];
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    /**
     * Attach payment method to payment intent
     */
    public function attachPaymentMethod($payment_intent_id, $payment_method_id, $return_url) {
        $url = $this->api_url . '/payment_intents/' . $payment_intent_id . '/attach';
        
        $data = [
            'data' => [
                'attributes' => [
                    'payment_method' => $payment_method_id,
                    'return_url' => $return_url
                ]
            ]
        ];
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    /**
     * Get payment intent
     */
    public function getPaymentIntent($payment_intent_id) {
        $url = $this->api_url . '/payment_intents/' . $payment_intent_id;
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Create a source (for GCash/Maya)
     */
    public function createSource($type, $amount, $currency = 'PHP', $redirect_url = '', $metadata = []) {
        $url = $this->api_url . '/sources';
        
        // Ensure amount is integer (cents)
        $amount_in_cents = intval($amount * 100);
        
        $data = [
            'data' => [
                'attributes' => [
                    'type' => $type,
                    'amount' => $amount_in_cents,
                    'currency' => $currency,
                    'redirect' => [
                        'success' => $redirect_url . '&status=success',
                        'failed' => $redirect_url . '&status=failed'
                    ]
                ]
            ]
        ];
        
        // Add metadata only if not empty
        if (!empty($metadata)) {
            $data['data']['attributes']['metadata'] = $metadata;
        }
        
        error_log("PayMongo createSource request: " . json_encode($data));
        return $this->makeRequest('POST', $url, $data);
    }
    
    /**
     * Get source
     */
    public function getSource($source_id) {
        $url = $this->api_url . '/sources/' . $source_id;
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Make HTTP request to PayMongo API
     */
    private function makeRequest($method, $url, $data = null) {
        $curl = curl_init();
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->secret_key . ':')
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        // Log detailed request info
        error_log("PayMongo API Request URL: " . $url);
        error_log("PayMongo API Request Method: " . $method);
        if ($data) {
            error_log("PayMongo API Request Data: " . json_encode($data));
        }
        error_log("PayMongo API Response Code: " . $httpCode);
        error_log("PayMongo API Response: " . $response);
        
        curl_close($curl);
        
        if ($error) {
            error_log("PayMongo API cURL Error: " . $error);
            return ['error' => 'Connection error: ' . $error];
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            error_log("PayMongo API HTTP Error $httpCode: " . $response);
            return ['error' => 'HTTP Error ' . $httpCode, 'response' => $decodedResponse];
        }
        
        return $decodedResponse;
    }
    
    /**
     * Get webhook signature
     */
    public function verifyWebhookSignature($payload, $signature) {
        $computed_signature = hash_hmac('sha256', $payload, $this->secret_key);
        return hash_equals($signature, $computed_signature);
    }
}

/**
 * Helper function to format amount for PayMongo
 */
function formatAmountForPayMongo($amount) {
    return intval($amount * 100); // Convert to cents
}

/**
 * Helper function to format amount from PayMongo
 */
function formatAmountFromPayMongo($amount) {
    return $amount / 100; // Convert from cents
}

/**
 * Get PayMongo test card numbers for testing
 */
function getTestCardNumbers() {
    return [
        'visa_success' => '4343434343434345',
        'visa_decline' => '4571736000000075',
        'mastercard_success' => '5555555555554444',
        'mastercard_decline' => '5506900490000436'
    ];
}

/**
 * Check if PayMongo is in sandbox mode
 */
function isPayMongoSandboxMode() {
    return defined('PAYMONGO_MODE') && PAYMONGO_MODE === 'sandbox';
}

/**
 * Get PayMongo mode display name
 */
function getPayMongoModeDisplay() {
    return isPayMongoSandboxMode() ? '🧪 SANDBOX/TEST MODE' : '🔴 LIVE MODE';
}

/**
 * Generate return URL for payment
 */
function generateReturnURL($order_type = 'regular') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $host;
    
    // Log the mode for debugging
    $mode = isPayMongoSandboxMode() ? 'SANDBOX' : 'LIVE';
    error_log("[PAYMONGO-{$mode}] Generating return URL for {$order_type} order");
    
    // Add status=success parameter so payment-return.php knows payment succeeded
    if ($order_type === 'availtoday') {
        return $base_url . '/frontend/pages/cart/payment-return.php?type=availtoday&status=success';
    } else {
        return $base_url . '/frontend/pages/cart/payment-return.php?type=regular&status=success';
    }
}
?>
