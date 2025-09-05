<?php
require_once "config/database.php";

echo "<h1>NeoCafe Bulk Order System Test</h1>\n";

// Test 1: Check if bulk_orders table exists with correct schema
echo "<h2>Test 1: Database Schema Check</h2>\n";
$table_check = mysqli_query($conn, "DESCRIBE bulk_orders");
if ($table_check) {
    echo "<p style='color: green;'>✓ bulk_orders table exists</p>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    while ($row = mysqli_fetch_assoc($table_check)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p style='color: red;'>✗ bulk_orders table does not exist or has errors</p>\n";
    echo "<p>Error: " . mysqli_error($conn) . "</p>\n";
}

// Test 2: Check if bulk_order_items table exists
echo "<h2>Test 2: Bulk Order Items Table Check</h2>\n";
$items_table_check = mysqli_query($conn, "DESCRIBE bulk_order_items");
if ($items_table_check) {
    echo "<p style='color: green;'>✓ bulk_order_items table exists</p>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    while ($row = mysqli_fetch_assoc($items_table_check)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p style='color: red;'>✗ bulk_order_items table does not exist or has errors</p>\n";
}

// Test 3: Test bulk order data retrieval
echo "<h2>Test 3: Sample Bulk Orders</h2>\n";
$bulk_orders = mysqli_query($conn, "SELECT * FROM bulk_orders ORDER BY created_at DESC LIMIT 5");
if ($bulk_orders && mysqli_num_rows($bulk_orders) > 0) {
    echo "<p style='color: green;'>✓ Found " . mysqli_num_rows($bulk_orders) . " bulk orders</p>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Customer Name</th><th>Contact</th><th>Order Type</th><th>Date Needed</th><th>Status</th><th>Total Amount</th></tr>\n";
    while ($order = mysqli_fetch_assoc($bulk_orders)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['contact']) . "</td>";
        echo "<td>" . htmlspecialchars($order['order_type']) . "</td>";
        echo "<td>" . htmlspecialchars($order['date_needed']) . "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "<td>₱" . number_format($order['total_amount'], 2) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p style='color: orange;'>⚠ No bulk orders found in database</p>\n";
}

// Test 4: Admin pages accessibility
echo "<h2>Test 4: Admin Pages Check</h2>\n";
$admin_pages = [
    'backend/pages/bulk-order-lists.php' => 'Bulk Order Lists',
    'backend/pages/bulk-order.php' => 'Bulk Order Details'
];

foreach ($admin_pages as $page => $name) {
    if (file_exists($page)) {
        echo "<p style='color: green;'>✓ $name page exists</p>\n";
    } else {
        echo "<p style='color: red;'>✗ $name page missing</p>\n";
    }
}

// Test 5: Frontend pages accessibility
echo "<h2>Test 5: Frontend Pages Check</h2>\n";
$frontend_pages = [
    'frontend/pages/bulk-form.php' => 'Bulk Order Form',
    'frontend/pages/profile/profile.php' => 'User Profile'
];

foreach ($frontend_pages as $page => $name) {
    if (file_exists($page)) {
        echo "<p style='color: green;'>✓ $name page exists</p>\n";
    } else {
        echo "<p style='color: red;'>✗ $name page missing</p>\n";
    }
}

mysqli_close($conn);

echo "<h2>Test Summary</h2>\n";
echo "<p>If all tests show green checkmarks, your bulk order system is properly configured!</p>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>Test the bulk order form at <a href='frontend/pages/bulk-form.php'>frontend/pages/bulk-form.php</a></li>\n";
echo "<li>Check admin order management at <a href='backend/pages/bulk-order-lists.php'>backend/pages/bulk-order-lists.php</a></li>\n";
echo "<li>View user profile bulk order history at <a href='frontend/pages/profile/profile.php'>frontend/pages/profile/profile.php</a></li>\n";
echo "</ul>\n";
?>
