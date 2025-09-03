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
require_once '../../user-includes/database.php';
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
        // For presentation purposes, simulate successful payment verification
        error_log("Simulating successful payment verification for presentation");
        
        // Mock successful payment result
        $payment_status = 'paid';
        
        if (in_array($payment_status, ['paid', 'succeeded'])) {
            // Payment successful - finalize order (mock for presentation)
            $order_id = $pending_payment['order_id'];
            
            if ($order_id) {
                // Send order confirmation email
                try {
                    error_log("Sending order confirmation email for order: " . $order_id);
                    sendOrderConfirmationEmail($order_id, $pending_payment['order_data'], $type);
                    error_log("Order confirmation email sent successfully");
                } catch (Exception $e) {
                    error_log("Failed to send order confirmation email: " . $e->getMessage());
                    // Don't fail the payment process if email fails
                }
                
                // Clear cart based on order type
                if ($type === 'availtoday') {
                    clearAvailTodayCart($pending_payment['order_id']);
                } else {
                    clearRegularCart($pending_payment['order_id']);
                }
                
                // Store success data for confirmation page
                $_SESSION['payment_success'] = [
                    'order_id' => $order_id,
                    'amount' => $pending_payment['amount'],
                    'payment_method' => $pending_payment['payment_method'],
                    'order_type' => $type
                ];
                
                // Clear pending payment
                unset($_SESSION['pending_payment']);
                
                // Redirect to success page
                header("Location: payment-success.php?type=$type");
                exit();
            } else {
                throw new Exception('Failed to finalize order');
            }
        } else {
            // Payment failed
            throw new Exception('Payment not completed. Status: ' . $payment_status);
        }
        
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
        // Get order details from database (or use order_data for mock)
        $orderDetails = [
            'order_id' => $order_id,
            'customer_name' => $order_data['customer_name'] ?? $order_data['first_name'] . ' ' . $order_data['last_name'],
            'customer_email' => $order_data['customer_email'] ?? $order_data['email'],
            'customer_contact' => $order_data['phone'] ?? 'N/A',
            'payment_method' => $order_data['payment_method'],
            'total_amount' => $order_data['cart_total'] ?? '0',
            'delivery_method' => ucfirst($order_data['shipping_method']),
            'order_date' => date('Y-m-d H:i:s'),
            'pickup_date' => date('Y-m-d', strtotime('+1 day')), // Default to tomorrow
            'delivery_date' => date('Y-m-d', strtotime('+1 day')),
            'delivery_time' => '10:00:00',
            'notes' => $order_data['special_instructions'] ?? '',
            'order_type' => $order_type,
            'cart_items' => json_decode($order_data['cart_items'], true)
        ];
        
        error_log("Order details prepared for email: " . json_encode($orderDetails));
        
        // Send email to customer
        if (!empty($orderDetails['customer_email'])) {
            $customerEmailSent = sendCustomerOrderConfirmation($orderDetails);
            error_log("Customer email sent: " . ($customerEmailSent ? 'Success' : 'Failed'));
        }
        
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
