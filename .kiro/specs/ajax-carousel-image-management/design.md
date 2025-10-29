# Design Document

## Overview

This design implements an AJAX-based carousel image upload system that mirrors the existing product image AJAX functionality. The system will enable administrators to upload carousel images to Cloudinary with instant feedback, eliminating page reloads and improving the user experience. The implementation will reuse the existing Cloudinary infrastructure and follow the same architectural patterns established in the product image system.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Admin Interface Layer                     │
│  (manage-carousel-images.php)                               │
│  - Form with file inputs                                     │
│  - Image preview containers                                  │
│  - Hidden metadata fields                                    │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  JavaScript Layer                            │
│  (carousel-image-ajax.js)                                   │
│  - File validation                                           │
│  - AJAX upload/delete handlers                              │
│  - UI state management                                       │
│  - Preview rendering                                         │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  Backend API Layer                           │
│  - upload-carousel-image.php (new)                          │
│  - delete-carousel-image.php (new)                          │
│  - Authentication & CSRF validation                          │
│  - Image validation                                          │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│              Cloudinary Integration Layer                    │
│  (cloudinary-helper.php - existing)                         │
│  - Upload to Cloudinary                                      │
│  - Delete from Cloudinary                                    │
│  - Folder: assets/images/carousel/                          │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  Database Layer                              │
│  - carousel_images table (existing)                         │
│  - temp_uploaded_images table (existing)                    │
│  - Orphan tracking                                           │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Backend API Endpoints

#### upload-carousel-image.php

**Purpose**: Handle AJAX carousel image uploads to Cloudinary

**Location**: `backend/api/upload-carousel-image.php`

**Request Format**:
- Method: POST
- Content-Type: multipart/form-data
- Parameters:
  - `image` (file): Image file to upload
  - `csrf_token` (string): CSRF protection token
  - `title` (string, optional): Image title for logging

**Response Format** (JSON):
```json
{
  "success": true,
  "url": "https://res.cloudinary.com/dvdccumbs/image/upload/v1234567890/Home/assets/images/carousel/carousel_1234567890.jpg",
  "public_id": "Home/assets/images/carousel/carousel_1234567890",
  "width": 1920,
  "height": 1080,
  "format": "jpg",
  "bytes": 245678
}
```

**Error Response**:
```json
{
  "success": false,
  "error": "Error message",
  "error_code": "ERROR_CODE"
}
```

**Processing Flow**:
1. Verify admin authentication (session check)
2. Validate CSRF token
3. Validate uploaded file (type, size, dimensions)
4. Generate unique filename with timestamp
5. Upload to Cloudinary folder: `Home/assets/images/carousel/`
6. Log upload to `temp_uploaded_images` table
7. Return JSON response with image metadata

**Security**:
- Session-based admin authentication
- CSRF token validation
- File type validation (JPEG, PNG, GIF, WebP only)
- File size limit (5MB maximum)
- Dimension validation (recommended 1920x1080)

#### delete-carousel-image.php

**Purpose**: Handle AJAX carousel image deletion from Cloudinary

**Location**: `backend/api/delete-carousel-image.php`

**Request Format**:
- Method: POST
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `public_id` (string): Cloudinary public ID
  - `csrf_token` (string): CSRF protection token

**Response Format** (JSON):
```json
{
  "success": true,
  "public_id": "Home/assets/images/carousel/carousel_1234567890",
  "message": "Image deleted successfully"
}
```

**Processing Flow**:
1. Verify admin authentication
2. Validate CSRF token
3. Sanitize public_id parameter
4. Delete from Cloudinary using existing helper
5. Remove from `temp_uploaded_images` table
6. Return JSON response

### 2. Frontend JavaScript Module

#### carousel-image-ajax.js

**Purpose**: Handle client-side AJAX upload/delete operations and UI updates

**Location**: `backend/pages/user-page-content/js/carousel-image-ajax.js`

**Key Functions**:

```javascript
// Upload image to Cloudinary
async function uploadImageToCloudinary(file)

// Delete image from Cloudinary
async function deleteImageFromCloudinary(publicId)

// Store metadata in hidden form fields
function storeImageMetadata(url, publicId)

// Remove metadata from hidden form fields
function removeImageMetadata(publicId)

// Add image preview to UI
function addImagePreview(url, publicId)

// Remove image preview from UI
function removeImagePreview(publicId)

// Validate file before upload
function validateFile(file)

// Show/hide loading indicators
function showLoadingIndicator()
function hideLoadingIndicator()

// Show success/error messages
function showSuccessIndicator()
function showErrorMessage(message)
```

**Event Handlers**:
- File input change → Validate and upload image
- Remove button click → Delete image and update UI
- Form submit → Read metadata from hidden fields

**State Management**:
- Track uploading status
- Track uploaded images count
- Manage UI element states (buttons, indicators)

### 3. CSS Styling

#### carousel-image-ajax.css

**Purpose**: Style AJAX upload interface components

**Location**: `backend/pages/user-page-content/css/carousel-image-ajax.css`

**Key Styles**:
- Image preview containers
- Loading indicators (spinners)
- Success/error notifications
- Remove buttons
- Upload buttons
- Responsive layout

### 4. Updated Admin Page

#### manage-carousel-images.php

**Changes Required**:

1. **Add CSRF Token Generation**:
```php
<?php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
```

2. **Add Hidden Metadata Fields**:
```html
<!-- Image metadata storage -->
<input type="hidden" id="carousel_image_url" name="carousel_image_url">
<input type="hidden" id="carousel_image_public_id" name="carousel_image_public_id">
```

3. **Add Preview Containers**:
```html
<div id="carouselPreviewContainer" class="image-preview-container"></div>
<div id="carouselLoadingIndicator" class="loading-indicator" style="display: none;">
    <i class="fas fa-spinner fa-spin"></i> Uploading...
</div>
<div id="carouselSuccessIndicator" class="success-indicator" style="display: none;">
    <i class="fas fa-check-circle"></i> Upload successful!
</div>
```

4. **Update File Input**:
```html
<input type="file" 
       id="carouselImageInput" 
       name="image" 
       accept="image/jpeg,image/png,image/gif,image/webp">
```

5. **Include JavaScript and CSS**:
```html
<link rel="stylesheet" href="css/carousel-image-ajax.css">
<script src="js/carousel-image-ajax.js"></script>
```

6. **Update Form Submission Handler**:
```php
if (isset($_POST['add_image'])) {
    // Read metadata from hidden fields instead of $_FILES
    $image_url = $_POST['carousel_image_url'] ?? '';
    $public_id = $_POST['carousel_image_public_id'] ?? '';
    
    if (empty($image_url) || empty($public_id)) {
        $error_message = "Please upload an image first.";
    } else {
        // Insert into carousel_images table
        $insert_query = "INSERT INTO carousel_images 
                        (image_url, cloud_url, cloud_public_id, cloud_provider, 
                         title, display_order, is_active, created_by) 
                        VALUES (?, ?, ?, 'cloudinary', ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssssiis", 
            $image_url, $image_url, $public_id, 
            $title, $display_order, $is_active, $_SESSION['admin_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            // Remove from temp_uploaded_images
            $delete_temp = "DELETE FROM temp_uploaded_images WHERE public_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_temp);
            mysqli_stmt_bind_param($delete_stmt, "s", $public_id);
            mysqli_stmt_execute($delete_stmt);
            
            $success_message = "Carousel image added successfully!";
        }
    }
}
```

## Data Models

### carousel_images Table (Existing - Needs Update)

**Current Schema**:
```sql
CREATE TABLE carousel_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT
);
```

**Required Migration**:
```sql
-- Add Cloudinary-specific columns
ALTER TABLE carousel_images 
ADD COLUMN cloud_url TEXT NULL AFTER image_url,
ADD COLUMN cloud_public_id VARCHAR(255) NULL AFTER cloud_url,
ADD COLUMN cloud_provider VARCHAR(50) DEFAULT 'cloudinary' AFTER cloud_public_id;

-- Make image_url nullable for Cloudinary-only storage
ALTER TABLE carousel_images 
MODIFY COLUMN image_url VARCHAR(255) NULL;

-- Add indexes for performance
CREATE INDEX idx_cloud_public_id ON carousel_images(cloud_public_id);
CREATE INDEX idx_display_order ON carousel_images(display_order);
```

**Updated Query for Display**:
```sql
-- Prioritize Cloudinary URLs
SELECT id, 
       COALESCE(cloud_url, image_url) as image_url, 
       title, 
       display_order, 
       is_active 
FROM carousel_images 
WHERE is_active = 1 
ORDER BY display_order ASC;
```

### temp_uploaded_images Table (Existing - Reuse)

**Schema** (already exists from product image system):
```sql
CREATE TABLE temp_uploaded_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(255) NOT NULL UNIQUE,
    cloud_url TEXT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploaded_at (uploaded_at),
    INDEX idx_public_id (public_id)
);
```

**Purpose**: Track uploaded images for orphan cleanup

## Error Handling

### Client-Side Validation

1. **File Type Validation**:
   - Allowed: JPEG, PNG, GIF, WebP
   - Error: "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed."

2. **File Size Validation**:
   - Maximum: 5MB
   - Error: "File size exceeds 5MB limit."

3. **Network Errors**:
   - Timeout: "Upload timed out. Please try again."
   - Connection: "Network error. Please check your connection."

### Server-Side Validation

1. **Authentication Errors**:
   - HTTP 401: "Unauthorized. Please log in as admin."

2. **CSRF Errors**:
   - HTTP 403: "Invalid CSRF token. Please refresh the page."

3. **Upload Errors**:
   - HTTP 400: "Invalid file format"
   - HTTP 413: "File too large"
   - HTTP 500: "Upload failed. Please try again."

4. **Cloudinary Errors**:
   - Rate limit: "Too many uploads. Please wait a moment."
   - Storage limit: "Storage quota exceeded."
   - Invalid credentials: "Configuration error. Please contact support."

### Error Recovery

1. **Retry Logic**:
   - Automatic retry for network errors (max 2 attempts)
   - User-initiated retry for other errors

2. **Rollback**:
   - Failed uploads: No database entry created
   - Failed deletions: Image remains in Cloudinary and temp table

3. **User Feedback**:
   - Clear error messages displayed in UI
   - Error logging for debugging
   - Admin activity logging for audit trail

## Testing Strategy

### Unit Tests

1. **JavaScript Functions**:
   - File validation logic
   - Metadata storage/retrieval
   - UI state management

2. **PHP Functions**:
   - Image validation
   - Temp image logging
   - Metadata extraction

### Integration Tests

1. **Upload Flow**:
   - Select file → Upload → Preview → Save form
   - Verify Cloudinary upload
   - Verify database entry
   - Verify temp table cleanup

2. **Delete Flow**:
   - Upload image → Delete before save
   - Verify Cloudinary deletion
   - Verify temp table cleanup

3. **Edit Flow**:
   - Load existing carousel → Upload new image
   - Verify old image deletion
   - Verify new image upload

### Manual Testing

1. **Browser Compatibility**:
   - Chrome, Firefox, Safari, Edge
   - Mobile browsers

2. **Network Conditions**:
   - Fast connection
   - Slow connection
   - Intermittent connection

3. **Error Scenarios**:
   - Invalid file types
   - Oversized files
   - Network failures
   - Session expiration

4. **Security Testing**:
   - CSRF token validation
   - Authentication bypass attempts
   - File upload exploits

## Implementation Notes

### Reusable Components

The following existing components will be reused:
- `backend/includes/cloudinary-helper.php` - Upload/delete functions
- `temp_uploaded_images` table - Orphan tracking
- CSRF token generation pattern
- Admin authentication checks
- Activity logging functions

### Cloudinary Folder Structure

```
Home/
└── assets/
    └── images/
        └── carousel/
            ├── carousel_1234567890.jpg
            ├── carousel_1234567891.jpg
            └── carousel_1234567892.png
```

**Naming Convention**: `carousel_[timestamp].[extension]`

### Performance Considerations

1. **Image Optimization**:
   - Cloudinary automatic optimization enabled
   - Recommended dimensions: 1920x1080px
   - Format: Auto (Cloudinary chooses best format)

2. **Loading Strategy**:
   - Lazy loading for image previews
   - Progressive JPEG for large images
   - Thumbnail generation for admin list view

3. **Caching**:
   - Cloudinary CDN caching
   - Browser caching for static assets
   - Database query caching for carousel list

### Security Considerations

1. **Authentication**:
   - Session-based admin authentication
   - Role verification (admin role required)

2. **CSRF Protection**:
   - Token generation on page load
   - Token validation on all AJAX requests

3. **File Validation**:
   - MIME type checking
   - File extension validation
   - Image dimension validation
   - File size limits

4. **Input Sanitization**:
   - Public ID sanitization
   - Title escaping
   - SQL injection prevention (prepared statements)

5. **Activity Logging**:
   - Log all upload/delete operations
   - Include admin ID and timestamp
   - Track IP address for audit trail

## Migration Path

### Phase 1: Database Migration
1. Run migration to add Cloudinary columns to carousel_images table
2. Verify temp_uploaded_images table exists

### Phase 2: Backend API
1. Create upload-carousel-image.php endpoint
2. Create delete-carousel-image.php endpoint
3. Test endpoints with Postman/curl

### Phase 3: Frontend JavaScript
1. Create carousel-image-ajax.js
2. Create carousel-image-ajax.css
3. Test upload/delete functionality

### Phase 4: Admin Page Integration
1. Update manage-carousel-images.php
2. Add CSRF token generation
3. Add hidden metadata fields
4. Update form submission handler
5. Include JavaScript and CSS files

### Phase 5: Testing & Validation
1. Test complete upload flow
2. Test delete functionality
3. Test edit functionality
4. Verify orphan cleanup
5. Security testing

### Phase 6: Edit Page Support (Optional)
1. Update edit carousel functionality
2. Support replacing existing images
3. Handle old image deletion

## Future Enhancements

1. **Bulk Upload**: Support multiple carousel images at once
2. **Image Cropping**: Built-in image cropping tool
3. **Drag & Drop**: Drag and drop file upload
4. **Progress Bar**: Detailed upload progress indicator
5. **Image Optimization**: Automatic compression and resizing
6. **Orphan Cleanup Cron**: Automated cleanup of orphaned images
7. **Image Library**: Reuse previously uploaded images
8. **Preview Mode**: Preview carousel before publishing
