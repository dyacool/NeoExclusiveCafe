# Requirements Document: AJAX Product Image Management with Cloudinary

## Introduction

This feature implements a modern, real-time image management system for products using AJAX and Cloudinary. Images are uploaded immediately to Cloudinary when selected, allowing admins to add, replace, and remove images with instant visual feedback. The system eliminates temporary folder storage and provides a seamless editing experience.

## Glossary

- **AJAX Upload**: Asynchronous JavaScript upload that sends images to Cloudinary without page refresh
- **Cloudinary**: Cloud-based image storage and CDN service
- **Product Image Management System**: Interface for adding, editing, replacing, and removing product images
- **Real-time Preview**: Immediate visual feedback showing uploaded images before form submission
- **Image Placeholder**: Temporary database record for uploaded images that haven't been associated with a saved product
- **Orphan Cleanup**: Process of removing Cloudinary images that were uploaded but never saved to a product

## Requirements

### Requirement 1

**User Story:** As an admin, I want to upload product images immediately when I select them, so that I can see instant feedback and manage images before saving the product

#### Acceptance Criteria

1. WHEN an admin selects an image file, THE System SHALL upload it to Cloudinary via AJAX without page refresh
2. WHEN the upload completes, THE System SHALL display a preview of the uploaded image with a remove button
3. WHEN the upload is in progress, THE System SHALL display a loading indicator
4. IF the upload fails, THEN THE System SHALL display an error message and allow retry
5. THE System SHALL validate image files (type, size) before uploading to Cloudinary

### Requirement 2

**User Story:** As an admin, I want to remove uploaded images before saving the product, so that I can correct mistakes without cluttering Cloudinary storage

#### Acceptance Criteria

1. WHEN an admin clicks the remove button on an image preview, THE System SHALL delete the image from Cloudinary via AJAX
2. WHEN the deletion completes, THE System SHALL remove the image preview from the interface
3. THE System SHALL update hidden form fields to exclude the removed image
4. IF deletion fails, THEN THE System SHALL display an error message and keep the preview visible
5. THE System SHALL allow removing and re-uploading images multiple times before form submission

### Requirement 3

**User Story:** As an admin, I want the system to save image references when I submit the product form, so that uploaded images are properly associated with the product

#### Acceptance Criteria

1. WHEN an admin submits the product form, THE System SHALL associate all uploaded images with the product ID
2. THE System SHALL store image data in the product_images table with cloud_url, cloud_public_id, and cloud_provider
3. THE System SHALL mark the first uploaded image as primary (is_primary=1)
4. THE System SHALL mark additional images as non-primary (is_primary=0)
5. THE System SHALL allow image_url to be NULL since images are stored in Cloudinary

### Requirement 4

**User Story:** As an admin, I want to edit existing product images by adding, replacing, or removing them, so that I can keep product information up to date

#### Acceptance Criteria

1. WHEN an admin opens the edit modal, THE System SHALL display all existing product images with remove buttons
2. WHEN an admin uploads a new image in edit mode, THE System SHALL upload it to Cloudinary via AJAX immediately
3. WHEN an admin removes an existing image, THE System SHALL delete it from Cloudinary and mark it as removed in the database
4. WHEN an admin replaces the primary image, THE System SHALL delete the old primary image from Cloudinary
5. THE System SHALL allow adding additional images up to the maximum limit (3 additional images)

### Requirement 5

**User Story:** As an admin, I want to replace the primary image, so that I can update the main product photo

#### Acceptance Criteria

1. WHEN an admin uploads a new primary image in edit mode, THE System SHALL upload it to Cloudinary via AJAX
2. WHEN the new primary image is uploaded, THE System SHALL delete the old primary image from Cloudinary
3. THE System SHALL update the database to reference the new primary image
4. THE System SHALL display the new primary image preview immediately
5. IF the upload fails, THEN THE System SHALL retain the existing primary image

### Requirement 6

**User Story:** As a system, I want to clean up orphaned images, so that Cloudinary storage is not wasted on unused images

#### Acceptance Criteria

1. WHEN an admin uploads images but cancels without saving, THE System SHALL track these as orphaned images
2. THE System SHALL provide a cleanup mechanism to delete orphaned images from Cloudinary
3. THE System SHALL run cleanup automatically after a configurable time period (e.g., 24 hours)
4. THE System SHALL log all orphaned image deletions for audit purposes
5. THE System SHALL not delete images that are associated with saved products

### Requirement 7

**User Story:** As an admin, I want clear visual feedback during image operations, so that I understand what is happening

#### Acceptance Criteria

1. WHEN an image is uploading, THE System SHALL display a progress indicator or loading spinner
2. WHEN an upload succeeds, THE System SHALL display a success indicator briefly
3. WHEN an upload fails, THE System SHALL display a clear error message with the reason
4. WHEN an image is being deleted, THE System SHALL display a loading indicator
5. THE System SHALL disable action buttons during operations to prevent duplicate requests

### Requirement 8

**User Story:** As an admin, I want to upload multiple additional images at once, so that I can efficiently add product photos

#### Acceptance Criteria

1. WHEN an admin selects multiple image files, THE System SHALL upload them sequentially to Cloudinary via AJAX
2. THE System SHALL display progress for each individual image upload
3. THE System SHALL enforce the maximum limit of 3 additional images
4. IF one upload fails, THE System SHALL continue uploading remaining images
5. THE System SHALL display all successfully uploaded images with individual remove buttons

### Requirement 9

**User Story:** As a developer, I want the AJAX endpoints to be secure and validated, so that the system is protected from malicious uploads

#### Acceptance Criteria

1. THE System SHALL verify admin authentication before processing any image upload requests
2. THE System SHALL validate file type, size, and content on the server side
3. THE System SHALL sanitize filenames and generate secure Cloudinary public IDs
4. THE System SHALL use CSRF tokens to prevent cross-site request forgery
5. THE System SHALL log all image operations for security auditing

### Requirement 10

**User Story:** As an admin, I want the image management to work consistently in both add and edit modes, so that I have a familiar experience

#### Acceptance Criteria

1. THE System SHALL use the same AJAX upload mechanism for both add and edit product pages
2. THE System SHALL use the same UI components for image previews in both modes
3. THE System SHALL use the same validation rules for both modes
4. THE System SHALL provide the same error handling and feedback in both modes
5. THE System SHALL maintain the same maximum image limits in both modes
