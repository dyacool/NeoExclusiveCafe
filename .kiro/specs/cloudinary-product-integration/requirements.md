# Requirements Document: Cloudinary Product Image Integration

## Introduction

This feature integrates Cloudinary cloud storage throughout the entire product management system, replacing all local file storage with secure Cloudinary URLs. This ensures images are served securely via HTTPS, automatically optimized, and delivered through a global CDN, eliminating local file system vulnerabilities.

## Glossary

- **Cloudinary**: Cloud-based image and video management service with CDN delivery
- **CloudinaryImageFetcher**: PHP class that securely fetches images from Cloudinary
- **Product Management System**: Admin interface for adding, editing, and displaying products
- **Cloudinary URL**: Secure HTTPS URL pointing to an image stored in Cloudinary (e.g., `https://res.cloudinary.com/dvdccumbs/image/upload/v1761594980/primary_1757776354_mpd5kt.jpg`)
- **Public ID**: Cloudinary's unique identifier for an image
- **Image Upload**: Process of uploading an image file directly to Cloudinary
- **Image Display**: Process of fetching and showing images from Cloudinary

## Requirements

### Requirement 1

**User Story:** As an admin, I want to add new products with images that upload directly to Cloudinary, so that images are stored securely in the cloud

#### Acceptance Criteria

1. WHEN an admin uploads a primary product image, THE System SHALL upload the image directly to Cloudinary without storing it locally
2. WHEN the Cloudinary upload succeeds, THE System SHALL store the Cloudinary URL in the `cloudinary_url` database column
3. WHEN an admin uploads additional product images, THE System SHALL upload each image to Cloudinary and store URLs in the `cloudinary_additional_images` column
4. WHEN the upload completes, THE System SHALL delete any temporary local files
5. IF the Cloudinary upload fails, THEN THE System SHALL display an error message and prevent product creation

### Requirement 2

**User Story:** As an admin, I want to view product images from Cloudinary in the product list, so that I can see secure, optimized images

#### Acceptance Criteria

1. WHEN displaying the product list, THE System SHALL use CloudinaryImageFetcher to retrieve image URLs
2. WHEN an image is fetched, THE System SHALL apply automatic quality and format optimization
3. WHEN a product has no Cloudinary URL, THE System SHALL display a placeholder image
4. THE System SHALL display images using secure HTTPS URLs only
5. WHEN images load, THE System SHALL use lazy loading for performance

### Requirement 3

**User Story:** As an admin, I want to view product images from Cloudinary on the product dashboard, so that customers see optimized, secure images

#### Acceptance Criteria

1. WHEN displaying products on the dashboard, THE System SHALL fetch images using CloudinaryImageFetcher
2. WHEN fetching images, THE System SHALL apply responsive transformations based on viewport size
3. WHEN multiple products are displayed, THE System SHALL use batch fetching for performance
4. THE System SHALL cache fetched image URLs to reduce API calls
5. WHEN an image fails to load, THE System SHALL display a fallback placeholder

### Requirement 4

**User Story:** As an admin, I want to edit product images by uploading new ones to Cloudinary, so that image updates are secure and efficient

#### Acceptance Criteria

1. WHEN an admin uploads a new primary image during edit, THE System SHALL upload to Cloudinary and update the `cloudinary_url` column
2. WHEN a new image is uploaded, THE System SHALL delete the old image from Cloudinary using its public ID
3. WHEN editing additional images, THE System SHALL allow adding, removing, and replacing images in Cloudinary
4. THE System SHALL prevent storing any images locally during the edit process
5. IF the upload fails, THEN THE System SHALL retain the existing Cloudinary URL and display an error

### Requirement 5

**User Story:** As an admin, I want the database to support Cloudinary URLs, so that image data is properly stored and retrieved

#### Acceptance Criteria

1. THE System SHALL have a `cloud_url` column in the product_images table for Cloudinary URLs
2. THE System SHALL have a `cloud_public_id` column in the product_images table for Cloudinary public IDs
3. THE System SHALL have a `cloud_provider` column in the product_images table defaulting to 'cloudinary'
4. WHEN inserting images, THE System SHALL allow `image_url` to be NULL or store the cloud_url value
5. WHEN querying products, THE System SHALL retrieve Cloudinary URLs from the cloud_url column

### Requirement 6

**User Story:** As a system, I want to use CloudinaryImageFetcher for all image retrieval, so that image fetching is centralized and secure

#### Acceptance Criteria

1. WHEN fetching a single product image, THE System SHALL use `CloudinaryImageFetcher::fetchProductImage()`
2. WHEN fetching multiple product images, THE System SHALL use `CloudinaryImageFetcher::fetchMultipleProductImages()` for batch optimization
3. WHEN an image is fetched, THE System SHALL apply default transformations (quality: auto, format: auto, width: 800)
4. THE System SHALL cache fetched images to reduce redundant API calls
5. IF a product has no Cloudinary URL, THEN THE System SHALL throw an exception with a clear error message

### Requirement 7

**User Story:** As a developer, I want to ensure no local file storage is used, so that the system is secure and compliant

#### Acceptance Criteria

1. THE System SHALL NOT store uploaded images in `/assets/product-images/` or any local directory
2. WHEN an image is uploaded, THE System SHALL upload directly to Cloudinary from the temporary upload location
3. WHEN displaying images, THE System SHALL NEVER use local file paths
4. THE System SHALL delete temporary files immediately after Cloudinary upload
5. THE System SHALL log any attempts to access local image files for security auditing

### Requirement 8

**User Story:** As an admin, I want image uploads to be validated before sending to Cloudinary, so that only valid images are uploaded

#### Acceptance Criteria

1. WHEN an admin uploads an image, THE System SHALL validate the file type (JPEG, PNG, GIF, WebP only)
2. WHEN validating, THE System SHALL check the file size does not exceed 10MB
3. WHEN validating, THE System SHALL verify the file is a valid image using `getimagesize()`
4. IF validation fails, THEN THE System SHALL display a specific error message
5. THE System SHALL sanitize filenames before generating Cloudinary public IDs

### Requirement 9

**User Story:** As a system, I want to handle Cloudinary errors gracefully, so that users receive helpful feedback

#### Acceptance Criteria

1. WHEN a Cloudinary upload fails, THE System SHALL log the error with details
2. WHEN an error occurs, THE System SHALL display a user-friendly error message
3. WHEN the Cloudinary API is unavailable, THE System SHALL inform the admin and prevent product creation
4. THE System SHALL provide retry functionality for failed uploads
5. WHEN displaying images, THE System SHALL show a placeholder if the Cloudinary URL is invalid

### Requirement 10

**User Story:** As a developer, I want to test the Cloudinary integration, so that I can verify everything works correctly

#### Acceptance Criteria

1. THE System SHALL provide a test page that displays products with Cloudinary images
2. WHEN testing, THE System SHALL show image URLs, source, and transformation details
3. THE System SHALL provide a diagnostic page that checks Cloudinary connection status
4. THE System SHALL display cache statistics and performance metrics
5. THE System SHALL allow testing different image transformations (thumbnail, medium, large)
