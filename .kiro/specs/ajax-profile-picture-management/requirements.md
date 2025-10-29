# Requirements Document

## Introduction

This feature implements real-time AJAX-based profile picture uploads to Cloudinary for both admin users and regular customers. The system will reuse the proven pattern from carousel and product image management, including automatic image compression, instant previews, and orphan cleanup tracking.

## Glossary

- **Profile Picture System**: The web application component that manages user and admin profile image uploads
- **Admin User**: An authenticated administrator with access to the admin panel at admin.neocafe.shop
- **Customer User**: A registered customer with access to the frontend at neocafe.shop
- **Cloudinary**: The cloud-based image storage and delivery service
- **AJAX Upload**: Asynchronous file upload without page refresh
- **Image Compression**: Client-side reduction of image file size before upload
- **Orphan Image**: An uploaded image that was never associated with a user profile
- **CSRF Token**: Cross-Site Request Forgery protection token

## Requirements

### Requirement 1

**User Story:** As an admin user, I want to upload my profile picture instantly via AJAX, so that I can see my new profile image immediately without page refresh

#### Acceptance Criteria

1. WHEN the admin user selects a profile picture file, THE Profile Picture System SHALL upload the image to Cloudinary asynchronously
2. WHILE the image is uploading, THE Profile Picture System SHALL display a loading indicator to the admin user
3. WHEN the upload completes successfully, THE Profile Picture System SHALL display a preview of the uploaded image
4. WHEN the upload completes successfully, THE Profile Picture System SHALL store the Cloudinary URL in the users table profile_image column
5. IF the image file size exceeds 2MB, THEN THE Profile Picture System SHALL compress the image client-side before upload

### Requirement 2

**User Story:** As a customer user, I want to upload my profile picture instantly via AJAX, so that I can personalize my account without waiting for page reloads

#### Acceptance Criteria

1. WHEN the customer user selects a profile picture file, THE Profile Picture System SHALL upload the image to Cloudinary asynchronously
2. WHILE the image is uploading, THE Profile Picture System SHALL display a loading indicator to the customer user
3. WHEN the upload completes successfully, THE Profile Picture System SHALL display a preview of the uploaded image
4. WHEN the upload completes successfully, THE Profile Picture System SHALL update the users table profile_image column with the Cloudinary URL
5. IF the image file size exceeds 2MB, THEN THE Profile Picture System SHALL compress the image client-side before upload

### Requirement 3

**User Story:** As an admin user, I want to remove my current profile picture, so that I can replace it with a new one or revert to the default avatar

#### Acceptance Criteria

1. WHEN the admin user clicks the remove button on their profile picture, THE Profile Picture System SHALL delete the image from Cloudinary
2. WHEN the Cloudinary deletion succeeds, THE Profile Picture System SHALL remove the cloud_url from the users table
3. WHEN the profile picture is removed, THE Profile Picture System SHALL display the default avatar image
4. IF the Cloudinary deletion fails, THEN THE Profile Picture System SHALL log the error and still remove the database reference

### Requirement 4

**User Story:** As a customer user, I want to remove my current profile picture, so that I can replace it with a new one or use the default avatar

#### Acceptance Criteria

1. WHEN the customer user clicks the remove button on their profile picture, THE Profile Picture System SHALL delete the image from Cloudinary
2. WHEN the Cloudinary deletion succeeds, THE Profile Picture System SHALL remove the cloud_url from the users table
3. WHEN the profile picture is removed, THE Profile Picture System SHALL display the default avatar image
4. IF the Cloudinary deletion fails, THEN THE Profile Picture System SHALL log the error and still remove the database reference

### Requirement 5

**User Story:** As a system administrator, I want profile picture uploads to be validated and secured, so that only authorized users can upload appropriate images

#### Acceptance Criteria

1. THE Profile Picture System SHALL validate that the user is authenticated before accepting uploads
2. THE Profile Picture System SHALL verify CSRF tokens on all upload and delete requests
3. THE Profile Picture System SHALL accept only image file types (JPEG, PNG, GIF, WebP)
4. THE Profile Picture System SHALL reject images larger than 10MB before compression
5. IF validation fails, THEN THE Profile Picture System SHALL return a descriptive error message to the user

### Requirement 6

**User Story:** As a system administrator, I want uploaded profile pictures to be organized in Cloudinary folders, so that images are easy to manage and identify

#### Acceptance Criteria

1. WHEN an admin user uploads a profile picture, THE Profile Picture System SHALL store it in the Cloudinary folder "Home/assets/public/admin-profile-images/"
2. WHEN a customer user uploads a profile picture, THE Profile Picture System SHALL store it in the Cloudinary folder "Home/assets/public/profile-images/"
3. THE Profile Picture System SHALL name uploaded files using the format "profile_[user_id]_[timestamp].[ext]"
4. WHEN a new profile picture is uploaded, THE Profile Picture System SHALL delete the previous profile picture from Cloudinary
5. THE Profile Picture System SHALL log all uploads to the temp_uploaded_images table for orphan cleanup

### Requirement 7

**User Story:** As a system administrator, I want the database schema to support Cloudinary profile pictures, so that both legacy and new images can be displayed correctly

#### Acceptance Criteria

1. THE Profile Picture System SHALL store Cloudinary URLs in the users table profile_image column
2. THE Profile Picture System SHALL add a cloud_public_id column to the users table for Cloudinary reference
3. THE Profile Picture System SHALL add a cloud_provider column to the users table with default value "cloudinary"
4. THE Profile Picture System SHALL create an index on the cloud_public_id column for query performance
5. THE Profile Picture System SHALL make the profile_image column nullable to support Cloudinary-only storage

### Requirement 8

**User Story:** As an admin or customer user, I want my profile picture to display correctly throughout the application, so that my identity is consistently represented

#### Acceptance Criteria

1. WHEN a user has a Cloudinary profile picture, THE Profile Picture System SHALL display the cloud_url in navigation bars
2. WHEN a user has a Cloudinary profile picture, THE Profile Picture System SHALL display the cloud_url in profile pages
3. WHEN a user has a Cloudinary profile picture, THE Profile Picture System SHALL display the cloud_url in comment sections
4. WHEN a user has no profile picture, THE Profile Picture System SHALL display a default avatar image
5. THE Profile Picture System SHALL use COALESCE(cloud_url, profile_image) to prioritize Cloudinary URLs over legacy paths

### Requirement 9

**User Story:** As a user on a mobile device, I want profile picture uploads to work smoothly, so that I can update my profile from any device

#### Acceptance Criteria

1. THE Profile Picture System SHALL provide a responsive upload interface that works on mobile devices
2. THE Profile Picture System SHALL compress images on mobile devices before upload
3. THE Profile Picture System SHALL display loading indicators that are visible on small screens
4. THE Profile Picture System SHALL show error messages that are readable on mobile devices
5. WHEN upload completes on mobile, THE Profile Picture System SHALL update the profile picture preview immediately

### Requirement 10

**User Story:** As a system administrator, I want orphaned profile pictures to be tracked and cleanable, so that unused images don't accumulate storage costs

#### Acceptance Criteria

1. WHEN a profile picture is uploaded, THE Profile Picture System SHALL log it to the temp_uploaded_images table
2. WHEN a profile picture is successfully saved to a user profile, THE Profile Picture System SHALL remove it from temp_uploaded_images
3. WHEN a user cancels or navigates away before saving, THE Profile Picture System SHALL leave the image in temp_uploaded_images for cleanup
4. THE Profile Picture System SHALL reuse the existing temp_uploaded_images table structure
5. WHERE an orphan cleanup process exists, THE Profile Picture System SHALL allow identification of profile pictures older than 24 hours in temp_uploaded_images
