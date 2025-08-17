<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Require login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

// Include database connection
require_once '../../user-includes/database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: availtoday-checkout.php");
    exit();
}

// Get form data
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$special_instructions = trim($_POST['special_instructions'] ?? '');
$shipping_method = $_POST['shipping_method'] ?? 'pickup';
$cart_total = floatval($_POST['cart_total'] ?? 0);
$has_mixed_status = intval($_POST['has_mixed_status'] ?? 0);

// Decode cart items
$cart_items = json_decode($_POST['cart_items'] ?? '[]', true);

// Validate required fields
$errors = [];
if (empty($first_name)) $errors[] = 'First name is required';
if (empty($last_name)) $errors[] = 'Last name is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($phone)) $errors[] = 'Phone number is required';
if (empty($cart_items)) $errors[] = 'No cart items found';
if ($cart_total <= 0) $errors[] = 'Invalid cart total';

// Validate delivery address if needed
$has_delivery_items = false;
foreach ($cart_items as $item) {
    if (($item['availtoday_status_id'] == 2) || 
        ($item['availtoday_status_id'] === null && $item['status_id'] == 2)) {
        $has_delivery_items = true;
        break;
    }
}

if ($has_delivery_items) {
    if (empty($address)) $errors[] = 'Delivery address is required';
    if (empty($city)) $errors[] = 'City is required';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    $_SESSION['checkout_form_data'] = $_POST;
    header("Location: availtoday-checkout.php");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Create order record
    $order_sql = "INSERT INTO orders (
        user_id, 
        first_name, 
        last_name, 
        email, 
        phone, 
        address, 
        city, 
        postal_code, 
        special_instructions, 
        shipping_method, 
        total_amount, 
        order_status,
        order_type,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'availtoday', NOW())";
    
    $order_stmt = $conn->prepare($order_sql);
    if (!$order_stmt) {
        throw new Exception("Failed to prepare order statement: " . $conn->error);
    }
    
    $order_stmt->bind_param("isssssssssd", 
        $_SESSION['user_id'],
        $first_name,
        $last_name,
        $email,
        $phone,
        $address,
        $city,
        $postal_code,
        $special_instructions,
        $shipping_method,
        $cart_total
    );
    
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create order: " . $order_stmt->error);
    }
    
    $order_id = $conn->insert_id;
    error_log("Created order ID: " . $order_id);
    
    // Insert order items
    $item_sql = "INSERT INTO order_items (
        order_id, 
        product_id, 
        quantity, 
        price, 
        total_price,
        availtoday_status_id,
        shipping_method
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $item_stmt = $conn->prepare($item_sql);
    if (!$item_stmt) {
        throw new Exception("Failed to prepare order items statement: " . $conn->error);
    }
    
    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $item_shipping_method = $item['shipping_method'];
        $availtoday_status_id = $item['availtoday_status_id'] ?: null;
        
        $item_stmt->bind_param("iiiddis",
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $item_total,
            $availtoday_status_id,
            $item_shipping_method
        );
        
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to insert order item: " . $item_stmt->error);
        }
        
        error_log("Added order item - Product ID: " . $item['product_id'] . ", Quantity: " . $item['quantity']);
    }
    
    // Clear the availtoday cart for this user
    $clear_cart_sql = "DELETE FROM cart_availtoday WHERE user_id = ?";
    $clear_cart_stmt = $conn->prepare($clear_cart_sql);
    if (!$clear_cart_stmt) {
        throw new Exception("Failed to prepare clear cart statement: " . $conn->error);
    }
    
    $clear_cart_stmt->bind_param("i", $_SESSION['user_id']);
    if (!$clear_cart_stmt->execute()) {
        throw new Exception("Failed to clear cart: " . $clear_cart_stmt->error);
    }
    
    error_log("Cleared availtoday cart for user: " . $_SESSION['user_id']);
    
    // Commit transaction
    $conn->commit();
    
    // Store order info in session for confirmation page
    $_SESSION['order_confirmation'] = [
        'order_id' => $order_id,
        'total_amount' => $cart_total,
        'shipping_method' => $shipping_method,
        'has_mixed_status' => $has_mixed_status,
        'items' => $cart_items,
        'customer_info' => [
            'name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postal_code
        ]
    ];
    
    // Clear any previous checkout data
    unset($_SESSION['availtoday_cart_items']);
    unset($_SESSION['availtoday_cart_total']);
    unset($_SESSION['availtoday_shipping_method']);
    unset($_SESSION['has_mixed_availtoday_status']);
    unset($_SESSION['checkout_errors']);
    unset($_SESSION['checkout_form_data']);
    
    // Redirect to confirmation page
    header("Location: availtoday-order-confirmation.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    error_log("Error processing availtoday checkout: " . $e->getMessage());
    
    $_SESSION['checkout_errors'] = ['An error occurred while processing your order. Please try again.'];
    $_SESSION['checkout_form_data'] = $_POST;
    
    header("Location: availtoday-checkout.php");
    exit();
} finally {
    // Close statements
    if (isset($order_stmt)) $order_stmt->close();
    if (isset($item_stmt)) $item_stmt->close();
    if (isset($clear_cart_stmt)) $clear_cart_stmt->close();
}
?>
