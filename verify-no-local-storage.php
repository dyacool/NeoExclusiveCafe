<?php
/**
 * Verification Script: No Local File Storage
 * 
 * This script verifies that the system is no longer using local file storage
 * and all images are being served from Cloudinary.
 */

require_once __DIR__ . '/config/database-config.php';

echo "<h1>Local File Storage Verification</h1>";
echo "<p>Checking if the system is using local file storage...</p>";

// Check 1: Count products with local paths but no Cloudinary URLs
echo "<h2>1. Products with Local Paths Only</h2>";
$sql = "SELECT COUNT(*) as count FROM product_images WHERE image_url IS NOT NULL AND cloud_url IS NULL";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$local_only_count = $row['count'];

if ($local_only_count > 0) {
    echo "<p style='color: orange;'>⚠️ Found $local_only_count images with local paths but no Cloudinary URLs</p>";
    echo "<p>These images need to be migrated to Cloudinary.</p>";
    
    // Show details
    $detail_sql = "SELECT pi.id, pi.product_id, p.name, pi.image_url, pi.is_primary 
                   FROM product_images pi 
                   JOIN products p ON pi.product_id = p.id 
                   WHERE pi.image_url IS NOT NULL AND pi.cloud_url IS NULL 
                   LIMIT 10";
    $detail_result = $conn->query($detail_sql);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Image ID</th><th>Product ID</th><th>Product Name</th><th>Local Path</th><th>Type</th></tr>";
    while ($detail = $detail_result->fetch_assoc()) {
        $type = $detail['is_primary'] ? 'Primary' : 'Additional';
        echo "<tr>";
        echo "<td>{$detail['id']}</td>";
        echo "<td>{$detail['product_id']}</td>";
        echo "<td>{$detail['name']}</td>";
        echo "<td>{$detail['image_url']}</td>";
        echo "<td>$type</td>";
        echo "</tr>";
    }
    echo "</table>";
    if ($local_only_count > 10) {
        echo "<p><em>Showing first 10 of $local_only_count images</em></p>";
    }
} else {
    echo "<p style='color: green;'>✅ No images with local paths only</p>";
}

// Check 2: Count products with Cloudinary URLs
echo "<h2>2. Products with Cloudinary URLs</h2>";
$sql = "SELECT COUNT(*) as count FROM product_images WHERE cloud_url IS NOT NULL";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$cloudinary_count = $row['count'];

echo "<p style='color: green;'>✅ Found $cloudinary_count images with Cloudinary URLs</p>";

// Check 3: Verify no files in local directory
echo "<h2>3. Local Directory Check</h2>";
$local_dir = __DIR__ . '/assets/product-images';

if (is_dir($local_dir)) {
    $files = scandir($local_dir);
    $files = array_diff($files, ['.', '..', '.htaccess', '1_TEMP_IMAGES']);
    
    if (count($files) > 0) {
        echo "<p style='color: orange;'>⚠️ Found " . count($files) . " items in local directory</p>";
        echo "<p>These can be safely deleted after verifying all images are in Cloudinary.</p>";
        echo "<ul>";
        foreach (array_slice($files, 0, 10) as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
        if (count($files) > 10) {
            echo "<p><em>Showing first 10 of " . count($files) . " items</em></p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Local directory is clean (no product folders)</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Local directory does not exist</p>";
}

// Check 4: Verify temporary directory is clean
echo "<h2>4. Temporary Directory Check</h2>";
$temp_dir = __DIR__ . '/assets/product-images/1_TEMP_IMAGES';

if (is_dir($temp_dir)) {
    $temp_files = scandir($temp_dir);
    $temp_files = array_diff($temp_files, ['.', '..']);
    
    if (count($temp_files) > 0) {
        echo "<p style='color: orange;'>⚠️ Found " . count($temp_files) . " temporary files</p>";
        echo "<p>These should be cleaned up automatically. If they persist, they can be safely deleted.</p>";
    } else {
        echo "<p style='color: green;'>✅ Temporary directory is clean</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Temporary directory does not exist</p>";
}

// Check 5: Recent uploads verification
echo "<h2>5. Recent Uploads (Last 24 Hours)</h2>";
$sql = "SELECT pi.id, pi.product_id, p.name, pi.cloud_url, pi.cloud_public_id, pi.created_at 
        FROM product_images pi 
        JOIN products p ON pi.product_id = p.id 
        WHERE pi.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        ORDER BY pi.created_at DESC 
        LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Image ID</th><th>Product</th><th>Cloudinary URL</th><th>Public ID</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $has_cloudinary = !empty($row['cloud_url']) ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>$has_cloudinary " . (empty($row['cloud_url']) ? 'Missing' : 'Present') . "</td>";
        echo "<td>" . ($row['cloud_public_id'] ?? 'N/A') . "</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No uploads in the last 24 hours</p>";
}

// Summary
echo "<h2>Summary</h2>";
$total_images = $local_only_count + $cloudinary_count;
$cloudinary_percentage = $total_images > 0 ? round(($cloudinary_count / $total_images) * 100, 2) : 0;

echo "<ul>";
echo "<li><strong>Total Images:</strong> $total_images</li>";
echo "<li><strong>Cloudinary Images:</strong> $cloudinary_count ($cloudinary_percentage%)</li>";
echo "<li><strong>Local Only Images:</strong> $local_only_count</li>";
echo "</ul>";

if ($local_only_count == 0 && $cloudinary_count > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: All images are using Cloudinary!</p>";
} elseif ($local_only_count > 0) {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ MIGRATION NEEDED: Some images still use local paths</p>";
    echo "<p>Run the migration script: <code>php scripts/migrate-images-to-cloudinary.php</code></p>";
} else {
    echo "<p style='color: blue;'>ℹ️ No images found in the system</p>";
}

echo "<hr>";
echo "<p><em>Verification completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
