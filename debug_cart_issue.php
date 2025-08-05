<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Cart Debug - Step by Step</h2>";

// Step 1: Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo "<p style='color: red; font-weight: bold;'>❌ ISSUE FOUND: User not logged in!</p>";
    echo "<p>You need to log in first to use the cart functionality.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p style='color: green;'>✅ User logged in with ID: $user_id</p>";

// Step 2: Check if there are any products available
echo "<h3>Step 2: Checking Available Products</h3>";
$products_sql = "SELECT p.id, p.name, p.price, p.quantity, ps.name as status_name
                 FROM products p
                 LEFT JOIN product_statuses ps ON p.status_id = ps.id
                 WHERE p.deleted_at IS NULL 
                 AND ps.name IN ('Pickup', 'Delivery')
                 AND p.quantity > 0
                 ORDER BY ps.name, p.name
                 LIMIT 5";

$products_result = $conn->query($products_sql);

if ($products_result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found " . $products_result->num_rows . " available products</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Status</th></tr>";
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
    echo "<p style='color: red;'>❌ No available products found!</p>";
    echo "<p>This means either:</p>";
    echo "<ul>";
    echo "<li>All products have 0 stock</li>";
    echo "<li>All products have 'Unavailable' status</li>";
    echo "<li>No products exist in the database</li>";
    echo "</ul>";
}

// Step 3: Check current cart items
echo "<h3>Step 3: Current Cart Items</h3>";
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
    echo "<p style='color: green;'>✅ Found " . $result->num_rows . " items in cart</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
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
    echo "<p style='color: orange;'>⚠️ No items in cart</p>";
}

// Step 4: Test add-to-cart functionality
echo "<h3>Step 4: Testing Add-to-Cart</h3>";
echo "<p>Let's test adding a product to cart manually:</p>";

// Get first available product
$test_product_sql = "SELECT p.id, p.name, p.price, p.quantity, ps.name as status_name
                     FROM products p
                     LEFT JOIN product_statuses ps ON p.status_id = ps.id
                     WHERE p.deleted_at IS NULL 
                     AND ps.name IN ('Pickup', 'Delivery')
                     AND p.quantity > 0
                     LIMIT 1";

$test_result = $conn->query($test_product_sql);

if ($test_result->num_rows > 0) {
    $test_product = $test_result->fetch_assoc();
    echo "<p>Testing with product: <strong>" . $test_product['name'] . "</strong> (ID: " . $test_product['id'] . ")</p>";
    
    // Simulate adding to cart
    $test_product_id = $test_product['id'];
    $test_quantity = 1;
    
    // Check if product exists and is available
    $check_sql = "SELECT id, price, quantity FROM products WHERE id = ? AND status_id != 3 AND deleted_at IS NULL";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $test_product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo "<p style='color: red;'>❌ Product not available for cart addition</p>";
    } else {
        $product = $check_result->fetch_assoc();
        echo "<p style='color: green;'>✅ Product is available (Stock: " . $product['quantity'] . ")</p>";
        
        // Check if already in cart
        $cart_check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $cart_check_stmt = $conn->prepare($cart_check_sql);
        $cart_check_stmt->bind_param("ii", $user_id, $test_product_id);
        $cart_check_stmt->execute();
        $cart_check_result = $cart_check_stmt->get_result();
        
        if ($cart_check_result->num_rows > 0) {
            echo "<p style='color: orange;'>⚠️ Product already in cart</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Product not in cart yet - ready to add</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ No products available for testing</p>";
}

// Step 5: Check product statuses
echo "<h3>Step 5: Product Statuses</h3>";
$status_sql = "SELECT * FROM product_statuses ORDER BY id";
$status_result = $conn->query($status_sql);

if ($status_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Name</th></tr>";
    while ($row = $status_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No product statuses found!</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Make sure you're logged in to the website</li>";
echo "<li>Go to the products page</li>";
echo "<li>Open browser developer console (F12)</li>";
echo "<li>Try adding a product to cart</li>";
echo "<li>Check console for any error messages</li>";
echo "</ol>";

$conn->close();
?> 