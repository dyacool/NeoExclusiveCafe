<?php
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Heroku Database Check</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Heroku Database Status Check</h1>
    
    <?php
    // Check product images
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN cloud_url IS NOT NULL AND cloud_url != '' THEN 1 ELSE 0 END) as with_cloudinary,
        SUM(CASE WHEN cloud_url IS NULL OR cloud_url = '' THEN 1 ELSE 0 END) as without_cloudinary
    FROM product_images 
    WHERE is_removed = 0";
    
    $result = $conn->query($sql);
    $stats = $result->fetch_assoc();
    
    echo "<h2>Product Images Status:</h2>";
    echo "<p>Total images: {$stats['total']}</p>";
    echo "<p class='success'>With Cloudinary URLs: {$stats['with_cloudinary']}</p>";
    echo "<p class='error'>Without Cloudinary URLs: {$stats['without_cloudinary']}</p>";
    
    if ($stats['without_cloudinary'] > 0) {
        echo "<div class='error'>";
        echo "<h3>❌ PROBLEM FOUND!</h3>";
        echo "<p>Your Heroku database doesn't have Cloudinary URLs.</p>";
        echo "<p><strong>Solution:</strong> <a href='fix-cloudinary-urls-web.php'>Click here to fix it</a></p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Database is OK!</h3>";
        echo "<p>All images have Cloudinary URLs.</p>";
        echo "</div>";
    }
    
    // Show sample image
    echo "<h2>Sample Image Test:</h2>";
    $sample_sql = "SELECT id, image_url, cloud_url FROM product_images WHERE is_removed = 0 LIMIT 1";
    $sample_result = $conn->query($sample_sql);
    $sample = $sample_result->fetch_assoc();
    
    if ($sample) {
        echo "<p><strong>Local path:</strong> " . htmlspecialchars($sample['image_url']) . "</p>";
        echo "<p><strong>Cloudinary URL:</strong> " . htmlspecialchars($sample['cloud_url'] ?: 'NONE') . "</p>";
        
        $display_url = !empty($sample['cloud_url']) ? $sample['cloud_url'] : '/assets/' . $sample['image_url'];
        
        echo "<p><strong>Will display from:</strong> " . htmlspecialchars($display_url) . "</p>";
        echo "<img src='" . htmlspecialchars($display_url) . "' style='max-width: 300px; border: 2px solid #ccc;' onerror=\"this.style.border='2px solid red'; this.alt='IMAGE FAILED TO LOAD';\">";
        
        echo "<p><em>If you see a broken image above, the URL is not accessible.</em></p>";
    }
    
    $conn->close();
    ?>
    
</body>
</html>
