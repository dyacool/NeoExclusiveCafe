<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Disable display_errors to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);


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
    
    // First, try to find in promotions table (regular coupons)
    $sql = "SELECT * FROM promotions WHERE code = ? AND status = 'active' AND activation_date <= CURDATE() AND expiration_date >= CURDATE()";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $coupon_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $coupon = null;
    $is_voucher = false;
    $stmt_closed = false;
    
    if ($result->num_rows > 0) {
        // Found in promotions table
        $coupon = $result->fetch_assoc();
        $stmt->close();
        $stmt_closed = true;
    } else {
        // Not found in promotions, try refund_vouchers table
        $stmt->close();
        $stmt_closed = true;
        
        $voucher_sql = "SELECT * FROM refund_vouchers WHERE voucher_code = ? AND status = 'active' AND expiry_date >= CURDATE()";
        $voucher_stmt = $conn->prepare($voucher_sql);
        
        if (!$voucher_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $voucher_stmt->bind_param("s", $coupon_code);
        $voucher_stmt->execute();
        $voucher_result = $voucher_stmt->get_result();
        
        if ($voucher_result->num_rows > 0) {
            // Found in refund_vouchers table
            $voucher = $voucher_result->fetch_assoc();
            $is_voucher = true;
            
            // Convert voucher to coupon format
            $coupon = [
                'id' => $voucher['id'],
                'code' => $voucher['voucher_code'],
                'title' => 'Refund Voucher',
                'type' => 'fixed',
                'value' => floatval($voucher['amount']),
                'min_purchase' => 0,
                'usage_limit' => 1,
                'used_count' => ($voucher['status'] === 'used') ? 1 : 0,
                'applicable_to' => 'all',
                'include_free_shipping' => false,
                'prevent_discounted' => false
            ];
        }
        
        $voucher_stmt->close();
    }
    
    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
        exit();
    }
    

    if ($coupon['min_purchase'] > 0 && $subtotal < $coupon['min_purchase']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum purchase of ₱' . number_format($coupon['min_purchase'], 2) . ' required for this coupon'
        ]);
        exit();
    }
    

    // Check usage limit (vouchers can only be used once)
    if ($is_voucher) {
        // For vouchers, check if status is 'used'
        if ($coupon['used_count'] >= 1) {
            echo json_encode(['success' => false, 'message' => 'This voucher has already been used']);
            exit();
        }
    } else {
        // For regular coupons, check usage limit
        if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
            echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit']);
            exit();
        }
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
        'prevent_discounted' => (bool)$coupon['prevent_discounted'],
        'is_voucher' => $is_voucher
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
