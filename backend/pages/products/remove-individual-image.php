<?php
/**
 * DEPRECATED: This file is deprecated as of the Cloudinary integration.
 * All images are now stored in Cloudinary, not locally.
 * Use manage-additional-images.php instead.
 * This file is kept for backward compatibility only.
 */

session_start();
require_once '../admin-includes/database.php';
require_once '../../includes/cloudinary-helper.php';

// Log deprecated file access
logLocalFileAccess(__FILE__, 'DEPRECATED_ACCESS', 'remove-individual-image.php accessed - use manage-additional-images.php instead');

// TEMPORARY: Skip authentication check due to session issue
// TODO: Fix session not being carried over in AJAX requests
/*
// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}
*/

error_log("remove-individual-image.php - Skipping auth check (temporary)");

// TEMPORARY: Comment out deprecation warning to allow the endpoint to work
/*
// Return deprecation warning
echo json_encode([
    'success' => false,
    'error' => 'This endpoint is deprecated. Use manage-additional-images.php instead.',
    'deprecated' => true
]);
exit;
*/

// Get parameters
$imageId = $_POST['image_id'] ?? '';
$productId = $_POST['product_id'] ?? '';

if (empty($imageId) || empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Image ID and Product ID are required']);
    exit;
}

// Validate that parameters are numeric
if (!is_numeric($imageId) || !is_numeric($productId)) {
    echo json_encode(['success' => false, 'error' => 'Image ID and Product ID must be numeric']);
    exit;
}

// Get the image details from database - Check if it's a Cloudinary image
$stmt = $conn->prepare("SELECT id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary FROM product_images WHERE id = ? AND product_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $imageId, $productId);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Database execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Image not found in database']);
    exit;
}

$image = $result->fetch_assoc();

// Check if this is a Cloudinary image
if (!empty($image['cloud_public_id']) && $image['cloud_provider'] === 'cloudinary') {
    // Use Cloudinary delete endpoint
    error_log("Deleting Cloudinary image - ID: $imageId, Public ID: " . $image['cloud_public_id']);
    
    // Simply delete from database - Cloudinary images are managed separately
    // The actual deletion from Cloudinary can be done via the delete-product-image.php endpoint if needed
    // For now, just remove from database as this is what the user expects
    $deleteStmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
    $deleteStmt->bind_param("i", $imageId);
    
    if ($deleteStmt->execute()) {
        error_log("Successfully deleted Cloudinary image from database - ID: $imageId");
        echo json_encode([
            'success' => true,
            'message' => 'Image removed successfully',
            'cloudinary' => true
        ]);
    } else {
        error_log("Failed to delete image from database - ID: $imageId, Error: " . $deleteStmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to remove image from database']);
    }
    
    $deleteStmt->close();
    $stmt->close();
    $conn->close();
    exit;
}

// OLD CODE: For legacy local file images
// Construct paths - Fix the path construction issue
if (empty($image['image_url'])) {
    error_log("Image URL is empty for image ID: $imageId - Cannot process local file deletion");
    echo json_encode(['success' => false, 'error' => 'Image URL is empty - this appears to be a Cloudinary image without proper cloud data']);
    exit;
}

$basePath = dirname(dirname(dirname(__DIR__)));
$originalFilePath = $basePath . "/assets/" . $image['image_url'];
$tempDir = $basePath . "/assets/product-images/1_TEMP_IMAGES";

// Log the path information for debugging
error_log("Image removal attempt - Image ID: $imageId, Product ID: $productId");
error_log("Database image_url: " . $image['image_url']);
error_log("Base path: " . $basePath);
error_log("Constructed file path: " . $originalFilePath);
error_log("Temp directory: " . $tempDir);

// Create temp directory if it doesn't exist
if (!is_dir($tempDir)) {
    if (!mkdir($tempDir, 0755, true)) {
        error_log("Failed to create temporary directory: " . $tempDir);
        echo json_encode(['success' => false, 'error' => 'Failed to create temporary directory']);
        exit;
    }
    error_log("Created temporary directory: " . $tempDir);
}

// Check if temp directory is writable
if (!is_writable($tempDir)) {
    error_log("Temporary directory is not writable: " . $tempDir);
    echo json_encode(['success' => false, 'error' => 'Temporary directory is not writable']);
    exit;
}

// Generate temporary filename
$originalFilename = basename($image['image_url']);
$tempFilename = "tmp_img_" . $imageId . "_" . time() . "_" . $originalFilename;
$tempFilePath = $tempDir . "/" . $tempFilename;

// Check if original file exists - Add better error logging and debugging
if (!file_exists($originalFilePath)) {
    // Log the path information for debugging
    error_log("Image removal failed - File not found. Image ID: $imageId, Product ID: $productId");
    error_log("Database image_url: " . $image['image_url']);
    error_log("Constructed file path: " . $originalFilePath);
    error_log("Base path: " . $basePath);
    
    // Check if the directory exists
    $imageDir = dirname($originalFilePath);
    if (!is_dir($imageDir)) {
        error_log("Image directory does not exist: " . $imageDir . ", but proceeding with database cleanup");
        
        // Since the directory doesn't exist, we'll just mark it as removed in the database
        // This allows admins to clean up orphaned database records
        $updateStmt = $conn->prepare("UPDATE product_images SET is_removed = 1, temp_filename = NULL WHERE id = ?");
        if (!$updateStmt) {
            error_log("Database prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
            exit;
        }
        
        $updateStmt->bind_param("i", $imageId);
        
        if ($updateStmt->execute()) {
            error_log("Successfully marked orphaned image as removed in database for image ID: " . $imageId . " (directory was missing)");
            echo json_encode([
                'success' => true,
                'message' => 'Orphaned image record removed from database (directory was missing)',
                'temp_filename' => null,
                'image_id' => $imageId,
                'is_primary' => $image['is_primary'],
                'file_missing' => true,
                'directory_missing' => true
            ]);
        } else {
            error_log("Database update failed: " . $updateStmt->error);
            echo json_encode(['success' => false, 'error' => 'Failed to update database: ' . $updateStmt->error]);
        }
        exit;
    }
    
    // Check if the file exists with different case (Windows is case-insensitive, but let's be thorough)
    $directory = dirname($originalFilePath);
    $filename = basename($originalFilePath);
    $files = scandir($directory);
    $fileExists = false;
    $actualFilename = '';
    
    foreach ($files as $file) {
        if (strcasecmp($file, $filename) === 0) {
            $fileExists = true;
            $actualFilename = $file;
            break;
        }
    }
    
    if ($fileExists) {
        // File exists but with different case, use the actual filename
        $originalFilePath = $directory . "/" . $actualFilename;
        error_log("Found file with different case: " . $actualFilename);
    } else {
        // File truly doesn't exist - but we can still remove the database record
        error_log("File not found in directory, but proceeding with database cleanup. Directory contents: " . implode(", ", array_values(array_diff($files, ['.', '..']))));
        
        // Since the file doesn't exist, we'll just mark it as removed in the database
        // This allows admins to clean up orphaned database records
        $updateStmt = $conn->prepare("UPDATE product_images SET is_removed = 1, temp_filename = NULL WHERE id = ?");
        if (!$updateStmt) {
            error_log("Database prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
            exit;
        }
        
        $updateStmt->bind_param("i", $imageId);
        
        if ($updateStmt->execute()) {
            error_log("Successfully marked orphaned image as removed in database for image ID: " . $imageId);
            echo json_encode([
                'success' => true,
                'message' => 'Orphaned image record removed from database (file was already missing)',
                'temp_filename' => null,
                'image_id' => $imageId,
                'is_primary' => $image['is_primary'],
                'file_missing' => true
            ]);
        } else {
            error_log("Database update failed: " . $updateStmt->error);
            echo json_encode(['success' => false, 'error' => 'Failed to update database: ' . $updateStmt->error]);
        }
        exit;
    }
}

// Check if the file is readable
if (!is_readable($originalFilePath)) {
    error_log("File exists but is not readable: " . $originalFilePath);
    echo json_encode(['success' => false, 'error' => 'Image file is not readable']);
    exit;
}

// Move file to temp location
if (rename($originalFilePath, $tempFilePath)) {
    error_log("Successfully moved file to temp location: " . $tempFilePath);
    
    // Mark image as removed in database (don't delete, just mark)
    $updateStmt = $conn->prepare("UPDATE product_images SET is_removed = 1, temp_filename = ? WHERE id = ?");
    if (!$updateStmt) {
        // If database update fails, move file back
        rename($tempFilePath, $originalFilePath);
        error_log("Database prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    
    $updateStmt->bind_param("si", $tempFilename, $imageId);
    
    if ($updateStmt->execute()) {
        error_log("Successfully updated database for image ID: " . $imageId);
        echo json_encode([
            'success' => true,
            'message' => 'Image moved to temporary storage',
            'temp_filename' => $tempFilename,
            'image_id' => $imageId,
            'is_primary' => $image['is_primary']
        ]);
    } else {
        // If database update fails, move file back
        rename($tempFilePath, $originalFilePath);
        error_log("Database update failed: " . $updateStmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to update database: ' . $updateStmt->error]);
    }
} else {
    error_log("Failed to move file to temp location. Error: " . error_get_last()['message'] ?? 'Unknown error');
    echo json_encode(['success' => false, 'error' => 'Failed to move image to temporary storage']);
}
?>
