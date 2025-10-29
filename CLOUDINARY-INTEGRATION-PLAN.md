# Cloudinary Integration Plan

## Goal
Convert all product image handling to use Cloudinary URLs exclusively for security.

## Example Cloudinary URL
```
https://res.cloudinary.com/dvdccumbs/image/upload/v1761594980/primary_1757776354_mpd5kt.jpg
```

## What Needs to Change

### 1. Database Schema ✅
- Add `cloudinary_url` column to products table
- Add `cloudinary_additional_images` column to products table

### 2. Image Upload (Add Product) 🔄
**Current:** Uploads to local `/assets/product-images/` then tries Cloudinary
**New:** Upload directly to Cloudinary, store URL in database, NO local storage

### 3. Image Display (Product List/Dashboard) 🔄
**Current:** Uses local `image_path` from database
**New:** Use `CloudinaryImageFetcher` to get secure Cloudinary URLs

### 4. Image Edit (Edit Product) 🔄
**Current:** Updates local files
**New:** Upload new images to Cloudinary, update URLs, delete old from Cloudinary

## Implementation Steps

1. ✅ Create `CloudinaryImageFetcher` class (DONE)
2. ✅ Create database migration script (DONE)
3. 🔄 Update `add-product.php` - Upload directly to Cloudinary
4. 🔄 Update `product-list.php` - Display from Cloudinary
5. 🔄 Update `product-dashboard.php` - Display from Cloudinary  
6. 🔄 Update edit product functionality
7. 🔄 Test everything

## Security Benefits
- ✅ No local file system access
- ✅ All images served over HTTPS
- ✅ Automatic image optimization
- ✅ CDN delivery worldwide
- ✅ No direct file paths exposed
