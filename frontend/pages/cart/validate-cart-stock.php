<?php
/**
 * Validate Cart Stock Before Checkout
 * 
 * This endpoint validates that all cart items have sufficient stock available
 * before allowing the user to proceed to checkout.
 * 
 * For pre-order items: checks products.quantity
 * For same-day items: checks quantity_per_day_sdo.quantity for today's date
 */

require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!SessionManager::isUserLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit();
}

$user_id = SessionManager::getUserId();

// Get the cart IDs and order type from POST
$cart_ids = $_POST['cart_ids'] ?? [];
$order_type = $_POST['order_type'] ?? ''; // 'preorder' or 'sameday'

if (empty($cart_ids) || empty($order_type)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request parameters'
    ]);
    exit();
}

$validation_errors = [];
$today_date = date('Y-m-d');

if ($order_type === 'preorder') {
    // Validate pre-order items against products.quantity
    foreach ($cart_ids as $cart_id) {
        $query = "
            SELECT 
                c.id as cart_id,
                c.quantity as cart_quantity,
                c.product_id,
                p.name as product_name,
                p.quantity as available_stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.id = ? AND c.user_id = ? AND p.deleted_at IS NULL
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            
            // Check if cart quantity exceeds available stock
            if ($item['cart_quantity'] > $item['available_stock']) {
                $validation_errors[] = [
                    'product_name' => $item['product_name'],
                    'cart_quantity' => $item['cart_quantity'],
                    'available_stock' => $item['available_stock'],
                    'message' => "{$item['product_name']}: You have {$item['cart_quantity']} in cart but only {$item['available_stock']} available"
                ];
            }
            
            // Check if product is out of stock
            if ($item['available_stock'] <= 0) {
                $validation_errors[] = [
                    'product_name' => $item['product_name'],
                    'cart_quantity' => $item['cart_quantity'],
                    'available_stock' => 0,
                    'message' => "{$item['product_name']}: Out of stock"
                ];
            }
        }
        
        $stmt->close();
    }
    
} elseif ($order_type === 'sameday') {
    // Validate same-day items against quantity_per_day_sdo.quantity for today
    foreach ($cart_ids as $cart_id) {
        $query = "
            SELECT 
                c.id as cart_id,
                c.quantity as cart_quantity,
                c.product_id,
                p.name as product_name,
                p.status_id,
                COALESCE(qpd.quantity, 0) as available_stock,
                qpd.date as stock_date
            FROM availtoday_cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = ?
            WHERE c.id = ? AND c.user_id = ? AND p.deleted_at IS NULL
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $today_date, $cart_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            
            // Check if product has same-day availability for today
            if (empty($item['stock_date'])) {
                $validation_errors[] = [
                    'product_name' => $item['product_name'],
                    'cart_quantity' => $item['cart_quantity'],
                    'available_stock' => 0,
                    'message' => "{$item['product_name']}: Not available for same-day order today"
                ];
                $stmt->close();
                continue;
            }
            
            // Check if cart quantity exceeds available stock
            if ($item['cart_quantity'] > $item['available_stock']) {
                $validation_errors[] = [
                    'product_name' => $item['product_name'],
                    'cart_quantity' => $item['cart_quantity'],
                    'available_stock' => $item['available_stock'],
                    'message' => "{$item['product_name']}: You have {$item['cart_quantity']} in cart but only {$item['available_stock']} available today"
                ];
            }
            
            // Check if product is out of stock for today
            if ($item['available_stock'] <= 0) {
                $validation_errors[] = [
                    'product_name' => $item['product_name'],
                    'cart_quantity' => $item['cart_quantity'],
                    'available_stock' => 0,
                    'message' => "{$item['product_name']}: Out of stock for today"
                ];
            }
            
            // Additional check: Verify the product is configured for same-day orders
            // Status 4 = Same-day only, or status 1/2/3 with availtoday_status_id
            $config_query = "
                SELECT 
                    p.status_id,
                    p.availtoday_status_id,
                    COUNT(CASE 
                        WHEN p.status_id = 4 THEN tpd.available_date
                        WHEN p.status_id IN (1,2,3) AND p.availtoday_status_id IS NOT NULL THEN rptd.available_date
                    END) as has_today_date
                FROM products p
                LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id AND tpd.available_date = ?
                LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id AND rptd.available_date = ?
                WHERE p.id = ?
                GROUP BY p.id, p.status_id, p.availtoday_status_id
            ";
            
            $config_stmt = $conn->prepare($config_query);
            $config_stmt->bind_param("ssi", $today_date, $today_date, $item['product_id']);
            $config_stmt->execute();
            $config_result = $config_stmt->get_result();
            
            if ($config_result->num_rows > 0) {
                $config = $config_result->fetch_assoc();
                
                // Check if today's date is configured
                if ($config['has_today_date'] == 0) {
                    $validation_errors[] = [
                        'product_name' => $item['product_name'],
                        'cart_quantity' => $item['cart_quantity'],
                        'available_stock' => 0,
                        'message' => "{$item['product_name']}: Not configured for same-day order today"
                    ];
                }
            }
            
            $config_stmt->close();
        }
        
        $stmt->close();
    }
}

// Return validation results
if (empty($validation_errors)) {
    echo json_encode([
        'success' => true,
        'message' => 'All items have sufficient stock'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Some items have insufficient stock or are unavailable',
        'errors' => $validation_errors
    ]);
}

$conn->close();
?>
