# Requirements Document

## Introduction

This feature aims to synchronize functionality between the product edit modal in `product-list.php` and the add product form in `add-product.php`. Currently, the edit modal contains several features that are missing or inconsistently implemented in the add product page, creating a fragmented user experience for administrators managing products.

## Glossary

- **Add Product Page**: The standalone page (`add-product.php`) where administrators create new products
- **Edit Modal**: The modal dialog in `product-list.php` that allows administrators to edit existing products
- **Product Dashboard**: The frontend product listing page (`product-dashboard.php`) that displays products to customers
- **Category System**: The product categorization feature that organizes products into groups
- **Image Management Section**: The UI component that handles primary and additional product images
- **Same Day Order (SDO)**: Products available for same-day delivery or pickup
- **Pre-order Products**: Products with status Pick Up, Delivery, or Delivery or Pick Up

## Requirements

### Requirement 1: Category Selection Feature

**User Story:** As an administrator, I want to assign categories to products when creating them, so that products are properly organized from the start

#### Acceptance Criteria

1. WHEN THE Add Product Page loads, THE Add Product Page SHALL display a category dropdown field
2. THE category dropdown field SHALL contain all active categories from the database
3. THE category dropdown field SHALL include a "No Category" option as the default selection
4. WHEN an administrator selects a category, THE Add Product Page SHALL store the selected category_id with the product
5. THE category dropdown field SHALL be positioned between the Product Name field and the Price/Stock fields

### Requirement 2: Image Management Backend Enhancement

**User Story:** As an administrator, I want the image upload functionality to work consistently, so that images are properly validated and stored

#### Acceptance Criteria

1. THE Add Product Page SHALL maintain its current image upload UI without changes
2. WHEN an administrator uploads an image, THE System SHALL process it through the existing AJAX upload handler
3. THE image upload functionality SHALL integrate with Cloudinary content moderation (see Requirement 6)
4. THE existing image preview and remove functionality SHALL continue to work as currently implemented
5. THE System SHALL maintain backward compatibility with existing image upload code

### Requirement 3: Product Description Field (No Changes)

**User Story:** As an administrator, I want the product description field to continue working as it currently does

#### Acceptance Criteria

1. THE Add Product Page SHALL keep the existing product description field without modifications
2. THE product description textarea SHALL maintain its current styling and behavior
3. THE product description field SHALL remain in its current position in the form
4. THE System SHALL continue to save product descriptions to the database as currently implemented

### Requirement 4: Form Field Organization (Minimal Changes)

**User Story:** As an administrator, I want the category field added to the form without disrupting the existing layout

#### Acceptance Criteria

1. THE Add Product Page SHALL insert the category dropdown after the Product Name field
2. THE Add Product Page SHALL keep all other fields in their current positions
3. THE Add Product Page SHALL maintain the existing form layout and styling
4. THE category field SHALL use the same form-group wrapper as other fields

### Requirement 5: Validation and Error Handling

**User Story:** As an administrator, I want clear validation messages, so that I know what information is required before submitting

#### Acceptance Criteria

1. WHEN an administrator attempts to submit without a product name, THE Add Product Page SHALL display an error message "Product name is required"
2. WHEN an administrator attempts to submit without a price, THE Add Product Page SHALL display an error message "Price is required"
3. WHEN an administrator selects Same Day Order without dates, THE Add Product Page SHALL display an error message "Please select at least one date for Same Day Order"
4. WHEN an administrator selects Same Day Order without shipping method, THE Add Product Page SHALL display an error message "Please select a Same Day Order Options"
5. THE Add Product Page SHALL prevent form submission until all required fields are completed

### Requirement 6: Cloudinary Content Moderation

**User Story:** As an administrator, I want uploaded product images to be automatically screened for inappropriate content, so that only safe images are displayed to customers

#### Acceptance Criteria

1. WHEN an image is uploaded to Cloudinary, THE System SHALL request automatic content moderation analysis
2. THE System SHALL use Cloudinary's moderation add-on to detect nudity, violence, drugs, and offensive content
3. WHEN an image is flagged as inappropriate with confidence above 80%, THE System SHALL reject the upload
4. WHEN an image upload is rejected, THE System SHALL display an error message "Image rejected: Inappropriate content detected"
5. THE System SHALL log all moderation results to the database with timestamp, image public_id, and moderation status
6. WHEN an image is rejected, THE System SHALL delete the image from Cloudinary automatically
7. THE System SHALL send an email notification to administrators when an image is auto-rejected
8. THE moderation feature SHALL apply to both primary and additional product images
9. THE System SHALL store moderation metadata including confidence scores and detected categories
10. WHEN moderation analysis is pending, THE System SHALL display a loading indicator "Analyzing image for safety..."
