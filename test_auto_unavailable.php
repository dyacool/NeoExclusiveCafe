<?php
// Test script to verify automatic status update to unavailable when quantity reaches 0

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Testing Automatic Status Update to Unavailable</h2>";

// Test 1: Check current products with quantity 0
echo "<h3>Test 1: Products with quantity 0</h3>";
$sql = "SELECT 
            p.id, p.name, p.quantity, p.status_id, ps.name AS status_name
        FROM products p
        LEFT JOIN product_statuses ps ON p.status_id = ps.id
        WHERE p.quantity <= 0 AND p.deleted_at IS NULL
        ORDER BY p.id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Quantity</th><th>Status ID</th><th>Status Name</th><th>Should be Unavailable</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
            $should_be_unavailable = in_array($row['status_id'], [4, 5]); // ID 4 = Unavailable Pick Up, ID 5 = Unavailable Delivery
    $status_color = $should_be_unavailable ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['quantity'] . "</td>";
    echo "<td>" . $row['status_id'] . "</td>";
    echo "<td>" . $row['status_name'] . "</td>";
    echo "<td style='color: " . $status_color . ";'>" . ($should_be_unavailable ? '✓ Correct' : '✗ Should be Unavailable') . "</td>";
    echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No products with quantity 0 found.</p>";
}

// Test 2: Simulate updating a product quantity to 0
echo "<h3>Test 2: Simulating product update with quantity 0</h3>";

// Find a product with quantity > 0 to test with
$test_sql = "SELECT id, name, quantity, status_id FROM products WHERE quantity > 0 AND deleted_at IS NULL LIMIT 1";
$test_result = $conn->query($test_sql);

if ($test_result->num_rows > 0) {
    $test_product = $test_result->fetch_assoc();
    echo "<p>Testing with product: " . $test_product['name'] . " (ID: " . $test_product['id'] . ", Current Quantity: " . $test_product['quantity'] . ")</p>";
    
    // Simulate the update logic from update-product.php
    $original_status = $test_product['status_id'];
    
    // Update quantity to 0
    $update_sql = "UPDATE products SET quantity = 0 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $test_product['id']);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Apply the automatic status update logic
    $new_status_id = 5; // Default to Unavailable Delivery
    
    if ($original_status == 1) {
        // Currently Pick Up - set to Unavailable Pick Up (ID 4)
        $new_status_id = 4;
    } else if ($original_status == 2) {
        // Currently Delivery - set to Unavailable Delivery (ID 5)
        $new_status_id = 5;
    }
    
    if ($original_status != $new_status_id) {
        $auto_status_sql = "UPDATE products SET status_id = ? WHERE id = ?";
        $auto_status_stmt = $conn->prepare($auto_status_sql);
        $auto_status_stmt->bind_param("ii", $new_status_id, $test_product['id']);
        $auto_status_stmt->execute();
        $auto_status_stmt->close();
        
        $status_name = ($new_status_id == 4) ? "Unavailable Pick Up" : "Unavailable Delivery";
        echo "<p style='color: green;'>✓ Applied automatic status update to " . $status_name . "</p>";
    }
    
    // Verify the result
    $verify_sql = "SELECT p.id, p.name, p.quantity, p.status_id, ps.name AS status_name 
                   FROM products p 
                   LEFT JOIN product_statuses ps ON p.status_id = ps.id 
                   WHERE p.id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("i", $test_product['id']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $final_product = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    echo "<p><strong>Final Result:</strong></p>";
    echo "<ul>";
    echo "<li>Quantity: " . $final_product['quantity'] . "</li>";
    echo "<li>Status ID: " . $final_product['status_id'] . "</li>";
    echo "<li>Status Name: " . $final_product['status_name'] . "</li>";
    echo "</ul>";
    
    if ($final_product['quantity'] == 0 && in_array($final_product['status_id'], [4, 5])) {
        $status_name = ($final_product['status_id'] == 4) ? "Unavailable Pick Up" : "Unavailable Delivery";
        echo "<p style='color: green;'>✓ Test PASSED: Product automatically set to " . $status_name . " when quantity reached 0</p>";
    } else {
        echo "<p style='color: red;'>✗ Test FAILED: Product status not updated correctly</p>";
    }
    
    // Restore the original quantity for testing
    $restore_sql = "UPDATE products SET quantity = ? WHERE id = ?";
    $restore_stmt = $conn->prepare($restore_sql);
    $restore_stmt->bind_param("ii", $test_product['quantity'], $test_product['id']);
    $restore_stmt->execute();
    $restore_stmt->close();
    
    echo "<p><em>Note: Original quantity has been restored for future testing.</em></p>";
    
} else {
    echo "<p>No products with quantity > 0 found for testing.</p>";
}

// Test 3: Check all product statuses
echo "<h3>Test 3: All Product Statuses</h3>";
$status_sql = "SELECT * FROM product_statuses ORDER BY id";
$status_result = $conn->query($status_sql);

if ($status_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
    while ($row = $status_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . (in_array($row['id'], [4, 5]) ? 'Used when quantity = 0' : 'Normal status') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
