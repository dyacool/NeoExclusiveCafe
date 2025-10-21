# Cart Truncation Testing Guide

## 🎯 Immediate Testing Options

### Option 1: Force Truncate (Quickest)
**URL:** `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php?force=1`

**What it does:**
- Bypasses time check
- Immediately truncates the `availtoday_cart` table
- Returns JSON response with results

**Expected Response:**
```json
{
  "success": true,
  "message": "Cart truncated successfully - business hours closed",
  "mode": "force",
  "action": "truncated",
  "items_removed": X
}
```

---

### Option 2: Check Auto-Truncation Status
**URL:** `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php`

**What it does:**
- Checks current time vs closing time
- Only truncates if past closing time
- Returns JSON with debug info

**Response (Business Open):**
```json
{
  "success": true,
  "message": "Business still open - no action needed",
  "action": "none",
  "current_time": "15:30:00",
  "closing_time": "23:42:00"
}
```

**Response (Business Closed):**
```json
{
  "success": true,
  "message": "Cart truncated successfully",
  "action": "truncated",
  "items_removed": X
}
```

---

## 🧪 Testing Automatic Truncation

### Step 1: Add Test Items to Cart
1. Login as a customer
2. Add some products with `availtoday_status_id` set
3. Go to: `http://neocafe.cafe:8080/frontend/pages/cart/shopping-cart-sameday.php`
4. Verify items are in cart

### Step 2: Set Closing Time to NOW (for testing)

**Run this SQL in phpMyAdmin:**
```sql
-- Set closing time to 5 minutes ago
UPDATE business_hours 
SET closing_time = DATE_SUB(NOW(), INTERVAL 5 MINUTE)
WHERE id = 1;
```

**Or use the SQL file:**
- File: `sql_configs/set_closing_time_now.sql`
- Copy and paste into phpMyAdmin SQL tab

### Step 3: Trigger Truncation Check
**Visit:** `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php`

**Expected Result:**
```json
{
  "success": true,
  "message": "Cart truncated successfully - business hours closed",
  "action": "truncated",
  "current_time": "15:35:00",
  "closing_time": "15:30:00",
  "items_removed": 2
}
```

### Step 4: Verify Cart is Empty
1. Refresh the shopping cart page
2. Should see "Your same-day cart is empty"
3. Or check database directly:
   ```sql
   SELECT COUNT(*) as cart_count FROM availtoday_cart;
   -- Should return 0
   ```

### Step 5: Restore Original Closing Time
**Run this SQL:**
```sql
UPDATE business_hours 
SET closing_time = '23:42:00'
WHERE id = 1;
```

**Or use the SQL file:**
- File: `sql_configs/restore_closing_time.sql`

---

## ⏰ Setting Up Automated Truncation (Production)

### Method 1: AlwaysData Cron Job (Recommended)

1. **Login to AlwaysData**
2. **Go to:** Web > Scheduled tasks
3. **Add new task:**
   - **URL:** `https://neocafe.cafe/NeoExclusiveCafe/truncate-availtoday-cart.php`
   - **Frequency:** Every minute
   - **Or specific times:** 18:00, 19:00, 20:00, 21:00, 22:00, 23:00, 23:45

### Method 2: cPanel Cron Job

**Command:**
```bash
*/1 * * * * /usr/bin/php /path/to/NeoExclusiveCafe/truncate-availtoday-cart.php
```

**Or run at specific times:**
```bash
0,30,59 * * * * /usr/bin/php /path/to/NeoExclusiveCafe/truncate-availtoday-cart.php
```

### Method 3: Manual Trigger via Web Request

Create a webhook or scheduled task that calls:
```
GET http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php
```

---

## 📊 Monitoring & Logs

### Check Truncation Log
**File:** `NeoExclusiveCafe/cart-truncation.log`

**Sample Log:**
```
[2025-10-16 15:35:00] === AvailToday Cart Truncation Check Started ===
[2025-10-16 15:35:00] Database connection: OK
[2025-10-16 15:35:00] Current time: 15:35:00, Date: 2025-10-16
[2025-10-16 15:35:00] Business hours: 08:00:00 - 15:30:00
[2025-10-16 15:35:00] Time check: Current time >= Closing time, business is CLOSED
[2025-10-16 15:35:00] Business is CLOSED - proceeding with cart truncation
[2025-10-16 15:35:00] Cart currently has 2 items
[2025-10-16 15:35:00] SUCCESS: Cart truncated successfully - 2 items removed
[2025-10-16 15:35:00] Verification: Cart now has 0 items
[2025-10-16 15:35:00] === Truncation Check Completed Successfully ===
```

### View Log File
```bash
tail -f NeoExclusiveCafe/cart-truncation.log
```

Or download via FTP/File Manager

---

## 🔧 Troubleshooting

### Issue: Cart Not Truncating
**Check:**
1. Verify closing time in database:
   ```sql
   SELECT * FROM business_hours;
   ```
2. Check current server time:
   ```sql
   SELECT NOW(), TIME(NOW());
   ```
3. Test force mode: Add `?force=1` parameter
4. Check log file for errors

### Issue: "Business Still Open" but it's Past Closing Time
**Solution:**
- Server timezone might be different
- Check: `SELECT NOW()` in phpMyAdmin
- Adjust closing time accordingly

### Issue: Script Returns Error
**Check:**
1. Database connection in `backend/pages/admin-includes/database.php`
2. File permissions on `cart-truncation.log`
3. Check PHP error logs

---

## 📝 Quick Reference

| Action | URL |
|--------|-----|
| Force Truncate | `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php?force=1` |
| Auto Check | `http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php` |
| Shopping Cart | `http://neocafe.cafe:8080/frontend/pages/cart/shopping-cart-sameday.php` |

| SQL File | Purpose |
|----------|---------|
| `set_closing_time_now.sql` | Set closing time to NOW for testing |
| `restore_closing_time.sql` | Restore original closing time (23:42:00) |

---

## ✅ Testing Checklist

- [ ] Add items to availtoday cart
- [ ] Check current business hours
- [ ] Test force truncation
- [ ] Set closing time to NOW
- [ ] Verify automatic truncation
- [ ] Check cart is empty after truncation
- [ ] Restore original closing time
- [ ] Set up cron job/scheduled task
- [ ] Monitor logs for 24 hours
- [ ] Verify production behavior

---

**Last Updated:** October 16, 2025

