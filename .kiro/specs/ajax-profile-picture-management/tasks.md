# Implementation Plan

- [x] 1. Set up database schema for Cloudinary profile pictures



  - Run migration to add cloud_url, cloud_public_id, and cloud_provider columns to users table
  - Add indexes on cloud_public_id for query performance
  - Verify temp_uploaded_images table exists for orphan tracking



  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 2. Create backend API endpoint for admin profile picture upload
  - Create backend/api/upload-profile-picture.php with admin authentication
  - Implement CSRF token validation
  - Add file validation (type, size, dimensions)
  - Implement old profile picture deletion before new upload
  - Upload to Cloudinary folder: Home/assets/public/admin-profile-images/
  - Use filename format: profile_[user_id]_[timestamp].[ext]
  - Log upload to temp_uploaded_images table


  - Update users table with cloud_url and cloud_public_id
  - Remove from temp_uploaded_images after successful save
  - Return JSON response with image metadata
  - _Requirements: 1.1, 1.3, 1.4, 5.1, 5.2, 5.3, 5.4, 5.5, 6.1, 6.3, 6.4, 6.5_

- [ ] 3. Create backend API endpoint for admin profile picture deletion
  - Create backend/api/delete-profile-picture.php with admin authentication
  - Implement CSRF token validation


  - Verify user owns the profile picture
  - Delete image from Cloudinary using existing helper
  - Update users table to clear cloud_url and cloud_public_id
  - Remove from temp_uploaded_images table
  - Return JSON response
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 5.1, 5.2_

- [ ] 4. Create frontend API endpoint for customer profile picture upload
  - Create frontend/api/upload-profile-picture.php with customer authentication
  - Implement CSRF token validation
  - Add file validation (type, size, dimensions)



  - Implement old profile picture deletion before new upload
  - Upload to Cloudinary folder: Home/assets/public/profile-images/
  - Use filename format: profile_[user_id]_[timestamp].[ext]
  - Log upload to temp_uploaded_images table
  - Update users table with cloud_url and cloud_public_id
  - Remove from temp_uploaded_images after successful save
  - Return JSON response with image metadata


  - _Requirements: 2.1, 2.3, 2.4, 5.1, 5.2, 5.3, 5.4, 5.5, 6.2, 6.3, 6.4, 6.5_

- [ ] 5. Create frontend API endpoint for customer profile picture deletion
  - Create frontend/api/delete-profile-picture.php with customer authentication
  - Implement CSRF token validation
  - Verify user owns the profile picture
  - Delete image from Cloudinary using existing helper
  - Update users table to clear cloud_url and cloud_public_id
  - Remove from temp_uploaded_images table
  - Return JSON response
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 5.1, 5.2_




- [ ] 6. Create JavaScript module for admin profile picture AJAX
  - Create backend/pages/account/js/profile-picture-ajax.js
  - Implement uploadProfilePictureToCloudinary() function with compression
  - Implement deleteProfilePictureFromCloudinary() function
  - Add file validation (type, size)



  - Implement automatic image compression for files >2MB
  - Add updateAvatarDisplay() to show uploaded image
  - Add revertToInitials() to show initials when no image
  - Implement loading indicators and success/error messages
  - Add event handlers for avatar click and file input change
  - Add event handler for remove button click
  - _Requirements: 1.1, 1.2, 1.3, 1.5, 3.1, 3.3, 9.1, 9.2, 9.3, 9.4, 9.5_



- [ ] 7. Create CSS styling for admin profile picture interface
  - Create backend/pages/account/css/profile-picture-ajax.css
  - Style avatar container with hover overlay
  - Style loading indicators and spinners
  - Style success/error notifications
  - Style remove button
  - Add responsive layout for mobile devices
  - _Requirements: 9.1, 9.3, 9.4_

- [ ] 8. Update admin account page to use AJAX profile picture upload
  - Update backend/pages/account/admin-account.php


  - Add CSRF token generation and hidden fields
  - Update avatar display logic to prioritize Cloudinary URLs using COALESCE pattern
  - Add remove button for existing profile pictures
  - Add loading and success indicators
  - Include profile-picture-ajax.js and profile-picture-ajax.css
  - Remove old upload script and replace with AJAX implementation
  - Update query to fetch cloud_url and cloud_public_id from users table



  - _Requirements: 1.1, 1.2, 1.3, 1.4, 3.1, 3.2, 3.3, 8.4_

- [ ] 9. Create JavaScript module for customer profile picture AJAX
  - Create frontend/pages/account/js/profile-picture-ajax.js
  - Implement uploadProfilePictureToCloudinary() function with compression
  - Implement deleteProfilePictureFromCloudinary() function
  - Add file validation (type, size)
  - Implement automatic image compression for files >2MB
  - Add updateAvatarDisplay() to show uploaded image
  - Add revertToInitials() to show initials when no image
  - Implement loading indicators and success/error messages
  - Add event handlers for avatar click and file input change
  - Add event handler for remove button click
  - _Requirements: 2.1, 2.2, 2.3, 2.5, 4.1, 4.3, 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 10. Create CSS styling for customer profile picture interface
  - Create frontend/pages/account/css/profile-picture-ajax.css
  - Style avatar container with hover overlay
  - Style loading indicators and spinners
  - Style success/error notifications
  - Style remove button
  - Add responsive layout for mobile devices
  - _Requirements: 9.1, 9.3, 9.4_

- [ ] 11. Find or create customer account page and integrate AJAX profile picture upload
  - Find existing customer account page or create frontend/pages/account/customer-account.php
  - Add CSRF token generation and hidden fields
  - Add avatar display with upload overlay
  - Update avatar display logic to prioritize Cloudinary URLs using COALESCE pattern
  - Add remove button for existing profile pictures
  - Add loading and success indicators
  - Include profile-picture-ajax.js and profile-picture-ajax.css
  - Update query to fetch cloud_url and cloud_public_id from users table
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 4.1, 4.2, 4.3, 8.4_

- [x] 12. Update admin navigation bar to display Cloudinary profile pictures


  - Update backend/pages/admin-includes/navbar/navbar.php
  - Modify query to use COALESCE(cloud_url, profile_image) pattern
  - Update display logic to show Cloudinary URLs when available
  - Fallback to initials when no profile picture exists
  - _Requirements: 8.1, 8.4, 8.5_

- [x] 13. Update customer navigation bar to display Cloudinary profile pictures


  - Update frontend/user-includes/navbar/customer-navigation.php
  - Modify query to use COALESCE(cloud_url, profile_image) pattern
  - Update display logic to show Cloudinary URLs when available
  - Fallback to initials when no profile picture exists
  - _Requirements: 8.1, 8.4, 8.5_

- [x] 14. Update admin profile display page to show Cloudinary profile pictures

  - Find and update backend/pages/account/admin-profile.php
  - Modify query to use COALESCE(cloud_url, profile_image) pattern
  - Update display logic to show Cloudinary URLs when available
  - Fallback to initials when no profile picture exists
  - _Requirements: 8.2, 8.4, 8.5_

- [x] 15. Update all other profile picture display locations

  - Search for all locations displaying profile pictures (comments, reviews, orders)
  - Update queries to use COALESCE(cloud_url, profile_image) pattern
  - Update display logic to prioritize Cloudinary URLs
  - Ensure consistent fallback to initials across all displays
  - _Requirements: 8.3, 8.4, 8.5_

- [x] 16. Verify orphan cleanup tracking


  - Test that uploads are logged to temp_uploaded_images table
  - Test that successful saves remove entries from temp_uploaded_images
  - Test that cancelled uploads remain in temp_uploaded_images for cleanup
  - Verify old profile pictures are deleted when new ones are uploaded
  - _Requirements: 6.5, 10.1, 10.2, 10.3, 10.4, 10.5_
