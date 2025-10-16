# Auto-Truncate AvailToday Cart System

## Overview
The `availtoday_cart` table is automatically truncated (emptied) when the current time exceeds the closing time set in the `business_hours` table.

## How It Works

### Business Logic
- **During Business Hours**: Cart remains active, customers can add/modify items
- **After Closing Time**: Cart is automatically emptied (truncated)
- **Midnight Crossing**: Handles cases where business closes past midnight

### Time Comparison
```
Current Time > Closing Time → Cart is TRUNCATED
Current Time ≤ Closing Time → Cart remains ACTIVE
```

## Database Tables

### 1. `business_hours` Table
Stores opening and closing times:
```sql
CREATE TABLE `business_hours` (
  `id` int(11) NOT NULL,
  `opening_time` time NOT NULL,
  `closing_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
```

**Current Settings:**
- Opening Time: 08:00:00
- Closing Time: 23:42:00

### 2. `availtoday_cart` Table
Stores same-day order cart items that will be truncated after closing time.

## The Script

### **truncate-availtoday-cart.php** (Unified Script)
**Purpose**: Automatically truncates cart when business hours close
**Location**: `NeoExclusiveCafe/truncate-availtoday-cart.php`

**Features:**
✅ Works in multiple modes (CLI, Web API, Force)
✅ Checks current time vs closing time
✅ Logs all actions to `cart-truncation.log`
✅ Only truncates if cart has items
✅ Handles midnight crossing scenarios
✅ JSON response for web requests
✅ Text output for CLI/terminal

### Usage Modes

#### 1. **Cron Job (Automated)**
Run automatically via cron job:

```bash
# Run every minute
* * * * * /usr/bin/php /path/to/NeoExclusiveCafe/truncate-availtoday-cart.php

# Run every 5 minutes
*/5 * * * * /usr/bin/php /path/to/NeoExclusiveCafe/truncate-availtoday-cart.php

# Run at specific times (6 PM, 7 PM, 8 PM, etc.)
0 18,19,20,21,22,23 * * * /usr/bin/php /path/to/NeoExclusiveCafe/truncate-availtoday-cart.php
```

**CLI Output:**
```
=== AvailToday Cart Truncation Check Started ===
Database connection: OK
Current time: 23:45:00, Date: 2025-10-16
Business hours: 08:00:00 - 23:42:00
Time check: Current time >= Closing time, business is CLOSED
Business is CLOSED - proceeding with cart truncation
Cart currently has 2 items
SUCCESS: Cart truncated successfully - 2 items removed
Verification: Cart now has 0 items
=== Truncation Check Completed Successfully ===
```

#### 2. **Web API (Manual Check)**
Access via browser or API call:

**URL**: `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php`

**JSON Response (Business Closed):**
```json
{
  "success": true,
  "message": "Cart truncated successfully - business hours closed",
  "timestamp": "2025-10-16 23:45:00",
  "mode": "auto",
  "action": "truncated",
  "current_time": "23:45:00",
  "current_date": "2025-10-16",
  "opening_time": "08:00:00",
  "closing_time": "23:42:00",
  "items_before": 2,
  "items_after": 0,
  "items_removed": 2,
  "debug_info": {
    "current_minutes": 1425,
    "closing_minutes": 1422,
    "is_closed": true,
    "midnight_crossing": false
  }
}
```

**JSON Response (Business Open):**
```json
{
  "success": true,
  "message": "Business still open - no action needed",
  "timestamp": "2025-10-16 15:30:00",
  "mode": "auto",
  "action": "none",
  "current_time": "15:30:00",
  "opening_time": "08:00:00",
  "closing_time": "23:42:00"
}
```

#### 3. **Force Mode (Emergency)**
Force truncate regardless of business hours:

**URL**: `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php?force=1`

**Or via CLI:**
```bash
php /path/to/NeoExclusiveCafe/truncate-availtoday-cart.php
# Then manually add ?force=1 via web, or modify script
```

**Response:**
```json
{
  "success": true,
  "message": "Cart truncated successfully - business hours closed",
  "mode": "force",
  "action": "truncated",
  "items_removed": 2
}
```

**Log File**: `NeoExclusiveCafe/cart-truncation.log`

## Setup Instructions

### Step 1: Set Business Hours
Update the `business_hours` table with your actual business hours:
```sql
UPDATE business_hours 
SET opening_time = '08:00:00', 
    closing_time = '22:00:00' 
WHERE id = 1;
```

### Step 2: Set Up Cron Job (Linux/cPanel/AlwaysData)
1. Access your cron job manager
2. Add a new cron job:
   ```bash
   */1 * * * * /usr/bin/php /path/to/NeoExclusiveCafe/truncate-availtoday-cart.php
   ```
3. Adjust the path to match your server setup

### Step 3: Test the System
1. **Web Test**: Visit `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php`
2. **Force Test**: Add `?force=1` parameter to URL
3. **CLI Test**: Run `php NeoExclusiveCafe/truncate-availtoday-cart.php` from terminal
4. **Check Logs**: Review `NeoExclusiveCafe/cart-truncation.log` for activity

## Testing Examples

### Test 1: Check Status (During Business Hours)
**Access**: `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php`
**Expected Result:**
```json
{
  "success": true,
  "message": "Business still open - no action needed",
  "action": "none"
}
```

### Test 2: Check Status (After Closing Time)
**Access**: Same URL after closing time
**Expected Result:**
```json
{
  "success": true,
  "message": "Cart truncated successfully - business hours closed",
  "action": "truncated"
}
```

### Test 3: Force Truncate
**Access**: `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php?force=1`
**Expected Result:**
```json
{
  "success": true,
  "mode": "force",
  "action": "truncated",
  "items_removed": 2
}
```

## Monitoring

### Check Log File
View the truncation activity log:
```bash
tail -f NeoExclusiveCafe/cart-truncation.log
```

**Sample Log Output:**
```
[2025-10-16 03:15:00] Starting auto-truncate cart check
[2025-10-16 03:15:00] Current time: 03:15:00, Date: 2025-10-16
[2025-10-16 03:15:00] Business hours: 08:00 - 23:42
[2025-10-16 03:15:00] Business is CLOSED - proceeding with cart truncation
[2025-10-16 03:15:00] Cart currently has 2 items
[2025-10-16 03:15:00] SUCCESS: Cart truncated successfully - 2 items removed
[2025-10-16 03:15:00] Auto-truncate cart check completed successfully
```

## Troubleshooting

### Issue: Cart Not Being Truncated
**Causes:**
1. Cron job not set up or not running
2. Business hours not configured correctly
3. Time zone mismatch

**Solutions:**
1. Verify cron job is active
2. Check `business_hours` table data
3. Ensure server timezone matches business timezone

### Issue: Cart Truncated Too Early
**Cause**: Incorrect closing time in database

**Solution:**
```sql
UPDATE business_hours SET closing_time = 'HH:MM:SS' WHERE id = 1;
```

### Issue: Script Errors
**Check:**
1. Database connection in `database.php`
2. File permissions
3. PHP error logs

## Security Notes

1. **Cron Job Security**: Ensure only authorized users can modify cron jobs
2. **Manual Triggers**: Consider adding authentication to web-accessible scripts
3. **Log Files**: Regularly rotate and clean up log files

## Updates Made (Oct 16, 2025)

### Version 2.0 - Consolidated Script
✅ **Consolidated** 3 separate scripts into 1 unified file
✅ **Created** `truncate-availtoday-cart.php` with multiple modes:
  - CLI mode for cron jobs
  - Web API mode with JSON responses
  - Force mode for emergency truncation
✅ **Removed** redundant files:
  - `auto-truncate-cart.php` (deleted)
  - `truncate-cart-availtoday.php` (deleted)
  - `force-truncate-cart.php` (deleted)
✅ **Fixed** table name from `cart_availtoday` → `availtoday_cart`

**Benefits:**
- Single file to maintain
- Consistent behavior across all modes
- Better error handling
- Comprehensive logging
- Flexible usage options

