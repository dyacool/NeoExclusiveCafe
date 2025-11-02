# Implementation Plan

- [x] 1. Implement order type determination logic


  - Add `determineOrderType()` function to `backend/pages/admin-includes/mailer.php`
  - Implement date comparison logic to classify orders as "Sameday Order" or "Pre-Order"
  - Handle edge cases (missing dates, invalid formats) with appropriate fallbacks
  - Add error logging for debugging
  - _Requirements: 1.3, 1.4_



- [ ] 2. Update email subject line generation
  - Modify `sendOrderNotificationEmail()` function to call `determineOrderType()`
  - Update subject line format to include order type: "{OrderType} Notification - Order #{order_id}"



  - Ensure order ID is included in subject for reference
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 3. Update email body template
  - Modify `createOrderEmailBody()` function signature to accept `$orderType` parameter
  - Add default parameter value for backward compatibility
  - Replace hardcoded "New Order Notification" with dynamic `$orderType . " Notification"`
  - Update `sendOrderNotificationEmail()` to pass order type to `createOrderEmailBody()`
  - _Requirements: 1.1, 1.2, 1.4_

- [ ]* 4. Test order type determination
  - Test same-day pickup order classification
  - Test same-day delivery order classification
  - Test future-date pickup order classification
  - Test future-date delivery order classification
  - Test edge cases (missing dates, invalid formats)
  - _Requirements: 1.3, 3.1, 3.2, 3.3, 3.4_

- [ ]* 5. Test email generation and delivery
  - Verify email subject includes correct order type for same-day orders
  - Verify email subject includes correct order type for pre-orders
  - Verify email body displays correct notification title for same-day orders
  - Verify email body displays correct notification title for pre-orders
  - Verify all other email content remains unchanged
  - Test email rendering in different email clients (Gmail, Outlook)
  - _Requirements: 1.1, 1.2, 2.1, 2.2_
