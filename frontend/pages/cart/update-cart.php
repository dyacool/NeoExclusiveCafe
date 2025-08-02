<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
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

$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit();
}

// Verify the cart item belongs to the user
$verify_sql = "SELECT id FROM cart WHERE id = ? AND user_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $cart_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "Cart item not found"]);
    exit();
}

// Update the quantity
$update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $quantity, $cart_id);

if ($update_stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to update quantity"]);
}

$conn->close();
?> 