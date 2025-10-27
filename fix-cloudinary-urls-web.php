<?php
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Cloudinary URLs</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        .btn { background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🔧 Fix Cloudinary URLs in Database</h1>
    
    <?php
    if (isset($_POST['fix_urls'])) {
        echo "<div class='box'>";
        echo "<h2>Updating Database...</h2>";
        
        $cloud_name = 'dvdccumbs';
        $updated_count = 0;
        $failed_count = 0;
        
        // Update product images
        echo "<h3>Product Images:</h3>";
        $sql = "SELECT id, image_url FROM product_images WHERE is_removed = 0";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $image_id = $row['id'];
            $local_path = $row['image_url'];
            
            // Build Cloudinary URL
            $path_without_ext = pathinfo($local_path, PATHINFO_DIRNAME) . '/' . pathinfo($local_path, PATHINFO_FILENAME);
            $public_id = 'assets/' . $path_without_ext;
            $extension = pathinfo($local_path, PATHINFO_EXTENSION);
            $cloudinary_url = "https://res.cloudinary.com/{$cloud_name}/image/upload/{$public_id}.{$extension}";
            
            // Update database
            $update_sql = "UPDATE product_images SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $cloudinary_url, $public_id, $image_id);
            
            if ($stmt->execute()) {
                echo "<span class='success'>✅ Updated ID {$image_id}</span><br>";
                $updated_count++;
            } else {
                echo "<span class='error'>❌ Failed ID {$image_id}: " . $stmt->error . "</span><br>";
                $failed_count++;
            }
        }
        
        // Update carousel images
        echo "<h3>Carousel Images:</h3>";
        $carousel_sql = "SELECT id, image_url FROM carousel_images";
        $carousel_result = $conn->query($carousel_sql);
        
        while ($row = $carousel_result->fetch_assoc()) {
            $image_id = $row['id'];
            $local_path = $row['image_url'];
            
            // Build Cloudinary URL
            $path_without_ext = pathinfo($local_path, PATHINFO_DIRNAME) . '/' . pathinfo($local_path, PATHINFO_FILENAME);
            $public_id = 'assets/' . $path_without_ext;
            $extension = pathinfo($local_path, PATHINFO_EXTENSION);
            $cloudinary_url = "https://res.cloudinary.com/{$cloud_name}/image/upload/{$public_id}.{$extension}";
            
            // Update database
            $update_sql = "UPDATE carousel_images SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $cloudinary_url, $public_id, $image_id);
            
            if ($stmt->execute()) {
                echo "<span class='success'>✅ Updated Carousel ID {$image_id}</span><br>";
                $updated_count++;
            } else {
                echo "<span class='error'>❌ Failed Carousel ID {$image_id}: " . $stmt->error . "</span><br>";
                $failed_count++;
            }
        }
        
        echo "<hr>";
        echo "<h2 class='success'>✅ Complete!</h2>";
        echo "<p><strong>Successfully updated:</strong> {$updated_count}</p>";
        echo "<p><strong>Failed:</strong> {$failed_count}</p>";
        echo "<p><a href='test-image-status.php' target='_blank'>Click here to verify the results</a></p>";
        echo "</div>";
        
    } else {
        // Show current status
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN cloud_url IS NOT NULL AND cloud_url != '' THEN 1 ELSE 0 END) as migrated,
            SUM(CASE WHEN cloud_url IS NULL OR cloud_url = '' THEN 1 ELSE 0 END) as not_migrated
        FROM product_images
        WHERE is_removed = 0";
        
        $result = $conn->query($sql);
        $stats = $result->fetch_assoc();
        
        $carousel_sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN cloud_url IS NOT NULL AND cloud_url != '' THEN 1 ELSE 0 END) as migrated,
            SUM(CASE WHEN cloud_url IS NULL OR cloud_url = '' THEN 1 ELSE 0 END) as not_migrated
        FROM carousel_images";
        
        $carousel_result = $conn->query($carousel_sql);
        $carousel_stats = $carousel_result->fetch_assoc();
        
        echo "<div class='box'>";
        echo "<h2>Current Status</h2>";
        echo "<table>";
        echo "<tr><th>Type</th><th>Total</th><th>Migrated</th><th>Not Migrated</th></tr>";
        echo "<tr><td>Product Images</td><td>{$stats['total']}</td><td class='success'>{$stats['migrated']}</td><td class='error'>{$stats['not_migrated']}</td></tr>";
        echo "<tr><td>Carousel Images</td><td>{$carousel_stats['total']}</td><td class='success'>{$carousel_stats['migrated']}</td><td class='error'>{$carousel_stats['not_migrated']}</td></tr>";
        echo "</table>";
        echo "</div>";
        
        // Show sample images
        echo "<div class='box'>";
        echo "<h2>Sample Product Images (First 5)</h2>";
        $sample_sql = "SELECT id, product_id, image_url, cloud_url, is_primary 
                       FROM product_images 
                       WHERE is_removed = 0 
                       LIMIT 5";
        $sample_result = $conn->query($sample_sql);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Product ID</th><th>Local Path</th><th>Cloudinary URL</th><th>Primary</th></tr>";
        while ($row = $sample_result->fetch_assoc()) {
            $status = empty($row['cloud_url']) ? "<span class='error'>NOT MIGRATED</span>" : "<span class='success'>✅ Migrated</span>";
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['product_id']}</td>";
            echo "<td>" . htmlspecialchars($row['image_url']) . "</td>";
            echo "<td>{$status}</td>";
            echo "<td>" . ($row['is_primary'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        if ($stats['not_migrated'] > 0 || $carousel_stats['not_migrated'] > 0) {
            echo "<div class='box'>";
            echo "<h2 class='error'>⚠️ Action Required</h2>";
            echo "<p>You have images that need Cloudinary URLs added to the database.</p>";
            echo "<p>This will update the database to point to your Cloudinary images at:</p>";
            echo "<ul>";
            echo "<li><code>https://res.cloudinary.com/dvdccumbs/image/upload/assets/product-images/...</code></li>";
            echo "<li><code>https://res.cloudinary.com/dvdccumbs/image/upload/assets/images/...</code></li>";
            echo "</ul>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='fix_urls' class='btn'>🔧 Fix Cloudinary URLs Now</button>";
            echo "</form>";
            echo "</div>";
        } else {
            echo "<div class='box'>";
            echo "<h2 class='success'>✅ All images are configured!</h2>";
            echo "<p>All your images have Cloudinary URLs in the database.</p>";
            echo "</div>";
        }
    }
    
    $conn->close();
    ?>
    
</body>
</html>
