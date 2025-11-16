<?php
/**
 * Check Purchase API
 * Verifies if a user has purchased a specific product
 */

require_once __DIR__ . '/../../includes/session-manager.php';
require_once __DIR__ . '/../../config/database-config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!SessionManager::isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'has_purchased' => false, 'message' => 'Please log in']);
    exit;
}

$user_id = SessionManager::getUserId();
$conn = getDatabaseConnection();

// Get product ID from query parameter
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'has_purchased' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Get user email for order matching
$user_query = "SELECT email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'has_purchased' => false, 'message' => 'User not found']);
    exit;
}

$user = $user_result->fetch_assoc();
$user_email = $user['email'];

// Check if user has purchased this product
// We'll check order_items by matching product_name with products table
// Since order_items doesn't have product_id, we match by product name
// Also check if orders table has user_id column (for newer schema)
$check_user_id = $conn->query("SHOW COLUMNS FROM orders LIKE 'user_id'");
$has_user_id = $check_user_id && $check_user_id->num_rows > 0;

if ($has_user_id) {
    // Use user_id if available
    $purchase_query = "SELECT DISTINCT o.order_id, o.order_date, o.status
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    INNER JOIN products p ON oi.product_name = p.name
    WHERE o.user_id = ? 
    AND p.id = ?
    AND o.status IN ('Completed', 'Delivered', 'Picked-up')
    ORDER BY o.order_date DESC
    LIMIT 1";
    $purchase_stmt = $conn->prepare($purchase_query);
    $purchase_stmt->bind_param("ii", $user_id, $product_id);
} else {
    // Fallback to customer_email
    $purchase_query = "SELECT DISTINCT o.order_id, o.order_date, o.status
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    INNER JOIN products p ON oi.product_name = p.name
    WHERE o.customer_email = ? 
    AND p.id = ?
    AND o.status IN ('Completed', 'Delivered', 'Picked-up')
    ORDER BY o.order_date DESC
    LIMIT 1";
    $purchase_stmt = $conn->prepare($purchase_query);
    $purchase_stmt->bind_param("si", $user_email, $product_id);
}

$purchase_stmt->execute();
$purchase_result = $purchase_stmt->get_result();

$has_purchased = $purchase_result->num_rows > 0;
$order_info = null;

if ($has_purchased) {
    $order_info = $purchase_result->fetch_assoc();
}

// Also check if user has already reviewed this product
$review_check = $conn->prepare("SELECT id, rating, review_text FROM product_reviews WHERE user_id = ? AND product_id = ?");
$review_check->bind_param("ii", $user_id, $product_id);
$review_check->execute();
$review_result = $review_check->get_result();
$existing_review = $review_result->num_rows > 0 ? $review_result->fetch_assoc() : null;

echo json_encode([
    'success' => true,
    'has_purchased' => $has_purchased,
    'order_info' => $order_info,
    'has_reviewed' => $existing_review !== null,
    'existing_review' => $existing_review
]);

$user_stmt->close();
$purchase_stmt->close();
$review_check->close();
$conn->close();
?>

