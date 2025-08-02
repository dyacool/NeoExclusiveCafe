<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/NeoExclusiveCafe/php/includes/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/NeoExclusiveCafe/php/includes/mailer.php';

// Ensure no output before JSON response
ob_start();

// Function to send email
function sendOrderEmail($orderDetails, $adminEmail) {
    try {
        // Create order notification data structure
        $notificationData = [
            'order_id' => $orderDetails['order_id'],
            'customer_name' => $orderDetails['user_name'],
            'user_email' => $orderDetails['user_email'],
            'customer_contact' => $orderDetails['contact_number'],
            'customer_address' => $orderDetails['delivery_method'] === 'delivery' ? $orderDetails['delivery_address'] : '',
            'delivery_method' => $orderDetails['delivery_method'],
            'pickup_date' => $orderDetails['delivery_method'] === 'pickup' ? $orderDetails['pickup_date'] : null,
            'pickup_time' => $orderDetails['delivery_method'] === 'pickup' ? $orderDetails['pickup_time'] : null,
            'delivery_date' => $orderDetails['delivery_method'] === 'delivery' ? $orderDetails['delivery_date'] : null,
            'delivery_time' => $orderDetails['delivery_method'] === 'delivery' ? $orderDetails['delivery_time'] : null,
            'payment_method' => $orderDetails['payment_method'],
            'cart_items' => $orderDetails['cart_items'],
            'cart_total' => $orderDetails['cart_total'],
            'shipping_fee' => $orderDetails['shipping_fee'],
            'total_amount' => $orderDetails['total_amount'],
            'order_notes' => $orderDetails['order_notes'] ?? ''
        ];

        // Use the mailer function from mailer.php
        $result = sendOrderNotificationEmail($notificationData);
        error_log("Order notification email " . ($result ? "sent successfully" : "failed to send") . " to " . $adminEmail);
        return $result;
    } catch (Exception $e) {
        error_log("Error sending order notification email: " . $e->getMessage());
        return false;
    }
}

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    // Get admin email from database
    $admin_query = "SELECT email FROM users WHERE is_admin = 1 LIMIT 1";
    $admin_result = $conn->query($admin_query);
    if (!$admin_result || $admin_result->num_rows === 0) {
        throw new Exception('Admin email not found');
    }
    $admin_email = $admin_result->fetch_assoc()['email'];
    
    // Process the order data
    $orderDetails = [
        'user_id' => intval($_SESSION["user_id"]),
        'user_name' => $_POST['user_name'],
        'user_email' => $_POST['user_email'],
        'contact_number' => $_POST['contact_number'],
        'delivery_method' => $_POST['delivery_method'],
        'payment_method' => $_POST['payment_method'],
        'order_notes' => $_POST['order_notes'] ?? '',
        'shipping_fee' => $_POST['delivery_method'] === 'delivery' ? 50 : 0
    ];

    // Process cart items
    $cart_items = json_decode($_POST['cart_items'], true);
    if (!$cart_items || !is_array($cart_items) || empty($cart_items)) {
        error_log("Invalid cart items data received: " . print_r($_POST['cart_items'], true));
        throw new Exception('No cart items provided');
    }

    $orderDetails['cart_items'] = $cart_items;
    $orderDetails['cart_total'] = floatval($_POST['cart_total']);
    $orderDetails['total_amount'] = $orderDetails['cart_total'] + $orderDetails['shipping_fee'];
    
    // Debug log
    error_log("Cart Items Data: " . print_r($orderDetails['cart_items'], true));
    error_log("Total Order Details: " . print_r($orderDetails, true));
    
    // Add delivery/pickup specific details
    if ($orderDetails['delivery_method'] === 'delivery') {
        $orderDetails['delivery_address'] = $_POST['delivery_address'];
        $orderDetails['delivery_date'] = $_POST['delivery_date'];
        $orderDetails['delivery_time'] = $_POST['delivery_time'];
        $order_date = $_POST['delivery_date'];
    } else {
        $orderDetails['pickup_date'] = $_POST['pickup_date'];
        $orderDetails['pickup_time'] = $_POST['pickup_time'];
        $order_date = $_POST['pickup_date'];
    }

    // Check order limits before proceeding
    // First get the default limit
    $default_query = "SELECT default_limit FROM order_limits WHERE id = 1";
    $default_result = $conn->query($default_query);
    $default_limit = $default_result->fetch_assoc()['default_limit'] ?? 10;

    // Check if the date has a specific limit or is not accepting orders
    $limit_query = "SELECT 
        COALESCE(dl.limit_value, ?) as limit_value,
        COUNT(DISTINCT o.order_id) as current_orders,
        CASE 
            WHEN os.status = 'not_accepting' OR dl.limit_value = 0 OR dl.not_accepting_orders = TRUE THEN 'not_accepting'
            ELSE 'accepting'
        END as status
    FROM (SELECT ? as date) d
    LEFT JOIN date_limits dl ON d.date = dl.date
    LEFT JOIN orders o ON (d.date = o.pickup_date OR d.date = o.delivery_date) 
        AND o.status IN ('Pending')
    LEFT JOIN orderdate_status os ON d.date = os.date
    GROUP BY dl.limit_value, dl.not_accepting_orders, os.status";

    $limit_stmt = $conn->prepare($limit_query);
    if (!$limit_stmt) {
        throw new Exception('Failed to prepare limit check statement: ' . $conn->error);
    }
    
    $limit_stmt->bind_param("is", $default_limit, $order_date);
    if (!$limit_stmt->execute()) {
        throw new Exception('Failed to execute limit check: ' . $limit_stmt->error . "\nQuery: " . $limit_query);
    }
    
    $limit_result = $limit_stmt->get_result();
    $limit_data = $limit_result->fetch_assoc();

    if (!$limit_data) {
        // If no result, use default values
        $limit_data = [
            'limit_value' => $default_limit,
            'current_orders' => 0,
            'status' => 'accepting'
        ];
    }

    if ($limit_data['status'] === 'not_accepting') {
        throw new Exception('Sorry, we are not accepting orders for this date.');
    }

    $limit = intval($limit_data['limit_value']);
    $current_orders = intval($limit_data['current_orders']);

    if ($current_orders >= $limit) {
        throw new Exception('Sorry, the selected date has reached its order limit. Please choose another date.');
    }
    
    // First, create or update customer record
    $customer_sql = "INSERT INTO customers (contact, address) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE contact = VALUES(contact), address = VALUES(address)";
    
    $customer_stmt = $conn->prepare($customer_sql);
    if (!$customer_stmt) {
        throw new Exception('Failed to prepare customer statement: ' . $conn->error);
    }

    $customer_contact = $orderDetails['contact_number'];
    $customer_address = $orderDetails['delivery_method'] === 'delivery' ? $orderDetails['delivery_address'] : null;
    
    $customer_stmt->bind_param("ss", $customer_contact, $customer_address);
    
    if (!$customer_stmt->execute()) {
        throw new Exception('Failed to save customer: ' . $customer_stmt->error);
    }
    
    // Get the customer_id (either newly created or existing)
    $customer_id = $customer_stmt->insert_id;
    if (!$customer_id) {
        // If no insert_id (means customer already existed), get the existing customer_id
        $get_customer_sql = "SELECT customer_id FROM customers WHERE contact = ?";
        $get_customer_stmt = $conn->prepare($get_customer_sql);
        $get_customer_stmt->bind_param("s", $customer_contact);
        $get_customer_stmt->execute();
        $get_customer_stmt->bind_result($customer_id);
        $get_customer_stmt->fetch();
        $get_customer_stmt->close();
    }
    
    if (!$customer_id) {
        throw new Exception('Failed to get customer ID');
    }
    
    // Calculate total items
    $total_items = 0;
    foreach ($orderDetails['cart_items'] as $item) {
        $total_items += $item['quantity'];
    }
    
    // Format delivery method to match enum ('Delivery' or 'Pick-up')
    $delivery_method = $orderDetails['delivery_method'] === 'delivery' ? 'Delivery' : 'Pick-up';
    
    // Prepare variables for binding
    $customer_name = $orderDetails['user_name'];
    $payment_method = $orderDetails['payment_method'];
    $total_amount = $orderDetails['total_amount'];
    $delivery_date = $orderDetails['delivery_method'] === 'delivery' ? $orderDetails['delivery_date'] : null;
    $delivery_time = $orderDetails['delivery_method'] === 'delivery' ? $orderDetails['delivery_time'] : null;
    $pickup_date = $orderDetails['delivery_method'] === 'pickup' ? $orderDetails['pickup_date'] : null;
    $pickup_time = $orderDetails['delivery_method'] === 'pickup' ? $orderDetails['pickup_time'] : null;
    $notes = $orderDetails['order_notes'];
    
    // Save order to database with the customer_id
    $order_sql = "INSERT INTO orders (
                    order_date,
                    customer_id,
                    customer_email,
                    customer_name,
                    customer_contact,
                    customer_address,
                    payment_method,
                    total_items,
                    total_amount,
                    delivery_method,
                    delivery_date,
                    delivery_time,
                    pickup_date,
                    pickup_time,
                    notes,
                    status
                ) VALUES (
                    CURRENT_TIMESTAMP,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending'
                )";
                  
    $stmt = $conn->prepare($order_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare order statement: ' . $conn->error);
    }
    
    // Debug log before binding
    error_log("Order Data to be inserted: " . print_r([
        'customer_id' => $customer_id,
        'customer_email' => $orderDetails['user_email'],
        'customer_name' => $customer_name,
        'customer_contact' => $customer_contact,
        'customer_address' => $customer_address,
        'payment_method' => $payment_method,
        'total_items' => $total_items,
        'total_amount' => $total_amount,
        'delivery_method' => $delivery_method,
        'delivery_date' => $delivery_date,
        'delivery_time' => $delivery_time,
        'pickup_date' => $pickup_date,
        'pickup_time' => $pickup_time,
        'notes' => $notes
    ], true));
    
    $stmt->bind_param(
        "issssidsssssss",
        $customer_id,
        $orderDetails['user_email'],
        $customer_name,
        $customer_contact,
        $customer_address,
        $payment_method,
        $total_items,
        $total_amount,
        $delivery_method,
        $delivery_date,
        $delivery_time,
        $pickup_date,
        $pickup_time,
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save order: ' . $stmt->error);
    }
    
    // Get the auto-generated order_id
    $order_id = $stmt->insert_id;
    error_log("Initial order_id from stmt: " . $order_id);
    
    if (!$order_id) {
        // Try getting it from the connection
        $order_id = $conn->insert_id;
        error_log("Fallback order_id from conn: " . $order_id);
        
        if (!$order_id) {
            // Last resort: try to find the order we just inserted
            $last_order_sql = "SELECT order_id FROM orders 
                              WHERE customer_id = ? 
                              AND customer_name = ? 
                              AND total_amount = ? 
                              ORDER BY order_id DESC LIMIT 1";
            $last_order_stmt = $conn->prepare($last_order_sql);
            $last_order_stmt->bind_param("isd", $customer_id, $customer_name, $total_amount);
            $last_order_stmt->execute();
            $last_order_result = $last_order_stmt->get_result();
            if ($last_order_row = $last_order_result->fetch_assoc()) {
                $order_id = $last_order_row['order_id'];
                error_log("Retrieved order_id through query: " . $order_id);
            } else {
                throw new Exception('Failed to get valid order ID after insertion');
            }
        }
    }

    if (!$order_id) {
        throw new Exception('Failed to get valid order ID after all attempts');
    }

    error_log("Final order_id to be used: " . $order_id);

    // Save order items with explicit column list
    foreach ($orderDetails['cart_items'] as $item) {
        // Verify we have required data
        if (empty($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
            error_log("Invalid item data: " . print_r($item, true));
            throw new Exception('Invalid item data provided');
        }

        $item_sql = "INSERT INTO order_items (
                        order_id, 
                        product_name,
                        image_path, 
                        price, 
                        quantity
                    ) VALUES (?, ?, NULL, ?, ?)";

        $item_stmt = $conn->prepare($item_sql);
        if (!$item_stmt) {
            throw new Exception('Failed to prepare item statement: ' . $conn->error);
        }
        
        // Debug log before binding item data
        error_log("Order item to be inserted: " . print_r([
            'order_id' => $order_id,
            'product_name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity']
        ], true));

        $item_stmt->bind_param("isdi", 
            $order_id,
            $item['name'],
            $item['price'],
            $item['quantity']
        );
        
        if (!$item_stmt->execute()) {
            throw new Exception('Failed to save order item: ' . $item_stmt->error);
        }

        error_log("Successfully inserted order item with order_id: " . $order_id);
    }
    
    // Add the generated order_id to orderDetails for email
    $orderDetails['order_id'] = $order_id;
    
    // Send email to admin
    if (!sendOrderEmail($orderDetails, $admin_email)) {
        // Log email failure but don't stop the process
        error_log("Failed to send order email for order " . $orderDetails['order_id']);
    }
    
    // Clear cart items
    $cart_ids = array_column($orderDetails['cart_items'], 'cart_id');
    if (!empty($cart_ids)) {
        $clear_cart_sql = "DELETE FROM cart WHERE user_id = ? AND id IN (" . implode(',', $cart_ids) . ")";
        $clear_cart_stmt = $conn->prepare($clear_cart_sql);
        if ($clear_cart_stmt) {
            $clear_cart_stmt->bind_param("i", $orderDetails['user_id']);
            $clear_cart_stmt->execute();
        }
    }
    
    // Clear any output buffers before sending JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Return success response with the order_id
    echo json_encode([
        'success' => true, 
        'order_id' => $order_id,
        'receipt_url' => '/NeoExclusiveCafe/order_receipt.php?order_id=' . $order_id
    ]);
    
} catch (Exception $e) {
    // Clear any output buffers before sending JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    
    error_log("Order processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 