# Same-Day Availability Check Implementation

## ✅ Feature Added

Products with status_id 1, 2, or 3 that also have status_id 4 (same-day option) will now only show the pre-order option if:
1. The date is not today, OR
2. There are no more available dates for same-day

## Changes Made

### 1. product-dashboard.php

**New Function: `checkSameDayAvailability(productId)`**
- Checks if same-day option should be available for a product
- Calls `get-sdo-quantity.php` to check:
  - If there's a date entry for today
  - If there's quantity available
- Returns `true` only if BOTH conditions are met

**Updated Function: `openQuantityModalWithOrderType()`**
- Now `async` to support the availability check
- For products with both pre-order and same-day (status 1/2/3 + availtoday_status_id):
  - Calls `checkSameDayAvailability()` first
  - If same-day is available → Shows both options
  - If same-day is NOT available → Shows pre-order only

### 2. get-sdo-quantity.php

**New Response Field: `has_date_today`**
- Returns boolean indicating if product has a date entry for today
- Checks appropriate table based on product type:
  - Status 1/2/3 with availtoday_status_id → `regular_products_today_dates`
  - Status 4 only → `todays_products_dates`

## Logic Flow

```
Product with status_id 1/2/3 + availtoday_status_id
    ↓
Check same-day availability
    ↓
┌─────────────────────────────────────┐
│ Has date for today?                 │
│ AND                                 │
│ Has quantity available?             │
└─────────────────────────────────────┘
    ↓                    ↓
   YES                  NO
    ↓                    ↓
Show both          Show pre-order
options            only
```

## Example Scenarios

### Scenario 1: Same-Day Available
- Product: Pandesal (Status 2 + availtoday_status_id 2)
- Date: Today (2024-10-25) exists in `regular_products_today_dates`
- Quantity: 50 available in `quantity_per_day_sdo`
- **Result:** Shows both "Pre-Order" and "Same Day Order" buttons

### Scenario 2: No Date for Today
- Product: Ensaymada (Status 1 + availtoday_status_id 1)
- Date: Today (2024-10-25) NOT in `regular_products_today_dates`
- **Result:** Shows "Pre-Order" button only

### Scenario 3: Date Exists but No Quantity
- Product: Cassava Cake (Status 3 + availtoday_status_id null)
- Date: Today exists in `regular_products_today_dates`
- Quantity: 0 in `quantity_per_day_sdo`
- **Result:** Shows "Pre-Order" button only

### Scenario 4: Status 4 Only (Same-Day Only Product)
- Product: Fresh Bread (Status 4)
- Date: Today NOT in `todays_products_dates`
- **Result:** Shows "Not available today" message

## Benefits

✅ **Prevents confusion** - Users only see same-day option when actually available
✅ **Better UX** - No need to show unavailable options
✅ **Accurate inventory** - Checks real-time availability
✅ **Flexible** - Works for all product status combinations

## Testing Checklist

- [ ] Product with status 1 + availtoday_status_id (date today, quantity > 0)
- [ ] Product with status 2 + availtoday_status_id (date today, quantity = 0)
- [ ] Product with status 3 + availtoday_status_id (no date today)
- [ ] Product with status 1/2/3 only (no availtoday_status_id)
- [ ] Product with status 4 only (date today)
- [ ] Product with status 4 only (no date today)

## Technical Details

**API Call:**
```javascript
GET /frontend/pages/products/get-sdo-quantity.php?product_id=123
```

**Response:**
```json
{
  "success": true,
  "quantity": 50,
  "date": "2024-10-25",
  "status_id": 2,
  "availtoday_status_id": 2,
  "source": "quantity_per_day_sdo (regular product with same-day)",
  "has_date_today": true
}
```

**Availability Check:**
```javascript
sameDayAvailable = has_date_today && quantity > 0
```

## Notes

- The check is performed asynchronously when opening the quantity modal
- If the API call fails, it defaults to showing pre-order only (safe fallback)
- The check happens in real-time, ensuring accurate availability
- Works seamlessly with existing cart and checkout systems
