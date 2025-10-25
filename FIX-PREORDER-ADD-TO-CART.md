# Fix Pre-Order Add to Cart

## ✅ Issue Fixed

Pre-order items were not being added to cart because the code was only using `addToAvailableTodayCart()` which adds to the same-day cart table.

## Problem

**Before:**
- All products used `addToAvailableTodayCart()` function
- This function adds to `availtoday_cart` table
- Pre-order items should go to `cart` table
- Result: Pre-order items not showing in cart.php

## Solution

Updated `confirmAddToCart()` function to check the order type and use the appropriate method:

### Logic Flow

```
User clicks "Add to Cart"
    ↓
Check selectedOrderType
    ↓
┌─────────────────────────────────────┐
│ Order Type?                         │
└─────────────────────────────────────┘
    ↓                    ↓
 'preorder'         'sameday'
    ↓                    ↓
Use fetch API      Use addToAvailableTodayCart()
    ↓                    ↓
POST to            Add to availtoday_cart
add-to-cart.php    
    ↓
Add to cart table
```

## Implementation

### Pre-Order Items
```javascript
fetch('../cart/add-to-cart.php', {
    method: 'POST',
    body: `product_id=${id}&quantity=${quantity}`
})
```
- Uses `add-to-cart.php` endpoint
- Adds to `cart` table
- Shows in cart.php pre-order section

### Same-Day Items
```javascript
addToAvailableTodayCart(id, quantity, button)
```
- Uses existing function
- Adds to `availtoday_cart` table
- Shows in cart.php same-day section

## Order Type Determination

The `selectedOrderType` is set in `openQuantityModalWithOrderType()`:

1. **Status 1, 2, 3 only** → `'preorder'` (default)
2. **Status 4 only** → `'sameday'`
3. **Status 1, 2, 3 + availtoday_status_id** → User selects via radio buttons

## Error Handling

**Pre-Order:**
- Checks response.success
- Shows error message if failed
- Resets button state
- Logs error to console

**Same-Day:**
- Checks if function exists
- Shows error if not available
- Resets button state

## Success Flow

Both order types now:
1. ✅ Show loading spinner
2. ✅ Add to appropriate cart table
3. ✅ Show success message
4. ✅ Display green checkmark
5. ✅ Auto-close modal
6. ✅ Reset button state

## Files Modified

**frontend/pages/products/product-dashboard.php**
- Updated `confirmAddToCart()` function
- Added order type check
- Added fetch API call for pre-order
- Maintained same-day functionality

## Testing Checklist

### Pre-Order Items (Status 1, 2, 3)
- [ ] Add Status 1 (Pick Up) product
- [ ] Check cart.php pre-order section
- [ ] Verify item appears
- [ ] Check quantity is correct

### Same-Day Items (Status 4)
- [ ] Add Status 4 product
- [ ] Check cart.php same-day section
- [ ] Verify item appears
- [ ] Check quantity is correct

### Mixed Items (Status 1/2/3 + availtoday_status_id)
- [ ] Select "Pre-Order" option
- [ ] Add to cart
- [ ] Verify in pre-order section
- [ ] Select "Same Day Order" option
- [ ] Add to cart
- [ ] Verify in same-day section

## Database Tables

**cart** (Pre-Order)
- Columns: id, user_id, product_id, quantity, price, created_at
- Used for: Status 1, 2, 3 products

**availtoday_cart** (Same-Day)
- Columns: id, user_id, product_id, quantity, created_at
- Used for: Status 4 products and same-day options

## Notes

- The fix maintains backward compatibility with same-day cart
- Pre-order items now correctly use the regular cart table
- Both cart types show in cart.php in their respective sections
- Loading states and success messages work for both types
