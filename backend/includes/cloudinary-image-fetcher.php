<?php
/**
 * Cloudinary Image Fetcher - SECURE VERSION
 * Centralized and secure image fetching from Cloudinary ONLY
 * NO LOCAL FALLBACKS for security reasons
 */

// Safely require config files with error handling
try {
    require_once __DIR__ . '/../../config/cloudinary-config.php';
} catch (Exception $e) {
    error_log("CloudinaryImageFetcher: Failed to load cloudinary-config.php: " . $e->getMessage());
}

try {
    require_once __DIR__ . '/../../config/database-config.php';
} catch (Exception $e) {
    error_log("CloudinaryImageFetcher: Failed to load database-config.php: " . $e->getMessage());
}

class CloudinaryImageFetcher {
    private $cloudinary;
    private $conn;
    private $cache = [];
    private $cacheExpiry = 3600;
    private $maxRetries = 2;
    private $retryDelay = 1000000; // 1 second in microseconds
    
    public function __construct($dbConnection = null) {
        try {
            if (class_exists('CloudinaryConfig')) {
                $this->cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
            } else {
                error_log("CloudinaryImageFetcher: CloudinaryConfig class not found");
                $this->cloudinary = null;
            }
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher: Failed to initialize Cloudinary: " . $e->getMessage());
            $this->cloudinary = null;
        }
        
        if ($dbConnection !== null) {
            $this->conn = $dbConnection;
        } elseif (isset($GLOBALS['conn']) && $GLOBALS['conn']) {
            $this->conn = $GLOBALS['conn'];
        } else {
            try {
                if (function_exists('getDatabaseConnection')) {
                    $this->conn = getDatabaseConnection();
                } else {
                    error_log("CloudinaryImageFetcher: getDatabaseConnection function not found");
                    $this->conn = null;
                }
            } catch (Exception $e) {
                error_log("CloudinaryImageFetcher: Failed to get database connection: " . $e->getMessage());
                $this->conn = null;
            }
        }
        
        if (!$this->conn) {
            error_log("CloudinaryImageFetcher: Database connection is null");
        }
    }
    
    public function fetchProductImage($productId, $imageType = 'primary', $transformations = []) {
        if (!is_numeric($productId) || $productId <= 0) {
            $error = "Invalid product ID: $productId";
            error_log("CloudinaryImageFetcher::fetchProductImage - $error");
            throw new Exception($error);
        }
        
        $cacheKey = "product_{$productId}_{$imageType}_" . md5(json_encode($transformations));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        try {
            $sql = "SELECT id, name, cloudinary_url, cloudinary_additional_images 
                    FROM products WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Database query preparation failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $productId);
            
            if (!$stmt->execute()) {
                throw new Exception("Database query execution failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            if (!$product) {
                throw new Exception("Product not found: ID {$productId}");
            }
            
            $cloudinaryUrl = ($imageType === 'primary') ? $product['cloudinary_url'] : $product['cloudinary_additional_images'];
            
            if (empty($cloudinaryUrl)) {
                error_log("CloudinaryImageFetcher: Product {$productId} ({$product['name']}) has no Cloudinary URL for type: $imageType");
                throw new Exception("Product {$productId} ({$product['name']}) has no Cloudinary URL");
            }
            
            // Process Cloudinary URL with retry mechanism
            $imageData = $this->processCloudinaryUrlWithRetry($cloudinaryUrl, $transformations);
            $imageData['product_id'] = $productId;
            $imageData['product_name'] = $product['name'];
            
            $this->cache[$cacheKey] = $imageData;
            return $imageData;
            
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::fetchProductImage - Error fetching image for product $productId: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchMultipleProductImages($productIds, $transformations = [], $skipMissing = false) {
        if (empty($productIds) || !is_array($productIds)) {
            error_log("CloudinaryImageFetcher::fetchMultipleProductImages - Empty or invalid product IDs array");
            return [];
        }
        
        $productIds = array_filter($productIds, function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        if (empty($productIds)) {
            error_log("CloudinaryImageFetcher::fetchMultipleProductImages - No valid product IDs after filtering");
            return [];
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT id, name, cloudinary_url FROM products WHERE id IN ($placeholders) AND deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database query preparation failed: " . $this->conn->error);
            }
            
            $types = str_repeat('i', count($productIds));
            $stmt->bind_param($types, ...$productIds);
            
            if (!$stmt->execute()) {
                throw new Exception("Database query execution failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $images = [];
            $missingCloudinary = [];
            $failedFetches = [];
            
            while ($product = $result->fetch_assoc()) {
                if (empty($product['cloudinary_url'])) {
                    $missingCloudinary[] = "Product {$product['id']} ({$product['name']})";
                    error_log("CloudinaryImageFetcher: Product {$product['id']} ({$product['name']}) has no Cloudinary URL");
                    if (!$skipMissing) continue;
                    continue;
                }
                
                try {
                    $imageData = $this->processCloudinaryUrlWithRetry($product['cloudinary_url'], $transformations);
                    $imageData['product_id'] = $product['id'];
                    $imageData['product_name'] = $product['name'];
                    $images[$product['id']] = $imageData;
                } catch (Exception $e) {
                    $errorMsg = "Product {$product['id']} ({$product['name']}): " . $e->getMessage();
                    $failedFetches[] = $errorMsg;
                    error_log("CloudinaryImageFetcher: Error processing Cloudinary URL for product {$product['id']}: " . $e->getMessage());
                    if (!$skipMissing) throw $e;
                }
            }
            
            $stmt->close();
            
            // Log summary of issues
            if (!empty($missingCloudinary)) {
                error_log("CloudinaryImageFetcher: " . count($missingCloudinary) . " products without Cloudinary URLs");
            }
            if (!empty($failedFetches)) {
                error_log("CloudinaryImageFetcher: " . count($failedFetches) . " products failed to fetch");
            }
            
            if (!empty($missingCloudinary) && !$skipMissing) {
                throw new Exception("Products without Cloudinary URLs: " . implode(', ', $missingCloudinary));
            }
            
            return $images;
            
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::fetchMultipleProductImages - Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchPaymentProof($cloudinaryPublicId, $transformations = []) {
        $cloudinaryPublicId = trim($cloudinaryPublicId);
        if (empty($cloudinaryPublicId)) {
            error_log("CloudinaryImageFetcher::fetchPaymentProof - Empty public ID provided");
            throw new Exception("Cloudinary public ID required");
        }
        
        try {
            return $this->processCloudinaryUrlWithRetry($cloudinaryPublicId, $transformations, true);
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::fetchPaymentProof - Error fetching payment proof: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchAssetImage($cloudinaryPublicId, $transformations = []) {
        $cloudinaryPublicId = trim($cloudinaryPublicId);
        if (empty($cloudinaryPublicId)) {
            error_log("CloudinaryImageFetcher::fetchAssetImage - Empty public ID provided");
            throw new Exception("Cloudinary public ID required");
        }
        
        try {
            return $this->processCloudinaryUrlWithRetry($cloudinaryPublicId, $transformations, true);
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::fetchAssetImage - Error fetching asset image: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process Cloudinary URL with retry mechanism
     * 
     * @param string $urlOrPublicId Cloudinary URL or public ID
     * @param array $transformations Transformation parameters
     * @param bool $isPublicId Whether input is a public ID
     * @return array Image data
     */
    private function processCloudinaryUrlWithRetry($urlOrPublicId, $transformations = [], $isPublicId = false) {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $this->processCloudinaryUrl($urlOrPublicId, $transformations, $isPublicId);
            } catch (Exception $e) {
                $lastException = $e;
                error_log("CloudinaryImageFetcher: Attempt $attempt/{$this->maxRetries} failed: " . $e->getMessage());
                
                // Don't retry on the last attempt
                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * $attempt); // Exponential backoff
                }
            }
        }
        
        // All retries failed
        error_log("CloudinaryImageFetcher: All {$this->maxRetries} retry attempts failed for: $urlOrPublicId");
        throw new Exception("Failed to process Cloudinary URL after {$this->maxRetries} attempts: " . $lastException->getMessage());
    }
    
    private function processCloudinaryUrl($urlOrPublicId, $transformations = [], $isPublicId = false) {
        try {
            if (!$this->cloudinary) {
                throw new Exception("Cloudinary not initialized");
            }
            
            $publicId = $isPublicId ? $urlOrPublicId : $this->extractPublicIdFromUrl($urlOrPublicId);
            
            if (empty($publicId)) {
                throw new Exception("Invalid or empty public ID");
            }
            
            if (empty($transformations)) {
                $transformations = [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                    'width' => 800,
                    'crop' => 'limit'
                ];
            }
            
            $imageBuilder = $this->cloudinary->image($publicId);
            
            if (isset($transformations['width'])) {
                $imageBuilder->resize(scale()->width($transformations['width']));
            }
            if (isset($transformations['height'])) {
                $imageBuilder->resize(scale()->height($transformations['height']));
            }
            if (isset($transformations['quality'])) {
                $imageBuilder->delivery(quality($transformations['quality']));
            }
            if (isset($transformations['fetch_format'])) {
                $imageBuilder->delivery(format($transformations['fetch_format']));
            }
            
            $url = $imageBuilder->toUrl();
            
            if (empty($url)) {
                throw new Exception("Failed to generate Cloudinary URL");
            }
            
            return [
                'url' => $url,
                'source' => 'cloudinary',
                'public_id' => $publicId,
                'transformations' => $transformations,
                'secure' => true
            ];
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::processCloudinaryUrl - Error: " . $e->getMessage() . " for: $urlOrPublicId");
            throw $e;
        }
    }
    
    private function extractPublicIdFromUrl($url) {
        if (strpos($url, 'http') !== 0) {
            return $url;
        }
        
        $pattern = '/\/upload\/(?:v\d+\/)?(.+?)(?:\.[a-z]+)?$/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return $url;
    }
    
    public function verifyImageExists($publicId) {
        if (empty($publicId)) {
            error_log("CloudinaryImageFetcher::verifyImageExists - Empty public ID provided");
            return false;
        }
        
        try {
            $result = $this->cloudinary->adminApi()->resource($publicId);
            $exists = isset($result['public_id']);
            error_log("CloudinaryImageFetcher::verifyImageExists - Image $publicId " . ($exists ? "exists" : "not found"));
            return $exists;
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::verifyImageExists - Error checking image $publicId: " . $e->getMessage());
            return false;
        }
    }
    
    public function getImageMetadata($publicId) {
        if (empty($publicId)) {
            error_log("CloudinaryImageFetcher::getImageMetadata - Empty public ID provided");
            throw new Exception("Public ID required");
        }
        
        try {
            $result = $this->cloudinary->adminApi()->resource($publicId);
            
            $metadata = [
                'public_id' => $result['public_id'] ?? null,
                'format' => $result['format'] ?? null,
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'bytes' => $result['bytes'] ?? null,
                'created_at' => $result['created_at'] ?? null,
                'secure_url' => $result['secure_url'] ?? null
            ];
            
            error_log("CloudinaryImageFetcher::getImageMetadata - Successfully retrieved metadata for $publicId");
            return $metadata;
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::getImageMetadata - Failed to get metadata for $publicId: " . $e->getMessage());
            throw new Exception("Failed to get image metadata: " . $e->getMessage());
        }
    }
    
    public function clearCache() {
        $this->cache = [];
    }
    
    public function getCacheStats() {
        return [
            'cached_items' => count($this->cache),
            'cache_expiry' => $this->cacheExpiry
        ];
    }
    
    public function getCloudinaryStatus() {
        try {
            $result = $this->cloudinary->adminApi()->ping();
            error_log("CloudinaryImageFetcher::getCloudinaryStatus - Connection successful");
            return [
                'connected' => true,
                'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
                'status' => 'ok',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::getCloudinaryStatus - Connection failed: " . $e->getMessage());
            return [
                'connected' => false,
                'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Get placeholder image URL from Cloudinary
     * 
     * @return string Cloudinary placeholder image URL
     */
    public function getPlaceholderImage() {
        // Use a Cloudinary placeholder image (generic no-image placeholder)
        // This is a public Cloudinary placeholder that always works
        return 'https://res.cloudinary.com/dvdccumbs/image/upload/v1/placeholder/no-image.jpg';
    }
    
    /**
     * Safely fetch product image with fallback to placeholder
     * 
     * @param int $productId Product ID
     * @param string $imageType Image type (primary/additional)
     * @param array $transformations Transformation parameters
     * @return array Image data with URL (either Cloudinary or placeholder)
     */
    public function fetchProductImageSafe($productId, $imageType = 'primary', $transformations = []) {
        try {
            return $this->fetchProductImage($productId, $imageType, $transformations);
        } catch (Exception $e) {
            error_log("CloudinaryImageFetcher::fetchProductImageSafe - Falling back to placeholder for product $productId: " . $e->getMessage());
            return [
                'url' => $this->getPlaceholderImage(),
                'source' => 'placeholder',
                'public_id' => null,
                'transformations' => [],
                'secure' => true,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ];
        }
    }
}
