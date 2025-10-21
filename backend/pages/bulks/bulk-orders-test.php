<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo "Admin not logged in. Current session: ";
    var_dump($_SESSION);
    echo "<br><a href='../login/admin/admin-login.php'>Login as Admin</a>";
    exit();
}

require_once __DIR__ . "/admin-includes/database.php";

echo "<h1>Bulk Orders Test Page</h1>";
echo "<p>Admin logged in successfully!</p>";
echo "<p>Database connection: " . (isset($conn) ? "Connected" : "Failed") . "</p>";

// Test if tables exist
$tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'bulk_orders'");
if (mysqli_num_rows($tables_check) > 0) {
    echo "<p>bulk_orders table exists</p>";
    
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM bulk_orders");
    $count = mysqli_fetch_assoc($count_query)['count'];
    echo "<p>Total bulk orders: $count</p>";
} else {
    echo "<p>bulk_orders table does not exist - will be created automatically</p>";
}

echo "<br><a href='bulk-order-lists.php'>Go to Bulk Orders List</a>";
echo "<br><a href='orders/order-list.php'>Go to Regular Orders List</a>";
?>
