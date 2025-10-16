<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "Not authenticated"]);
    exit();
}

header('Content-Type: application/json');

require_once '../../../backend/pages/admin-includes/database.php';

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;

if ($cart_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid cart ID"]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Item removed successfully"]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to remove item"]);
}

$stmt->close();
$conn->close();
?>

