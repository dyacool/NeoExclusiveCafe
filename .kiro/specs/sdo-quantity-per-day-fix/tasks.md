# Implementation Plan

- [x] 1. Locate and analyze the save handler function


  - Find where `saveProductChanges()` is defined (inline script or separate JS file)
  - Document current data collection logic
  - Identify where SDO quantity collection should be added
  - _Requirements: 1.1, 1.2_



- [ ] 2. Implement SDO quantity collection in save handler
  - [x] 2.1 Add logic to detect if product has SDO enabled

    - Check if status_id === 4 OR availtoday_status_id is set
    - _Requirements: 1.1, 2.1, 2.2_
  
  - [x] 2.2 Collect SDO quantities using getSDOQuantities()

    - Call the existing function from sdo-quantity-manager.js
    - Store result in productData object
    - _Requirements: 1.1, 1.2_
  
  - [x] 2.3 Add validation for SDO quantity data

    - Validate date format (YYYY-MM-DD)
    - Validate quantity values (non-negative integers)
    - Display error messages for invalid data
    - _Requirements: 3.1, 3.2, 3.3, 3.4_
  


  - [ ] 2.4 Include SDO quantities in the request payload
    - Serialize quantities as JSON string
    - Add to productData as 'sdo_quantities' field
    - _Requirements: 1.2_


- [ ] 3. Enhance update-product.php to save SDO quantities
  - [ ] 3.1 Add logic to receive and parse SDO quantities
    - Check if 'sdo_quantities' exists in input

    - Parse JSON string to associative array
    - Log received data for debugging
    - _Requirements: 1.3_
  
  - [x] 3.2 Implement quantity deletion logic

    - Delete existing quantities for the product from quantity_per_day_sdo
    - Use prepared statement for security
    - _Requirements: 1.3, 2.4, 2.5_
  

  - [ ] 3.3 Implement quantity insertion logic
    - Insert new quantities into quantity_per_day_sdo table
    - Use batch insert with prepared statement
    - Handle empty quantities array (delete only)
    - _Requirements: 1.3, 1.4_
  


  - [ ] 3.4 Add transaction handling
    - Wrap quantity operations in database transaction
    - Rollback on error to maintain data consistency
    - _Requirements: 1.3_


  
  - [ ] 3.5 Add error handling and logging
    - Catch exceptions during quantity save
    - Log errors to PHP error log


    - Return appropriate error response
    - _Requirements: 1.3_

- [ ] 4. Verify date table synchronization
  - [x] 4.1 Ensure dates are saved to correct table based on status

    - Status 4 → todays_products_dates
    - Status 1/2/3 with SDO → regular_products_today_dates
    - Verify existing logic in update-product.php handles this
    - _Requirements: 2.2, 2.3_
  

  - [ ] 4.2 Add cleanup logic for status transitions
    - When removing SDO, delete from quantity_per_day_sdo
    - When removing SDO, delete from appropriate dates table
    - _Requirements: 2.4, 2.5_

- [x] 5. Test the complete save flow

  - [ ] 5.1 Test SDO-only product (status_id = 4)
    - Create/edit product with status 4
    - Set quantities for multiple dates
    - Save and verify database entries
    - Reload page and verify quantities display

    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_
  
  - [ ] 5.2 Test Pre-Order with SDO product
    - Create/edit product with status 1/2/3 and availtoday_status_id set
    - Set quantities for dates


    - Save and verify both pre-order and SDO data saved
    - _Requirements: 2.1, 2.2_
  
  - [ ] 5.3 Test status transitions
    - Change product from Pre-Order to SDO
    - Verify quantities can be set and saved
    - Change from SDO to Pre-Order only
    - Verify SDO quantities are cleared
    - _Requirements: 2.4, 2.5_
  
  - [ ] 5.4 Test validation
    - Try saving with invalid date format
    - Try saving with negative quantity
    - Verify error messages display correctly
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_
  
  - [ ] 5.5 Test edge cases
    - Save product with no dates selected
    - Save product with 0 quantity for a date
    - Save product with many dates (10+)
    - _Requirements: 1.3, 1.4_

- [ ] 6. Verify UI updates after save
  - Check that product list shows updated quantities
  - Verify stock display reflects saved quantities
  - Ensure no page errors or console warnings
  - _Requirements: 1.5_
