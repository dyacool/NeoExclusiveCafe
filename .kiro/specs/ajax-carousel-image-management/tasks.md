# Implementation Plan

- [x] 1. Database migration for carousel AJAX support



  - Create migration SQL file to add Cloudinary columns (cloud_url, cloud_public_id, cloud_provider) to carousel_images table
  - Make image_url column nullable to support Cloudinary-only storage
  - Add indexes for cloud_public_id and display_order columns
  - Create PHP migration runner script
  - Verify temp_uploaded_images table exists (reuse from product images)


  - _Requirements: 2.3, 2.4, 5.1, 5.3_

- [ ] 2. Backend AJAX upload endpoint
  - Create backend/api/upload-carousel-image.php endpoint
  - Implement admin authentication verification using session checks
  - Implement CSRF token validation
  - Implement file validation (type: JPEG/PNG/GIF/WebP, size: max 5MB)
  - Generate unique filename with timestamp: carousel_[timestamp].[ext]
  - Upload to Cloudinary folder: Home/assets/images/carousel/
  - Log upload to temp_uploaded_images table for orphan tracking



  - Return JSON response with image metadata (url, public_id, dimensions, format, bytes)
  - Implement error handling with appropriate HTTP status codes (401, 403, 400, 500)
  - _Requirements: 1.1, 1.4, 2.1, 2.2, 2.3, 2.4, 5.1, 5.3, 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 3. Backend AJAX delete endpoint
  - Create backend/api/delete-carousel-image.php endpoint
  - Implement admin authentication verification
  - Implement CSRF token validation


  - Sanitize and validate public_id parameter
  - Delete image from Cloudinary using existing cloudinary-helper.php
  - Remove entry from temp_uploaded_images table
  - Return JSON response with success/error status
  - Implement error handling for deletion failures
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 7.1, 7.2, 7.3, 7.4_

- [ ] 4. Frontend JavaScript AJAX handler
  - Create backend/pages/user-page-content/js/carousel-image-ajax.js
  - Implement uploadImageToCloudinary() function with FormData and fetch API
  - Implement deleteImageFromCloudinary() function
  - Implement client-side file validation (type, size max 5MB)
  - Implement storeImageMetadata() to populate hidden form fields with URL and public_id
  - Implement removeImageMetadata() to clear hidden form fields
  - Implement addImagePreview() to display uploaded image with remove button
  - Implement removeImagePreview() to remove image from UI



  - Implement showLoadingIndicator() and hideLoadingIndicator() for upload feedback
  - Implement showSuccessIndicator() for successful uploads (display for 3 seconds)
  - Implement showErrorMessage() for error display
  - Implement handleRemoveImage() for remove button clicks
  - Implement file input change event handler
  - Disable upload buttons during upload operations
  - Initialize on DOM ready
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.1, 3.2, 3.3, 4.1, 4.2, 8.1, 8.2, 8.3, 8.4, 8.5_



- [ ] 5. Frontend CSS styling
  - Create backend/pages/user-page-content/css/carousel-image-ajax.css
  - Style image preview container with responsive grid layout
  - Style image preview cards with hover effects
  - Style remove button with icon and hover state
  - Style loading indicator with spinner animation
  - Style success indicator with checkmark icon
  - Style error notification with error icon and auto-dismiss animation
  - Style upload buttons with disabled states
  - Ensure mobile responsiveness
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [ ] 6. Update manage-carousel-images.php for AJAX uploads
  - Add CSRF token generation at top of file



  - Add hidden input field for CSRF token
  - Add hidden input fields for carousel_image_url and carousel_image_public_id
  - Add image preview container div (carouselPreviewContainer)
  - Add loading indicator div (carouselLoadingIndicator) with spinner
  - Add success indicator div (carouselSuccessIndicator) with checkmark
  - Update file input to trigger AJAX upload on change
  - Include carousel-image-ajax.css stylesheet
  - Include carousel-image-ajax.js script
  - Update add_image form handler to read metadata from hidden fields instead of $_FILES
  - Insert image metadata into carousel_images table (image_url, cloud_url, cloud_public_id, cloud_provider)
  - Remove uploaded image from temp_uploaded_images table after successful save
  - Remove old file upload code that uses move_uploaded_file()
  - _Requirements: 1.1, 1.2, 1.3, 4.1, 4.2, 4.3, 4.4, 5.2, 7.1, 7.2_

- [ ] 7. Update carousel display to use Cloudinary URLs
  - Update frontend/pages/home/user-dashboard.php query to use COALESCE(cloud_url, image_url)
  - Verify carousel images display correctly from Cloudinary
  - Test with both Cloudinary URLs and legacy local paths
  - _Requirements: 2.3_

- [ ]* 8. Testing and validation
- [ ]* 8.1 Test complete upload flow (select file → upload → preview → save form)
  - Verify image uploads to Cloudinary in correct folder
  - Verify temp_uploaded_images entry is created
  - Verify preview displays correctly
  - Verify metadata is stored in hidden fields
  - Verify form submission saves to carousel_images table
  - Verify temp_uploaded_images entry is removed after save
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 4.1, 4.2, 4.4, 5.1, 5.2_

- [ ]* 8.2 Test delete functionality
  - Upload image and click remove button before saving
  - Verify image is deleted from Cloudinary
  - Verify temp_uploaded_images entry is removed
  - Verify preview is removed from UI
  - Verify metadata is cleared from hidden fields
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [ ]* 8.3 Test error scenarios
  - Test invalid file types (PDF, TXT, etc.)
  - Test oversized files (>5MB)
  - Test network failures (disconnect during upload)
  - Test session expiration
  - Test CSRF token validation
  - Verify appropriate error messages are displayed
  - _Requirements: 1.4, 1.5, 7.2, 7.3, 7.4, 8.3_

- [ ]* 8.4 Test security measures
  - Verify admin authentication is required
  - Verify CSRF tokens are validated
  - Verify file type validation prevents malicious uploads
  - Verify activity logging captures all operations
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ]* 8.5 Test carousel display
  - Verify carousel images display correctly on user dashboard
  - Test with Cloudinary URLs
  - Test with legacy local paths (backward compatibility)
  - Verify display order is respected
  - _Requirements: 2.3_
