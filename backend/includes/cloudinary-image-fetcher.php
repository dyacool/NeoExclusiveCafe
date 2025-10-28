<?php
/**
 * Cloudinary Image Fetcher
 * Centralized and secure image fetching from Cloudinary with caching and error handling
 */

require_once __DIR__ . '/../../config/cloudinary-config.php';
require_once __DIR__ . '/../../config/database-config.php';

class CloudinaryImageFetcher {
    private $cloudinary;
    private $conn;
    private $cache = [];
    private $cacheExpiry = 3600; // 1 hour cache
    
    public function __construct($dbConnection = null) {
        try {
            $this->cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
            $this->conn = $dbConnection ?? $GLOBALS['conn'];
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher initialization failed: " . $e->getMessage());
            throw new Exception("Failed to initialize Cloudinary connection");
        }
    }
    
    /**
     * Fetch product image with safety checks and fallback
     * 
     * @param int $productId Product ID
     * @param string $imageType 'primary' or 'additional'
     * @param array $transformations Optional Cloudinary transformations
     * @return array Image data with URL and metadata
     */
    public function fetchProductImage($productId, $imageType = 'primary', $transformations = []) {
        try {
            // Validate input
            if (!is_numeric($productId) || $productId <= 0) {
                throw new Exception("Invalid product ID");
            }
            
            // Check cache first
            $cacheKey = "product_{$productId}_{$imageType}";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            // Fetch from database
            $sql = "SELECT id, name, cloudinary_url, cloudinary_additional_images, image_path, additional_images 
                    FROM products WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Database query preparation failed");
            }
            
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            if (!$product) {
                return $this->getFallbackImage('product_not_found');
            }
            
            // Get appropriate URL
            $cloudinaryUrl = null;
            $localPath = null;
            
            if ($imageType === 'primary') {
                $cloudinaryUrl = $product['cloudinary_url'];
                $localPath = $product['image_path'];
            } else {
                $cloudinaryUrl = $product['cloudinary_additional_images'];
                $localPath = $product['additional_images'];
            }
            
            // Process URL with transformations
            if (!empty($cloudinaryUrl)) {
                $imageData = $this->processCloudinaryUrl($cloudinaryUrl, $transformations);
            } elseif (!empty($localPath)) {
                // Fallback to local path if Cloudinary URL not available
                $imageData = [
                    'url' => $this->getBaseUrl() . '/' . $localPath,
                    'source' => 'local',
                    'product_id' => $productId,
                    'product_name' => $product['name']
                ];
            } else {
                $imageData = $this->getFallbackImage('no_image');
            }
            
            // Cache the result
            $this->cache[$cacheKey] = $imageData;
            
            return $imageData;
            
        } catch (Exception $e) {
            error_log("Error fetching product image: " . $e->getMessage());
            return $this->getFallbackImage('error');
        }
    }
    
    /**
     * Fetch multiple product images at once (optimized)
     * 
     * @param array $productIds Array of product IDs
     * @param array $transformations Optional Cloudinary transformations
     * @return array Associative array of product_id => image_data
     */
    public function fetchMultipleProductImages($productIds, $transformations = []) {
        try {
            if (empty($productIds) || !is_array($productIds)) {
                return [];
            }
            
            // Sanitize IDs
            $productIds = array_filter($productIds, function($id) {
                return is_numeric($id) && $id > 0;
            });
            
            if (empty($productIds)) {
                return [];
            }
            
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT id, name, cloudinary_url, image_path 
                    FROM products 
                    WHERE id IN ($placeholders) AND deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Database query preparation failed");
            }
            
            $types = str_repeat('i', count($productIds));
            $stmt->bind_param($types, ...$productIds);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $images = [];
            while ($product = $result->fetch_assoc()) {
                $cloudinaryUrl = $product['cloudinary_url'];
                $localPath = $product['image_path'];
                
                if (!empty($cloudinaryUrl)) {
                    $images[$product['id']] = $this->processCloudinaryUrl($cloudinaryUrl, $transformations);
                } elseif (!empty($localPath)) {
                    $images[$product['id']] = [
                        'url' => $this->getBaseUrl() . '/' . $localPath,
                        'source' => 'local',
                        'product_id' => $product['id'],
                        'product_name' => $product['name']
                    ];
                } else {
                    $images[$product['id']] = $this->getFallbackImage('no_image');
                }
            }
            
            $stmt->close();
            return $images;
            
        } catch (Exception $e) {
            error_log("Error fetching multiple product images: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Fetch payment proof image
     * 
     * @param string $filename Filename or Cloudinary public ID
     * @param string $type 'bulk_payments' or 'refund_proofs'
     * @return array Image data
     */
    public function fetchPaymentProof($filename, $type = 'bulk_payments') {
        try {
            // Validate type
            $allowedTypes = ['bulk_payments', 'refund_proofs'];
            if (!in_array($type, $allowedTypes)) {
                throw new Exception("Invalid payment proof type");
            }
            
            // Sanitize filename
            $filename = basename($filename);
            
            // Try Cloudinary first
            $publicId = "neocafe/{$type}/" . pathinfo($filename, PATHINFO_FILENAME);
            
            try {
                // Check if image exists in Cloudinary
                $cloudinaryUrl = $this->cloudinary->image($publicId)
                    ->delivery(quality('auto'))
                    ->delivery(format('auto'))
                    ->toUrl();
                
                return [
                    'url' => $cloudinaryUrl,
                    'source' => 'cloudinary',
                    'type' => $type,
                    'filename' => $filename,
                    'public_id' => $publicId
                ];
            } catch (Exception $e) {
                // Fallback to local path
                $localPath = "assets/{$type}/{$filename}";
                if (file_exists(__DIR__ . '/../../' . $localPath)) {
                    return [
                        'url' => $this->getBaseUrl() . '/' . $localPath,
                        'source' => 'local',
                        'type' => $type,
                        'filename' => $filename
                    ];
                }
            }
            
            return $this->getFallbackImage('payment_proof_not_found');
            
        } catch (Exception $e) {
            error_log("Error fetching payment proof: " . $e->getMessage());
            return $this->getFallbackImage('error');
        }
    }
    
    /**
     * Fetch general asset image
     * 
     * @param string $filename Filename
     * @param array $transformations Optional transformations
     * @return array Image data
     */
    public function fetchAssetImage($filename, $transformations = []) {
        try {
            $filename = basename($filename);
            $publicId = "neocafe/assets/" . pathinfo($filename, PATHINFO_FILENAME);
            
            try {
                $cloudinaryUrl = $this->processCloudinaryUrl($publicId, $transformations, true);
                return $cloudinaryUrl;
            } catch (Exception $e) {
                // Fallback to local
                $localPath = "assets/images/{$filename}";
                if (file_exists(__DIR__ . '/../../' . $localPath)) {
                    return [
                        'url' => $this->getBaseUrl() . '/' . $localPath,
                        'source' => 'local',
                        'filename' => $filename
                    ];
                }
            }
            
            return $this->getFallbackImage('asset_not_found');
            
        } catch (Exception $e) {
            error_log("Error fetching asset image: " . $e->getMessage());
            return $this->getFallbackImage('error');
        }
    }
    
    /**
     * Process Cloudinary URL with transformations
     * 
     * @param string $urlOrPublicId Cloudinary URL or public ID
     * @param array $transformations Transformation parameters
     * @param bool $isPublicId Whether input is public ID
     * @return array Processed image data
     */
    private function processCloudinaryUrl($urlOrPublicId, $transformations = [], $isPublicId = false) {
        try {
            if ($isPublicId) {
                $publicId = $urlOrPublicId;
            } else {
                // Extract public ID from URL if needed
                $publicId = $this->extractPublicIdFromUrl($urlOrPublicId);
            }
            
            // Apply default transformations if none provided
            if (empty($transformations)) {
                $transformations = [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                    'width' => 800,
                    'crop' => 'limit'
                ];
            }
            
            // Build transformation URL
            $imageBuilder = $this->cloudinary->image($publicId);
            
            // Apply transformations
            if (isset($transformations['width'])) {
                $imageBuilder->resize(scale()->width($transformations['width']));
            }
            if (isset($transformations['quality'])) {
                $imageBuilder->delivery(quality($transformations['quality']));
            }
            if (isset($transformations['fetch_format'])) {
                $imageBuilder->delivery(format($transformations['fetch_format']));
            }
            
            $url = $imageBuilder->toUrl();
            
            return [
                'url' => $url,
                'source' => 'cloudinary',
                'public_id' => $publicId,
                'transformations' => $transformations
            ];
            
        } catch (Exception $e) {
            error_log("Error processing Cloudinary URL: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Extract public ID from Cloudinary URL
     * 
     * @param string $url Cloudinary URL
     * @return string Public ID
     */
    private function extractPublicIdFromUrl($url) {
        // If it's already a public ID, return as is
        if (strpos($url, 'http') !== 0) {
            return $url;
        }
        
        // Extract from URL pattern: https://res.cloudinary.com/{cloud_name}/image/upload/{version}/{public_id}.{format}
        $pattern = '/\/upload\/(?:v\d+\/)?(.+?)(?:\.[a-z]+)?$/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return $url;
    }
    
    /**
     * Get fallback image based on error type
     * 
     * @param string $errorType Type of error
     * @return array Fallback image data
     */
    private function getFallbackImage($errorType) {
        $fallbacks = [
            'product_not_found' => '/assets/images/placeholder-product.png',
            'no_image' => '/assets/images/no-image.png',
            'payment_proof_not_found' => '/assets/images/placeholder-document.png',
            'asset_not_found' => '/assets/images/placeholder.png',
            'error' => '/assets/images/error-image.png'
        ];
        
        $fallbackPath = $fallbacks[$errorType] ?? $fallbacks['error'];
        
        return [
            'url' => $this->getBaseUrl() . $fallbackPath,
            'source' => 'fallback',
            'error_type' => $errorType
        ];
    }
    
    /**
     * Get base URL for the application
     * 
     * @return string Base URL
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    /**
     * Verify image exists in Cloudinary
     * 
     * @param string $publicId Cloudinary public ID
     * @return bool True if exists
     */
    public function verifyImageExists($publicId) {
        try {
            $result = $this->cloudinary->adminApi()->resource($publicId);
            return isset($result['public_id']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear cache
     */
    public function clearCache() {
        $this->cache = [];
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache stats
     */
    public function getCacheStats() {
        return [
            'cached_items' => count($this->cache),
            'cache_expiry' => $this->cacheExpiry
        ];
    }
}
