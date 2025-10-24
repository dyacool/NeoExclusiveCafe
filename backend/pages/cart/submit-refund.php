<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();
require_once '../admin-includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
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

// Handle proof image upload
$proof_image = '';
if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../../assets/refund-proofs/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
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
    
    $filename = 'refund_' . $order_id . '_' . $user_id . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $filepath)) {
        $proof_image = 'assets/refund-proofs/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload proof image']);
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
$insert_sql = "INSERT INTO order_refunds (order_id, user_id, refund_reason, refund_items, refund_note, proof_image, refund_amount, refund_status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param('iissssd', $order_id, $user_id, $refund_reason, $refund_items, $refund_note, $proof_image, $refund_amount);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Refund request submitted successfully',
        'refund_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit refund request']);
}

$stmt->close();
$conn->close();
?>
