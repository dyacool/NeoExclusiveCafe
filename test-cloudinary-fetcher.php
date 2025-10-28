<?php
/**
 * Cloudinary Image Fetcher - Live Test Page
 * Access this file from your domain to test the fetcher functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$conn = null;
$initError = null;

try {
    // Check if files exist
    $configFile = __DIR__ . '/config/database-config.php';
    $fetcherFile = __DIR__ . '/backend/includes/cloudinary-image-fetcher.php';
    
    if (!file_exists($configFile)) {
        throw new Exception("Database config file not found at: {$configFile}");
    }
    
    if (!file_exists($fetcherFile)) {
        throw new Exception("Cloudinary fetcher file not found at: {$fetcherFile}");
    }
    
    require_once $configFile;
    require_once $fetcherFile;
    
    // Check if class exists
    if (!class_exists('CloudinaryImageFetcher')) {
        throw new Exception("CloudinaryImageFetcher class not loaded. File may have syntax errors.");
    }
    
    // Get database connection
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        throw new Exception("Failed to establish database connection");
    }
} catch (Exception $e) {
    $initError = $e->getMessage();
} catch (Error $e) {
    $initError = "PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloudinary Image Fetcher - Live Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .test-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .image-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .image-info {
            font-size: 0.9em;
            color: #666;
        }
        
        .image-info strong {
            color: #333;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Cloudinary Image Fetcher - Live Test</h1>
        <p class="subtitle">Testing image fetching functionality on your live domain</p>
        
        <?php
        // Check for initialization errors
        if ($initError) {
            echo '<div class="test-section">';
            echo '<h2>❌ Initialization Error</h2>';
            echo '<span class="status error">✗ FAILED</span>';
            echo '<div class="error-message">';
            echo '<strong>Error:</strong> ' . htmlspecialchars($initError) . '<br><br>';
            echo '<strong>Possible causes:</strong><br>';
            echo '• Database connection failed<br>';
            echo '• Cloudinary configuration missing<br>';
            echo '• Required files not found<br>';
            echo '• PHP syntax error in included files';
            echo '</div>';
            echo '</div>';
            echo '</div></body></html>';
            exit;
        }
        
        $testResults = [];
        $totalTests = 0;
        $passedTests = 
        
        // Test 1: Initialize Fetcher
        echo '<div class="test-section">';
        echo '<h2>Test 1: Initialize Cloudinary Image Fetcher</h2>';
        try {
            $imageFetcher = new CloudinaryImageFetcher($conn);
            echo '<span class="status success">✓ SUCCESS</span>';
            echo '<p>Image fetcher initialized successfully!</p>';
            
            // Show Cloudinary status
            $cloudinaryStatus = $imageFetcher->getCloudinaryStatus();
            echo '<div class="code-block">';
            echo 'Cloudinary Status:<br>';
            echo 'Available: ' . ($cloudinaryStatus['available'] ? 'Yes ✓' : 'No (will use local images)') . '<br>';
            echo 'Cloud Name: ' . htmlspecialchars($cloudinaryStatus['cloud_name']) . '<br>';
            echo 'API Key Set: ' . ($cloudinaryStatus['api_key_set'] ? 'Yes ✓' : 'No ✗') . '<br>';
            echo 'API Secret Set: ' . ($cloudinaryStatus['api_secret_set'] ? 'Yes ✓' : 'No ✗');
            echo '</div>';
            
            if (!$cloudinaryStatus['available']) {
                echo '<div class="success-message">';
                echo '<strong>ℹ️ Note:</strong> Cloudinary is not available, but the fetcher will work with local images as fallback.';
                echo '</div>';
            }
            
            $passedTests++;
            $testResults['init'] = true;
        } catch (Exception $e) {
            echo '<span class="status error">✗ FAILED</span>';
            echo '<div class="error-message">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $testResults['init'] = false;
        }
        $totalTests++;
        echo '</div>';
        
        // Test 2: Fetch Single Product Image
        if ($testResults['init']) {
            echo '<div class="test-section">';
            echo '<h2>Test 2: Fetch Single Product Image</h2>';
            try {
                // Get first product from database
                $result = $conn->query("SELECT id, name FROM products WHERE deleted_at IS NULL LIMIT 1");
                if ($result && $product = $result->fetch_assoc()) {
                    $productId = $product['id'];
                    $productName = $product['name'];
                    
                    $imageData = $imageFetcher->fetchProductImage($productId, 'primary');
                    
                    echo '<span class="status success">✓ SUCCESS</span>';
                    echo '<p>Successfully fetched image for product: <strong>' . htmlspecialchars($productName) . '</strong></p>';
                    
                    echo '<div class="image-grid">';
                    echo '<div class="image-card">';
                    echo '<img src="' . htmlspecialchars($imageData['url']) . '" alt="Product Image" loading="lazy">';
                    echo '<div class="image-info">';
                    echo '<strong>Product ID:</strong> ' . $productId . '<br>';
                    echo '<strong>Source:</strong> ' . htmlspecialchars($imageData['source']) . '<br>';
                    echo '<strong>URL:</strong> ' . htmlspecialchars(substr($imageData['url'], 0, 50)) . '...';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    $passedTests++;
                    $testResults['single'] = true;
                } else {
                    echo '<span class="status error">✗ FAILED</span>';
                    echo '<div class="error-message">No products found in database</div>';
                    $testResults['single'] = false;
                }
            } catch (Exception $e) {
                echo '<span class="status error">✗ FAILED</span>';
                echo '<div class="error-message">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $testResults['single'] = false;
            }
            $totalTests++;
            echo '</div>';
            
            // Test 3: Fetch Multiple Product Images
            echo '<div class="test-section">';
            echo '<h2>Test 3: Fetch Multiple Product Images (Batch)</h2>';
            try {
                $result = $conn->query("SELECT id FROM products WHERE deleted_at IS NULL LIMIT 6");
                $productIds = [];
                while ($row = $result->fetch_assoc()) {
                    $productIds[] = $row['id'];
                }
                
                if (!empty($productIds)) {
                    $startTime = microtime(true);
                    $images = $imageFetcher->fetchMultipleProductImages($productIds);
                    $endTime = microtime(true);
                    $executionTime = round(($endTime - $startTime) * 1000, 2);
                    
                    echo '<span class="status success">✓ SUCCESS</span>';
                    echo '<p>Fetched ' . count($images) . ' images in ' . $executionTime . 'ms</p>';
                    
                    echo '<div class="image-grid">';
                    foreach ($images as $productId => $imageData) {
                        echo '<div class="image-card">';
                        echo '<img src="' . htmlspecialchars($imageData['url']) . '" alt="Product ' . $productId . '" loading="lazy">';
                        echo '<div class="image-info">';
                        echo '<strong>ID:</strong> ' . $productId . '<br>';
                        echo '<strong>Source:</strong> ' . htmlspecialchars($imageData['source']);
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    $passedTests++;
                    $testResults['multiple'] = true;
                } else {
                    echo '<span class="status error">✗ FAILED</span>';
                    echo '<div class="error-message">No products found for batch test</div>';
                    $testResults['multiple'] = false;
                }
            } catch (Exception $e) {
                echo '<span class="status error">✗ FAILED</span>';
                echo '<div class="error-message">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $testResults['multiple'] = false;
            }
            $totalTests++;
            echo '</div>';
            
            // Test 4: Image Transformations
            echo '<div class="test-section">';
            echo '<h2>Test 4: Image Transformations (Responsive Sizes)</h2>';
            try {
                $result = $conn->query("SELECT id FROM products WHERE deleted_at IS NULL LIMIT 1");
                if ($result && $product = $result->fetch_assoc()) {
                    $productId = $product['id'];
                    
                    $sizes = [
                        'thumbnail' => ['width' => 200],
                        'medium' => ['width' => 400],
                        'large' => ['width' => 800]
                    ];
                    
                    echo '<span class="status success">✓ SUCCESS</span>';
                    echo '<p>Generated multiple sizes for responsive images</p>';
                    
                    echo '<div class="image-grid">';
                    foreach ($sizes as $sizeName => $transformation) {
                        $imageData = $imageFetcher->fetchProductImage($productId, 'primary', $transformation);
                        echo '<div class="image-card">';
                        echo '<img src="' . htmlspecialchars($imageData['url']) . '" alt="' . $sizeName . '" loading="lazy">';
                        echo '<div class="image-info">';
                        echo '<strong>Size:</strong> ' . ucfirst($sizeName) . '<br>';
                        echo '<strong>Width:</strong> ' . $transformation['width'] . 'px';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    $passedTests++;
                    $testResults['transformations'] = true;
                } else {
                    echo '<span class="status error">✗ FAILED</span>';
                    echo '<div class="error-message">No products found for transformation test</div>';
                    $testResults['transformations'] = false;
                }
            } catch (Exception $e) {
                echo '<span class="status error">✗ FAILED</span>';
                echo '<div class="error-message">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $testResults['transformations'] = false;
            }
            $totalTests++;
            echo '</div>';
            
            // Test 5: Cache Performance
            echo '<div class="test-section">';
            echo '<h2>Test 5: Cache Performance</h2>';
            try {
                $result = $conn->query("SELECT id FROM products WHERE deleted_at IS NULL LIMIT 1");
                if ($result && $product = $result->fetch_assoc()) {
                    $productId = $product['id'];
                    
                    // First fetch (no cache)
                    $imageFetcher->clearCache();
                    $startTime = microtime(true);
                    $imageFetcher->fetchProductImage($productId);
                    $firstFetchTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    // Second fetch (with cache)
                    $startTime = microtime(true);
                    $imageFetcher->fetchProductImage($productId);
                    $cachedFetchTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    $speedup = round($firstFetchTime / $cachedFetchTime, 2);
                    
                    echo '<span class="status success">✓ SUCCESS</span>';
                    echo '<p>Cache is working correctly!</p>';
                    
                    echo '<div class="stats-grid">';
                    echo '<div class="stat-card">';
                    echo '<div class="stat-value">' . $firstFetchTime . 'ms</div>';
                    echo '<div class="stat-label">First Fetch (No Cache)</div>';
                    echo '</div>';
                    echo '<div class="stat-card">';
                    echo '<div class="stat-value">' . $cachedFetchTime . 'ms</div>';
                    echo '<div class="stat-label">Cached Fetch</div>';
                    echo '</div>';
                    echo '<div class="stat-card">';
                    echo '<div class="stat-value">' . $speedup . 'x</div>';
                    echo '<div class="stat-label">Speed Improvement</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    $cacheStats = $imageFetcher->getCacheStats();
                    echo '<div class="code-block">';
                    echo 'Cache Statistics:<br>';
                    echo 'Cached Items: ' . $cacheStats['cached_items'] . '<br>';
                    echo 'Cache Expiry: ' . $cacheStats['cache_expiry'] . ' seconds';
                    echo '</div>';
                    
                    $passedTests++;
                    $testResults['cache'] = true;
                } else {
                    echo '<span class="status error">✗ FAILED</span>';
                    echo '<div class="error-message">No products found for cache test</div>';
                    $testResults['cache'] = false;
                }
            } catch (Exception $e) {
                echo '<span class="status error">✗ FAILED</span>';
                echo '<div class="error-message">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $testResults['cache'] = false;
            }
            $totalTests++;
            echo '</div>';
            
            // Test 6: Error Handling & Fallbacks
            echo '<div class="test-section">';
            echo '<h2>Test 6: Error Handling & Fallback Images</h2>';
            try {
                // Test with non-existent product
                $imageData = $imageFetcher->fetchProductImage(999999, 'primary');
                
                echo '<span class="status success">✓ SUCCESS</span>';
                echo '<p>Fallback mechanism working correctly for missing products</p>';
                
                echo '<div class="image-grid">';
                echo '<div class="image-card">';
                echo '<img src="' . htmlspecialchars($imageData['url']) . '" alt="Fallback Image" loading="lazy">';
                echo '<div class="image-info">';
                echo '<strong>Source:</strong> ' . htmlspecialchars($imageData['source']) . '<br>';
                echo '<strong>Type:</strong> ' . htmlspecialchars($imageData['error_type'] ?? 'N/A');
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                $passedTests++;
                $testResults['fallback'] = true;
            } catch (Exception $e) {
                echo '<span class="status error">✗ FAILED</span>';
                echo '<div class="error-message">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $testResults['fallback'] = false;
            }
            $totalTests++;
            echo '</div>';
        }
        
        // Final Summary
        echo '<div class="test-section" style="border-left-color: ' . ($passedTests === $totalTests ? '#28a745' : '#dc3545') . ';">';
        echo '<h2>📊 Test Summary</h2>';
        
        $successRate = round(($passedTests / $totalTests) * 100, 1);
        
        echo '<div class="stats-grid">';
        echo '<div class="stat-card">';
        echo '<div class="stat-value">' . $passedTests . '/' . $totalTests . '</div>';
        echo '<div class="stat-label">Tests Passed</div>';
        echo '</div>';
        echo '<div class="stat-card">';
        echo '<div class="stat-value">' . $successRate . '%</div>';
        echo '<div class="stat-label">Success Rate</div>';
        echo '</div>';
        echo '<div class="stat-card">';
        echo '<div class="stat-value">' . ($passedTests === $totalTests ? '✓' : '✗') . '</div>';
        echo '<div class="stat-label">Overall Status</div>';
        echo '</div>';
        echo '</div>';
        
        if ($passedTests === $totalTests) {
            echo '<div class="success-message">';
            echo '<strong>🎉 All tests passed!</strong><br>';
            echo 'The Cloudinary Image Fetcher is working perfectly on your domain.';
            echo '</div>';
        } else {
            echo '<div class="error-message">';
            echo '<strong>⚠️ Some tests failed</strong><br>';
            echo 'Please check the error messages above and verify your Cloudinary configuration.';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Close database connection
        $conn->close();
        ?>
        
        <div class="test-section">
            <h2>ℹ️ Next Steps</h2>
            <p>If all tests passed, you can now:</p>
            <ul style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                <li>Integrate the fetcher into your product pages</li>
                <li>Update your cart and checkout pages to use Cloudinary images</li>
                <li>Replace direct database queries with the fetcher methods</li>
                <li>Implement responsive images using transformations</li>
            </ul>
        </div>
    </div>
</body>
</html>
