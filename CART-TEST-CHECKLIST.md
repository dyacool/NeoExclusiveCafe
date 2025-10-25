# Cart.php Test Checklist

## Before Testing - Backup
- [x] Created cart-new.php
- [ ] Backup old cart.php (rename to cart-old.php)
- [ ] Rename cart-new.php to cart.php

## Test Scenarios

### 1. Pre-Order Items Display
- [ ] Pre-order items show in "Pre-Order Items" section
- [ ] Items load from `cart` table correctly
- [ ] Product images display
- [ ] Quantities display correctly
- [ ] Prices calculate correctly

### 2. Same Day Order Items Display
- [ ] Same day items show in "Same Day Order Items" section
- [ ] Items load from `availtoday_cart` table correctly
- [ ] Shows today's date in header
- [ ] Product images display
- [ ] Quantities display correctly
- [ ] Prices calculate correctly

### 3. Shipping Method Badges
- [ ] **Status 1 products** show "Pick Up Only!" badge (Green)
- [ ] **Status 2 products** show "Delivery Only!" badge (Blue)
- [ ] **Status 3 products** show "Pick Up or Delivery" badge (Purple)

### 4. Shipping Method Logic
- [ ] Cannot select both Status 1 and Status 2 products together
- [ ] Alert shows when trying to mix Pick Up Only and Delivery Only
- [ ] Can select Status 1 + Status 3 products together
- [ ] Can select Status 2 + Status 3 products together
- [ ] Status 3 products inherit shipping method from Status 1 or 2

### 5. Quantity Updates
- [ ] Click + button increases quantity
- [ ] Click - button decreases quantity
- [ ] Quantity updates in database (pre-order)
- [ ] Quantity updates in database (same-day)
- [ ] Page refreshes after update
- [ ] Totals recalculate correctly

### 6. Remove Items
- [ ] Click "Remove" shows confirmation
- [ ] Confirm removes item from cart (pre-order)
- [ ] Confirm removes item from cart (same-day)
- [ ] Page refreshes after removal
- [ ] Totals recalculate correctly

### 7. Selection & Checkout
- [ ] Checkboxes work for pre-order items
- [ ] Checkboxes work for same-day items
- [ ] Selected count updates correctly
- [ ] Total amount updates correctly
- [ ] Checkout button enables when items selected
- [ ] Checkout button disabled when no items selected

### 8. Checkout Redirect
- [ ] Selecting only pre-order items → redirects to `checkout.php`
- [ ] Selecting only same-day items → redirects to `availtoday-checkout.php`
- [ ] Cart IDs passed correctly in URL

### 9. Empty Cart States
- [ ] "No pre-order items in cart" shows when empty
- [ ] "No same day order items in cart" shows when empty
- [ ] Both sections can be empty independently

### 10. Responsive Design
- [ ] Layout works on desktop
- [ ] Layout works on tablet
- [ ] Layout works on mobile
- [ ] Tables scroll horizontally if needed

## Database Queries to Verify

### Pre-Order Items
```sql
SELECT c.id, c.quantity, c.price, p.name, p.status_id
FROM cart c
JOIN products p ON c.product_id = p.id
WHERE c.user_id = YOUR_USER_ID;
```

### Same Day Items
```sql
SELECT c.id, c.quantity, p.name, p.price, p.status_id
FROM availtoday_cart c
JOIN products p ON c.product_id = p.id
WHERE c.user_id = YOUR_USER_ID;
```

## Known Issues to Watch For
- [ ] Mixed shipping method alert works
- [ ] Quantity cannot go below 1
- [ ] Removed items don't reappear
- [ ] Totals match actual cart contents

## Success Criteria
✅ All pre-order items display correctly
✅ All same-day items display correctly
✅ Shipping badges show correctly
✅ Cannot mix incompatible shipping methods
✅ Quantity updates work
✅ Remove items work
✅ Checkout redirects correctly
✅ No JavaScript errors in console
✅ No PHP errors on page

---

## Ready to Deploy?
Once all tests pass, the new cart.php is ready to replace the old one!
