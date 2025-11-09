<?php
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

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        throw new Exception('Invalid product ID');
    }

    $product_id = (int)$_POST['product_id'];
    $is_primary = false;
    $uploaded_file = null;

    // Check if it's a primary image or additional image
    if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['primary_image'];
        $is_primary = true;
    } elseif (isset($_FILES['additional_image']) && $_FILES['additional_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['additional_image'];
        $is_primary = false;
    } else {
        throw new Exception('No valid image uploaded');
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/jfif'];
    if (!in_array($uploaded_file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, WebP, and JFIF are allowed.');
    }

    // Verify product exists
    $product_sql = "SELECT name FROM products WHERE id = ?";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $product_data = $product_result->fetch_assoc();

    if (!$product_data) {
        throw new Exception('Product not found');
    }

    // Upload directly to Cloudinary (no local storage)
    require_once __DIR__ . '/../../../backend/includes/cloudinary-helper.php';
    
    $timestamp = time();
    $image_type = $is_primary ? 'primary' : 'additional';
    $publicId = 'product_' . $product_id . '_' . $image_type . '_' . $timestamp;
    
    // Log: Uploading directly to Cloudinary without local storage
    error_log("Uploading image directly to Cloudinary for product $product_id (type: $image_type, public_id: $publicId)");
    
    $cloudinaryResult = uploadToCloudinary($uploaded_file['tmp_name'], 'neocafe/products', $publicId);
    
    if ($cloudinaryResult['success']) {
        // Begin transaction for database operations
        $conn->begin_transaction();
        
        try {
            // Store only Cloudinary URL (no local path)
            $insert_sql = "INSERT INTO product_images (product_id, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, ?, ?, 'cloudinary', ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issi", $product_id, $cloudinaryResult['url'], $cloudinaryResult['public_id'], $is_primary);
            
            if (!$insert_stmt->execute()) {
                throw new Exception('Failed to save image to database: ' . $insert_stmt->error);
            }
            
            $image_id = $insert_stmt->insert_id;
            
            // Commit transaction
            $conn->commit();
            
            // Delete temporary file after successful Cloudinary upload and database save
            if (file_exists($uploaded_file['tmp_name'])) {
                @unlink($uploaded_file['tmp_name']);
                error_log("Deleted temporary file for product $product_id after successful upload");
            }
            
            error_log("Successfully uploaded and saved image for product $product_id (Image ID: $image_id, Public ID: {$cloudinaryResult['public_id']})");
            
            echo json_encode([
                'success' => true,
                'image' => [
                    'id' => $image_id,
                    'image_url' => $cloudinaryResult['url'], // Return Cloudinary URL for display
                    'is_primary' => $is_primary
                ]
            ]);
        } catch (Exception $e) {
            // Rollback database transaction
            $conn->rollback();
            
            // Delete the uploaded file from Cloudinary since database save failed
            $deleteResult = deleteFromCloudinary($cloudinaryResult['public_id']);
            if (!$deleteResult['success']) {
                error_log("Failed to rollback Cloudinary upload for {$cloudinaryResult['public_id']}: " . ($deleteResult['error'] ?? 'Unknown error'));
            }
            
            // Delete temporary file
            if (file_exists($uploaded_file['tmp_name'])) {
                @unlink($uploaded_file['tmp_name']);
            }
            
            throw $e;
        }
    } else {
        // Cloudinary upload failed - delete temporary file and throw exception
        if (file_exists($uploaded_file['tmp_name'])) {
            @unlink($uploaded_file['tmp_name']);
            error_log("Deleted temporary file after Cloudinary upload failure for product $product_id");
        }
        
        $errorMessage = $cloudinaryResult['error'] ?? 'Unknown error';
        $errorCode = $cloudinaryResult['error_code'] ?? 'UNKNOWN';
        
        error_log("Cloudinary upload failed for product $product_id: $errorMessage (Code: $errorCode)");
        
        throw new Exception("Failed to upload image: $errorMessage");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
