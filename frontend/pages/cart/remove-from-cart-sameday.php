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

if (!$user_id || $cart_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE id = ? AND user_id = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $cart_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Item removed successfully"]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to remove item: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

