<?php
require_once __DIR__ . '/../../config/cloudinary-config.php';

/**
 * Upload a file to Cloudinary
 * 
 * @param string $filePath Local file path
 * @param string $folder Cloudinary folder (e.g., 'neocafe/products')
 * @param string|null $publicId Optional public ID for the image
 * @return array Result with success status, URL, and public_id
 */
function uploadToCloudinary($filePath, $folder = 'neocafe', $publicId = null) {
    try {
        // Validate file exists
        if (!file_exists($filePath)) {
            $error = 'File not found: ' . basename($filePath);
            error_log("Cloudinary upload error: $error (Full path: $filePath)");
            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'FILE_NOT_FOUND'
            ];
        }
        
        // Validate file is readable
        if (!is_readable($filePath)) {
            $error = 'File is not readable: ' . basename($filePath);
            error_log("Cloudinary upload error: $error (Full path: $filePath)");
            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'FILE_NOT_READABLE'
            ];
        }
        
        // Validate file is an image
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            $error = 'File is not a valid image: ' . basename($filePath);
            error_log("Cloudinary upload error: $error (Full path: $filePath)");
            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'INVALID_IMAGE'
            ];
        }
        
        // Validate file size (max 10MB)
        $fileSize = filesize($filePath);
        if ($fileSize > 10 * 1024 * 1024) {
            $error = 'File size exceeds 10MB limit';
            error_log("Cloudinary upload error: $error (Size: " . round($fileSize / 1024 / 1024, 2) . "MB, File: " . basename($filePath) . ")");
            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'FILE_TOO_LARGE'
            ];
        }
        
        // Get Cloudinary instance
        $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        
        $options = [
            'overwrite' => true,
            'resource_type' => 'auto',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ];
        
        if ($publicId) {
            $options['public_id'] = $publicId;
        } elseif ($folder) {
            // Only use folder parameter if no public_id is provided
            $options['folder'] = $folder;
        }
        
        // Log upload attempt
        error_log("Attempting Cloudinary upload: " . basename($filePath) . " to folder: $folder" . ($publicId ? " with public_id: $publicId" : ""));
        
        // Attempt upload
        $result = $cloudinary->uploadApi()->upload($filePath, $options);
        
        // Validate result
        if (!isset($result['secure_url']) || empty($result['secure_url'])) {
            throw new Exception('Cloudinary upload succeeded but no secure URL returned');
        }
        
        // Log successful upload
        error_log("Cloudinary upload successful: " . $result['public_id'] . " (URL: " . $result['secure_url'] . ")");
        
        return [
            'success' => true,
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'format' => $result['format'] ?? null,
            'bytes' => $result['bytes'] ?? null
        ];
    } catch (Exception $e) {
        // Log detailed error information
        $errorMessage = $e->getMessage();
        $errorDetails = [
            'message' => $errorMessage,
            'file' => basename($filePath ?? 'unknown'),
            'folder' => $folder ?? 'unknown',
            'public_id' => $publicId ?? 'auto-generated',
            'trace' => $e->getTraceAsString()
        ];
        error_log("Cloudinary upload exception: " . json_encode($errorDetails));
        
        // Return user-friendly error message
        $userMessage = 'Failed to upload image to Cloudinary';
        if (strpos($errorMessage, 'timeout') !== false) {
            $userMessage = 'Upload timeout - please try again';
        } elseif (strpos($errorMessage, 'network') !== false || strpos($errorMessage, 'connection') !== false) {
            $userMessage = 'Network error - please check your connection';
        } elseif (strpos($errorMessage, 'quota') !== false || strpos($errorMessage, 'limit') !== false) {
            $userMessage = 'Upload limit reached - please contact administrator';
        }
        
        return [
            'success' => false,
            'error' => $userMessage,
            'error_code' => 'CLOUDINARY_EXCEPTION',
            'error_details' => $errorMessage
        ];
    }
}

/**
 * Get Cloudinary URL with transformations
 * 
 * @param string $publicId Cloudinary public ID
 * @param array $transformations Optional transformations
 * @return string Cloudinary URL
 */
function getCloudinaryUrl($publicId, $transformations = []) {
    try {
        // Build URL manually for compatibility
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: $_ENV['CLOUDINARY_CLOUD_NAME'] ?? 'dvdccumbs';
        
        if (empty($transformations)) {
            // Default transformations for optimization
            $transformations = [
                'width' => 800,
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ];
        }
        
        // Build transformation string
        $transformParams = [];
        if (isset($transformations['width'])) {
            $transformParams[] = 'w_' . $transformations['width'];
        }
        if (isset($transformations['height'])) {
            $transformParams[] = 'h_' . $transformations['height'];
        }
        if (isset($transformations['quality'])) {
            $transformParams[] = 'q_' . $transformations['quality'];
        }
        if (isset($transformations['fetch_format'])) {
            $transformParams[] = 'f_' . $transformations['fetch_format'];
        }
        
        $transformString = !empty($transformParams) ? implode(',', $transformParams) . '/' : '';
        
        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transformString}{$publicId}";
    } catch (Exception $e) {
        error_log("Failed to get Cloudinary URL: " . $e->getMessage());
        return '';
    }
}

/**
 * Delete an image from Cloudinary
 * 
 * @param string $publicId Cloudinary public ID
 * @return array Result with success status and details
 */
function deleteFromCloudinary($publicId) {
    try {
        if (empty($publicId)) {
            error_log("Cloudinary delete error: Empty public ID provided");
            return [
                'success' => false,
                'error' => 'Invalid public ID',
                'error_code' => 'EMPTY_PUBLIC_ID'
            ];
        }
        
        error_log("Attempting to delete from Cloudinary: $publicId");
        
        $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        $result = $cloudinary->uploadApi()->destroy($publicId);
        
        if ($result['result'] === 'ok') {
            error_log("Successfully deleted from Cloudinary: $publicId");
            return [
                'success' => true,
                'public_id' => $publicId,
                'result' => $result['result']
            ];
        } elseif ($result['result'] === 'not found') {
            error_log("Cloudinary delete: Image not found: $publicId");
            return [
                'success' => false,
                'error' => 'Image not found in Cloudinary',
                'error_code' => 'NOT_FOUND',
                'public_id' => $publicId
            ];
        } else {
            error_log("Cloudinary delete failed with result: " . $result['result'] . " for public_id: $publicId");
            return [
                'success' => false,
                'error' => 'Delete operation failed: ' . $result['result'],
                'error_code' => 'DELETE_FAILED',
                'public_id' => $publicId
            ];
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        error_log("Cloudinary delete exception for $publicId: $errorMessage");
        
        return [
            'success' => false,
            'error' => 'Failed to delete image from Cloudinary',
            'error_code' => 'CLOUDINARY_EXCEPTION',
            'error_details' => $errorMessage,
            'public_id' => $publicId
        ];
    }
}

/**
 * Validate image file
 * 
 * @param string $filePath File path to validate
 * @return array Validation result
 */
function validateImageFile($filePath) {
    if (!file_exists($filePath)) {
        return [
            'valid' => false,
            'error' => 'File does not exist'
        ];
    }
    
    if (!is_readable($filePath)) {
        return [
            'valid' => false,
            'error' => 'File is not readable'
        ];
    }
    
    $imageInfo = @getimagesize($filePath);
    if ($imageInfo === false) {
        return [
            'valid' => false,
            'error' => 'File is not a valid image'
        ];
    }
    
    // Check file size (max 10MB)
    $fileSize = filesize($filePath);
    if ($fileSize > 10 * 1024 * 1024) {
        return [
            'valid' => false,
            'error' => 'File size exceeds 10MB limit'
        ];
    }
    
    // Check image dimensions
    list($width, $height) = $imageInfo;
    if ($width > 5000 || $height > 5000) {
        return [
            'valid' => false,
            'error' => 'Image dimensions exceed 5000x5000 limit'
        ];
    }
    
    return [
        'valid' => true,
        'width' => $width,
        'height' => $height,
        'mime_type' => $imageInfo['mime'],
        'size' => $fileSize
    ];
}

/**
 * Log migration activity
 * 
 * @param mysqli $conn Database connection
 * @param string $localPath Local file path
 * @param string $cloudinaryUrl Cloudinary URL
 * @param string $publicId Cloudinary public ID
 * @param string $imageType Type of image
 * @param string $status Status (success/failed)
 * @param string|null $errorMessage Error message if failed
 */
function logMigration($conn, $localPath, $cloudinaryUrl, $publicId, $imageType, $status = 'success', $errorMessage = null) {
    $sql = "INSERT INTO image_migrations (local_path, cloudinary_url, cloudinary_public_id, image_type, status, error_message) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssss", $localPath, $cloudinaryUrl, $publicId, $imageType, $status, $errorMessage);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Generate a safe public ID from filename
 * 
 * @param string $filename Original filename
 * @param string $prefix Optional prefix
 * @return string Safe public ID
 */
function generatePublicId($filename, $prefix = '') {
    // Remove extension
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Replace spaces and special characters
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    
    // Add prefix if provided
    if ($prefix) {
        $name = $prefix . '_' . $name;
    }
    
    return $name;
}

/**
 * Log local file access attempts for security auditing
 * 
 * @param string $filePath The local file path that was accessed
 * @param string $operation The operation attempted (read, write, delete, etc.)
 * @param string $context Additional context about the access attempt
 */
function logLocalFileAccess($filePath, $operation, $context = '') {
    $logMessage = sprintf(
        "[LOCAL FILE ACCESS] Operation: %s | Path: %s | Context: %s | Timestamp: %s | Backtrace: %s",
        $operation,
        $filePath,
        $context,
        date('Y-m-d H:i:s'),
        json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3))
    );
    
    error_log($logMessage);
    
    // Also log to a dedicated security log file if it exists
    $securityLogPath = __DIR__ . '/../../logs/security_audit.log';
    if (is_writable(dirname($securityLogPath))) {
        error_log($logMessage . PHP_EOL, 3, $securityLogPath);
    }
}
?>
