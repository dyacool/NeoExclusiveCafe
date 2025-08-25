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

// Get parameters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? 'regular';
$source_id = $_GET['source_id'] ?? '';

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
        // Verify payment with PayMongo
        if (isset($pending_payment['source_id'])) {
            $result = $paymongo->getSource($pending_payment['source_id']);
        } else {
            $result = $paymongo->getPaymentIntent($pending_payment['payment_intent_id']);
        }
        
        if (isset($result['error'])) {
            throw new Exception('Failed to verify payment: ' . json_encode($result['error']));
        }
        
        // Check payment status
        $payment_status = $result['data']['attributes']['status'];
        
        if (in_array($payment_status, ['paid', 'succeeded'])) {
            // Payment successful - finalize order
            $order_id = finalizeOrder($pending_payment['order_id'], $result);
            
            if ($order_id) {
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
                       order_status = 'confirmed',
                       payment_status = 'paid',
                       payment_id = ?,
                       amount_paid = ?,
                       paid_at = NOW()
                       WHERE id = ?";
        
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
    global $conn;
    
    $update_sql = "UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $order_status, $payment_status, $order_id);
    $update_stmt->execute();
}

/**
 * Clear available today cart
 */
function clearAvailTodayCart($order_id) {
    global $conn;
    
    $clear_sql = "DELETE FROM cart_availtoday WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_sql);
    $clear_stmt->bind_param("i", $_SESSION['user_id']);
    $clear_stmt->execute();
}

/**
 * Clear regular cart
 */
function clearRegularCart($order_id) {
    global $conn;
    
    // Get cart items that were in the order
    $order_items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $order_items_stmt = $conn->prepare($order_items_sql);
    $order_items_stmt->bind_param("i", $order_id);
    $order_items_stmt->execute();
    $order_items_result = $order_items_stmt->get_result();
    
    // Remove items from cart
    while ($item = $order_items_result->fetch_assoc()) {
        $delete_cart_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $delete_cart_stmt = $conn->prepare($delete_cart_sql);
        $delete_cart_stmt->bind_param("ii", $_SESSION['user_id'], $item['product_id']);
        $delete_cart_stmt->execute();
    }
}
?>
