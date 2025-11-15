<?php
/**
 * Add discount_amount and shipping_fee columns to orders table
 * This script adds the missing columns to store coupon discounts and delivery fees
 */

require_once 'backend/pages/admin-includes/database.php';

echo "<h1>Add discount_amount and shipping_fee Columns to Orders Table</h1>";

try {
    $columns_added = [];
    
    // Check if discount_amount column already exists
    $check_discount_sql = "SHOW COLUMNS FROM orders LIKE 'discount_amount'";
    $discount_result = mysqli_query($conn, $check_discount_sql);
    
    if (mysqli_num_rows($discount_result) > 0) {
        echo "<p style='color: orange;'>✓ Column 'discount_amount' already exists in orders table.</p>";
    } else {
        // Add the discount_amount column
        $alter_discount_sql = "ALTER TABLE orders 
                               ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 
                               AFTER total_amount";
        
        if (mysqli_query($conn, $alter_discount_sql)) {
            echo "<p style='color: green;'>✓ Successfully added 'discount_amount' column to orders table!</p>";
            $columns_added[] = 'discount_amount';
        } else {
            throw new Exception("Failed to add discount_amount column: " . mysqli_error($conn));
        }
    }
    
    // Check if shipping_fee column already exists
    $check_shipping_sql = "SHOW COLUMNS FROM orders LIKE 'shipping_fee'";
    $shipping_result = mysqli_query($conn, $check_shipping_sql);
    
    if (mysqli_num_rows($shipping_result) > 0) {
        echo "<p style='color: orange;'>✓ Column 'shipping_fee' already exists in orders table.</p>";
    } else {
        // Add the shipping_fee column
        $alter_shipping_sql = "ALTER TABLE orders 
                               ADD COLUMN shipping_fee DECIMAL(10,2) DEFAULT 0.00 
                               AFTER discount_amount";
        
        if (mysqli_query($conn, $alter_shipping_sql)) {
            echo "<p style='color: green;'>✓ Successfully added 'shipping_fee' column to orders table!</p>";
            $columns_added[] = 'shipping_fee';
        } else {
            throw new Exception("Failed to add shipping_fee column: " . mysqli_error($conn));
        }
    }
    
    // Display updated table structure
    echo "<h2>Updated Orders Table Structure:</h2>";
    
    if (!empty($columns_added)) {
        echo "<p style='color: green; font-weight: bold;'>✓ Added " . count($columns_added) . " new column(s): " . implode(', ', $columns_added) . "</p>";
    } else {
        echo "<p style='color: blue;'>All required columns already exist.</p>";
    }
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    $structure_sql = "SHOW COLUMNS FROM orders";
    $structure_result = mysqli_query($conn, $structure_sql);
    
    while ($row = mysqli_fetch_assoc($structure_result)) {
        $highlight = (in_array($row['Field'], ['discount_amount', 'shipping_fee'])) ? " style='background-color: #90EE90;'" : "";
        echo "<tr{$highlight}>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>What's Next?</h2>";
    echo "<p>✅ The discount_amount and shipping_fee columns have been added!</p>";
    echo "<p><strong>Updated Files:</strong></p>";
    echo "<ul>";
    echo "<li>✅ frontend/pages/cart/payment-return.php - Now saves both discount_amount and shipping_fee</li>";
    echo "</ul>";
    echo "<p><strong>Test the changes:</strong></p>";
    echo "<ol>";
    echo "<li>Go to checkout and apply a coupon</li>";
    echo "<li>Select delivery method to add shipping fee</li>";
    echo "<li>Complete the order</li>";
    echo "<li>Check the orders table - both discount_amount and shipping_fee should be saved!</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

mysqli_close($conn);
?>
