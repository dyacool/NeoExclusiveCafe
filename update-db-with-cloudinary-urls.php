<?php
require_once __DIR__ . '/config/cloudinary-config.php';
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

echo "=== Updating Database with Cloudinary URLs ===\n\n";

$cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
$cloud_name = getenv('CLOUDINARY_CLOUD_NAME');

$updated_count = 0;
$failed_count = 0;

// Update product images
echo "Updating product images...\n";
$sql = "SELECT id, image_url FROM product_images WHERE (cloud_url IS NULL OR cloud_url = '') AND is_removed = 0";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $image_id = $row['id'];
    $local_path = $row['image_url']; // e.g., "product-images/Classic_Sourdough_Bread_1757776354/primary_1757776354.jpg"
    
    // Build Cloudinary URL based on your folder structure
    // Your structure: assets/product-images/...
    // So Cloudinary public_id would be: assets/product-images/Classic_Sourdough_Bread_1757776354/primary_1757776354
    
    $public_id = 'assets/' . pathinfo($local_path, PATHINFO_DIRNAME) . '/' . pathinfo($local_path, PATHINFO_FILENAME);
    $cloudinary_url = "https://res.cloudinary.com/{$cloud_name}/image/upload/{$public_id}." . pathinfo($local_path, PATHINFO_EXTENSION);
    
    // Update database
    $update_sql = "UPDATE product_images SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $cloudinary_url, $public_id, $image_id);
    
    if ($stmt->execute()) {
        echo "  ✅ Updated image ID {$image_id}: {$local_path}\n";
        echo "     Cloudinary URL: {$cloudinary_url}\n";
        $updated_count++;
    } else {
        echo "  ❌ Failed to update image ID {$image_id}: " . $stmt->error . "\n";
        $failed_count++;
    }
}

// Update carousel images
echo "\nUpdating carousel images...\n";
$carousel_sql = "SELECT id, image_url FROM carousel_images WHERE (cloud_url IS NULL OR cloud_url = '')";
$carousel_result = $conn->query($carousel_sql);

while ($row = $carousel_result->fetch_assoc()) {
    $image_id = $row['id'];
    $local_path = $row['image_url']; // e.g., "images/carousel/image.jpg"
    
    // Build Cloudinary URL
    $public_id = 'assets/' . pathinfo($local_path, PATHINFO_DIRNAME) . '/' . pathinfo($local_path, PATHINFO_FILENAME);
    $cloudinary_url = "https://res.cloudinary.com/{$cloud_name}/image/upload/{$public_id}." . pathinfo($local_path, PATHINFO_EXTENSION);
    
    // Update database
    $update_sql = "UPDATE carousel_images SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $cloudinary_url, $public_id, $image_id);
    
    if ($stmt->execute()) {
        echo "  ✅ Updated carousel image ID {$image_id}: {$local_path}\n";
        echo "     Cloudinary URL: {$cloudinary_url}\n";
        $updated_count++;
    } else {
        echo "  ❌ Failed to update carousel image ID {$image_id}: " . $stmt->error . "\n";
        $failed_count++;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY:\n";
echo "  ✅ Successfully updated: {$updated_count}\n";
echo "  ❌ Failed: {$failed_count}\n";
echo str_repeat("=", 80) . "\n\n";

echo "Now run: php test-image-status.php to verify\n";

$conn->close();
?>
