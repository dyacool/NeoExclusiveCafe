<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/mailer.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/dummy-mailer.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log errors
function logError($message, $data = null) {
    $logDir = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/logs";
    $logFile = $logDir . "/order_errors.log";
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        if (!mkdir($logDir, 0777, true)) {
            // If we can't create the directory, just return without logging
            return;
        }
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    $logMessage .= "\n" . str_repeat('-', 50) . "\n";
    
    // Try to write to the log file, but don't throw errors if it fails
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if (!isset($_SESSION["user_id"])) {
    header("Location: /NeoExclusiveCafe/pages/auth/login-signup.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = null;
$error = null;
$debug_info = [];

// Process the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Log POST data for debugging
        logError("POST Data Received", $_POST);
        
        // Get form data with validation
        $required_fields = ['customer_name', 'customer_contact', 'customer_address', 'payment_method', 'delivery_method'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $customer_name = $_POST['customer_name'];
        $customer_contact = $_POST['customer_contact'];
        $customer_email = $_POST['customer_email'] ?? ''; // Keep for display but don't use in DB
        $customer_address = $_POST['customer_address'];
        $payment_method = $_POST['payment_method'];
        $delivery_method = $_POST['delivery_method'];
        $notes = $_POST['notes'] ?? '';
        $subtotal = floatval($_POST['subtotal'] ?? 0);
        
        // Use the actual_time parameter if provided
        $delivery_time = $_POST['actual_time'] ?? $_POST['delivery_time'] ?? '';
        
        // Debug log form data
        $debug_info['form_data'] = [
            'customer_name' => $customer_name,
            'customer_contact' => $customer_contact,
            'customer_email' => $customer_email, // Keep in debug log
            'payment_method' => $payment_method,
            'delivery_method' => $delivery_method,
            'subtotal' => $subtotal
        ];
        logError("Processed Form Data", $debug_info['form_data']);
        
        // Get delivery/pickup details
        $delivery_date = null;
        $pickup_date = null;
        
        if ($delivery_method == 'delivery') {
            if (empty($_POST['delivery_date'])) {
                throw new Exception("Delivery date is required for delivery orders");
            }
            $delivery_date = $_POST['delivery_date'];
            $shipping_fee = 50.00;
        } else {
            if (empty($_POST['pickup_date'])) {
                throw new Exception("Pickup date is required for pickup orders");
            }
            $pickup_date = $_POST['pickup_date'];
            $shipping_fee = 0.00;
        }
        
        $total_amount = $subtotal + $shipping_fee;
        
        // Get selected cart items
        $selected_cart_ids = explode(',', $_POST['selected_cart_ids'] ?? '');
        if (empty($selected_cart_ids)) {
            throw new Exception("No items selected for checkout");
        }

        // Debug log cart items
        $debug_info['cart_items'] = $selected_cart_ids;
        logError("Selected Cart Items", $debug_info['cart_items']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check order limit for the selected date
            $check_date = $delivery_method == 'delivery' ? $delivery_date : $pickup_date;
            
            // Get the limit for the selected date
            $limit_query = "SELECT 
                COALESCE(dl.limit_value, 
                    (SELECT default_limit FROM order_limits WHERE id = 1)
                ) as daily_limit,
                CASE WHEN os.status = 'not_accepting' OR dl.limit_value = 0 OR dl.not_accepting_orders = TRUE 
                    THEN 'not_accepting' 
                    ELSE 'accepting' 
                END as status
            FROM (SELECT ? as date) d
            LEFT JOIN date_limits dl ON d.date = dl.date
            LEFT JOIN orderdate_status os ON d.date = os.date";
            
            $limit_stmt = $conn->prepare($limit_query);
            if (!$limit_stmt) {
                throw new Exception("Failed to prepare limit check statement: " . $conn->error);
            }
            
            $limit_stmt->bind_param("s", $check_date);
            if (!$limit_stmt->execute()) {
                throw new Exception("Failed to execute limit check: " . $limit_stmt->error);
            }
            
            $limit_result = $limit_stmt->get_result();
            $limit_row = $limit_result->fetch_assoc();
            
            if ($limit_row['status'] === 'not_accepting') {
                throw new Exception("Orders are not being accepted for this date.");
            }
            
            $daily_limit = intval($limit_row['daily_limit']);
            
            // Count existing orders for the date
            $count_query = "SELECT COUNT(*) as order_count 
                FROM orders 
                WHERE (pickup_date = ? OR delivery_date = ?) 
                AND status IN ('Pending')";
            
            $count_stmt = $conn->prepare($count_query);
            if (!$count_stmt) {
                throw new Exception("Failed to prepare count statement: " . $conn->error);
            }
            
            $count_stmt->bind_param("ss", $check_date, $check_date);
            if (!$count_stmt->execute()) {
                throw new Exception("Failed to execute count query: " . $count_stmt->error);
            }
            
            $count_result = $count_stmt->get_result();
            $current_count = $count_result->fetch_assoc()['order_count'];
            
            if ($current_count >= $daily_limit) {
                throw new Exception("Sorry, the selected date has reached its order limit. Please choose a different date.");
            }

            // Count total items
            $total_items = 0;
            $placeholders = implode(',', array_fill(0, count($selected_cart_ids), '?'));
            
            $items_query = "SELECT SUM(quantity) as total_items FROM cart WHERE id IN ($placeholders)";
            $items_stmt = $conn->prepare($items_query);
            if (!$items_stmt) {
                throw new Exception("Failed to prepare items count statement: " . $conn->error);
            }
            
            // Bind parameters manually
            $types = str_repeat('i', count($selected_cart_ids));
            $bind_params = [$items_stmt, $types];
            foreach ($selected_cart_ids as $key => $value) {
                $bind_params[] = &$selected_cart_ids[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
            
            if (!$items_stmt->execute()) {
                throw new Exception("Failed to execute items count statement: " . $items_stmt->error);
            }
            
            $items_result = $items_stmt->get_result();
            $total_items = $items_result->fetch_assoc()['total_items'] ?? 0;
            
            // Debug log total items
            $debug_info['total_items'] = $total_items;
            logError("Total Items Count", $debug_info['total_items']);
            
            // Convert delivery_method to proper format for ENUM field
            if ($delivery_method == 'delivery') {
                $delivery_method = 'Delivery';
            } else {
                $delivery_method = 'Pick-up';
            }
            
            // Get the maximum order_id to manually handle auto-increment if needed
            $max_id_query = "SELECT MAX(order_id) as max_id FROM orders";
            $max_id_result = $conn->query($max_id_query);
            $next_id = 1; // Default if no orders exist
            
            if ($max_id_result && $max_id_row = $max_id_result->fetch_assoc()) {
                $next_id = (int)$max_id_row['max_id'] + 1;
            }
            
            // Initialize order_sql variable
            $order_sql = "";
            
            // Insert order with explicit order_id if needed
            if ($conn->query("SHOW COLUMNS FROM orders WHERE Field = 'order_id' AND Extra LIKE '%auto_increment%'")->num_rows == 0) {
                // No AUTO_INCREMENT, so we'll specify the ID
                $order_sql = "INSERT INTO orders (
                    order_id, customer_name, customer_contact, customer_email, customer_address, 
                    payment_method, delivery_method, delivery_date, pickup_date, 
                    delivery_time, notes, total_items, total_amount, status, order_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
                
                $order_stmt = $conn->prepare($order_sql);
                if (!$order_stmt) {
                    throw new Exception("Failed to prepare order statement: " . $conn->error);
                }
                
                // Count placeholders to ensure type string matches
                $placeholderCount = substr_count($order_sql, '?');
                logError("Parameter count for explicit ID query", [
                    'placeholder_count' => $placeholderCount,
                    'sql' => $order_sql
                ]);
                
                // Use the correct number of type specifiers (13 parameters)
                $order_stmt->bind_param(
                    "isssssssssisd",  // 13 parameters: i + 9s + i + s + d
                    $next_id, $customer_name, $customer_contact, $customer_email, $customer_address,
                    $payment_method, $delivery_method, $delivery_date, $pickup_date,
                    $delivery_time, $notes, $total_items, $total_amount
                );
            } else {
                // Using AUTO_INCREMENT
                $order_sql = "INSERT INTO orders (
                    customer_name, customer_contact, customer_email, customer_address, 
                    payment_method, delivery_method, delivery_date, pickup_date, 
                    delivery_time, notes, total_items, total_amount, status, order_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
                
                $order_stmt = $conn->prepare($order_sql);
                if (!$order_stmt) {
                    throw new Exception("Failed to prepare order statement: " . $conn->error);
                }
                
                $order_stmt->bind_param(
                    "sssssssssisd",
                    $customer_name, $customer_contact, $customer_email, $customer_address,
                    $payment_method, $delivery_method, $delivery_date, $pickup_date,
                    $delivery_time, $notes, $total_items, $total_amount
                );
            }
            
            // Debug logging after order_sql is set
            logError("Preparing to insert order", [
                'delivery_method' => $delivery_method,
                'next_id' => $next_id,
                'sql' => $order_sql,
                'user_id' => $user_id,
                'customer_details' => [
                    'name' => $customer_name,
                    'contact' => $customer_contact,
                    'address' => $customer_address
                ]
            ]);
            
            if (!$order_stmt->execute()) {
                throw new Exception("Failed to execute order statement: " . $order_stmt->error);
            }
            
            // Get the order_id - if we used auto_increment it's in insert_id, otherwise we used $next_id
            if ($conn->query("SHOW COLUMNS FROM orders WHERE Field = 'order_id' AND Extra LIKE '%auto_increment%'")->num_rows == 0) {
                $order_id = $next_id;
            } else {
                $order_id = $conn->insert_id;
            }
            
            $debug_info['order_id'] = $order_id;
            logError("Order Created", $debug_info['order_id']);
            
            // Get cart items with product details
            $cart_sql = "
                SELECT c.*, p.name as product_name, p.id as product_id, p.quantity as product_stock, pi.image_url 
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE c.id IN ($placeholders)
            ";
            
            $cart_stmt = $conn->prepare($cart_sql);
            if (!$cart_stmt) {
                throw new Exception("Failed to prepare cart statement: " . $conn->error);
            }
            
            // Bind parameters manually
            $types = str_repeat('i', count($selected_cart_ids));
            $bind_params = [$cart_stmt, $types];
            foreach ($selected_cart_ids as $key => $value) {
                $bind_params[] = &$selected_cart_ids[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
            
            if (!$cart_stmt->execute()) {
                throw new Exception("Failed to execute cart statement: " . $cart_stmt->error);
            }
            
            $cart_result = $cart_stmt->get_result();
            
            // Get the maximum item_id from order_items to manually handle auto-increment if needed
            $max_item_id_query = "SELECT MAX(item_id) as max_id FROM order_items";
            $max_item_id_result = $conn->query($max_item_id_query);
            $next_item_id = 0; // Default if no items exist
            
            if ($max_item_id_result && $max_item_id_row = $max_item_id_result->fetch_assoc()) {
                $next_item_id = (int)$max_item_id_row['max_id'] + 1;
            }
            
            // Check if item_id is auto_increment
            $item_id_auto_increment = $conn->query("SHOW COLUMNS FROM order_items WHERE Field = 'item_id' AND Extra LIKE '%auto_increment%'")->num_rows > 0;
            
            // Prepare statements for order items and stock update
            if (!$item_id_auto_increment) {
                $item_sql = "INSERT INTO order_items (
                    item_id, order_id, product_name, image_path, price, quantity
                ) VALUES (?, ?, ?, ?, ?, ?)";
            } else {
                $item_sql = "INSERT INTO order_items (
                    order_id, product_name, image_path, price, quantity
                ) VALUES (?, ?, ?, ?, ?)";
            }
            
            $item_stmt = $conn->prepare($item_sql);
            if (!$item_stmt) {
                throw new Exception("Failed to prepare order items statement: " . $item_stmt->error);
            }

            $update_stock_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?";
            $update_stock_stmt = $conn->prepare($update_stock_sql);
            if (!$update_stock_stmt) {
                throw new Exception("Failed to prepare stock update statement: " . $update_stock_stmt->error);
            }
            
            // Process each cart item
            while ($item = $cart_result->fetch_assoc()) {
                // Debug log current item
                logError("Processing Cart Item", $item);
                
                // Check if product has sufficient stock
                if ($item['product_stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: " . $item['product_name']);
                }
                
                // Insert order item
                $image_path = $item['image_url'] ?? '/NeoExclusiveCafe/assets/img/default-product.png';
                
                if (!$item_id_auto_increment) {
                    $item_stmt->bind_param(
                        "iissdi",
                        $next_item_id, $order_id, $item['product_name'], $image_path,
                        $item['price'], $item['quantity']
                    );
                    $next_item_id++; // Increment for next item
                } else {
                    $item_stmt->bind_param(
                        "issdi",
                        $order_id, $item['product_name'], $image_path,
                        $item['price'], $item['quantity']
                    );
                }
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to insert order item: " . $item_stmt->error);
                }

                // Update product stock
                $update_stock_stmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                if (!$update_stock_stmt->execute()) {
                    throw new Exception("Failed to update stock: " . $update_stock_stmt->error);
                }
            }
            
            // Delete cart items
            $delete_sql = "DELETE FROM cart WHERE id IN ($placeholders)";
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt) {
                throw new Exception("Failed to prepare cart delete statement: " . $delete_stmt->error);
            }
            
            // Bind parameters manually
            $types = str_repeat('i', count($selected_cart_ids));
            $bind_params = [$delete_stmt, $types];
            foreach ($selected_cart_ids as $key => $value) {
                $bind_params[] = &$selected_cart_ids[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Failed to delete cart items: " . $delete_stmt->error);
            }
            
            // Commit transaction
            $conn->commit();
            logError("Transaction Committed Successfully");
            
            // Send email notification to admin
            try {
                // Get order details for email
                $orderDetailsQuery = "SELECT 
                    o.*,
                    i.item_id,
                    i.product_name,
                    i.price,
                    i.quantity
                FROM orders o
                LEFT JOIN order_items i ON o.order_id = i.order_id
                WHERE o.order_id = ?";
                
                $orderStmt = $conn->prepare($orderDetailsQuery);
                if ($orderStmt) {
                    $orderStmt->bind_param("i", $order_id);
                    $orderStmt->execute();
                    $orderResult = $orderStmt->get_result();
                    
                    $orderDetails = null;
                    $orderItems = [];
                    
                    while ($row = $orderResult->fetch_assoc()) {
                        if ($orderDetails === null) {
                            $orderDetails = [
                                'order_id' => $row['order_id'],
                                'order_date' => $row['order_date'],
                                'customer_name' => $row['customer_name'],
                                'customer_contact' => $row['customer_contact'],
                                'customer_address' => $row['customer_address'],
                                'payment_method' => $row['payment_method'],
                                'order_type' => $row['delivery_method'],
                                'pickup_date' => $row['pickup_date'],
                                'delivery_date' => $row['delivery_date'],
                                'pickup_time' => $row['delivery_time'],
                                'notes' => $row['notes'],
                                'total_items' => $row['total_items'],
                                'total_amount' => $row['total_amount'],
                                'status' => $row['status']
                            ];
                        }
                        
                        if (!empty($row['item_id'])) {
                            $orderItems[] = [
                                'item_id' => $row['item_id'],
                                'product_name' => $row['product_name'],
                                'price' => $row['price'],
                                'quantity' => $row['quantity']
                            ];
                        }
                    }
                    
                    if ($orderDetails) {
                        // Restructure order details to match email function expectations
                        $emailOrderDetails = [
                            'order_id' => $orderDetails['order_id'],
                            'order_date' => $orderDetails['order_date'],
                            'customer_name' => $orderDetails['customer_name'],
                            'user_email' => $orderDetails['customer_contact'], // Using contact as email
                            'customer_contact' => $orderDetails['customer_contact'],
                            'customer_address' => $orderDetails['customer_address'],
                            'payment_method' => $orderDetails['payment_method'],
                            'order_type' => $orderDetails['order_type'],
                            'pickup_date' => $orderDetails['pickup_date'],
                            'delivery_date' => $orderDetails['delivery_date'],
                            'pickup_time' => $orderDetails['pickup_time'],
                            'order_notes' => $orderDetails['notes'],
                            'cart_total' => $orderDetails['total_amount'] - ($orderDetails['order_type'] === 'Delivery' ? 50.00 : 0.00),
                            'shipping_fee' => $orderDetails['order_type'] === 'Delivery' ? 50.00 : 0.00,
                            'total_amount' => $orderDetails['total_amount'],
                            'cart_items' => array_map(function($item) {
                                return [
                                    'name' => $item['product_name'],
                                    'price' => $item['price'],
                                    'quantity' => $item['quantity']
                                ];
                            }, $orderItems)
                        ];
                        
                        // Debug logging for email process
                        error_log("Starting order email notification process");
                        error_log("Order details for email: " . print_r($emailOrderDetails, true));
                        
                        if ($isDevelopment) {
                            error_log("Running in development mode");
                            $emailSent = sendDummyEmail($adminEmail = getAdminEmail(), 
                                "Order #{$emailOrderDetails['order_id']} - {$emailOrderDetails['order_type']} - " . 
                                ($emailOrderDetails['order_type'] == 'Pick-up' ? $emailOrderDetails['pickup_date'] : $emailOrderDetails['delivery_date']),
                                createOrderEmailBody($emailOrderDetails));
                            error_log("Dummy email notification " . ($emailSent ? "saved to log" : "failed"));
                            logError("Dummy email notification " . ($emailSent ? "saved to log" : "failed"));
                        } else {
                            error_log("Running in production mode");
                            $emailSent = sendOrderNotificationEmail($emailOrderDetails);
                            error_log("Email notification " . ($emailSent ? "sent" : "failed"));
                            logError("Email notification " . ($emailSent ? "sent" : "failed"));
                        }
                        
                        if (!$emailSent) {
                            error_log("Failed to send email notification");
                            // Add error to debug info but don't stop the order process
                            $debug_info['email_error'] = "Failed to send order notification email";
                        }
                    }
                }
            } catch (Exception $e) {
                // Just log the error, don't stop the order process if email fails
                logError("Email notification error: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            logError("Transaction Rolled Back", [
                'error' => $e->getMessage(),
                'debug_info' => $debug_info
            ]);
            throw $e;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        logError("Order Processing Error", [
            'error' => $error,
            'debug_info' => $debug_info
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="/NeoExclusiveCafe/css/users/checkout.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .confirmation-box {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            margin-top: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .confirmation-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        .order-details {
            margin-top: 30px;
            text-align: left;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .order-details h3 {
            margin-bottom: 15px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-row label {
            font-weight: bold;
            width: 150px;
        }
        .buttons {
            margin-top: 30px;
        }
        .buttons a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background-color: #4a4a4a;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .buttons a:hover {
            background-color: #333;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .debug-info {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/customer-navigation.php"; ?>
    
    <div class="confirmation-container">
        <?php if ($error): ?>
            <div class="error-message">
                <h2>Error</h2>
                <p><?= htmlspecialchars($error) ?></p>
                <?php if (!empty($debug_info)): ?>
                    <div class="debug-info">
                        <h3>Debug Information:</h3>
                        <pre><?= htmlspecialchars(print_r($debug_info, true)) ?></pre>
                    </div>
                <?php endif; ?>
                <div class="buttons">
                    <a href="/NeoExclusiveCafe/pages/users/cart.php">Return to Cart</a>
                </div>
            </div>
        <?php elseif ($order_id): ?>
            <div class="confirmation-box">
                <div class="confirmation-icon">✓</div>
                <h2>Order Confirmed!</h2>
                <p>Thank you for your order. Your order has been received and is being processed.</p>
                <p>Order #: <strong><?= $order_id ?></strong></p>
                
                <div class="order-details">
                    <h3>Order Details</h3>
                    <div class="detail-row">
                        <label>Name:</label>
                        <span><?= htmlspecialchars($_POST['customer_name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <label>Email:</label>
                        <span><?= isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : 'N/A' ?></span>
                    </div>
                    <div class="detail-row">
                        <label>Contact:</label>
                        <span><?= htmlspecialchars($_POST['customer_contact']) ?></span>
                    </div>
                    <div class="detail-row">
                        <label>Address:</label>
                        <span><?= htmlspecialchars($_POST['customer_address']) ?></span>
                    </div>
                    <div class="detail-row">
                        <label>Payment Method:</label>
                        <span><?= htmlspecialchars(ucfirst($_POST['payment_method'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <label>Delivery Method:</label>
                        <span><?= htmlspecialchars(ucfirst($_POST['delivery_method'])) ?></span>
                    </div>
                    <?php if ($_POST['delivery_method'] == 'delivery'): ?>
                        <div class="detail-row">
                            <label>Delivery Date:</label>
                            <span><?= htmlspecialchars($_POST['delivery_date']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="detail-row">
                            <label>Pickup Date:</label>
                            <span><?= htmlspecialchars($_POST['pickup_date']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <label>Time:</label>
                        <span><?= htmlspecialchars($delivery_time) ?></span>
                    </div>
                    <div class="detail-row">
                        <label>Total Amount:</label>
                        <span>₱<?= number_format($total_amount, 2) ?></span>
                    </div>
                </div>
                
                <div class="buttons">
                    <a href="/NeoExclusiveCafe/pages/users/user-dashboard.php">Return to Dashboard</a>
                    <a href="/NeoExclusiveCafe/pages/users/product-dashboard.php">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="error-message">
                <h2>No Order Information</h2>
                <p>No order information was found. Please try again.</p>
                <div class="buttons">
                    <a href="/NeoExclusiveCafe/pages/users/cart.php">Return to Cart</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
