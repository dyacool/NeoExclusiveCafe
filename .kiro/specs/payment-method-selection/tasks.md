# Implementation Plan

- [x] 1. Add payment method selection UI to checkout.php


  - Add payment options section with radio buttons for GCash, Maya, and Bank Transfer after shipping details section
  - Use existing `.section-card` and `.radio-option` CSS classes for consistent styling
  - Set `required` attribute on radio buttons for HTML5 validation
  - _Requirements: 1.1, 1.2, 1.3, 1.4_


- [ ] 2. Add payment method selection UI to availtoday-checkout.php
  - Add identical payment options section with radio buttons for GCash, Maya, and Bank Transfer
  - Use same HTML structure and CSS classes as checkout.php
  - Ensure consistent placement after shipping details section



  - _Requirements: 1.5, 2.1, 2.2, 2.3_

- [ ] 3. Verify form submission integration
  - Confirm existing JavaScript form handler correctly reads payment_method value
  - Verify payment_method is included in form data sent to process-payment.php
  - Test validation error displays when no payment method is selected
  - _Requirements: 1.3, 1.4, 2.4, 2.5, 2.6, 3.1_

- [ ]* 4. Test payment flows in sandbox environment
  - Test GCash payment flow on both checkout pages
  - Test Maya payment flow on both checkout pages
  - Test Bank Transfer/Card payment flow on both checkout pages
  - Verify PayMongo redirects work correctly for all payment types
  - _Requirements: 2.4, 2.5, 2.6, 3.2, 3.3, 3.4, 3.5, 3.6_
