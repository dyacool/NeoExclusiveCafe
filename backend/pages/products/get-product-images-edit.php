<?php
/**
 * Get Product Images for Editing
 * 
 * Returns all images (primary and additional) for a product
 * Used by the edit product interface
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check admin authentication
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate product ID
    if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
        throw new Exception('Invalid product ID');
    }
    
    $product_id = (int)$_GET['product_id'];
    
    // Get all images for the product (not removed)
    $stmt = $conn->prepare("
        SELECT 
            id,
            cloud_url,
            cloud_public_id,
            image_url,
            is_primary,
            created_at,
            updated_at
        FROM product_images 
        WHERE product_id = ? AND is_removed = 0
        ORDER BY is_primary DESC, id ASC
    ");
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $primary_image = null;
    $additional_images = [];
    
    while ($row = $result->fetch_assoc()) {
        // Only use Cloudinary URLs, log if local path is found
        if (empty($row['cloud_url']) && !empty($row['image_url'])) {
            require_once __DIR__ . '/../../includes/cloudinary-helper.php';
            logLocalFileAccess($row['image_url'], 'READ_ATTEMPT', "Image ID {$row['id']} for product $product_id has local path but no Cloudinary URL");
        }
        
        $image_data = [
            'id' => $row['id'],
            'url' => $row['cloud_url'], // Only use Cloudinary URL
            'cloudinary_url' => $row['cloud_url'],
            'public_id' => $row['cloud_public_id'],
            'is_primary' => (bool)$row['is_primary'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
        
        // Skip images without Cloudinary URLs
        if (empty($image_data['url'])) {
            continue;
        }
        
        if ($row['is_primary']) {
            $primary_image = $image_data;
        } else {
            $additional_images[] = $image_data;
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'primary_image' => $primary_image,
        'additional_images' => $additional_images,
        'total_additional' => count($additional_images),
        'can_add_more' => count($additional_images) < 3
    ]);

} catch (Exception $e) {
    error_log("Error getting product images: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
