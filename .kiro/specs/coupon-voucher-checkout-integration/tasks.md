# Implementation Plan

- [x] 1. Add coupon UI components to availtoday checkout page


  - Add coupon input section HTML with input field, "Check Coupon" button, message display area, and applied coupon display section
  - Add discount row to order summary section that shows/hides based on coupon application
  - Copy and adapt coupon-related CSS styles from checkout.php (lines 990-1126) including .coupon-section, .coupon-input-group, .btn-check-coupon, .coupon-message, .coupon-applied classes
  - Ensure responsive design styles are included for mobile devices
  - _Requirements: 1.1, 4.2_

- [x] 2. Implement JavaScript coupon validation functionality

- [x] 2.1 Add global variables and helper functions


  - Declare appliedCoupon, discountAmount, and subtotal variables in JavaScript scope
  - Implement showCouponMessage() function to display success/error messages with auto-hide after 5 seconds
  - Implement showAppliedCoupon() function to display applied coupon details with code and discount amount
  - Implement hideAppliedCoupon() function to hide the applied coupon display
  - Implement updateOrderTotal() function to recalculate total with discount and shipping
  - _Requirements: 1.3, 1.4, 2.1, 2.2, 2.3_

- [x] 2.2 Implement checkCoupon() async function


  - Create async function that gets coupon code from input field and converts to uppercase
  - Add input validation to check for empty coupon code
  - Implement button disable/loading state during API request
  - Make AJAX POST request to validate-coupon.php with coupon_code, subtotal, and cart_items
  - Handle successful response by storing coupon data, showing applied coupon UI, updating totals, and displaying discount row
  - Handle error response by showing appropriate error message
  - Implement try-catch for network errors with user-friendly error message
  - Reset button state in finally block
  - _Requirements: 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 4.1_

- [x] 2.3 Implement removeCoupon() function

  - Clear appliedCoupon and discountAmount variables
  - Hide applied coupon display and discount row
  - Recalculate order total to original amount
  - Show success message for coupon removal
  - _Requirements: 1.5_

- [x] 3. Update form submission to include coupon data


  - Modify form submission handler to check if appliedCoupon exists
  - Create hidden input field for applied_coupon with JSON stringified coupon object
  - Create hidden input field for discount_amount with numeric value
  - Append hidden inputs to form before submission
  - _Requirements: 3.1, 3.2_

- [x] 4. Update backend order processing to handle coupon data

- [x] 4.1 Receive and validate coupon data


  - Add code after cart_items decoding to check for applied_coupon in POST data
  - Decode applied_coupon JSON and extract discount_amount
  - Add error logging for coupon data received
  - Initialize discount_amount to 0 if no coupon applied
  - _Requirements: 3.1, 3.2_

- [x] 4.2 Apply discount to order total calculation


  - Calculate final_total by subtracting discount_amount from cart_total
  - Add validation to ensure final_total is not negative (set to 0 if negative)
  - Update order SQL to use final_total instead of cart_total
  - _Requirements: 3.5_

- [x] 4.3 Store coupon information in order notes


  - Modify combined_notes variable to append coupon information if applied_coupon exists
  - Format coupon info string with code and discount amount
  - Ensure notes field in order SQL uses the updated combined_notes
  - _Requirements: 3.3, 3.4_

- [x] 4.4 Implement coupon usage tracking


  - Add code after successful order creation to update promotions table
  - Prepare UPDATE SQL statement to increment used_count for the coupon
  - Execute update with coupon ID from applied_coupon data
  - Add error logging for failed coupon updates (non-blocking - order should still succeed)
  - Add success logging when coupon usage count is updated
  - Close prepared statement after execution
  - _Requirements: 5.1, 5.5_

- [x] 5. Test coupon integration functionality






  - Test valid coupon application shows success message and updates total
  - Test invalid coupon shows appropriate error message
  - Test coupon with minimum purchase requirement validation
  - Test coupon removal restores original total
  - Test order submission includes coupon data in database
  - Test coupon usage count increments after order completion
  - Test coupon at usage limit is rejected
  - Test free shipping coupon applies correctly
  - Test UI responsiveness on mobile devices
  - Test AJAX requests work without page refresh
  - _Requirements: All_
