<?php
/**
 * Submit Delivery Proof API
 * Handles proof image upload, order status update, and notifications
 */

session_start();

// TODO: Implement proper rider authentication
// For now, using a simple check - replace with actual rider authentication
if (!isset($_SESSION["is_rider"]) && !isset($_SESSION["is_admin"])) {
    // Temporary: Allow admin access for testing
    if (!isset($_SESSION["is_admin"])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized access'
        ]);
        exit();
    }
}

require_once __DIR__ . '/../backend/pages/admin-includes/database.php';
require_once __DIR__ . '/../backend/pages/admin-includes/activity-logger.php';
require_once __DIR__ . '/../backend/includes/cloudinary-helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Validate inputs
    if (!isset($_POST['order_id']) || !isset($_FILES['proof_image'])) {
        throw new Exception('Missing required parameters');
    }
    
    $order_id = intval($_POST['order_id']);
    $proof_image = $_FILES['proof_image'];
    
    // Validate order exists and is a delivery order
    $order_sql = "SELECT order_id, customer_email, customer_name, delivery_method, status 
                  FROM orders WHERE order_id = ?";
    $order_stmt = mysqli_prepare($conn, $order_sql);
    mysqli_stmt_bind_param($order_stmt, "i", $order_id);
    mysqli_stmt_execute($order_stmt);
    $order_result = mysqli_stmt_get_result($order_stmt);
    
    if (mysqli_num_rows($order_result) == 0) {
        throw new Exception('Order not found');
    }
    
    $order = mysqli_fetch_assoc($order_result);
    
    if ($order['delivery_method'] !== 'Delivery') {
        throw new Exception('Order is not a delivery order');
    }
    
    // Check if proof already exists
    $check_sql = "SELECT id FROM pod_orders WHERE order_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $order_id);
    mysqli_stmt_execute($check_stmt);
    if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
        throw new Exception('Proof already submitted for this order');
    }
    
    // Validate image file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($proof_image['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPEG and PNG are allowed');
    }
    
    if ($proof_image['size'] > $max_size) {
        throw new Exception('File too large. Maximum size is 5MB');
    }
    
    if ($proof_image['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $proof_image['error']);
    }
    
    // Upload to Cloudinary
    $public_id = 'order_' . $order_id . '_' . date('Ymd_His');
    $cloudinary_result = uploadToCloudinary(
        $proof_image['tmp_name'],
        'neocafe/delivery-proofs',
        $public_id
    );
    
    if (!$cloudinary_result['success']) {
        throw new Exception('Failed to upload proof image: ' . ($cloudinary_result['error'] ?? 'Unknown error'));
    }
    
    $cloudinary_url = $cloudinary_result['url'];
    $cloudinary_public_id = $cloudinary_result['public_id'];
    
    // Get rider identifier (use session or default)
    $submitted_by = isset($_SESSION['rider_name']) ? $_SESSION['rider_name'] : 
                   (isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Rider');
    
    // Insert into pod_orders table with Cloudinary URL
    $pod_sql = "INSERT INTO pod_orders (order_id, proof_image_path, cloudinary_public_id, submitted_by, image_size) 
                VALUES (?, ?, ?, ?, ?)";
    $pod_stmt = mysqli_prepare($conn, $pod_sql);
    $image_size = $cloudinary_result['bytes'] ?? 0;
    mysqli_stmt_bind_param($pod_stmt, "isssi", $order_id, $cloudinary_url, $cloudinary_public_id, $submitted_by, $image_size);
    
    if (!mysqli_stmt_execute($pod_stmt)) {
        // Delete from Cloudinary if database insert fails
        deleteFromCloudinary($cloudinary_public_id);
        throw new Exception('Failed to save proof record: ' . mysqli_stmt_error($pod_stmt));
    }
    
    // Update order status to "Delivered"
    $status_sql = "UPDATE orders SET status = 'Delivered', completion_date = NOW() WHERE order_id = ?";
    $status_stmt = mysqli_prepare($conn, $status_sql);
    mysqli_stmt_bind_param($status_stmt, "i", $order_id);
    
    if (!mysqli_stmt_execute($status_stmt)) {
        throw new Exception('Failed to update order status: ' . mysqli_stmt_error($status_stmt));
    }
    
    // Log activity
    logAdminActivity($conn, 'DELIVERY', "Delivery proof submitted for order #$order_id by $submitted_by", 'orders', $order_id);
    
    // Send notifications (email and in-app)
    sendDeliveryNotifications($conn, $order_id, $order['customer_email'], $order['customer_name']);
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Delivery proof submitted successfully',
        'order_id' => $order_id,
        'proof_url' => $cloudinary_url,
        'cloudinary_public_id' => $cloudinary_public_id,
        'new_status' => 'Delivered'
    ]);
    
} catch (Exception $e) {
    error_log('Proof submission error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Send delivery notifications to customer
 */
function sendDeliveryNotifications($conn, $order_id, $customer_email, $customer_name) {
    try {
        // Send in-app notification
        if (file_exists(__DIR__ . '/../frontend/pages/notifications/class-notif.php')) {
            require_once __DIR__ . '/../frontend/pages/notifications/class-notif.php';
            $notification = new Notification($conn);
            $notification->createOrderNotification($order_id, 'Delivered');
        }
        
        // Send email notification
        if (!empty($customer_email) && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            if (file_exists(__DIR__ . '/../backend/pages/admin-includes/mailer.php')) {
                require_once __DIR__ . '/../backend/pages/admin-includes/mailer.php';
                
                $subject = "Order #{$order_id} Has Been Delivered!";
                $base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $order_link = $base . $host . "/NeoCafe/frontend/pages/cart/order-details.php?order_id=" . $order_id;
                
                $body = "<!DOCTYPE html><html><body style='font-family: Arial, sans-serif; color:#333; line-height: 1.6;'>"
                      . "<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>"
                      . "<h2 style='color: #22c55e;'>✓ Order Delivered Successfully!</h2>"
                      . "<p>Hello " . htmlspecialchars($customer_name) . ",</p>"
                      . "<p>Great news! Your order <strong>#" . $order_id . "</strong> has been delivered.</p>"
                      . "<p>Our delivery rider has submitted photographic proof of delivery for your records.</p>"
                      . "<div style='margin: 30px 0;'>"
                      . "<a href='" . $order_link . "' style='background:#667eea;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;'>View Order & Delivery Proof</a>"
                      . "</div>"
                      . "<p style='font-size:12px;color:#777;'>If the button doesn't work, copy and paste this URL:<br>" . $order_link . "</p>"
                      . "<hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>"
                      . "<p style='font-size:14px;'>Thank you for choosing Neo Exclusive Cafe!</p>"
                      . "<p style='font-size:12px; color:#999;'>This is an automated message. Please do not reply to this email.</p>"
                      . "</div>"
                      . "</body></html>";
                
                sendEmail($customer_email, $subject, $body, true);
            }
        }
    } catch (Exception $e) {
        error_log('Notification error: ' . $e->getMessage());
        // Don't throw - notifications are non-critical
    }
}

mysqli_close($conn);
?>
