<?php
header('Content-Type: application/json');

// Include database connection first - it handles session configuration
require_once "../../../backend/pages/admin-includes/database.php";

// Check if user is logged in with proper role
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user") {
    echo json_encode(["success" => false, "error" => "User not logged in"]);
    exit();
}

if (!isset($_POST["cart_id"]) || !isset($_POST["quantity"])) {
    echo json_encode(["success" => false, "error" => "Missing required parameters"]);
    exit();
}

$cart_id = $_POST["cart_id"];
$quantity = $_POST["quantity"];
$user_id = $_SESSION["user_id"];
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit();
}

// Get cart item info and verify it belongs to the user
$verify_sql = "SELECT c.product_id, c.quantity, p.quantity as product_stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $cart_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Cart item not found"]);
    exit();
}

$cart_data = $verify_result->fetch_assoc();
$product_id = $cart_data['product_id'];
$current_quantity = $cart_data['quantity'];
$product_stock = $cart_data['product_stock'];

// Get total quantity already in cart for this product (excluding this cart item)
$other_cart_sql = "SELECT COALESCE(SUM(quantity), 0) as other_qty FROM cart WHERE product_id = ? AND user_id = ? AND id != ?";
$other_cart_stmt = $conn->prepare($other_cart_sql);
$other_cart_stmt->bind_param("iii", $product_id, $user_id, $cart_id);
$other_cart_stmt->execute();
$other_cart_result = $other_cart_stmt->get_result();
$other_cart_data = $other_cart_result->fetch_assoc();
$other_cart_qty = intval($other_cart_data['other_qty']);

// Calculate maximum allowed quantity for this cart item
$max_allowed = max(0, $product_stock - $other_cart_qty);

if ($quantity > $max_allowed) {
    echo json_encode([
        "success" => false, 
        "message" => "Cannot update to {$quantity}. Maximum available: {$max_allowed} (Total stock: {$product_stock}, Already in cart: {$other_cart_qty})",
        "current_quantity" => $current_quantity
    ]);
    exit();
}

// Update the quantity
$update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $quantity, $cart_id);

if ($update_stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Quantity updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update quantity: " . $update_stmt->error]);
}

$conn->close();
?> 