# Error Handling Quick Reference Guide

## For Upload Operations

### Using `uploadToCloudinary()`

```php
require_once __DIR__ . '/backend/includes/cloudinary-helper.php';

$result = uploadToCloudinary($filePath, 'neocafe/products', 'product_123_primary');

if ($result['success']) {
    // Success - use the URL
    $cloudinaryUrl = $result['url'];
    $publicId = $result['public_id'];
    
    // Save to database...
} else {
    // Handle error
    $errorMessage = $result['error']; // User-friendly message
    $errorCode = $result['error_code']; // Machine-readable code
    
    // Log for debugging
    error_log("Upload failed: $errorMessage (Code: $errorCode)");
    
    // Show to user
    $_SESSION['error_message'] = $errorMessage;
}
```

### Error Codes
- `FILE_NOT_FOUND`: File doesn't exist
- `FILE_NOT_READABLE`: Permission issue
- `INVALID_IMAGE`: Not a valid image
- `FILE_TOO_LARGE`: Exceeds 10MB
- `CLOUDINARY_EXCEPTION`: API error

### With Transaction Rollback

```php
$conn->begin_transaction();

try {
    // Upload to Cloudinary
    $result = uploadToCloudinary($filePath, 'neocafe/products', 'product_123');
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    // Save to database
    $stmt = $conn->prepare("INSERT INTO product_images (product_id, cloud_url, cloud_public_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $productId, $result['url'], $result['public_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Database save failed");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Clean up temp file
    @unlink($filePath);
    
} catch (Exception $e) {
    // Rollback database
    $conn->rollback();
    
    // Delete from Cloudinary if uploaded
    if (isset($result['public_id'])) {
        deleteFromCloudinary($result['public_id']);
    }
    
    // Clean up temp file
    @unlink($filePath);
    
    // Handle error
    error_log("Upload failed: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to upload image. Please try again.";
}
```

## For Display Operations

### Using `CloudinaryImageFetcher` (Throws Exceptions)

```php
require_once __DIR__ . '/backend/includes/cloudinary-image-fetcher.php';

try {
    $fetcher = new CloudinaryImageFetcher($conn);
    $imageData = $fetcher->fetchProductImage($productId);
    
    // Use the image
    $imageUrl = $imageData['url'];
    echo "<img src='$imageUrl' alt='Product'>";
    
} catch (Exception $e) {
    // Handle error
    error_log("Failed to fetch image for product $productId: " . $e->getMessage());
    
    // Show placeholder
    echo "<img src='/assets/images/placeholder-product.png' alt='Product'>";
}
```

### Using Safe Fetch (Never Throws)

```php
$fetcher = new CloudinaryImageFetcher($conn);
$imageData = $fetcher->fetchProductImageSafe($productId);

// Always returns data - check source
if ($imageData['source'] === 'cloudinary') {
    // Real image
    $imageUrl = $imageData['url'];
} else {
    // Placeholder (error occurred)
    $imageUrl = $imageData['url']; // Placeholder path
    $error = $imageData['error']; // Error message
    error_log("Using placeholder for product $productId: $error");
}

echo "<img src='$imageUrl' alt='Product'>";
```

### Batch Fetching with Error Handling

```php
$fetcher = new CloudinaryImageFetcher($conn);

try {
    // Skip products without Cloudinary URLs
    $images = $fetcher->fetchMultipleProductImages(
        $productIds,
        ['width' => 300, 'quality' => 'auto'],
        true // skipMissing = true
    );
    
    // Display products
    foreach ($products as $product) {
        if (isset($images[$product['id']])) {
            $imageUrl = $images[$product['id']]['url'];
        } else {
            $imageUrl = '/assets/images/placeholder-product.png';
        }
        
        echo "<img src='$imageUrl' alt='{$product['name']}'>";
    }
    
} catch (Exception $e) {
    error_log("Batch fetch failed: " . $e->getMessage());
    // All images will use placeholder
}
```

### HTML with Fallback

```php
<img src="<?php echo htmlspecialchars($imageUrl); ?>" 
     alt="<?php echo htmlspecialchars($productName); ?>"
     loading="lazy"
     onerror="this.onerror=null; this.src='/assets/images/placeholder-product.png';">
```

## For Delete Operations

### Using `deleteFromCloudinary()`

```php
$result = deleteFromCloudinary($publicId);

if ($result['success']) {
    // Successfully deleted
    error_log("Deleted image: {$result['public_id']}");
} else {
    // Handle error
    $errorCode = $result['error_code'];
    
    if ($errorCode === 'NOT_FOUND') {
        // Image already deleted or never existed
        error_log("Image not found: $publicId");
    } else {
        // Other error
        error_log("Delete failed: {$result['error']}");
    }
}
```

## Checking Cloudinary Status

```php
$fetcher = new CloudinaryImageFetcher($conn);
$status = $fetcher->getCloudinaryStatus();

if ($status['connected']) {
    echo "Cloudinary is connected";
} else {
    echo "Cloudinary connection failed: {$status['error']}";
}
```

## Common Patterns

### Pattern 1: Upload with Validation

```php
// Validate file
$validation = validateUploadedImage($_FILES['image']);
if (!$validation['valid']) {
    $_SESSION['error_message'] = $validation['error'];
    header("Location: add-product.php");
    exit();
}

// Upload to Cloudinary
$result = uploadToCloudinary($_FILES['image']['tmp_name'], 'neocafe/products', 'product_' . $productId);

if (!$result['success']) {
    $_SESSION['error_message'] = $result['error'];
    header("Location: add-product.php");
    exit();
}

// Success
$_SESSION['success_message'] = "Image uploaded successfully";
```

### Pattern 2: Display with Graceful Degradation

```php
$fetcher = new CloudinaryImageFetcher($conn);

try {
    $images = $fetcher->fetchMultipleProductImages($productIds, ['width' => 300], true);
} catch (Exception $e) {
    error_log("Image fetch failed: " . $e->getMessage());
    $images = [];
}

foreach ($products as $product) {
    $imageUrl = $images[$product['id']]['url'] ?? '/assets/images/placeholder-product.png';
    echo "<img src='$imageUrl' alt='{$product['name']}' onerror=\"this.src='/assets/images/placeholder-product.png'\">";
}
```

### Pattern 3: Replace Image with Cleanup

```php
// Get old image
$oldPublicId = getOldPublicId($productId);

// Upload new image
$result = uploadToCloudinary($newImagePath, 'neocafe/products', 'product_' . $productId);

if ($result['success']) {
    // Update database
    updateProductImage($productId, $result['url'], $result['public_id']);
    
    // Delete old image
    if ($oldPublicId) {
        $deleteResult = deleteFromCloudinary($oldPublicId);
        if (!$deleteResult['success']) {
            error_log("Warning: Failed to delete old image: $oldPublicId");
        }
    }
} else {
    error_log("Failed to upload new image: {$result['error']}");
}
```

## Logging Best Practices

### What to Log

```php
// Success operations
error_log("Successfully uploaded image: $publicId (URL: $url)");

// Failures with context
error_log("Upload failed for product $productId: $errorMessage (Code: $errorCode)");

// Warnings
error_log("Warning: Failed to delete old image $publicId, but continuing");

// Retry attempts
error_log("Retry attempt 1/2 failed: $errorMessage");
```

### What NOT to Log

- User passwords or sensitive data
- Full file paths (use basename())
- API keys or credentials
- Personal information

## Error Messages for Users

### Good Messages
- "Failed to upload image. Please try again."
- "Image size exceeds 10MB limit."
- "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed."
- "Upload timeout - please check your connection."

### Bad Messages
- "Exception in uploadToCloudinary() at line 42"
- "SQLSTATE[HY000]: General error"
- "Cloudinary API returned 500"

## Testing Error Handling

```bash
# Run comprehensive tests
php test-error-handling.php

# Check error logs
tail -f logs/php_errors.log
```

## Troubleshooting

### Issue: Images not displaying
1. Check error logs for fetch failures
2. Verify Cloudinary connection: `$fetcher->getCloudinaryStatus()`
3. Check if product has Cloudinary URL in database
4. Verify placeholder image exists

### Issue: Upload fails
1. Check file permissions
2. Verify file size < 10MB
3. Check Cloudinary credentials
4. Review error logs for specific error code

### Issue: Old images not deleted
1. Check if public ID is correct
2. Verify Cloudinary credentials have delete permission
3. Review error logs for delete failures
4. Note: This is logged as warning, not critical error

## Configuration

### Adjust Retry Settings

```php
// In CloudinaryImageFetcher class
private $maxRetries = 2; // Change to 3 for more retries
private $retryDelay = 1000000; // Change to 2000000 for 2 second delay
```

### Change Placeholder Image

```php
// In CloudinaryImageFetcher class
public function getPlaceholderImage() {
    return '/assets/images/your-custom-placeholder.png';
}
```
