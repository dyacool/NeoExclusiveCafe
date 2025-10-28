<?php
require_once __DIR__ . '/config/cloudinary-config.php';
require_once __DIR__ . '/backend/pages/admin-includes/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloudinary Images Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .image-info {
            padding: 10px;
        }
        .image-info h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #333;
        }
        .image-info p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .url {
            font-size: 10px;
            color: #999;
            word-break: break-all;
        }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .test-methods {
            background: #e8f5e9;
            padding: 15px;
            border-left: 4px solid #4CAF50;
            margin: 20px 0;
        }
        .test-methods h3 {
            margin-top: 0;
            color: #2e7d32;
        }
        .code-block {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🖼️ Cloudinary Images Test Page</h1>
    
    <div class="section">
        <h2>📊 Connection Status</h2>
        <?php
        try {
            $config = CloudinaryConfig::getInstance();
            $testResult = $config->testConnection();
            
            if ($testResult['success']) {
                echo "<p class='status success'>✅ Connected to Cloudinary</p>";
                echo "<p><strong>Cloud Name:</strong> " . htmlspecialchars($testResult['cloud_name']) . "</p>";
            } else {
                echo "<p class='status error'>❌ Connection Failed</p>";
                echo "<p>" . htmlspecialchars($testResult['message']) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p class='status error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>🎨 Product Images from Database</h2>
        <p>These are product images that have Cloudinary URLs in the database:</p>
        
        <?php
        $sql = "SELECT pi.id, pi.product_id, pi.image_url, pi.cloud_url, pi.cloud_public_id, pi.is_primary, p.name as product_name
                FROM product_images pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.cloud_url IS NOT NULL AND pi.cloud_url != ''
                LIMIT 12";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<div class='image-grid'>";
            while ($row = $result->fetch_assoc()) {
                echo "<div class='image-card'>";
                echo "<img src='" . htmlspecialchars($row['cloud_url']) . "' alt='" . htmlspecialchars($row['product_name']) . "' onerror=\"this.src='assets/images/no-image.jpg'\">";
                echo "<div class='image-info'>";
                echo "<h3>" . htmlspecialchars($row['product_name']) . "</h3>";
                echo "<p><span class='status success'>" . ($row['is_primary'] ? 'PRIMARY' : 'ADDITIONAL') . "</span></p>";
                echo "<p class='url'>" . htmlspecialchars($row['cloud_url']) . "</p>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<p class='status warning'>⚠️ No product images with Cloudinary URLs found in database yet.</p>";
            echo "<p>You need to either:</p>";
            echo "<ul>";
            echo "<li>Run the migration script to upload images</li>";
            echo "<li>Manually update the database with Cloudinary URLs</li>";
            echo "</ul>";
        }
        ?>
    </div>

    <div class="section">
        <h2>🎪 Carousel Images from Database</h2>
        
        <?php
        $sql = "SELECT id, title, image_url, cloud_url, cloud_public_id
                FROM carousel_images
                WHERE cloud_url IS NOT NULL AND cloud_url != ''
                AND is_active = 1
                LIMIT 6";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<div class='image-grid'>";
            while ($row = $result->fetch_assoc()) {
                echo "<div class='image-card'>";
                echo "<img src='" . htmlspecialchars($row['cloud_url']) . "' alt='" . htmlspecialchars($row['title']) . "' onerror=\"this.src='assets/images/no-image.jpg'\">";
                echo "<div class='image-info'>";
                echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                echo "<p class='url'>" . htmlspecialchars($row['cloud_url']) . "</p>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<p class='status warning'>⚠️ No carousel images with Cloudinary URLs found.</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>🔗 Direct Cloudinary URL Test</h2>
        <p>Test loading an image directly from your Cloudinary account:</p>
        
        <div class="test-methods">
            <h3>Method 1: Direct URL</h3>
            <p>Replace <code>YOUR_PUBLIC_ID</code> with your actual image public ID from Cloudinary:</p>
            <div class="code-block">
                https://res.cloudinary.com/dvdccumbs/image/upload/YOUR_PUBLIC_ID.jpg
            </div>
            
            <h3 style="margin-top: 20px;">Method 2: With Transformations</h3>
            <div class="code-block">
                https://res.cloudinary.com/dvdccumbs/image/upload/w_400,h_300,c_fill,q_auto,f_auto/YOUR_PUBLIC_ID.jpg
            </div>
            
            <h3 style="margin-top: 20px;">Example (if you have an image named 'sample'):</h3>
            <div class="code-block">
                https://res.cloudinary.com/dvdccumbs/image/upload/w_400,q_auto,f_auto/sample.jpg
            </div>
        </div>
        
        <!-- Test with Cloudinary's sample image -->
        <h3>Cloudinary Sample Image Test:</h3>
        <div class="image-card" style="max-width: 400px;">
            <img src="https://res.cloudinary.com/dvdccumbs/image/upload/w_400,q_auto,f_auto/sample.jpg" 
                 alt="Cloudinary Sample" 
                 onerror="this.parentElement.innerHTML='<p class=\'status error\'>❌ Could not load sample image. Check your Cloudinary account.</p>'">
            <div class="image-info">
                <h3>Cloudinary Sample Image</h3>
                <p>If you see this image, Cloudinary is working!</p>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>📝 Migration Status</h2>
        
        <?php
        $sql = "SELECT image_type, status, COUNT(*) as count
                FROM image_migrations
                GROUP BY image_type, status
                ORDER BY image_type, status";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #f5f5f5;'>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Image Type</th>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Status</th>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Count</th>";
            echo "</tr>";
            
            while ($row = $result->fetch_assoc()) {
                $statusClass = $row['status'] === 'success' ? 'success' : 'error';
                echo "<tr>";
                echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($row['image_type']) . "</td>";
                echo "<td style='padding: 10px; border: 1px solid #ddd;'><span class='status {$statusClass}'>" . htmlspecialchars($row['status']) . "</span></td>";
                echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($row['count']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='status warning'>⚠️ No migration records found. Run the migration script first.</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>🛠️ How to Use Cloudinary Images in Your Code</h2>
        
        <h3>Option 1: Use cloud_url from database (Recommended)</h3>
        <div class="code-block">
&lt;?php
// In your product display code:
$sql = "SELECT pi.cloud_url, pi.image_url, p.name 
        FROM product_images pi 
        JOIN products p ON pi.product_id = p.id";

// Use cloud_url if available, fallback to local
$imageUrl = $row['cloud_url'] ?: '../../../assets/' . $row['image_url'];
?&gt;

&lt;img src="&lt;?php echo htmlspecialchars($imageUrl); ?&gt;" alt="Product"&gt;
        </div>

        <h3>Option 2: Direct Cloudinary URL</h3>
        <div class="code-block">
&lt;img src="https://res.cloudinary.com/dvdccumbs/image/upload/neocafe/products/product_123.jpg" alt="Product"&gt;
        </div>

        <h3>Option 3: Using Helper Function</h3>
        <div class="code-block">
&lt;?php
require_once 'backend/includes/cloudinary-helper.php';
$url = getCloudinaryUrl('neocafe/products/product_123');
?&gt;

&lt;img src="&lt;?php echo $url; ?&gt;" alt="Product"&gt;
        </div>
    </div>

    <div class="section" style="background: #fff3cd; border-left: 4px solid #ffc107;">
        <h2>⚡ Next Steps</h2>
        <ol>
            <li><strong>Verify your images are in Cloudinary:</strong> Log into your Cloudinary dashboard at <a href="https://cloudinary.com/console" target="_blank">cloudinary.com/console</a></li>
            <li><strong>Note your image public IDs:</strong> Check the folder structure (e.g., neocafe/products/)</li>
            <li><strong>Update database:</strong> Add Cloudinary URLs to your product_images and carousel_images tables</li>
            <li><strong>Update application code:</strong> Modify your PHP files to use cloud_url instead of image_url</li>
            <li><strong>Test thoroughly:</strong> Check all pages that display images</li>
        </ol>
    </div>

</body>
</html>
