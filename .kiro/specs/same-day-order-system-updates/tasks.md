# Implementation Plan

- [x] 1. Update same-day order validation to separate delivery and pickup limits



  - Modify `process-availtoday-checkout.php` to check fulfillment method before applying order limits
  - Replace `order_limits` table queries with `availtoday_order_limit` table queries for same-day delivery orders
  - Remove order count limit checks for same-day pickup orders (keep unlimited)
  - Ensure `date_limits` table checks apply to both delivery and pickup methods
  - Add appropriate error messages for each validation scenario


  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.5_

- [ ] 2. Update product dashboard business hours logic
  - Modify `product-dashboard.php` to filter product visibility based on product type and business hours
  - Update JavaScript business hours check to hide only same-day products (status_id = 4) when closed
  - Ensure regular pre-order products (status_id = 1, 2, 3) remain visible 24/7



  - Remove any logic that triggers complete store closure based on business hours
  - Update UI messages to reflect that only same-day products are affected by business hours
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [ ] 3. Verify system separation and data integrity
  - Confirm `process-availtoday-checkout.php` uses only `availtoday_order_limit` table for same-day orders
  - Confirm regular checkout process (`process_order.php`) uses only `order_limits` table
  - Verify no cross-contamination between same-day and regular order validation logic
  - Test that date limits work correctly for both delivery and pickup same-day orders
  - _Requirements: 3.5, 4.3, 4.4_

- [ ]* 4. Test order limit scenarios
  - Test same-day delivery orders hitting the `availtoday_order_limit`
  - Test same-day pickup orders with no count limits
  - Test admin date blocks affecting both delivery and pickup
  - Test mixed scenarios with both delivery and pickup orders
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.1, 3.2, 3.3_

- [ ]* 5. Test business hours product visibility
  - Test same-day product visibility during business hours
  - Test same-day product hiding after business hours
  - Test regular pre-order product visibility 24/7
  - Test product dashboard UI states (open, closed, loading)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_
