<?php
// Simple script to clear expired cart data
require_once __DIR__ . "/../admin-includes/database.php";

// Clear the cart_availToday table
$result = $conn->query("TRUNCATE TABLE cart_availToday");

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Cart cleared']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error clearing cart']);
}

$conn->close();
?>
