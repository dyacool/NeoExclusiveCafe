<?php
header('Content-Type: application/json');

// Include database connection first - it handles session configuration
require_once "../../../backend/pages/admin-includes/database.php";

// Check if user is logged in with proper role
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user") {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

if (!isset($_POST["product_id"])) {
    echo json_encode(["success" => false, "message" => "Product ID not provided"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$product_id = $_POST["product_id"];
$quantity = isset($_POST["quantity"]) ? intval($_POST["quantity"]) : 1;

if ($quantity < 1) {
    echo json_encode(["success" => false, "message" => "Invalid quantity"]);
    exit();
}

// Check if product exists, is available, and has sufficient stock
// Status 1, 2, 3 = Pre-order products (Pick Up, Delivery, Flexible)
// Status 4 = Same Day Order (should not be added via this endpoint)
$check_sql = "SELECT id, price, quantity FROM products WHERE id = ? AND status_id IN (1, 2, 3) AND deleted_at IS NULL";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Product not available for pre-order"]);
    exit();
}

$product = $result->fetch_assoc();

// Check if requested quantity is available
if ($quantity > $product["quantity"]) {
    echo json_encode(["success" => false, "message" => "Insufficient stock. Available: " . $product["quantity"]]);
    exit();
}

// Check if product is already in cart
$cart_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("ii", $user_id, $product_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Update quantity if product already in cart
    $cart_item = $cart_result->fetch_assoc();
    $new_quantity = $cart_item["quantity"] + $quantity;
    
    // Check if total quantity exceeds available stock
    if ($new_quantity > $product["quantity"]) {
        echo json_encode([
            "success" => false, 
            "message" => "Cannot add more items. Cart would exceed available stock. Available: " . 
                      ($product["quantity"] - $cart_item["quantity"])
        ]);
        exit();
    }
    
    $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_quantity, $cart_item["id"]);
    $update_stmt->execute();
} else {
    // Add new item to cart
    $insert_sql = "INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iiid", $user_id, $product_id, $quantity, $product["price"]);
    $insert_stmt->execute();
}

echo json_encode(["success" => true]);
$conn->close();
?> 