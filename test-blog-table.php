<?php
// Test script to check blog_posts table structure
require_once __DIR__ . "/config/database-config.php";

try {
    $conn = getDatabaseConnection();
    
    // Check table structure
    $result = mysqli_query($conn, "DESCRIBE blog_posts");
    
    echo "<h3>Blog Posts Table Structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
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
    
    // Check recent posts
    $result = mysqli_query($conn, "SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 3");
    
    echo "<h3>Recent Posts:</h3>";
    echo "<table border='1'>";
    $first_row = true;
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($first_row) {
            echo "<tr>";
            foreach (array_keys($row) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            $first_row = false;
        }
        
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>