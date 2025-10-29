# Product Image Editing API - Cloudinary Integration

This documentation describes the new API endpoints for editing product images with Cloudinary integration.

## Overview

Three new endpoints have been created to handle product image editing:

1. **get-product-images-edit.php** - Retrieve all images for a product
2. **replace-product-image.php** - Replace or add primary/additional images
3. **manage-additional-images.php** - Add or remove additional images

All endpoints require admin authentication and handle Cloudinary uploads/deletions automatically.

---

## 1. Get Product Images

**Endpoint:** `GET /backend/pages/products/get-product-images-edit.php`

**Purpose:** Retrieve all images (primary and additional) for a specific product.

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product_id | integer | Yes | The ID of the product |

### Example Request

```javascript
fetch('get-product-images-edit.php?product_id=123')
  .then(response => response.json())
  .then(data => console.log(data));
```

### Success Response

```json
{
  "success": true,
  "primary_image": {
    "id": 456,
    "url": "https://res.cloudinary.com/dvdccumbs/image/upload/v123/product_123_primary.jpg",
    "cloudinary_url": "https://res.cloudinary.com/dvdccumbs/image/upload/v123/product_123_primary.jpg",
    "public_id": "neocafe/products/product_123_primary_1234567890",
    "is_primary": true,
    "created_at": "2025-01-15 10:30:00",
    "updated_at": "2025-01-15 10:30:00"
  },
  "additional_images": [
    {
      "id": 457,
      "url": "https://res.cloudinary.com/dvdccumbs/image/upload/v123/product_123_additional_1.jpg",
      "cloudinary_url": "https://res.cloudinary.com/dvdccumbs/image/upload/v123/product_123_additional_1.jpg",
      "public_id": "neocafe/products/product_123_additional_1_1234567890",
      "is_primary": false,
      "created_at": "2025-01-15 10:31:00",
      "updated_at": "2025-01-15 10:31:00"
    }
  ],
  "total_additional": 1,
  "can_add_more": true
}
```

### Error Response

```json
{
  "success": false,
  "error": "Invalid product ID"
}
```

---

## 2. Replace Product Image

**Endpoint:** `POST /backend/pages/products/replace-product-image.php`

**Purpose:** Replace an existing image or add a new one (primary or additional).

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product_id | integer | Yes | The ID of the product |
| image_id | integer | No | The ID of the image to replace (omit to add new) |
| is_primary | boolean | Yes | Whether this is a primary image (1 or 0) |
| image | file | Yes | The image file to upload |

### Example Request (JavaScript)

```javascript
const formData = new FormData();
formData.append('product_id', 123);
formData.append('image_id', 456); // Optional - omit to add new
formData.append('is_primary', 1);
formData.append('image', fileInput.files[0]);

fetch('replace-product-image.php', {
  method: 'POST',
  body: formData
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### Success Response

```json
{
  "success": true,
  "image": {
    "id": 456,
    "url": "https://res.cloudinary.com/dvdccumbs/image/upload/v124/product_123_primary_1234567891.jpg",
    "public_id": "neocafe/products/product_123_primary_1234567891",
    "is_primary": true
  },
  "message": "Image replaced successfully"
}
```

### Error Responses

```json
{
  "success": false,
  "error": "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed"
}
```

```json
{
  "success": false,
  "error": "File size exceeds 10MB limit"
}
```

### Behavior

- If `image_id` is provided, the existing image is replaced
- The old image is automatically deleted from Cloudinary
- If `image_id` is omitted, a new image record is created
- Temporary files are automatically cleaned up
- Database transaction ensures data consistency

---

## 3. Manage Additional Images

**Endpoint:** `POST /backend/pages/products/manage-additional-images.php`

**Purpose:** Add new additional images or remove existing ones.

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| action | string | Yes | Either "add" or "remove" |
| product_id | integer | Yes | The ID of the product |
| image | file | Yes (for add) | The image file to upload |
| image_id | integer | Yes (for remove) | The ID of the image to remove |

### Example Request - Add Image

```javascript
const formData = new FormData();
formData.append('action', 'add');
formData.append('product_id', 123);
formData.append('image', fileInput.files[0]);

fetch('manage-additional-images.php', {
  method: 'POST',
  body: formData
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### Example Request - Remove Image

```javascript
const formData = new FormData();
formData.append('action', 'remove');
formData.append('product_id', 123);
formData.append('image_id', 457);

fetch('manage-additional-images.php', {
  method: 'POST',
  body: formData
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### Success Response (Add)

```json
{
  "success": true,
  "image": {
    "id": 458,
    "url": "https://res.cloudinary.com/dvdccumbs/image/upload/v125/product_123_additional_2.jpg",
    "public_id": "neocafe/products/product_123_additional_2_1234567892",
    "is_primary": false
  },
  "message": "Additional image added successfully"
}
```

### Success Response (Remove)

```json
{
  "success": true,
  "message": "Additional image removed successfully"
}
```

### Error Responses

```json
{
  "success": false,
  "error": "Maximum of 3 additional images allowed"
}
```

```json
{
  "success": false,
  "error": "Cannot remove primary image through this endpoint"
}
```

### Behavior

- **Add Action:**
  - Validates that product has fewer than 3 additional images
  - Uploads to Cloudinary with sequential numbering
  - Inserts record into database
  - Cleans up temporary files

- **Remove Action:**
  - Marks image as removed in database (soft delete)
  - Deletes image from Cloudinary
  - Prevents removal of primary images
  - Uses database transaction for consistency

---

## Validation Rules

All endpoints enforce the following validation rules:

### File Type Validation
- Allowed types: JPEG, PNG, GIF, WebP
- Validated using `getimagesize()` for security

### File Size Validation
- Maximum size: 10MB
- Enforced before upload to Cloudinary

### Image Limits
- Primary images: 1 per product
- Additional images: Maximum 3 per product

### Authentication
- All endpoints require admin session
- Returns 401 if not authenticated

---

## Error Handling

All endpoints follow consistent error handling:

1. **Validation Errors:** Return specific error messages
2. **Upload Failures:** Rollback database changes
3. **Database Errors:** Use transactions to maintain consistency
4. **Cloudinary Errors:** Log errors and provide user-friendly messages

### Common Error Codes

| Error Message | Cause | Solution |
|---------------|-------|----------|
| "Unauthorized access" | Not logged in as admin | Login as admin |
| "Invalid product ID" | Missing or invalid product_id | Provide valid product ID |
| "File is not a valid image" | Corrupted or non-image file | Upload valid image file |
| "File size exceeds 10MB limit" | File too large | Compress or resize image |
| "Maximum of 3 additional images allowed" | Too many images | Remove existing images first |

---

## Testing

A test page is available at:
```
/backend/pages/products/test-edit-images.html
```

This page provides a UI to test all endpoints with:
- Get product images
- Replace primary image
- Add additional images
- Remove additional images

---

## Integration Example

Here's a complete example of integrating these endpoints into a product edit form:

```javascript
class ProductImageEditor {
  constructor(productId) {
    this.productId = productId;
  }

  async loadImages() {
    const response = await fetch(`get-product-images-edit.php?product_id=${this.productId}`);
    const data = await response.json();
    
    if (data.success) {
      this.displayImages(data);
    }
  }

  async replacePrimary(file) {
    const formData = new FormData();
    formData.append('product_id', this.productId);
    formData.append('is_primary', 1);
    formData.append('image', file);

    const response = await fetch('replace-product-image.php', {
      method: 'POST',
      body: formData
    });
    
    return await response.json();
  }

  async addAdditional(file) {
    const formData = new FormData();
    formData.append('product_id', this.productId);
    formData.append('action', 'add');
    formData.append('image', file);

    const response = await fetch('manage-additional-images.php', {
      method: 'POST',
      body: formData
    });
    
    return await response.json();
  }

  async removeAdditional(imageId) {
    const formData = new FormData();
    formData.append('product_id', this.productId);
    formData.append('action', 'remove');
    formData.append('image_id', imageId);

    const response = await fetch('manage-additional-images.php', {
      method: 'POST',
      body: formData
    });
    
    return await response.json();
  }

  displayImages(data) {
    // Implement your UI display logic here
    console.log('Primary:', data.primary_image);
    console.log('Additional:', data.additional_images);
  }
}

// Usage
const editor = new ProductImageEditor(123);
editor.loadImages();
```

---

## Database Schema

The endpoints work with the `product_images` table:

```sql
CREATE TABLE product_images (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  image_url VARCHAR(500),  -- Legacy local path
  cloud_url VARCHAR(500),  -- Cloudinary URL
  cloud_public_id VARCHAR(255),  -- Cloudinary public ID
  cloud_provider VARCHAR(50) DEFAULT 'cloudinary',
  is_primary TINYINT(1) DEFAULT 0,
  is_removed TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
);
```

---

## Security Considerations

1. **Authentication:** All endpoints check admin session
2. **File Validation:** Multiple layers of validation
3. **SQL Injection:** All queries use prepared statements
4. **XSS Prevention:** Output is JSON-encoded
5. **CSRF Protection:** Should be added in production
6. **File Cleanup:** Temporary files are always deleted
7. **Transaction Safety:** Database changes are atomic

---

## Logging

All endpoints log important events:

- Successful uploads
- Failed uploads with error details
- Cloudinary deletion attempts
- Database errors
- Admin activities (via activity logger)

Logs can be found in:
- PHP error log
- Application logs directory
- Admin activity log table

---

## Requirements Satisfied

This implementation satisfies the following requirements from the spec:

- **Requirement 4.1:** Upload new image during edit and update cloudinary_url
- **Requirement 4.2:** Delete old image from Cloudinary using public ID
- **Requirement 4.3:** Allow adding, removing, and replacing additional images
- **Requirement 4.4:** Prevent local storage during edit process
- **Requirement 4.5:** Retain existing URL on upload failure

---

## Future Enhancements

Potential improvements for future versions:

1. Batch image operations
2. Image cropping/editing before upload
3. Automatic image optimization settings
4. Image reordering for additional images
5. Bulk image replacement
6. Image history/versioning
7. CSRF token implementation
8. Rate limiting for uploads
