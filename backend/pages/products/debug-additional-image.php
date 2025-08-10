<?php
// Debug script for additional image path construction
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Additional Image Path Debug</h2>";

// Include database connection
require_once __DIR__ . '/../admin-includes/database.php';

echo "<h3>Database Connection Test</h3>";
if ($conn->ping()) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

echo "<h3>Additional Images in Database</h3>";

// Get all additional images
$stmt = $conn->prepare("SELECT id, product_id, image_url, is_primary FROM product_images WHERE is_primary = 0 LIMIT 5");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Product ID</th><th>Image URL</th><th>Is Primary</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['product_id'] . "</td>";
            echo "<td>" . $row['image_url'] . "</td>";
            echo "<td>" . ($row['is_primary'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
            
            // Test path construction for this image
            $basePath = dirname(dirname(dirname(__DIR__)));
            $constructedPath = $basePath . "/assets/" . $row['image_url'];
            
            echo "<tr><td colspan='4' style='background-color: #f0f0f0;'>";
            echo "<strong>Path Construction Test:</strong><br>";
            echo "Base Path: " . $basePath . "<br>";
            echo "Image URL from DB: " . $row['image_url'] . "<br>";
            echo "Constructed Path: " . $constructedPath . "<br>";
            echo "File Exists: " . (file_exists($constructedPath) ? 'YES' : 'NO') . "<br>";
            
            // Check if directory exists
            $imageDir = dirname($constructedPath);
            echo "Image Directory: " . $imageDir . "<br>";
            echo "Directory Exists: " . (is_dir($imageDir) ? 'YES' : 'NO') . "<br>";
            
            if (is_dir($imageDir)) {
                $files = scandir($imageDir);
                echo "Directory Contents: " . implode(", ", array_values(array_diff($files, ['.', '..']))) . "<br>";
            }
            
            echo "</td></tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ No additional images found in database</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Failed to query additional images</p>";
}

echo "<h3>Test Complete</h3>";
?>
