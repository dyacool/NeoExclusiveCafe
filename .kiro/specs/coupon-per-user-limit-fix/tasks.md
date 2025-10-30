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





  - [ ] 2.2 Integrate per-user check into validation flow
    - Add session check to verify user is logged in
    - Call `checkPerUserUsage()` after global usage limit check


    - Return rejection message if per-user limit exceeded
    - Handle guest users attempting to use per-user limited coupons
    - _Requirements: 1.2, 3.1, 3.4_



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


  - [ ] 3.3 Integrate usage recording in availtoday checkout
    - Modify `process-availtoday-checkout.php` to call `recordCouponUsage()` after order creation
    - Extract coupon data from session
    - Update global used_count in promotions table
    - _Requirements: 1.4, 2.3_

- [ ] 4. Verify and test the implementation
  - [ ] 4.1 Test database table creation
    - Verify table is created with correct schema
    - Check indexes and foreign keys are properly set
    - _Requirements: 1.5, 2.1_

  - [ ] 4.2 Test per-user limit enforcement
    - Test coupon with limit=1 can only be used once per user
    - Test different users can use same coupon
    - Test unlimited per-user limit (NULL/0) allows multiple uses
    - Test guest user rejection for per-user limited coupons
    - _Requirements: 1.1, 1.2, 1.3, 3.1_

  - [ ] 4.3 Test usage recording
    - Verify usage records are created on order completion
    - Check duplicate prevention works correctly
    - Verify global used_count increments properly
    - _Requirements: 1.4, 2.3, 2.4_
