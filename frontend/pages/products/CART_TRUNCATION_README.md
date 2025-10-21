# Cart Truncation System for NeoCafe

This system automatically clears the `cart_availtoday` table when business hours are closed, ensuring that customers cannot place orders outside of business hours.

## Features

- **Automatic Cart Clearing**: Automatically truncates the `cart_availtoday` table when closing time has passed
- **Business Hours Integration**: Uses the existing business hours system to determine when to clear the cart
- **Real-time Monitoring**: Checks business hours every minute when users are on the page
- **Automated Background Processing**: Can run as a scheduled task for continuous monitoring
- **Logging**: Comprehensive logging for monitoring and debugging

## Files Included

1. **`truncate-cart-availtoday.php`** - Manual cart truncation script
2. **`auto-truncate-cart.php`** - Automated script for scheduled execution
3. **`setup-cart-truncation.bat`** - Windows setup script for Task Scheduler
4. **`check-cart-table.php`** - Utility to check cart table structure and data
5. **`availtoday-cart.js`** - Frontend JavaScript with business hours checking

## How It Works

### 1. Frontend Monitoring (Real-time)
- When users visit the product dashboard, the system checks business hours every minute
- If business hours are closed, it automatically clears the local cart and disables add-to-cart buttons
- Calls the truncation API to clear the server-side cart data

### 2. Background Automation
- The `auto-truncate-cart.php` script can be run as a scheduled task
- Checks business hours and automatically truncates the cart when closed
- Runs independently of user activity

## Setup Instructions

### Option 1: Windows Task Scheduler (Recommended)

1. **Run the setup script**:
   ```
   Double-click setup-cart-truncation.bat
   ```

2. **Verify the task was created**:
   ```
   schtasks /query /tn "NeoCafe Cart Truncation"
   ```

3. **The task will run every 5 minutes automatically**

### Option 2: Manual Task Scheduler Setup

1. Open Task Scheduler (`taskschd.msc`)
2. Create Basic Task
3. Name: `NeoCafe Cart Truncation`
4. Trigger: Every 5 minutes
5. Action: Start a program
6. Program: `C:\xampp\php\php.exe`
7. Arguments: `[path-to-script]/auto-truncate-cart.php`

### Option 3: Cron Job (Linux/Mac)

Add this line to your crontab:
```bash
# Run every 5 minutes
*/5 * * * * /usr/bin/php /path/to/auto-truncate-cart.php

# Or run at specific times (e.g., every hour after 6 PM)
0 18,19,20,21,22,23 * * * /usr/bin/php /path/to/auto-truncate-cart.php
```

## Testing the System

### 1. Check Cart Table Structure
Visit: `[your-domain]/frontend/pages/products/check-cart-table.php`

### 2. Manual Truncation
Visit: `[your-domain]/frontend/pages/products/truncate-cart-availtoday.php`

### 3. View Logs
Check the log file: `frontend/pages/products/cart-truncation.log`

## Business Hours Configuration

The system reads business hours from the `business_hours` table:
- **Table**: `business_hours`
- **Columns**: `opening_time`, `closing_time`
- **Format**: `HH:MM:SS` (e.g., `08:00:00`, `17:00:00`)

If no business hours are set, it defaults to 8:00 AM - 5:00 PM.

## Customization

### Change Check Frequency
- **Frontend**: Modify the `setInterval` call in `availtoday-cart.js`
- **Background**: Modify the scheduled task timing

### Modify Default Business Hours
Edit the default values in `auto-truncate-cart.php`:
```php
$opening_time = '08:00';  // Change this
$closing_time = '17:00';  // Change this
```

### Add Additional Cleanup
In `auto-truncate-cart.php`, add your custom cleanup logic after the cart truncation:
```php
// Add your custom cleanup here
// e.g., clear session data, update status, etc.
```

## Troubleshooting

### Common Issues

1. **Task Scheduler Permission Error**
   - Run the setup script as Administrator
   - Check Windows Task Scheduler permissions

2. **PHP Path Not Found**
   - Update the `PHP_PATH` variable in the batch file
   - Ensure PHP is installed and accessible

3. **Database Connection Error**
   - Check database credentials in `user-includes/database.php`
   - Ensure the database server is running

4. **Log File Permission Error**
   - Ensure the script directory is writable
   - Check file permissions

### Debug Mode

To enable debug mode, modify `auto-truncate-cart.php`:
```php
// Change this line:
ini_set('display_errors', 0);
// To:
ini_set('display_errors', 1);
```

## Security Considerations

- The truncation scripts should only be accessible to authorized users
- Consider adding authentication to the manual truncation endpoints
- Log all truncation activities for audit purposes
- Ensure database credentials are properly secured

## Performance Impact

- **Frontend**: Minimal impact - checks every minute only when users are active
- **Background**: Very low impact - simple database queries every 5 minutes
- **Database**: Minimal impact - only truncates when necessary

## Support

For issues or questions:
1. Check the log files first
2. Verify business hours are set correctly
3. Test the manual truncation script
4. Check database connectivity

## Version History

- **v1.0**: Initial release with basic cart truncation
- **v1.1**: Added automated background processing
- **v1.2**: Added comprehensive logging and error handling
