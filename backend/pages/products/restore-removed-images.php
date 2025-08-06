<?php
session_start();
require_once '../admin-includes/database.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get parameters
$productId = $_POST['product_id'] ?? '';

if (empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

// Get all removed images for this product
$stmt = $conn->prepare("SELECT id, image_url, temp_filename FROM product_images WHERE product_id = ? AND is_removed = 1 AND temp_filename IS NOT NULL");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

$restoredImages = [];
$errors = [];

while ($image = $result->fetch_assoc()) {
    // Construct paths
    $tempFilePath = dirname(dirname(dirname(__DIR__))) . "/assets/product-images/1_TEMP_IMAGES/" . $image['temp_filename'];
    $originalFilePath = dirname(dirname(dirname(__DIR__))) . "/assets/" . $image['image_url'];
    
    // Check if temp file exists
    if (!file_exists($tempFilePath)) {
        $errors[] = "Temp file not found for image ID: " . $image['id'];
        continue;
    }
    
    // Create original directory if it doesn't exist
    $originalDir = dirname($originalFilePath);
    if (!is_dir($originalDir)) {
        mkdir($originalDir, 0755, true);
    }
    
    // Move file back to original location
    if (rename($tempFilePath, $originalFilePath)) {
        // Update database to mark as not removed
        $updateStmt = $conn->prepare("UPDATE product_images SET is_removed = 0, temp_filename = NULL WHERE id = ?");
        $updateStmt->bind_param("i", $image['id']);
        
        if ($updateStmt->execute()) {
            $restoredImages[] = $image['id'];
        } else {
            $errors[] = "Failed to update database for image ID: " . $image['id'];
            // Move file back to temp if database update fails
            rename($originalFilePath, $tempFilePath);
        }
    } else {
        $errors[] = "Failed to restore image ID: " . $image['id'];
    }
}

if (empty($errors)) {
    echo json_encode([
        'success' => true,
        'message' => 'All removed images restored successfully',
        'restored_images' => $restoredImages
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Some images could not be restored',
        'errors' => $errors,
        'restored_images' => $restoredImages
    ]);
}
?>
