<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');


error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once __DIR__ . '/database-config.php';


$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$coupon_code = strtoupper(trim($input['coupon_code'] ?? ''));
$subtotal = floatval($input['subtotal'] ?? 0);
$cart_items = $input['cart_items'] ?? [];

if (empty($coupon_code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
    exit();
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order amount']);
    exit();
}

try {
    $conn = getDBConnection();
    createPromotionsTable($conn);
    

    $sql = "SELECT * FROM promotions WHERE code = ? AND status = 'active' AND activation_date <= CURDATE() AND expiration_date >= CURDATE()";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $coupon_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
        exit();
    }
    
    $coupon = $result->fetch_assoc();
    $stmt->close();
    

    if ($coupon['min_purchase'] > 0 && $subtotal < $coupon['min_purchase']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum purchase of ₱' . number_format($coupon['min_purchase'], 2) . ' required for this coupon'
        ]);
        exit();
    }
    

    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit']);
        exit();
    }
    

    $applicable_to = $coupon['applicable_to'] ?? 'all';
    if ($applicable_to !== 'all') {

    }
    

    $discount_amount = 0;
    $discount_type = $coupon['type'] ?? 'fixed';
    $discount_value = floatval($coupon['value'] ?? 0);
    
    if ($discount_type === 'percentage') {
        $discount_amount = ($subtotal * $discount_value) / 100;
    } elseif ($discount_type === 'fixed') {
        $discount_amount = $discount_value;
    } elseif ($discount_type === 'free_shipping') {
        $discount_amount = 0; 
    }
    

    $discount_amount = min($discount_amount, $subtotal);
    

    $coupon_data = [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'title' => $coupon['title'],
        'type' => $discount_type,
        'value' => $discount_value,
        'discount_amount' => $discount_amount,
        'min_purchase' => floatval($coupon['min_purchase']),
        'applicable_to' => $applicable_to,
        'include_free_shipping' => (bool)$coupon['include_free_shipping'],
        'prevent_discounted' => (bool)$coupon['prevent_discounted']
    ];
    

    $message = 'Coupon applied successfully! ';
    if ($discount_type === 'percentage') {
        $message .= "You saved {$discount_value}% (₱" . number_format($discount_amount, 2) . ")";
    } elseif ($discount_type === 'fixed') {
        $message .= "You saved ₱" . number_format($discount_amount, 2);
    } elseif ($discount_type === 'free_shipping') {
        $message .= "Free shipping applied!";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'coupon' => $coupon_data,
        'discount_amount' => $discount_amount
    ]);
    
} catch (Exception $e) {
    error_log('Coupon validation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error validating coupon. Please try again.']);
}

$conn->close();
?>
