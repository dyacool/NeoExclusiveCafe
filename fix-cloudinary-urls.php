<?php
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "=== Fixing Cloudinary URLs in Database ===\n\n";

$cloud_name = 'dvdccumbs';
$updated_count = 0;

// Update product images
echo "Updating product images...\n";
$sql = "SELECT id, image_url FROM product_images WHERE is_removed = 0";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $image_id = $row['id'];
    $local_path = $row['image_url']; // e.g., "product-images/Classic_Sourdough_Bread_1757776354/primary_1757776354.jpg"
    
    // Remove extension
    $path_without_ext = pathinfo($local_path, PATHINFO_DIRNAME) . '/' . pathinfo($local_path, PATHINFO_FILENAME);
    
    // Build Cloudinary URL - your structure is assets/product-images/...
    $public_id = 'assets/' . $path_without_ext;
    $extension = pathinfo($local_path, PATHINFO_EXTENSION);
    
    // Cloudinary URL format
    $cloudinary_url = "https://res.cloudinary.com/{$cloud_name}/image/upload/{$public_id}.{$extension}";
    
    // Update database
    $update_sql = "UPDATE product_images SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $cloudinary_url, $public_id, $image_id);
    
    if ($stmt->execute()) {
        echo "  ✅ ID {$image_id}: {$cloudinary_url}\n";
        $updated_count++;
    } else {
        echo "  ❌ Failed ID {$image_id}: " . $stmt->error . "\n";
    }
}

// Update carousel images
echo "\nUpdating carousel images...\n";
$carousel_sql = "SELECT id, image_url FROM carousel_images";
$carousel_result = $conn->query($carousel_sql);

while ($row = $carousel_result->fetch_assoc()) {
    $image_id = $row['id'];
    $local_path = $row['image_url']; // e.g., "images/carousel/image.jpg"
    
    // Remove extension
    $path_without_ext = pathinfo($local_path, PATHINFO_DIRNAME) . '/' . pathinfo($local_path, PATHINFO_FILENAME);
    
    // Build Cloudinary URL
    $public_id = 'assets/' . $path_without_ext;
    $extension = pathinfo($local_path, PATHINFO_EXTENSION);
    
    $cloudinary_url = "https://res.cloudinary.com/{$cloud_name}/image/upload/{$public_id}.{$extension}";
    
    // Update database
    $update_sql = "UPDATE carousel_images SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $cloudinary_url, $public_id, $image_id);
    
    if ($stmt->execute()) {
        echo "  ✅ Carousel ID {$image_id}: {$cloudinary_url}\n";
        $updated_count++;
    } else {
        echo "  ❌ Failed carousel ID {$image_id}: " . $stmt->error . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Updated {$updated_count} images with Cloudinary URLs\n";
echo str_repeat("=", 80) . "\n\n";

echo "Now check your website - images should be loading from Cloudinary!\n";
echo "Run: php test-image-status.php to verify\n";

$conn->close();
?>
