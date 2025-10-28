<?php
/**
 * Cloudinary Image Fetcher - Usage Examples
 * 
 * This file demonstrates how to safely fetch images from Cloudinary
 * using the centralized CloudinaryImageFetcher class
 */

require_once __DIR__ . '/../backend/includes/cloudinary-image-fetcher.php';

// Initialize the fetcher
try {
    $imageFetcher = new CloudinaryImageFetcher();
    
    // Example 1: Fetch a single product image
    echo "=== Example 1: Fetch Single Product Image ===\n";
    $productImage = $imageFetcher->fetchProductImage(1, 'primary');
    echo "URL: " . $productImage['url'] . "\n";
    echo "Source: " . $productImage['source'] . "\n\n";
    
    // Example 2: Fetch product image with custom transformations
    echo "=== Example 2: Fetch with Transformations ===\n";
    $transformations = [
        'width' => 400,
        'quality' => 'auto',
        'fetch_format' => 'auto'
    ];
    $thumbnailImage = $imageFetcher->fetchProductImage(1, 'primary', $transformations);
    echo "Thumbnail URL: " . $thumbnailImage['url'] . "\n\n";
    
    // Example 3: Fetch multiple product images (optimized for performance)
    echo "=== Example 3: Fetch Multiple Products ===\n";
    $productIds = [1, 2, 3, 4, 5];
    $multipleImages = $imageFetcher->fetchMultipleProductImages($productIds);
    foreach ($multipleImages as $productId => $imageData) {
        echo "Product {$productId}: {$imageData['url']}\n";
    }
    echo "\n";
    
    // Example 4: Fetch payment proof
    echo "=== Example 4: Fetch Payment Proof ===\n";
    $paymentProof = $imageFetcher->fetchPaymentProof('payment_12345.jpg', 'bulk_payments');
    echo "Payment Proof URL: " . $paymentProof['url'] . "\n";
    echo "Source: " . $paymentProof['source'] . "\n\n";
    
    // Example 5: Fetch general asset
    echo "=== Example 5: Fetch General Asset ===\n";
    $assetImage = $imageFetcher->fetchAssetImage('logo.png');
    echo "Asset URL: " . $assetImage['url'] . "\n\n";
    
    // Example 6: Verify image exists
    echo "=== Example 6: Verify Image Exists ===\n";
    $exists = $imageFetcher->verifyImageExists('neocafe/products/product_1_primary');
    echo "Image exists: " . ($exists ? 'Yes' : 'No') . "\n\n";
    
    // Example 7: Cache statistics
    echo "=== Example 7: Cache Statistics ===\n";
    $stats = $imageFetcher->getCacheStats();
    echo "Cached items: " . $stats['cached_items'] . "\n";
    echo "Cache expiry: " . $stats['cache_expiry'] . " seconds\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// ============================================
// Usage in HTML/PHP Pages
// ============================================
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cloudinary Image Display Example</title>
    <style>
        .product-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px;
            display: inline-block;
        }
        .product-image {
            max-width: 300px;
            height: auto;
        }
    </style>
</head>
<body>
    <h1>Product Gallery</h1>
    
    <?php
    // Example: Display product images in a gallery
    $imageFetcher = new CloudinaryImageFetcher();
    $productIds = [1, 2, 3, 4, 5];
    $images = $imageFetcher->fetchMultipleProductImages($productIds);
    
    foreach ($images as $productId => $imageData):
    ?>
        <div class="product-card">
            <img 
                src="<?php echo htmlspecialchars($imageData['url']); ?>" 
                alt="Product <?php echo $productId; ?>"
                class="product-image"
                loading="lazy"
            />
            <p>Product ID: <?php echo $productId; ?></p>
            <p>Source: <?php echo $imageData['source']; ?></p>
        </div>
    <?php endforeach; ?>
    
    <hr>
    
    <h2>Single Product with Responsive Images</h2>
    <?php
    // Example: Responsive images with different sizes
    $productId = 1;
    
    // Fetch different sizes
    $large = $imageFetcher->fetchProductImage($productId, 'primary', ['width' => 1200]);
    $medium = $imageFetcher->fetchProductImage($productId, 'primary', ['width' => 800]);
    $small = $imageFetcher->fetchProductImage($productId, 'primary', ['width' => 400]);
    ?>
    
    <picture>
        <source media="(min-width: 1200px)" srcset="<?php echo htmlspecialchars($large['url']); ?>">
        <source media="(min-width: 768px)" srcset="<?php echo htmlspecialchars($medium['url']); ?>">
        <img 
            src="<?php echo htmlspecialchars($small['url']); ?>" 
            alt="Responsive Product Image"
            class="product-image"
        />
    </picture>
    
</body>
</html>

<?php
// ============================================
// Usage in API Endpoints
// ============================================

/**
 * Example API endpoint that returns product images
 */
function apiGetProductImages() {
    header('Content-Type: application/json');
    
    try {
        $imageFetcher = new CloudinaryImageFetcher();
        
        // Get product ID from request
        $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product ID']);
            return;
        }
        
        // Fetch image
        $imageData = $imageFetcher->fetchProductImage($productId);
        
        // Return JSON response
        echo json_encode([
            'success' => true,
            'data' => $imageData
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Example API endpoint for batch image fetching
 */
function apiGetMultipleProductImages() {
    header('Content-Type: application/json');
    
    try {
        $imageFetcher = new CloudinaryImageFetcher();
        
        // Get product IDs from request
        $productIds = isset($_GET['product_ids']) ? explode(',', $_GET['product_ids']) : [];
        $productIds = array_map('intval', $productIds);
        
        if (empty($productIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'No product IDs provided']);
            return;
        }
        
        // Fetch images
        $images = $imageFetcher->fetchMultipleProductImages($productIds);
        
        // Return JSON response
        echo json_encode([
            'success' => true,
            'data' => $images,
            'count' => count($images)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// ============================================
// Best Practices
// ============================================

/*
 * BEST PRACTICES FOR USING CLOUDINARY IMAGE FETCHER:
 * 
 * 1. ALWAYS use the fetcher instead of direct database queries
 *    ✓ $imageFetcher->fetchProductImage($id)
 *    ✗ Direct SQL query for image URLs
 * 
 * 2. Use batch fetching for multiple images
 *    ✓ $imageFetcher->fetchMultipleProductImages([1,2,3,4,5])
 *    ✗ Loop with individual fetchProductImage() calls
 * 
 * 3. Apply transformations for responsive images
 *    ✓ ['width' => 400, 'quality' => 'auto']
 *    ✗ Loading full-size images everywhere
 * 
 * 4. Always handle errors gracefully
 *    ✓ try-catch blocks with fallback images
 *    ✗ Assuming images always exist
 * 
 * 5. Use lazy loading for images
 *    ✓ <img loading="lazy" />
 *    ✗ Loading all images immediately
 * 
 * 6. Sanitize output in HTML
 *    ✓ htmlspecialchars($imageData['url'])
 *    ✗ Direct echo of URLs
 * 
 * 7. Cache results when possible
 *    The fetcher has built-in caching, reuse the same instance
 * 
 * 8. Verify images exist before critical operations
 *    ✓ $imageFetcher->verifyImageExists($publicId)
 *    ✗ Assuming all images are available
 */
?>
