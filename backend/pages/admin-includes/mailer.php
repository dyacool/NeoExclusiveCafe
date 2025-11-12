<?php
/**
 * Mailer Utility for NeoExclusiveCafe
 * Uses PHPMailer for sending emails
 */

// Use composer autoloader
require_once __DIR__ . '/../../config/mailer/vendor/autoload.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Function to send order notification to admin
function sendOrderNotificationEmail($orderDetails) {
    try {
        error_log("Starting sendOrderNotificationEmail function");
        error_log("Order details received: " . print_r($orderDetails, true));
        
        // Get admin email from settings or use default
        $adminEmail = getAdminEmail();
        error_log("Admin email retrieved: " . $adminEmail);
        
        // Format time and date properly
        $orderDate = isset($orderDetails['order_date']) ? $orderDetails['order_date'] : 'N/A';
        $pickupDate = isset($orderDetails['pickup_date']) ? $orderDetails['pickup_date'] : $orderDate;
        $deliveryDate = isset($orderDetails['delivery_date']) ? $orderDetails['delivery_date'] : 'N/A';
        $orderTime = isset($orderDetails['delivery_time']) ? $orderDetails['delivery_time'] : 'N/A';
        
        error_log("Dates formatted - Order: $orderDate, Pickup: $pickupDate, Delivery: $deliveryDate, Time: $orderTime");
        
        // Determine order type (Sameday Order or Pre-Order)
        $orderType = determineOrderType($orderDetails);
        error_log("Order type determined: " . $orderType);
        
        // Get delivery method for title
        $deliveryMethod = $orderDetails['delivery_method'];
        
        // Create email subject with order type and delivery method
        $subject = "{$orderType} ({$deliveryMethod}) - Order #{$orderDetails['order_id']}";
        error_log("Email subject created: " . $subject);
        
        // Generate the email body with order type and delivery method
        $emailBody = createOrderEmailBody($orderDetails, $orderType, $deliveryMethod);
        error_log("Email body generated, length: " . strlen($emailBody));
        
        // Send the email
        error_log("Attempting to send email to: " . $adminEmail);
        $result = sendEmail($adminEmail, $subject, $emailBody, true);
        error_log("Email send result: " . ($result ? "Success" : "Failed"));
        
        return $result;
    } catch (Exception $e) {
        // Log the error
        error_log("Email sending failed in sendOrderNotificationEmail: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Function to send bulk order request notification to admin (when user submits)
function sendBulkOrderRequestEmail($bulkOrderId, $conn) {
    try {
        error_log("Starting bulk order request email notification for order #$bulkOrderId");
        
        // Validate inputs
        if (!$bulkOrderId || $bulkOrderId <= 0) {
            error_log("Invalid bulk order ID: $bulkOrderId");
            return false;
        }
        
        if (!$conn) {
            error_log("Database connection not provided");
            return false;
        }
        
        // Fetch bulk order details
        $order_sql = "SELECT * FROM bulk_orders WHERE id = ?";
        $order_stmt = mysqli_prepare($conn, $order_sql);
        
        if (!$order_stmt) {
            error_log("Failed to prepare bulk order query: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($order_stmt, "i", $bulkOrderId);
        
        if (!mysqli_stmt_execute($order_stmt)) {
            error_log("Failed to execute bulk order query: " . mysqli_error($conn));
            mysqli_stmt_close($order_stmt);
            return false;
        }
        
        $order_result = mysqli_stmt_get_result($order_stmt);
        $bulkOrder = mysqli_fetch_assoc($order_result);
        mysqli_stmt_close($order_stmt);
        
        if (!$bulkOrder) {
            error_log("Bulk order #$bulkOrderId not found");
            return false;
        }
        
        // Fetch order items
        $items_sql = "SELECT * FROM bulk_order_items WHERE bulk_order_id = ? ORDER BY id";
        $items_stmt = mysqli_prepare($conn, $items_sql);
        
        if (!$items_stmt) {
            error_log("Failed to prepare bulk order items query: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($items_stmt, "i", $bulkOrderId);
        
        if (!mysqli_stmt_execute($items_stmt)) {
            error_log("Failed to execute bulk order items query: " . mysqli_error($conn));
            mysqli_stmt_close($items_stmt);
            return false;
        }
        
        $items_result = mysqli_stmt_get_result($items_stmt);
        $items = [];
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        mysqli_stmt_close($items_stmt);
        
        $bulkOrder['items'] = $items;
        
        // Get admin email
        $adminEmail = getAdminEmail();
        
        // Create email subject
        $orderIdDisplay = !empty($bulkOrder['unique_order_id']) ? $bulkOrder['unique_order_id'] : 'BO' . str_pad($bulkOrderId, 6, '0', STR_PAD_LEFT);
        $subject = "New Bulk Order Request - Order #" . $orderIdDisplay;
        
        // Generate email body
        $emailBody = createBulkOrderRequestEmailBody($bulkOrder);
        
        // Send email
        $result = sendEmail($adminEmail, $subject, $emailBody, true);
        error_log("Bulk order request email send result: " . ($result ? "Success" : "Failed"));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Failed to send bulk order request email: " . $e->getMessage());
        return false;
    }
}

// Function to send bulk order notification to admin (when admin processes)
function sendBulkOrderNotificationEmail($bulkOrderId, $conn) {
    try {
        error_log("Starting bulk order email notification for order #$bulkOrderId");
        
        // Validate inputs
        if (!$bulkOrderId || $bulkOrderId <= 0) {
            error_log("Invalid bulk order ID: $bulkOrderId");
            return false;
        }
        
        if (!$conn) {
            error_log("Database connection not provided");
            return false;
        }
        
        // Fetch bulk order details
        $order_sql = "SELECT * FROM bulk_orders WHERE id = ?";
        $order_stmt = mysqli_prepare($conn, $order_sql);
        
        if (!$order_stmt) {
            error_log("Failed to prepare bulk order query: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($order_stmt, "i", $bulkOrderId);
        
        if (!mysqli_stmt_execute($order_stmt)) {
            error_log("Failed to execute bulk order query: " . mysqli_error($conn));
            mysqli_stmt_close($order_stmt);
            return false;
        }
        
        $order_result = mysqli_stmt_get_result($order_stmt);
        $bulkOrder = mysqli_fetch_assoc($order_result);
        mysqli_stmt_close($order_stmt);
        
        if (!$bulkOrder) {
            error_log("Bulk order #$bulkOrderId not found");
            return false;
        }
        
        error_log("Bulk order details fetched: " . print_r($bulkOrder, true));
        
        // Fetch order items
        $items_sql = "SELECT * FROM bulk_order_items WHERE bulk_order_id = ? ORDER BY id";
        $items_stmt = mysqli_prepare($conn, $items_sql);
        
        if (!$items_stmt) {
            error_log("Failed to prepare bulk order items query: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($items_stmt, "i", $bulkOrderId);
        
        if (!mysqli_stmt_execute($items_stmt)) {
            error_log("Failed to execute bulk order items query: " . mysqli_error($conn));
            mysqli_stmt_close($items_stmt);
            return false;
        }
        
        $items_result = mysqli_stmt_get_result($items_stmt);
        $items = [];
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        mysqli_stmt_close($items_stmt);
        
        error_log("Fetched " . count($items) . " items for bulk order #$bulkOrderId");
        
        // Add items to bulk order array
        $bulkOrder['items'] = $items;
        
        // Calculate totals
        $regularTotal = 0;
        $finalTotal = 0;
        
        foreach ($items as $item) {
            $regularTotal += $item['subtotal'];
            
            // If discount price exists, use it; otherwise use regular price
            if (isset($item['discount_price']) && $item['discount_price'] > 0) {
                $finalTotal += $item['discount_price'] * $item['quantity'];
            } else {
                $finalTotal += $item['product_price'] * $item['quantity'];
            }
        }
        
        $bulkOrder['calculated_regular_total'] = $regularTotal;
        $bulkOrder['calculated_final_total'] = $finalTotal;
        
        error_log("Calculated totals - Regular: ₱" . number_format($regularTotal, 2) . ", Final: ₱" . number_format($finalTotal, 2));
        
        // Get admin email
        $adminEmail = getAdminEmail();
        error_log("Admin email retrieved: " . $adminEmail);
        
        // Create email subject
        $orderIdDisplay = !empty($bulkOrder['unique_order_id']) ? $bulkOrder['unique_order_id'] : 'BO' . str_pad($bulkOrderId, 6, '0', STR_PAD_LEFT);
        $subject = "Bulk Order Notification - Order #" . $orderIdDisplay;
        error_log("Email subject created: " . $subject);
        
        // Generate email body
        $emailBody = createBulkOrderEmailBody($bulkOrder);
        error_log("Email body generated, length: " . strlen($emailBody));
        
        // Send email
        error_log("Attempting to send bulk order email to: " . $adminEmail);
        $result = sendEmail($adminEmail, $subject, $emailBody, true);
        error_log("Bulk order email send result: " . ($result ? "Success" : "Failed"));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Failed to send bulk order email: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Function to determine order type based on pickup/delivery date
function determineOrderType($orderDetails) {
    try {
        error_log("Determining order type for order details: " . print_r($orderDetails, true));
        
        // Determine the relevant date based on delivery method
        $relevantDate = null;
        
        if (isset($orderDetails['delivery_method'])) {
            if ($orderDetails['delivery_method'] === 'Pick-up' || $orderDetails['delivery_method'] === 'pickup') {
                $relevantDate = isset($orderDetails['pickup_date']) ? $orderDetails['pickup_date'] : null;
                error_log("Using pickup_date for order type determination: " . ($relevantDate ?? 'NULL'));
            } else if ($orderDetails['delivery_method'] === 'Delivery' || $orderDetails['delivery_method'] === 'delivery') {
                $relevantDate = isset($orderDetails['delivery_date']) ? $orderDetails['delivery_date'] : null;
                error_log("Using delivery_date for order type determination: " . ($relevantDate ?? 'NULL'));
            }
        }
        
        // If no relevant date found, check both fields as fallback
        if (empty($relevantDate)) {
            $relevantDate = isset($orderDetails['pickup_date']) ? $orderDetails['pickup_date'] : 
                           (isset($orderDetails['delivery_date']) ? $orderDetails['delivery_date'] : null);
            error_log("Using fallback date for order type determination: " . ($relevantDate ?? 'NULL'));
        }
        
        // If still no date, return Pre-Order as safe default
        if (empty($relevantDate)) {
            error_log("No date found in order details, defaulting to Pre-Order");
            return "Pre-Order";
        }
        
        // Get current date in Y-m-d format
        $currentDate = date('Y-m-d');
        error_log("Current date: $currentDate, Relevant order date: $relevantDate");
        
        // Parse the relevant date to ensure it's in proper format
        $orderDateTimestamp = strtotime($relevantDate);
        if ($orderDateTimestamp === false) {
            error_log("Failed to parse order date '$relevantDate', defaulting to Pre-Order");
            return "Pre-Order";
        }
        
        // Convert to Y-m-d format for comparison
        $orderDate = date('Y-m-d', $orderDateTimestamp);
        
        // Compare dates
        if ($orderDate === $currentDate) {
            error_log("Order date matches current date - classified as Sameday Order");
            return "Sameday Order";
        } else {
            error_log("Order date is different from current date - classified as Pre-Order");
            return "Pre-Order";
        }
        
    } catch (Exception $e) {
        error_log("Error determining order type: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        // Return Pre-Order as safe default on error
        return "Pre-Order";
    }
}

// Function to create order email body
function createOrderEmailBody($order, $orderType = "New Order", $deliveryMethod = "") {
    // Base URL for assets and links
    $baseUrl = getBaseUrl();
    
    // Format the title with delivery method if provided
    $emailTitle = htmlspecialchars($orderType);
    if (!empty($deliveryMethod)) {
        $emailTitle .= ' (' . htmlspecialchars($deliveryMethod) . ')';
    }
    
    // Start building HTML email
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Notification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2f603c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .order-info { background-color: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 0 0 5px 5px; }
            .section { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
            .section h2 { color: #2f603c; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #2f603c; color: white; }
            .total { font-weight: bold; text-align: right; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . $emailTitle . ' Notification</h1>
                <p>Order #' . $order['order_id'] . '</p>
                <p>' . date('F j, Y g:i A') . '</p>
            </div>
            
            <div class="section">
                <h2>Customer Information</h2>
                <p><strong>Name:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($order['user_email'] ?? 'Not provided') . '</p>
                <p><strong>Contact:</strong> ' . htmlspecialchars($order['customer_contact']) . '</p>
                <p><strong>Address:</strong> ' . htmlspecialchars($order['customer_address'] ?? 'N/A') . '</p>
            </div>
            
            <div class="section">
                <h2>Order Details</h2>
                <p><strong>Order Type:</strong> ' . $order['delivery_method'] . '</p>';
    
    if ($order['delivery_method'] === 'delivery') {
        $html .= '
                <p><strong>Delivery Date:</strong> ' . date('F j, Y', strtotime($order['delivery_date'])) . '</p>
                <p><strong>Delivery Time:</strong> ' . date('g:i A', strtotime($order['delivery_time'])) . '</p>';
    } else {
        $html .= '
                <p><strong>Pickup Date:</strong> ' . date('F j, Y', strtotime($order['pickup_date'])) . '</p>
                <p><strong>Pickup Time:</strong> ' . date('g:i A', strtotime($order['pickup_time'])) . '</p>';
    }
    
    $html .= '
                <p><strong>Payment Method:</strong> ' . ucfirst($order['payment_method']) . '</p>
            </div>';
    
    if (!empty($order['cart_items'])) {
        $html .= '
            <div class="section">
                <h2>Order Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($order['cart_items'] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars($item['name']) . '</td>
                            <td style="text-align: center;">' . $item['quantity'] . '</td>
                            <td style="text-align: right;">₱' . number_format($item['price'], 2) . '</td>
                            <td style="text-align: right;">₱' . number_format($subtotal, 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="total">Subtotal:</td>
                            <td style="text-align: right;">₱' . number_format($order['cart_total'], 2) . '</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="total">Shipping Fee:</td>
                            <td style="text-align: right;">₱' . number_format($order['shipping_fee'], 2) . '</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="total"><strong>Total Amount:</strong></td>
                            <td style="text-align: right;"><strong>₱' . number_format($order['total_amount'], 2) . '</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>';
    }
    
    if (!empty($order['order_notes'])) {
        $html .= '
            <div class="section">
                <h2>Order Notes</h2>
                <p>' . nl2br(htmlspecialchars($order['order_notes'])) . '</p>
            </div>';
    }
    
    $html .= '
            <div class="footer">
                <p>This is an automated notification from Neo Exclusive Cafe\'s ordering system.</p>
                <p>© ' . date('Y') . ' Neo Exclusive Cafe. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Function to send bulk order approval email to customer
function sendBulkOrderApprovalEmail($bulkOrderId, $conn) {
    try {
        error_log("Starting bulk order approval email to customer for order #$bulkOrderId");
        
        // Validate inputs
        if (!$bulkOrderId || $bulkOrderId <= 0) {
            error_log("Invalid bulk order ID: $bulkOrderId");
            return false;
        }
        
        if (!$conn) {
            error_log("Database connection not provided");
            return false;
        }
        
        // Fetch bulk order details
        $order_sql = "SELECT * FROM bulk_orders WHERE id = ?";
        $order_stmt = mysqli_prepare($conn, $order_sql);
        
        if (!$order_stmt) {
            error_log("Failed to prepare bulk order query: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($order_stmt, "i", $bulkOrderId);
        
        if (!mysqli_stmt_execute($order_stmt)) {
            error_log("Failed to execute bulk order query: " . mysqli_error($conn));
            mysqli_stmt_close($order_stmt);
            return false;
        }
        
        $order_result = mysqli_stmt_get_result($order_stmt);
        $bulkOrder = mysqli_fetch_assoc($order_result);
        mysqli_stmt_close($order_stmt);
        
        if (!$bulkOrder) {
            error_log("Bulk order #$bulkOrderId not found");
            return false;
        }
        
        // Fetch order items
        $items_sql = "SELECT * FROM bulk_order_items WHERE bulk_order_id = ? ORDER BY id";
        $items_stmt = mysqli_prepare($conn, $items_sql);
        
        if (!$items_stmt) {
            error_log("Failed to prepare bulk order items query: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($items_stmt, "i", $bulkOrderId);
        
        if (!mysqli_stmt_execute($items_stmt)) {
            error_log("Failed to execute bulk order items query: " . mysqli_error($conn));
            mysqli_stmt_close($items_stmt);
            return false;
        }
        
        $items_result = mysqli_stmt_get_result($items_stmt);
        $items = [];
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        mysqli_stmt_close($items_stmt);
        
        $bulkOrder['items'] = $items;
        
        // Calculate totals
        $regularTotal = 0;
        $finalTotal = 0;
        
        foreach ($items as $item) {
            $regularTotal += $item['subtotal'];
            
            if (isset($item['discount_price']) && $item['discount_price'] > 0) {
                $finalTotal += $item['discount_price'] * $item['quantity'];
            } else {
                $finalTotal += $item['product_price'] * $item['quantity'];
            }
        }
        
        $bulkOrder['calculated_regular_total'] = $regularTotal;
        $bulkOrder['calculated_final_total'] = $finalTotal;
        
        // Get customer email
        $customerEmail = $bulkOrder['email'];
        
        if (empty($customerEmail)) {
            error_log("No customer email found for bulk order #$bulkOrderId");
            return false;
        }
        
        // Create email subject
        $orderIdDisplay = !empty($bulkOrder['unique_order_id']) ? $bulkOrder['unique_order_id'] : 'BO' . str_pad($bulkOrderId, 6, '0', STR_PAD_LEFT);
        $subject = "Your Bulk Order Has Been Approved - Order #" . $orderIdDisplay;
        
        // Generate email body
        $emailBody = createBulkOrderApprovalEmailBody($bulkOrder);
        
        // Send email
        $result = sendEmail($customerEmail, $subject, $emailBody, true);
        error_log("Bulk order approval email to customer send result: " . ($result ? "Success" : "Failed"));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
        return false;
    }
}

// Function to create bulk order email body (for admin notification)
function createBulkOrderEmailBody($bulkOrder) {
    // Base URL for assets and links
    $baseUrl = getBaseUrl();
    
    // Format order ID for display
    $orderIdDisplay = !empty($bulkOrder['unique_order_id']) ? $bulkOrder['unique_order_id'] : 'BO' . str_pad($bulkOrder['id'], 6, '0', STR_PAD_LEFT);
    
    // Start building HTML email
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bulk Order Notification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2f603c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .order-info { background-color: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 0 0 5px 5px; }
            .section { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
            .section h2 { color: #2f603c; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #2f603c; color: white; }
            .total { font-weight: bold; text-align: right; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
            .cta-button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #2f603c;
                color: white !important;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                margin: 20px 0;
            }
            .cta-button:hover {
                background-color: #234a2e;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 4px;
                font-size: 14px;
                font-weight: bold;
            }
            .status-approved { background-color: #d1fae5; color: #065f46; }
            .status-pending { background-color: #fef3c7; color: #92400e; }
            .discount-row { background-color: #f0fdf4; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Bulk Order Notification</h1>
                <p>Order #' . htmlspecialchars($orderIdDisplay) . '</p>
                <p>' . date('F j, Y g:i A') . '</p>
            </div>
            
            <div class="section">
                <h2>Customer Information</h2>
                <p><strong>Name:</strong> ' . htmlspecialchars($bulkOrder['name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($bulkOrder['email']) . '</p>
                <p><strong>Contact:</strong> ' . htmlspecialchars($bulkOrder['contact']) . '</p>
                <p><strong>Billing Address:</strong> ' . nl2br(htmlspecialchars($bulkOrder['billing_address'])) . '</p>
            </div>
            
            <div class="section">
                <h2>Order Details</h2>
                <p><strong>Order Type:</strong> ' . ucfirst(htmlspecialchars($bulkOrder['order_type'])) . '</p>';
    
    if ($bulkOrder['order_type'] === 'delivery' && !empty($bulkOrder['delivery_address'])) {
        $html .= '
                <p><strong>Delivery Address:</strong> ' . nl2br(htmlspecialchars($bulkOrder['delivery_address'])) . '</p>';
    }
    
    $html .= '
                <p><strong>Purpose:</strong> ' . nl2br(htmlspecialchars($bulkOrder['purpose'])) . '</p>
                <p><strong>Date Needed:</strong> ' . date('F j, Y', strtotime($bulkOrder['date_needed'])) . '</p>
                <p><strong>Time Needed:</strong> ' . date('g:i A', strtotime($bulkOrder['time_needed'])) . '</p>
                <p><strong>Status:</strong> <span class="status-badge status-' . strtolower($bulkOrder['status']) . '">' . ucfirst(str_replace('_', ' ', htmlspecialchars($bulkOrder['status']))) . '</span></p>
                <p><strong>Date Submitted:</strong> ' . date('F j, Y g:i A', strtotime($bulkOrder['created_at'])) . '</p>
            </div>';
    
    if (!empty($bulkOrder['items'])) {
        $html .= '
            <div class="section">
                <h2>Order Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $hasDiscount = false;
        foreach ($bulkOrder['items'] as $item) {
            $itemSubtotal = $item['product_price'] * $item['quantity'];
            $discountSubtotal = isset($item['discount_price']) && $item['discount_price'] > 0 
                ? $item['discount_price'] * $item['quantity'] 
                : 0;
            
            if ($discountSubtotal > 0) {
                $hasDiscount = true;
            }
            
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars($item['product_name']) . '</td>
                            <td style="text-align: center;">' . $item['quantity'] . '</td>
                            <td style="text-align: right;">₱' . number_format($item['product_price'], 2) . '</td>
                            <td style="text-align: right;">' . ($discountSubtotal > 0 ? '₱' . number_format($item['discount_price'], 2) : '-') . '</td>
                            <td style="text-align: right;">₱' . number_format($itemSubtotal, 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="total">Regular Total:</td>
                            <td style="text-align: right;"><strong>₱' . number_format($bulkOrder['calculated_regular_total'], 2) . '</strong></td>
                        </tr>';
        
        if ($hasDiscount) {
            $html .= '
                        <tr class="discount-row">
                            <td colspan="4" class="total">Final Total (with discounts):</td>
                            <td style="text-align: right;"><strong>₱' . number_format($bulkOrder['calculated_final_total'], 2) . '</strong></td>
                        </tr>';
        }
        
        $html .= '
                    </tfoot>
                </table>
            </div>';
    } else {
        $html .= '
            <div class="section">
                <h2>Order Items</h2>
                <p style="color: #666; text-align: center;">No items found for this order</p>
            </div>';
    }
    
    if (!empty($bulkOrder['note'])) {
        $html .= '
            <div class="section">
                <h2>Customer Notes</h2>
                <p>' . nl2br(htmlspecialchars($bulkOrder['note'])) . '</p>
            </div>';
    }
    
    if (!empty($bulkOrder['admin_notes'])) {
        $html .= '
            <div class="section">
                <h2>Admin Notes</h2>
                <p>' . nl2br(htmlspecialchars($bulkOrder['admin_notes'])) . '</p>
            </div>';
    }
    
    // CTA Button
    $bulkOrderUrl = $baseUrl . '/backend/pages/bulks/bulk-order.php?id=' . $bulkOrder['id'];
    $html .= '
            <div class="section" style="text-align: center;">
                <a href="' . htmlspecialchars($bulkOrderUrl) . '" class="cta-button">
                    View Bulk Order Details
                </a>
            </div>';
    
    $html .= '
            <div class="footer">
                <p>This is an automated notification from Neo Exclusive Cafe\'s ordering system.</p>
                <p>© ' . date('Y') . ' Neo Exclusive Cafe. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Function to create bulk order request email body (when user submits - sent to admin)
function createBulkOrderRequestEmailBody($bulkOrder) {
    $baseUrl = getBaseUrl();
    $orderIdDisplay = !empty($bulkOrder['unique_order_id']) ? $bulkOrder['unique_order_id'] : 'BO' . str_pad($bulkOrder['id'], 6, '0', STR_PAD_LEFT);
    
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>New Bulk Order Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2f603c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .alert-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .section { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
            .section h2 { color: #2f603c; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #2f603c; color: white; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
            .cta-button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #2f603c;
                color: white !important;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔔 New Bulk Order Request</h1>
                <p>Order #' . htmlspecialchars($orderIdDisplay) . '</p>
                <p>' . date('F j, Y g:i A') . '</p>
            </div>
            
            <div class="alert-box">
                <strong>⚠️ Action Required:</strong> A new bulk order request has been submitted and requires your review.
            </div>
            
            <div class="section">
                <h2>Customer Information</h2>
                <p><strong>Name:</strong> ' . htmlspecialchars($bulkOrder['name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($bulkOrder['email']) . '</p>
                <p><strong>Contact:</strong> ' . htmlspecialchars($bulkOrder['contact']) . '</p>
                <p><strong>Billing Address:</strong> ' . nl2br(htmlspecialchars($bulkOrder['billing_address'])) . '</p>
            </div>
            
            <div class="section">
                <h2>Order Details</h2>
                <p><strong>Order Type:</strong> ' . ucfirst(htmlspecialchars($bulkOrder['order_type'])) . '</p>';
    
    if ($bulkOrder['order_type'] === 'delivery' && !empty($bulkOrder['delivery_address'])) {
        $html .= '
                <p><strong>Delivery Address:</strong> ' . nl2br(htmlspecialchars($bulkOrder['delivery_address'])) . '</p>';
    }
    
    $html .= '
                <p><strong>Purpose:</strong> ' . nl2br(htmlspecialchars($bulkOrder['purpose'])) . '</p>
                <p><strong>Date Needed:</strong> ' . date('F j, Y', strtotime($bulkOrder['date_needed'])) . '</p>
                <p><strong>Time Needed:</strong> ' . date('g:i A', strtotime($bulkOrder['time_needed'])) . '</p>
                <p><strong>Date Submitted:</strong> ' . date('F j, Y g:i A', strtotime($bulkOrder['created_at'])) . '</p>
            </div>';
    
    if (!empty($bulkOrder['items'])) {
        $totalAmount = 0;
        $html .= '
            <div class="section">
                <h2>Requested Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($bulkOrder['items'] as $item) {
            $itemSubtotal = $item['product_price'] * $item['quantity'];
            $totalAmount += $itemSubtotal;
            
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars($item['product_name']) . '</td>
                            <td style="text-align: center;">' . $item['quantity'] . '</td>
                            <td style="text-align: right;">₱' . number_format($item['product_price'], 2) . '</td>
                            <td style="text-align: right;">₱' . number_format($itemSubtotal, 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                            <td style="text-align: right;"><strong>₱' . number_format($totalAmount, 2) . '</strong></td>
                        </tr>
                    </tfoot>
                </table>
                <p style="margin-top: 10px; font-size: 14px; color: #666;"><em>Note: These are retail prices. You can apply bulk discounts when reviewing the order.</em></p>
            </div>';
    }
    
    if (!empty($bulkOrder['note'])) {
        $html .= '
            <div class="section">
                <h2>Customer Notes</h2>
                <p>' . nl2br(htmlspecialchars($bulkOrder['note'])) . '</p>
            </div>';
    }
    
    $bulkOrderUrl = $baseUrl . '/backend/pages/bulks/bulk-order.php?id=' . $bulkOrder['id'];
    $html .= '
            <div class="section" style="text-align: center;">
                <a href="' . htmlspecialchars($bulkOrderUrl) . '" class="cta-button">
                    Review & Process Order
                </a>
            </div>
            
            <div class="footer">
                <p>This is an automated notification from Neo Exclusive Cafe\'s ordering system.</p>
                <p>© ' . date('Y') . ' Neo Exclusive Cafe. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Function to create bulk order approval email body (sent to customer)
function createBulkOrderApprovalEmailBody($bulkOrder) {
    $baseUrl = getBaseUrl();
    $orderIdDisplay = !empty($bulkOrder['unique_order_id']) ? $bulkOrder['unique_order_id'] : 'BO' . str_pad($bulkOrder['id'], 6, '0', STR_PAD_LEFT);
    
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bulk Order Approved</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2f603c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .success-box { background-color: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px; color: #065f46; }
            .section { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
            .section h2 { color: #2f603c; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #2f603c; color: white; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
            .discount-row { background-color: #f0fdf4; }
            .cta-button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #2f603c;
                color: white !important;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ Your Bulk Order Has Been Approved!</h1>
                <p>Order #' . htmlspecialchars($orderIdDisplay) . '</p>
                <p>' . date('F j, Y g:i A') . '</p>
            </div>
            
            <div class="success-box">
                <strong>Good News!</strong> Your bulk order has been reviewed and approved by our team.
            </div>
            
            <div class="section">
                <h2>Order Summary</h2>
                <p><strong>Order Type:</strong> ' . ucfirst(htmlspecialchars($bulkOrder['order_type'])) . '</p>';
    
    if ($bulkOrder['order_type'] === 'delivery' && !empty($bulkOrder['delivery_address'])) {
        $html .= '
                <p><strong>Delivery Address:</strong> ' . nl2br(htmlspecialchars($bulkOrder['delivery_address'])) . '</p>';
    }
    
    $html .= '
                <p><strong>Purpose:</strong> ' . nl2br(htmlspecialchars($bulkOrder['purpose'])) . '</p>
                <p><strong>Date Needed:</strong> ' . date('F j, Y', strtotime($bulkOrder['date_needed'])) . '</p>
                <p><strong>Time Needed:</strong> ' . date('g:i A', strtotime($bulkOrder['time_needed'])) . '</p>
            </div>';
    
    if (!empty($bulkOrder['items'])) {
        $hasDiscount = false;
        foreach ($bulkOrder['items'] as $item) {
            if (isset($item['discount_price']) && $item['discount_price'] > 0) {
                $hasDiscount = true;
                break;
            }
        }
        
        $html .= '
            <div class="section">
                <h2>Order Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>';
        
        if ($hasDiscount) {
            $html .= '
                            <th>Your Price</th>';
        }
        
        $html .= '
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($bulkOrder['items'] as $item) {
            $itemSubtotal = $item['product_price'] * $item['quantity'];
            $discountSubtotal = isset($item['discount_price']) && $item['discount_price'] > 0 
                ? $item['discount_price'] * $item['quantity'] 
                : 0;
            
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars($item['product_name']) . '</td>
                            <td style="text-align: center;">' . $item['quantity'] . '</td>
                            <td style="text-align: right;">₱' . number_format($item['product_price'], 2) . '</td>';
            
            if ($hasDiscount) {
                $html .= '
                            <td style="text-align: right;">' . ($discountSubtotal > 0 ? '₱' . number_format($item['discount_price'], 2) : '-') . '</td>';
            }
            
            $html .= '
                            <td style="text-align: right;">₱' . number_format($itemSubtotal, 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="' . ($hasDiscount ? '4' : '3') . '" style="text-align: right;"><strong>Regular Total:</strong></td>
                            <td style="text-align: right;"><strong>₱' . number_format($bulkOrder['calculated_regular_total'], 2) . '</strong></td>
                        </tr>';
        
        if ($hasDiscount) {
            $savings = $bulkOrder['calculated_regular_total'] - $bulkOrder['calculated_final_total'];
            $html .= '
                        <tr class="discount-row">
                            <td colspan="4" style="text-align: right;"><strong>Your Discounted Total:</strong></td>
                            <td style="text-align: right;"><strong>₱' . number_format($bulkOrder['calculated_final_total'], 2) . '</strong></td>
                        </tr>
                        <tr class="discount-row">
                            <td colspan="4" style="text-align: right;"><strong>You Save:</strong></td>
                            <td style="text-align: right;"><strong style="color: #10b981;">₱' . number_format($savings, 2) . '</strong></td>
                        </tr>';
        }
        
        $html .= '
                    </tfoot>
                </table>
            </div>';
    }
    
    if (!empty($bulkOrder['admin_notes'])) {
        $html .= '
            <div class="section">
                <h2>Message from Neo Cafe</h2>
                <p>' . nl2br(htmlspecialchars($bulkOrder['admin_notes'])) . '</p>
            </div>';
    }
    
    $html .= '
            <div class="section">
                <h2>Next Steps</h2>
                <ol>
                    <li>Review your order details above</li>
                    <li>Proceed with payment for your order</li>
                    <li>Upload proof of payment through your account</li>
                    <li>We\'ll prepare your order for ' . date('F j, Y', strtotime($bulkOrder['date_needed'])) . '</li>
                </ol>
            </div>
            
            <div class="section" style="text-align: center;">
                <a href="' . htmlspecialchars($baseUrl) . '/frontend/pages/bulk/bulk-order-details.php?id=' . $bulkOrder['id'] . '" class="cta-button">
                    View Order Details
                </a>
            </div>
            
            <div class="footer">
                <p>Thank you for choosing Neo Exclusive Cafe!</p>
                <p>If you have any questions, please contact us.</p>
                <p>© ' . date('Y') . ' Neo Exclusive Cafe. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Function to get email configuration
function getEmailConfig() {
    // Try to load from configuration file if exists
    $configFile = __DIR__ . '/../../config/mailer/email_config.php';
    if (file_exists($configFile)) {
        include $configFile;
        if (isset($email_config) && is_array($email_config)) {
            return $email_config;
        }
    }
    
    // Default configuration for Gmail SMTP
    return [
        'smtp_enabled' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_user' => 'noreplyneoexclusive@gmail.com',
        'smtp_pass' => 'cgfc ktij ytbo wlgu',
        'smtp_secure' => 'tls',
        'smtp_port' => 587,
        'from_email' => 'noreplyneoexclusive@gmail.com',
        'from_name' => 'Neo Exclusive Cafe Orders',
        'reply_email' => 'noreplyneoexclusive@gmail.com'
    ];
}

// Function to send email using PHPMailer
function sendEmail($to, $subject, $body, $isHTML = true) {
    error_log("Starting email send process to: " . $to);
    error_log("Subject: " . $subject);
    
    try {
        // Create PHPMailer instance
        $mail = createPHPMailer();
        
        // Get email configuration
        $config = getEmailConfig();
        error_log("Email configuration loaded: " . print_r($config, true));
        
        // Server settings
        $mail->SMTPDebug = 3; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str");
        };
        
        if ($config['smtp_enabled']) {
            error_log("Using SMTP configuration");
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Performance optimization settings
            $mail->Timeout = 30; // Increased timeout
            $mail->SMTPKeepAlive = true; // Keep connection alive
            
            // Minimal SSL options for development
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            error_log("SMTP settings configured");
        } else {
            error_log("Using PHP mail() function");
            $mail->isMail();
        }
        
        // Sender and recipient settings
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addReplyTo($config['reply_email'], $config['from_name']);
        $mail->addAddress($to);
        
        error_log("Email settings configured. From: {$config['from_email']}, To: {$to}");
        
        // Content settings
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        if ($isHTML) {
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        }
        
        // Attempt to send
        error_log("Attempting to send email...");
        if (!$mail->send()) {
            throw new Exception("Mailer Error: " . $mail->ErrorInfo);
        }
        
        error_log("Email sent successfully to: " . $to);
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Function to test email configuration
function testEmailConfiguration() {
    try {
        $config = getEmailConfig();
        $adminEmail = getAdminEmail();
        
        error_log("Testing email configuration...");
        error_log("Admin email: " . $adminEmail);
        error_log("SMTP Configuration: " . print_r($config, true));
        
        $testSubject = "Test Email from Neo Exclusive Cafe";
        $testBody = "
            <html>
            <body>
                <h2>Test Email</h2>
                <p>This is a test email to verify the email configuration is working correctly.</p>
                <p>Sent at: " . date('Y-m-d H:i:s') . "</p>
            </body>
            </html>
        ";
        
        $result = sendEmail($adminEmail, $testSubject, $testBody);
        error_log("Test email result: " . ($result ? "Success" : "Failed"));
        return $result;
        
    } catch (Exception $e) {
        error_log("Test email failed: " . $e->getMessage());
        return false;
    }
}

// Helper function to create a PHPMailer instance
function createPHPMailer() {
    error_log("Creating PHPMailer instance...");
    
    try {
        $mail = new PHPMailer(true);
        error_log("PHPMailer instance created successfully");
        return $mail;
    } catch (Exception $e) {
        error_log("Error creating PHPMailer instance: " . $e->getMessage());
        throw $e;
    }
}

// Function to get admin email from configuration
function getAdminEmail() {
    try {
        error_log("Getting admin email from configuration...");
        
        // Get admin email from configuration file
        $config = getEmailConfig();
        if (isset($config['admin_email']) && !empty($config['admin_email'])) {
            error_log("Admin email found in config: " . $config['admin_email']);
            return $config['admin_email'];
        }
        
        error_log("No admin email found in configuration, using fallback");
        // Fallback to default
        return 'admin@neoexclusive.com';
        
    } catch (Exception $e) {
        error_log("Error fetching admin email: " . $e->getMessage());
        // Fallback to default
        return 'admin@neoexclusive.com';
    }
}

// Function to get base URL
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName;
}

// Function to generate a secure token for order acceptance
function generateOrderToken($orderId) {
    $secret = 'NeoExclusiveCafe_SecretKey'; // Should be stored in a config file
    return hash('sha256', $orderId . $secret . date('Ymd'));
}

$isDevelopment = false; // Change to false in production
?> 