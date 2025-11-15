<?php
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "<h2>Database Table Structure Check</h2>";

// Check which database we're connected to
$db_check = mysqli_query($conn, "SELECT DATABASE()");
$db_name = mysqli_fetch_row($db_check)[0];
echo "<p><strong>Connected to database:</strong> $db_name</p>";

// Check if orders table exists
$tables = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($tables) > 0) {
    echo "<p><strong>Table 'orders' exists:</strong> YES</p>";
    
    // Show all columns
    $result = mysqli_query($conn, 'DESCRIBE `orders`');
    
    if ($result) {
        echo "<h3>Columns in 'orders' table:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test the problematic query
        echo "<h3>Testing status query:</h3>";
        $test_query = "SELECT `status`, COUNT(*) as count FROM `orders` GROUP BY `status`";
        echo "<p><strong>Query:</strong> $test_query</p>";
        
        $test_result = mysqli_query($conn, $test_query);
        if ($test_result) {
            echo "<p style='color: green;'><strong>Query executed successfully!</strong></p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Status</th><th>Count</th></tr>";
            while($row = mysqli_fetch_assoc($test_result)) {
                echo "<tr><td>" . $row['status'] . "</td><td>" . $row['count'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'><strong>Query failed:</strong> " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>Error describing table:</strong> " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: red;'><strong>Table 'orders' exists:</strong> NO</p>";
    echo "<p>Available tables:</p>";
    $all_tables = mysqli_query($conn, "SHOW TABLES");
    echo "<ul>";
    while($table = mysqli_fetch_row($all_tables)) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
}

mysqli_close($conn);

