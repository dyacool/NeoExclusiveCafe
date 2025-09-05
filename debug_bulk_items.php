<?php
require_once "config/database.php";

echo "<h2>Bulk Order Items Table Diagnostic</h2>\n";

// Check table structure
$structure_query = mysqli_query($conn, "DESCRIBE bulk_order_items");
if ($structure_query) {
    echo "<h3>Table Structure:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    while ($row = mysqli_fetch_assoc($structure_query)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p style='color: red;'>Error getting table structure: " . mysqli_error($conn) . "</p>\n";
}

// Check sample data
$sample_query = mysqli_query($conn, "SELECT bulk_order_id, product_name, product_price, quantity, subtotal FROM bulk_order_items LIMIT 5");
if ($sample_query) {
    echo "<h3>Sample Data (first 5 rows with relevant fields):</h3>\n";
    if (mysqli_num_rows($sample_query) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Bulk Order ID</th><th>Product Name</th><th>Product Price</th><th>Quantity</th><th>Subtotal</th></tr>\n";
        while ($row = mysqli_fetch_assoc($sample_query)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['bulk_order_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>₱" . number_format($row['product_price'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
            echo "<td>₱" . number_format($row['subtotal'], 2) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No data found in bulk_order_items table</p>\n";
    }
} else {
    echo "<p style='color: red;'>Error getting sample data: " . mysqli_error($conn) . "</p>\n";
}

mysqli_close($conn);
?>
