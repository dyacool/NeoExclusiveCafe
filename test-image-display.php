<?php
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "<h2>Image Display Test</h2>";

// Get one product image
$sql = "SELECT id, product_id, image_url, cloud_url FROM product_images WHERE is_removed = 0 LIMIT 1";
$result = $conn->query($sql);
$image = $result->fetch_assoc();

echo "<h3>Database Record:</h3>";
echo "<pre>";
print_r($image);
echo "</pre>";

echo "<h3>What the code will use (COALESCE logic):</h3>";
$display_url = !empty($image['cloud_url']) ? $image['cloud_url'] : $image['image_url'];
echo "<p><strong>URL to display:</strong> " . htmlspecialchars($display_url) . "</p>";

// Check if it's a full URL or local path
if (strpos($display_url, 'http://') === 0 || strpos($display_url, 'https://') === 0) {
    $final_url = $display_url;
    echo "<p><strong>Type:</strong> Full Cloudinary URL</p>";
} else {
    $final_url = '/assets/' . $display_url;
    echo "<p><strong>Type:</strong> Local path (will prepend /assets/)</p>";
}

echo "<h3>Final Image URL:</h3>";
echo "<p><code>" . htmlspecialchars($final_url) . "</code></p>";

echo "<h3>Image Preview:</h3>";
echo "<img src='" . htmlspecialchars($final_url) . "' style='max-width: 300px; border: 2px solid #ccc;' onerror=\"this.style.border='2px solid red'; this.alt='Failed to load';\">";

echo "<h3>Test Result:</h3>";
echo "<p>If you see the image above, Cloudinary is working! ✅</p>";
echo "<p>If you see a broken image, there's an issue. ❌</p>";

$conn->close();
?>
