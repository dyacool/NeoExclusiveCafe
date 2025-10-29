<?php
/**
 * Manage Additional Product Images - Cloudinary Integration
 * 
 * This endpoint handles:
 * - Adding new additional images (up to 3 total)
 * - Removing specific additional images from Cloudinary
 * - Updating the cloudinary_additional_images JSON array
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

// Include required files
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../includes/cloudinary-helper.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get action type
    $action = $_POST['action'] ?? '';
    
    if (!in_array($action, ['add', 'remove'])) {
        throw new Exception('Invalid action. Must be "add" or "remove"');
    }
    
    // Validate product ID
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        throw new Exception('Invalid product ID');
    }
    
    $product_id = (int)$_POST['product_id'];
    
    // Handle ADD action
    if ($action === 'add') {
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
        
        // Check current count of additional images
        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ? AND is_primary = 0 AND is_removed = 0");
        $count_stmt->bind_param("i", $product_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $current_count = $count_result->fetch_assoc()['count'];
        $count_stmt->close();
        
        if ($current_count >= 3) {
            throw new Exception('Maximum of 3 additional images allowed');
        }
        
        // Generate public ID for new image
        $timestamp = time();
        $image_number = $current_count + 1;
        $public_id = 'product_' . $product_id . '_additional_' . $image_number . '_' . $timestamp;
        
        // Upload to Cloudinary
        $cloudinary_result = uploadToCloudinary(
            $uploaded_file['tmp_name'],
            'neocafe/products',
            $public_id
        );
        
        if (!$cloudinary_result['success']) {
            throw new Exception('Cloudinary upload failed: ' . $cloudinary_result['error']);
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO product_images (product_id, cloud_url, cloud_public_id, cloud_provider, is_primary, is_removed) VALUES (?, ?, ?, 'cloudinary', 0, 0)");
        $stmt->bind_param("iss", $product_id, $cloudinary_result['url'], $cloudinary_result['public_id']);
        
        if (!$stmt->execute()) {
            // If database insert fails, delete from Cloudinary
            deleteFromCloudinary($cloudinary_result['public_id']);
            throw new Exception('Failed to save image to database');
        }
        
        $image_id = $stmt->insert_id;
        $stmt->close();
        
        // Delete temporary file
        @unlink($uploaded_file['tmp_name']);
        
        // Log activity
        logAdminActivity($conn, 'INSERT', "Added additional image for product ID: $product_id", 'product_images', $image_id);
        
        echo json_encode([
            'success' => true,
            'image' => [
                'id' => $image_id,
                'url' => $cloudinary_result['url'],
                'public_id' => $cloudinary_result['public_id'],
                'is_primary' => false
            ],
            'message' => 'Additional image added successfully'
        ]);
        
    } 
    // Handle REMOVE action
    else if ($action === 'remove') {
        // Validate image ID
        if (!isset($_POST['image_id']) || !is_numeric($_POST['image_id'])) {
            throw new Exception('Invalid image ID');
        }
        
        $image_id = (int)$_POST['image_id'];
        
        // Get image data
        $stmt = $conn->prepare("SELECT cloud_url, cloud_public_id, is_primary FROM product_images WHERE id = ? AND product_id = ?");
        $stmt->bind_param("ii", $image_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Image not found');
        }
        
        $image_data = $result->fetch_assoc();
        $stmt->close();
        
        // Prevent removal of primary images through this endpoint
        if ($image_data['is_primary']) {
            throw new Exception('Cannot remove primary image through this endpoint');
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Mark image as removed in database
            $stmt = $conn->prepare("UPDATE product_images SET is_removed = 1 WHERE id = ?");
            $stmt->bind_param("i", $image_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to remove image from database');
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Delete from Cloudinary
            if ($image_data['cloud_public_id']) {
                $delete_result = deleteFromCloudinary($image_data['cloud_public_id']);
                if (!$delete_result) {
                    error_log("Warning: Failed to delete image from Cloudinary: " . $image_data['cloud_public_id']);
                } else {
                    error_log("Successfully deleted image from Cloudinary: " . $image_data['cloud_public_id']);
                }
            }
            
            // Log activity
            logAdminActivity($conn, 'DELETE', "Removed additional image for product ID: $product_id", 'product_images', $image_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Additional image removed successfully'
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            throw $e;
        }
    }

} catch (Exception $e) {
    error_log("Error managing additional images: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
