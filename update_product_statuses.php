<?php
echo "<h1>Update Product Statuses</h1>";

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Show current statuses
echo "<h2>Current Product Statuses:</h2>";
$current_sql = "SELECT * FROM product_statuses ORDER BY id";
$current_result = $conn->query($current_sql);

if ($current_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "</tr>";
    
    while ($row = $current_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Update id 3 from "Unavailable" to "Available Today pick up"
echo "<h2>Updating Status ID 3...</h2>";
$update_sql = "UPDATE product_statuses SET name = 'Available Today pick up' WHERE id = 3";
if ($conn->query($update_sql) === TRUE) {
    echo "<p style='color: green;'>✓ Successfully updated status ID 3 to 'Available Today pick up'</p>";
} else {
    echo "<p style='color: red;'>✗ Error updating status ID 3: " . $conn->error . "</p>";
}

// Add new status with id 4: "Unavailable Pick Up"
echo "<h2>Adding Status ID 4...</h2>";
$insert_sql_4 = "INSERT INTO product_statuses (id, name) VALUES (4, 'Unavailable Pick Up')";
if ($conn->query($insert_sql_4) === TRUE) {
    echo "<p style='color: green;'>✓ Successfully added status ID 4: 'Unavailable Pick Up'</p>";
} else {
    echo "<p style='color: red;'>✗ Error adding status ID 4: " . $conn->error . "</p>";
}

// Add new status with id 5: "Unavailable Delivery"
echo "<h2>Adding Status ID 5...</h2>";
$insert_sql_5 = "INSERT INTO product_statuses (id, name) VALUES (5, 'Unavailable Delivery')";
if ($conn->query($insert_sql_5) === TRUE) {
    echo "<p style='color: green;'>✓ Successfully added status ID 5: 'Unavailable Delivery'</p>";
} else {
    echo "<p style='color: red;'>✗ Error adding status ID 5: " . $conn->error . "</p>";
}

// Show updated statuses
echo "<h2>Updated Product Statuses:</h2>";
$updated_sql = "SELECT * FROM product_statuses ORDER BY id";
$updated_result = $conn->query($updated_sql);

if ($updated_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #4CAF50; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "</tr>";
    
    while ($row = $updated_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check for any products that might be affected
echo "<h2>Products Currently Using Status ID 3 (Previously 'Unavailable'):</h2>";
$affected_sql = "SELECT p.id, p.name, p.status_id, ps.name as status_name 
                 FROM products p 
                 LEFT JOIN product_statuses ps ON p.status_id = ps.id 
                 WHERE p.status_id = 3";
$affected_result = $conn->query($affected_sql);

if ($affected_result->num_rows > 0) {
    echo "<p><strong>Found " . $affected_result->num_rows . " products using status ID 3:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Product ID</th>";
    echo "<th>Product Name</th>";
    echo "<th>Status ID</th>";
    echo "<th>Status Name</th>";
    echo "</tr>";
    
    while ($row = $affected_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $row['status_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['status_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No products are currently using status ID 3.</p>";
}

$conn->close();

echo "<h2>Update Complete!</h2>";
echo "<p>The product_statuses table has been updated with the new status definitions.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}

h1, h2 {
    color: #333;
}

table {
    background-color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th {
    padding: 10px;
    text-align: left;
}

td {
    padding: 8px;
    border: 1px solid #ddd;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

p {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
}
</style> 