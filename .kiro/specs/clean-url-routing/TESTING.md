# Clean URL Routing - Testing Guide

## Prerequisites
- Ensure your web server (Apache) is running
- Ensure mod_rewrite is enabled
- The .htaccess file has been updated with clean URL rules

## Testing Checklist

### 1. Test Special Case Mapping
**Test:** Navigate to `/user-dashboard`
- **Expected:** Should load the user dashboard page from `/frontend/pages/home/user-dashboard.php`
- **Browser URL should show:** `neocafe.shop/user-dashboard` (clean URL, no .php extension)
- **Status:** ⬜ Pass / ⬜ Fail

### 2. Test Primary Page Pattern
**Test:** Navigate to `/bulk-form`
- **Expected:** Should load from `/frontend/pages/bulk/bulk-form.php`
- **Browser URL should show:** `neocafe.shop/bulk-form`
- **Status:** ⬜ Pass / ⬜ Fail

### 3. Test Nested Path Pattern
**Test:** Navigate to `/products/view` (if this file exists)
- **Expected:** Should load from `/frontend/pages/products/view.php`
- **Browser URL should show:** `neocafe.shop/products/view`
- **Status:** ⬜ Pass / ⬜ Fail / ⬜ N/A (file doesn't exist)

### 4. Test Static Assets (CSS)
**Test:** Check if CSS files load correctly
- Open browser developer tools (F12)
- Navigate to any page
- Check the Network tab for CSS files
- **Expected:** CSS files should load with 200 status code
- **Status:** ⬜ Pass / ⬜ Fail

### 5. Test Static Assets (JavaScript)
**Test:** Check if JS files load correctly
- Open browser developer tools (F12)
- Navigate to any page
- Check the Network tab for JS files
- **Expected:** JS files should load with 200 status code
- **Status:** ⬜ Pass / ⬜ Fail

### 6. Test Static Assets (Images)
**Test:** Check if images load correctly
- Navigate to any page with images
- **Expected:** Images should display correctly
- **Status:** ⬜ Pass / ⬜ Fail

### 7. Test API Endpoints (Frontend)
**Test:** Verify frontend API endpoints still work
- Test an API call like `/frontend/api/get-products.php`
- **Expected:** API should respond normally (not rewritten)
- **Status:** ⬜ Pass / ⬜ Fail

### 8. Test API Endpoints (Backend)
**Test:** Verify backend API endpoints still work
- Test an API call like `/backend/api/update-product.php`
- **Expected:** API should respond normally (not rewritten)
- **Status:** ⬜ Pass / ⬜ Fail

### 9. Test 404 Behavior
**Test:** Navigate to `/nonexistent-page`
- **Expected:** Should return a 404 error
- **Expected:** Should NOT expose internal file paths in the error
- **Status:** ⬜ Pass / ⬜ Fail

### 10. Test Domain Routing (Admin Subdomain)
**Test:** Navigate to `admin.neocafe.shop`
- **Expected:** Should load the admin interface via index.php
- **Status:** ⬜ Pass / ⬜ Fail

### 11. Test Domain Routing (Rider Subdomain)
**Test:** Navigate to `rider.neocafe.shop`
- **Expected:** Should load the rider interface via index.php
- **Status:** ⬜ Pass / ⬜ Fail

### 12. Test Domain Routing (Main Domain)
**Test:** Navigate to `neocafe.shop`
- **Expected:** Should redirect to `www.neocafe.shop` (force-www rule)
- **Status:** ⬜ Pass / ⬜ Fail

### 13. Test Old URLs Still Work
**Test:** Navigate to `/frontend/pages/home/user-dashboard.php` (full path)
- **Expected:** Should still load the page (backward compatibility)
- **Status:** ⬜ Pass / ⬜ Fail

### 14. Browser Compatibility
**Test:** Test in multiple browsers
- ⬜ Chrome/Edge
- ⬜ Firefox
- ⬜ Safari (if available)
- **Expected:** Clean URLs should work consistently across all browsers

## Common Issues and Solutions

### Issue: Clean URLs return 404
**Possible Causes:**
1. mod_rewrite is not enabled
2. .htaccess file is not being read (AllowOverride not set)
3. File path doesn't match the expected pattern

**Solutions:**
1. Enable mod_rewrite: `a2enmod rewrite` (Linux) or check httpd.conf (Windows)
2. Ensure AllowOverride is set to "All" in your Apache configuration
3. Verify the file exists at the expected location

### Issue: Static assets not loading
**Possible Causes:**
1. File paths in HTML are incorrect
2. Rewrite rules are too aggressive

**Solutions:**
1. Check that static asset paths are absolute or relative to the document root
2. Verify the static file bypass rules are working (they should be first)

### Issue: API endpoints not working
**Possible Causes:**
1. API exclusion rules not working
2. API paths don't match the exclusion pattern

**Solutions:**
1. Check that API paths start with `/frontend/api/` or `/backend/api/`
2. Add additional exclusion rules if needed

### Issue: Infinite redirect loop
**Possible Causes:**
1. Conflicting rewrite rules
2. Missing [L] flag on rules

**Solutions:**
1. Review rule order and ensure [L] flags are present
2. Check for conflicts with other .htaccess files in subdirectories

## Performance Verification

After testing functionality, verify performance:

1. **Page Load Times:** Compare before/after clean URL implementation
   - Should be similar or slightly faster (fewer redirects)

2. **Caching:** Verify static assets are still cached
   - Check response headers for Cache-Control and Expires

3. **Compression:** Verify compression is still active
   - Check response headers for Content-Encoding: gzip

## Testing Complete

Once all tests pass, the clean URL routing implementation is complete and ready for production use.

**Date Tested:** _______________
**Tested By:** _______________
**Overall Status:** ⬜ All Tests Passed / ⬜ Issues Found (see notes below)

**Notes:**
