<?php
/**
 * AJAX Upload Endpoint for Product Images
 * 
 * This endpoint handles real-time image uploads to Cloudinary via AJAX.
 * Images are uploaded immediately when selected and tracked in temp_uploaded_images
 * table until they are associated with a saved product.
 */

// Start output buffering to prevent any accidental output
ob_start();

try {
    session_start();
} catch (Exception $e) {
    error_log("Session start failed: " . $e->getMessage());
}

// Clean any previous output and set headers
ob_clean();
header('Content-Type: application/json');

// Enable error reporting for debugging but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../includes/session-manager.php';

// Verify admin authentication
if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please log in as admin.'
    ]);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid CSRF token. Please refresh the page and try again.'
    ]);
    exit();
}

require_once __DIR__ . '/../includes/cloudinary-helper.php';
require_once __DIR__ . '/../pages/admin-includes/database.php';

/**
 * Log temporary image upload for orphan tracking
 * 
 * @param mysqli $conn Database connection
 * @param string $publicId Cloudinary public ID
 * @param string $url Cloudinary URL
 * @param string $moderationStatus Initial moderation status (default: 'pending')
 * @return bool Success status
 */
function logTempImageUpload($conn, $publicId, $url, $moderationStatus = 'pending') {
    try {
        $stmt = $conn->prepare("INSERT INTO temp_uploaded_images (public_id, cloud_url, uploaded_at, moderation_status) VALUES (?, ?, NOW(), ?)");
        if (!$stmt) {
            error_log("Failed to prepare statement for temp image logging: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("sss", $publicId, $url, $moderationStatus);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Failed to log temp image upload: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    } catch (Exception $e) {
        error_log("Exception logging temp image upload: " . $e->getMessage());
        return false;
    }
}

// Validate uploaded file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'No file uploaded';
    
    if (isset($_FILES['image']['error'])) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'File size exceeds maximum limit';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage = 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage = 'File upload stopped by extension';
                break;
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ]);
    exit();
}

// Validate image file
$validation = validateImageFile($_FILES['image']['tmp_name']);
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $validation['error']
    ]);
    exit();
}

// Resize image if dimensions exceed 5000x5000 (optional - only if GD available)
$fileToUpload = $_FILES['image']['tmp_name'];
$resizeResult = ['success' => true, 'resized' => false, 'file_path' => $fileToUpload];

// Check if image needs resizing
if ($validation['needs_resize']) {
    try {
        $resizeResult = resizeImageIfNeeded($fileToUpload, 5000, 5000);
        
        if ($resizeResult['success'] && $resizeResult['resized']) {
            // Use resized image
            $fileToUpload = $resizeResult['file_path'];
            error_log("Image was resized from {$resizeResult['original_width']}x{$resizeResult['original_height']} to {$resizeResult['new_width']}x{$resizeResult['new_height']}");
        } else if (!$resizeResult['success']) {
            // Resize failed, but continue with original image
            error_log("Image resize failed: " . ($resizeResult['error'] ?? 'Unknown error') . ". Uploading original image.");
            $fileToUpload = $_FILES['image']['tmp_name'];
        }
    } catch (Exception $e) {
        error_log("Image resize exception: " . $e->getMessage() . ". Uploading original image.");
        // Continue with original file if resize fails
        $fileToUpload = $_FILES['image']['tmp_name'];
    }
}

// Get image type (primary or additional)
$imageType = $_POST['image_type'] ?? 'additional';
if (!in_array($imageType, ['primary', 'additional'])) {
    $imageType = 'additional';
}

// Get product name for folder structure (sanitized)
$productName = $_POST['product_name'] ?? 'Unnamed_Product';

// Log what we received
error_log("AJAX Upload - Received product_name: " . $productName);

$sanitizedProductName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productName);
$sanitizedProductName = preg_replace('/_+/', '_', $sanitizedProductName);
$sanitizedProductName = trim($sanitizedProductName, '_');

// If sanitization resulted in empty string, use default
if (empty($sanitizedProductName)) {
    $sanitizedProductName = 'Unnamed_Product';
}

// Generate unique folder name with product name and timestamp
$timestamp = time();
$folderName = $sanitizedProductName . '_' . $timestamp;

error_log("AJAX Upload - Folder name: " . $folderName);

// Folder path: assets/product-images/[ProductName_timestamp]
$folder = 'assets/product-images/' . $folderName;

// Generate simple filename for public_id (folder is separate)
$imageFilename = ($imageType === 'primary' ? 'primary' : 'additional_' . ($timestamp + rand(1, 999))) . '_' . $timestamp;

try {
    // Upload to Cloudinary with folder and filename separate
    $result = uploadToCloudinary(
        $fileToUpload, // Use potentially resized image
        $folder, // Folder path: assets/product-images/ProductName_timestamp
        $imageFilename // Just the filename, not the full path
    );
    
    if ($result['success']) {
        // Check if moderation is enabled
        require_once __DIR__ . '/../includes/cloudinary-moderation-helper.php';
        $moderationHelper = new CloudinaryModerationHelper($conn);
        $moderationEnabled = $moderationHelper->isModerationEnabled();
        
        // Determine initial moderation status
        $moderationStatus = 'pending'; // Default for async moderation
        
        // Log the upload for orphan cleanup tracking
        $logged = logTempImageUpload($conn, $result['public_id'], $result['url'], $moderationStatus);
        
        if (!$logged) {
            error_log("Warning: Failed to log temp image upload for tracking. Public ID: " . $result['public_id']);
        }
        
        // Return success response with moderation info
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'format' => $result['format'] ?? null,
            'bytes' => $result['bytes'] ?? null,
            'image_type' => $imageType,
            'moderation_enabled' => $moderationEnabled,
            'moderation_status' => $moderationStatus,
            'moderation_message' => $moderationEnabled ? 'Image uploaded. Safety check in progress...' : null
        ]);
        
        error_log("AJAX upload successful: " . $result['public_id'] . " (Type: $imageType, Moderation: " . ($moderationEnabled ? 'enabled' : 'disabled') . ")");
    } else {
        // Upload failed
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Upload failed',
            'error_code' => $result['error_code'] ?? 'UNKNOWN'
        ]);
        
        error_log("AJAX upload failed: " . ($result['error'] ?? 'Unknown error'));
    }
} catch (Exception $e) {
    error_log('AJAX upload exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Upload failed: ' . $e->getMessage()
    ]);
}

// Delete temporary files
@unlink($_FILES['image']['tmp_name']);
if (isset($resizeResult) && $resizeResult['resized'] && isset($resizeResult['file_path'])) {
    @unlink($resizeResult['file_path']);
}

$conn->close();
?>
