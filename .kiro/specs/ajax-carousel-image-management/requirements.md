# Requirements Document

## Introduction

This feature implements an AJAX-based real-time image upload system for carousel images in the Neo Cafe admin panel. The system will allow administrators to upload carousel images directly to Cloudinary with instant preview and feedback, eliminating the need for full page reloads and improving the user experience. The implementation will mirror the existing AJAX product image upload system.

## Glossary

- **Carousel System**: The hero carousel/slideshow displayed on the main user dashboard showing promotional images
- **Admin Panel**: The backend administrative interface at admin.neocafe.shop
- **Cloudinary**: Third-party cloud-based image management service (account: dvdccumbs)
- **AJAX Upload**: Asynchronous JavaScript upload that sends files to the server without page reload
- **Carousel Image**: A promotional banner image displayed in the hero carousel section
- **Orphan Image**: An uploaded image that exists in Cloudinary but is not associated with any carousel entry in the database
- **Display Order**: Numeric value determining the sequence in which carousel images appear

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to upload carousel images instantly via AJAX, so that I can see immediate feedback without waiting for page reloads

#### Acceptance Criteria

1. WHEN the administrator selects an image file in the carousel management form, THE Carousel System SHALL upload the image to Cloudinary immediately
2. WHILE the image is uploading, THE Carousel System SHALL display a loading indicator to the administrator
3. WHEN the upload completes successfully, THE Carousel System SHALL display a preview of the uploaded image with a remove button
4. IF the upload fails, THEN THE Carousel System SHALL display an error message indicating the failure reason
5. THE Carousel System SHALL validate image file type (JPEG, PNG, GIF, WebP) and size (maximum 5MB) before upload

### Requirement 2

**User Story:** As an administrator, I want uploaded carousel images to be stored in a consistent Cloudinary folder structure, so that images are organized and easy to manage

#### Acceptance Criteria

1. THE Carousel System SHALL upload carousel images to the Cloudinary folder path "Home/assets/images/carousel/"
2. THE Carousel System SHALL append a timestamp to each uploaded image filename to ensure uniqueness
3. THE Carousel System SHALL store the complete Cloudinary URL in the database for retrieval
4. THE Carousel System SHALL store the Cloudinary public_id for each image to enable deletion

### Requirement 3

**User Story:** As an administrator, I want to remove uploaded carousel images before saving the form, so that I can correct mistakes without cluttering Cloudinary storage

#### Acceptance Criteria

1. WHEN the administrator clicks the remove button on an image preview, THE Carousel System SHALL send a delete request to Cloudinary via AJAX
2. WHEN the delete request succeeds, THE Carousel System SHALL remove the image preview from the interface
3. WHEN the delete request succeeds, THE Carousel System SHALL remove the image metadata from the hidden form fields
4. IF the delete request fails, THEN THE Carousel System SHALL display an error message and keep the image preview visible

### Requirement 4

**User Story:** As an administrator, I want carousel image metadata to be automatically populated in the form, so that I don't need to manually enter image URLs

#### Acceptance Criteria

1. WHEN an image upload completes successfully, THE Carousel System SHALL populate a hidden form field with the Cloudinary URL
2. WHEN an image upload completes successfully, THE Carousel System SHALL populate a hidden form field with the Cloudinary public_id
3. WHEN the form is submitted, THE Carousel System SHALL read image metadata from the hidden fields
4. WHEN the form is submitted successfully, THE Carousel System SHALL save the image metadata to the carousel_images table

### Requirement 5

**User Story:** As an administrator, I want orphaned carousel images to be tracked, so that unused images can be cleaned up later

#### Acceptance Criteria

1. WHEN an image is uploaded via AJAX, THE Carousel System SHALL record the upload in the temp_uploaded_images table
2. WHEN the carousel form is saved successfully, THE Carousel System SHALL remove the corresponding entry from temp_uploaded_images
3. THE Carousel System SHALL store the Cloudinary public_id and cloud_url in temp_uploaded_images for orphan cleanup

### Requirement 6

**User Story:** As an administrator, I want the carousel edit functionality to support AJAX uploads, so that I can update carousel images with the same seamless experience

#### Acceptance Criteria

1. WHEN editing an existing carousel image, THE Carousel System SHALL display the current image as a preview
2. WHEN the administrator uploads a new image while editing, THE Carousel System SHALL upload the new image via AJAX
3. WHEN a new image is uploaded during editing, THE Carousel System SHALL mark the old image for deletion
4. WHEN the edit form is saved, THE Carousel System SHALL delete the old image from Cloudinary if a new image was uploaded

### Requirement 7

**User Story:** As an administrator, I want all carousel image uploads to be secure and authenticated, so that unauthorized users cannot upload images

#### Acceptance Criteria

1. THE Carousel System SHALL verify administrator authentication before processing any upload request
2. THE Carousel System SHALL validate CSRF tokens on all AJAX upload and delete requests
3. THE Carousel System SHALL return HTTP 401 Unauthorized if authentication fails
4. THE Carousel System SHALL return HTTP 403 Forbidden if CSRF validation fails
5. THE Carousel System SHALL log all carousel image upload and delete activities with administrator identification

### Requirement 8

**User Story:** As an administrator, I want clear visual feedback during image operations, so that I understand what is happening at all times

#### Acceptance Criteria

1. WHILE an image is uploading, THE Carousel System SHALL display a loading spinner on the upload area
2. WHEN an upload succeeds, THE Carousel System SHALL display a success message for 3 seconds
3. WHEN an upload fails, THE Carousel System SHALL display an error message with the failure reason
4. WHEN an image is deleted, THE Carousel System SHALL display a confirmation message
5. THE Carousel System SHALL disable the upload input while an upload is in progress
