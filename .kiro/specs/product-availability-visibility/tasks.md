# Implementation Plan

- [x] 1. Enhance database query to include hide_when_unavailable column


  - Add `p.hide_when_unavailable` to the SELECT statement in the main product query
  - Verify the column exists in the products table
  - Test query execution to ensure no errors
  - _Requirements: 5.1, 5.2_




- [ ] 2. Implement availability determination function
- [ ] 2.1 Create determineProductAvailability() function
  - Write function that accepts product row and today's date as parameters
  - Implement stock checking logic for all three product types (same-day only, pre-order only, dual capability)
  - Implement date availability checking from todays_products_dates and regular_products_today_dates
  - Combine stock and date checks to determine overall unavailability
  - Apply visibility rules based on show_when_unavailable and hide_when_unavailable flags


  - Return structured result with is_unavailable, unavailable_reason, and should_display flags
  - _Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_


- [x] 2.2 Add logging for visibility decisions


  - Log when products are hidden due to unavailability
  - Include product ID, name, reason, and flag values in log messages
  - Use error_log() for debugging purposes
  - _Requirements: 5.4_


- [ ] 3. Integrate availability function into product rendering loop
- [ ] 3.1 Call determineProductAvailability() for each product
  - Iterate through all fetched products
  - Call availability function for each product


  - Store availability results in product data array
  - _Requirements: 4.1, 4.2, 4.3, 5.3_

- [x] 3.2 Filter products based on should_display flag


  - Create new array for products to display
  - Skip products where should_display is false
  - Maintain existing sorting logic for displayed products
  - _Requirements: 2.1, 2.2, 4.5, 4.6, 5.5_

- [x] 3.3 Update product data with availability information



  - Add is_unavailable flag to product data
  - Add unavailable_reason to product data
  - Add hide_when_unavailable to productData JSON for frontend use
  - Ensure data is properly passed to frontend rendering
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 4. Verify unavailable product indicators in UI
  - Confirm "Out of Stock" badge displays for unavailable products that are shown
  - Verify "Unavailable" button is disabled for unavailable products
  - Check that unavailable overlay appears on product images
  - Ensure unavailable products have reduced opacity styling
  - Test that unavailable products sort to bottom of list
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 5. Update product modal to respect availability logic
  - Ensure modal uses the same availability determination logic
  - Update modal's "Add to Cart" button to be disabled for unavailable products
  - Display appropriate unavailability reason in modal
  - _Requirements: 6.1, 6.2, 6.4_

- [ ]* 6. Test availability scenarios
  - Test same-day only product with 0 stock
  - Test same-day only product without today's date in todays_products_dates
  - Test pre-order only product with 0 stock
  - Test dual capability product with 0 in both stocks
  - Test dual capability product with stock in one but not the other
  - Test dual capability product without today's date in regular_products_today_dates
  - _Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_

- [ ]* 7. Test visibility flag combinations
  - Test show_when_unavailable=1, hide_when_unavailable=0 (should show with indicators)
  - Test show_when_unavailable=0, hide_when_unavailable=1 (should hide)
  - Test show_when_unavailable=1, hide_when_unavailable=1 (should hide - priority rule)
  - Test show_when_unavailable=0, hide_when_unavailable=0 (should hide - default)
  - Test available product with any flag combination (should always show)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 4.4, 4.5, 4.6, 4.7_

- [ ]* 8. Verify database query performance
  - Measure query execution time with test dataset
  - Verify LEFT JOINs return correct data for all scenarios
  - Test with products missing records in joined tables
  - Check that indexes exist on foreign keys and date columns
  - _Requirements: 5.1, 5.2_
