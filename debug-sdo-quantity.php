<?php
session_start();
require_once 'backend/pages/admin-includes/database.php';

// Get product_id from URL or use a default
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$today = date('Y-m-d');

echo "<h1>SDO Quantity Debug Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background: #4CAF50; color: white; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .query { background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3; }
</style>";

if (!$product_id) {
    echo "<div class='section'>";
    echo "<h2>Select a Product to Debug</h2>";
    echo "<form method='GET'>";
    echo "<label>Product ID: <input type='number' name='product_id' required></label>";
    echo "<button type='submit'>Debug</button>";
    echo "</form>";
    
    // Show all products with status 4
    echo "<h3>Products with Status 4 (Same Day Order):</h3>";
    $query = "SELECT id, name, status_id, quantity, availtoday_status_id FROM products WHERE status_id = 4 AND deleted_at IS NULL";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Status ID</th><th>Quantity</th><th>Availtoday Status ID</th><th>Action</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['status_id']}</td>";
            echo "<td>{$row['quantity']}</td>";
            echo "<td>" . ($row['availtoday_status_id'] ?? 'NULL') . "</td>";
            echo "<td><a href='?product_id={$row['id']}'>Debug</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>No products with status 4 found!</p>";
    }
    echo "</div>";
    exit();
}

echo "<div class='section'>";
echo "<h2>Debugging Product ID: $product_id</h2>";
echo "<p class='info'>Today's Date: $today</p>";
echo "</div>";

// Step 1: Get product details
echo "<div class='section'>";
echo "<h3>Step 1: Product Details</h3>";
$query = "SELECT id, name, status_id, availtoday_status_id, quantity FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "<p class='error'>Product not found!</p>";
    exit();
}

echo "<table>";
echo "<tr><th>Field</th><th>Value</th></tr>";
foreach ($product as $key => $value) {
    echo "<tr><td>$key</td><td>" . ($value ?? 'NULL') . "</td></tr>";
}
echo "</table>";

$status_id = $product['status_id'];
$availtoday_status_id = $product['availtoday_status_id'];

echo "<div class='query'>";
echo "<strong>Query:</strong><br>";
echo "<pre>SELECT id, name, status_id, availtoday_status_id, quantity FROM products WHERE id = $product_id</pre>";
echo "</div>";
echo "</div>";

// Step 2: Determine scenario
echo "<div class='section'>";
echo "<h3>Step 2: Scenario Detection</h3>";

if (($status_id == 1 || $status_id == 2 || $status_id == 3) && $availtoday_status_id != null) {
    echo "<p class='success'>✓ Scenario 1: Status 1/2/3 WITH availtoday_status_id (Has both types)</p>";
    $scenario = 1;
} else if ($status_id == 4) {
    echo "<p class='success'>✓ Scenario 2: Status 4 ONLY (Same Day Order only)</p>";
    $scenario = 2;
} else {
    echo "<p class='success'>✓ Scenario 3: Status 1/2/3 WITHOUT availtoday_status_id (Pre-order only)</p>";
    $scenario = 3;
}
echo "</div>";

// Step 3: Check availability table
if ($scenario == 1) {
    echo "<div class='section'>";
    echo "<h3>Step 3: Check regular_products_today_dates</h3>";
    
    $query = "SELECT * FROM regular_products_today_dates WHERE product_id = ? AND available_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $product_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<div class='query'>";
    echo "<strong>Query:</strong><br>";
    echo "<pre>SELECT * FROM regular_products_today_dates WHERE product_id = $product_id AND available_date = '$today'</pre>";
    echo "</div>";
    
    if ($result->num_rows > 0) {
        echo "<p class='success'>✓ Product is available today!</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Product ID</th><th>Available Date</th><th>Availtoday Status ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ Product is NOT available today in regular_products_today_dates</p>";
    }
    $stmt->close();
    echo "</div>";
    
} else if ($scenario == 2) {
    echo "<div class='section'>";
    echo "<h3>Step 3: Check todays_products_dates</h3>";
    
    $query = "SELECT * FROM todays_products_dates WHERE product_id = ? AND available_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $product_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<div class='query'>";
    echo "<strong>Query:</strong><br>";
    echo "<pre>SELECT * FROM todays_products_dates WHERE product_id = $product_id AND available_date = '$today'</pre>";
    echo "</div>";
    
    if ($result->num_rows > 0) {
        echo "<p class='success'>✓ Product is available today!</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Product ID</th><th>Available Date</th><th>Availtoday Status ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ Product is NOT available today in todays_products_dates</p>";
        
        // Show all dates for this product
        echo "<h4>All dates for this product in todays_products_dates:</h4>";
        $all_query = "SELECT * FROM todays_products_dates WHERE product_id = ?";
        $all_stmt = $conn->prepare($all_query);
        $all_stmt->bind_param("i", $product_id);
        $all_stmt->execute();
        $all_result = $all_stmt->get_result();
        
        if ($all_result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Product ID</th><th>Available Date</th><th>Availtoday Status ID</th></tr>";
            while ($row = $all_result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . ($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>No dates found for this product at all!</p>";
        }
        $all_stmt->close();
    }
    $stmt->close();
    echo "</div>";
}

// Step 4: Check quantity_per_day_sdo
if ($scenario == 1 || $scenario == 2) {
    echo "<div class='section'>";
    echo "<h3>Step 4: Check quantity_per_day_sdo</h3>";
    
    $query = "SELECT * FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $product_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<div class='query'>";
    echo "<strong>Query:</strong><br>";
    echo "<pre>SELECT * FROM quantity_per_day_sdo WHERE product_id = $product_id AND date = '$today'</pre>";
    echo "</div>";
    
    if ($result->num_rows > 0) {
        echo "<p class='success'>✓ Quantity found for today!</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Date</th><th>Product ID</th><th>Quantity</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No quantity found for today in quantity_per_day_sdo</p>";
        
        // Show all quantities for this product
        echo "<h4>All quantities for this product in quantity_per_day_sdo:</h4>";
        $all_query = "SELECT * FROM quantity_per_day_sdo WHERE product_id = ?";
        $all_stmt = $conn->prepare($all_query);
        $all_stmt->bind_param("i", $product_id);
        $all_stmt->execute();
        $all_result = $all_stmt->get_result();
        
        if ($all_result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Date</th><th>Product ID</th><th>Quantity</th></tr>";
            while ($row = $all_result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . ($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>No quantities found for this product at all!</p>";
        }
        $all_stmt->close();
    }
    $stmt->close();
    echo "</div>";
}

// Step 5: Final result
echo "<div class='section'>";
echo "<h3>Step 5: Final Result</h3>";

if ($scenario == 3) {
    echo "<p class='success'>Quantity: {$product['quantity']} (from products table)</p>";
} else {
    // Simulate the actual API logic
    $final_quantity = 0;
    
    if ($scenario == 1) {
        $check = $conn->prepare("SELECT id FROM regular_products_today_dates WHERE product_id = ? AND available_date = ?");
        $check->bind_param("is", $product_id, $today);
        $check->execute();
        $check_result = $check->get_result();
        
        if ($check_result->num_rows > 0) {
            $qty = $conn->prepare("SELECT quantity FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?");
            $qty->bind_param("is", $product_id, $today);
            $qty->execute();
            $qty_result = $qty->get_result();
            if ($row = $qty_result->fetch_assoc()) {
                $final_quantity = $row['quantity'];
            }
            $qty->close();
        }
        $check->close();
    } else if ($scenario == 2) {
        $check = $conn->prepare("SELECT id FROM todays_products_dates WHERE product_id = ? AND available_date = ?");
        $check->bind_param("is", $product_id, $today);
        $check->execute();
        $check_result = $check->get_result();
        
        if ($check_result->num_rows > 0) {
            $qty = $conn->prepare("SELECT quantity FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?");
            $qty->bind_param("is", $product_id, $today);
            $qty->execute();
            $qty_result = $qty->get_result();
            if ($row = $qty_result->fetch_assoc()) {
                $final_quantity = $row['quantity'];
            }
            $qty->close();
        }
        $check->close();
    }
    
    echo "<p class='success'>Final Quantity: $final_quantity</p>";
}

echo "</div>";

$conn->close();
?>
