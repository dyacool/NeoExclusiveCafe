<?php
// Debug page to check categories
require_once __DIR__ . '/user-includes/database.php';

echo "<h1>Categories Debug</h1>";

// Check if connection exists
if (!isset($conn)) {
    echo "<p style='color: red;'>Database connection not found!</p>";
    exit;
}

echo "<p style='color: green;'>Database connection OK</p>";

// Check if categories table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
if (mysqli_num_rows($table_check) == 0) {
    echo "<p style='color: red;'>Categories table does not exist!</p>";
    exit;
}

echo "<p style='color: green;'>Categories table exists</p>";

// Check table structure
echo "<h2>Table Structure:</h2>";
$structure = mysqli_query($conn, "DESCRIBE categories");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($structure)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check if slug column exists
$check_slug = mysqli_query($conn, "SHOW COLUMNS FROM categories LIKE 'slug'");
$has_slug = mysqli_num_rows($check_slug) > 0;
echo "<p>Has slug column: " . ($has_slug ? "YES" : "NO") . "</p>";

// Get all categories
echo "<h2>All Categories:</h2>";
$query = "SELECT * FROM categories ORDER BY display_order ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "<p style='color: red;'>Query error: " . mysqli_error($conn) . "</p>";
    exit;
}

echo "<p>Total categories: " . mysqli_num_rows($result) . "</p>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Display Order</th><th>Is Active</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>" . ($has_slug && isset($row['slug']) ? $row['slug'] : 'N/A') . "</td>";
    echo "<td>{$row['display_order']}</td>";
    echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the exact query used in navigation
echo "<h2>Active Categories (as used in navigation):</h2>";
if ($has_slug) {
    $nav_query = "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
} else {
    $nav_query = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
}

echo "<p>Query: <code>$nav_query</code></p>";

$nav_result = mysqli_query($conn, $nav_query);
if (!$nav_result) {
    echo "<p style='color: red;'>Query error: " . mysqli_error($conn) . "</p>";
} else {
    echo "<p>Results: " . mysqli_num_rows($nav_result) . " categories</p>";
    
    if (mysqli_num_rows($nav_result) > 0) {
        echo "<ul>";
        while ($cat = mysqli_fetch_assoc($nav_result)) {
            if ($has_slug && isset($cat['slug'])) {
                $url = '/frontend/pages/products/category.php?slug=' . urlencode($cat['slug']);
            } else {
                $url = '/frontend/pages/products/category.php?id=' . intval($cat['id']);
            }
            echo "<li><a href='" . htmlspecialchars($url) . "'>" . htmlspecialchars($cat['name']) . "</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>No active categories found!</p>";
    }
}

mysqli_close($conn);
?>
