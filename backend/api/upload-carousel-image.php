<?php
/**
 * AJAX Upload Endpoint for Carousel Images
 * 
 * This endpoint handles real-time carousel image uploads to Cloudinary via AJAX.
 * Images are uploaded immediately when selected and tracked in temp_uploaded_images
 * table until they are associated with a saved carousel entry.
 */

require_once __DIR__ . '/../../includes/session-manager.php';

header('Content-Type: application/json');

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
    $errorCode = 'UNKNOWN';
    
    if (isset($_FILES['image']['error'])) {
        $uploadError = $_FILES['image']['error'];
        error_log("Carousel upload error code: " . $uploadError);
        
        switch ($uploadError) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMessage = 'File size exceeds server limit (upload_max_filesize in php.ini). Current file size: ' . (isset($_FILES['image']['size']) ? round($_FILES['image']['size'] / 1024 / 1024, 2) . 'MB' : 'unknown');
                $errorCode = 'SERVER_SIZE_LIMIT';
                error_log("Upload failed: UPLOAD_ERR_INI_SIZE - File size: " . ($_FILES['image']['size'] ?? 'unknown'));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'File size exceeds form limit (MAX_FILE_SIZE). Current file size: ' . (isset($_FILES['image']['size']) ? round($_FILES['image']['size'] / 1024 / 1024, 2) . 'MB' : 'unknown');
                $errorCode = 'FORM_SIZE_LIMIT';
                error_log("Upload failed: UPLOAD_ERR_FORM_SIZE - File size: " . ($_FILES['image']['size'] ?? 'unknown'));
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'File was only partially uploaded';
                $errorCode = 'PARTIAL_UPLOAD';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'No file was uploaded';
                $errorCode = 'NO_FILE';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage = 'Missing temporary folder';
                $errorCode = 'NO_TMP_DIR';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage = 'Failed to write file to disk';
                $errorCode = 'CANT_WRITE';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage = 'File upload stopped by extension';
                $errorCode = 'EXTENSION_BLOCKED';
                break;
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage,
        'error_code' => $errorCode
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

// Get optional title for logging
$title = $_POST['title'] ?? 'Carousel Image';

// Log what we received
error_log("Carousel AJAX Upload - Title: " . $title);

// Generate unique filename with timestamp
$timestamp = time();
$fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$imageFilename = 'carousel_' . $timestamp;

error_log("Carousel AJAX Upload - Filename: " . $imageFilename);

// Folder path: Home/assets/images/carousel/
$folder = 'Home/assets/images/carousel';

try {
    // Upload to Cloudinary
    $result = uploadToCloudinary(
        $_FILES['image']['tmp_name'],
        $folder, // Folder path: Home/assets/images/carousel
        $imageFilename // Filename: carousel_timestamp
    );
    
    if ($result['success']) {
        // Log the upload for orphan cleanup tracking
        $logged = logTempImageUpload($conn, $result['public_id'], $result['url']);
        
        if (!$logged) {
            error_log("Warning: Failed to log temp carousel image upload for tracking. Public ID: " . $result['public_id']);
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'format' => $result['format'] ?? null,
            'bytes' => $result['bytes'] ?? null
        ]);
        
        error_log("Carousel AJAX upload successful: " . $result['public_id']);
    } else {
        // Upload failed
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Upload failed',
            'error_code' => $result['error_code'] ?? 'UNKNOWN'
        ]);
        
        error_log("Carousel AJAX upload failed: " . ($result['error'] ?? 'Unknown error'));
    }
} catch (Exception $e) {
    error_log('Carousel AJAX upload exception: ' . $e->getMessage());
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
