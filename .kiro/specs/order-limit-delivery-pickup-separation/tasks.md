# Implementation Plan

- [x] 1. Update Date Availability API to support fulfillment method filtering



  - Add fulfillment_method parameter handling (default to 'delivery' if not provided)
  - Modify SQL query to count only delivery orders when fulfillment_method is 'delivery'
  - Return appropriate response structure based on fulfillment method (null order limits for pickup)
  - Add parameter validation and error handling


  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2_



- [ ] 2. Update order processing validation logic
  - [ ] 2.1 Add conditional validation based on delivery_method in process_order.php
    - Extract delivery_method from POST data

    - Implement delivery-specific validation (check order_limits + date_limits)
    - Implement pickup-specific validation (check only date_limits)
    - _Requirements: 1.1, 1.2, 1.5, 2.1, 5.1, 5.2_
  

  - [ ] 2.2 Update order counting query for delivery orders
    - Modify SQL to filter by delivery_method = 'Delivery'
    - Ensure only active orders are counted (exclude Completed, Delivered, Picked-up, Cancelled)


    - _Requirements: 1.3, 5.1_


  
  - [ ] 2.3 Update error messages to be specific to validation type
    - Add delivery capacity error message

    - Add date blocking error message
    - Log rejection reasons for debugging
    - _Requirements: 1.4, 5.3, 5.4_

- [x] 3. Update frontend checkout calendar integration

  - [ ] 3.1 Add fulfillment method parameter to API calls
    - Detect current fulfillment method (pickup vs delivery radio selection)
    - Pass fulfillment_method parameter to get-date-availability.php
    - _Requirements: 4.1, 4.2, 4.5_
  
  - [ ] 3.2 Implement calendar refresh on fulfillment method change
    - Add event listeners to pickup and delivery radio buttons
    - Trigger fetchDateLimits when method changes
    - Update calendar display with new availability data
    - _Requirements: 4.5_
  
  - [ ] 3.3 Conditionally display order limit information
    - Show order limit info and remaining slots only for delivery method
    - Hide order limit info for pickup method
    - Update calendar day tooltips/messages based on method
    - _Requirements: 4.3, 4.4_

- [ ] 4. Testing and validation
  - [ ] 4.1 Test delivery order validation
    - Test delivery order rejected when limit reached
    - Test delivery order rejected when date blocked
    - Test delivery order accepted when slots available
    - _Requirements: 1.1, 1.4, 2.1, 5.1_
  
  - [ ] 4.2 Test pickup order validation
    - Test pickup order accepted when delivery limit reached (should ignore limit)
    - Test pickup order rejected when date blocked
    - Test pickup order accepted on available dates
    - _Requirements: 1.2, 1.5, 2.1, 2.2, 5.2_
  
  - [ ] 4.3 Test calendar display behavior
    - Verify calendar shows different availability for delivery vs pickup
    - Verify switching methods updates calendar correctly
    - Verify order limit info only shows for delivery
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_
  
  - [ ] 4.4 Test API endpoint responses
    - Test API with fulfillment_method=delivery returns order limits
    - Test API with fulfillment_method=pickup excludes order limits
    - Test API handles missing parameter gracefully
    - _Requirements: 1.1, 1.2, 5.1, 5.2_
