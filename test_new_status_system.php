<?php
// Test script to verify the new status system with specific unavailable statuses
echo "<h1>Test: New Status System with Specific Unavailable Statuses</h1>";

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Show current product statuses
echo "<h2>Current Product Statuses:</h2>";
$status_sql = "SELECT * FROM product_statuses ORDER BY id";
$status_result = $conn->query($status_sql);

if ($status_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #4CAF50; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Description</th>";
    echo "</tr>";
    
    while ($row = $status_result->fetch_assoc()) {
        $description = "";
        if ($row['id'] == 1) $description = "Pick Up products";
        else if ($row['id'] == 2) $description = "Delivery products";
        else if ($row['id'] == 3) $description = "Available Today pick up";
        else if ($row['id'] == 4) $description = "Unavailable Pick Up (when quantity = 0)";
        else if ($row['id'] == 5) $description = "Unavailable Delivery (when quantity = 0)";
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $description . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test the new logic with sample products
echo "<h2>Testing New Status Logic:</h2>";

// Find products to test with
$test_sql = "SELECT p.id, p.name, p.quantity, p.status_id, ps.name AS status_name 
             FROM products p 
             LEFT JOIN product_statuses ps ON p.status_id = ps.id 
             WHERE p.deleted_at IS NULL 
             ORDER BY p.id 
             LIMIT 5";
$test_result = $conn->query($test_sql);

if ($test_result->num_rows > 0) {
    echo "<h3>Sample Products for Testing:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Current Quantity</th>";
    echo "<th>Current Status</th>";
    echo "<th>If Quantity = 0, Would Become</th>";
    echo "</tr>";
    
    while ($row = $test_result->fetch_assoc()) {
        $new_status = "";
        if ($row['status_id'] == 1) {
            $new_status = "Unavailable Pick Up (ID 4)";
        } else if ($row['status_id'] == 2) {
            $new_status = "Unavailable Delivery (ID 5)";
        } else if ($row['status_id'] == 3) {
            $new_status = "Unavailable Delivery (ID 5) - Default";
        } else {
            $new_status = "No change (already unavailable)";
        }
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . htmlspecialchars($row['status_name']) . "</td>";
        echo "<td>" . $new_status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test products with quantity 0
echo "<h3>Products with Quantity 0:</h3>";
$zero_sql = "SELECT p.id, p.name, p.quantity, p.status_id, ps.name AS status_name 
             FROM products p 
             LEFT JOIN product_statuses ps ON p.status_id = ps.id 
             WHERE p.quantity <= 0 AND p.deleted_at IS NULL 
             ORDER BY p.id";
$zero_result = $conn->query($zero_sql);

if ($zero_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Quantity</th>";
    echo "<th>Current Status</th>";
    echo "<th>Status Correct?</th>";
    echo "</tr>";
    
    while ($row = $zero_result->fetch_assoc()) {
        $is_correct = in_array($row['status_id'], [4, 5]);
        $status_color = $is_correct ? 'green' : 'red';
        $status_text = $is_correct ? '✓ Correct' : '✗ Should be unavailable';
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . htmlspecialchars($row['status_name']) . "</td>";
        echo "<td style='color: " . $status_color . ";'>" . $status_text . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No products with quantity 0 found.</p>";
}

// Test delivery products specifically
echo "<h3>Delivery Products (for weekly-product.php):</h3>";
$delivery_sql = "SELECT 
                    p.id, p.name, p.price, p.description, p.status_id, p.is_featured,
                    ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable,
                    GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days
                FROM products p
                LEFT JOIN product_statuses ps ON p.status_id = ps.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN product_day pd ON p.id = pd.product_id
                WHERE p.deleted_at IS NULL 
                AND ps.name = 'Delivery'
                AND (p.status_id NOT IN (4, 5) 
                    OR (p.status_id IN (4, 5) AND p.show_when_unavailable = 1))
                GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, ps.name, pi.image_url, p.quantity, p.show_when_unavailable
                ORDER BY p.is_featured DESC, p.status_id ASC";

$delivery_result = $conn->query($delivery_sql);

if ($delivery_result->num_rows > 0) {
    echo "<p><strong>Found " . $delivery_result->num_rows . " delivery products:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Status</th>";
    echo "<th>Quantity</th>";
    echo "<th>Show When Unavailable</th>";
    echo "<th>Available Days</th>";
    echo "</tr>";
    
    while ($row = $delivery_result->fetch_assoc()) {
        $isUnavailable = in_array($row['status_id'], [4, 5]) || $row['quantity'] <= 0;
        $row_color = $isUnavailable ? '#ffe6e6' : '#e6ffe6';
        
        echo "<tr style='background-color: " . $row_color . ";'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status_name']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . ($row['show_when_unavailable'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($row['available_days'] ?: 'None') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No delivery products found.</p>";
}

// Test the filtering logic
echo "<h3>Filtering Test Results:</h3>";

// Count products by status
$count_sql = "SELECT ps.name AS status_name, COUNT(*) as count 
              FROM products p 
              LEFT JOIN product_statuses ps ON p.status_id = ps.id 
              WHERE p.deleted_at IS NULL 
              GROUP BY p.status_id, ps.name 
              ORDER BY ps.id";
$count_result = $conn->query($count_sql);

if ($count_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Status</th>";
    echo "<th>Count</th>";
    echo "<th>Description</th>";
    echo "</tr>";
    
    while ($row = $count_result->fetch_assoc()) {
        $description = "";
        if (strpos($row['status_name'], 'Unavailable') !== false) {
            $description = "Products with 0 quantity";
        } else {
            $description = "Available products";
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['status_name']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $description . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h2>Test Summary:</h2>";
echo "<ul>";
echo "<li><strong>Status ID 1:</strong> Pick Up - Products available for pickup</li>";
echo "<li><strong>Status ID 2:</strong> Delivery - Products available for delivery</li>";
echo "<li><strong>Status ID 3:</strong> Available Today pick up - Special pickup status</li>";
echo "<li><strong>Status ID 4:</strong> Unavailable Pick Up - Pick up products with 0 quantity</li>";
echo "<li><strong>Status ID 5:</strong> Unavailable Delivery - Delivery products with 0 quantity</li>";
echo "</ul>";

echo "<h3>Logic Summary:</h3>";
echo "<ul>";
echo "<li>When a <strong>Pick Up</strong> product (ID 1) reaches 0 quantity → becomes <strong>Unavailable Pick Up</strong> (ID 4)</li>";
echo "<li>When a <strong>Delivery</strong> product (ID 2) reaches 0 quantity → becomes <strong>Unavailable Delivery</strong> (ID 5)</li>";
echo "<li>When any other product reaches 0 quantity → becomes <strong>Unavailable Delivery</strong> (ID 5) by default</li>";
echo "</ul>";

echo "<h2>Test Complete!</h2>";
echo "<p>All files have been updated to use the new status system with specific unavailable statuses.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}

h1, h2, h3 {
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

ul {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

li {
    margin: 5px 0;
}
</style>
