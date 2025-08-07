<?php
// Script to fix existing products with quantity 0 but not set to unavailable status

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Fixing Products with Quantity 0</h2>";

// Find products with quantity 0 but not set to appropriate unavailable status
$sql = "SELECT 
            p.id, p.name, p.quantity, p.status_id, ps.name AS status_name
        FROM products p
        LEFT JOIN product_statuses ps ON p.status_id = ps.id
        WHERE p.quantity <= 0 AND p.status_id NOT IN (4, 5) AND p.deleted_at IS NULL
        ORDER BY p.id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h3>Found " . $result->num_rows . " products that need to be fixed:</h3>";
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Quantity</th><th>Current Status</th><th>Action</th></tr>";
    
    $fixed_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['status_name'] . "</td>";
        
        // Determine the appropriate unavailable status based on current status
        $new_status_id = 5; // Default to Unavailable Delivery
        
        if ($row['status_id'] == 1) {
            // Currently Pick Up - set to Unavailable Pick Up (ID 4)
            $new_status_id = 4;
        } else if ($row['status_id'] == 2) {
            // Currently Delivery - set to Unavailable Delivery (ID 5)
            $new_status_id = 5;
        }
        
        // Update the product status to appropriate unavailable status
        $update_sql = "UPDATE products SET status_id = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_status_id, $row['id']);
        
        if ($update_stmt->execute()) {
            $status_name = ($new_status_id == 4) ? "Unavailable Pick Up" : "Unavailable Delivery";
            echo "<td style='color: green;'>✓ Fixed - Set to " . $status_name . "</td>";
            $fixed_count++;
        } else {
            echo "<td style='color: red;'>✗ Failed to update</td>";
        }
        
        $update_stmt->close();
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green;'><strong>Successfully fixed " . $fixed_count . " products.</strong></p>";
    
} else {
    echo "<p style='color: green;'>✓ All products with quantity 0 are already set to unavailable status.</p>";
}

// Show summary of all products with quantity 0
echo "<h3>Summary of all products with quantity 0:</h3>";
$summary_sql = "SELECT 
                    p.id, p.name, p.quantity, p.status_id, ps.name AS status_name
                FROM products p
                LEFT JOIN product_statuses ps ON p.status_id = ps.id
                WHERE p.quantity <= 0 AND p.deleted_at IS NULL
                ORDER BY p.status_id, p.id";

$summary_result = $conn->query($summary_sql);

if ($summary_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Quantity</th><th>Status</th><th>Status</th></tr>";
    
    while ($row = $summary_result->fetch_assoc()) {
        $status_color = $row['status_id'] == 3 ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['status_id'] . "</td>";
        echo "<td style='color: " . $status_color . ";'>" . $row['status_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No products with quantity 0 found.</p>";
}

$conn->close();
?>
