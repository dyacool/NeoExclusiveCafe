# Design Document: AJAX Product Image Management with Cloudinary

## Overview

This design implements a modern, real-time image management system that uploads images directly to Cloudinary via AJAX as soon as they are selected. This eliminates the need for temporary folders and provides instant visual feedback. The system handles both adding new products and editing existing products with the same seamless interface.

## Architecture

### System Flow

```
User Selects Image → AJAX Upload to Cloudinary → Store Metadata → Display Preview
                                                                    ↓
User Clicks Remove → AJAX Delete from Cloudinary → Remove Metadata → Update UI
                                                                    ↓
User Submits Form → Associate Images with Product → Save to Database
```

### Key Components

1. **Frontend AJAX Handler** - JavaScript that handles file selection, upload, and UI updates
2. **Backend Upload Endpoint** - PHP endpoint that receives files and uploads to Cloudinary
3. **Backend Delete Endpoint** - PHP endpoint that deletes images from Cloudinary
4. **Image Metadata Tracker** - Hidden form fields that track uploaded image URLs and public IDs
5. **Database Layer** - Stores final image associations in product_images table

## Components and Interfaces

### 1. Frontend JavaScript (product-image-ajax.js)

**Purpose:** Handle all client-side image operations

**Key Functions:**

```javascript
// Upload image to Cloudinary via AJAX
async function uploadImageToCloudinary(file, imageType) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('image_type', imageType); // 'primary' or 'additional'
    formData.append('csrf_token', getCsrfToken());
    
    try {
        showLoadingIndicator(imageType);
        
        const response = await fetch('/backend/api/upload-product-image.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            addImagePreview(result.url, result.public_id, imageType);
            storeImageMetadata(result.url, result.public_id, imageType);
            showSuccessIndicator(imageType);
        } else {
            showErrorMessage(result.error);
        }
    } catch (error) {
        showErrorMessage('Upload failed: ' + error.message);
    } finally {
        hideLoadingIndicator(imageType);
    }
}

// Delete image from Cloudinary via AJAX
async function deleteImageFromCloudinary(publicId, imageType) {
    const formData = new FormData();
    formData.append('public_id', publicId);
    formData.append('csrf_token', getCsrfToken());
    
    try {
        showLoadingIndicator(imageType);
        
        const response = await fetch('/backend/api/delete-product-image.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            removeImagePreview(publicId);
            removeImageMetadata(publicId);
        } else {
            showErrorMessage(result.error);
        }
    } catch (error) {
        showErrorMessage('Delete failed: ' + error.message);
    } finally {
        hideLoadingIndicator(imageType);
    }
}

// Store image metadata in hidden form fields
function storeImageMetadata(url, publicId, imageType) {
    if (imageType === 'primary') {
        document.getElementById('primary_image_url').value = url;
        document.getElementById('primary_image_public_id').value = publicId;
    } else {
        // Add to array of additional images
        const urlsField = document.getElementById('additional_image_urls');
        const idsField = document.getElementById('additional_image_public_ids');
        
        const urls = urlsField.value ? JSON.parse(urlsField.value) : [];
        const ids = idsField.value ? JSON.parse(idsField.value) : [];
        
        urls.push(url);
        ids.push(publicId);
        
        urlsField.value = JSON.stringify(urls);
        idsField.value = JSON.stringify(ids);
    }
}

// Add image preview to UI
function addImagePreview(url, publicId, imageType) {
    const previewContainer = imageType === 'primary' 
        ? document.getElementById('primaryPreviewContainer')
        : document.getElementById('additionalPreviewContainer');
    
    const previewDiv = document.createElement('div');
    previewDiv.className = 'image-preview';
    previewDiv.dataset.publicId = publicId;
    
    previewDiv.innerHTML = `
        <img src="${url}" alt="Product image">
        <button type="button" class="remove-image-btn" onclick="handleRemoveImage('${publicId}', '${imageType}')">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    if (imageType === 'primary') {
        previewContainer.innerHTML = ''; // Replace existing primary image
    }
    
    previewContainer.appendChild(previewDiv);
}
```

### 2. Backend Upload Endpoint (upload-product-image.php)

**Purpose:** Receive image files and upload to Cloudinary

**Implementation:**

```php
<?php
session_start();

// Verify admin authentication
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

require_once __DIR__ . '/../includes/cloudinary-helper.php';

// Validate uploaded file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit();
}

// Validate image type
$validation = validateUploadedImage($_FILES['image']);
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'error' => $validation['error']]);
    exit();
}

// Get image type (primary or additional)
$imageType = $_POST['image_type'] ?? 'additional';

// Generate unique public ID
$timestamp = time();
$randomString = bin2hex(random_bytes(8));
$publicId = 'product_temp_' . $timestamp . '_' . $randomString;

try {
    // Upload to Cloudinary
    $result = uploadToCloudinary(
        $_FILES['image']['tmp_name'],
        'neocafe/products',
        $publicId
    );
    
    if ($result['success']) {
        // Log the upload for orphan cleanup tracking
        logTempImageUpload($result['public_id'], $result['url']);
        
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'public_id' => $result['public_id'],
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Upload failed'
        ]);
    }
} catch (Exception $e) {
    error_log('Image upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Upload failed: ' . $e->getMessage()
    ]);
}

// Delete temporary file
@unlink($_FILES['image']['tmp_name']);

/**
 * Log temporary image upload for orphan cleanup
 */
function logTempImageUpload($publicId, $url) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO temp_uploaded_images (public_id, cloud_url, uploaded_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $publicId, $url);
    $stmt->execute();
    $stmt->close();
}
?>
```

### 3. Backend Delete Endpoint (delete-product-image.php)

**Purpose:** Delete images from Cloudinary

**Implementation:**

```php
<?php
session_start();

// Verify admin authentication
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

require_once __DIR__ . '/../includes/cloudinary-helper.php';

$publicId = $_POST['public_id'] ?? '';

if (empty($publicId)) {
    echo json_encode(['success' => false, 'error' => 'No public ID provided']);
    exit();
}

try {
    // Delete from Cloudinary
    $result = deleteFromCloudinary($publicId);
    
    if ($result['success']) {
        // Remove from temp tracking table
        removeTempImageLog($publicId);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Delete failed'
        ]);
    }
} catch (Exception $e) {
    error_log('Image delete error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Delete failed: ' . $e->getMessage()
    ]);
}

/**
 * Remove temporary image from tracking table
 */
function removeTempImageLog($publicId) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM temp_uploaded_images WHERE public_id = ?");
    $stmt->bind_param("s", $publicId);
    $stmt->execute();
    $stmt->close();
}
?>
```

### 4. Form Submission Handler (add-product.php / edit-product.php)

**Purpose:** Associate uploaded images with product when form is submitted

**Implementation:**

```php
// In add-product.php after product is created
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... create product and get $product_id ...
    
    // Handle primary image
    $primaryImageUrl = $_POST['primary_image_url'] ?? '';
    $primaryImagePublicId = $_POST['primary_image_public_id'] ?? '';
    
    if (!empty($primaryImageUrl) && !empty($primaryImagePublicId)) {
        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 1)");
        $stmt->bind_param("iss", $product_id, $primaryImageUrl, $primaryImagePublicId);
        $stmt->execute();
        
        // Remove from temp tracking (no longer orphaned)
        removeTempImageLog($primaryImagePublicId);
    }
    
    // Handle additional images
    $additionalUrls = $_POST['additional_image_urls'] ?? '';
    $additionalPublicIds = $_POST['additional_image_public_ids'] ?? '';
    
    if (!empty($additionalUrls) && !empty($additionalPublicIds)) {
        $urls = json_decode($additionalUrls, true);
        $publicIds = json_decode($additionalPublicIds, true);
        
        if (is_array($urls) && is_array($publicIds) && count($urls) === count($publicIds)) {
            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 0)");
            
            foreach ($urls as $index => $url) {
                $publicId = $publicIds[$index];
                $stmt->bind_param("iss", $product_id, $url, $publicId);
                $stmt->execute();
                
                // Remove from temp tracking
                removeTempImageLog($publicId);
            }
        }
    }
}
```

### 5. HTML Form Structure

**Hidden Fields for Image Metadata:**

```html
<!-- Primary Image Metadata -->
<input type="hidden" id="primary_image_url" name="primary_image_url" value="">
<input type="hidden" id="primary_image_public_id" name="primary_image_public_id" value="">

<!-- Additional Images Metadata (JSON arrays) -->
<input type="hidden" id="additional_image_urls" name="additional_image_urls" value="">
<input type="hidden" id="additional_image_public_ids" name="additional_image_public_ids" value="">

<!-- CSRF Token -->
<input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
```

**File Input Elements:**

```html
<!-- Primary Image Upload -->
<div class="image-upload primary-image-upload">
    <input type="file" id="primaryImageInput" accept="image/*" style="display: none;">
    <label for="primaryImageInput" class="upload-btn">
        Click to Upload Primary Image
    </label>
    <div class="primary-preview-container" id="primaryPreviewContainer"></div>
    <div class="loading-indicator" id="primaryLoadingIndicator" style="display: none;">
        <i class="fas fa-spinner fa-spin"></i> Uploading...
    </div>
</div>

<!-- Additional Images Upload -->
<div class="image-upload additional-images-upload">
    <input type="file" id="additionalImagesInput" accept="image/*" multiple style="display: none;">
    <label for="additionalImagesInput" class="upload-btn">
        Click to Upload Additional Images (Max 3)
    </label>
    <div class="additional-preview-container" id="additionalPreviewContainer"></div>
    <div class="loading-indicator" id="additionalLoadingIndicator" style="display: none;">
        <i class="fas fa-spinner fa-spin"></i> Uploading...
    </div>
</div>
```

## Data Models

### Database Tables

**product_images table (existing):**
```sql
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NULL,  -- Make nullable
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

**temp_uploaded_images table (new - for orphan cleanup):**
```sql
CREATE TABLE temp_uploaded_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    public_id VARCHAR(255) NOT NULL UNIQUE,
    cloud_url TEXT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploaded_at (uploaded_at)
);
```

## Error Handling

### Upload Errors

```javascript
function handleUploadError(error, imageType) {
    const errorMessages = {
        'file_too_large': 'Image file is too large (max 10MB)',
        'invalid_type': 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed',
        'upload_failed': 'Upload failed. Please try again',
        'network_error': 'Network error. Please check your connection'
    };
    
    const message = errorMessages[error] || error;
    showErrorNotification(message);
    
    // Log error for debugging
    console.error('Upload error:', error);
}
```

### Delete Errors

```javascript
function handleDeleteError(error, publicId) {
    showErrorNotification('Failed to delete image. Please try again.');
    console.error('Delete error:', error, 'Public ID:', publicId);
    
    // Keep the image preview visible so user can retry
}
```

## Testing Strategy

### Unit Tests
- Test image validation functions
- Test metadata storage/retrieval
- Test AJAX request/response handling

### Integration Tests
- Test complete upload flow (select → upload → preview → save)
- Test complete delete flow (remove → delete from Cloudinary → update UI)
- Test form submission with multiple images
- Test edit mode with existing images

### Manual Testing
- Upload primary image and verify Cloudinary storage
- Upload multiple additional images
- Remove images before saving
- Submit form and verify database records
- Edit product and replace images
- Test error scenarios (network failure, invalid files)

## Security Considerations

### Authentication & Authorization
- All AJAX endpoints verify admin session
- CSRF tokens required for all operations
- File uploads restricted to authenticated admins only

### Input Validation
- Server-side file type validation
- File size limits enforced
- Filename sanitization
- Public ID generation uses secure random strings

### Cloudinary Security
- Use signed uploads for sensitive operations
- Implement rate limiting on upload endpoints
- Log all upload/delete operations
- Regular orphan cleanup to prevent storage abuse

## Performance Considerations

### Optimization Strategies
- Upload images sequentially to avoid overwhelming the server
- Use image compression before upload (optional)
- Implement client-side image preview before upload
- Cache Cloudinary URLs in browser

### Orphan Cleanup
- Run cleanup cron job daily to remove images older than 24 hours from temp_uploaded_images
- Delete corresponding images from Cloudinary
- Log cleanup operations for audit

```php
// cleanup-orphaned-images.php (cron job)
$stmt = $conn->query("SELECT public_id FROM temp_uploaded_images WHERE uploaded_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

while ($row = $stmt->fetch_assoc()) {
    deleteFromCloudinary($row['public_id']);
    $conn->query("DELETE FROM temp_uploaded_images WHERE public_id = '{$row['public_id']}'");
}
```

## Migration from Current System

### Step 1: Database Updates
```sql
-- Make image_url nullable
ALTER TABLE product_images MODIFY COLUMN image_url VARCHAR(255) NULL;

-- Create temp tracking table
CREATE TABLE temp_uploaded_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    public_id VARCHAR(255) NOT NULL UNIQUE,
    cloud_url TEXT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploaded_at (uploaded_at)
);
```

### Step 2: Update add-product.php
- Remove direct file upload handling
- Add hidden fields for image metadata
- Update form submission to use metadata from hidden fields

### Step 3: Create AJAX Endpoints
- Create upload-product-image.php
- Create delete-product-image.php

### Step 4: Add JavaScript
- Create product-image-ajax.js
- Include in add-product.php and edit-product.php

### Step 5: Update Edit Functionality
- Load existing images into preview containers
- Enable same AJAX upload/delete for editing

### Step 6: Setup Orphan Cleanup
- Create cleanup-orphaned-images.php cron job
- Schedule to run daily
