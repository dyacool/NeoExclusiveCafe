<?php
// Quick debug script to check cart tables
session_start();
require_once 'backend/pages/admin-includes/database.php';

echo "<h2>Cart Debug Information</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>No user logged in</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p>User ID: $user_id</p>";

// Check pre-order cart (cart table)
echo "<h3>Pre-Order Cart (cart table)</h3>";
$preorder_query = "SELECT id as cart_id, product_id, quantity, created_at FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($preorder_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>Cart ID</th><th>Product ID</th><th>Quantity</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['cart_id']}</td><td>{$row['product_id']}</td><td>{$row['quantity']}</td><td>{$row['created_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No items in pre-order cart</p>";
}
$stmt->close();

// Check same-day cart (availtoday_cart table)
echo "<h3>Same-Day Cart (availtoday_cart table)</h3>";
$sameday_query = "SELECT id as cart_id, product_id, quantity, created_at FROM availtoday_cart WHERE user_id = ?";
$stmt = $conn->prepare($sameday_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>Cart ID</th><th>Product ID</th><th>Quantity</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['cart_id']}</td><td>{$row['product_id']}</td><td>{$row['quantity']}</td><td>{$row['created_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No items in same-day cart</p>";
}
$stmt->close();

// Check if cart ID 108 exists anywhere
echo "<h3>Cart ID 108 Check</h3>";
$check_preorder = "SELECT 'cart' as table_name, id, product_id, user_id FROM cart WHERE id = 108";
$check_sameday = "SELECT 'availtoday_cart' as table_name, id, product_id, user_id FROM availtoday_cart WHERE id = 108";

$result1 = $conn->query($check_preorder);
$result2 = $conn->query($check_sameday);

if ($result1 && $result1->num_rows > 0) {
    $row = $result1->fetch_assoc();
    echo "<p>Found in {$row['table_name']}: ID={$row['id']}, Product={$row['product_id']}, User={$row['user_id']}</p>";
} elseif ($result2 && $result2->num_rows > 0) {
    $row = $result2->fetch_assoc();
    echo "<p>Found in {$row['table_name']}: ID={$row['id']}, Product={$row['product_id']}, User={$row['user_id']}</p>";
} else {
    echo "<p>Cart ID 108 not found in either table</p>";
}

$conn->close();
?>
