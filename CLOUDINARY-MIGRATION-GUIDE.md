# Cloudinary Image Migration Guide

## ✅ Setup Complete

Your Cloudinary integration is now fully configured and ready to migrate images!

### What's Been Done:

1. **Cloudinary SDK Installed** ✅
   - Package: `cloudinary/cloudinary_php`
   - Configuration file: `config/cloudinary-config.php`
   - Credentials stored in `.env` (secure)

2. **Database Prepared** ✅
   - `product_images` table has Cloudinary columns
   - `carousel_images` table has Cloudinary columns
   - `image_migrations` tracking table created

3. **Helper Functions Created** ✅
   - File: `backend/includes/cloudinary-helper.php`
   - Functions: upload, validate, delete, logging

4. **Migration Script Ready** ✅
   - File: `scripts/migrate-images-to-cloudinary.php`
   - Handles: products, carousel, general, payments, refunds

---

## 🚀 How to Run the Migration

### Step 1: Test Connection (Already Done)
```bash
php test-cloudinary-connection.php
```
Expected output: ✅ SUCCESS: Successfully connected to Cloudinary

### Step 2: Prepare Database (Already Done)
```bash
php scripts/prepare-database-for-cloudinary.php
```

### Step 3: Run the Full Migration
```bash
php scripts/migrate-images-to-cloudinary.php
```

This will:
- Migrate all product images from `product_images` table
- Migrate all carousel images from `carousel_images` table
- Migrate general images from `assets/images/`
- Migrate payment proofs from `assets/bulk_payments/`
- Migrate refund proofs from `assets/refund-proofs/`
- Generate a detailed migration report

**⏱️ Estimated Time:** Depends on number of images (approximately 1-2 seconds per image)

---

## 📊 What the Migration Does

### For Each Image:
1. ✅ Validates the local file exists and is readable
2. ✅ Checks file size and dimensions
3. ✅ Uploads to Cloudinary with optimization
4. ✅ Updates database with Cloudinary URL
5. ✅ Logs success/failure to `image_migrations` table
6. ✅ Generates detailed report

### Database Updates:
- **product_images**: Updates `cloud_url`, `cloud_public_id`, `cloud_provider`
- **carousel_images**: Updates `cloud_url`, `cloud_public_id`, `cloud_provider`
- **image_migrations**: Tracks all migrations with status

---

## 📁 Cloudinary Folder Structure

Your images will be organized in Cloudinary as:
```
neocafe/
├── products/          # Product images
├── carousel/          # Carousel/banner images
├── assets/            # General UI images
├── bulk_payments/     # Payment proof images
└── refund_proofs/     # Refund proof images
```

---

## 🔍 Monitoring the Migration

### During Migration:
The script shows real-time progress:
```
[1/50] Migrating: Product Name - PRIMARY
  ✅ SUCCESS: https://res.cloudinary.com/...
```

### After Migration:
Check the generated report:
```
scripts/migration-report-YYYY-MM-DD-HHMMSS.txt
```

### Verify in Database:
```sql
-- Check migrated product images
SELECT COUNT(*) as migrated 
FROM product_images 
WHERE cloud_url IS NOT NULL AND cloud_url != '';

-- Check migration log
SELECT image_type, status, COUNT(*) as count
FROM image_migrations
GROUP BY image_type, status;
```

---

## ⚠️ Important Notes

### Before Running Migration:

1. **Backup Your Database**
   ```bash
   # Create a backup first!
   mysqldump -u username -p database_name > backup_before_migration.sql
   ```

2. **Check Disk Space**
   - Ensure you have enough space for the migration log
   - Original files will NOT be deleted automatically

3. **Test with a Few Images First**
   - You can modify the script to limit the number of images
   - Add `LIMIT 5` to SQL queries for testing

### During Migration:

- ⏳ Don't interrupt the process
- 📊 Monitor the console output
- 🔍 Check for any error messages

### After Migration:

- ✅ Verify images are accessible in Cloudinary dashboard
- ✅ Check database has been updated
- ✅ Review the migration report
- ✅ Test image display on your website

---

## 🐛 Troubleshooting

### Issue: "File not found"
**Solution:** Check that the image paths in the database match actual file locations

### Issue: "Failed to upload"
**Solution:** 
- Check your Cloudinary credentials in `.env`
- Verify internet connection
- Check Cloudinary account limits

### Issue: "Database update failed"
**Solution:** Check database connection and permissions

### Issue: Images not displaying after migration
**Solution:** You need to update your application code to use Cloudinary URLs (see next section)

---

## 📝 Next Steps After Migration

### Task 8: Update Application Code

You'll need to update your PHP files to use Cloudinary URLs instead of local paths:

#### Example for Product Images:
```php
// OLD WAY (local):
<img src="<?php echo $product['image_url']; ?>" alt="Product">

// NEW WAY (Cloudinary):
<img src="<?php echo $product['cloud_url'] ?: $product['image_url']; ?>" alt="Product">
```

#### Files to Update:
- `frontend/pages/products/product-dashboard.php`
- `backend/pages/products/product-list.php`
- `backend/pages/products/add-product.php`
- Any other files displaying images

### Task 9: Update Upload Handlers

Modify upload scripts to send new images directly to Cloudinary:

```php
require_once 'backend/includes/cloudinary-helper.php';

// When user uploads a new image:
$result = uploadToCloudinary($_FILES['image']['tmp_name'], 'neocafe/products');

if ($result['success']) {
    // Save $result['url'] and $result['public_id'] to database
    $cloudUrl = $result['url'];
    $publicId = $result['public_id'];
}
```

### Task 10: Cleanup (Optional)

After verifying everything works:
1. Keep local images as backup for 30 days
2. After verification period, you can delete local image directories
3. Update `.gitignore` (already done)

---

## 📞 Support

If you encounter issues:
1. Check the migration report log file
2. Review the `image_migrations` table for failed uploads
3. Verify Cloudinary dashboard for uploaded images
4. Check PHP error logs

---

## 🎉 Benefits After Migration

- ✅ Reduced deployment size (no large image files in git)
- ✅ Faster image loading (Cloudinary CDN)
- ✅ Automatic image optimization
- ✅ Responsive images support
- ✅ Better scalability
- ✅ Built-in image transformations

---

## 📊 Migration Checklist

- [x] Cloudinary SDK installed
- [x] Configuration files created
- [x] Database prepared
- [x] Helper functions created
- [x] Migration script created
- [ ] **Run migration** ← YOU ARE HERE
- [ ] Verify migration success
- [ ] Update application code
- [ ] Update upload handlers
- [ ] Test thoroughly
- [ ] Cleanup (optional)

---

**Ready to migrate?** Run: `php scripts/migrate-images-to-cloudinary.php`
