# Testing Guide for SDO Quantity Per Day Fix

## Implementation Complete ✓

The following changes have been made:

### Frontend Changes (`backend/pages/products/product-list.js`)
- Added SDO quantity collection in `saveProductChanges()` function
- Added validation for date format and quantity values
- Quantities are collected using `getSDOQuantities()` from sdo-quantity-manager.js
- Data is sent as JSON in FormData field `sdo_quantities`

### Backend Changes (`backend/api/update-product.php`)
- Added logic to receive and parse `sdo_quantities` from POST data
- Implemented transaction-based save to `quantity_per_day_sdo` table
- Added cleanup logic for status transitions
- Added comprehensive error logging

## Manual Testing Steps

### Test 1: SDO-Only Product (Status 4)

1. **Navigate** to product management page
2. **Edit** a product or create new one
3. **Check** only "Same-day order" checkbox
4. **Select** shipping method (Pick Up, Delivery, or Both)
5. **Select** 3 dates in the calendar
6. **Set quantities** for each date (e.g., 10, 15, 20)
7. **Click** "Save Changes"
8. **Verify** in database:
   ```sql
   SELECT * FROM quantity_per_day_sdo WHERE product_id = [YOUR_PRODUCT_ID];
   SELECT * FROM todays_products_dates WHERE product_id = [YOUR_PRODUCT_ID];
   ```
9. **Reload** the page and edit the product again
10. **Verify** quantities display correctly

**Expected Results:**
- ✓ Quantities saved to `quantity_per_day_sdo`
- ✓ Dates saved to `todays_products_dates`
- ✓ Quantities display when reopening modal
- ✓ Product list shows correct stock

### Test 2: Pre-Order with SDO

1. **Edit** a product
2. **Check** both "Pre-order" and "Same-day order" checkboxes
3. **Select** pre-order type (Pick Up, Delivery, or Both)
4. **Select** same-day shipping method
5. **Select** available days for pre-order
6. **Select** dates for same-day order
7. **Set quantities** for each same-day date
8. **Click** "Save Changes"
9. **Verify** in database:
   ```sql
   SELECT * FROM quantity_per_day_sdo WHERE product_id = [YOUR_PRODUCT_ID];
   SELECT * FROM regular_products_today_dates WHERE product_id = [YOUR_PRODUCT_ID];
   SELECT * FROM product_day WHERE product_id = [YOUR_PRODUCT_ID];
   ```

**Expected Results:**
- ✓ Quantities saved to `quantity_per_day_sdo`
- ✓ Dates saved to `regular_products_today_dates`
- ✓ Available days saved to `product_day`
- ✓ Both pre-order and SDO data coexist

### Test 3: Status Transitions

**Test 3a: Pre-Order → SDO**
1. Edit a pre-order product
2. Uncheck "Pre-order"
3. Check "Same-day order"
4. Set dates and quantities
5. Save
6. Verify pre-order data cleared, SDO data saved

**Test 3b: SDO → Pre-Order**
1. Edit an SDO product
2. Uncheck "Same-day order"
3. Check "Pre-order"
4. Save
5. Verify SDO data cleared:
   ```sql
   SELECT * FROM quantity_per_day_sdo WHERE product_id = [YOUR_PRODUCT_ID];
   -- Should return 0 rows
   ```

**Test 3c: Pre-Order+SDO → Pre-Order Only**
1. Edit a product with both
2. Uncheck "Same-day order"
3. Save
4. Verify SDO data cleared

**Expected Results:**
- ✓ Old data cleaned up
- ✓ New data saved correctly
- ✓ No orphaned records

### Test 4: Validation

**Test 4a: Invalid Date Format**
1. Open browser console
2. Edit SDO product
3. Manually corrupt date in calendar (if possible)
4. Try to save
5. Should see error in console

**Test 4b: Negative Quantity**
1. Edit SDO product
2. Set quantity to -5
3. Try to save
4. Should see validation error

**Test 4c: Empty Dates**
1. Edit SDO product
2. Don't select any dates
3. Save
4. Should save successfully with no quantities

### Test 5: Edge Cases

**Test 5a: Zero Quantity**
1. Edit SDO product
2. Set quantity to 0 for a date
3. Save
4. Verify 0 is saved (not skipped)

**Test 5b: Many Dates**
1. Edit SDO product
2. Select 10+ dates
3. Set quantities for all
4. Save
5. Verify all saved correctly

**Test 5c: Update Existing Quantities**
1. Edit SDO product with existing quantities
2. Change some quantities
3. Remove some dates
4. Add new dates
5. Save
6. Verify changes applied correctly

## Browser Console Checks

When testing, check browser console for:
- `Collecting SDO quantities:` log with quantity object
- No JavaScript errors
- Successful save message

## Database Verification Queries

```sql
-- Check quantities
SELECT p.name, qpd.date, qpd.quantity 
FROM quantity_per_day_sdo qpd
JOIN products p ON p.id = qpd.product_id
WHERE qpd.product_id = [YOUR_PRODUCT_ID]
ORDER BY qpd.date;

-- Check dates (SDO only)
SELECT p.name, tpd.available_date, ats.name as shipping_method
FROM todays_products_dates tpd
JOIN products p ON p.id = tpd.product_id
LEFT JOIN availtoday_status ats ON ats.id = tpd.availtoday_status_id
WHERE tpd.product_id = [YOUR_PRODUCT_ID]
ORDER BY tpd.available_date;

-- Check dates (Pre-order + SDO)
SELECT p.name, rptd.available_date, ats.name as shipping_method
FROM regular_products_today_dates rptd
JOIN products p ON p.id = rptd.product_id
LEFT JOIN availtoday_status ats ON ats.id = rptd.availtoday_status_id
WHERE rptd.product_id = [YOUR_PRODUCT_ID]
ORDER BY rptd.available_date;
```

## PHP Error Log Checks

Check PHP error logs for:
```
=== SDO QUANTITIES UPDATE ===
Raw SDO quantities JSON: ...
Parsed SDO quantities: ...
Deleted X existing SDO quantities
Inserting: product_id=X, date=YYYY-MM-DD, quantity=X
Inserted X SDO quantities
SDO quantities saved successfully
```

## Known Issues / Limitations

None identified at this time.

## Rollback Instructions

If issues occur:

1. **Revert JavaScript changes:**
   ```bash
   git checkout backend/pages/products/product-list.js
   ```

2. **Revert PHP changes:**
   ```bash
   git checkout backend/api/update-product.php
   ```

3. **Clear browser cache** and reload

4. **Data remains intact** - no data loss from rollback
