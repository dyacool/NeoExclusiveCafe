<?php
/**
 * Quick Cart Test - Verify cart data before deploying new cart.php
 */
session_start();
require_once 'backend/pages/admin-includes/database.php';

if (!isset($_SESSION["user_id"])) {
    die("Please login first: <a href='frontend/login/user/login-signup.php'>Login</a>");
}

$user_id = $_SESSION['user_id'];

echo "<h1>Cart Test Results</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
    th { background: #4CAF50; color: white; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    .badge-1 { background: #4CAF50; color: white; }
    .badge-2 { background: #2196F3; color: white; }
    .badge-3 { background: #9C27B0; color: white; }
    .badge-4 { background: #FF9800; color: white; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
</style>";

// Test 1: Pre-Order Items
echo "<div class='section'>";
echo "<h2>Test 1: Pre-Order Items (cart table)</h2>";

$preorder_query = "
    SELECT c.id AS cart_id, c.quantity, c.price, c.product_id,
           p.name AS product_name, p.status_id,
           ps.name as status_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_statuses ps ON p.status_id = ps.id
    WHERE c.user_id = ? AND p.deleted_at IS NULL
    ORDER BY p.name ASC
";
$stmt = $conn->prepare($preorder_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p class='success'>✓ Found " . $result->num_rows . " pre-order items</p>";
    echo "<table>";
    echo "<tr><th>Cart ID</th><th>Product</th><th>Status ID</th><th>Status Name</th><th>Quantity</th><th>Price</th><th>Badge</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $badge_class = "badge badge-" . $row['status_id'];
        $badge_text = '';
        if ($row['status_id'] == 1) $badge_text = 'Pick Up Only!';
        elseif ($row['status_id'] == 2) $badge_text = 'Delivery Only!';
        elseif ($row['status_id'] == 3) $badge_text = 'Pick Up or Delivery';
        
        echo "<tr>";
        echo "<td>{$row['cart_id']}</td>";
        echo "<td>{$row['product_name']}</td>";
        echo "<td>{$row['status_id']}</td>";
        echo "<td>{$row['status_name']}</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td><span class='$badge_class'>$badge_text</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ No pre-order items found</p>";
}
$stmt->close();
echo "</div>";

// Test 2: Same Day Order Items
echo "<div class='section'>";
echo "<h2>Test 2: Same Day Order Items (availtoday_cart table)</h2>";

$sameday_query = "
    SELECT c.id AS cart_id, c.quantity, c.product_id,
           p.name AS product_name, p.price, p.status_id,
           ps.name as status_name
    FROM availtoday_cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_statuses ps ON p.status_id = ps.id
    WHERE c.user_id = ? AND p.deleted_at IS NULL
    ORDER BY p.name ASC
";
$stmt = $conn->prepare($sameday_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p class='success'>✓ Found " . $result->num_rows . " same-day items</p>";
    echo "<table>";
    echo "<tr><th>Cart ID</th><th>Product</th><th>Status ID</th><th>Status Name</th><th>Quantity</th><th>Price</th><th>Badge</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $badge_class = "badge badge-" . $row['status_id'];
        $badge_text = '';
        if ($row['status_id'] == 1) $badge_text = 'Pick Up Only!';
        elseif ($row['status_id'] == 2) $badge_text = 'Delivery Only!';
        elseif ($row['status_id'] == 3) $badge_text = 'Pick Up or Delivery';
        elseif ($row['status_id'] == 4) $badge_text = 'Same Day Order';
        
        echo "<tr>";
        echo "<td>{$row['cart_id']}</td>";
        echo "<td>{$row['product_name']}</td>";
        echo "<td>{$row['status_id']}</td>";
        echo "<td>{$row['status_name']}</td>";
        echo "<td>{$row['quantity']}</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td><span class='$badge_class'>$badge_text</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ No same-day items found</p>";
}
$stmt->close();
echo "</div>";

// Test 3: Shipping Method Conflicts
echo "<div class='section'>";
echo "<h2>Test 3: Shipping Method Conflict Check</h2>";

$conflict_query = "
    SELECT 
        SUM(CASE WHEN p.status_id = 1 THEN 1 ELSE 0 END) as pickup_only,
        SUM(CASE WHEN p.status_id = 2 THEN 1 ELSE 0 END) as delivery_only,
        SUM(CASE WHEN p.status_id = 3 THEN 1 ELSE 0 END) as both
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.deleted_at IS NULL
";
$stmt = $conn->prepare($conflict_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$counts = $result->fetch_assoc();
$stmt->close();

echo "<p>Pick Up Only (Status 1): {$counts['pickup_only']} items</p>";
echo "<p>Delivery Only (Status 2): {$counts['delivery_only']} items</p>";
echo "<p>Pick Up or Delivery (Status 3): {$counts['both']} items</p>";

if ($counts['pickup_only'] > 0 && $counts['delivery_only'] > 0) {
    echo "<p class='error'>⚠️ WARNING: Cart has both Pick Up Only and Delivery Only items! This will cause conflicts.</p>";
} else {
    echo "<p class='success'>✓ No shipping method conflicts</p>";
}
echo "</div>";

// Test 4: Files Check
echo "<div class='section'>";
echo "<h2>Test 4: Required Files Check</h2>";

$files = [
    'frontend/pages/cart/cart-new.php' => 'New cart file',
    'frontend/pages/cart/update-cart.php' => 'Update pre-order quantity',
    'frontend/pages/cart/update-cart-quantity-sameday.php' => 'Update same-day quantity',
    'frontend/pages/cart/remove-from-cart.php' => 'Remove pre-order item',
    'frontend/pages/cart/remove-from-cart-sameday.php' => 'Remove same-day item',
    'frontend/pages/cart/checkout.php' => 'Pre-order checkout',
    'frontend/pages/cart/availtoday-checkout.php' => 'Same-day checkout'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $description: $file</p>";
    } else {
        echo "<p class='error'>✗ MISSING: $description: $file</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>Ready to Test?</h2>";
echo "<p><a href='frontend/pages/cart/cart-new.php' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Open New Cart</a></p>";
echo "<p style='color: #666; font-size: 14px;'>If everything looks good above, click to test the new cart!</p>";
echo "</div>";

$conn->close();
?>
