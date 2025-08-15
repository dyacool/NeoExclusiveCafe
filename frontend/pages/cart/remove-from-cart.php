<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    header('Location: ../../login/user/login-signup.php');
    exit();
}

if (!isset($_POST["cart_id"])) {
    echo json_encode(["success" => false, "error" => "Cart ID not provided"]);
    exit();
}

$cart_id = $_POST["cart_id"];
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

// Delete the cart item
$delete_sql = "DELETE FROM cart WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $cart_id);

if ($delete_stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to remove item"]);
}

$conn->close();
?> 