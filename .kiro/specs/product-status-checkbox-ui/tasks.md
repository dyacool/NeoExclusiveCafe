# Implementation Plan

- [x] 1. Update modal HTML structure with checkbox interface


  - Replace the single "Shipping Method" dropdown with two checkboxes ("Pre-order" and "Same-day order")
  - Add conditional dropdown container for pre-order shipping methods (Pick Up, Delivery, Delivery or Pick Up)
  - Add conditional dropdown container for same-day order shipping methods (Pick Up, Delivery, Delivery and Pick Up)
  - Ensure proper HTML structure with labels and IDs for JavaScript integration
  - _Requirements: 1.1, 2.1, 2.2, 3.1, 3.2_





- [ ] 2. Implement checkbox event handlers in JavaScript
  - [ ] 2.1 Create `handlePreOrderCheckboxChange()` function to show/hide pre-order dropdown
    - Show pre-order dropdown when checkbox is checked

    - Hide pre-order dropdown when checkbox is unchecked
    - Set default dropdown value to "Pick Up" (status_id 1) when first checked
    - _Requirements: 2.1, 2.2_
  
  - [ ] 2.2 Create `handleSameDayCheckboxChange()` function to show/hide same-day dropdown and calendar
    - Show same-day dropdown and calendar container when checkbox is checked

    - Hide same-day dropdown and calendar container when checkbox is unchecked
    - Set default dropdown value to "Pick Up" (availtoday_status_id 1) when first checked
    - Initialize calendar component when checkbox is checked
    - _Requirements: 3.1, 3.2, 4.1, 4.2_



  
  - [ ] 2.3 Create `updateQuantityFieldState()` function to manage quantity field state
    - Disable quantity field when only same-day checkbox is checked (not pre-order)
    - Disable quantity field when product is marked as unavailable

    - Enable quantity field when pre-order checkbox is checked (and not unavailable)
    - _Requirements: 5.1, 5.2, 5.3_

- [x] 3. Update `openEditModal()` function to initialize checkbox states

  - [ ] 3.1 Add logic to determine checkbox states from product data
    - Check pre-order checkbox if status_id is 1, 2, or 3
    - Check same-day checkbox if status_id is 4 OR availtoday_status_id is not null
    - _Requirements: 1.2, 1.3_
  

  - [ ] 3.2 Set pre-order dropdown value and visibility
    - Show pre-order dropdown if pre-order checkbox is checked
    - Set dropdown value to current status_id (1, 2, or 3)



    - _Requirements: 2.3_
  
  - [ ] 3.3 Set same-day dropdown value and visibility
    - Show same-day dropdown if same-day checkbox is checked
    - Set dropdown value to current availtoday_status_id
    - Show calendar container if same-day checkbox is checked
    - _Requirements: 3.3, 4.3, 4.4_
  
  - [ ] 3.4 Initialize calendar with existing dates
    - Load todays_product_dates if status_id is 4



    - Load regular_today_dates if status_id is 1, 2, or 3 with availtoday_status_id set
    - _Requirements: 4.3, 4.4_

- [ ] 4. Implement form validation logic
  - [x] 4.1 Create `validateCheckboxSelection()` function

    - Check if at least one checkbox is selected
    - Display alert if neither checkbox is checked
    - Return false to prevent form submission
    - _Requirements: 5.4_
  

  - [ ]* 4.2 Add same-day date validation
    - Check if dates are selected when same-day checkbox is checked
    - Display error message if no dates selected
    - Highlight calendar component



    - _Requirements: 4.1, 4.2_

- [ ] 5. Update form submission logic
  - [ ] 5.1 Modify `handleFormSubmit()` function to process checkbox states
    - Call validation function before processing

    - Determine status_id based on checkbox states
    - Determine availtoday_status_id based on checkbox states
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  




  - [ ] 5.2 Implement status mapping logic
    - If only pre-order checked: status_id = pre-order dropdown value, availtoday_status_id = NULL
    - If only same-day checked: status_id = 4, availtoday_status_id = same-day dropdown value
    - If both checked: status_id = pre-order dropdown value, availtoday_status_id = same-day dropdown value
    - _Requirements: 5.1, 5.2, 5.3_

  
  - [ ] 5.3 Update FormData construction
    - Append status_id to form data
    - Append availtoday_status_id to form data (or empty string if NULL)
    - Maintain all other existing form fields
    - _Requirements: 5.1, 5.2, 5.3_

- [ ] 6. Update CSS styling for checkbox interface
  - [ ] 6.1 Add styles for checkbox containers
    - Style checkbox items with proper spacing
    - Add indentation for conditional dropdowns (margin-left: 24px)
    - Ensure consistent alignment
    - _Requirements: 1.1_
  
  - [ ] 6.2 Ensure mobile responsiveness
    - Test checkbox layout on mobile devices
    - Adjust spacing and sizing for touch targets
    - Verify dropdown visibility on small screens
    - _Requirements: 1.1, 2.1, 3.1_

- [ ] 7. Verify product list display and filtering
  - [ ] 7.1 Test product list badge display
    - Verify pre-order only products show pre-order badge
    - Verify same-day only products show "Same Day Order" badge
    - Verify products with both types show both badges
    - _Requirements: 6.1, 6.2, 6.3_
  
  - [ ] 7.2 Test filter functionality
    - Verify "Pick Up" filter shows products with status_id=1
    - Verify "Delivery" filter shows products with status_id=2
    - Verify "Delivery or Pick Up" filter shows products with status_id=3
    - Verify "Same Day Order" filter shows products with status_id=4 OR availtoday_status_id not null
    - _Requirements: 6.4_

- [ ]* 8. Perform comprehensive testing
  - [ ]* 8.1 Test all checkbox state combinations
    - Test opening modal with pre-order only products
    - Test opening modal with same-day only products
    - Test opening modal with both types enabled
    - Test checking and unchecking checkboxes
    - _Requirements: 1.2, 1.3, 1.4_
  
  - [ ]* 8.2 Test form submission scenarios
    - Test saving with only pre-order checked
    - Test saving with only same-day checked
    - Test saving with both checkboxes checked
    - Test validation when neither checkbox is checked
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  
  - [ ]* 8.3 Test calendar integration
    - Test calendar visibility when same-day checkbox is checked
    - Test date selection and persistence
    - Test calendar hiding when same-day checkbox is unchecked
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  
  - [ ]* 8.4 Test browser compatibility
    - Test on Chrome, Firefox, Safari, Edge
    - Test on mobile devices (iOS Safari, Chrome Mobile)
    - Verify checkbox styling and dropdown behavior across browsers
    - _Requirements: 1.1, 2.1, 3.1_
