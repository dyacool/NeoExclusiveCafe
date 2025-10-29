<?php
/**
 * Test Single Cloudinary Image
 * Test if a specific Cloudinary URL works
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Single Cloudinary Image</h1>";

// Your example URL
$testUrl = 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594980/primary_1757776354_mpd5kt.jpg';

echo "<h2>Original URL</h2>";
echo "<p>$testUrl</p>";
echo "<img src='$testUrl' style='max-width: 300px;' alt='Test Image'><br>";
echo "<p>If you see an image above, Cloudinary URLs are working fine!</p>";

// Test extracting public ID
echo "<h2>Extracting Public ID</h2>";
$pattern = '/\/upload\/(?:v\d+\/)?(.+?)(?:\.[a-z]+)?$/i';
if (preg_match($pattern, $testUrl, $matches)) {
    $publicId = $matches[1];
    echo "✓ Extracted Public ID: <strong>$publicId</strong><br>";
} else {
    echo "✗ Failed to extract public ID<br>";
    exit;
}

// Test with CloudinaryImageFetcher
echo "<h2>Testing with CloudinaryImageFetcher</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/config/database-config.php';
    require_once __DIR__ . '/backend/includes/cloudinary-image-fetcher.php';
    
    $conn = getDatabaseConnection();
    $fetcher = new CloudinaryImageFetcher($conn);
    
    echo "✓ CloudinaryImageFetcher initialized<br>";
    
    // Test processing the URL
    try {
        $imageData = $fetcher->fetchAssetImage($publicId, [
            'width' => 300,
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ]);
        
        echo "✓ Image processed successfully!<br>";
        echo "<h3>Processed Image Data:</h3>";
        echo "<pre>" . print_r($imageData, true) . "</pre>";
        
        echo "<h3>Transformed Image:</h3>";
        echo "<img src='{$imageData['url']}' style='max-width: 300px;' alt='Transformed Image'><br>";
        
    } catch (Exception $e) {
        echo "✗ Error processing image: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test getting a product from database
echo "<h2>Testing with Real Product</h2>";
try {
    $result = $conn->query("SELECT id, name, cloudinary_url FROM products WHERE cloudinary_url IS NOT NULL AND cloudinary_url != '' LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo "Product ID: {$product['id']}<br>";
        echo "Product Name: {$product['name']}<br>";
        echo "Cloudinary URL: {$product['cloudinary_url']}<br>";
        
        try {
            $imageData = $fetcher->fetchProductImage($product['id'], 'primary', [
                'width' => 300,
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]);
            
            echo "✓ Product image fetched successfully!<br>";
            echo "<img src='{$imageData['url']}' style='max-width: 300px;' alt='{$product['name']}'><br>";
            
        } catch (Exception $e) {
            echo "✗ Error fetching product image: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "No products with Cloudinary URLs found in database<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

$conn->close();
?>
