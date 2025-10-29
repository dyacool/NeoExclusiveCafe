# Design Document: Cloudinary Product Image Integration

## Overview

This design outlines the technical implementation for integrating Cloudinary throughout the product management system. The solution replaces all local file storage with secure Cloudinary URLs, using the CloudinaryImageFetcher class for centralized, secure image retrieval.

## Architecture

### System Flow

```
Admin Upload → Validation → Cloudinary Upload → Store URL in DB → Display via Fetcher
```

### Key Components

1. **CloudinaryImageFetcher** - Centralized image retrieval class (already created)
2. **Database Schema** - Columns for Cloudinary URLs
3. **Upload Handler** - Direct Cloudinary upload logic
4. **Display Layer** - Uses fetcher to show images
5. **Edit Handler** - Updates Cloudinary images and URLs

## Components and Interfaces

### 1. Database Schema Updates

**Current Schema:** `product_images` table already has Cloudinary columns

```sql
-- Existing product_images table structure:
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,  -- Legacy local path (make nullable)
  `cloud_public_id` varchar(255) DEFAULT NULL,
  `cloud_provider` varchar(50) DEFAULT 'cloudinary',
  `cloud_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_removed` tinyint(1) DEFAULT 0,
  `temp_filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `cloud_public_id` (`cloud_public_id`)
);
```

**Migration Needed:** Make `image_url` nullable for Cloudinary-only images

```sql
-- Allow image_url to be NULL since we're using cloud_url
ALTER TABLE product_images MODIFY COLUMN image_url varchar(255) NULL;
```

### 2. Image Upload Flow (Add Product)

**File:** `backend/pages/products/add-product.php`

**Current Flow:**
```
Upload → Save to /assets/product-images/ → Try Cloudinary → Store local path
```

**New Flow:**
```
Upload → Validate → Upload to Cloudinary → Store Cloudinary URL → Delete temp file
```

**Implementation:**

```php
// Validate uploaded file
$validation = validateImageFile($_FILES['primary_image']['tmp_name']);
if (!$validation['valid']) {
    throw new Exception($validation['error']);
}

// Upload directly to Cloudinary
require_once __DIR__ . '/../../includes/cloudinary-helper.php';
$publicId = 'neocafe/products/product_' . $product_id . '_primary';
$result = uploadToCloudinary($_FILES['primary_image']['tmp_name'], 'neocafe/products', $publicId);

if ($result['success']) {
    // Store Cloudinary URL in database
    $cloudinaryUrl = $result['url'];
    $stmt = $conn->prepare("UPDATE products SET cloudinary_url = ? WHERE id = ?");
    $stmt->bind_param("si", $cloudinaryUrl, $product_id);
    $stmt->execute();
    
    // Delete temporary file
    unlink($_FILES['primary_image']['tmp_name']);
} else {
    throw new Exception("Cloudinary upload failed: " . $result['error']);
}
```

### 3. Image Display Flow (Product List)

**File:** `backend/pages/products/product-list.php`

**Current Flow:**
```
Query DB → Get image_path → Display local image
```

**New Flow:**
```
Query DB → Get product IDs → Batch fetch via CloudinaryImageFetcher → Display optimized images
```

**Implementation:**

```php
require_once __DIR__ . '/../../includes/cloudinary-image-fetcher.php';

// Get products
$sql = "SELECT id, name, price, stock FROM products WHERE deleted_at IS NULL";
$result = $conn->query($sql);

$productIds = [];
while ($row = $result->fetch_assoc()) {
    $productIds[] = $row['id'];
    $products[] = $row;
}

// Batch fetch images
$fetcher = new CloudinaryImageFetcher($conn);
$images = $fetcher->fetchMultipleProductImages($productIds, ['width' => 300], true);

// Display
foreach ($products as $product) {
    $imageData = $images[$product['id']] ?? null;
    if ($imageData) {
        echo '<img src="' . htmlspecialchars($imageData['url']) . '" alt="' . htmlspecialchars($product['name']) . '">';
    } else {
        echo '<img src="/assets/images/placeholder.png" alt="No image">';
    }
}
```

### 4. Image Display Flow (Product Dashboard)

**File:** `frontend/pages/products/product-dashboard.php`

**Similar to product list but with responsive transformations:**

```php
// For mobile
$images = $fetcher->fetchMultipleProductImages($productIds, ['width' => 400]);

// For desktop
$images = $fetcher->fetchMultipleProductImages($productIds, ['width' => 800]);
```

### 5. Image Edit Flow

**File:** `backend/pages/products/edit-product.php` (or similar)

**Flow:**
```
Get existing Cloudinary URL → Upload new image → Delete old from Cloudinary → Update DB
```

**Implementation:**

```php
// Get existing image
$sql = "SELECT cloudinary_url FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$oldCloudinaryUrl = $product['cloudinary_url'];

// Upload new image
$publicId = 'neocafe/products/product_' . $product_id . '_primary';
$result = uploadToCloudinary($_FILES['primary_image']['tmp_name'], 'neocafe/products', $publicId);

if ($result['success']) {
    // Update database
    $stmt = $conn->prepare("UPDATE products SET cloudinary_url = ? WHERE id = ?");
    $stmt->bind_param("si", $result['url'], $product_id);
    $stmt->execute();
    
    // Delete old image from Cloudinary (if different)
    if ($oldCloudinaryUrl && $oldCloudinaryUrl !== $result['url']) {
        $oldPublicId = extractPublicIdFromUrl($oldCloudinaryUrl);
        deleteFromCloudinary($oldPublicId);
    }
}
```

### 6. Additional Images Handling

**Storage Format:** JSON array in `cloudinary_additional_images` column

```json
[
    "https://res.cloudinary.com/dvdccumbs/image/upload/v123/product_1_add_1.jpg",
    "https://res.cloudinary.com/dvdccumbs/image/upload/v123/product_1_add_2.jpg",
    "https://res.cloudinary.com/dvdccumbs/image/upload/v123/product_1_add_3.jpg"
]
```

**Upload Logic:**

```php
$additionalUrls = [];
foreach ($_FILES['additional_images']['tmp_name'] as $index => $tmpName) {
    $publicId = 'neocafe/products/product_' . $product_id . '_add_' . ($index + 1);
    $result = uploadToCloudinary($tmpName, 'neocafe/products', $publicId);
    if ($result['success']) {
        $additionalUrls[] = $result['url'];
    }
}

// Store as JSON
$jsonUrls = json_encode($additionalUrls);
$stmt = $conn->prepare("UPDATE products SET cloudinary_additional_images = ? WHERE id = ?");
$stmt->bind_param("si", $jsonUrls, $product_id);
$stmt->execute();
```

## Data Models

### Product Images Table Schema

```sql
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NULL,  -- Legacy local path (nullable for Cloudinary-only)
    cloud_public_id VARCHAR(255) NULL,
    cloud_provider VARCHAR(50) DEFAULT 'cloudinary',
    cloud_url TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_removed TINYINT(1) DEFAULT 0,
    temp_filename VARCHAR(255) NULL,
    
    INDEX idx_product_id (product_id),
    INDEX idx_cloud_public_id (cloud_public_id)
);
```

### Image Data Structure (from CloudinaryImageFetcher)

```php
[
    'url' => 'https://res.cloudinary.com/dvdccumbs/image/upload/...',
    'source' => 'cloudinary',
    'public_id' => 'neocafe/products/product_1_primary',
    'transformations' => ['width' => 800, 'quality' => 'auto'],
    'secure' => true,
    'product_id' => 1,
    'product_name' => 'Product Name'
]
```

## Error Handling

### Upload Errors

```php
try {
    $result = uploadToCloudinary($tmpFile, 'neocafe/products', $publicId);
    if (!$result['success']) {
        throw new Exception("Upload failed: " . $result['error']);
    }
} catch (Exception $e) {
    // Log error
    error_log("Cloudinary upload error for product {$product_id}: " . $e->getMessage());
    
    // Show user-friendly message
    $_SESSION['error'] = "Failed to upload image. Please try again.";
    
    // Rollback product creation if necessary
    $conn->query("DELETE FROM products WHERE id = {$product_id}");
}
```

### Display Errors

```php
try {
    $imageData = $fetcher->fetchProductImage($product_id);
    $imageUrl = $imageData['url'];
} catch (Exception $e) {
    // Log error
    error_log("Failed to fetch image for product {$product_id}: " . $e->getMessage());
    
    // Use placeholder
    $imageUrl = '/assets/images/placeholder-product.png';
}
```

## Testing Strategy

### 1. Unit Tests
- Test CloudinaryImageFetcher methods
- Test upload validation
- Test URL extraction

### 2. Integration Tests
- Test complete upload flow
- Test complete display flow
- Test edit flow with image replacement

### 3. Manual Testing
- Upload new product with images
- View product list
- View product dashboard
- Edit product and replace images
- Test with no Cloudinary URL (fallback)

### 4. Test Pages
- `test-cloudinary-images-display.php` - Visual verification
- `test-cloudinary-simple.php` - Diagnostic checks
- `add-cloudinary-columns.php` - Database migration

## Performance Considerations

### Batch Fetching
Use `fetchMultipleProductImages()` instead of individual calls:

```php
// ❌ Slow - N queries
foreach ($products as $product) {
    $image = $fetcher->fetchProductImage($product['id']);
}

// ✅ Fast - 1 query + batch processing
$images = $fetcher->fetchMultipleProductImages($productIds);
```

### Caching
CloudinaryImageFetcher has built-in caching (1 hour):
- Reduces API calls
- Improves response time
- Automatic cache invalidation

### Lazy Loading
```html
<img src="cloudinary-url" loading="lazy" alt="Product">
```

### Responsive Images
```php
// Mobile
$mobile = $fetcher->fetchProductImage($id, 'primary', ['width' => 400]);

// Desktop
$desktop = $fetcher->fetchProductImage($id, 'primary', ['width' => 1200]);
```

## Security Considerations

### 1. No Local File Storage
- All images go directly to Cloudinary
- No files stored in `/assets/product-images/`
- Temporary files deleted immediately

### 2. HTTPS Only
- All Cloudinary URLs use HTTPS
- Secure image delivery

### 3. Input Validation
- File type validation
- File size limits (10MB)
- Image dimension checks
- Filename sanitization

### 4. Access Control
- Only admins can upload images
- Session validation required
- CSRF protection on forms

### 5. Error Logging
- All Cloudinary errors logged
- No sensitive data in error messages
- Security audit trail

## Migration Strategy

### Phase 1: Add Database Columns
Run `add-cloudinary-columns.php`

### Phase 2: Update Add Product
Modify upload logic to use Cloudinary

### Phase 3: Update Display Pages
Use CloudinaryImageFetcher for all displays

### Phase 4: Update Edit Product
Handle image replacement via Cloudinary

### Phase 5: Test Everything
Use test pages to verify

### Phase 6: Migrate Existing Images (Optional)
Run migration script to upload existing local images to Cloudinary

## Rollback Plan

If issues occur:
1. Keep `image_path` columns as backup
2. Can temporarily switch back to local images
3. Cloudinary images remain in cloud
4. Re-run migration after fixes
