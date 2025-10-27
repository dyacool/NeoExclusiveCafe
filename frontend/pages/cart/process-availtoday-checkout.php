<?php
// Enable error reporting and set custom log file
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\htdocs\NeoCafe\logs\php_errors.log');

// Create logs directory if it doesn't exist
$log_dir = 'C:\xampp\htdocs\NeoCafe\logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Log that the file is being executed
error_log("========================================");
error_log("process-availtoday-checkout.php STARTED at " . date('Y-m-d H:i:s'));
error_log("========================================");

// Require login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

// Include database connection
require_once '../../../backend/pages/admin-includes/database.php';

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

// Process coupon data if provided
$discount_amount = 0;
$applied_coupon = null;

if (!empty($_POST['applied_coupon'])) {
    $applied_coupon = json_decode($_POST['applied_coupon'], true);
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    
    error_log("Coupon applied: " . print_r($applied_coupon, true));
    error_log("Discount amount: " . $discount_amount);
}

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
    
    // Create order record - matching actual orders table structure
    $customer_full_name = $first_name . ' ' . $last_name;
    $delivery_method_enum = ($shipping_method === 'delivery') ? 'Delivery' : 'Pick-up';
    $combined_notes = $special_instructions;
    
    // Include coupon information in notes if applied
    if ($applied_coupon) {
        $coupon_info = "\n\nCoupon Applied: " . $applied_coupon['code'] . 
                       " - Discount: ₱" . number_format($discount_amount, 2);
        $combined_notes .= $coupon_info;
    }
    
    $today_date = date('Y-m-d');
    $pickup_time = '10:00:00'; // Default pickup time for same-day orders
    
    // Calculate final total with discount
    $final_total = $cart_total - $discount_amount;
    
    // Validate that total is not negative
    if ($final_total < 0) {
        $final_total = 0;
    }
    
    error_log("Cart total: $cart_total, Discount: $discount_amount, Final total: $final_total");
    
    // Combine address fields
    $full_address = trim($address);
    if (!empty($city)) {
        $full_address .= ', ' . $city;
    }
    if (!empty($postal_code)) {
        $full_address .= ' ' . $postal_code;
    }
    
    $order_sql = "INSERT INTO orders (
        customer_name,
        customer_contact,
        customer_email,
        customer_address,
        payment_method,
        total_items,
        total_amount,
        status,
        delivery_method,
        pickup_date,
        pickup_time,
        notes,
        customer_id
    ) VALUES (?, ?, ?, ?, 'Cash on Delivery', ?, ?, 'Pending', ?, ?, ?, ?, NULL)";
    
    $order_stmt = $conn->prepare($order_sql);
    if (!$order_stmt) {
        throw new Exception("Failed to prepare order statement: " . $conn->error);
    }
    
    // Calculate total items
    $total_items = 0;
    foreach ($cart_items as $item) {
        $total_items += $item['quantity'];
    }
    
    error_log("Preparing to insert order: Name=$customer_full_name, Email=$email, Phone=$phone, Total=$final_total, Items=$total_items");
    
    $order_stmt->bind_param("sssidsss", 
        $customer_full_name,
        $phone,
        $email,
        $full_address,
        $total_items,
        $final_total,
        $delivery_method_enum,
        $today_date,
        $pickup_time,
        $combined_notes
    );
    
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create order: " . $order_stmt->error);
    }
    
    $order_id = $conn->insert_id;
    error_log("Created order ID: " . $order_id);
    
    // Insert order items - matching actual order_items table structure
    $item_sql = "INSERT INTO order_items (
        order_id, 
        product_name,
        image_path,
        price, 
        quantity
    ) VALUES (?, ?, NULL, ?, ?)";
    
    $item_stmt = $conn->prepare($item_sql);
    if (!$item_stmt) {
        throw new Exception("Failed to prepare order items statement: " . $conn->error);
    }
    
    foreach ($cart_items as $item) {
        $item_stmt->bind_param("isdi",
            $order_id,
            $item['name'],
            $item['price'],
            $item['quantity']
        );
        
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to insert order item: " . $item_stmt->error);
        }
        
        error_log("Added order item - Product: " . $item['name'] . ", Quantity: " . $item['quantity']);
        
        // Update same-day order inventory - subtract from quantity_per_day_sdo table
        $product_id = $item['product_id'];
        $ordered_quantity = $item['quantity'];
        $today_date = date('Y-m-d');
        
        error_log("=== SAME-DAY INVENTORY UPDATE START ===");
        error_log("Product ID: $product_id, Ordered Quantity: $ordered_quantity, Date: $today_date");
        
        // Get product name for logging
        $product_name_sql = "SELECT name FROM products WHERE id = ?";
        $product_name_stmt = $conn->prepare($product_name_sql);
        $product_name_stmt->bind_param("i", $product_id);
        $product_name_stmt->execute();
        $product_name_result = $product_name_stmt->get_result();
        $product_name = 'Unknown';
        if ($product_name_row = $product_name_result->fetch_assoc()) {
            $product_name = $product_name_row['name'];
        }
        $product_name_stmt->close();
        error_log("Product Name: $product_name");
        
        // Check current stock in quantity_per_day_sdo for today
        $stock_check_sql = "SELECT quantity FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?";
        $stock_check_stmt = $conn->prepare($stock_check_sql);
        $stock_check_stmt->bind_param("is", $product_id, $today_date);
        $stock_check_stmt->execute();
        $stock_result = $stock_check_stmt->get_result();
        
        error_log("Stock check query executed. Rows found: " . $stock_result->num_rows);
        
        if ($stock_row = $stock_result->fetch_assoc()) {
            $current_stock = $stock_row['quantity'];
            error_log("Current stock in quantity_per_day_sdo: $current_stock");
            
            // Check if there's sufficient stock
            if ($current_stock >= $ordered_quantity) {
                // Update same-day stock for today
                $update_stock_sql = "UPDATE quantity_per_day_sdo SET quantity = quantity - ? WHERE product_id = ? AND date = ?";
                $update_stock_stmt = $conn->prepare($update_stock_sql);
                $update_stock_stmt->bind_param("iis", $ordered_quantity, $product_id, $today_date);
                
                error_log("Executing UPDATE query: quantity = quantity - $ordered_quantity WHERE product_id = $product_id AND date = $today_date");
                
                if ($update_stock_stmt->execute()) {
                    $affected_rows = $update_stock_stmt->affected_rows;
                    error_log("UPDATE executed successfully. Affected rows: $affected_rows");
                    error_log("Successfully updated same-day inventory for product ID $product_id on $today_date: reduced by $ordered_quantity");
                    
                    // Check if same-day quantity reached 0
                    $new_stock = $current_stock - $ordered_quantity;
                    error_log("New stock after deduction: $new_stock");
                    if ($new_stock <= 0) {
                        error_log("Product '$product_name' (ID: $product_id) same-day stock depleted for $today_date");
                    }
                } else {
                    error_log("FAILED to update same-day inventory for product ID $product_id: " . $update_stock_stmt->error);
                }
                $update_stock_stmt->close();
            } else {
                error_log("INSUFFICIENT STOCK for product '$product_name' (ID: $product_id). Available: $current_stock, Requested: $ordered_quantity");
            }
        } else {
            error_log("NO STOCK ENTRY FOUND in quantity_per_day_sdo for product ID $product_id on date $today_date");
        }
        $stock_check_stmt->close();
        error_log("=== SAME-DAY INVENTORY UPDATE END ===");
    }
    
    // Clear the availtoday cart for this user
    $clear_cart_sql = "DELETE FROM availtoday_cart WHERE user_id = ?";
    $clear_cart_stmt = $conn->prepare($clear_cart_sql);
    if (!$clear_cart_stmt) {
        throw new Exception("Failed to prepare clear cart statement: " . $conn->error);
    }
    
    $clear_cart_stmt->bind_param("i", $_SESSION['user_id']);
    if (!$clear_cart_stmt->execute()) {
        throw new Exception("Failed to clear cart: " . $clear_cart_stmt->error);
    }
    
    error_log("Cleared availtoday cart for user: " . $_SESSION['user_id']);
    
    // Update coupon/voucher usage count if applied
    if ($applied_coupon && isset($applied_coupon['id'])) {
        $is_voucher = isset($applied_coupon['is_voucher']) && $applied_coupon['is_voucher'];
        
        if ($is_voucher) {
            // Update refund_vouchers table - mark as used
            $update_voucher_sql = "UPDATE refund_vouchers SET status = 'used' WHERE id = ?";
            $update_voucher_stmt = $conn->prepare($update_voucher_sql);
            
            if ($update_voucher_stmt) {
                $voucher_id = intval($applied_coupon['id']);
                $update_voucher_stmt->bind_param("i", $voucher_id);
                
                if (!$update_voucher_stmt->execute()) {
                    error_log("Warning: Failed to update voucher status: " . $update_voucher_stmt->error);
                } else {
                    error_log("Successfully marked voucher as used for voucher ID: " . $voucher_id);
                }
                
                $update_voucher_stmt->close();
            } else {
                error_log("Warning: Failed to prepare voucher update statement: " . $conn->error);
            }
        } else {
            // Update promotions table - increment usage count
            $update_coupon_sql = "UPDATE promotions SET used_count = used_count + 1 WHERE id = ?";
            $update_coupon_stmt = $conn->prepare($update_coupon_sql);
            
            if ($update_coupon_stmt) {
                $coupon_id = intval($applied_coupon['id']);
                $update_coupon_stmt->bind_param("i", $coupon_id);
                
                if (!$update_coupon_stmt->execute()) {
                    error_log("Warning: Failed to update coupon usage count: " . $update_coupon_stmt->error);
                } else {
                    error_log("Successfully updated coupon usage count for coupon ID: " . $coupon_id);
                }
                
                $update_coupon_stmt->close();
            } else {
                error_log("Warning: Failed to prepare coupon update statement: " . $conn->error);
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    error_log("========================================");
    error_log("TRANSACTION COMMITTED SUCCESSFULLY");
    error_log("Order ID: $order_id created successfully");
    error_log("========================================");
    
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
    
    error_log("========================================");
    error_log("ERROR PROCESSING AVAILTODAY CHECKOUT");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error File: " . $e->getFile());
    error_log("Error Line: " . $e->getLine());
    error_log("Stack Trace: " . $e->getTraceAsString());
    error_log("========================================");
    
    $_SESSION['checkout_errors'] = ['An error occurred while processing your order: ' . $e->getMessage()];
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
