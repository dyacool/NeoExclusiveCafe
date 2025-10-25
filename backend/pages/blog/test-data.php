<?php
// Test script to check blog post data
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    die("Access denied");
}

// Include database configuration
require_once __DIR__ . "/../../../config/database-config.php";

// Get database connection
$conn = getDatabaseConnection();

echo "<h2>Blog Posts Data Test</h2>";

// Get all blog posts
$sql = "SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Title</th><th>Description (first 100 chars)</th><th>Image Path</th><th>Author</th><th>Created At</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id'] ?? $row['adblog_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['title'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['description'] ?? 'N/A', 0, 100)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['image_path'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['author'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No blog posts found.</p>";
}

// Test specific post retrieval
if (isset($_GET['test_id'])) {
    $test_id = intval($_GET['test_id']);
    echo "<h3>Testing specific post ID: $test_id</h3>";
    
    $sql = "SELECT * FROM blog_posts WHERE adblog_id = ? OR id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $test_id, $test_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
        
        echo "<h4>JSON Output:</h4>";
        echo "<pre>" . json_encode($row, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>Post with ID $test_id not found.</p>";
    }
}

mysqli_close($conn);
?>

<style>
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; }
</style>

<p><strong>Instructions:</strong> Add <code>?test_id=X</code> to the URL (replace X with a post ID) to test specific post retrieval.</p>