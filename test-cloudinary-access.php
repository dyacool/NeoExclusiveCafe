<?php
require_once __DIR__ . '/backend/pages/admin-includes/database.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Cloudinary Image Access</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .test-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { border-color: green; background: #f0fff0; }
        .error { border-color: red; background: #fff0f0; }
        img { max-width: 200px; border: 2px solid #ccc; }
        .broken { border-color: red !important; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Cloudinary Image Access Test</h1>
    <p>Testing if Cloudinary URLs are actually accessible...</p>
    
    <?php
    // Get first 5 product images
    $sql = "SELECT id, product_id, image_url, cloud_url FROM product_images WHERE is_removed = 0 LIMIT 5";
    $result = $conn->query($sql);
    
    $working = 0;
    $broken = 0;
    
    while ($row = $result->fetch_assoc()) {
        echo "<div class='test-item' id='test-{$row['id']}'>";
        echo "<h3>Image ID: {$row['id']} (Product {$row['product_id']})</h3>";
        echo "<p><strong>Local path:</strong> <code>" . htmlspecialchars($row['image_url']) . "</code></p>";
        echo "<p><strong>Cloudinary URL:</strong> <code>" . htmlspecialchars($row['cloud_url']) . "</code></p>";
        
        echo "<p><strong>Image preview:</strong></p>";
        echo "<img src='" . htmlspecialchars($row['cloud_url']) . "' 
              onload=\"document.getElementById('test-{$row['id']}').className='test-item success'; document.getElementById('status-{$row['id']}').innerHTML='✅ WORKING';\"
              onerror=\"document.getElementById('test-{$row['id']}').className='test-item error'; document.getElementById('status-{$row['id']}').innerHTML='❌ BROKEN - Image not found at this URL'; this.className='broken';\">";
        
        echo "<p id='status-{$row['id']}'>⏳ Loading...</p>";
        echo "</div>";
    }
    
    $conn->close();
    ?>
    
    <script>
    setTimeout(function() {
        let working = document.querySelectorAll('.test-item.success').length;
        let broken = document.querySelectorAll('.test-item.error').length;
        
        let summary = document.createElement('div');
        summary.style.cssText = 'position: fixed; top: 20px; right: 20px; background: white; border: 2px solid #333; padding: 15px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);';
        summary.innerHTML = '<h3>Summary</h3><p>✅ Working: ' + working + '</p><p>❌ Broken: ' + broken + '</p>';
        document.body.appendChild(summary);
    }, 3000);
    </script>
    
</body>
</html>
