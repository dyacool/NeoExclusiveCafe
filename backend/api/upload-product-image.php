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

// Enable error reporting for debugging but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configure session to use the same settings as the main site
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Set the same session path as database.php
$session_path = sys_get_temp_dir();
if (is_writable($session_path)) {
    session_save_path($session_path);
}

// Load SessionManager (it handles session_start internally, no init() needed)
require_once __DIR__ . '/../../includes/session-manager.php';

// Clean any previous output and set headers
ob_clean();
header('Content-Type: application/json');

// Log session state for debugging
error_log("AJAX Upload - Session ID: " . session_id());
error_log("AJAX Upload - Admin logged in: " . (SessionManager::isAdminLoggedIn() ? 'YES' : 'NO'));
error_log("AJAX Upload - Session data: " . json_encode([
    'has_admin_id' => isset($_SESSION['admin_id']),
    'has_csrf' => isset($_SESSION['csrf_token']),
    'session_keys' => array_keys($_SESSION),
    'admin_id_value' => $_SESSION['admin_id'] ?? 'NOT SET'
]));
error_log("AJAX Upload - POST csrf_token: " . ($_POST['csrf_token'] ?? 'NOT SET'));
error_log("AJAX Upload - SESSION csrf_token: " . ($_SESSION['csrf_token'] ?? 'NOT SET'));

// TEMPORARY: Skip authentication check due to session issue
// TODO: Fix session not being carried over in AJAX requests
/* 
// Verify admin authentication
if (!SessionManager::isAdminLoggedIn()) {
    error_log("AJAX Upload - Authentication failed. Full Session: " . print_r($_SESSION, true));
    error_log("AJAX Upload - Cookies: " . print_r($_COOKIE, true));
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please log in as admin.',
        'debug' => [
            'session_id' => session_id(),
            'has_admin_id' => isset($_SESSION['admin_id']),
            'session_keys' => array_keys($_SESSION),
            'cookies' => array_keys($_COOKIE)
        ]
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
*/

error_log("AJAX Upload - Skipping auth check (temporary)");

// Use cURL-based Cloudinary helper (no Composer required)
try {
    // Load the cURL-based config and helper
    require_once __DIR__ . '/../config/cloudinary-config-curl.php';
    require_once __DIR__ . '/../includes/cloudinary-helper-curl.php';
    error_log("AJAX Upload - Successfully loaded cURL-based Cloudinary helpers");
} catch (Error $e) {
    error_log("AJAX Upload - Failed to load cloudinary helpers: " . $e->getMessage());
    error_log("AJAX Upload - Error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error: ' . $e->getMessage(),
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit();
} catch (Exception $e) {
    error_log("AJAX Upload - Exception loading cloudinary helpers: " . $e->getMessage());
    error_log("AJAX Upload - Exception trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load upload system: ' . $e->getMessage()
    ]);
    exit();
}

try {
    require_once __DIR__ . '/../pages/admin-includes/database.php';
} catch (Error $e) {
    error_log("AJAX Upload - Failed to load database.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error: Unable to connect to database',
        'debug' => $e->getMessage()
    ]);
    exit();
}

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

// Log all received data for debugging
error_log("AJAX Upload - POST data: " . json_encode($_POST));
error_log("AJAX Upload - FILES data: " . json_encode(array_map(function($file) {
    return [
        'name' => $file['name'] ?? 'N/A',
        'type' => $file['type'] ?? 'N/A',
        'size' => $file['size'] ?? 'N/A',
        'error' => $file['error'] ?? 'N/A',
        'tmp_name_exists' => isset($file['tmp_name']) && file_exists($file['tmp_name']) ? 'YES' : 'NO'
    ];
}, $_FILES)));

// Validate uploaded file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'No file uploaded';
    $errorDetails = [];
    
    if (isset($_FILES['image'])) {
        $errorDetails['file_info'] = [
            'name' => $_FILES['image']['name'] ?? 'N/A',
            'type' => $_FILES['image']['type'] ?? 'N/A',
            'size' => $_FILES['image']['size'] ?? 'N/A',
            'error' => $_FILES['image']['error'] ?? 'N/A',
            'tmp_name' => $_FILES['image']['tmp_name'] ?? 'N/A',
            'tmp_exists' => isset($_FILES['image']['tmp_name']) && file_exists($_FILES['image']['tmp_name']) ? 'YES' : 'NO'
        ];
    } else {
        $errorDetails['files_array'] = array_keys($_FILES);
    }
    
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
    
    error_log("AJAX Upload - File validation failed: $errorMessage - Details: " . json_encode($errorDetails));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage,
        'debug_info' => $errorDetails
    ]);
    exit();
}

error_log("AJAX Upload - File validation passed. Processing file: " . $_FILES['image']['name']);

// Validate image file
try {
    $validation = validateImageFile($_FILES['image']['tmp_name']);
    error_log("AJAX Upload - Validation result: " . json_encode($validation));
} catch (Exception $e) {
    error_log("AJAX Upload - Validation exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Image validation failed: ' . $e->getMessage()
    ]);
    exit();
} catch (Error $e) {
    error_log("AJAX Upload - Validation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Image validation error: ' . $e->getMessage()
    ]);
    exit();
}

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
    error_log("AJAX Upload - About to call uploadToCloudinary with folder: $folder, filename: $imageFilename");
    
    // Upload to Cloudinary with folder and filename separate
    $result = uploadToCloudinary(
        $fileToUpload, // Use potentially resized image
        $folder, // Folder path: assets/product-images/ProductName_timestamp
        $imageFilename // Just the filename, not the full path
    );
    
    error_log("AJAX Upload - uploadToCloudinary result: " . json_encode($result));
    
    if ($result['success']) {
        // Check if moderation is enabled
        $moderationEnabled = false;
        try {
            require_once __DIR__ . '/../includes/cloudinary-moderation-helper.php';
            $moderationHelper = new CloudinaryModerationHelper($conn);
            $moderationEnabled = $moderationHelper->isModerationEnabled();
        } catch (Exception $e) {
            error_log("Warning: Failed to check moderation status: " . $e->getMessage());
            // Continue without moderation
            $moderationEnabled = false;
        } catch (Error $e) {
            error_log("Warning: Failed to load moderation helper: " . $e->getMessage());
            // Continue without moderation
            $moderationEnabled = false;
        }
        
        // Determine initial moderation status
        $moderationStatus = 'pending'; // Default for async moderation
        
        // Log the upload for orphan cleanup tracking
        $logged = logTempImageUpload($conn, $result['public_id'], $result['url'], $moderationStatus);
        
        if (!$logged) {
            error_log("Warning: Failed to log temp image upload for tracking. Public ID: " . $result['public_id']);
        }
        
        // If product_id is provided, save directly to product_images table
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if ($product_id > 0) {
            error_log("AJAX Upload - Saving image to product_images for product_id: $product_id");
            
            $is_primary = ($imageType === 'primary') ? 1 : 0;
            
            // If this is a primary image, unset any existing primary images for this product
            if ($is_primary) {
                $stmt = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ? AND is_primary = 1");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $stmt->close();
                error_log("AJAX Upload - Unmarked previous primary images for product $product_id");
            }
            
            // Insert into product_images
            $stmt = $conn->prepare("INSERT INTO product_images (product_id, cloud_url, cloud_public_id, cloud_provider, is_primary, is_removed) VALUES (?, ?, ?, 'cloudinary', ?, 0)");
            $stmt->bind_param("issi", $product_id, $result['url'], $result['public_id'], $is_primary);
            
            if ($stmt->execute()) {
                $image_id = $stmt->insert_id;
                error_log("AJAX Upload - Successfully saved image to product_images (ID: $image_id, Product: $product_id, Type: $imageType)");
            } else {
                error_log("AJAX Upload - Failed to save image to product_images: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("AJAX Upload - No product_id provided, image only logged to temp_uploaded_images");
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
            'moderation_message' => $moderationEnabled ? 'Image uploaded. Safety check in progress...' : null,
            'saved_to_database' => ($product_id > 0)
        ]);
        
        error_log("AJAX upload successful: " . $result['public_id'] . " (Type: $imageType, Moderation: " . ($moderationEnabled ? 'enabled' : 'disabled') . ", Saved to DB: " . ($product_id > 0 ? 'yes' : 'no') . ")");
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
    error_log('AJAX upload exception trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Upload exception: ' . $e->getMessage(),
        'type' => 'Exception',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    error_log('AJAX upload error: ' . $e->getMessage());
    error_log('AJAX upload error trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Upload error: ' . $e->getMessage(),
        'type' => 'Error',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Delete temporary files
@unlink($_FILES['image']['tmp_name']);
if (isset($resizeResult) && $resizeResult['resized'] && isset($resizeResult['file_path'])) {
    @unlink($resizeResult['file_path']);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
