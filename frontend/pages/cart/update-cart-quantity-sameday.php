<?php
// Include database first (starts session automatically)
require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

header('Content-Type: application/json');

// Check authentication
if (!SessionManager::isUserLoggedIn()) {
    echo json_encode(["success" => false, "error" => "Not authenticated"]);
    exit();
}

$user_id = SessionManager::getUserId();
$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if (!$user_id || $cart_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit();
}

if ($quantity <= 0) {
    // Remove item if quantity is 0
    $stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Item removed"]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to remove item: " . $stmt->error]);
    }
    $stmt->close();
} else {
    // Get product_id and current quantity for stock validation
    $cart_info = $conn->prepare("SELECT product_id, quantity FROM availtoday_cart WHERE id = ? AND user_id = ?");
    if (!$cart_info) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit();
    }
    
    $cart_info->bind_param("ii", $cart_id, $user_id);
    $cart_info->execute();
    $cart_result = $cart_info->get_result();
    
    if ($cart_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Cart item not found"]);
        exit();
    }
    
    $cart_data = $cart_result->fetch_assoc();
    $product_id = $cart_data['product_id'];
    $current_quantity = $cart_data['quantity'];
    $cart_info->close();
    
    // Get available stock and total cart quantity for this product
    $stock_check = $conn->prepare("
        SELECT 
            COALESCE(qpd.quantity, 0) as available_stock,
            COALESCE((SELECT SUM(quantity) FROM availtoday_cart WHERE product_id = ? AND user_id = ? AND id != ?), 0) as other_cart_qty
        FROM quantity_per_day_sdo qpd
        WHERE qpd.product_id = ? AND DATE(qpd.date) = CURDATE()
    ");
    
    if (!$stock_check) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit();
    }
    
    $stock_check->bind_param("iiii", $product_id, $user_id, $cart_id, $product_id);
    $stock_check->execute();
    $stock_result = $stock_check->get_result();
    $stock_data = $stock_result->fetch_assoc();
    $available_stock = $stock_data ? intval($stock_data['available_stock']) : 0;
    $other_cart_qty = $stock_data ? intval($stock_data['other_cart_qty']) : 0;
    $stock_check->close();
    
    // Calculate how much this user can add (total stock - what they already have in other cart items)
    $max_allowed = max(0, $available_stock - $other_cart_qty);
    
    if ($quantity > $max_allowed) {
        echo json_encode([
            "success" => false, 
            "message" => "Cannot update to {$quantity}. Maximum available: {$max_allowed} (Total stock: {$available_stock}, Already in cart: {$other_cart_qty})",
            "current_quantity" => $current_quantity
        ]);
        exit();
    }
    
    // Update quantity
    $stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ? WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Quantity updated"]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update quantity: " . $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>

