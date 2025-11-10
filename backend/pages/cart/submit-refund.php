<?php
// Load database first (it starts session)
require_once '../admin-includes/database.php';

// Then load SessionManager
require_once __DIR__ . '/../../../includes/session-manager.php';

header('Content-Type: application/json');

if (!SessionManager::isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = SessionManager::getUserId();
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$refund_reason = isset($_POST['refund_reason']) ? trim($_POST['refund_reason']) : '';
$refund_items = isset($_POST['refund_items']) ? $_POST['refund_items'] : '';
$refund_note = isset($_POST['refund_note']) ? trim($_POST['refund_note']) : '';

// Validation
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

if (!in_array($refund_reason, ['spoiled', 'wrong_item', 'damaged', 'other'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid refund reason']);
    exit();
}

if (empty($refund_items)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one item to refund']);
    exit();
}

// Verify order belongs to user
$order_check = "SELECT order_id, status FROM orders WHERE order_id = ? AND (customer_id = ? OR customer_email = (SELECT email FROM users WHERE id = ?))";
$stmt = $conn->prepare($order_check);
$stmt->bind_param('iii', $order_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
    exit();
}

// Check if order is delivered/picked up
$status_lower = strtolower($order['status']);
if ($status_lower !== 'delivered' && $status_lower !== 'picked-up' && $status_lower !== 'picked up') {
    echo json_encode(['success' => false, 'message' => 'Refunds are only available for delivered/picked-up orders']);
    exit();
}

// Check if refund already exists
$refund_check = "SELECT refund_id FROM order_refunds WHERE order_id = ? AND user_id = ?";
$stmt = $conn->prepare($refund_check);
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A refund request already exists for this order']);
    exit();
}
$stmt->close();

// Handle proof image upload to Cloudinary
$proof_image = '';
$cloud_url = '';
$cloud_public_id = '';
if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    // Load Cloudinary helper
    require_once __DIR__ . '/../../includes/cloudinary-helper.php';
    
    $file_extension = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
        exit();
    }
    
    // Check file size (max 5MB)
    if ($_FILES['proof_image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
        exit();
    }
    
    // Generate unique public ID
    $publicId = 'refund_' . $order_id . '_' . $user_id . '_' . time();
    
    // Upload to Cloudinary
    $result = uploadToCloudinary($_FILES['proof_image']['tmp_name'], 'neocafe/refund_proofs', $publicId);
    
    if ($result['success']) {
        $cloud_url = $result['url'];
        $cloud_public_id = $result['public_id'];
        $proof_image = $cloud_url; // For backward compatibility
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload proof image: ' . $result['error']]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Proof image is required']);
    exit();
}

// Calculate refund amount from selected items
$items_array = json_decode($refund_items, true);
if (!is_array($items_array) || empty($items_array)) {
    echo json_encode(['success' => false, 'message' => 'Invalid items data']);
    exit();
}

$refund_amount = 0;
foreach ($items_array as $item) {
    $refund_amount += floatval($item['price']) * intval($item['quantity']);
}

// Insert refund request
$insert_sql = "INSERT INTO order_refunds (order_id, user_id, refund_reason, refund_items, refund_note, proof_image, cloud_url, cloud_public_id, cloud_provider, refund_amount, refund_status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cloudinary', ?, 'pending')";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param('iisssssd', $order_id, $user_id, $refund_reason, $refund_items, $refund_note, $proof_image, $cloud_url, $cloud_public_id, $refund_amount);

if ($stmt->execute()) {
    $refund_id = $stmt->insert_id;
    
    // Create admin notification for new refund request
    try {
        require_once '../admin-includes/notifications/notification.php';
        $notificationHandler = new NotificationHandler($conn);
        
        // Get customer name and username
        $customer_query = "SELECT o.customer_name, u.username 
                          FROM orders o 
                          LEFT JOIN users u ON o.customer_id = u.id 
                          WHERE o.order_id = ?";
        $customer_stmt = $conn->prepare($customer_query);
        $customer_stmt->bind_param('i', $order_id);
        $customer_stmt->execute();
        $customer_result = $customer_stmt->get_result();
        $customer_data = $customer_result->fetch_assoc();
        $customer_stmt->close();
        
        $customer_name = $customer_data['customer_name'] ?? 'Unknown Customer';
        $username = $customer_data['username'] ?? null;
        
        // Create notification for new refund request
        $notificationHandler->createRefundNotification(
            $refund_id,
            $order_id,
            'refund_new',
            $customer_name,
            $username,
            null,
            $refund_amount
        );
        
    } catch (Exception $e) {
        error_log("Failed to create refund notification: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Refund request submitted successfully',
        'refund_id' => $refund_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit refund request']);
}

$stmt->close();
$conn->close();
?>
