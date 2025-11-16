<?php
// Test script to check bulk orders data
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "<h2>Testing Bulk Orders Data</h2>";

// Test 1: Check if bulk_orders table has data
echo "<h3>1. All Bulk Orders with Purpose:</h3>";
$sql = "SELECT id, unique_order_id, purpose, status, created_at FROM bulk_orders LIMIT 10";
$result = mysqli_query($conn, $sql);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Order ID</th><th>Purpose</th><th>Status</th><th>Created At</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['unique_order_id']}</td>";
    echo "<td>{$row['purpose']}</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test 2: Check unique purposes
echo "<h3>2. Unique Purposes in Database:</h3>";
$sql = "SELECT DISTINCT purpose FROM bulk_orders WHERE purpose IS NOT NULL AND purpose != ''";
$result = mysqli_query($conn, $sql);
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>'{$row['purpose']}' (length: " . strlen($row['purpose']) . ")</li>";
}
echo "</ul>";

// Test 3: Check bulk_order_items
echo "<h3>3. Sample Bulk Order Items:</h3>";
$sql = "SELECT boi.*, bo.unique_order_id, bo.purpose 
        FROM bulk_order_items boi 
        INNER JOIN bulk_orders bo ON boi.bulk_order_id = bo.id 
        LIMIT 10";
$result = mysqli_query($conn, $sql);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Order ID</th><th>Purpose</th><th>Product Name</th><th>Quantity</th><th>Price</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['unique_order_id']}</td>";
    echo "<td>{$row['purpose']}</td>";
    echo "<td>{$row['product_name']}</td>";
    echo "<td>{$row['quantity']}</td>";
    echo "<td>{$row['price']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test 4: Test the actual query with different date ranges
echo "<h3>4. Testing Query with Different Conditions:</h3>";

$categories = ['Corporate Event', 'Wedding', 'Birthday Party', 'Business Supply', 'Others'];

foreach ($categories as $category) {
    echo "<h4>Category: $category</h4>";
    
    // Test without date filter
    $sql = "SELECT 
                boi.product_name,
                SUM(boi.quantity) as total_quantity,
                SUM(boi.quantity * boi.price) as total_revenue,
                COUNT(*) as order_count
            FROM bulk_order_items boi
            INNER JOIN bulk_orders bo ON boi.bulk_order_id = bo.id
            WHERE LOWER(TRIM(bo.purpose)) = LOWER(?)
            GROUP BY boi.product_name
            ORDER BY total_quantity DESC
            LIMIT 3";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $category);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Product</th><th>Total Qty</th><th>Total Revenue</th><th>Orders</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['product_name']}</td>";
            echo "<td>{$row['total_quantity']}</td>";
            echo "<td>₱" . number_format($row['total_revenue'], 2) . "</td>";
            echo "<td>{$row['order_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No data found for this category</p>";
    }
}

echo "<h3>5. Test with Status Filter:</h3>";
foreach ($categories as $category) {
    $sql = "SELECT COUNT(*) as count
            FROM bulk_orders bo
            WHERE LOWER(TRIM(bo.purpose)) = LOWER(?)
            AND bo.status IN ('approved', 'payment_received', 'ready_for_delivery', 'completed')";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $category);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    echo "<p>$category: {$row['count']} orders</p>";
}
?>
