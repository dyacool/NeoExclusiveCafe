<?php
/**
 * PayMongo Payment Processing Handler
 * Handles payment creation and processing for both regular and availtoday orders
 */

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Require login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Include required files
require_once '../../user-includes/database.php';
require_once 'paymongo-config.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Extract required data
    $payment_method = $input['payment_method'] ?? '';
    $order_type = $input['order_type'] ?? 'regular'; // 'regular' or 'availtoday'
    $order_data = $input['order_data'] ?? [];
    $amount = floatval($input['amount'] ?? 0);
    
    // Validate required fields
    if (empty($payment_method) || $amount <= 0 || empty($order_data)) {
        throw new Exception('Missing required payment data');
    }
    
    // Validate payment method
    if (!in_array($payment_method, ['gcash', 'paymaya', 'card'])) {
        throw new Exception('Invalid payment method');
    }
    
    // Initialize PayMongo API
    $paymongo = new PayMongoAPI();
    
    // Create order first (temporary, will be finalized after payment)
    $order_id = createTemporaryOrder($order_data, $order_type);
    
    if (!$order_id) {
        throw new Exception('Failed to create order');
    }
    
    // Prepare metadata
    $metadata = [
        'order_id' => $order_id,
        'order_type' => $order_type,
        'user_id' => $_SESSION['user_id'],
        'customer_name' => $order_data['customer_name'] ?? '',
        'customer_email' => $order_data['customer_email'] ?? ''
    ];
    
    $description = "NeoCafe Order #$order_id - " . ucfirst($order_type);
    
    // Handle different payment methods
    if (in_array($payment_method, ['gcash', 'paymaya'])) {
        // Create source for GCash/Maya
        $return_url = generateReturnURL($order_type);
        
        $result = $paymongo->createSource(
            $payment_method,
            $amount,
            'PHP',
            $return_url,
            $metadata
        );
        
        if (isset($result['error'])) {
            throw new Exception('PayMongo Error: ' . json_encode($result['error']));
        }
        
        // Store payment info in session
        $_SESSION['pending_payment'] = [
            'source_id' => $result['data']['id'],
            'order_id' => $order_id,
            'order_type' => $order_type,
            'amount' => $amount,
            'payment_method' => $payment_method
        ];
        
        echo json_encode([
            'success' => true,
            'payment_type' => 'source',
            'checkout_url' => $result['data']['attributes']['redirect']['checkout_url'],
            'source_id' => $result['data']['id']
        ]);
        
    } else if ($payment_method === 'card') {
        // Create payment intent for card payments
        $result = $paymongo->createPaymentIntent(
            $amount,
            'PHP',
            $description,
            $metadata
        );
        
        if (isset($result['error'])) {
            throw new Exception('PayMongo Error: ' . json_encode($result['error']));
        }
        
        // Store payment info in session
        $_SESSION['pending_payment'] = [
            'payment_intent_id' => $result['data']['id'],
            'client_secret' => $result['data']['attributes']['client_key'],
            'order_id' => $order_id,
            'order_type' => $order_type,
            'amount' => $amount,
            'payment_method' => $payment_method
        ];
        
        echo json_encode([
            'success' => true,
            'payment_type' => 'payment_intent',
            'payment_intent_id' => $result['data']['id'],
            'client_secret' => $result['data']['attributes']['client_key'],
            'public_key' => PAYMONGO_PUBLIC_KEY
        ]);
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Create temporary order that will be finalized after payment
 */
function createTemporaryOrder($order_data, $order_type) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Create order record
        if ($order_type === 'availtoday') {
            $order_sql = "INSERT INTO orders (
                user_id, first_name, last_name, email, phone, address, city, postal_code,
                special_instructions, shipping_method, total_amount, order_status,
                order_type, payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'availtoday', 'pending', NOW())";
        } else {
            $order_sql = "INSERT INTO orders (
                user_id, first_name, last_name, email, phone, address, city, postal_code,
                special_instructions, shipping_method, total_amount, order_status,
                order_type, payment_status, pickup_date, pickup_time, delivery_date, delivery_time, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'regular', 'pending', ?, ?, ?, ?, NOW())";
        }
        
        $order_stmt = $conn->prepare($order_sql);
        if (!$order_stmt) {
            throw new Exception("Failed to prepare order statement");
        }
        
        if ($order_type === 'availtoday') {
            $order_stmt->bind_param("isssssssssd",
                $_SESSION['user_id'],
                $order_data['first_name'],
                $order_data['last_name'],
                $order_data['email'],
                $order_data['phone'],
                $order_data['address'] ?? '',
                $order_data['city'] ?? '',
                $order_data['postal_code'] ?? '',
                $order_data['special_instructions'] ?? '',
                $order_data['shipping_method'],
                $order_data['total_amount']
            );
        } else {
            $order_stmt->bind_param("isssssssssdsssss",
                $_SESSION['user_id'],
                $order_data['first_name'],
                $order_data['last_name'],
                $order_data['email'],
                $order_data['phone'],
                $order_data['address'] ?? '',
                $order_data['city'] ?? '',
                $order_data['postal_code'] ?? '',
                $order_data['special_instructions'] ?? '',
                $order_data['shipping_method'],
                $order_data['total_amount'],
                $order_data['pickup_date'] ?? null,
                $order_data['pickup_time'] ?? null,
                $order_data['delivery_date'] ?? null,
                $order_data['delivery_time'] ?? null
            );
        }
        
        if (!$order_stmt->execute()) {
            throw new Exception("Failed to create order");
        }
        
        $order_id = $conn->insert_id;
        
        // Insert order items
        $items = json_decode($order_data['cart_items'], true);
        if ($items) {
            $item_sql = "INSERT INTO order_items (
                order_id, product_id, quantity, price, total_price, availtoday_status_id, shipping_method
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $item_stmt = $conn->prepare($item_sql);
            
            foreach ($items as $item) {
                $total_price = $item['price'] * $item['quantity'];
                $availtoday_status_id = $item['availtoday_status_id'] ?? null;
                $shipping_method = $item['shipping_method'] ?? $order_data['shipping_method'];
                
                $item_stmt->bind_param("iiiddis",
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $total_price,
                    $availtoday_status_id,
                    $shipping_method
                );
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to insert order item");
                }
            }
            $item_stmt->close();
        }
        
        $conn->commit();
        return $order_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error creating temporary order: " . $e->getMessage());
        return false;
    } finally {
        if (isset($order_stmt)) $order_stmt->close();
    }
}
?>
