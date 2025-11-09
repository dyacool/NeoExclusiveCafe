<?php
/**
 * Replace Product Image - Cloudinary Integration
 * 
 * This endpoint handles replacing existing product images with new ones.
 * - Uploads new image to Cloudinary
 * - Deletes old image from Cloudinary
 * - Updates database with new Cloudinary URL
 * - Handles both primary and additional images
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

if (!SessionManager::isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Include required files
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../includes/cloudinary-helper.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        throw new Exception('Invalid product ID');
    }

    $product_id = (int)$_POST['product_id'];
    $image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : null;
    $is_primary = isset($_POST['is_primary']) ? (bool)$_POST['is_primary'] : false;
    
    // Validate uploaded file
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No valid image uploaded');
    }

    $uploaded_file = $_FILES['image'];

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_info = @getimagesize($uploaded_file['tmp_name']);
    
    if ($file_info === false) {
        throw new Exception('File is not a valid image');
    }
    
    if (!in_array($file_info['mime'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed');
    }
    
    // Validate file size (max 10MB)
    if ($uploaded_file['size'] > 10 * 1024 * 1024) {
        throw new Exception('File size exceeds 10MB limit');
    }

    // Get existing image data if image_id is provided
    $old_cloudinary_url = null;
    $old_public_id = null;
    
    if ($image_id) {
        $stmt = $conn->prepare("SELECT cloud_url, cloud_public_id FROM product_images WHERE id = ? AND product_id = ?");
        $stmt->bind_param("ii", $image_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $image_data = $result->fetch_assoc();
            $old_cloudinary_url = $image_data['cloud_url'];
            $old_public_id = $image_data['cloud_public_id'];
        }
        $stmt->close();
    }

    // Generate public ID for new image
    $timestamp = time();
    $image_type = $is_primary ? 'primary' : 'additional';
    $public_id = 'product_' . $product_id . '_' . $image_type . '_' . $timestamp;

    // Upload new image to Cloudinary
    $cloudinary_result = uploadToCloudinary(
        $uploaded_file['tmp_name'],
        'neocafe/products',
        $public_id
    );

    if (!$cloudinary_result['success']) {
        throw new Exception('Cloudinary upload failed: ' . $cloudinary_result['error']);
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        if ($image_id) {
            // Update existing image record
            $stmt = $conn->prepare("UPDATE product_images SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND product_id = ?");
            $stmt->bind_param("ssii", $cloudinary_result['url'], $cloudinary_result['public_id'], $image_id, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update image in database');
            }
            $stmt->close();
        } else {
            // Insert new image record
            $stmt = $conn->prepare("INSERT INTO product_images (product_id, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, ?, ?, 'cloudinary', ?)");
            $stmt->bind_param("issi", $product_id, $cloudinary_result['url'], $cloudinary_result['public_id'], $is_primary);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert image into database');
            }
            
            $image_id = $stmt->insert_id;
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();

        // Delete old image from Cloudinary (if it exists and is different)
        if ($old_public_id && $old_public_id !== $cloudinary_result['public_id']) {
            $delete_result = deleteFromCloudinary($old_public_id);
            if (!$delete_result['success']) {
                error_log("Warning: Failed to delete old image from Cloudinary: " . $old_public_id . " - " . ($delete_result['error'] ?? 'Unknown error'));
            } else {
                error_log("Successfully deleted old image from Cloudinary: " . $old_public_id);
            }
        }

        // Delete temporary file
        @unlink($uploaded_file['tmp_name']);

        // Log activity
        $action = $image_id ? 'replaced' : 'added';
        logAdminActivity($conn, 'UPDATE', "Image $action for product ID: $product_id", 'product_images', $image_id);

        echo json_encode([
            'success' => true,
            'image' => [
                'id' => $image_id,
                'url' => $cloudinary_result['url'],
                'public_id' => $cloudinary_result['public_id'],
                'is_primary' => $is_primary
            ],
            'message' => 'Image replaced successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Try to delete the newly uploaded image from Cloudinary since DB update failed
        if (isset($cloudinary_result['public_id'])) {
            deleteFromCloudinary($cloudinary_result['public_id']);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error replacing product image: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
