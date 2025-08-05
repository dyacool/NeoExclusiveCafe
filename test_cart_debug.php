<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Cart Debug Information</h2>";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo "<p style='color: red;'>User not logged in!</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p><strong>User ID:</strong> $user_id</p>";

// Check current cart items
echo "<h3>Current Cart Items:</h3>";
$cart_sql = "SELECT c.id AS cart_id, c.quantity, c.price,
                   p.id AS product_id, p.name AS product_name, p.quantity as product_stock,
                   ps.name as status_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_statuses ps ON p.status_id = ps.id
            WHERE c.user_id = ?";

$stmt = $conn->prepare($cart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Cart ID</th><th>Product Name</th><th>Quantity</th><th>Price</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['cart_id'] . "</td>";
        echo "<td>" . $row['product_name'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . $row['status_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No items in cart</p>";
}

// Check available products
echo "<h3>Available Products (Pickup and Delivery):</h3>";
$products_sql = "SELECT p.id, p.name, p.price, p.quantity, ps.name as status_name
                 FROM products p
                 LEFT JOIN product_statuses ps ON p.status_id = ps.id
                 WHERE p.deleted_at IS NULL 
                 AND ps.name IN ('Pickup', 'Delivery')
                 AND p.quantity > 0
                 ORDER BY ps.name, p.name";

$products_result = $conn->query($products_sql);

if ($products_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Product ID</th><th>Product Name</th><th>Price</th><th>Stock</th><th>Status</th></tr>";
    while ($row = $products_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['status_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No available products found!</p>";
}

// Check product statuses
echo "<h3>Product Statuses:</h3>";
$status_sql = "SELECT * FROM product_statuses ORDER BY id";
$status_result = $conn->query($status_sql);

if ($status_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th></tr>";
    while ($row = $status_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No product statuses found!</p>";
}

$conn->close();
?> 