<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Updating Product Statuses</h2>";

// First, let's see what statuses currently exist
echo "<h3>Current Product Statuses:</h3>";
$result = $conn->query("SELECT * FROM product_statuses");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No product statuses found.";
}

// Update the status names
echo "<h3>Updating Status Names...</h3>";

$update1 = $conn->query("UPDATE product_statuses SET name = 'Delivery' WHERE name = 'Bread of the Week'");
if ($update1) {
    echo "✓ Updated 'Bread of the Week' to 'Delivery'<br>";
} else {
    echo "✗ Error updating 'Bread of the Week': " . $conn->error . "<br>";
}

$update2 = $conn->query("UPDATE product_statuses SET name = 'Pickup' WHERE name = 'Available'");
if ($update2) {
    echo "✓ Updated 'Available' to 'Pickup'<br>";
} else {
    echo "✗ Error updating 'Available': " . $conn->error . "<br>";
}

// Verify the changes
echo "<h3>Updated Product Statuses:</h3>";
$result = $conn->query("SELECT * FROM product_statuses");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No product statuses found.";
}

echo "<h3>Summary:</h3>";
echo "• status_id = 1: Delivery (formerly Bread of the Week)<br>";
echo "• status_id = 2: Pickup (formerly Available)<br>";
echo "• status_id = 3: Unavailable (unchanged)<br>";

$conn->close();
echo "<p><strong>Product statuses have been updated successfully!</strong></p>";
?> 