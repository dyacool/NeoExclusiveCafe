# Design Document

## Overview

This design modifies the order limit system to differentiate between delivery and pick-up orders. The key change is that `order_limits` (daily delivery capacity) will only apply to delivery orders, while `date_limits` (admin-blocked dates) will apply to both order types. Additionally, all availToday order limit functionality will be removed from the system.

## Architecture

### Current System
- `order_limits` table: Stores daily order limit (applies to all orders)
- `date_limits` table: Stores date-specific blocks and limits
- `get-date-availability.php`: API that returns date availability for checkout calendar
- `process_order.php`: Validates and processes regular orders
- `process-availtoday-checkout.php`: Validates and processes same-day orders
- `checkout.php`: Frontend checkout page with calendar

### Modified System (Phase 1: Regular Checkout)
- `order_limits` table: Will only apply to delivery orders
- `date_limits` table: Continues to apply to both delivery and pick-up orders
- `get-date-availability.php`: Will accept fulfillment method parameter to return appropriate availability
- `process_order.php`: Will check order_limits only for delivery orders
- `checkout.php`: Calendar will show different availability based on fulfillment method

**Note:** AvailToday checkout (`process-availtoday-checkout.php`) will be addressed in a later phase.

## Components and Interfaces

### 1. Backend API: get-date-availability.php

**Current Behavior:**
- Returns date availability based on order_limits and date_limits for all orders
- Counts all orders (delivery + pick-up) against the limit

**New Behavior:**
- Accept `fulfillment_method` parameter (`delivery` or `pickup`)
- For `delivery`: Check both order_limits (counting only delivery orders) and date_limits
- For `pickup`: Check only date_limits, ignore order_limits entirely
- Return appropriate availability status based on fulfillment method

**API Interface:**
```php
GET /backend/pages/calendar/get-date-availability.php
Parameters:
  - start_date: string (YYYY-MM-DD)
  - end_date: string (YYYY-MM-DD)
  - fulfillment_method: string ('delivery' | 'pickup') [NEW]

Response:
{
  "success": true,
  "default_limit": 10,  // Only relevant for delivery
  "dates": {
    "2025-11-01": {
      "date": "2025-11-01",
      "limit": 10,  // Only for delivery, null for pickup
      "current_orders": 5,  // Only delivery orders for delivery method
      "remaining_slots": 5,  // Only for delivery
      "status": "available" | "full" | "disabled",
      "is_available": true,
      "message": "5 slots remaining" | "Not accepting orders"
    }
  }
}
```

### 2. Order Processing: process_order.php

**Current Behavior:**
- Validates all orders against order_limits
- Counts all active orders (delivery + pick-up)

**New Behavior:**
- Check `delivery_method` from POST data
- If `delivery_method === 'delivery'`:
  - Validate against order_limits (count only delivery orders)
  - Validate against date_limits
- If `delivery_method === 'pickup'`:
  - Skip order_limits validation entirely
  - Validate only against date_limits

**Validation Logic:**
```php
// For delivery orders
if ($delivery_method === 'delivery') {
    // Check order_limits - count only delivery orders
    $limit_query = "SELECT ... 
        FROM orders o 
        WHERE o.delivery_date = ? 
        AND o.delivery_method = 'Delivery'
        AND o.status NOT IN ('Completed', 'Delivered', 'Picked-up', 'Cancelled')";
    
    // Check date_limits
    $date_query = "SELECT ... FROM date_limits WHERE date = ?";
}

// For pick-up orders
if ($delivery_method === 'pickup') {
    // Skip order_limits check entirely
    
    // Check only date_limits
    $date_query = "SELECT ... FROM date_limits WHERE date = ?";
}
```

### 3. AvailToday Processing: process-availtoday-checkout.php

**Status:** Deferred to Phase 2

This component will be updated in a future phase after the regular checkout is working correctly.

### 4. Frontend Calendar: checkout.php

**Current Behavior:**
- Fetches date availability without specifying fulfillment method
- Shows same availability for both delivery and pick-up

**New Behavior:**
- Pass current fulfillment method to API when fetching availability
- Update calendar when user switches between delivery and pick-up
- Show order limit information only for delivery method
- For pick-up, show only date blocks (no order limit info)

**JavaScript Changes:**
```javascript
function fetchDateLimits(start, end) {
    // Get current fulfillment method
    const isDelivery = deliveryRadio.checked;
    const fulfillmentMethod = isDelivery ? 'delivery' : 'pickup';
    
    // Add fulfillment_method parameter to API call
    fetch(`...get-date-availability.php?start_date=${startStr}&end_date=${endStr}&fulfillment_method=${fulfillmentMethod}`)
        .then(...)
}

// Re-fetch when fulfillment method changes
deliveryRadio.addEventListener('change', () => {
    updateVisibility();
    // Re-fetch date limits with new fulfillment method
    if (pickupCalendar) {
        pickupCalendar.fetchCurrentMonthLimits();
    }
});

pickupRadio.addEventListener('change', () => {
    updateVisibility();
    // Re-fetch date limits with new fulfillment method
    if (pickupCalendar) {
        pickupCalendar.fetchCurrentMonthLimits();
    }
});
```

## Data Models

### Database Tables (No Changes Required)

**order_limits Table:**
```sql
CREATE TABLE order_limits (
    id INT PRIMARY KEY,
    default_limit INT NOT NULL
);
```
- Interpretation changes: Now represents daily delivery order limit only

**date_limits Table:**
```sql
CREATE TABLE date_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL UNIQUE,
    limit_value INT,
    not_accepting_orders BOOLEAN DEFAULT FALSE
);
```
- No changes: Continues to apply to both delivery and pick-up

**orders Table:**
```sql
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_method ENUM('Delivery', 'Pick-up'),
    delivery_date DATE,
    pickup_date DATE,
    status VARCHAR(50),
    ...
);
```
- No changes: Used to count orders by delivery_method

## Error Handling

### Delivery Order Validation Errors

**Order Limit Reached:**
```
Error: "Sorry, we have reached the delivery order limit for this date. Please choose another date."
```

**Date Blocked:**
```
Error: "Sorry, we are not accepting orders for this date."
```

### Pick-up Order Validation Errors

**Date Blocked (Only Error for Pick-up):**
```
Error: "Sorry, we are not accepting orders for this date."
```

### API Error Responses

**Invalid Fulfillment Method:**
```json
{
  "success": false,
  "error": "Invalid fulfillment method. Must be 'delivery' or 'pickup'."
}
```

## Testing Strategy

### Unit Testing Focus Areas

1. **API Endpoint Testing (get-date-availability.php)**
   - Test with `fulfillment_method=delivery`: Should return order limits and counts
   - Test with `fulfillment_method=pickup`: Should return only date blocks, no order limits
   - Test without fulfillment_method parameter: Should default to delivery behavior
   - Test date blocks apply to both methods

2. **Order Processing Testing (process_order.php)**
   - Test delivery order with limit reached: Should reject
   - Test delivery order with date blocked: Should reject
   - Test pick-up order with limit reached: Should accept (ignore limit)
   - Test pick-up order with date blocked: Should reject
   - Test pick-up order on date with no blocks: Should accept

3. **AvailToday Processing Testing (process-availtoday-checkout.php)**
   - Test same-day order with date blocked: Should reject
   - Test same-day order with no blocks: Should accept
   - Verify order_limits are not checked

### Integration Testing

1. **Calendar Display Testing**
   - Switch from delivery to pick-up: Calendar should update to show more available dates
   - Switch from pick-up to delivery: Calendar should update to show fewer available dates (if limits apply)
   - Verify order limit info only shows for delivery

2. **End-to-End Order Flow**
   - Place delivery order on date at limit: Should be rejected
   - Place pick-up order on same date: Should succeed
   - Place any order on blocked date: Should be rejected

### Manual Testing Checklist

- [ ] Admin blocks a date → Both delivery and pick-up orders rejected
- [ ] Delivery limit reached → Delivery orders rejected, pick-up orders accepted
- [ ] Switch fulfillment method in checkout → Calendar updates correctly
- [ ] Order limit info only visible for delivery method
- [ ] AvailToday orders ignore order_limits
- [ ] Error messages are clear and specific

## Implementation Notes

### Files to Modify (Phase 1)

1. **backend/pages/calendar/get-date-availability.php**
   - Add fulfillment_method parameter handling
   - Modify query to count only delivery orders when method is 'delivery'
   - Return null/empty for order limit fields when method is 'pickup'

2. **frontend/pages/cart/process_order.php**
   - Add conditional logic based on delivery_method
   - Modify order counting query to filter by delivery_method
   - Update error messages

3. **frontend/pages/cart/checkout.php**
   - Add fulfillment_method parameter to API calls
   - Add event listeners to re-fetch on method change
   - Conditionally display order limit information
   - Update calendar rendering logic

### Files to Remove/Clean Up

- No files to delete in Phase 1
- process-availtoday-checkout.php will be addressed in Phase 2

### Backward Compatibility

- Existing orders are not affected
- Admin calendar management remains unchanged
- Database schema remains unchanged
- Only business logic changes

### Performance Considerations

- Additional API calls when switching fulfillment method (minimal impact)
- More efficient queries (filtering by delivery_method reduces result set)
- No additional database indexes required

## Design Decisions and Rationales

### Decision 1: Use fulfillment_method parameter instead of separate endpoints
**Rationale:** Single endpoint is easier to maintain and allows for future expansion (e.g., curbside pickup)

### Decision 2: Default to delivery behavior if parameter is missing
**Rationale:** Safer to be more restrictive (apply limits) than less restrictive if parameter is accidentally omitted

### Decision 3: Keep date_limits applying to both methods
**Rationale:** Admin needs ability to block all orders on specific dates (holidays, closures, etc.)

### Decision 4: Remove availToday order limits entirely
**Rationale:** Simplifies the system and aligns with the new approach where pick-up orders are not limited by daily capacity

### Decision 5: No database schema changes
**Rationale:** Existing tables support the new logic; only interpretation changes, reducing migration risk
