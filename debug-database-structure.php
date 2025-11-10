<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database connection
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "<h2>Database Structure Debug</h2>";

// Show which database we're connected to
echo "<p><strong>Hostname:</strong> " . $host . "</p>";
echo "<p><strong>Database:</strong> " . $dbname . "</p>";

// Check if orders table exists
echo "<h3>Checking if 'orders' table exists...</h3>";
$tables = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($tables) > 0) {
    echo "<p style='color: green;'><strong>✓ Table 'orders' exists</strong></p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $result = mysqli_query($conn, "DESCRIBE `orders`");
    
    if ($result) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $has_status_column = false;
        while($row = mysqli_fetch_assoc($result)) {
            if ($row['Field'] === 'status') {
                $has_status_column = true;
                echo "<tr style='background-color: #90EE90;'>";
            } else {
                echo "<tr>";
            }
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($has_status_column) {
            echo "<p style='color: green;'><strong>✓ 'status' column found!</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>✗ 'status' column NOT found!</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>Error describing table: " . mysqli_error($conn) . "</p>";
    }
    
    // Try the problematic query
    echo "<h3>Testing the GROUP BY query:</h3>";
    $test_query = "SELECT `status`, COUNT(*) as count FROM `orders` GROUP BY `status`";
    echo "<p><code>" . htmlspecialchars($test_query) . "</code></p>";
    
    $test_result = @mysqli_query($conn, $test_query);
    if ($test_result) {
        echo "<p style='color: green;'><strong>✓ Query executed successfully!</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>Status</th><th>Count</th></tr>";
        while($row = mysqli_fetch_assoc($test_result)) {
            echo "<tr><td>" . htmlspecialchars($row['status']) . "</td><td>" . $row['count'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>✗ Query failed!</strong></p>";
        echo "<p><strong>Error:</strong> " . mysqli_error($conn) . "</p>";
        echo "<p><strong>Error Number:</strong> " . mysqli_errno($conn) . "</p>";
    }
    
    // Show sample data
    echo "<h3>Sample Orders (first 5):</h3>";
    $sample_query = "SELECT * FROM `orders` LIMIT 5";
    $sample_result = mysqli_query($conn, $sample_query);
    if ($sample_result && mysqli_num_rows($sample_result) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
        $first_row = true;
        while($row = mysqli_fetch_assoc($sample_result)) {
            if ($first_row) {
                echo "<tr style='background-color: #f0f0f0;'>";
                foreach ($row as $key => $value) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                $first_row = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No orders found or error: " . mysqli_error($conn) . "</p>";
    }
    
} else {
    echo "<p style='color: red;'><strong>✗ Table 'orders' does NOT exist</strong></p>";
    
    // Show all available tables
    echo "<h3>Available tables in this database:</h3>";
    $all_tables = mysqli_query($conn, "SHOW TABLES");
    echo "<ul>";
    while($table = mysqli_fetch_row($all_tables)) {
        echo "<li>" . htmlspecialchars($table[0]) . "</li>";
    }
    echo "</ul>";
}

mysqli_close($conn);
?>
