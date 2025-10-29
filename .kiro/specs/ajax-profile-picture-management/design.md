# Design Document

## Overview

This design implements an AJAX-based profile picture upload system for both admin users and customer users, following the proven architecture from the carousel and product image systems. The system enables real-time profile picture uploads to Cloudinary with instant feedback, automatic image compression, and seamless integration with the existing user management system.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface Layer                      │
│  Admin: backend/pages/account/admin-account.php            │
│  Customer: frontend/pages/account/customer-account.php     │
│  - Avatar display with upload overlay                       │
│  - Hidden metadata fields                                   │
│  - Remove button for existing images                        │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  JavaScript Layer                            │
│  - backend/pages/account/js/profile-picture-ajax.js        │
│  - frontend/pages/account/js/profile-picture-ajax.js       │
│  - File validation & compression                            │
│  - AJAX upload/delete handlers                              │
│  - UI state management                                       │
│  - Avatar preview rendering                                  │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  Backend API Layer                           │
│  - backend/api/upload-profile-picture.php (new)            │
│  - backend/api/delete-profile-picture.php (new)            │
│  - frontend/api/upload-profile-picture.php (new)           │
│  - frontend/api/delete-profile-picture.php (new)           │
│  - Authentication & CSRF validation                          │
│  - Image validation & compression                            │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│              Cloudinary Integration Layer                    │
│  (cloudinary-helper.php - existing)                         │
│  - Upload to Cloudinary                                      │
│  - Delete from Cloudinary                                    │
│  - Admin folder: Home/assets/public/admin-profile-images/  │
│  - Customer folder: Home/assets/public/profile-images/     │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  Database Layer                              │
│  - users table (updated with Cloudinary columns)            │
│  - temp_uploaded_images table (existing)                    │
│  - Orphan tracking                                           │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Backend API Endpoints

#### Admin Profile Picture Upload

**Location**: `backend/api/upload-profile-picture.php`

**Purpose**: Handle AJAX admin profile picture uploads to Cloudinary

**Request Format**:
- Method: POST
- Content-Type: multipart/form-data
- Parameters:
  - `image` (file): Image file to upload
  - `csrf_token` (string): CSRF protection token
  - `user_id` (int): Admin user ID

**Response Format** (JSON):
```json
{
  "success": true,
  "url": "https://res.cloudinary.com/dvdccumbs/image/upload/v1234567890/Home/assets/public/admin-profile-images/profile_123_1234567890.jpg",
  "public_id": "Home/assets/public/admin-profile-images/profile_123_1234567890",
  "width": 500,
  "height": 500,
  "format": "jpg",
  "bytes": 45678
}
```

**Processing Flow**:
1. Verify admin authentication (session check)
2. Validate CSRF token
3. Validate uploaded file (type, size, dimensions)
4. Check for existing profile picture and delete from Cloudinary
5. Generate unique filename: `profile_[user_id]_[timestamp].[ext]`
6. Upload to Cloudinary folder: `Home/assets/public/admin-profile-images/`
7. Log upload to `temp_uploaded_images` table
8. Update `users` table with new Cloudinary URL and public_id
9. Remove from `temp_uploaded_images` after successful save
10. Return JSON response with image metadata

**Security**:
- Session-based admin authentication
- CSRF token validation
- File type validation (JPEG, PNG, GIF, WebP only)
- File size limit (10MB maximum, 2MB after compression)
- User ID verification (can only upload own profile picture)

#### Customer Profile Picture Upload

**Location**: `frontend/api/upload-profile-picture.php`

**Purpose**: Handle AJAX customer profile picture uploads to Cloudinary

**Request Format**: Same as admin endpoint

**Response Format**: Same as admin endpoint

**Processing Flow**: Same as admin endpoint, but:
- Upload to folder: `Home/assets/public/profile-images/`
- Verify customer authentication instead of admin

#### Profile Picture Deletion

**Location**: 
- `backend/api/delete-profile-picture.php` (admin)
- `frontend/api/delete-profile-picture.php` (customer)

**Purpose**: Handle AJAX profile picture deletion from Cloudinary

**Request Format**:
- Method: POST
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `public_id` (string): Cloudinary public ID
  - `csrf_token` (string): CSRF protection token
  - `user_id` (int): User ID

**Response Format** (JSON):
```json
{
  "success": true,
  "public_id": "Home/assets/public/admin-profile-images/profile_123_1234567890",
  "message": "Profile picture deleted successfully"
}
```

**Processing Flow**:
1. Verify authentication (admin or customer)
2. Validate CSRF token
3. Verify user owns the profile picture
4. Delete from Cloudinary using existing helper
5. Update `users` table to clear cloud_url and cloud_public_id
6. Remove from `temp_uploaded_images` table
7. Return JSON response

### 2. Frontend JavaScript Module

#### profile-picture-ajax.js

**Purpose**: Handle client-side AJAX upload/delete operations and UI updates

**Location**: 
- `backend/pages/account/js/profile-picture-ajax.js` (admin)
- `frontend/pages/account/js/profile-picture-ajax.js` (customer)

**Key Functions**:

```javascript
// Upload profile picture to Cloudinary
async function uploadProfilePictureToCloudinary(file, userId)

// Delete profile picture from Cloudinary
async function deleteProfilePictureFromCloudinary(publicId, userId)

// Update avatar display with new image
function updateAvatarDisplay(url)

// Revert to initials display
function revertToInitials(firstname, lastname)

// Validate file before upload
function validateFile(file)

// Compress image before upload
async function compressImage(file, maxSizeMB = 2, maxWidth = 500)

// Show/hide loading indicators
function showLoadingIndicator()
function hideLoadingIndicator()

// Show success/error messages
function showSuccessMessage(message)
function showErrorMessage(message)
```

**Event Handlers**:
- Avatar container click → Open file picker
- File input change → Validate, compress, and upload image
- Remove button click → Delete image and revert to initials
- Name input change → Update initials display

**State Management**:
- Track uploading status
- Track current profile picture URL and public_id
- Manage UI element states (avatar, buttons, indicators)

### 3. CSS Styling

#### profile-picture-ajax.css

**Purpose**: Style AJAX upload interface components

**Location**: 
- `backend/pages/account/css/profile-picture-ajax.css` (admin)
- `frontend/pages/account/css/profile-picture-ajax.css` (customer)

**Key Styles**:
- Avatar container with hover overlay
- Loading indicators (spinners)
- Success/error notifications
- Remove button styling
- Upload overlay
- Responsive layout for mobile

### 4. Updated Account Pages

#### admin-account.php

**Changes Required**:

1. **Add CSRF Token Generation**:
```php
<?php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
<input type="hidden" id="user_id" value="<?php echo $_SESSION['admin_id']; ?>">
```

2. **Update Avatar Display to Support Cloudinary**:
```php
// Determine profile image url - prioritize Cloudinary
$profile_image_url = '';
$profile_public_id = '';
$has_profile_image = false;

if (isset($admin['cloud_url']) && !empty(trim($admin['cloud_url']))) {
    $profile_image_url = trim($admin['cloud_url']);
    $profile_public_id = $admin['cloud_public_id'] ?? '';
    $has_profile_image = true;
} elseif (isset($admin['profile_image']) && !empty(trim($admin['profile_image']))) {
    $db_path = trim($admin['profile_image']);
    if ($db_path[0] !== '/') {
        $db_path = '/' . $db_path;
    }
    $profile_image_url = $db_path;
    $has_profile_image = true;
}
```

3. **Add Remove Button**:
```html
<div class="avatar-upload-container" id="avatar-upload-container">
    <div class="avatar" id="avatar">
        <?php if ($has_profile_image): ?>
            <img id="profile-image" src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile picture">
        <?php else: ?>
            <span id="initials"><?php echo strtoupper(substr($admin['firstname'], 0, 1) . substr($admin['lastname'], 0, 1)); ?></span>
        <?php endif; ?>
    </div>
    <div class="avatar-overlay">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
            <circle cx="12" cy="13" r="3"></circle>
        </svg>
    </div>
    <?php if ($has_profile_image && !empty($profile_public_id)): ?>
        <button type="button" class="remove-avatar-btn" id="remove-avatar-btn" data-public-id="<?php echo htmlspecialchars($profile_public_id); ?>">
            <i class="fas fa-times"></i>
        </button>
    <?php endif; ?>
</div>
```

4. **Add Loading and Success Indicators**:
```html
<div id="profileLoadingIndicator" class="loading-indicator" style="display: none;">
    <i class="fas fa-spinner fa-spin"></i> Uploading...
</div>
<div id="profileSuccessIndicator" class="success-indicator" style="display: none;">
    <i class="fas fa-check-circle"></i> Upload successful!
</div>
```

5. **Include JavaScript and CSS**:
```html
<link rel="stylesheet" href="css/profile-picture-ajax.css">
<script src="js/profile-picture-ajax.js"></script>
```

6. **Remove Old Upload Script**: Replace existing file upload JavaScript with new AJAX implementation

#### customer-account.php

**Changes Required**: Same as admin-account.php, but:
- Use customer session variables instead of admin
- Link to frontend API endpoints
- Use customer-specific folder path

## Data Models

### users Table (Existing - Needs Update)

**Current Schema** (partial):
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Required Migration**:
```sql
-- Add Cloudinary-specific columns
ALTER TABLE users 
ADD COLUMN cloud_url TEXT NULL AFTER profile_image,
ADD COLUMN cloud_public_id VARCHAR(255) NULL AFTER cloud_url,
ADD COLUMN cloud_provider VARCHAR(50) DEFAULT 'cloudinary' AFTER cloud_public_id;

-- Add indexes for performance
CREATE INDEX idx_cloud_public_id ON users(cloud_public_id);
```

**Updated Query for Display**:
```sql
-- Prioritize Cloudinary URLs
SELECT id, username, firstname, lastname, email,
       COALESCE(cloud_url, profile_image) as profile_image_url,
       cloud_public_id
FROM users 
WHERE id = ?;
```

### temp_uploaded_images Table (Existing - Reuse)

**Schema** (already exists):
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
   - Maximum before compression: 10MB
   - Maximum after compression: 2MB
   - Error: "File size exceeds limit. Please use a smaller image."

3. **Network Errors**:
   - Timeout: "Upload timed out. Please try again."
   - Connection: "Network error. Please check your connection."

### Server-Side Validation

1. **Authentication Errors**:
   - HTTP 401: "Unauthorized. Please log in."

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
   - Failed uploads: No database entry created, revert to previous image
   - Failed deletions: Image remains in Cloudinary and database

3. **User Feedback**:
   - Clear error messages displayed in UI
   - Error logging for debugging
   - Activity logging for audit trail

## Testing Strategy

### Unit Tests

1. **JavaScript Functions**:
   - File validation logic
   - Image compression
   - Avatar display updates
   - Initials generation

2. **PHP Functions**:
   - Image validation
   - Old image deletion
   - Temp image logging
   - Database updates

### Integration Tests

1. **Upload Flow**:
   - Select file → Compress → Upload → Update avatar
   - Verify Cloudinary upload
   - Verify database update
   - Verify old image deletion
   - Verify temp table cleanup

2. **Delete Flow**:
   - Click remove → Delete from Cloudinary → Revert to initials
   - Verify Cloudinary deletion
   - Verify database update
   - Verify temp table cleanup

3. **Replace Flow**:
   - Upload new image → Delete old image → Update avatar
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
   - User ID verification

## Implementation Notes

### Reusable Components

The following existing components will be reused:
- `backend/includes/cloudinary-helper.php` - Upload/delete functions
- `temp_uploaded_images` table - Orphan tracking
- CSRF token generation pattern
- Authentication checks (admin and customer)
- Activity logging functions
- Image compression JavaScript from carousel system

### Cloudinary Folder Structure

```
Home/
└── assets/
    └── public/
        ├── admin-profile-images/
        │   ├── profile_1_1234567890.jpg
        │   ├── profile_2_1234567891.jpg
        │   └── profile_3_1234567892.png
        └── profile-images/
            ├── profile_101_1234567890.jpg
            ├── profile_102_1234567891.jpg
            └── profile_103_1234567892.png
```

**Naming Convention**: `profile_[user_id]_[timestamp].[extension]`

### Performance Considerations

1. **Image Optimization**:
   - Client-side compression before upload (target 2MB)
   - Cloudinary automatic optimization enabled
   - Recommended dimensions: 500x500px
   - Format: Auto (Cloudinary chooses best format)

2. **Loading Strategy**:
   - Instant avatar preview after upload
   - Cache busting with timestamp parameter
   - Lazy loading for profile images in lists

3. **Caching**:
   - Cloudinary CDN caching
   - Browser caching for static assets
   - Database query caching for user data

### Security Considerations

1. **Authentication**:
   - Session-based authentication (admin and customer)
   - User ID verification (can only modify own profile)

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
   - User ID validation
   - SQL injection prevention (prepared statements)

5. **Activity Logging**:
   - Log all upload/delete operations
   - Include user ID and timestamp
   - Track IP address for audit trail

### Old Image Cleanup

When a user uploads a new profile picture:
1. Check if user has existing `cloud_public_id` in database
2. If exists, delete old image from Cloudinary before uploading new one
3. This prevents accumulation of unused profile pictures
4. Orphaned images (uploaded but never saved) are tracked in `temp_uploaded_images` for cleanup

## Migration Path

### Phase 1: Database Migration
1. Run migration to add Cloudinary columns to users table
2. Verify temp_uploaded_images table exists
3. Test migration on staging database

### Phase 2: Backend API (Admin)
1. Create backend/api/upload-profile-picture.php endpoint
2. Create backend/api/delete-profile-picture.php endpoint
3. Test endpoints with Postman/curl

### Phase 3: Backend API (Customer)
1. Create frontend/api/upload-profile-picture.php endpoint
2. Create frontend/api/delete-profile-picture.php endpoint
3. Test endpoints with Postman/curl

### Phase 4: Frontend JavaScript (Admin)
1. Create backend/pages/account/js/profile-picture-ajax.js
2. Create backend/pages/account/css/profile-picture-ajax.css
3. Test upload/delete functionality

### Phase 5: Frontend JavaScript (Customer)
1. Create frontend/pages/account/js/profile-picture-ajax.js
2. Create frontend/pages/account/css/profile-picture-ajax.css
3. Test upload/delete functionality

### Phase 6: Admin Page Integration
1. Update backend/pages/account/admin-account.php
2. Add CSRF token generation
3. Update avatar display logic
4. Add remove button
5. Include JavaScript and CSS files
6. Remove old upload script

### Phase 7: Customer Page Integration
1. Find or create frontend/pages/account/customer-account.php
2. Apply same changes as admin page
3. Test customer profile picture upload

### Phase 8: Display Integration
1. Update all pages that display profile pictures:
   - Navigation bars (admin and customer)
   - Comment sections
   - User lists
   - Order history
2. Use COALESCE(cloud_url, profile_image) pattern

### Phase 9: Testing & Validation
1. Test complete upload flow (admin and customer)
2. Test delete functionality
3. Test replace functionality
4. Verify orphan cleanup
5. Security testing
6. Cross-browser testing

## Future Enhancements

1. **Image Cropping**: Built-in image cropping tool before upload
2. **Drag & Drop**: Drag and drop file upload
3. **Progress Bar**: Detailed upload progress indicator
4. **Avatar Library**: Choose from predefined avatars
5. **Webcam Capture**: Take photo directly from webcam
6. **Orphan Cleanup Cron**: Automated cleanup of orphaned images
7. **Image Filters**: Apply filters or effects to profile pictures
8. **Multiple Sizes**: Generate thumbnail and full-size versions

## Display Integration Points

Profile pictures need to be displayed in multiple locations throughout the application. All display logic should use the COALESCE pattern to prioritize Cloudinary URLs:

### Admin Panel
1. **Navigation Bar**: `backend/pages/admin-includes/navbar/navbar.php`
2. **Profile Page**: `backend/pages/account/admin-profile.php`
3. **Account Settings**: `backend/pages/account/admin-account.php`
4. **Activity Logs**: Any admin activity displays

### Customer Frontend
1. **Navigation Bar**: `frontend/user-includes/navbar/customer-navigation.php`
2. **Profile Page**: `frontend/pages/account/customer-profile.php` (if exists)
3. **Account Settings**: `frontend/pages/account/customer-account.php` (if exists)
4. **Comment Sections**: Blog comments, product reviews
5. **Order History**: Customer name/avatar in order displays

### Query Pattern for All Displays
```php
$query = "SELECT id, username, firstname, lastname, 
          COALESCE(cloud_url, profile_image) as profile_image_url,
          cloud_public_id
          FROM users 
          WHERE id = ?";
```

### Display Pattern
```php
<?php if (!empty($profile_image_url)): ?>
    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile">
<?php else: ?>
    <span class="initials"><?php echo strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1)); ?></span>
<?php endif; ?>
```
