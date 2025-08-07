<?php
// Test script to fetch all delivery products
echo "<h1>Test: Fetch All Delivery Products</h1>";

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all delivery products
$sql = "SELECT 
            p.id, p.name, p.price, p.description, p.status_id, p.is_featured,
            ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable,
            GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days
        FROM products p
        LEFT JOIN product_statuses ps ON p.status_id = ps.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_day pd ON p.id = pd.product_id
        WHERE p.deleted_at IS NULL 
        AND ps.name = 'Delivery'
        AND (p.status_id != 3 
            OR (p.status_id = 3 AND p.show_when_unavailable = 1))
        GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, ps.name, pi.image_url, p.quantity, p.show_when_unavailable
        ORDER BY p.is_featured DESC, p.status_id ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Found " . $result->num_rows . " delivery products:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Price</th>";
    echo "<th>Status</th>";
    echo "<th>Quantity</th>";
    echo "<th>Featured</th>";
    echo "<th>Show When Unavailable</th>";
    echo "<th>Available Days (Full)</th>";
    echo "<th>Available Days (Abbreviated)</th>";
    echo "<th>Image</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $isUnavailable = $row['status_id'] == 3 || $row['quantity'] <= 0;
        
        // Convert to abbreviated format
        $abbreviated_days = '';
        if (!empty($row['available_days']) && !$isUnavailable) {
            $abbreviated_days = str_replace(
                ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                ['S', 'M', 'T', 'W', 'Th', 'F', 'Sa'],
                $row['available_days']
            );
        }
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['status_name']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . ($row['is_featured'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($row['show_when_unavailable'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($row['available_days'] ?: 'None') . "</td>";
        echo "<td>" . htmlspecialchars($abbreviated_days ?: 'None') . "</td>";
        echo "<td>" . htmlspecialchars($row['image_url'] ?: 'No image') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Display summary statistics
    echo "<h3>Summary Statistics:</h3>";
    
    // Reset result pointer
    $result->data_seek(0);
    
    $total_products = 0;
    $featured_products = 0;
    $unavailable_products = 0;
    $available_products = 0;
    $products_with_days = 0;
    $products_without_days = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_products++;
        $isUnavailable = $row['status_id'] == 3 || $row['quantity'] <= 0;
        
        if ($row['is_featured']) {
            $featured_products++;
        }
        
        if ($isUnavailable) {
            $unavailable_products++;
        } else {
            $available_products++;
        }
        
        if (!empty($row['available_days'])) {
            $products_with_days++;
        } else {
            $products_without_days++;
        }
    }
    
    echo "<ul>";
    echo "<li><strong>Total Delivery Products:</strong> " . $total_products . "</li>";
    echo "<li><strong>Featured Products:</strong> " . $featured_products . "</li>";
    echo "<li><strong>Available Products:</strong> " . $available_products . "</li>";
    echo "<li><strong>Unavailable Products:</strong> " . $unavailable_products . "</li>";
    echo "<li><strong>Products with Available Days:</strong> " . $products_with_days . "</li>";
    echo "<li><strong>Products without Available Days:</strong> " . $products_without_days . "</li>";
    echo "</ul>";
    
} else {
    echo "<h2>No delivery products found.</h2>";
}

// Test the filtering logic
echo "<h2>Testing Filtering Logic:</h2>";

// Reset result pointer
$result->data_seek(0);

$day_counts = [
    'Sunday' => 0,
    'Monday' => 0,
    'Tuesday' => 0,
    'Wednesday' => 0,
    'Thursday' => 0,
    'Friday' => 0,
    'Saturday' => 0
];

$unavailable_count = 0;

while ($row = $result->fetch_assoc()) {
    $isUnavailable = $row['status_id'] == 3 || $row['quantity'] <= 0;
    
    if ($isUnavailable) {
        $unavailable_count++;
    } else if (!empty($row['available_days'])) {
        $days = explode(', ', $row['available_days']);
        foreach ($days as $day) {
            if (isset($day_counts[$day])) {
                $day_counts[$day]++;
            }
        }
    }
}

echo "<h3>Products Available by Day:</h3>";
echo "<ul>";
foreach ($day_counts as $day => $count) {
    $abbrev = str_replace(
        ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ['S', 'M', 'T', 'W', 'Th', 'F', 'Sa'],
        $day
    );
    echo "<li><strong>{$abbrev} ({$day}):</strong> {$count} products</li>";
}
echo "<li><strong>Unavailable:</strong> {$unavailable_count} products</li>";
echo "</ul>";

$conn->close();

echo "<h2>Test Complete!</h2>";
echo "<p>This test shows all delivery products and their filtering information.</p>";
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
    background-color: #4CAF50 !important;
    color: white;
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

tr:hover {
    background-color: #f0f0f0;
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
