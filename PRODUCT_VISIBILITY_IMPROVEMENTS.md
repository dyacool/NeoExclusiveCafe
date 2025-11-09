# Product Visibility and Order Type Logic Improvements

## Summary of Changes

I've improved the product visibility and order type selection logic in `product-dashboard.php` to properly handle the requirements you specified.

## Changes Made

### 1. Product Visibility Logic (PHP - `determineProductAvailability()` function)

**Improved behavior:**
- **`show_when_unavailable = 1`**: Product is ALWAYS shown even when `p.quantity = 0` or `quantity_per_day_sdo = 0`. The "Out of Stock" badge is displayed.
- **`hide_when_unavailable = 1`**: Product is ALWAYS hidden when `p.quantity = 0` or `quantity_per_day_sdo = 0`.
- **Default behavior**: Products with no stock are hidden (unless `show_when_unavailable = 1`).

**Priority hierarchy:**
1. `hide_when_unavailable = 1` (highest priority - always hide)
2. `show_when_unavailable = 1` (medium priority - always show)
3. Default (lowest priority - hide unavailable products)

### 2. Order Type Selection Logic (JavaScript - `openQuantityModalWithOrderType()` function)

**Improved behavior for dual-capability products (status 1/2/3 with `availtoday_status_id`):**

The quantity modal now intelligently determines what to show based on actual stock availability:

#### Scenario 1: Both Pre-order and Same-day have stock > 0
- **Shows**: Order type selector with both options
- **Customer can**: Choose between "Pre-Order" or "Same Day Order"
- **Each option fetches**: Respective stock (`p.quantity` for pre-order, `quantity_per_day_sdo` for same-day)

#### Scenario 2: Only Same-day has stock > 0 (p.quantity = 0, quantity_per_day_sdo > 0)
- **Shows**: Same-day order ONLY (no selector)
- **Fetches**: `quantity_per_day_sdo` from the database
- **Label**: "For: Today"

#### Scenario 3: Only Pre-order has stock > 0 (p.quantity > 0, quantity_per_day_sdo = 0 or not today)
- **Shows**: Pre-order ONLY (no selector)
- **Fetches**: `p.quantity` from the database

#### Scenario 4: Status 4 products (Same-day only)
- **Shows**: Same-day order ONLY
- **Fetches**: `quantity_per_day_sdo` for today's date
- **Requires**: Date must be in `todays_products_dates` table

### 3. New Helper Functions

Added two new functions to check stock availability without updating the UI:
- `fetchPreOrderQuantityValue(productId)`: Returns pre-order stock quantity
- `fetchTodayQuantityValue(productId)`: Returns same-day stock quantity for today

These functions are used to determine which order types to show before rendering the modal.

## How It Works

### Product Card Display
1. When the page loads, `determineProductAvailability()` checks each product
2. Products are filtered based on visibility flags and stock availability
3. Unavailable products show "Out of Stock" badge if `show_when_unavailable = 1`
4. Products are hidden if `hide_when_unavailable = 1` or if they have no stock (default)

### Product Badge Display
Badges are displayed based on **actual stock availability**, not just configuration:
- **"Same Day & Pre-Order"**: Shows when BOTH `p.quantity > 0` AND `quantity_per_day_sdo > 0` (and date is today)
- **"Same Day Order"**: Shows when ONLY `quantity_per_day_sdo > 0` (and date is today)
- **"Pre-Order"**: Shows when ONLY `p.quantity > 0`
- **"Out of Stock"**: Shows when product is unavailable but `show_when_unavailable = 1`

**Important**: Badges reflect what the customer can actually order, not just what the product is configured to support.

### Quantity Modal
1. When "Add to Cart" is clicked, the modal checks both stock types (if applicable)
2. Based on available stock, it shows:
   - Both options (if both have stock)
   - Only same-day (if only same-day has stock)
   - Only pre-order (if only pre-order has stock)
3. Each option fetches the correct stock quantity from the appropriate table
4. The modal prevents ordering if stock is 0

## Database Tables Used

- **`products.quantity`**: Pre-order stock
- **`quantity_per_day_sdo.quantity`**: Same-day order stock for specific dates
- **`todays_products_dates`**: Available dates for status 4 products
- **`regular_products_today_dates`**: Available dates for status 1/2/3 products with same-day capability
- **`products.show_when_unavailable`**: Flag to force show unavailable products
- **`products.hide_when_unavailable`**: Flag to force hide unavailable products

## Testing Recommendations

1. **Test visibility flags:**
   - Set `show_when_unavailable = 1` on a product with 0 stock → Should show with "Out of Stock" badge
   - Set `hide_when_unavailable = 1` on a product with 0 stock → Should be hidden

2. **Test order type selection:**
   - Product with both stocks > 0 → Should show selector
   - Product with only pre-order stock → Should show pre-order only
   - Product with only same-day stock → Should show same-day only
   - Status 4 product → Should always show same-day only

3. **Test stock fetching:**
   - Verify correct stock is displayed when switching between order types
   - Verify "Add to Cart" is disabled when stock is 0


---

## Cart Stock Validation Feature

### Overview
Added real-time stock validation before checkout to prevent customers from purchasing items that are no longer available or have insufficient stock.

### New Files Created

#### `frontend/pages/cart/validate-cart-stock.php`
Server-side validation endpoint that checks stock availability before allowing checkout.

**Validation Logic:**

**For Pre-Order Items:**
- Checks `products.quantity` (pre-order stock)
- Validates that cart quantity ≤ available stock
- Ensures product is not deleted
- Returns detailed error messages for each invalid item

**For Same-Day Order Items:**
- Checks `quantity_per_day_sdo.quantity` for today's date
- Validates that cart quantity ≤ available stock for today
- Verifies product has same-day configuration for today
- Checks date availability in `todays_products_dates` or `regular_products_today_dates`
- Returns detailed error messages for each invalid item

### Modified Files

#### `frontend/pages/cart/cart.php`
Updated the `proceedToCheckout()` function to:
1. Call stock validation API before proceeding
2. Show loading states: "Processing..." → "Validating stock..." → "Proceeding to checkout..."
3. Display detailed error messages if validation fails
4. Automatically reload page after validation failure to show updated stock
5. Only proceed to checkout if all items pass validation

#### `frontend/pages/cart/cart.css`
Added loading spinner styles for the checkout button loading states.

### User Experience Flow

1. **User clicks "Proceed to Checkout"**
   - Button shows "Processing..." with spinner

2. **Stock Validation (AJAX call)**
   - Button shows "Validating stock..." with spinner
   - Server checks actual database stock for each item
   - Validates based on order type (pre-order vs same-day)

3. **Validation Success**
   - Button shows "Proceeding to checkout..." with spinner
   - User is redirected to checkout page

4. **Validation Failure**
   - Alert shows detailed error messages:
     ```
     Stock validation failed:
     
     • Chocolate Cake: You have 5 in cart but only 3 available
     • Vanilla Cupcake: Out of stock
     • Strawberry Tart: Not available for same-day order today
     
     Please update your cart quantities or remove unavailable items.
     ```
   - Page automatically reloads to show updated stock
   - User can adjust cart and try again

### Error Messages

The validation provides specific error messages based on the issue:

**Pre-Order Items:**
- "Out of stock" - Product has 0 stock
- "You have X in cart but only Y available" - Cart quantity exceeds stock

**Same-Day Order Items:**
- "Out of stock for today" - No stock available for today
- "You have X in cart but only Y available today" - Cart quantity exceeds today's stock
- "Not available for same-day order today" - No date configuration for today
- "Not configured for same-day order today" - Product not set up for same-day on this date

### Benefits

1. **Prevents checkout errors**: Catches stock issues before payment processing
2. **Better user experience**: Clear, actionable error messages
3. **Reduces support tickets**: Users know exactly what's wrong and how to fix it
4. **Accurate inventory**: Validates against real-time database stock
5. **Separate validation**: Pre-order and same-day items validated independently

### Technical Details

**API Endpoint:** `POST /frontend/pages/cart/validate-cart-stock.php`

**Request Parameters:**
- `cart_ids[]`: Array of cart item IDs to validate
- `order_type`: Either "preorder" or "sameday"

**Response Format:**
```json
{
  "success": true/false,
  "message": "Overall status message",
  "errors": [
    {
      "product_name": "Product Name",
      "cart_quantity": 5,
      "available_stock": 3,
      "message": "Detailed error message"
    }
  ]
}
```

### Database Queries

**Pre-Order Validation:**
```sql
SELECT c.quantity as cart_quantity, p.quantity as available_stock
FROM cart c
JOIN products p ON c.product_id = p.id
WHERE c.id = ? AND c.user_id = ?
```

**Same-Day Validation:**
```sql
SELECT c.quantity as cart_quantity, qpd.quantity as available_stock
FROM availtoday_cart c
JOIN products p ON c.product_id = p.id
LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
WHERE c.id = ? AND c.user_id = ?
```
