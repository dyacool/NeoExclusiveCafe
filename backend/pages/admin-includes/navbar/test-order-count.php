<?php
// Test script to create sample orders for testing the count badge
require_once __DIR__ . '/../../../../includes/session-manager.php';

// Check if user is admin
if (!SessionManager::isAdminLoggedIn()) {
    die("Admin access required");
}

require_once __DIR__ . '/../database.php';

echo "<h2>Order Count Badge Test</h2>";

// Check current order counts
$total_query = "SELECT COUNT(*) as total FROM orders";
$total_result = mysqli_query($conn, $total_query);
$total_count = mysqli_fetch_assoc($total_result)['total'];

$active_query = "SELECT COUNT(*) as active FROM orders WHERE status NOT IN ('Delivered', 'Picked-up')";
$active_result = mysqli_query($conn, $active_query);
$active_count = mysqli_fetch_assoc($active_result)['active'];

echo "<p><strong>Current Orders:</strong></p>";
echo "<ul>";
echo "<li>Total Orders: " . $total_count . "</li>";
echo "<li>Active Orders: " . $active_count . " (this number appears in the navbar badge)</li>";
echo "</ul>";

// Add test orders button
if (isset($_POST['add_test_orders'])) {
    // Create some test orders
    $test_orders = [
        ['John Doe', 'Pending', 150.00],
        ['Jane Smith', 'Preparing', 75.50],
        ['Mike Johnson', 'Ready for Pick-up', 45.25],
        ['Sarah Wilson', 'Pending', 200.00],
        ['Tom Brown', 'Out for Delivery', 85.75]
    ];
    
    foreach ($test_orders as $order) {
        $insert_query = "INSERT INTO orders (customer_name, status, total_amount, order_date) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssd", $order[0], $order[1], $order[2]);
        mysqli_stmt_execute($stmt);
    }
    
    echo "<p style='color: green;'>✅ Added 5 test orders!</p>";
    echo "<p><a href='navbar.php'>View Navbar with Order Count</a></p>";
    
    // Refresh counts
    $total_result = mysqli_query($conn, $total_query);
    $total_count = mysqli_fetch_assoc($total_result)['total'];
    
    $active_result = mysqli_query($conn, $active_query);
    $active_count = mysqli_fetch_assoc($active_result)['active'];
    
    echo "<p><strong>Updated Orders:</strong></p>";
    echo "<ul>";
    echo "<li>Total Orders: " . $total_count . "</li>";
    echo "<li>Active Orders: " . $active_count . " (navbar badge should show this number)</li>";
    echo "</ul>";
}

if (isset($_POST['clear_test_orders'])) {
    // Clear test orders
    $clear_query = "DELETE FROM orders WHERE customer_name IN ('John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'Tom Brown')";
    mysqli_query($conn, $clear_query);
    
    echo "<p style='color: orange;'>🗑️ Cleared test orders!</p>";
    
    // Refresh counts
    $total_result = mysqli_query($conn, $total_query);
    $total_count = mysqli_fetch_assoc($total_result)['total'];
    
    $active_result = mysqli_query($conn, $active_query);
    $active_count = mysqli_fetch_assoc($active_result)['active'];
    
    echo "<p><strong>Updated Orders:</strong></p>";
    echo "<ul>";
    echo "<li>Total Orders: " . $total_count . "</li>";
    echo "<li>Active Orders: " . $active_count . " (navbar badge should show this number)</li>";
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Count Test - NeoCafe Admin</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f9fafb;
        }
        
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .btn {
            background: #16a34a;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px 5px;
            font-weight: 500;
        }
        
        .btn:hover {
            background: #15803d;
        }
        
        .btn.danger {
            background: #dc2626;
        }
        
        .btn.danger:hover {
            background: #b91c1c;
        }
        
        .link-btn {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            font-weight: 500;
        }
        
        .link-btn:hover {
            background: #2563eb;
        }
        
        ul {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #16a34a;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Order Count Badge Test</h1>
        
        <p>This page helps you test the new order count badge functionality in the admin navbar.</p>
        
        <h3>Test Actions:</h3>
        <form method="POST" style="display: inline;">
            <button type="submit" name="add_test_orders" class="btn">Add 5 Test Orders</button>
        </form>
        
        <form method="POST" style="display: inline;">
            <button type="submit" name="clear_test_orders" class="btn danger">Clear Test Orders</button>
        </form>
        
        <br><br>
        
        <a href="../orders/order-list.php" class="link-btn">View Orders Page</a>
        <a href="../dashboard/dashboard.php" class="link-btn">View Dashboard with Navbar</a>
        
        <hr style="margin: 30px 0;">
        
        <h3>How It Works:</h3>
        <ol>
            <li><strong>Order Count Badge:</strong> Shows number of active orders (excluding 'Delivered' and 'Picked-up')</li>
            <li><strong>Badge Visibility:</strong> Only appears when there are active orders (count > 0)</li>
            <li><strong>Badge Styling:</strong> Green background with white text, responsive design</li>
            <li><strong>Bulk Orders:</strong> Also includes count badge for active bulk orders</li>
        </ol>
        
        <h3>Technical Details:</h3>
        <ul>
            <li>Count is calculated using: <code>WHERE status NOT IN ('Delivered', 'Picked-up')</code></li>
            <li>Badge CSS class: <code>.nav-count-badge</code></li>
            <li>Helper function: <code>getOrderCounts($conn)</code></li>
            <li>Mobile responsive with smaller font size</li>
        </ul>
    </div>
</body>
</html>