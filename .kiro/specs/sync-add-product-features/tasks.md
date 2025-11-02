# Implementation Plan

- [x] 1. Set up database schema for content moderation



  - Create `image_moderation_log` table with fields: id, public_id, status, kind, response_data, created_at
  - Add indexes on public_id, status, and created_at columns
  - Alter `temp_uploaded_images` table to add moderation_status and moderation_checked_at columns



  - _Requirements: 6.5, 6.9_

- [ ] 2. Create Cloudinary moderation configuration
  - Create `config/cloudinary-moderation-config.php` with moderation settings
  - Configure AWS Rekognition as moderation provider


  - Set auto-reject threshold to 0.8 (80% confidence)
  - Define content categories to detect (nudity, violence, drugs, etc.)
  - Configure admin notification settings
  - _Requirements: 6.1, 6.2, 6.3_

- [ ] 3. Implement moderation webhook handler
  - Create `backend/api/moderation-webhook.php` to receive Cloudinary callbacks



  - Parse webhook payload and extract moderation data
  - Insert moderation results into `image_moderation_log` table
  - Handle rejected images by deleting from Cloudinary
  - Update `temp_uploaded_images` table with moderation status



  - Implement webhook signature verification for security
  - _Requirements: 6.1, 6.2, 6.6, 6.7_

- [ ] 4. Create moderation status check API
  - Create `backend/api/check-moderation-status.php` endpoint



  - Query `image_moderation_log` table for moderation results
  - Return status (approved, rejected, pending) as JSON
  - Handle cases where moderation is not yet complete
  - _Requirements: 6.10_



- [ ] 5. Enhance image upload handler with moderation
  - Modify `backend/api/upload-product-image.php` to include moderation parameter
  - Add 'moderation' => 'aws_rek' to Cloudinary upload options
  - Configure notification_url for webhook callbacks
  - Return moderation status in upload response
  - Handle synchronous moderation results if available
  - _Requirements: 6.1, 6.2_

- [ ] 6. Implement frontend moderation handling
  - Modify `backend/pages/products/js/product-image-ajax.js` to handle moderation responses
  - Add handleUploadResponse() function to check moderation status
  - Implement pollModerationStatus() function for async status checking
  - Display appropriate messages for pending, approved, and rejected statuses
  - Show loading indicator during moderation check



  - _Requirements: 6.4, 6.10_

- [ ] 7. Create admin notification system for rejected images
  - Create email template for moderation rejection notifications
  - Implement sendModerationAlert() function in mailer.php
  - Include image public_id, rejection reason, and timestamp in email
  - Send notification to configured admin email address
  - Log notification attempts for audit trail



  - _Requirements: 6.7_

- [ ] 8. Add category selection to add-product form
  - Add category dropdown HTML after Product Name field in add-product.php
  - Query categories table for active categories ordered by display_order
  - Include "No Category" as default option
  - Add category_id to product INSERT statement
  - Bind category_id parameter in prepared statement
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 9. Implement form validation for category and moderation
  - Add client-side validation to check if category is selected (if required)
  - Validate that images have passed moderation before form submission
  - Display error messages for validation failures
  - Prevent form submission if validation fails
  - Update validateForm() function in add-product.php
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 10. Create database migration scripts
  - Write SQL migration script for creating image_moderation_log table
  - Write SQL migration script for altering temp_uploaded_images table
  - Create rollback scripts for both migrations
  - Test migrations in development environment
  - Document migration steps in deployment notes
  - _Requirements: 6.5, 6.9_

- [ ]* 11. Set up monitoring and logging
  - Add error logging for moderation webhook failures
  - Log all moderation events (approved, rejected, pending)
  - Set up alerts for high rejection rates
  - Monitor Cloudinary API usage for moderation
  - Create dashboard query for moderation statistics
  - _Requirements: 6.5, 6.9_

- [ ]* 12. Write unit tests for moderation functionality
  - Test webhook payload parsing
  - Test moderation status updates in database
  - Test image deletion on rejection
  - Test admin notification sending
  - Test frontend polling logic
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.6, 6.7_

- [ ]* 13. Perform integration testing
  - Test end-to-end product creation with category
  - Test image upload with moderation approval
  - Test image upload with moderation rejection
  - Test webhook callback processing
  - Test admin notification delivery
  - Verify database logging is working correctly
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

- [ ]* 14. Create documentation
  - Document Cloudinary moderation configuration steps
  - Document webhook setup in Cloudinary dashboard
  - Document database schema changes
  - Document API endpoints for moderation status checking
  - Create troubleshooting guide for common issues
  - _Requirements: 6.1, 6.2, 6.5_
