# Deployment Guide: Localhost to Production (neocafe.shop)

This guide explains how to deploy changes from your localhost development environment to the production domain **http://www.neocafe.shop/**

## 📋 Prerequisites

1. **AlwaysData FTP/SFTP Access**
   - FTP Host: `ftp-neoexclusivecafe.alwaysdata.net`
   - Username: Your AlwaysData account username
   - Password: Your AlwaysData account password
   - Port: 21 (FTP) or 22 (SFTP)

2. **Database Access**
   - Database is already configured in `config/database-config.php`
   - Production database: `neoexclusivecafe_crud`
   - Host: `mysql-neoexclusivecafe.alwaysdata.net`

3. **FTP Client Software**
   - **Windows**: FileZilla, WinSCP, or built-in Windows Explorer
   - **Mac**: FileZilla, Cyberduck, or Transmit
   - **VS Code**: SFTP extension (recommended for developers)

---

## 🚀 Deployment Methods

### Method 1: Using FileZilla (Recommended for Beginners)

#### Step 1: Download and Install FileZilla
- Download from: https://filezilla-project.org/
- Install the FileZilla Client (not Server)

#### Step 2: Connect to AlwaysData
1. Open FileZilla
2. Click **File** → **Site Manager**
3. Click **New Site** and name it "NeoCafe Production"
4. Enter your credentials:
   ```
   Host: ftp-neoexclusivecafe.alwaysdata.net
   Protocol: FTP - File Transfer Protocol
   Port: 21
   Logon Type: Normal
   User: [Your AlwaysData username]
   Password: [Your AlwaysData password]
   ```
5. Click **Connect**

#### Step 3: Upload Files
1. **Left side (Local)**: Navigate to your project folder:
   ```
   C:\Users\andre\OneDrive\Documents\Capstone\NeoExclusiveCafe
   ```

2. **Right side (Remote)**: Navigate to your AlwaysData web directory:
   ```
   /www/neoexclusivecafe/  (or similar path)
   ```
   *Note: The exact path may vary. Check your AlwaysData dashboard for the correct path.*

3. **Select files to upload**:
   - For **new files**: Right-click → **Upload**
   - For **modified files**: Right-click → **Upload** (overwrites existing)
   - For **multiple files**: Select multiple files, then drag and drop

4. **Important files to upload**:
   ```
   frontend/api/submit-review.php
   frontend/pages/products/product-dashboard.php
   frontend/pages/cart/order-details.php
   config/database-config.php (if changed)
   config/domain-config.php (if changed)
   ```

#### Step 4: Verify Upload
- Check file timestamps on the server
- Ensure file sizes match

---

### Method 2: Using VS Code SFTP Extension (Recommended for Developers)

#### Step 1: Install SFTP Extension
1. Open VS Code
2. Go to Extensions (Ctrl+Shift+X)
3. Search for "SFTP" by Natizyskunk
4. Click **Install**

#### Step 2: Configure SFTP
1. Create `.vscode/sftp.json` in your project root:
   ```json
   {
       "name": "NeoCafe Production",
       "host": "ftp-neoexclusivecafe.alwaysdata.net",
       "protocol": "ftp",
       "port": 21,
       "username": "your-username",
       "password": "your-password",
       "remotePath": "/www/neoexclusivecafe/",
       "uploadOnSave": false,
       "useTempFile": false,
       "openSsh": false
   }
   ```

#### Step 3: Upload Files
1. Right-click on a file or folder
2. Select **SFTP: Upload**
3. Or use command palette (Ctrl+Shift+P) → **SFTP: Upload**

---

### Method 3: Using Git + AlwaysData Git Deployment (If Configured)

If you have Git deployment set up in AlwaysData:

```bash
# 1. Commit your changes
git add .
git commit -m "Deploy: Remove review form from product modal"

# 2. Push to your repository
git push origin main

# 3. AlwaysData will automatically deploy (if auto-deploy is enabled)
```

---

## 📁 Files Changed in This Session

Based on your recent changes, upload these files:

### Modified Files:
```
✅ frontend/api/submit-review.php
✅ frontend/pages/products/product-dashboard.php
✅ frontend/pages/cart/order-details.php
```

### Database Changes (if needed):
If you created the `product_reviews` table locally, you need to run the migration on production:

1. **Option A: Via phpMyAdmin**
   - Go to: https://admin.alwaysdata.com
   - Access phpMyAdmin
   - Select database: `neoexclusivecafe_crud`
   - Go to SQL tab
   - Run: `sql_configs/create_product_reviews_table.sql`

2. **Option B: Via FTP + Browser**
   - Upload `scripts/create-reviews-table.php` to server
   - Visit: `http://www.neocafe.shop/scripts/create-reviews-table.php`
   - Check for success message
   - **Delete the script after running** (security)

---

## ✅ Pre-Deployment Checklist

Before uploading, ensure:

- [ ] **Tested locally**: All changes work on localhost
- [ ] **No debug code**: Remove `console.log()`, `var_dump()`, `print_r()`
- [ ] **Error handling**: Production errors are logged, not displayed
- [ ] **Database ready**: Tables exist or migration scripts ready
- [ ] **Backup created**: Backup production database (if making DB changes)
- [ ] **File permissions**: Ensure uploaded files have correct permissions (644 for files, 755 for directories)

---

## 🔍 Post-Deployment Testing

After uploading, test these on **http://www.neocafe.shop/**:

### 1. Review System
- [ ] Open a product modal → Should **NOT** show "Write a Review" form
- [ ] Reviews section should still display existing reviews
- [ ] Go to order details page → "Write a Review" button should be visible
- [ ] Submit a review from order details → Should work correctly

### 2. General Functionality
- [ ] Homepage loads correctly
- [ ] Products display correctly
- [ ] Add to cart works
- [ ] Checkout process works
- [ ] No JavaScript errors in browser console (F12)

### 3. Database
- [ ] Check if `product_reviews` table exists (if needed)
- [ ] Verify reviews can be submitted
- [ ] Verify reviews display correctly

---

## 🐛 Troubleshooting

### Issue: Changes not appearing on production

**Solutions:**
1. **Clear browser cache**: Ctrl+Shift+Delete → Clear cache
2. **Hard refresh**: Ctrl+F5 or Ctrl+Shift+R
3. **Check file upload**: Verify files were uploaded correctly
4. **Check file paths**: Ensure paths are correct on server
5. **Check permissions**: Files should be 644, directories 755

### Issue: 500 Internal Server Error

**Solutions:**
1. **Check error logs**: AlwaysData dashboard → Logs
2. **Check PHP syntax**: Run `php -l filename.php` locally
3. **Check database connection**: Verify `config/database-config.php`
4. **Check file permissions**: Ensure files are readable

### Issue: Database errors

**Solutions:**
1. **Verify table exists**: Check via phpMyAdmin
2. **Run migration script**: If table is missing
3. **Check foreign keys**: May need to disable temporarily
4. **Check database credentials**: Verify in `config/database-config.php`

### Issue: CSS/JS not loading

**Solutions:**
1. **Check file paths**: Use relative paths or absolute paths
2. **Clear cache**: Browser cache and CDN cache (if using)
3. **Check .htaccess**: Ensure rewrite rules are correct
4. **Check file permissions**: CSS/JS files should be readable

---

## 🔒 Security Best Practices

1. **Never upload sensitive files**:
   - `.env` files
   - `composer.json` with dev dependencies
   - Test files
   - Backup files

2. **Remove debug files**:
   - `test-*.php`
   - `debug-*.php`
   - Migration scripts after running

3. **Set correct permissions**:
   - Files: 644
   - Directories: 755
   - Executable scripts: 755

4. **Use SFTP instead of FTP** (if available):
   - More secure
   - Encrypted connection

---

## 📝 Quick Reference

### AlwaysData Dashboard
- URL: https://admin.alwaysdata.com
- Access: Your AlwaysData account credentials

### Production URLs
- Main Site: http://www.neocafe.shop/
- Admin: http://admin.neocafe.shop/
- Rider: http://rider.neocafe.shop/

### Database Access
- phpMyAdmin: Via AlwaysData dashboard
- Host: `mysql-neoexclusivecafe.alwaysdata.net`
- Database: `neoexclusivecafe_crud`

### FTP Connection
- Host: `ftp-neoexclusivecafe.alwaysdata.net`
- Port: 21 (FTP) or 22 (SFTP)
- Protocol: FTP or SFTP

---

## 🎯 Deployment Workflow Summary

```
1. Test changes locally ✅
   ↓
2. Backup production database (if DB changes) 💾
   ↓
3. Upload modified files via FTP/SFTP 📤
   ↓
4. Run database migrations (if needed) 🗄️
   ↓
5. Test on production domain 🧪
   ↓
6. Monitor error logs 📊
   ↓
7. Done! ✅
```

---

## 📞 Support

If you encounter issues:

1. **Check AlwaysData Documentation**: https://help.alwaysdata.com/
2. **Check Error Logs**: AlwaysData dashboard → Logs
3. **Check Browser Console**: F12 → Console tab
4. **Check Network Tab**: F12 → Network tab (for API errors)

---

**Last Updated**: Based on current project structure
**Project**: NeoExclusiveCafe
**Production Domain**: http://www.neocafe.shop/

