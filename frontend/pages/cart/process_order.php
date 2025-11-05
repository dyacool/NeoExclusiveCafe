<?php
// Start session with dynamic domain
$session_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => $session_domain
]);
session_start();
require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../backend/pages/admin-includes/mailer.php';
require_once '../../../backend/pages/admin-includes/notifications/notification.php';

// Ensure no output before JSON response
ob_start();

/**
 * Fetch customer information from saved_customer_info table
 * Retrieves email, complete_address, phone, and name for the given user
 * Prioritizes primary records, falls back to most recent
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to fetch info for
 * @return array|null Associative array with customer data or null if not found
 */
function fetchCustomerInfoFromSaved($conn, $user_id) {
    try {
        error_log("=== FETCHING SAVED CUSTOMER INFO ===");
        error_log("User ID: " . $user_id);
        
        $query = "SELECT 
                    sci.email, 
                    sci.complete_address, 
                    sci.phone, 
                    sci.first_name, 
                    sci.last_name,
                    CONCAT(dl.municipality, ', ', dl.city, ' ', dl.postal_code) as delivery_location
                FROM saved_customer_info sci
                LEFT JOIN delivery_locations dl ON sci.delivery_location_id = dl.delivery_id
                WHERE sci.user_id = ? 
                ORDER BY sci.is_primary DESC, sci.updated_at DESC 
                LIMIT 1";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare saved info query: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            error_log("Failed to execute saved info query: " . $stmt->error);
            $stmt->close();
            return null;
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("No saved customer info found for user_id: " . $user_id);
            $stmt->close();
            return null;
        }
        
        $saved_info = $result->fetch_assoc();
        $stmt->close();
        
        error_log("✓ Saved customer info retrieved successfully");
        error_log("Email: " . ($saved_info['email'] ?? 'NULL'));
        error_log("Complete Address: " . ($saved_info['complete_address'] ?? 'NULL'));
        error_log("Phone: " . ($saved_info['phone'] ?? 'NULL'));
        error_log("Name: " . ($saved_info['first_name'] ?? '') . ' ' . ($saved_info['last_name'] ?? ''));
        error_log("=== END FETCHING SAVED CUSTOMER INFO ===");
        
        return $saved_info;
        
    } catch (Exception $e) {
        error_log("Error fetching saved customer info: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return null;
    }
}

// Function to send email
function sendOrderEmail($orderDetails, $adminEmail) {
    try {
        // Normalize delivery method for comparison (handle both 'delivery'/'pickup' and 'Delivery'/'Pick-up')
        $isDelivery = (strtolower($orderDetails['delivery_method']) === 'delivery');
        $isPickup = (strtolower($orderDetails['delivery_method']) === 'pickup' || $orderDetails['delivery_method'] === 'Pick-up');
        
        // Create order notification data structure
        $notificationData = [
            'order_id' => $orderDetails['order_id'],
            'customer_name' => $orderDetails['user_name'],
            'user_email' => $orderDetails['user_email'],
            'customer_contact' => $orderDetails['contact_number'],
            'customer_address' => $isDelivery ? ($orderDetails['delivery_address'] ?? '') : '',
            'delivery_method' => $orderDetails['delivery_method'],
            'pickup_date' => $isPickup ? ($orderDetails['pickup_date'] ?? null) : null,
            'pickup_time' => $isPickup ? ($orderDetails['pickup_time'] ?? null) : null,
            'delivery_date' => $isDelivery ? ($orderDetails['delivery_date'] ?? null) : null,
            'delivery_time' => $isDelivery ? ($orderDetails['delivery_time'] ?? null) : null,
            'payment_method' => $orderDetails['payment_method'],
            'cart_items' => $orderDetails['cart_items'],
            'cart_total' => $orderDetails['cart_total'],
            'shipping_fee' => $orderDetails['shipping_fee'],
            'total_amount' => $orderDetails['total_amount'],
            'order_notes' => $orderDetails['order_notes'] ?? '',
            'discount_amount' => $orderDetails['discount_amount'] ?? 0,
            'applied_coupon' => $orderDetails['applied_coupon'] ?? null
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
    header('Location: ../../login/user/login-signup.php');
    exit();
}
    
    // Get admin email from database
    $admin_query = "SELECT email FROM users WHERE is_admin = 1 LIMIT 1";
    $admin_result = $conn->query($admin_query);
    if (!$admin_result || $admin_result->num_rows === 0) {
        throw new Exception('Admin email not found');
    }
    $admin_email = $admin_result->fetch_assoc()['email'];
    
    // Process the order data - extract POST data first
    $user_email = $_POST['user_email'] ?? '';
    $user_name = $_POST['user_name'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $delivery_method = $_POST['delivery_method'] ?? '';
    $delivery_address = $_POST['delivery_address'] ?? '';
    
    // Fetch saved customer info and merge with POST data
    error_log("=== CUSTOMER DATA MERGING START (PRE-ORDER) ===");
    error_log("POST data - Email: " . ($user_email ?: 'EMPTY') . ", Contact: " . ($contact_number ?: 'EMPTY'));
    error_log("POST data - Name: " . ($user_name ?: 'EMPTY'));
    error_log("POST data - Delivery Address: " . ($delivery_address ?: 'EMPTY'));
    error_log("POST data - Delivery Method: " . $delivery_method);
    
    $saved_info = null;
    if (isset($_SESSION['user_id'])) {
        $saved_info = fetchCustomerInfoFromSaved($conn, intval($_SESSION['user_id']));
    }
    
    // Merge saved info with POST data - saved info takes precedence
    if ($saved_info !== null) {
        error_log("Merging saved customer info with POST data...");
        
        // Email: saved info takes precedence
        if (!empty($saved_info['email'])) {
            $user_email = $saved_info['email'];
            error_log("Using email from saved info: " . $user_email);
        }
        
        // Contact number: saved info takes precedence
        if (!empty($saved_info['phone'])) {
            $contact_number = $saved_info['phone'];
            error_log("Using contact from saved info: " . $contact_number);
        }
        
        // Name: saved info takes precedence
        if (!empty($saved_info['first_name']) && !empty($saved_info['last_name'])) {
            $user_name = $saved_info['first_name'] . ' ' . $saved_info['last_name'];
            error_log("Using name from saved info: " . $user_name);
        }
        
        // Address: For delivery orders, use complete_address from saved info
        if ($delivery_method === 'delivery' && !empty($saved_info['complete_address'])) {
            $delivery_address = $saved_info['complete_address'];
            error_log("Using complete_address from saved info for delivery: " . $delivery_address);
        }
    } else {
        error_log("No saved customer info found, using POST data only");
    }
    
    // Log final merged values
    error_log("=== FINAL MERGED VALUES (PRE-ORDER) ===");
    error_log("Email: " . ($user_email ?: 'EMPTY'));
    error_log("Contact: " . ($contact_number ?: 'EMPTY'));
    error_log("Name: " . ($user_name ?: 'EMPTY'));
    error_log("Delivery Address: " . ($delivery_address ?: 'EMPTY'));
    error_log("Delivery Method: " . $delivery_method);
    error_log("=== CUSTOMER DATA MERGING END (PRE-ORDER) ===");
    
    $orderDetails = [
        'user_id' => intval($_SESSION["user_id"]),
        'user_name' => $user_name,
        'user_email' => $user_email,
        'contact_number' => $contact_number,
        'delivery_method' => $delivery_method,
        'payment_method' => $_POST['payment_method'],
        'order_notes' => $_POST['order_notes'] ?? '',
        'shipping_fee' => $delivery_method === 'delivery' ? 50 : 0
    ];

    // Process cart items
    $cart_items = json_decode($_POST['cart_items'], true);
    if (!$cart_items || !is_array($cart_items) || empty($cart_items)) {
        error_log("Invalid cart items data received: " . print_r($_POST['cart_items'], true));
        throw new Exception('No cart items provided');
    }

    $orderDetails['cart_items'] = $cart_items;
    $orderDetails['cart_total'] = floatval($_POST['cart_total']);
    
    // Process coupon data if provided
    $discount_amount = 0;
    $applied_coupon = null;
    
    if (!empty($_POST['applied_coupon'])) {
        $applied_coupon = json_decode($_POST['applied_coupon'], true);
        $discount_amount = floatval($_POST['discount_amount'] ?? 0);
        
        // Update total amount with discount
        $orderDetails['discount_amount'] = $discount_amount;
        $orderDetails['applied_coupon'] = $applied_coupon;
    }
    
    $orderDetails['total_amount'] = $orderDetails['cart_total'] + $orderDetails['shipping_fee'] - $discount_amount;
    
    // Debug log
    error_log("Cart Items Data: " . print_r($orderDetails['cart_items'], true));
    error_log("Total Order Details: " . print_r($orderDetails, true));
    
    // Add delivery/pickup specific details
    if ($orderDetails['delivery_method'] === 'delivery') {
        $orderDetails['delivery_address'] = $delivery_address; // Use merged address
        $orderDetails['delivery_date'] = $_POST['delivery_date'];
        $orderDetails['delivery_time'] = $_POST['delivery_time'];
        $order_date = $_POST['delivery_date'];
    } else {
        $orderDetails['pickup_date'] = $_POST['pickup_date'];
        $orderDetails['pickup_time'] = $_POST['pickup_time'];
        $order_date = $_POST['pickup_date'];
    }

    // Validate order based on delivery method
    if ($orderDetails['delivery_method'] === 'delivery') {
        // For DELIVERY orders: Check both order_limits and date_limits
        
        // First get the default delivery limit
        $default_query = "SELECT default_limit FROM order_limits WHERE id = 1";
        $default_result = $conn->query($default_query);
        $default_limit = $default_result->fetch_assoc()['default_limit'] ?? 10;

        // Check if the date has a specific limit or is not accepting orders
        // Count only DELIVERY orders for the limit check
        $limit_query = "SELECT 
            COALESCE(dl.limit_value, ?) as limit_value,
            COUNT(DISTINCT o.order_id) as current_orders,
            CASE 
                WHEN os.status = 'not_accepting' OR dl.not_accepting_orders = TRUE THEN 'not_accepting'
                ELSE 'accepting'
            END as status
        FROM (SELECT ? as date) d
        LEFT JOIN date_limits dl ON d.date = dl.date
        LEFT JOIN orders o ON d.date = o.delivery_date
            AND o.delivery_method = 'Delivery'
            AND o.status NOT IN ('Completed', 'Delivered', 'Picked-up', 'Cancelled')
        LEFT JOIN orderdate_status os ON d.date = os.date
        GROUP BY dl.limit_value, dl.not_accepting_orders, os.status";

        $limit_stmt = $conn->prepare($limit_query);
        if (!$limit_stmt) {
            throw new Exception('Failed to prepare limit check statement: ' . $conn->error);
        }
        
        $limit_stmt->bind_param("is", $default_limit, $order_date);
        if (!$limit_stmt->execute()) {
            throw new Exception('Failed to execute limit check: ' . $limit_stmt->error);
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

        // Check if date is blocked
        if ($limit_data['status'] === 'not_accepting') {
            error_log("Delivery order rejected: Date $order_date is not accepting orders");
            throw new Exception('Sorry, we are not accepting orders for this date.');
        }

        // Check if delivery limit is reached
        $limit = intval($limit_data['limit_value']);
        $current_orders = intval($limit_data['current_orders']);

        if ($current_orders >= $limit) {
            error_log("Delivery order rejected: Limit reached for $order_date (Current: $current_orders, Limit: $limit)");
            throw new Exception('Sorry, we have reached the delivery order limit for this date. Please choose another date.');
        }
        
        error_log("Delivery order validation passed for $order_date (Current: $current_orders, Limit: $limit)");
        
    } else {
        // For PICKUP orders: Check only date_limits (ignore order_limits)
        
        $date_check_query = "SELECT 
            CASE 
                WHEN os.status = 'not_accepting' OR dl.not_accepting_orders = TRUE THEN 'not_accepting'
                ELSE 'accepting'
            END as status
        FROM (SELECT ? as date) d
        LEFT JOIN date_limits dl ON d.date = dl.date
        LEFT JOIN orderdate_status os ON d.date = os.date";

        $date_stmt = $conn->prepare($date_check_query);
        if (!$date_stmt) {
            throw new Exception('Failed to prepare date check statement: ' . $conn->error);
        }
        
        $date_stmt->bind_param("s", $order_date);
        if (!$date_stmt->execute()) {
            throw new Exception('Failed to execute date check: ' . $date_stmt->error);
        }
        
        $date_result = $date_stmt->get_result();
        $date_data = $date_result->fetch_assoc();

        // Check if date is blocked
        if ($date_data && $date_data['status'] === 'not_accepting') {
            error_log("Pickup order rejected: Date $order_date is not accepting orders");
            throw new Exception('Sorry, we are not accepting orders for this date.');
        }
        
        error_log("Pickup order validation passed for $order_date (no order limit check)");
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
    // Include coupon information in notes if applied
    $notes = $orderDetails['order_notes'];
    if ($applied_coupon) {
        $coupon_info = "\n\nCoupon Applied: " . $applied_coupon['code'] . " - Discount: ₱" . number_format($discount_amount, 2);
        $notes .= $coupon_info;
    }
    
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

    // Broadcast new order notification to admins via realtime system
    try {
        require_once '../../../backend/api/event-broadcaster.php';
        EventBroadcaster::broadcastNewOrder(
            $order_id,
            $customer_name,
            $delivery_method,
            $total_amount,
            [
                'delivery_date' => $delivery_date,
                'pickup_date' => $pickup_date,
                'delivery_time' => $delivery_time,
                'pickup_time' => $pickup_time
            ]
        );
        error_log("Broadcasted new order notification for order ID: $order_id");
    } catch (Exception $e) {
        error_log("Failed to broadcast new order: " . $e->getMessage());
    }

    // Create admin notification for new order
    try {
        $notificationHandler = new NotificationHandler($conn);
        
        // Get username if available
        $username = null;
        if (isset($orderDetails['user_id']) && $orderDetails['user_id']) {
            $username_sql = "SELECT username FROM users WHERE id = ?";
            $username_stmt = $conn->prepare($username_sql);
            $username_stmt->bind_param("i", $orderDetails['user_id']);
            $username_stmt->execute();
            $username_result = $username_stmt->get_result();
            if ($username_row = $username_result->fetch_assoc()) {
                $username = $username_row['username'];
            }
        }
        
        // Create new order notification
        $notificationHandler->createOrderNotification(
            $order_id,
            'order_new',
            $customer_name,
            $username,
            null,
            $delivery_method,
            $delivery_method === 'Delivery' ? $delivery_date : $pickup_date,
            $delivery_method === 'Delivery' ? $delivery_time : $pickup_time
        );
        
        // Check if order is for tomorrow and create warning notification
        $order_date_check = $delivery_method === 'Delivery' ? $delivery_date : $pickup_date;
        if ($order_date_check) {
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            if ($order_date_check === $tomorrow) {
                $notificationHandler->createOrderNotification(
                    $order_id,
                    'order_warning',
                    $customer_name,
                    $username,
                    null,
                    $delivery_method,
                    $order_date_check,
                    $delivery_method === 'Delivery' ? $delivery_time : $pickup_time
                );
            }
        }
        
        error_log("✓ Admin notifications created for order #$order_id");
    } catch (Exception $notif_error) {
        error_log("Failed to create notification: " . $notif_error->getMessage());
        // Don't stop the order process if notification fails
    }

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
        
        // Update product inventory - subtract ordered quantity
        $product_id = $item['product_id'] ?? null;
        $ordered_quantity = $item['quantity'];
        
        if ($product_id) {
            // First, check current stock
            $stock_check_sql = "SELECT quantity, status_id, name FROM products WHERE id = ?";
            $stock_check_stmt = $conn->prepare($stock_check_sql);
            $stock_check_stmt->bind_param("i", $product_id);
            $stock_check_stmt->execute();
            $stock_result = $stock_check_stmt->get_result();
            
            if ($stock_row = $stock_result->fetch_assoc()) {
                $current_stock = $stock_row['quantity'];
                $current_status_id = $stock_row['status_id'];
                $product_name = $stock_row['name'];
                
                // Check if there's sufficient stock
                if ($current_stock >= $ordered_quantity) {
                    // Update product stock
                    $update_stock_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                    $update_stock_stmt = $conn->prepare($update_stock_sql);
                    $update_stock_stmt->bind_param("ii", $ordered_quantity, $product_id);
                    
                    if ($update_stock_stmt->execute()) {
                        error_log("Successfully updated inventory for product ID $product_id: reduced by $ordered_quantity");
                        
                        // Check if product quantity reached 0 and update status to unavailable
                        $new_stock = $current_stock - $ordered_quantity;
                        if ($new_stock <= 0) {
                            $new_status_id = 0;
                            
                            // Determine the appropriate unavailable status based on current status
                            if ($current_status_id == 1) {
                                // Currently Pick Up - set to Unavailable Pick Up (ID 4)
                                $new_status_id = 4;
                            } else if ($current_status_id == 2) {
                                // Currently Delivery - set to Unavailable Delivery (ID 5)
                                $new_status_id = 5;
                            } else {
                                // For any other status, default to Unavailable Delivery (ID 5)
                                $new_status_id = 5;
                            }
                            
                            $update_status_sql = "UPDATE products SET status_id = ? WHERE id = ?";
                            $update_status_stmt = $conn->prepare($update_status_sql);
                            $update_status_stmt->bind_param("ii", $new_status_id, $product_id);
                            
                            if ($update_status_stmt->execute()) {
                                error_log("Product '$product_name' (ID: $product_id) marked as unavailable due to zero stock");
                            } else {
                                error_log("Failed to update product status for product ID $product_id: " . $update_status_stmt->error);
                            }
                            $update_status_stmt->close();
                        }
                    } else {
                        error_log("Failed to update inventory for product ID $product_id: " . $update_stock_stmt->error);
                    }
                    $update_stock_stmt->close();
                } else {
                    error_log("Insufficient stock for product '$product_name' (ID: $product_id). Available: $current_stock, Requested: $ordered_quantity");
                }
            } else {
                error_log("Product not found for inventory update: product ID $product_id");
            }
            $stock_check_stmt->close();
        } else {
            error_log("No product_id found for inventory update in item: " . print_r($item, true));
        }
    }
    
    // Add the generated order_id to orderDetails for email
    $orderDetails['order_id'] = $order_id;
    
    // Record coupon usage if a coupon was applied
    error_log("=== COUPON RECORDING CHECK ===");
    error_log("applied_coupon exists: " . (isset($applied_coupon) ? 'YES' : 'NO'));
    error_log("applied_coupon data: " . json_encode($applied_coupon));
    
    if ($applied_coupon && isset($applied_coupon['id'])) {
        error_log("=== RECORDING COUPON USAGE ===");
        require_once '../../../backend/pages/user-page-content/database-config.php';
        $user_id = $orderDetails['user_id'];
        $coupon_id = intval($applied_coupon['id']);
        
        error_log("User ID: $user_id, Coupon ID: $coupon_id, Order ID: $order_id");
        
        // Record the usage
        if (recordCouponUsage($conn, $user_id, $coupon_id, $order_id)) {
            error_log("✓ Coupon usage recorded successfully: User $user_id used coupon $coupon_id on order $order_id");
            
            // Update global used_count
            $update_sql = "UPDATE promotions SET used_count = used_count + 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param("i", $coupon_id);
                if ($update_stmt->execute()) {
                    error_log("✓ Global used_count updated for coupon $coupon_id");
                } else {
                    error_log("✗ Failed to update global used_count: " . $update_stmt->error);
                }
                $update_stmt->close();
            } else {
                error_log("✗ Failed to prepare used_count update statement");
            }
        } else {
            error_log("✗ Failed to record coupon usage for order $order_id");
        }
    } else {
        error_log("=== NO COUPON TO RECORD ===");
    }
    
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
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Check if this is an AJAX request or form submission
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
              (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    
    if ($isAjax) {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'order_id' => $order_id,
            'receipt_url' => 'order_receipt.php?order_id=' . $order_id
        ]);
    } else {
        // Redirect to receipt page for form submissions
        header('Location: order_receipt.php?order_id=' . $order_id);
        exit();
    }
    
} catch (Exception $e) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    error_log("Order processing error: " . $e->getMessage());
    
    // Check if this is an AJAX request or form submission
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
              (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    
    if ($isAjax) {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        // Redirect to an error page or back to checkout for form submissions
        header('Location: checkout.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} 