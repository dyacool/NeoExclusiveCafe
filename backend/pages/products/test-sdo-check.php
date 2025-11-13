<?php
require_once __DIR__ . "/../admin-includes/database.php";

echo "<h2>Testing SDO Quantity System</h2>";

// Check if quantity_per_day_sdo table exists and has data
$result = $conn->query("SELECT * FROM quantity_per_day_sdo ORDER BY id DESC LIMIT 10");

echo "<h3>Recent SDO Quantities:</h3>";
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>Date</th><th>Quantity</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No SDO quantities found in database!</p>";
}

// Check recent products with status_id 4
$products = $conn->query("SELECT id, name, status_id FROM products WHERE status_id = 4 OR availtoday_status_id IS NOT NULL ORDER BY id DESC LIMIT 5");

echo "<h3>Recent SDO Products:</h3>";
if ($products && $products->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Status ID</th></tr>";
    while ($row = $products->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['status_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No SDO products found</p>";
}

// Check todays_products_dates
$dates = $conn->query("SELECT * FROM todays_products_dates ORDER BY id DESC LIMIT 10");
echo "<h3>Recent Today's Product Dates:</h3>";
if ($dates && $dates->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>Date</th></tr>";
    while ($row = $dates->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['available_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No dates found</p>";
}

$conn->close();
?>
