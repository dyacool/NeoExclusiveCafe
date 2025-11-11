<?php
/**
 * Cloudinary Helper - cURL version (No Composer required)
 * Uses Cloudinary REST API directly via cURL
 */

// Load config - use the cURL version
require_once __DIR__ . '/../config/cloudinary-config-curl.php';

/**
 * Upload image to Cloudinary using cURL
 * @param string $filePath Path to the local file
 * @param string $folder Cloudinary folder name
 * @param string|null $publicId Optional public ID
 * @return array Success/error response
 */
function uploadToCloudinary($filePath, $folder = 'neocafe', $publicId = null) {
    error_log("uploadToCloudinary called with file: $filePath, folder: $folder");
    
    // Validate the file first
    $validation = validateImageFile($filePath);
    if (!$validation['valid']) {
        error_log("File validation failed: " . $validation['error']);
        return [
            'success' => false,
            'error' => $validation['error'],
            'error_code' => $validation['error_code']
        ];
    }
    
    try {
        // Get Cloudinary credentials from config
        $config = CloudinaryConfig::getInstance();
        $cloudName = $config->getCloudName();
        $apiKey = $config->getApiKey();
        $apiSecret = $config->getApiSecret();
        
        error_log("Using cloud_name: $cloudName");
        
        // Generate timestamp for signature
        $timestamp = time();
        
        // Prepare upload parameters
        $params = [
            'timestamp' => $timestamp,
            'folder' => $folder
        ];
        
        // Add public_id if provided
        if ($publicId) {
            $params['public_id'] = $publicId;
        }
        
        // Add moderation if enabled
        if ($config->isModerationEnabled()) {
            $params['moderation'] = 'aws_rek:explicit';
        }
        
        // Generate signature
        $signature = generateCloudinarySignature($params, $apiSecret);
        $params['signature'] = $signature;
        $params['api_key'] = $apiKey;
        
        // Prepare file for upload
        if (class_exists('CURLFile')) {
            $params['file'] = new CURLFile($filePath);
        } else {
            $params['file'] = '@' . $filePath;
        }
        
        // Upload URL
        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
        
        error_log("Uploading to: $url");
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For XAMPP environments
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        error_log("Cloudinary HTTP Code: $httpCode");
        
        if ($curlError) {
            error_log("cURL Error: $curlError");
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $curlError,
                'error_code' => 'CURL_ERROR'
            ];
        }
        
        // Parse response
        $result = json_decode($response, true);
        
        if ($httpCode !== 200) {
            error_log("Cloudinary Error Response: " . print_r($result, true));
            $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'Unknown error';
            return [
                'success' => false,
                'error' => 'Cloudinary upload failed: ' . $errorMsg,
                'error_code' => 'CLOUDINARY_ERROR',
                'http_code' => $httpCode
            ];
        }
        
        error_log("Upload successful: " . $result['secure_url']);
        
        // Return success with Cloudinary response data
        return [
            'success' => true,
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'],
            'height' => $result['height'],
            'format' => $result['format'],
            'resource_type' => $result['resource_type'],
            'created_at' => $result['created_at'],
            'bytes' => $result['bytes'],
            'moderation' => isset($result['moderation']) ? $result['moderation'] : null
        ];
        
    } catch (Exception $e) {
        error_log("Exception in uploadToCloudinary: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage(),
            'error_code' => 'EXCEPTION'
        ];
    }
}

/**
 * Delete image from Cloudinary using cURL
 * @param string $publicId The public ID of the image to delete
 * @return array Success/error response
 */
function deleteFromCloudinary($publicId) {
    error_log("deleteFromCloudinary called for: $publicId");
    
    try {
        // Get Cloudinary credentials
        $config = CloudinaryConfig::getInstance();
        $cloudName = $config->getCloudName();
        $apiKey = $config->getApiKey();
        $apiSecret = $config->getApiSecret();
        
        // Generate timestamp for signature
        $timestamp = time();
        
        // Prepare parameters
        $params = [
            'public_id' => $publicId,
            'timestamp' => $timestamp
        ];
        
        // Generate signature
        $signature = generateCloudinarySignature($params, $apiSecret);
        $params['signature'] = $signature;
        $params['api_key'] = $apiKey;
        
        // Delete URL
        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy";
        
        error_log("Deleting from: $url");
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL Error: $curlError");
            return [
                'success' => false,
                'error' => 'Delete failed: ' . $curlError
            ];
        }
        
        // Parse response
        $result = json_decode($response, true);
        
        error_log("Delete response: " . print_r($result, true));
        
        if ($httpCode === 200 && isset($result['result']) && $result['result'] === 'ok') {
            return [
                'success' => true,
                'result' => $result['result']
            ];
        } else {
            $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'Unknown error';
            return [
                'success' => false,
                'error' => 'Cloudinary delete failed: ' . $errorMsg
            ];
        }
        
    } catch (Exception $e) {
        error_log("Exception in deleteFromCloudinary: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate Cloudinary signature for authentication
 * @param array $params Parameters to sign
 * @param string $apiSecret API secret
 * @return string SHA-1 signature
 */
function generateCloudinarySignature($params, $apiSecret) {
    // Sort parameters alphabetically
    ksort($params);
    
    // Build string to sign
    $stringToSign = [];
    foreach ($params as $key => $value) {
        if ($key !== 'file' && $key !== 'api_key') {
            $stringToSign[] = $key . '=' . $value;
        }
    }
    $stringToSign = implode('&', $stringToSign);
    
    // Generate SHA-1 signature
    return sha1($stringToSign . $apiSecret);
}

/**
 * Get Cloudinary URL with transformations
 * @param string $publicId Public ID of the image
 * @param array $transformations Array of transformations
 * @return string Full Cloudinary URL
 */
function getCloudinaryUrl($publicId, $transformations = []) {
    $config = CloudinaryConfig::getInstance();
    $cloudName = $config->getCloudName();
    
    $baseUrl = "https://res.cloudinary.com/{$cloudName}/image/upload";
    
    // Build transformation string
    $transformString = '';
    if (!empty($transformations)) {
        $parts = [];
        foreach ($transformations as $key => $value) {
            $parts[] = $key . '_' . $value;
        }
        $transformString = implode(',', $parts) . '/';
    }
    
    return "{$baseUrl}/{$transformString}{$publicId}";
}

/**
 * Validate image file
 * @param string $filePath Path to file
 * @return array Validation result
 */
function validateImageFile($filePath) {
    // Check if file exists
    if (!file_exists($filePath)) {
        return [
            'valid' => false,
            'error' => 'File not found',
            'error_code' => 'FILE_NOT_FOUND'
        ];
    }
    
    // Check if file is readable
    if (!is_readable($filePath)) {
        return [
            'valid' => false,
            'error' => 'File is not readable',
            'error_code' => 'FILE_NOT_READABLE'
        ];
    }
    
    // Check if it's an image
    $imageInfo = @getimagesize($filePath);
    if ($imageInfo === false) {
        return [
            'valid' => false,
            'error' => 'File is not a valid image',
            'error_code' => 'INVALID_IMAGE'
        ];
    }
    
    // Check file size (max 10MB)
    $fileSize = filesize($filePath);
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($fileSize > $maxSize) {
        return [
            'valid' => false,
            'error' => 'File size exceeds 10MB limit',
            'error_code' => 'FILE_TOO_LARGE'
        ];
    }
    
    // Check MIME type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'valid' => false,
            'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed',
            'error_code' => 'INVALID_FILE_TYPE'
        ];
    }
    
    return [
        'valid' => true,
        'mime_type' => $mimeType,
        'width' => $imageInfo[0],
        'height' => $imageInfo[1],
        'size' => $fileSize
    ];
}

/**
 * Resize image if needed
 * @param string $filePath Path to image file
 * @param int $maxWidth Maximum width
 * @param int $maxHeight Maximum height
 * @return bool Success status
 */
function resizeImageIfNeeded($filePath, $maxWidth = 5000, $maxHeight = 5000) {
    $imageInfo = @getimagesize($filePath);
    if (!$imageInfo) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Check if resize is needed
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true; // No resize needed
    }
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Create source image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save resized image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($newImage, $filePath, 90);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($newImage, $filePath, 9);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($newImage, $filePath);
            break;
        default:
            $success = false;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($newImage);
    
    return $success;
}

/**
 * Generate public ID for Cloudinary
 * @param string $filename Original filename
 * @param string $prefix Optional prefix
 * @return string Generated public ID
 */
function generatePublicId($filename, $prefix = '') {
    $pathInfo = pathinfo($filename);
    $basename = $pathInfo['filename'];
    $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
    $timestamp = time();
    $random = substr(md5(uniqid(rand(), true)), 0, 8);
    
    if ($prefix) {
        return $prefix . '_' . $basename . '_' . $timestamp . '_' . $random;
    }
    return $basename . '_' . $timestamp . '_' . $random;
}

/**
 * Log migration activity
 */
function logMigration($conn, $localPath, $cloudinaryUrl, $publicId, $imageType, $status = 'success', $errorMessage = null) {
    // Optional logging function - implement if needed
    error_log("Migration log: $localPath -> $cloudinaryUrl ($status)");
}

/**
 * Log local file access
 */
function logLocalFileAccess($filePath, $operation, $context = '') {
    error_log("File access: $operation - $filePath ($context)");
}
