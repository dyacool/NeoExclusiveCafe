<?php
/**
 * PayMongo Payment Processing Handler
 * Handles payment creation and processing for both regular and availtoday orders
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display_errors to prevent HTML output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Start output buffering to catch any accidental output
ob_start();

// Start session with dynamic domain
$session_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => $session_domain
]);
session_start();

// Set JSON content type
header('Content-Type: application/json');

// Test if includes work
try {
    require_once '../../../backend/pages/admin-includes/database.php';
    error_log("Database include successful");
    
    // Test database connection
    if (isset($conn) && $conn->ping()) {
        error_log("Database connection successful");
    } else {
        error_log("Database connection failed");
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    error_log("Database include failed: " . $e->getMessage());
    throw $e;
}

try {
    require_once 'paymongo-config.php';
    error_log("PayMongo config include successful");
} catch (Exception $e) {
    error_log("PayMongo config include failed: " . $e->getMessage());
    throw $e;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    error_log("=== PAYMENT PROCESSING STARTED ===");
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // Get raw input for debugging
    $raw_input = file_get_contents('php://input');
    error_log("Raw payment input: " . $raw_input);
    
    // Get JSON input
    $input = json_decode($raw_input, true);
    
    if (!$input) {
        error_log("JSON decode failed. Raw input: " . $raw_input);
        throw new Exception('Invalid JSON input');
    }
    
    error_log("Parsed input: " . json_encode($input));
    
    // Extract required data
    $payment_method = $input['payment_method'] ?? '';
    $order_type = $input['order_type'] ?? 'regular'; // 'regular' or 'availtoday'
    $order_data = $input['order_data'] ?? [];
    $amount = floatval($input['amount'] ?? 0);
    
    error_log("Payment method: $payment_method, Order type: $order_type, Amount: $amount");
    
    // Validate required fields
    if (empty($payment_method)) {
        error_log("Missing payment method");
        throw new Exception('Missing payment method');
    }
    
    if ($amount <= 0) {
        error_log("Invalid amount: $amount");
        throw new Exception('Invalid amount');
    }
    
    if (empty($order_data)) {
        error_log("Missing order data");
        throw new Exception('Missing order data');
    }
    
    // Validate payment method
    if (!in_array($payment_method, ['gcash', 'paymaya', 'card'])) {
        error_log("Invalid payment method: $payment_method");
        throw new Exception('Invalid payment method: ' . $payment_method);
    }
    
    // Initialize PayMongo API
    error_log("Initializing PayMongo API");
    
    // Test if PayMongo class exists
    if (!class_exists('PayMongoAPI')) {
        error_log("PayMongoAPI class not found!");
        throw new Exception('PayMongo configuration error');
    }
    
    $paymongo = new PayMongoAPI();
    error_log("PayMongo API initialized successfully");
    
    // Test API connectivity with a simple request
    error_log("Testing PayMongo API connectivity...");
    
    // Create order first (simplified without database for now)
    $order_id = rand(1000, 9999); // Generate simple order ID
    error_log("Generated order ID: $order_id");
    
    // Prepare metadata (PayMongo requires flat key-value pairs, no nested objects)
    $metadata = [
        'order_id' => (string)$order_id,
        'order_type' => (string)$order_type,
        'customer_name' => (string)($order_data['first_name'] . ' ' . $order_data['last_name']),
        'customer_email' => (string)($order_data['email'] ?? ''),
        'phone' => (string)($order_data['phone'] ?? ''),
        'shipping_method' => (string)($order_data['shipping_method'] ?? '')
    ];
    
    error_log("Metadata prepared: " . json_encode($metadata));
    
    $description = "NeoCafe Order #$order_id - " . ucfirst($order_type);
    
    // Handle different payment methods
    if (in_array($payment_method, ['gcash', 'paymaya'])) {
        // Create source for GCash/Maya
        error_log("Creating source for $payment_method");
        
        // Test if generateReturnURL function exists
        if (!function_exists('generateReturnURL')) {
            error_log("generateReturnURL function not found!");
            throw new Exception('Return URL generation error');
        }
        
        $return_url = generateReturnURL($order_type);
        error_log("Return URL: $return_url");
        
        error_log("Calling PayMongo createSource with amount: $amount");
        
        $result = $paymongo->createSource(
            $payment_method,
            $amount,
            'PHP',
            $return_url,
            $metadata
        );
        
        error_log("PayMongo createSource result: " . json_encode($result));
        
        if (isset($result['error'])) {
            error_log('PayMongo Source Error: ' . json_encode($result));
            // Include more detailed error info in the response
            $errorDetails = [
                'paymongo_error' => $result['error'],
                'paymongo_response' => isset($result['response']) ? $result['response'] : null,
                'http_code' => isset($result['response']['errors']) ? 'API_ERROR' : 'HTTP_ERROR'
            ];
            throw new Exception('PayMongo Error: ' . json_encode($errorDetails));
        }
        
        // Store payment info in session
        $_SESSION['pending_payment'] = [
            'source_id' => $result['data']['id'],
            'order_id' => $order_id,
            'order_type' => $order_type,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'order_data' => $order_data
        ];
        
        $response = [
            'success' => true,
            'payment_type' => 'source',
            'payment_url' => $result['data']['attributes']['redirect']['checkout_url'],
            'source_id' => $result['data']['id']
        ];
        
        error_log("Sending response: " . json_encode($response));
        echo json_encode($response);
        exit(); // Ensure clean exit after response
        
    } else if ($payment_method === 'card') {
        // Create payment intent for card payments
        $result = $paymongo->createPaymentIntent(
            $amount,
            'PHP',
            $description,
            $metadata
        );
        
        if (isset($result['error'])) {
            error_log('PayMongo Source Error: ' . json_encode($result));
            // Include more detailed error info in the response
            $errorDetails = [
                'paymongo_error' => $result['error'],
                'paymongo_response' => isset($result['response']) ? $result['response'] : null,
                'http_code' => isset($result['response']['errors']) ? 'API_ERROR' : 'HTTP_ERROR'
            ];
            throw new Exception('PayMongo Error: ' . json_encode($errorDetails));
        }
        
        // Store payment info in session
        $_SESSION['pending_payment'] = [
            'payment_intent_id' => $result['data']['id'],
            'client_secret' => $result['data']['attributes']['client_key'],
            'order_id' => $order_id,
            'order_type' => $order_type,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'order_data' => $order_data
        ];
        
        $response = [
            'success' => true,
            'payment_type' => 'payment_intent',
            'payment_intent_id' => $result['data']['id'],
            'client_secret' => $result['data']['attributes']['client_key'],
            'public_key' => 'pk_test_1XUMJ3yMs8QZugdq3uWr8vYU'
        ];
        
        error_log("Sending card response: " . json_encode($response));
        echo json_encode($response);
        exit(); // Ensure clean exit after response
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    error_log("Payment processing error stack: " . $e->getTraceAsString());
    
    // Clean any output buffer
    ob_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
    exit();
}

// Clean any accidental output and ensure only JSON is sent
ob_end_flush();
?>
