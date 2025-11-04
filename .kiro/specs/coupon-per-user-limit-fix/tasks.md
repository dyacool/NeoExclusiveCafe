# Implementation Plan

- [x] 1. Create coupon usage tracking table



  - Add `createCouponUsageTable()` function to `database-config.php`
  - Include table creation SQL with proper indexes and foreign keys
  - Call the function in existing initialization flow




  - _Requirements: 1.5, 2.1, 2.4, 3.5_

- [ ] 2. Implement per-user usage checking in validation
  - [x] 2.1 Add `checkPerUserUsage()` function to `validate-coupon.php`


    - Write function to query coupon_usage table for user's usage count
    - Return allowed/denied status with appropriate message
    - Handle NULL or 0 per-user limit as unlimited
    - _Requirements: 1.1, 1.3_





  - [x] 2.2 Integrate per-user check into validation flow


    - Add session check to verify user is logged in
    - Call `checkPerUserUsage()` after global usage limit check


    - Return rejection message if per-user limit exceeded
    - Handle guest users attempting to use per-user limited coupons
    - _Requirements: 1.2, 4.1, 4.4_




  - [ ] 2.3 Add single coupon enforcement to validation
    - Check if a coupon is already applied in session
    - Reject new coupon if different coupon already applied
    - Allow re-validation of same coupon code
    - Return appropriate error message
    - _Requirements: 3.5_





- [ ] 3. Implement coupon usage recording
  - [ ] 3.1 Add `recordCouponUsage()` function to checkout files
    - Write function to insert usage record into coupon_usage table






    - Use ON DUPLICATE KEY UPDATE to handle race conditions
    - Wrap in try-catch for non-blocking error handling
    - _Requirements: 1.4, 2.3, 2.4, 3.3_



  - [ ] 3.2 Integrate usage recording in regular checkout
    - Modify `process-checkout.php` to call `recordCouponUsage()` after order creation
    - Extract coupon data from session
    - Update global used_count in promotions table
    - _Requirements: 1.4, 2.3_





  - [x] 3.3 Integrate usage recording in availtoday checkout



    - Modify `process-availtoday-checkout.php` to call `recordCouponUsage()` after order creation
    - Extract coupon data from session
    - Update global used_count in promotions table
    - _Requirements: 1.4, 2.3_



- [ ] 4. Implement single coupon application UI
  - [ ] 4.1 Create coupon removal endpoint
    - Create `remove-coupon.php` to handle coupon removal from session
    - Clear applied_coupon from session
    - Return JSON success response
    - _Requirements: 3.3_

  - [x] 4.2 Update checkout page HTML structure


    - Modify `checkout.php` to add coupon input wrapper
    - Add applied coupon display section (hidden by default)
    - Add chat bubble tooltip element (hidden by default)
    - Add remove coupon button
    - _Requirements: 3.1, 3.2, 3.4_

  - [ ] 4.3 Update availtoday checkout page HTML structure
    - Modify `availtoday-checkout.php` to add coupon input wrapper
    - Add applied coupon display section (hidden by default)
    - Add chat bubble tooltip element (hidden by default)
    - Add remove coupon button


    - _Requirements: 3.1, 3.2, 3.4_

  - [ ] 4.4 Implement JavaScript coupon management logic
    - Add `disableCouponInput()` function to disable input and apply button
    - Add `enableCouponInput()` function to re-enable input and clear value
    - Add `showCouponTooltip()` function to display chat bubble on click
    - Add `showAppliedCoupon()` function to display applied coupon info
    - Add `hideAppliedCoupon()` function to hide applied coupon display
    - Add `removeCoupon()` function to call remove endpoint and update UI
    - Update `applyCoupon()` to call disable/show functions on success
    - Add click event listener to disabled input for tooltip
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [ ] 4.5 Add CSS styling for single coupon UI
    - Add disabled input state styling (gray background, cursor not-allowed)
    - Add applied coupon display styling (green background, flex layout)
    - Add remove button styling (red background, hover effect)
    - Add chat bubble tooltip styling with arrow pointer
    - Add fadeInOut animation for tooltip
    - _Requirements: 3.4, 3.6_

- [ ] 5. Verify and test the implementation
  - [ ] 5.1 Test database table creation
    - Verify table is created with correct schema
    - Check indexes and foreign keys are properly set
    - _Requirements: 1.5, 2.1_

  - [ ] 5.2 Test per-user limit enforcement
    - Test coupon with limit=1 can only be used once per user
    - Test different users can use same coupon
    - Test unlimited per-user limit (NULL/0) allows multiple uses
    - Test guest user rejection for per-user limited coupons
    - _Requirements: 1.1, 1.2, 1.3, 4.1_

  - [ ] 5.3 Test usage recording
    - Verify usage records are created on order completion
    - Check duplicate prevention works correctly
    - Verify global used_count increments properly
    - _Requirements: 1.4, 2.3, 2.4_

  - [ ] 5.4 Test single coupon application UI
    - Test input field disables when coupon is applied
    - Test chat bubble appears when clicking disabled field
    - Test remove button clears coupon and re-enables input
    - Test validation rejects second coupon when one is applied
    - Test visual styling for disabled state and applied coupon display
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_
