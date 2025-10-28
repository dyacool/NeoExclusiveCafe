# Implementation Plan: Cloudinary Image Migration

- [x] 1. Install and configure Cloudinary SDK




  - Install Cloudinary PHP SDK via Composer
  - Create `.env` file with Cloudinary credentials (CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET)
  - Create `config/cloudinary-config.php` with Cloudinary configuration class



  - Test Cloudinary connection with a simple upload
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 2. Prepare database for Cloudinary URLs


  - Add `cloudinary_url` column to `products` table
  - Add `cloudinary_additional_images` column to `products` table
  - Create `image_migrations` tracking table
  - Add indexes for performance
  - _Requirements: 2.3, 2.5_



- [ ] 3. Create migration helper functions
  - Create `backend/includes/cloudinary-helper.php` with upload, get URL, and delete functions
  - Implement error handling and logging
  - Add file validation functions
  - Test helper functions with sample images

  - _Requirements: 1.4, 6.3_

- [ ] 4. Create product images migration script
  - Create `scripts/migrate-images-to-cloudinary.php` main script file
  - Implement `migrateProductImages()` function to scan and upload product images
  - Update products table with Cloudinary URLs after successful uploads

  - Add progress logging and error handling
  - Test with a few sample products first
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [ ] 5. Create general assets migration function
  - Implement `migrateGeneralImages()` function for `/assets/images/` directory
  - Upload carousel images and update database references


  - Create mapping file for static asset URLs
  - Handle special characters in filenames
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 6. Create payment proofs migration function


  - Implement `migratePaymentProofs()` function for bulk payments and refund proofs


  - Upload from `/assets/bulk_payments/` to Cloudinary folder
  - Upload from `/assets/refund-proofs/` to Cloudinary folder
  - Update relevant database tables with Cloudinary URLs
  - Use secure upload settings for financial documents
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_



- [ ] 7. Run full migration and generate report
  - Execute complete migration script for all image types
  - Generate detailed migration report with success/failure counts

  - Log any errors or failed uploads
  - Create backup of database before migration
  - _Requirements: 2.4, 7.2, 7.3, 7.4_

- [x] 8. Update application to use Cloudinary URLs

- [ ] 8.1 Update product display pages
  - Modify product dashboard to use Cloudinary URLs
  - Update product detail pages
  - Add fallback for missing images
  - Apply Cloudinary transformations for optimization
  - _Requirements: 5.1, 5.4, 5.5_

- [ ] 8.2 Update admin product management
  - Modify add-product.php to upload directly to Cloudinary
  - Update edit-product.php to use Cloudinary
  - Update product-list.php to display Cloudinary images
  - _Requirements: 6.1, 6.3, 6.4_

- [ ] 8.3 Update carousel and general images
  - Update carousel display to use Cloudinary URLs
  - Update static asset references
  - Apply transformations for responsive images
  - _Requirements: 5.2, 5.4_

- [ ] 8.4 Update payment proof uploads
  - Modify bulk payment upload to use Cloudinary
  - Update refund proof upload to use Cloudinary
  - Store Cloudinary URLs in database
  - Delete local temp files after upload
  - _Requirements: 6.2, 6.3, 6.4, 6.5_

- [ ] 9. Verify migration success
  - Run verification script to check all database URLs point to Cloudinary
  - Test that all Cloudinary URLs are accessible
  - Verify image display on all pages
  - Check that new uploads work correctly
  - Generate final verification report
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 10. Cleanup and documentation
  - Update `.gitignore` to exclude local image directories
  - Create backup of local images before deletion
  - Document Cloudinary setup for team
  - Update deployment documentation
  - Remove local image files after verification (optional)
  - _Requirements: 7.5_
