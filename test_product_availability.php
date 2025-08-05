<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Product Availability Test</h2>";

// Check all products with their status
$sql = "SELECT 
            p.id, p.name, p.price, p.description, p.status_id, p.is_featured,
            ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable 
        FROM products p
        LEFT JOIN product_statuses ps ON p.status_id = ps.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.deleted_at IS NULL 
        AND ps.name != 'Delivery'
        AND (p.status_id != 3 
            OR (p.status_id = 3 AND p.show_when_unavailable = 1))
        ORDER BY p.is_featured DESC, p.status_id ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Status ID</th><th>Status Name</th><th>Quantity</th><th>Is Unavailable</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $isUnavailable = $row['status_name'] == 'Unavailable' || $row['quantity'] <= 0;
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['status_id'] . "</td>";
        echo "<td>" . $row['status_name'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td style='color: " . ($isUnavailable ? 'red' : 'green') . ";'>" . ($isUnavailable ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No products found!</p>";
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
}

$conn->close();
?> 