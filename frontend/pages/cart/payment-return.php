<?php
/**
 * PayMongo Payment Return Handler
 * Handles payment success/failure redirects from PayMongo
 */

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Include required files
require_once '../../../backend/pages/admin-includes/database.php';
require_once 'paymongo-config.php';
require_once '../../../backend/pages/admin-includes/mailer.php';

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
            $customer_name = trim(($order_data['user_name'] ?? ($order_data['customer_name'] ?? '')));
            $customer_email = $order_data['user_email'] ?? ($order_data['customer_email'] ?? null);
            $customer_contact = $order_data['phone'] ?? $order_data['contact_number'] ?? null;
            $customer_address = $order_data['delivery_address'] ?? ($order_data['address'] ?? null);
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

                // Inventory update
                if (!empty($it['product_id'])) {
                    $pid = intval($it['product_id']);
                    error_log("Updating inventory for product ID: $pid, quantity: $qty");
                    
                    // Check stock first
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

                            // If stock hits 0, mark unavailable similar to existing logic
                            if ($new_stock <= 0) {
                                $new_status_id = ($row['status_id'] == 1) ? 4 : 5; // pickup->4, delivery->5, default 5
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

        // Send email but don't block on failure
        try {
            sendOrderConfirmationEmail($order_id_created, is_array($pending_payment['order_data']) ? $pending_payment['order_data'] : $order_data, $type);
        } catch (Exception $e) {
            error_log("Order confirmation email failed: " . $e->getMessage());
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
        // Normalize email/name and fields for reliability
        $normalized_email = $order_data['customer_email'] ?? ($order_data['email'] ?? ($order_data['user_email'] ?? ''));
        $normalized_name = $order_data['customer_name'] ?? ($order_data['user_name'] ?? trim(($order_data['first_name'] ?? '') . ' ' . ($order_data['last_name'] ?? '')));
        $normalized_delivery_method = $order_data['delivery_method'] ?? ($order_data['shipping_method'] ?? 'pickup');
        $normalized_delivery_method = strtolower($normalized_delivery_method) === 'delivery' ? 'Delivery' : 'Pick-up';
        $normalized_items = $order_data['cart_items'] ?? [];
        if (is_string($normalized_items)) {
            $decoded = json_decode($normalized_items, true);
            if (json_last_error() === JSON_ERROR_NONE) { $normalized_items = $decoded; }
        }

        $orderDetails = [
            'order_id' => $order_id,
            'customer_name' => $normalized_name,
            'customer_email' => $normalized_email,
            'customer_contact' => $order_data['phone'] ?? ($order_data['contact_number'] ?? 'N/A'),
            'payment_method' => $order_data['payment_method'] ?? '',
            'total_amount' => $order_data['cart_total'] ?? ($order_data['total_amount'] ?? '0'),
            'delivery_method' => $normalized_delivery_method,
            'order_date' => date('Y-m-d H:i:s'),
            'pickup_date' => $order_data['pickup_date'] ?? date('Y-m-d', strtotime('+1 day')),
            'delivery_date' => $order_data['delivery_date'] ?? date('Y-m-d', strtotime('+1 day')),
            'delivery_time' => $order_data['delivery_time'] ?? '10:00:00',
            'notes' => $order_data['special_instructions'] ?? ($order_data['order_notes'] ?? ''),
            'order_type' => $order_type,
            'cart_items' => $normalized_items
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
?>
