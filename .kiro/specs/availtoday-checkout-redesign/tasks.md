# Implementation Plan

- [x] 1. Backup and prepare availtoday-checkout.php


  - Create backup of current availtoday-checkout.php
  - Document current functionality that must be preserved
  - Identify same-day specific logic to keep
  - _Requirements: All_





- [ ] 2. Copy HTML structure from checkout.php to availtoday-checkout.php
- [ ] 2.1 Copy the complete HTML head section
  - Copy all CSS links and style tags
  - Copy all JavaScript library imports (flatpickr, etc.)
  - Update page title to "Same-Day Checkout"
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 2.2 Copy User Information section
  - Copy the section-card user-information div
  - Include "Load Contacts" button
  - Include name, email, contact number fields
  - Ensure saved-info integration is included
  - _Requirements: 1.2, 1.3, 2.1, 2.2, 2.3_

- [ ] 2.3 Copy Shipping Options section WITHOUT calendar
  - Copy section-card shipping-details div
  - Copy pickup/delivery radio buttons
  - Copy pickup details section (NO calendar)
  - Copy delivery details section with "Set Location" button
  - Add "Same-Day Order" notice where calendar would be
  - Copy time selection inputs
  - _Requirements: 1.2, 1.3, 5.1, 5.2, 5.3, 5.4_

- [ ] 2.4 Copy Order Summary section
  - Copy section-card order-summary div
  - Include coupon code input and apply button
  - Include applied coupon display
  - Include cart items list
  - Include subtotal, shipping fee, discount, total rows
  - _Requirements: 1.2, 1.3, 3.1, 3.2, 3.3, 3.4_

- [ ] 2.5 Copy Payment Method section
  - Copy section-card payment-method div
  - Include GCash radio button
  - Include "Place Order" button with loading state
  - _Requirements: 1.2, 1.3, 4.1, 4.2_

- [ ] 3. Copy and adapt JavaScript functionality
- [ ] 3.1 Copy saved customer info integration
  - Copy SavedInfoManager initialization
  - Copy loadEntries() call
  - Copy loadPrimaryCustomerAddress() function
  - Copy updatePrimaryWithLocation() function
  - Ensure "Load Contacts" button functionality works
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ] 3.2 Copy shipping method toggle logic
  - Copy pickupRadio and deliveryRadio event listeners
  - Copy updateVisibility() function
  - Copy shipping fee calculation logic
  - Copy updateTotalAmount() function
  - _Requirements: 1.3_

- [ ] 3.3 Copy coupon system JavaScript
  - Copy applyCoupon() function
  - Copy removeCoupon() function


  - Copy calculateDiscount() function
  - Copy showCouponMessage() function
  - Copy showAppliedCoupon() and hideAppliedCoupon() functions
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [ ] 3.4 Implement automatic date setting for same-day
  - Create function to set today's date automatically
  - Set pickup_date hidden input to today
  - Set delivery_date hidden input to today
  - Remove any calendar initialization code
  - Add console logs for debugging
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 3.6 Implement dynamic minimum time (current time + 2 hours)
  - Create calculateMinimumTime() function that adds 2 hours to current time
  - Set min attribute on pickup_time input to calculated minimum time
  - Set min attribute on delivery_time input to calculated minimum time
  - Set default value of time inputs to minimum time
  - Add validation on time input change to prevent selecting earlier times
  - Display minimum time to user in same-day notice section
  - Implement setInterval to update minimum time every minute if page stays open
  - Show alert if user tries to select time earlier than minimum
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 3.7 Implement business hours check for delivery availability
  - Fetch business hours (opening_time, closing_time) from business_hours table
  - Pass closing_time to JavaScript
  - Create isDeliveryAvailable() function to compare minimum time with closing time
  - Disable delivery radio button if minimum time exceeds closing time
  - Automatically select pickup when delivery is disabled
  - Display "Delivery unavailable - too close to closing time" message
  - Add CSS styling for disabled delivery option
  - Hide delivery details section when delivery is disabled
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 3.5 Copy form validation logic
  - Copy checkValidity() checks
  - Copy reportValidity() calls
  - Copy required field validation
  - Copy delivery address validation for delivery orders
  - _Requirements: 8.1, 8.2, 8.3_

- [ ] 4. Implement PayMongo payment integration
- [ ] 4.1 Copy form submission handler
  - Copy checkout form submit event listener
  - Copy preventDefault() logic
  - Copy form validation before submission
  - Copy loading state management (setLoadingState function)
  - _Requirements: 4.1, 4.2, 4.3, 7.1, 8.4_

- [ ] 4.2 Prepare order data for PayMongo
  - Copy order data preparation logic
  - Set order_type to 'availtoday' instead of 'regular'
  - Include cart_items, cart_total, user info
  - Include delivery/pickup information with today's date
  - Include coupon information if applied
  - _Requirements: 4.3, 7.4_

- [ ] 4.3 Submit payment to process-payment.php
  - Copy fetch() call to process-payment.php
  - Send payment_method, order_type, amount, order_data
  - Handle response with payment_url
  - Redirect to PayMongo payment page
  - Handle errors and show user-friendly messages
  - _Requirements: 4.3, 4.4, 7.1, 7.2_

- [ ] 5. Update process-payment.php for availtoday orders
  - Verify it accepts order_type parameter
  - Ensure it stores type in session for payment-return.php
  - Test that it creates PayMongo payment intent correctly
  - Verify redirect URL includes type=availtoday parameter
  - _Requirements: 4.3, 7.1, 7.2, 7.3_

- [ ] 6. Update payment-return.php for availtoday orders
  - Verify it reads type parameter from URL
  - Ensure it retrieves correct order data from session
  - Verify it creates order with today's date for availtoday type
  - Ensure it checks same-day order limits
  - Verify it sends email notification
  - Test redirect to payment-success.php?type=availtoday
  - _Requirements: 4.5, 6.1, 6.2, 7.2, 7.3_

- [ ] 7. Update payment-success.php for unified display
  - Read type parameter from URL
  - Display "Available Today" when type=availtoday
  - Display "Pre-Order" when type=regular
  - Ensure order details display correctly for both types
  - Show appropriate messaging for same-day vs pre-order
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 8. Copy CSS styling
  - Copy all checkout.css styles
  - Copy saved-info.css styles
  - Copy any inline styles from checkout.php
  - Ensure responsive design works
  - Test on mobile devices
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 9. Add same-day specific UI elements
  - Add "Same-Day Order" badge/notice
  - Display today's date prominently
  - Add messaging that order is for today only
  - Style same-day notice to stand out
  - _Requirements: 5.3_

- [ ]* 10. Testing and validation
  - Test with user who has saved customer info
  - Test with user who has no saved info
  - Test pickup order flow with PayMongo
  - Test delivery order flow with PayMongo
  - Test coupon application (percentage, fixed, free shipping)
  - Test form validation (missing fields)
  - Test payment success flow
  - Test payment failure/cancellation flow
  - Verify email notifications are sent
  - Verify orders appear in admin panel
  - Test on mobile devices
  - Test in different browsers
  - _Requirements: All_

