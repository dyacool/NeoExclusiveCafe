<?php
require_once "config/database.php";

echo "<h1>Bulk Order Details Page Test</h1>\n";

// Test 1: Check if we have any bulk orders to test with
echo "<h2>Test 1: Available Bulk Orders</h2>\n";
$orders_query = mysqli_query($conn, "SELECT id, name, contact, order_type, status, created_at FROM bulk_orders ORDER BY created_at DESC LIMIT 10");

if ($orders_query && mysqli_num_rows($orders_query) > 0) {
    echo "<p style='color: green;'>✓ Found bulk orders for testing</p>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Customer</th><th>Contact</th><th>Type</th><th>Status</th><th>Created</th><th>Test Link</th></tr>\n";
    
    while ($order = mysqli_fetch_assoc($orders_query)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['contact']) . "</td>";
        echo "<td>" . htmlspecialchars($order['order_type']) . "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
        echo "<td><a href='frontend/pages/bulk/bulk-order-details.php?id=" . $order['id'] . "' target='_blank'>View Details</a></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p style='color: orange;'>⚠ No bulk orders found for testing</p>\n";
    echo "<p>Create a bulk order first using the <a href='frontend/pages/bulk-form.php'>bulk order form</a></p>\n";
}

// Test 2: Check bulk_order_items table
echo "<h2>Test 2: Bulk Order Items Check</h2>\n";
$items_query = mysqli_query($conn, "SELECT COUNT(*) as item_count FROM bulk_order_items");
if ($items_query) {
    $item_count = mysqli_fetch_assoc($items_query)['item_count'];
    echo "<p style='color: green;'>✓ Found $item_count items in bulk_order_items table</p>\n";
} else {
    echo "<p style='color: red;'>✗ Error checking bulk_order_items: " . mysqli_error($conn) . "</p>\n";
}

// Test 3: File accessibility
echo "<h2>Test 3: File Accessibility</h2>\n";
$files_to_check = [
    'frontend/pages/bulk/bulk-order-details.php' => 'Bulk Order Details Page',
    'frontend/pages/bulk/bulk-order-details.css' => 'Bulk Order Details CSS',
    'frontend/user-includes/navbar/customer-navigation.php' => 'Customer Navigation'
];

foreach ($files_to_check as $file => $name) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $name exists</p>\n";
    } else {
        echo "<p style='color: red;'>✗ $name missing</p>\n";
    }
}

mysqli_close($conn);

echo "<h2>Test Instructions</h2>\n";
echo "<ol>\n";
echo "<li>Click on any 'View Details' link above to test the bulk order details page</li>\n";
echo "<li>Verify that all order information displays correctly</li>\n";
echo "<li>Check that the order items table shows proper product details</li>\n";
echo "<li>If order status is 'approved', test the proof of payment upload functionality</li>\n";
echo "</ol>\n";
?>
