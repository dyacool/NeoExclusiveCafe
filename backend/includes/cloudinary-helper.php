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
            return [
                'success' => false,
                'error' => 'File not found: ' . $filePath
            ];
        }
        
        // Validate file is an image
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return [
                'success' => false,
                'error' => 'File is not a valid image: ' . $filePath
            ];
        }
        
        $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        
        $options = [
            'folder' => $folder,
            'overwrite' => true,
            'resource_type' => 'auto',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ];
        
        if ($publicId) {
            $options['public_id'] = $publicId;
        }
        
        $result = $cloudinary->uploadApi()->upload($filePath, $options);
        
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
        return [
            'success' => false,
            'error' => $e->getMessage()
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
        $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        
        if (empty($transformations)) {
            // Default transformations for optimization
            $transformations = [
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ];
        }
        
        return $cloudinary->image($publicId)
            ->resize(scale()->width(800))
            ->delivery(quality('auto'))
            ->delivery(format('auto'))
            ->toUrl();
    } catch (Exception $e) {
        error_log("Failed to get Cloudinary URL: " . $e->getMessage());
        return '';
    }
}

/**
 * Delete an image from Cloudinary
 * 
 * @param string $publicId Cloudinary public ID
 * @return bool Success status
 */
function deleteFromCloudinary($publicId) {
    try {
        $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        $result = $cloudinary->uploadApi()->destroy($publicId);
        return $result['result'] === 'ok';
    } catch (Exception $e) {
        error_log("Failed to delete from Cloudinary: " . $e->getMessage());
        return false;
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
?>
