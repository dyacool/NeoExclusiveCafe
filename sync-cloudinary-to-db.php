<?php
require_once __DIR__ . '/config/cloudinary-config.php';
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "=== Cloudinary to Database Sync Tool ===\n\n";

try {
    $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
    
    // Get all resources from Cloudinary
    echo "Fetching files from Cloudinary...\n";
    $result = $cloudinary->adminApi()->resources([
        'type' => 'upload',
        'prefix' => 'neocafe/',
        'max_results' => 500
    ]);
    
    $total_files = count($result['resources']);
    echo "Found {$total_files} files in Cloudinary\n\n";
    
    // Organize by type
    $product_images = [];
    $carousel_images = [];
    $other_images = [];
    
    foreach ($result['resources'] as $resource) {
        $public_id = $resource['public_id'];
        $url = $resource['secure_url'];
        
        if (strpos($public_id, 'neocafe/products/') === 0) {
            $product_images[] = ['public_id' => $public_id, 'url' => $url];
        } elseif (strpos($public_id, 'neocafe/carousel/') === 0) {
            $carousel_images[] = ['public_id' => $public_id, 'url' => $url];
        } else {
            $other_images[] = ['public_id' => $public_id, 'url' => $url];
        }
    }
    
    echo "Product images: " . count($product_images) . "\n";
    echo "Carousel images: " . count($carousel_images) . "\n";
    echo "Other images: " . count($other_images) . "\n\n";
    
    // Show what we found
    if (count($product_images) > 0) {
        echo "=== PRODUCT IMAGES IN CLOUDINARY ===\n";
        foreach (array_slice($product_images, 0, 10) as $img) {
            echo "  - {$img['public_id']}\n";
            echo "    URL: {$img['url']}\n";
        }
        if (count($product_images) > 10) {
            echo "  ... and " . (count($product_images) - 10) . " more\n";
        }
        echo "\n";
    }
    
    if (count($carousel_images) > 0) {
        echo "=== CAROUSEL IMAGES IN CLOUDINARY ===\n";
        foreach ($carousel_images as $img) {
            echo "  - {$img['public_id']}\n";
            echo "    URL: {$img['url']}\n";
        }
        echo "\n";
    }
    
    // Check database
    echo "=== DATABASE STATUS ===\n";
    $db_check = $conn->query("SELECT COUNT(*) as total FROM product_images WHERE cloud_url IS NOT NULL AND cloud_url != ''");
    $db_stats = $db_check->fetch_assoc();
    echo "Product images with Cloudinary URLs in DB: {$db_stats['total']}\n\n";
    
    // Ask user what to do
    echo "=== NEXT STEPS ===\n";
    echo "Your images are in Cloudinary but the database doesn't have the URLs.\n";
    echo "We need to map the Cloudinary files to your database records.\n\n";
    echo "Please tell me:\n";
    echo "1. What naming pattern did you use when uploading to Cloudinary?\n";
    echo "2. Did you keep the same filenames as the local files?\n";
    echo "3. What folder structure did you use? (e.g., neocafe/products/product_1_primary)\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
