<?php
/**
 * AJAX Upload Endpoint for Customer Profile Pictures
 * 
 * This endpoint handles real-time customer profile picture uploads to Cloudinary via AJAX.
 * Images are uploaded immediately when selected and immediately saved to the user's profile.
 * Old profile pictures are automatically deleted from Cloudinary before uploading new ones.
 */

session_start();

// Start output buffering to catch any unwanted output
ob_start();

header('Content-Type: application/json');

// Verify customer authentication
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please log in.'
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

require_once __DIR__ . '/../../backend/includes/cloudinary-helper.php';
require_once __DIR__ . '/../user-includes/database.php';

// Clean any output from included files
ob_clean();

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

/**
 * Delete old profile picture from Cloudinary
 * 
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return bool Success status
 */
function deleteOldProfilePicture($conn, $userId) {
    try {
        // Get current profile picture public_id
        $stmt = $conn->prepare("SELECT cloud_public_id FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && !empty($user['cloud_public_id'])) {
            $oldPublicId = $user['cloud_public_id'];
            error_log("Deleting old profile picture: $oldPublicId");
            
            // Delete from Cloudinary
            $deleteResult = deleteFromCloudinary($oldPublicId);
            
            if ($deleteResult['success']) {
                error_log("Successfully deleted old profile picture: $oldPublicId");
                
                // Remove from temp_uploaded_images if it exists
                $stmt = $conn->prepare("DELETE FROM temp_uploaded_images WHERE public_id = ?");
                $stmt->bind_param("s", $oldPublicId);
                $stmt->execute();
                $stmt->close();
                
                return true;
            } else {
                error_log("Failed to delete old profile picture: " . ($deleteResult['error'] ?? 'Unknown error'));
                // Continue anyway - we'll update the database
                return false;
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Exception deleting old profile picture: " . $e->getMessage());
        return false;
    }
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Validate uploaded file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'No file uploaded';
    $errorCode = 'UNKNOWN';
    
    if (isset($_FILES['image']['error'])) {
        $uploadError = $_FILES['image']['error'];
        error_log("Profile picture upload error code: " . $uploadError);
        
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

// Log what we received
error_log("Customer Profile Picture AJAX Upload - User ID: " . $userId);

// Delete old profile picture before uploading new one
deleteOldProfilePicture($conn, $userId);

// Generate unique filename with user ID and timestamp
$timestamp = time();
$fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$imageFilename = 'profile_' . $userId . '_' . $timestamp;

error_log("Customer Profile Picture AJAX Upload - Filename: " . $imageFilename);

// Folder path: Home/assets/public/profile-images/
$folder = 'Home/assets/public/profile-images';

try {
    // Upload to Cloudinary
    $result = uploadToCloudinary(
        $_FILES['image']['tmp_name'],
        $folder, // Folder path: Home/assets/public/profile-images
        $imageFilename // Filename: profile_[user_id]_[timestamp]
    );
    
    if ($result['success']) {
        // Log the upload for orphan cleanup tracking
        $logged = logTempImageUpload($conn, $result['public_id'], $result['url']);
        
        if (!$logged) {
            error_log("Warning: Failed to log temp profile picture upload for tracking. Public ID: " . $result['public_id']);
        }
        
        // Update user's profile in database
        $stmt = $conn->prepare("UPDATE users SET cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE id = ?");
        $stmt->bind_param("ssi", $result['url'], $result['public_id'], $userId);
        
        if ($stmt->execute()) {
            error_log("Successfully updated user profile with new picture: " . $result['public_id']);
            
            // Remove from temp_uploaded_images after successful save
            $deleteStmt = $conn->prepare("DELETE FROM temp_uploaded_images WHERE public_id = ?");
            $deleteStmt->bind_param("s", $result['public_id']);
            $deleteStmt->execute();
            $deleteStmt->close();
            
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
            
            error_log("Customer profile picture AJAX upload successful: " . $result['public_id']);
        } else {
            // Database update failed - delete the uploaded image
            error_log("Failed to update database with new profile picture: " . $stmt->error);
            deleteFromCloudinary($result['public_id']);
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to save profile picture to database',
                'error_code' => 'DATABASE_ERROR'
            ]);
        }
        
        $stmt->close();
    } else {
        // Upload failed
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Upload failed',
            'error_code' => $result['error_code'] ?? 'UNKNOWN'
        ]);
        
        error_log("Customer profile picture AJAX upload failed: " . ($result['error'] ?? 'Unknown error'));
    }
} catch (Exception $e) {
    error_log('Customer profile picture AJAX upload exception: ' . $e->getMessage());
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
