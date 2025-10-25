# Cart File Migration Plan

## Overview
Replace the old cart.php with cart-new.php and update all references.

## Files to Modify

### 1. Cart Files (frontend/pages/cart/)
- **cart.php** → Rename to **cart-old.php** (backup)
- **cart-new.php** → Rename to **cart.php** (new version)

### 2. Navigation File
**File:** `frontend/user-includes/navbar/customer-navigation.php`
**Line:** ~217
**Current:** 
```php
<a href="<?php echo isset($_SESSION['user_id']) ? '../../../frontend/pages/cart/shopping-cart-preorder.php' : '../../../frontend/login/user/login-signup.php'; ?>" class="cart-link">
```
**Status:** ✅ No change needed - links to `shopping-cart-preorder.php`, not `cart.php`

### 3. Product Dashboard
**File:** `frontend/pages/products/product-dashboard.php`
**Status:** ✅ No direct references to cart.php found
- Uses JavaScript for cart operations
- No hardcoded cart.php links

## Key Differences Between Old and New Cart

### Old Cart (cart.php)
- Separate Pickup and Delivery sections
- Day filtering per section
- Select All per section
- Mixed selection warnings
- Smart filtering based on common days
- Validation prevents mixing pickup/delivery

### New Cart (cart-new.php)
- **Combined Pre-Order and Same Day sections**
- **Shipping method inheritance for Status 3 products**
- Visual indicators ("→ Will be Pick Up" / "→ Will be Delivery")
- Prevents mixing Status 1 and Status 2 products
- Separate checkout flows (checkout.php vs availtoday-checkout.php)
- Terms and conditions checkbox

## Migration Steps

1. ✅ **Backup old cart.php**
   - Rename `frontend/pages/cart/cart.php` to `cart-old.php`

2. ✅ **Activate new cart**
   - Rename `frontend/pages/cart/cart-new.php` to `cart.php`

3. ✅ **Verify navigation links**
   - Check `customer-navigation.php` - Already points to `shopping-cart-preorder.php`
   - No changes needed

4. ✅ **Verify product dashboard**
   - No direct cart.php references found
   - Uses JavaScript cart functions

5. ⚠️ **Check for other references**
   - Search codebase for any other files linking to cart.php
   - Update if necessary

## Files That May Reference Cart

### Potential References to Check:
1. `frontend/pages/cart/checkout.php` - May redirect back to cart
2. `frontend/pages/cart/availtoday-checkout.php` - May redirect back to cart
3. Any JavaScript files in cart folder
4. Any redirect scripts after adding to cart

## Testing Checklist

After migration, test:
- [ ] Cart icon in navigation works
- [ ] Adding products to cart works
- [ ] Pre-order cart displays correctly
- [ ] Same-day cart displays correctly
- [ ] Shipping method inheritance works
- [ ] Status 3 products show correct indicators
- [ ] Checkout button redirects correctly
- [ ] Terms and conditions validation works
- [ ] Quantity updates work
- [ ] Item removal works
- [ ] Mixed selection prevention works

## Rollback Plan

If issues occur:
1. Rename `cart.php` back to `cart-new.php`
2. Rename `cart-old.php` back to `cart.php`
3. Clear browser cache
4. Test functionality

## Notes

- The new cart.php uses a completely different structure
- Old cart had pickup/delivery separation
- New cart has pre-order/same-day separation
- Shipping method inheritance is a new feature
- Both carts prevent incompatible product mixing
- Navigation already uses `shopping-cart-preorder.php` (not cart.php)

## Conclusion

✅ **Safe to proceed** - No direct references to cart.php found in navigation or product dashboard
⚠️ **Recommendation** - Search entire codebase for "cart.php" references before final migration
