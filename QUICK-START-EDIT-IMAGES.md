# Quick Start Guide - Product Image Editing

## 🚀 Quick Start

### Test the Implementation

1. **Access the test page:**
   ```
   http://your-domain/backend/pages/products/test-edit-images.html
   ```
   (Must be logged in as admin)

2. **Test basic operations:**
   - Enter a product ID (e.g., 1)
   - Click "Get Images" to see current images
   - Upload a new primary image
   - Add additional images (up to 3)
   - Remove additional images

### API Endpoints

#### 1. Get Product Images
```javascript
fetch('get-product-images-edit.php?product_id=123')
  .then(r => r.json())
  .then(data => console.log(data));
```

#### 2. Replace Primary Image
```javascript
const formData = new FormData();
formData.append('product_id', 123);
formData.append('is_primary', 1);
formData.append('image', fileInput.files[0]);

fetch('replace-product-image.php', {
  method: 'POST',
  body: formData
}).then(r => r.json());
```

#### 3. Add Additional Image
```javascript
const formData = new FormData();
formData.append('product_id', 123);
formData.append('action', 'add');
formData.append('image', fileInput.files[0]);

fetch('manage-additional-images.php', {
  method: 'POST',
  body: formData
}).then(r => r.json());
```

#### 4. Remove Additional Image
```javascript
const formData = new FormData();
formData.append('product_id', 123);
formData.append('action', 'remove');
formData.append('image_id', 456);

fetch('manage-additional-images.php', {
  method: 'POST',
  body: formData
}).then(r => r.json());
```

## 📋 Key Features

✅ Replace primary product images  
✅ Add up to 3 additional images  
✅ Remove specific additional images  
✅ Automatic Cloudinary upload  
✅ Automatic old image deletion  
✅ File validation (type, size)  
✅ Transaction-safe operations  
✅ Comprehensive error handling  

## 🔒 Validation Rules

- **File Types:** JPEG, PNG, GIF, WebP only
- **Max Size:** 10MB per image
- **Primary Images:** 1 per product
- **Additional Images:** Maximum 3 per product
- **Authentication:** Admin session required

## 📁 Files Created

```
backend/pages/products/
├── replace-product-image.php          # Replace/add images
├── manage-additional-images.php       # Add/remove additional images
├── get-product-images-edit.php        # Get all product images
├── test-edit-images.html              # Test page
├── EDIT-IMAGES-API-DOCUMENTATION.md   # Full API docs
└── (existing files...)

PRODUCT-EDIT-IMAGES-IMPLEMENTATION-SUMMARY.md  # Implementation summary
QUICK-START-EDIT-IMAGES.md                     # This file
```

## 🐛 Troubleshooting

### Image upload fails
- Check Cloudinary credentials in `config/cloudinary-config.php`
- Verify file size is under 10MB
- Ensure file type is JPEG, PNG, GIF, or WebP

### "Unauthorized access" error
- Make sure you're logged in as admin
- Check session is active

### "Maximum of 3 additional images allowed"
- Remove existing additional images first
- Or replace existing images instead of adding new ones

### Old images not deleted from Cloudinary
- Check error logs for Cloudinary API errors
- Verify Cloudinary credentials have delete permissions

## 📖 Documentation

- **Full API Docs:** `backend/pages/products/EDIT-IMAGES-API-DOCUMENTATION.md`
- **Implementation Summary:** `PRODUCT-EDIT-IMAGES-IMPLEMENTATION-SUMMARY.md`
- **Test Page:** `backend/pages/products/test-edit-images.html`

## 🔗 Integration Example

```javascript
// Simple integration class
class ProductImageManager {
  constructor(productId) {
    this.productId = productId;
    this.baseUrl = '/backend/pages/products/';
  }

  async getImages() {
    const response = await fetch(
      `${this.baseUrl}get-product-images-edit.php?product_id=${this.productId}`
    );
    return await response.json();
  }

  async replacePrimary(file) {
    const formData = new FormData();
    formData.append('product_id', this.productId);
    formData.append('is_primary', 1);
    formData.append('image', file);

    const response = await fetch(
      `${this.baseUrl}replace-product-image.php`,
      { method: 'POST', body: formData }
    );
    return await response.json();
  }

  async addAdditional(file) {
    const formData = new FormData();
    formData.append('product_id', this.productId);
    formData.append('action', 'add');
    formData.append('image', file);

    const response = await fetch(
      `${this.baseUrl}manage-additional-images.php`,
      { method: 'POST', body: formData }
    );
    return await response.json();
  }

  async removeAdditional(imageId) {
    const formData = new FormData();
    formData.append('product_id', this.productId);
    formData.append('action', 'remove');
    formData.append('image_id', imageId);

    const response = await fetch(
      `${this.baseUrl}manage-additional-images.php`,
      { method: 'POST', body: formData }
    );
    return await response.json();
  }
}

// Usage
const manager = new ProductImageManager(123);
await manager.getImages();
await manager.replacePrimary(fileInput.files[0]);
```

## ✅ Next Steps

1. Test all endpoints using the test page
2. Integrate into your product edit UI
3. Add UI elements for image management
4. Test with real product data
5. Monitor Cloudinary usage

## 📞 Support

For issues or questions:
- Check error logs: `logs/php_errors.log`
- Review API documentation
- Test with the provided test page
- Verify Cloudinary configuration

---

**Status:** ✅ Ready for use  
**Version:** 1.0  
**Last Updated:** October 29, 2025
