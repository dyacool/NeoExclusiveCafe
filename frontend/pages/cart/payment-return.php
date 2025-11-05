<?php
/**
 * PayMongo Payment Return Handler
 * Handles payment success/failure redirects from PayMongo
 */

// Determine domain based on environment
$domain = (strpos($_SERVER['HTTP_HOST'], 'neocafe.shop') !== false) ? 'neocafe.shop' : 'neocafe.cafe';

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => $domain
]);
session_start();

// Include required files
require_once '../../../backend/pages/admin-includes/database.php';
require_once 'paymongo-config.php';
require_once '../../../backend/pages/admin-includes/mailer.php';

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
        error_log("=== FETCHING SAVED CUSTOMER INFO (PAYMENT-RETURN) ===");
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
        error_log("=== END FETCHING SAVED CUSTOMER INFO (PAYMENT-RETURN) ===");
        
        return $saved_info;
        
    } catch (Exception $e) {
        error_log("Error fetching saved customer info: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return null;
    }
}

// Get parameters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? 'regular';
$source_id = $_GET['source_id'] ?? '';

// Debug logging
error_log("Payment return - Status: $status, Type: $type, Source ID: $source_id");
error_log("GET parameters: " . json_encode($_GET));
error_log("Session pending payment: " . json_encode($_SESSION['pending_payment'] ?? 'NOT SET'));

// Check if user has pending payment
if (!isset($_SESSION['pending_payment'])) {
    error_log("No pending payment found in session");
    header("Location: ../products/product-dashboard.php");
    exit();
}

$pending_payment = $_SESSION['pending_payment'];
$paymongo = new PayMongoAPI();

try {
    if ($status === 'success') {
        // Check if this payment has already been processed to prevent duplicates
        $payment_id = $pending_payment['source_id'] ?? ($pending_payment['payment_intent_id'] ?? null);
        if ($payment_id) {
            $check_sql = "SELECT order_id FROM orders WHERE payment_id = ? LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("s", $payment_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($existing_order = $check_result->fetch_assoc()) {
                    error_log("Payment $payment_id already processed for order " . $existing_order['order_id'] . ". Skipping duplicate.");
                    $check_stmt->close();
                    
                    // Clear pending payment and redirect to success
                    unset($_SESSION['pending_payment']);
                    header("Location: payment-success.php?type=$type&order_id=" . $existing_order['order_id']);
                    exit();
                }
                $check_stmt->close();
            }
        }
        
        // Treat as successful payment and persist the order + inventory updates
        error_log("Payment succeeded. Creating order and updating inventory.");

        $order_id_created = null;
        try {
            global $conn;

            // Normalize order data
            $order_data = $pending_payment['order_data'] ?? [];
            if (is_string($order_data)) {
                $decoded = json_decode($order_data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $order_data = $decoded;
                }
            }

            // cart_items may be JSON string
            $cart_items = $order_data['cart_items'] ?? [];
            if (is_string($cart_items)) {
                $decodedItems = json_decode($cart_items, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cart_items = $decodedItems;
                }
            }

            if (empty($cart_items) || !is_array($cart_items)) {
                throw new Exception('No cart items provided for order creation');
            }

            // Derive fields
            $total_items = 0;
            foreach ($cart_items as $ci) { $total_items += intval($ci['quantity'] ?? 0); }

            $total_amount = floatval($pending_payment['amount'] ?? ($order_data['cart_total'] ?? 0));
            
            // Try to get customer info from primary saved info first
            $customer_name = '';
            $customer_email = null;
            $customer_contact = null;
            $customer_address = null;
            
            // Check if user has primary saved customer info
            if (isset($_SESSION['user_id'])) {
                $user_id = intval($_SESSION['user_id']);
                
                // First, check if user has any saved info but no primary set
                $check_primary_sql = "SELECT COUNT(*) as total, SUM(is_primary) as primary_count 
                                      FROM saved_customer_info 
                                      WHERE user_id = ?";
                $check_primary_stmt = $conn->prepare($check_primary_sql);
                if ($check_primary_stmt) {
                    $check_primary_stmt->bind_param("i", $user_id);
                    $check_primary_stmt->execute();
                    $check_result = $check_primary_stmt->get_result();
                    $check_row = $check_result->fetch_assoc();
                    $total_entries = intval($check_row['total']);
                    $primary_count = intval($check_row['primary_count']);
                    $check_primary_stmt->close();
                    
                    // If user has saved info but no primary, set the first one as primary
                    if ($total_entries > 0 && $primary_count === 0) {
                        error_log("User $user_id has $total_entries saved entries but no primary. Setting first entry as primary.");
                        $set_first_primary_sql = "UPDATE saved_customer_info 
                                                  SET is_primary = 1 
                                                  WHERE user_id = ? 
                                                  ORDER BY created_at ASC 
                                                  LIMIT 1";
                        $set_first_primary_stmt = $conn->prepare($set_first_primary_sql);
                        if ($set_first_primary_stmt) {
                            $set_first_primary_stmt->bind_param("i", $user_id);
                            $set_first_primary_stmt->execute();
                            error_log("✓ Automatically set first entry as primary for user $user_id");
                            $set_first_primary_stmt->close();
                        }
                    }
                }
                
                // Now get the primary saved customer info
                $primary_info_sql = "SELECT first_name, last_name, email, phone, complete_address 
                                     FROM saved_customer_info 
                                     WHERE user_id = ? AND is_primary = 1 
                                     LIMIT 1";
                $primary_stmt = $conn->prepare($primary_info_sql);
                if ($primary_stmt) {
                    $primary_stmt->bind_param("i", $user_id);
                    $primary_stmt->execute();
                    $primary_result = $primary_stmt->get_result();
                    if ($primary_row = $primary_result->fetch_assoc()) {
                        $customer_name = trim($primary_row['first_name'] . ' ' . $primary_row['last_name']);
                        $customer_email = $primary_row['email'];
                        $customer_contact = $primary_row['phone'];
                        $customer_address = $primary_row['complete_address'];
                        error_log("Using primary saved customer info for user $user_id: $customer_name");
                    }
                    $primary_stmt->close();
                }
            }
            
            // Fallback to order_data if no primary info found
            if (empty($customer_name)) {
                $customer_name = trim(($order_data['user_name'] ?? ($order_data['customer_name'] ?? '')));
            }
            if (empty($customer_email)) {
                $customer_email = $order_data['user_email'] ?? ($order_data['customer_email'] ?? null);
            }
            if (empty($customer_contact)) {
                $customer_contact = $order_data['phone'] ?? $order_data['contact_number'] ?? null;
            }
            if (empty($customer_address)) {
                $customer_address = $order_data['delivery_address'] ?? ($order_data['address'] ?? null);
            }
            $payment_method = $pending_payment['payment_method'] ?? ($order_data['payment_method'] ?? '');
            $delivery_method_raw = $order_data['delivery_method'] ?? 'pickup';
            $delivery_method = $delivery_method_raw === 'delivery' ? 'Delivery' : 'Pick-up';
            $delivery_date = $delivery_method_raw === 'delivery' ? ($order_data['delivery_date'] ?? null) : null;
            $delivery_time = $delivery_method_raw === 'delivery' ? ($order_data['delivery_time'] ?? null) : null;
            $pickup_date = $delivery_method_raw === 'pickup' ? ($order_data['pickup_date'] ?? null) : null;
            $pickup_time = $delivery_method_raw === 'pickup' ? ($order_data['pickup_time'] ?? null) : null;
            $notes = $order_data['order_notes'] ?? ($order_data['notes'] ?? '');
            $customer_id = null; // optional
            $payment_id = $pending_payment['source_id'] ?? ($pending_payment['payment_intent_id'] ?? null);

            // Ensure orders.order_id is AUTO_INCREMENT primary key (fallback safety)
            try {
                $aiCheck = $conn->query("SHOW COLUMNS FROM orders WHERE Field = 'order_id' AND Extra LIKE '%auto_increment%'");
                if ($aiCheck && $aiCheck->num_rows === 0) {
                    // Ensure PRIMARY KEY exists
                    $pkCheck = $conn->query("SHOW INDEX FROM orders WHERE Key_name = 'PRIMARY'");
                    if ($pkCheck && $pkCheck->num_rows === 0) {
                        $conn->query("ALTER TABLE orders ADD PRIMARY KEY (order_id)");
                    }
                    // Set AUTO_INCREMENT on order_id
                    $conn->query("ALTER TABLE orders MODIFY order_id int(11) NOT NULL AUTO_INCREMENT");
                    error_log("payment-return: orders.order_id set to AUTO_INCREMENT");
                }
            } catch (Exception $e) {
                error_log("payment-return: Failed to enforce AUTO_INCREMENT on orders.order_id: " . $e->getMessage());
            }

            // Insert into orders
            $sql = "INSERT INTO orders (
                        order_date,
                        customer_name,
                        customer_contact,
                        customer_email,
                        customer_address,
                        payment_method,
                        total_items,
                        total_amount,
                        status,
                        delivery_method,
                        delivery_date,
                        pickup_date,
                        delivery_time,
                        notes,
                        pickup_time,
                        customer_id,
                        payment_id,
                        payment_status,
                        amount_paid,
                        paid_at
                    ) VALUES (
                        CURRENT_TIMESTAMP,
                        ?,?,?,?,?,?,?,'Confirmed',?,?,?,?,?,?,?,?, 'paid', ?, NOW()
                    )";

            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new Exception('Prepare order failed: ' . $conn->error); }

            $stmt->bind_param(
                "sssssidssssssisd",
                $customer_name,
                $customer_contact,
                $customer_email,
                $customer_address,
                $payment_method,
                $total_items,
                $total_amount,
                $delivery_method,
                $delivery_date,
                $pickup_date,
                $delivery_time,
                $notes,
                $pickup_time,
                $customer_id,
                $payment_id,
                $total_amount
            );

            if (!$stmt->execute()) { throw new Exception('Execute order failed: ' . $stmt->error); }
            $order_id_created = $stmt->insert_id;
            $stmt->close();

            if (!$order_id_created) {
                // Fallback: try connection insert_id
                $order_id_created = $conn->insert_id;
            }
            if (!$order_id_created) {
                // Fallback: query the most recent matching order for this customer and amount
                $fallback_sql = "SELECT order_id FROM orders 
                                 WHERE customer_email <=> ?
                                   AND customer_name <=> ?
                                   AND total_amount = ?
                                 ORDER BY order_id DESC LIMIT 1";
                $fb = $conn->prepare($fallback_sql);
                if ($fb) {
                    $fb->bind_param("ssd", $customer_email, $customer_name, $total_amount);
                    $fb->execute();
                    $res = $fb->get_result();
                    if ($r = $res->fetch_assoc()) {
                        $order_id_created = intval($r['order_id']);
                    }
                    $fb->close();
                }
            }
            if (!$order_id_created) { throw new Exception('Failed to get new order_id'); }
            
            // Broadcast new order notification to admins
            require_once '../../../backend/api/event-broadcaster.php';
            EventBroadcaster::broadcastNewOrder(
                $order_id_created,
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
            error_log("Broadcasted new order notification for order ID: $order_id_created");

            // Insert order_items and update inventory
            $item_sql = "INSERT INTO order_items (order_id, product_name, image_path, price, quantity) VALUES (?,?,?,?,?)";
            $item_stmt = $conn->prepare($item_sql);
            if (!$item_stmt) { throw new Exception('Prepare order_items failed: ' . $conn->error); }

            foreach ($cart_items as $it) {
                $pname = $it['name'] ?? 'Unknown Item';
                $price = floatval($it['price'] ?? 0);
                $qty = intval($it['quantity'] ?? 0);
                $image_path = null;
                $item_stmt->bind_param("issdi", $order_id_created, $pname, $image_path, $price, $qty);
                if (!$item_stmt->execute()) { throw new Exception('Insert order_item failed: ' . $item_stmt->error); }

                // Inventory update - check if same-day order or pre-order
                if (!empty($it['product_id'])) {
                    $pid = intval($it['product_id']);
                    $is_sameday_order = ($type === 'availtoday');
                    
                    error_log("Updating inventory for product ID: $pid, quantity: $qty, Order type: $type, Is same-day: " . ($is_sameday_order ? 'YES' : 'NO'));
                    
                    if ($is_sameday_order) {
                        // SAME-DAY ORDER: Update quantity_per_day_sdo table
                        $today_date = date('Y-m-d');
                        
                        // Get product name for logging
                        $name_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
                        $name_stmt->bind_param("i", $pid);
                        $name_stmt->execute();
                        $name_res = $name_stmt->get_result();
                        $product_name = 'Unknown';
                        if ($name_row = $name_res->fetch_assoc()) {
                            $product_name = $name_row['name'];
                        }
                        $name_stmt->close();
                        
                        // Check same-day stock
                        $sdo_stock_stmt = $conn->prepare("SELECT quantity FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?");
                        $sdo_stock_stmt->bind_param("is", $pid, $today_date);
                        $sdo_stock_stmt->execute();
                        $sdo_stock_res = $sdo_stock_stmt->get_result();
                        
                        if ($sdo_row = $sdo_stock_res->fetch_assoc()) {
                            $current_sdo_stock = intval($sdo_row['quantity']);
                            error_log("Product '$product_name' same-day stock for $today_date: $current_sdo_stock");
                            
                            if ($current_sdo_stock >= $qty) {
                                $upd_sdo = $conn->prepare("UPDATE quantity_per_day_sdo SET quantity = quantity - ? WHERE product_id = ? AND date = ?");
                                $upd_sdo->bind_param("iis", $qty, $pid, $today_date);
                                $upd_sdo->execute();
                                $affected = $upd_sdo->affected_rows;
                                $upd_sdo->close();
                                
                                $new_sdo_stock = $current_sdo_stock - $qty;
                                error_log("SUCCESS: Product '$product_name' same-day stock updated for $today_date: $current_sdo_stock -> $new_sdo_stock (Affected rows: $affected)");
                                
                                // If quantity reached 0, remove the date from the appropriate dates table
                                if ($new_sdo_stock <= 0) {
                                    // Get product status to determine which table to use
                                    $status_check = $conn->prepare("SELECT status_id FROM products WHERE id = ?");
                                    $status_check->bind_param("i", $pid);
                                    $status_check->execute();
                                    $status_result = $status_check->get_result();
                                    $status_row = $status_result->fetch_assoc();
                                    $status_id = $status_row['status_id'];
                                    $status_check->close();
                                    
                                    // Determine which table to delete from
                                    $dates_table = ($status_id == 4) ? 'todays_products_dates' : 'regular_products_today_dates';
                                    
                                    // Remove the date since quantity is 0
                                    $remove_date = $conn->prepare("DELETE FROM $dates_table WHERE product_id = ? AND available_date = ?");
                                    $remove_date->bind_param("is", $pid, $today_date);
                                    $remove_date->execute();
                                    $removed_rows = $remove_date->affected_rows;
                                    $remove_date->close();
                                    
                                    error_log("Product '$product_name' quantity reached 0 for $today_date. Removed date from $dates_table (Rows affected: $removed_rows)");
                                }
                            } else {
                                error_log("WARNING: Insufficient same-day stock for product '$product_name' on $today_date. Requested: $qty, Available: $current_sdo_stock");
                            }
                        } else {
                            error_log("WARNING: No same-day stock entry found for product ID $pid on date $today_date");
                        }
                        $sdo_stock_stmt->close();
                        
                    } else {
                        // PRE-ORDER: Update products.quantity table
                        $stock_stmt = $conn->prepare("SELECT quantity, status_id, name FROM products WHERE id = ?");
                        $stock_stmt->bind_param("i", $pid);
                        $stock_stmt->execute();
                        $stock_res = $stock_stmt->get_result();
                        if ($row = $stock_res->fetch_assoc()) {
                            $current_stock = intval($row['quantity']);
                            $product_name = $row['name'];
                            error_log("Product '$product_name' current stock: $current_stock");
                            
                            if ($current_stock >= $qty) {
                                $upd = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                                $upd->bind_param("ii", $qty, $pid);
                                $upd->execute();
                                $upd->close();
                                
                                $new_stock = $current_stock - $qty;
                                error_log("Product '$product_name' stock updated: $current_stock -> $new_stock");

                                // If stock hits 0, mark unavailable
                                if ($new_stock <= 0) {
                                    $new_status_id = ($row['status_id'] == 1) ? 4 : 5;
                                    $updS = $conn->prepare("UPDATE products SET status_id = ? WHERE id = ?");
                                    $updS->bind_param("ii", $new_status_id, $pid);
                                    $updS->execute();
                                    $updS->close();
                                    error_log("Product '$product_name' marked as unavailable (status_id: $new_status_id)");
                                }
                            } else {
                                error_log("WARNING: Insufficient stock for product '$product_name'. Requested: $qty, Available: $current_stock");
                            }
                        } else {
                            error_log("WARNING: Product ID $pid not found in database");
                        }
                        $stock_stmt->close();
                    }
                } else {
                    error_log("WARNING: Cart item missing product_id: " . json_encode($it));
                }
            }
            $item_stmt->close();

            // Clear cart entries by selected_cart_ids if provided
            if (!empty($order_data['selected_cart_ids'])) {
                $ids_csv = $order_data['selected_cart_ids'];
                if (is_array($ids_csv)) { $ids = $ids_csv; } else { $ids = array_filter(array_map('intval', explode(',', $ids_csv))); }
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $types = str_repeat('i', count($ids));
                    
                    // Determine which cart table to delete from based on order type
                    $cart_table = ($type === 'availtoday') ? 'availtoday_cart' : 'cart';
                    $del_sql = "DELETE FROM $cart_table WHERE id IN ($placeholders)";
                    $del = $conn->prepare($del_sql);
                    $del->bind_param($types, ...$ids);
                    $del->execute();
                    $affected_rows = $del->affected_rows;
                    $del->close();
                    
                    error_log("Cleared " . $affected_rows . " items from $cart_table for order type: $type");
                }
            }

        } catch (Exception $ex) {
            error_log('Order creation on payment return failed: ' . $ex->getMessage());
            throw $ex;
        }

        // Record coupon usage if a coupon was applied
        error_log("=== PAYMONGO COUPON RECORDING CHECK ===");
        $applied_coupon = $order_data['applied_coupon'] ?? $_SESSION['applied_coupon'] ?? null;
        error_log("applied_coupon exists: " . (isset($applied_coupon) ? 'YES' : 'NO'));
        error_log("applied_coupon data: " . json_encode($applied_coupon));
        
        if ($applied_coupon && isset($applied_coupon['id']) && intval($applied_coupon['id']) > 0) {
            error_log("=== RECORDING COUPON USAGE (PAYMONGO) ===");
            require_once '../../../backend/pages/user-page-content/database-config.php';
            $user_id = $_SESSION['user_id'] ?? null;
            $coupon_id = intval($applied_coupon['id']);
            
            if ($user_id) {
                error_log("User ID: $user_id, Coupon ID: $coupon_id, Order ID: $order_id_created");
                
                // Record the usage
                if (recordCouponUsage($conn, $user_id, $coupon_id, $order_id_created)) {
                    error_log("✓ Coupon usage recorded successfully: User $user_id used coupon $coupon_id on order $order_id_created");
                    
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
                    error_log("✗ Failed to record coupon usage for order $order_id_created");
                }
            } else {
                error_log("✗ No user_id in session, cannot record coupon usage");
            }
        } else {
            error_log("=== NO COUPON TO RECORD (PAYMONGO) ===");
        }

        // Auto-save customer information if user doesn't have any saved info
        try {
            autoSaveCustomerInfo($customer_name, $customer_email, $customer_contact, $customer_address, $order_data);
        } catch (Exception $e) {
            error_log("Auto-save customer info failed: " . $e->getMessage());
        }

        // Send email but don't block on failure
        try {
            sendOrderConfirmationEmail($order_id_created, is_array($pending_payment['order_data']) ? $pending_payment['order_data'] : $order_data, $type);
        } catch (Exception $e) {
            error_log("Order confirmation email failed: " . $e->getMessage());
        }
        
        // Create admin notification for new order
        try {
            require_once '../../backend/pages/admin-includes/notifications/notification.php';
            $notificationHandler = new NotificationHandler($conn);
            
            // Get username if user is logged in
            $username = null;
            if (isset($_SESSION['username'])) {
                $username = $_SESSION['username'];
            }
            
            // Create notification for new order
            $notificationHandler->createOrderNotification(
                $order_id_created,
                'order_new',
                $customer_name,
                $username,
                null,
                $delivery_method,
                $delivery_date,
                $delivery_time
            );
            
            // Check if order is for tomorrow and create warning notification
            $order_date = $delivery_method === 'Delivery' ? $delivery_date : $pickup_date;
            if ($order_date && $order_date === date('Y-m-d', strtotime('+1 day'))) {
                $notificationHandler->createOrderNotification(
                    $order_id_created,
                    'order_warning',
                    $customer_name,
                    $username,
                    null,
                    $delivery_method,
                    $delivery_date,
                    $delivery_time
                );
            }
            
        } catch (Exception $e) {
            error_log("Failed to create order notification: " . $e->getMessage());
        }
        
        // Auto-update order status for same-day orders
        try {
            // Check if this is a same-day order (order_date = pickup_date or delivery_date)
            $check_sameday_sql = "SELECT order_id, delivery_method, 
                                  DATE(order_date) as order_date_only,
                                  pickup_date, delivery_date, status
                                  FROM orders WHERE order_id = ?";
            $check_stmt = $conn->prepare($check_sameday_sql);
            $check_stmt->bind_param("i", $order_id_created);
            $check_stmt->execute();
            $order_result = $check_stmt->get_result();
            
            if ($order_row = $order_result->fetch_assoc()) {
                $is_same_day = false;
                $new_status = null;
                
                // Check if pickup order placed today for today
                if ($order_row['delivery_method'] === 'Pick-up' && 
                    $order_row['order_date_only'] === $order_row['pickup_date']) {
                    $is_same_day = true;
                    $new_status = 'Ready for Pick-up';
                }
                // Check if delivery order placed today for today
                else if ($order_row['delivery_method'] === 'Delivery' && 
                         $order_row['order_date_only'] === $order_row['delivery_date']) {
                    $is_same_day = true;
                    $new_status = 'Ready for Delivery';
                }
                
                // Update status if it's a same-day order and currently Confirmed
                if ($is_same_day && $new_status && $order_row['status'] === 'Confirmed') {
                    $update_status_sql = "UPDATE orders SET status = ? WHERE order_id = ?";
                    $update_stmt = $conn->prepare($update_status_sql);
                    $update_stmt->bind_param("si", $new_status, $order_id_created);
                    
                    if ($update_stmt->execute()) {
                        error_log("[AUTO-STATUS] Same-day order #$order_id_created automatically updated to '$new_status'");
                    }
                    $update_stmt->close();
                }
            }
            $check_stmt->close();
        } catch (Exception $e) {
            error_log("[AUTO-STATUS] Error updating order status: " . $e->getMessage());
        }

        // Success session payload
                $_SESSION['payment_success'] = [
            'order_id' => $order_id_created,
                    'amount' => $pending_payment['amount'],
                    'payment_method' => $pending_payment['payment_method'],
                    'order_type' => $type
                ];
                
        // Clear pending
                unset($_SESSION['pending_payment']);
                
                // Redirect to success page
                header("Location: payment-success.php?type=$type");
                exit();
        
    } else {
        // Payment failed or cancelled
        throw new Exception('Payment was cancelled or failed');
    }
    
} catch (Exception $e) {
    error_log("Payment return error: " . $e->getMessage());
    
    // Store error for display
    $_SESSION['payment_error'] = $e->getMessage();
    
    // Update order status to failed if exists
    if (isset($pending_payment['order_id'])) {
        updateOrderStatus($pending_payment['order_id'], 'failed', 'failed');
    }
    
    // Clear pending payment
    unset($_SESSION['pending_payment']);
    
    // Redirect to failure page
    header("Location: payment-failed.php?type=$type");
    exit();
}

/**
 * Finalize order after successful payment
 */
function finalizeOrder($order_id, $payment_result) {
    global $conn;
    
    try {
        // Extract payment information
        $payment_id = $payment_result['data']['id'];
        $amount_paid = formatAmountFromPayMongo($payment_result['data']['attributes']['amount']);
        
                 // Update order status
         $update_sql = "UPDATE orders SET 
                        status = 'Confirmed',
                        payment_status = 'paid',
                        payment_id = ?,
                        amount_paid = ?,
                        paid_at = NOW()
                        WHERE order_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sdi", $payment_id, $amount_paid, $order_id);
        
        if ($update_stmt->execute()) {
            error_log("Order $order_id finalized successfully");
            return $order_id;
        } else {
            throw new Exception("Failed to update order status");
        }
        
    } catch (Exception $e) {
        error_log("Error finalizing order: " . $e->getMessage());
        return false;
    }
}

/**
 * Update order status
 */
function updateOrderStatus($order_id, $order_status, $payment_status) {
    // Mock function for presentation - log the order update instead of database
    error_log("Order Status Update - Order ID: $order_id, Status: $order_status, Payment: $payment_status");
    
    // In a real implementation, this would update the database
    // For presentation purposes, we'll just log the action
    return true;
}

/**
 * Clear available today cart
 */
function clearAvailTodayCart($order_id) {
    // Mock function for presentation - log the cart clearing instead of database
    error_log("Clearing availtoday cart for order ID: $order_id");
    
    // In a real implementation, this would clear the cart from database
    // For presentation purposes, we'll just log the action
    return true;
}

/**
 * Clear regular cart
 */
function clearRegularCart($order_id) {
    // Mock function for presentation - log the cart clearing instead of database
    error_log("Clearing regular cart for order ID: $order_id");
    
    // In a real implementation, this would clear the cart from database
    // For presentation purposes, we'll just log the action
    return true;
}

/**
 * Send order confirmation email to customer and admin
 */
function sendOrderConfirmationEmail($order_id, $order_data, $order_type) {
    global $conn;
    
    try {
        // Fetch saved customer info if user is logged in
        error_log("=== CUSTOMER DATA MERGING START (PAYMENT-RETURN) ===");
        $saved_info = null;
        if (isset($_SESSION['user_id'])) {
            $saved_info = fetchCustomerInfoFromSaved($conn, intval($_SESSION['user_id']));
        }
        
        // Normalize email/name and fields for reliability
        $normalized_email = $order_data['customer_email'] ?? ($order_data['email'] ?? ($order_data['user_email'] ?? ''));
        $normalized_name = $order_data['customer_name'] ?? ($order_data['user_name'] ?? trim(($order_data['first_name'] ?? '') . ' ' . ($order_data['last_name'] ?? '')));
        $normalized_contact = $order_data['phone'] ?? ($order_data['contact_number'] ?? '');
        $normalized_address = $order_data['customer_address'] ?? ($order_data['delivery_address'] ?? ($order_data['address'] ?? ''));
        $normalized_delivery_method = $order_data['delivery_method'] ?? ($order_data['shipping_method'] ?? 'pickup');
        $normalized_delivery_method = strtolower($normalized_delivery_method) === 'delivery' ? 'Delivery' : 'Pick-up';
        $normalized_items = $order_data['cart_items'] ?? [];
        if (is_string($normalized_items)) {
            $decoded = json_decode($normalized_items, true);
            if (json_last_error() === JSON_ERROR_NONE) { $normalized_items = $decoded; }
        }
        
        error_log("POST/Order data - Email: " . ($normalized_email ?: 'EMPTY') . ", Contact: " . ($normalized_contact ?: 'EMPTY'));
        error_log("POST/Order data - Name: " . ($normalized_name ?: 'EMPTY') . ", Address: " . ($normalized_address ?: 'EMPTY'));
        
        // Merge saved info with order data - saved info takes precedence
        if ($saved_info !== null) {
            error_log("Merging saved customer info with order data...");
            
            // Email: saved info takes precedence
            if (!empty($saved_info['email'])) {
                $normalized_email = $saved_info['email'];
                error_log("Using email from saved info: " . $normalized_email);
            }
            
            // Contact: saved info takes precedence
            if (!empty($saved_info['phone'])) {
                $normalized_contact = $saved_info['phone'];
                error_log("Using contact from saved info: " . $normalized_contact);
            }
            
            // Name: saved info takes precedence
            if (!empty($saved_info['first_name']) && !empty($saved_info['last_name'])) {
                $normalized_name = $saved_info['first_name'] . ' ' . $saved_info['last_name'];
                error_log("Using name from saved info: " . $normalized_name);
            }
            
            // Address: For delivery orders, use complete_address from saved info
            if ($normalized_delivery_method === 'Delivery' && !empty($saved_info['complete_address'])) {
                $normalized_address = $saved_info['complete_address'];
                error_log("Using complete_address from saved info for delivery: " . $normalized_address);
            }
        } else {
            error_log("No saved customer info found, using order data only");
        }
        
        // Log final merged values
        error_log("=== FINAL MERGED VALUES (PAYMENT-RETURN) ===");
        error_log("Email: " . ($normalized_email ?: 'EMPTY'));
        error_log("Contact: " . ($normalized_contact ?: 'EMPTY'));
        error_log("Name: " . ($normalized_name ?: 'EMPTY'));
        error_log("Address: " . ($normalized_address ?: 'EMPTY'));
        error_log("Delivery Method: " . $normalized_delivery_method);
        error_log("=== CUSTOMER DATA MERGING END (PAYMENT-RETURN) ===");

        $orderDetails = [
            'order_id' => $order_id,
            'customer_name' => $normalized_name,
            'user_email' => $normalized_email,
            'customer_contact' => $normalized_contact,
            'customer_address' => $normalized_address,
            'payment_method' => $order_data['payment_method'] ?? '',
            'total_amount' => $order_data['cart_total'] ?? ($order_data['total_amount'] ?? '0'),
            'delivery_method' => $normalized_delivery_method,
            'order_date' => date('Y-m-d H:i:s'),
            'pickup_date' => $order_data['pickup_date'] ?? date('Y-m-d', strtotime('+1 day')),
            'delivery_date' => $order_data['delivery_date'] ?? date('Y-m-d', strtotime('+1 day')),
            'pickup_time' => $order_data['pickup_time'] ?? '09:00:00',
            'delivery_time' => $order_data['delivery_time'] ?? '09:00:00',
            'order_notes' => $order_data['special_instructions'] ?? ($order_data['order_notes'] ?? ''),
            'order_type' => $order_type,
            'cart_items' => $normalized_items,
            'cart_total' => $order_data['cart_total'] ?? '0',
            'shipping_fee' => $order_data['shipping_fee'] ?? 0
        ];
        
        error_log("Order details prepared for email: " . json_encode($orderDetails));
        
        // Customer email sending disabled: only admin receives order notifications
        // (per requirement: orders are sent to admin, not to the user)
        
        // Send notification to admin
        $adminEmailSent = sendOrderNotificationEmail($orderDetails);
        error_log("Admin email sent: " . ($adminEmailSent ? 'Success' : 'Failed'));
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error in sendOrderConfirmationEmail: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Send order confirmation email to customer
 */
function sendCustomerOrderConfirmation($orderDetails) {
    try {
        $customerEmail = $orderDetails['customer_email'];
        $customerName = $orderDetails['customer_name'];
        $orderId = $orderDetails['order_id'];
        
        $subject = "Order Confirmation #$orderId - Neo Exclusive Cafe";
        
        // Create customer email body
        $emailBody = createCustomerEmailBody($orderDetails);
        
        // Send email
        return sendEmail($customerEmail, $subject, $emailBody, true);
        
    } catch (Exception $e) {
        error_log("Error sending customer confirmation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Create customer email body
 */
function createCustomerEmailBody($orderDetails) {
    $orderId = $orderDetails['order_id'];
    $customerName = $orderDetails['customer_name'];
    $totalAmount = $orderDetails['total_amount'];
    $paymentMethod = ucfirst($orderDetails['payment_method']);
    $deliveryMethod = $orderDetails['delivery_method'];
    $orderDate = date('F j, Y g:i A', strtotime($orderDetails['order_date']));
    
    // Format cart items
    $itemsList = '';
    if (!empty($orderDetails['cart_items'])) {
        foreach ($orderDetails['cart_items'] as $item) {
            $itemName = $item['name'] ?? 'Unknown Item';
            $itemPrice = $item['price'] ?? '0.00';
            $itemQty = $item['quantity'] ?? 1;
            $itemTotal = number_format($itemPrice * $itemQty, 2);
            
            $itemsList .= "<tr>
                <td style='padding: 8px; border-bottom: 1px solid #eee;'>$itemName</td>
                <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: center;'>$itemQty</td>
                <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right;'>₱$itemTotal</td>
            </tr>";
        }
    }
    
    $emailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Order Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #8B4513; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background-color: #8B4513; color: white; padding: 10px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #eee; }
            .total { font-weight: bold; font-size: 18px; color: #8B4513; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Neo Exclusive Cafe</h1>
                <h2>Order Confirmation</h2>
            </div>
            
            <div class='content'>
                <p>Dear $customerName,</p>
                
                <p>Thank you for your order! We're excited to prepare your delicious items.</p>
                
                <div class='order-details'>
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #$orderId</p>
                    <p><strong>Order Date:</strong> $orderDate</p>
                    <p><strong>Payment Method:</strong> $paymentMethod</p>
                    <p><strong>Delivery Method:</strong> $deliveryMethod</p>
                </div>
                
                <div class='order-details'>
                    <h3>Items Ordered</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style='text-align: center;'>Quantity</th>
                                <th style='text-align: right;'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            $itemsList
                            <tr>
                                <td colspan='2' class='total'>TOTAL AMOUNT:</td>
                                <td class='total' style='text-align: right;'>₱$totalAmount</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class='order-details'>
                    <h3>What's Next?</h3>
                    <p>• We'll start preparing your order right away</p>
                    <p>• You'll receive updates via email</p>
                    <p>• For pickup orders: Please bring a valid ID</p>
                    <p>• For delivery orders: Please be available at your provided address</p>
                </div>
                
                <p>If you have any questions about your order, please don't hesitate to contact us.</p>
                
                <p>Thank you for choosing Neo Exclusive Cafe!</p>
            </div>
            
            <div class='footer'>
                <p>Neo Exclusive Cafe<br>
                Email: support@neocafe.cafe<br>
                This is an automated email, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $emailBody;
}

/**
 * Auto-save customer information if user doesn't have any saved info
 * This creates a primary customer info entry based on the order data
 */
function autoSaveCustomerInfo($customer_name, $customer_email, $customer_contact, $customer_address, $order_data) {
    global $conn;
    
    // Only proceed if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log("Auto-save customer info: No user_id in session");
        return false;
    }
    
    $user_id = intval($_SESSION['user_id']);
    
    try {
        // Check if user already has saved customer info
        $check_sql = "SELECT COUNT(*) as count FROM saved_customer_info WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            error_log("Auto-save customer info: Failed to prepare check statement");
            return false;
        }
        
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $existing_count = intval($check_row['count']);
        $check_stmt->close();
        
        // If user already has saved info, don't auto-save
        if ($existing_count > 0) {
            error_log("Auto-save customer info: User $user_id already has $existing_count saved entries, skipping auto-save");
            return false;
        }
        
        error_log("=== AUTO-SAVING CUSTOMER INFO ===");
        error_log("User ID: $user_id has no saved customer info, creating first entry");
        
        // Parse customer name into first and last name
        $name_parts = explode(' ', trim($customer_name), 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        
        // Validate required fields
        if (empty($first_name) || empty($customer_email) || empty($customer_contact)) {
            error_log("Auto-save customer info: Missing required fields (name: $first_name, email: $customer_email, phone: $customer_contact)");
            return false;
        }
        
        // Try to extract delivery location from order_data
        $delivery_location_id = null;
        
        // Check if delivery_location or delivery_location_id exists in order_data
        if (isset($order_data['delivery_location_id']) && intval($order_data['delivery_location_id']) > 0) {
            $delivery_location_id = intval($order_data['delivery_location_id']);
        } elseif (isset($order_data['delivery_location'])) {
            // Try to find delivery location by name
            $location_name = $order_data['delivery_location'];
            $location_sql = "SELECT delivery_id FROM delivery_locations WHERE CONCAT(municipality, ', ', city) LIKE ? LIMIT 1";
            $location_stmt = $conn->prepare($location_sql);
            if ($location_stmt) {
                $search_term = "%$location_name%";
                $location_stmt->bind_param("s", $search_term);
                $location_stmt->execute();
                $location_result = $location_stmt->get_result();
                if ($location_row = $location_result->fetch_assoc()) {
                    $delivery_location_id = intval($location_row['delivery_id']);
                }
                $location_stmt->close();
            }
        }
        
        // If still no delivery location, try to infer from address
        if (!$delivery_location_id && !empty($customer_address)) {
            // Try to match address with delivery locations
            $location_sql = "SELECT delivery_id FROM delivery_locations 
                            WHERE ? LIKE CONCAT('%', municipality, '%') 
                               OR ? LIKE CONCAT('%', city, '%')
                            LIMIT 1";
            $location_stmt = $conn->prepare($location_sql);
            if ($location_stmt) {
                $location_stmt->bind_param("ss", $customer_address, $customer_address);
                $location_stmt->execute();
                $location_result = $location_stmt->get_result();
                if ($location_row = $location_result->fetch_assoc()) {
                    $delivery_location_id = intval($location_row['delivery_id']);
                }
                $location_stmt->close();
            }
        }
        
        // If still no delivery location, use a default one (first available)
        if (!$delivery_location_id) {
            $default_location_sql = "SELECT delivery_id FROM delivery_locations ORDER BY delivery_id ASC LIMIT 1";
            $default_result = $conn->query($default_location_sql);
            if ($default_result && $default_row = $default_result->fetch_assoc()) {
                $delivery_location_id = intval($default_row['delivery_id']);
                error_log("Auto-save customer info: Using default delivery location ID: $delivery_location_id");
            }
        }
        
        // Final validation - must have delivery location
        if (!$delivery_location_id) {
            error_log("Auto-save customer info: Could not determine delivery location, aborting");
            return false;
        }
        
        // Use complete address or fallback to customer_address
        $complete_address = $customer_address ?? '';
        if (empty($complete_address)) {
            error_log("Auto-save customer info: No address provided, aborting");
            return false;
        }
        
        // Insert the customer info as primary (first entry is always primary)
        $insert_sql = "INSERT INTO saved_customer_info 
                      (user_id, label, first_name, last_name, email, phone, delivery_location_id, complete_address, is_primary) 
                      VALUES (?, 'My Address', ?, ?, ?, ?, ?, ?, 1)";
        
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            error_log("Auto-save customer info: Failed to prepare insert statement: " . $conn->error);
            return false;
        }
        
        $insert_stmt->bind_param("issssss", 
            $user_id, 
            $first_name, 
            $last_name, 
            $customer_email, 
            $customer_contact, 
            $delivery_location_id, 
            $complete_address
        );
        
        if ($insert_stmt->execute()) {
            $saved_id = $insert_stmt->insert_id;
            error_log("✓ Auto-saved customer info successfully for user $user_id (ID: $saved_id)");
            error_log("   Name: $first_name $last_name, Email: $customer_email, Phone: $customer_contact");
            error_log("   Location ID: $delivery_location_id, Address: $complete_address");
            $insert_stmt->close();
            return true;
        } else {
            error_log("✗ Failed to auto-save customer info: " . $insert_stmt->error);
            $insert_stmt->close();
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Auto-save customer info error: " . $e->getMessage());
        return false;
    }
}
?>
