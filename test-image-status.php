<?php
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "<h2>Image Migration Status Check</h2>\n\n";

// Check product images
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN cloud_url IS NOT NULL AND cloud_url != '' THEN 1 ELSE 0 END) as migrated,
    SUM(CASE WHEN cloud_url IS NULL OR cloud_url = '' THEN 1 ELSE 0 END) as not_migrated
FROM product_images
WHERE is_removed = 0";

$result = $conn->query($sql);
$stats = $result->fetch_assoc();

echo "<h3>Product Images:</h3>\n";
echo "Total: {$stats['total']}<br>\n";
echo "Migrated to Cloudinary: {$stats['migrated']}<br>\n";
echo "Not Migrated: {$stats['not_migrated']}<br>\n\n";

// Show sample images
echo "<h3>Sample Product Images (first 5):</h3>\n";
$sample_sql = "SELECT id, product_id, image_url, cloud_url, is_primary 
               FROM product_images 
               WHERE is_removed = 0 
               LIMIT 5";
$sample_result = $conn->query($sample_sql);

echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>ID</th><th>Product ID</th><th>Local Path</th><th>Cloudinary URL</th><th>Primary</th></tr>\n";
while ($row = $sample_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['product_id']}</td>";
    echo "<td>" . htmlspecialchars($row['image_url']) . "</td>";
    echo "<td>" . htmlspecialchars($row['cloud_url'] ?: 'NOT MIGRATED') . "</td>";
    echo "<td>" . ($row['is_primary'] ? 'Yes' : 'No') . "</td>";
    echo "</tr>\n";
}
echo "</table>\n\n";

// Check carousel images
$carousel_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN cloud_url IS NOT NULL AND cloud_url != '' THEN 1 ELSE 0 END) as migrated,
    SUM(CASE WHEN cloud_url IS NULL OR cloud_url = '' THEN 1 ELSE 0 END) as not_migrated
FROM carousel_images";

$carousel_result = $conn->query($carousel_sql);
$carousel_stats = $carousel_result->fetch_assoc();

echo "<h3>Carousel Images:</h3>\n";
echo "Total: {$carousel_stats['total']}<br>\n";
echo "Migrated to Cloudinary: {$carousel_stats['migrated']}<br>\n";
echo "Not Migrated: {$carousel_stats['not_migrated']}<br>\n\n";

echo "<hr>\n";
echo "<h3>Next Steps:</h3>\n";
if ($stats['not_migrated'] > 0 || $carousel_stats['not_migrated'] > 0) {
    echo "<p style='color: red;'><strong>⚠️ You need to run the migration script!</strong></p>\n";
    echo "<p>Run this command from your project root:</p>\n";
    echo "<pre>php scripts/migrate-images-to-cloudinary.php</pre>\n";
} else {
    echo "<p style='color: green;'><strong>✅ All images are migrated to Cloudinary!</strong></p>\n";
}

$conn->close();
?>
