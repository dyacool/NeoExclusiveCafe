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
        
        // Determine the relevant date based on delivery method
        $relevantDate = ($orderDetails['delivery_method'] == 'Pick-up') ? $pickupDate : $deliveryDate;
        
        // Create email subject
        $subject = "Order #{$orderDetails['order_id']} - {$orderDetails['delivery_method']} - {$relevantDate}";
        error_log("Email subject created: " . $subject);
        
        // Generate the email body
        $emailBody = createOrderEmailBody($orderDetails);
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

// Function to create order email body
function createOrderEmailBody($order) {
    // Base URL for assets and links
    $baseUrl = getBaseUrl();
    
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
                <h1>New Order Notification</h1>
                <p>Order #' . $order['order_id'] . '</p>
                <p>' . date('F j, Y g:i A') . '</p>
            </div>
            
            <div class="section">
                <h2>Customer Information</h2>
                <p><strong>Name:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($order['user_email']) . '</p>
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