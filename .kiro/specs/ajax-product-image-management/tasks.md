# Implementation Plan: AJAX Product Image Management with Cloudinary

- [x] 1. Database preparation and schema updates




  - Make image_url column nullable: `ALTER TABLE product_images MODIFY COLUMN image_url VARCHAR(255) NULL;`
  - Create temp_uploaded_images table for orphan tracking
  - Add indexes for performance (uploaded_at, public_id)
  - Test database changes work correctly


  - _Requirements: 3.5, 6.1, 6.2_



- [ ] 2. Create backend AJAX upload endpoint

- [ ] 2.1 Create upload-product-image.php endpoint

  - Verify admin authentication and CSRF token
  - Validate uploaded image file (type, size, content)
  - Generate unique public ID with timestamp and random string
  - Upload image to Cloudinary using uploadToCloudinary() helper

  - Log upload to temp_uploaded_images table for orphan tracking
  - Return JSON response with success status, URL, and public_id
  - Handle errors and return appropriate error messages
  - _Requirements: 1.1, 1.3, 1.4, 9.1, 9.2, 9.3, 9.4_




- [x] 2.2 Add helper function for temp image logging



  - Create logTempImageUpload() function in upload endpoint
  - Insert public_id and cloud_url into temp_uploaded_images table
  - Handle database errors gracefully
  - _Requirements: 6.1, 6.4_

- [ ] 3. Create backend AJAX delete endpoint


- [ ] 3.1 Create delete-product-image.php endpoint

  - Verify admin authentication and CSRF token
  - Validate public_id parameter


  - Delete image from Cloudinary using deleteFromCloudinary() helper


  - Remove entry from temp_uploaded_images table
  - Return JSON response with success status
  - Handle errors and return appropriate error messages
  - _Requirements: 2.1, 2.2, 2.4, 9.1, 9.4_

- [ ] 3.2 Add helper function for temp image cleanup

  - Create removeTempImageLog() function in delete endpoint

  - Delete record from temp_uploaded_images table by public_id
  - Handle database errors gracefully
  - _Requirements: 6.1, 6.4_

- [ ] 4. Create frontend JavaScript for AJAX operations

- [ ] 4.1 Create product-image-ajax.js file


  - Implement uploadImageToCloudinary() async function
  - Implement deleteImageFromCloudinary() async function
  - Implement storeImageMetadata() function for hidden fields
  - Implement removeImageMetadata() function
  - Add error handling for network failures
  - Add retry logic for failed uploads
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 7.1, 7.2, 7.3_


- [ ] 4.2 Implement UI preview functions

  - Create addImagePreview() function to display uploaded images
  - Create removeImagePreview() function to remove image from UI
  - Add loading indicators (showLoadingIndicator, hideLoadingIndicator)
  - Add success indicators (showSuccessIndicator)
  - Add error notifications (showErrorMessage)



  - _Requirements: 1.2, 1.3, 2.2, 7.1, 7.2, 7.3, 7.4, 7.5_



- [ ] 4.3 Implement file input event handlers

  - Add event listener for primary image file input
  - Add event listener for additional images file input (multiple files)
  - Validate files client-side before upload
  - Handle multiple file uploads sequentially

  - Enforce maximum limit of 3 additional images
  - _Requirements: 1.1, 1.5, 8.1, 8.2, 8.3, 8.4_

- [ ] 4.4 Implement remove button handlers

  - Add handleRemoveImage() function for remove button clicks
  - Confirm deletion with user (optional)
  - Call deleteImageFromCloudinary() function

  - Update UI after successful deletion
  - Disable button during deletion to prevent duplicates
  - _Requirements: 2.1, 2.2, 2.3, 2.5, 7.4, 7.5_

- [ ] 5. Update add-product.php form

- [ ] 5.1 Add hidden fields for image metadata

  - Add hidden field for primary_image_url
  - Add hidden field for primary_image_public_id
  - Add hidden field for additional_image_urls (JSON array)
  - Add hidden field for additional_image_public_ids (JSON array)
  - Add CSRF token hidden field
  - _Requirements: 3.1, 3.2, 9.4_

- [ ] 5.2 Update HTML structure for AJAX uploads

  - Remove direct file upload from form submission
  - Add preview containers for primary and additional images
  - Add loading indicators for upload progress
  - Include product-image-ajax.js script
  - Update CSS for preview styling
  - _Requirements: 1.2, 1.3, 7.1, 10.2_

- [ ] 5.3 Update form submission handler

  - Remove old file upload handling code
  - Read image metadata from hidden fields
  - Insert primary image into product_images table with metadata
  - Parse additional_image_urls and additional_image_public_ids JSON
  - Insert each additional image into product_images table
  - Remove images from temp_uploaded_images after successful save
  - Handle errors and rollback if image save fails
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 6.1_

- [ ] 6. Update edit-product.php functionality

- [ ] 6.1 Load existing images on page load

  - Query product_images table for existing product images
  - Display existing images in preview containers
  - Add remove buttons to existing images
  - Store existing image metadata in hidden fields
  - Mark existing images to differentiate from new uploads
  - _Requirements: 4.1, 10.1, 10.2_

- [ ] 6.2 Implement image replacement logic

  - Allow uploading new primary image via AJAX
  - Delete old primary image from Cloudinary when new one is uploaded
  - Update database record with new image metadata
  - Display new image preview immediately
  - Handle errors and retain old image if upload fails
  - _Requirements: 4.2, 4.3, 4.4, 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 6.3 Implement additional image management in edit mode

  - Allow adding new additional images up to maximum limit
  - Allow removing existing additional images
  - Delete removed images from Cloudinary
  - Mark removed images in database (is_removed=1) instead of deleting records
  - Update UI to reflect changes immediately
  - _Requirements: 4.1, 4.3, 4.5, 10.3_

- [ ] 6.4 Update edit form submission handler

  - Process new images from hidden fields
  - Update existing image records if replaced
  - Insert new additional images
  - Mark removed images as is_removed=1
  - Remove images from temp_uploaded_images after save
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 10.4_

- [ ] 7. Implement orphan cleanup system

- [ ] 7.1 Create cleanup-orphaned-images.php cron job

  - Query temp_uploaded_images for records older than 24 hours
  - Delete each orphaned image from Cloudinary
  - Remove records from temp_uploaded_images table
  - Log all cleanup operations for audit
  - Handle errors gracefully (continue on individual failures)
  - _Requirements: 6.2, 6.3, 6.4, 6.5_

- [ ] 7.2 Setup cron job scheduling

  - Configure server cron to run cleanup daily
  - Test cron job execution
  - Verify orphaned images are deleted correctly
  - Monitor logs for any issues
  - _Requirements: 6.2, 6.3_

- [ ] 8. Add comprehensive error handling

- [ ] 8.1 Implement client-side error handling

  - Add error messages for upload failures
  - Add error messages for delete failures
  - Add error messages for validation failures
  - Display user-friendly error notifications
  - Log errors to console for debugging
  - _Requirements: 1.4, 2.4, 7.2, 7.3, 9.5_

- [ ] 8.2 Implement server-side error handling

  - Validate all inputs on server side
  - Return appropriate HTTP status codes
  - Return detailed error messages in JSON
  - Log all errors for debugging
  - Handle Cloudinary API errors gracefully
  - _Requirements: 1.4, 2.4, 9.1, 9.2, 9.5_

- [ ] 9. Add security measures

- [ ] 9.1 Implement CSRF protection

  - Generate CSRF token in session
  - Include token in all AJAX requests
  - Verify token on server side for all endpoints
  - Return 403 error for invalid tokens
  - _Requirements: 9.4_

- [ ] 9.2 Add rate limiting (optional)

  - Implement rate limiting on upload endpoint
  - Limit uploads per user per time period
  - Return 429 error when limit exceeded
  - Log rate limit violations
  - _Requirements: 9.5_

- [ ] 10. Testing and validation

- [ ] 10.1 Test add product flow

  - Upload primary image and verify Cloudinary storage
  - Upload multiple additional images
  - Remove images before saving
  - Submit form and verify database records
  - Verify images removed from temp_uploaded_images
  - Test with various image formats and sizes
  - _Requirements: 1.1, 1.2, 2.1, 3.1, 3.2, 3.3, 3.4_

- [ ] 10.2 Test edit product flow

  - Load existing product with images
  - Replace primary image
  - Add new additional images
  - Remove existing images
  - Submit form and verify updates
  - Verify old images deleted from Cloudinary
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 5.1, 5.2, 5.3_

- [ ] 10.3 Test error scenarios

  - Test with invalid file types
  - Test with oversized files
  - Test with network failures
  - Test with Cloudinary API errors
  - Verify error messages display correctly
  - Verify system recovers gracefully
  - _Requirements: 1.4, 2.4, 7.2, 7.3, 8.1, 8.2_

- [ ] 10.4 Test orphan cleanup

  - Upload images without saving product
  - Wait for cleanup period (or manually trigger)
  - Verify images deleted from Cloudinary
  - Verify records removed from temp_uploaded_images
  - Check cleanup logs
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 10.5 Test security measures

  - Test CSRF protection (invalid token should fail)
  - Test authentication (unauthenticated requests should fail)
  - Test file validation (malicious files should be rejected)
  - Verify all operations are logged
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 11. Documentation and cleanup

  - Document AJAX API endpoints (request/response format)
  - Document JavaScript functions and usage
  - Create admin guide for image management
  - Update deployment documentation
  - Remove old temp folder code if any
  - _Requirements: All_
