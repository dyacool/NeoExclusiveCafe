# Enable GD Library in XAMPP

The GD library is needed for automatic image resizing. Follow these steps to enable it:

## Steps to Enable GD in XAMPP

1. **Open php.ini file:**
   - Go to `C:\xampp\php\php.ini`
   - Open it with a text editor (Notepad, VS Code, etc.)

2. **Find the GD extension line:**
   - Search for: `;extension=gd`
   - It should be around line 900-950

3. **Remove the semicolon to enable it:**
   - Change: `;extension=gd`
   - To: `extension=gd`

4. **Save the file**

5. **Restart Apache:**
   - Open XAMPP Control Panel
   - Stop Apache
   - Start Apache again

6. **Verify GD is enabled:**
   - Create a file `phpinfo.php` in `C:\xampp\htdocs\` with content: `<?php phpinfo(); ?>`
   - Visit `http://localhost/phpinfo.php` in your browser
   - Search for "gd" - you should see a GD section with version info
   - Delete the phpinfo.php file after checking

## Alternative: Check from Command Line

Run this command in your terminal:
```
php -m | findstr gd
```

If GD is enabled, you'll see "gd" in the output.

## What Happens Without GD?

- Images larger than 5000x5000 pixels will be uploaded without resizing
- Cloudinary may reject very large images
- The system will log a warning but continue to work

## Recommended: Enable GD

Enabling GD is highly recommended because:
- Automatically resizes large images before upload
- Reduces upload time and bandwidth
- Prevents Cloudinary dimension errors
- Maintains image quality while reducing file size
