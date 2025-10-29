<?php
/**
 * AJAX Upload Endpoint for Product Images
 * 
 * This endpoint handles real-time image uploads to Cloudinary via AJAX.
 * Images are uploaded immediately when selected and tracked in temp_uploaded_images
 * table until they are associated with a saved product.
 */

session_start();
header('Content-Type: application/json');

// Verify admin authentication
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
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
 * @return bool Success status
 */
function logTempImageUpload($conn, $publicId, $url) {
    try {
        $stmt = $conn->prepare("INSERT INTO temp_uploaded_images (public_id, cloud_url, uploaded_at) VALUES (?, ?, NOW())");
        if (!$stmt) {
            error_log("Failed to prepare statement for temp image logging: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("ss", $publicId, $url);
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

// Get image type (primary or additional)
$imageType = $_POST['image_type'] ?? 'additional';
if (!in_array($imageType, ['primary', 'additional'])) {
    $imageType = 'additional';
}

// Get product name for folder structure (sanitized)
$productName = $_POST['product_name'] ?? 'Unnamed_Product';
$sanitizedProductName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productName);
$sanitizedProductName = preg_replace('/_+/', '_', $sanitizedProductName);
$sanitizedProductName = trim($sanitizedProductName, '_');

// Generate unique folder name with product name and timestamp
$timestamp = time();
$folderName = $sanitizedProductName . '_' . $timestamp;

// Generate public ID: assets/product-images/[ProductName_timestamp]/[image_type]_[timestamp]
$imageFilename = ($imageType === 'primary' ? 'primary' : 'additional_' . ($timestamp + rand(1, 999))) . '_' . $timestamp;
$publicId = 'assets/product-images/' . $folderName . '/' . $imageFilename;

try {
    // Upload to Cloudinary (folder is included in public_id)
    $result = uploadToCloudinary(
        $_FILES['image']['tmp_name'],
        null, // No folder parameter since it's in the public_id
        $publicId
    );
    
    if ($result['success']) {
        // Log the upload for orphan cleanup tracking
        $logged = logTempImageUpload($conn, $result['public_id'], $result['url']);
        
        if (!$logged) {
            error_log("Warning: Failed to log temp image upload for tracking. Public ID: " . $result['public_id']);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'format' => $result['format'] ?? null,
            'bytes' => $result['bytes'] ?? null,
            'image_type' => $imageType
        ]);
        
        error_log("AJAX upload successful: " . $result['public_id'] . " (Type: $imageType)");
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

// Delete temporary file
@unlink($_FILES['image']['tmp_name']);

$conn->close();
?>
