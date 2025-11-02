# Implementation Plan

- [x] 1. Create customer info retrieval function



  - Add `fetchCustomerInfoFromSaved()` function to both checkout files
  - Implement SQL query to fetch email, complete_address, phone, first_name, last_name from saved_customer_info
  - Include LEFT JOIN with delivery_locations table to get full location string
  - Order by is_primary DESC, updated_at DESC to prioritize primary and most recent records
  - Return associative array with customer data or null if no records exist
  - Add error handling and logging for query failures




  - _Requirements: 1.1, 1.3, 1.4, 2.1, 2.3, 2.4, 3.1, 4.1, 4.2, 4.3, 4.4_

- [ ] 2. Update process-availtoday-checkout.php with data merging logic
- [x] 2.1 Replace existing email fallback logic

  - Remove the current email-only fallback query (lines ~58-77)
  - Call `fetchCustomerInfoFromSaved()` after extracting POST data
  - Log the fetch operation and results
  - _Requirements: 3.1, 3.3_

- [ ] 2.2 Implement comprehensive data merging
  - Merge saved customer info with POST data for email, phone, first_name, last_name


  - Give precedence to saved customer info over POST data
  - For delivery orders, use complete_address from saved info
  - For pickup orders, construct address from POST data or use minimal value
  - Log final merged values for debugging
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 3.2, 3.4, 4.1_



- [ ] 2.3 Update order insertion with merged data
  - Ensure customer_email uses merged email value
  - Ensure customer_address uses merged address value




  - Ensure customer_name uses merged first_name and last_name
  - Ensure customer_contact uses merged phone value
  - _Requirements: 3.2, 3.4_


- [ ] 2.4 Update orderDetails array construction
  - Verify user_email field uses merged email
  - Verify customer_address field uses merged address
  - Verify customer_name field uses merged name
  - Verify customer_contact field uses merged phone
  - _Requirements: 3.2, 3.4_


- [ ] 3. Update process_order.php with data merging logic
- [ ] 3.1 Replace existing email fallback logic
  - Remove the current email-only fallback query (lines ~79-102)
  - Call `fetchCustomerInfoFromSaved()` after extracting POST data
  - Log the fetch operation and results
  - _Requirements: 3.1, 3.3_



- [ ] 3.2 Implement comprehensive data merging
  - Merge saved customer info with POST data for email, phone, first_name, last_name
  - Give precedence to saved customer info over POST data



  - For delivery orders, use complete_address from saved info
  - For pickup orders, construct address from POST data or use minimal value
  - Log final merged values for debugging
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 3.2, 3.4, 4.1_

- [ ] 3.3 Update order data structure
  - Ensure user_email uses merged email value
  - Ensure delivery_address uses merged address value for delivery orders
  - Ensure user_name uses merged first_name and last_name
  - Ensure contact_number uses merged phone value
  - _Requirements: 3.2, 3.4_

- [ ] 3.4 Update notificationData array in sendOrderEmail function
  - Verify user_email field uses merged email
  - Verify customer_address field uses merged address
  - Verify customer_name field uses merged name
  - Verify customer_contact field uses merged phone
  - _Requirements: 3.2, 3.4_

- [ ] 4. Add enhanced error logging
  - Add log section before fetching saved customer info with user_id
  - Add log after fetch with success/failure status
  - Add log showing retrieved email and address values
  - Add log showing final merged email and address values
  - Add log before order insertion with complete customer data
  - Add critical error logs when email or address is missing for delivery orders
  - _Requirements: 3.3_

- [ ]* 5. Test customer info retrieval and email notifications
  - Place same-day delivery order with user who has saved info
  - Verify admin email shows correct customer email (not "Not provided")
  - Verify admin email shows correct delivery address (not "N/A")
  - Place same-day pickup order with user who has saved info
  - Verify admin email shows correct customer email
  - Place pre-order delivery with user who has saved info
  - Verify admin email shows correct customer email and address
  - Place order with user who has no saved info
  - Verify system falls back to POST data correctly
  - Review PHP error logs to verify logging is working
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4_

