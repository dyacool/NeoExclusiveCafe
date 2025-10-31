# Implementation Plan: Saved Customer Information

- [x] 1. Create database table and structure



  - Create saved_customer_info table with all required fields
  - Add foreign key constraints to users and delivery_locations tables
  - Create indexes for performance optimization
  - Test table creation and constraints





  - _Requirements: 1.2, 4.1, 4.2_

- [ ] 2. Implement backend API endpoints
- [ ] 2.1 Create get-saved-info.php endpoint
  - Implement session authentication check
  - Query saved_customer_info table with JOIN to delivery_locations


  - Return JSON response with all entries for authenticated user
  - Include delivery location details and delivery fee
  - Order results by is_primary DESC, created_at ASC
  - _Requirements: 2.2, 4.2_

- [ ] 2.2 Create save-customer-info.php endpoint
  - Implement session authentication check
  - Validate all input fields (email format, phone format, required fields)


  - Check max entries limit (3 per user) for new entries
  - Verify delivery_location_id exists in delivery_locations table
  - Insert new entry or update existing entry
  - Handle set_as_primary flag (update other entries to is_primary = 0)
  - Return success/error JSON response
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 2.5_



- [ ] 2.3 Create delete-saved-info.php endpoint
  - Implement session authentication check
  - Verify entry belongs to authenticated user





  - Delete entry from database
  - If deleted entry was primary, set oldest remaining entry as primary
  - Return success response with new primary ID if applicable
  - _Requirements: 2.7, 2.8, 2.9_

- [ ] 2.4 Create set-primary-info.php endpoint
  - Implement session authentication check

  - Verify entry belongs to authenticated user
  - Update all user entries to is_primary = 0
  - Set specified entry to is_primary = 1
  - Return success/error JSON response
  - _Requirements: 2.5_

- [ ] 3. Create frontend JavaScript module (saved-info-manager.js)
- [x] 3.1 Implement SavedInfoManager class

  - Create class constructor with entries array and currentEntryId
  - Implement loadEntries() method to fetch from get-saved-info.php
  - Implement populateSelector() to fill dropdown with entries
  - Implement autofillPrimary() to auto-fill form with primary entry
  - Implement fillForm(entry) to populate all form fields




  - Implement getLocationValue() to map delivery_location_id to dropdown value
  - _Requirements: 2.2, 2.3, 3.1, 3.2, 3.3_

- [ ] 3.2 Implement save and delete methods
  - Implement saveCurrentInfo(label) to save form data via API
  - Implement getFormData() to extract current form values
  - Implement getSelectedLocationId() to get delivery location ID



  - Implement deleteEntry(entryId) with confirmation dialog
  - Implement setPrimary(entryId) to set entry as primary
  - Add error handling and user feedback for all operations
  - _Requirements: 1.1, 2.6, 2.7_

- [x] 3.3 Setup event listeners and initialization


  - Initialize SavedInfoManager on DOMContentLoaded
  - Add event listener for saved info selector change
  - Add event listener for "Save this info" checkbox
  - Add event listener for "Manage saved info" button
  - Trigger delivery fee calculation when entry is selected
  - _Requirements: 2.3, 2.4, 3.8, 5.8, 5.9_

- [x] 4. Create saved information UI components



- [ ] 4.1 Add saved info selector to checkout.php
  - Add HTML structure for saved info section
  - Add dropdown selector with "Enter new information" default option
  - Add "Manage Saved Information" button
  - Add "Save this information" checkbox
  - Add optional label input field (hidden by default)

  - Style components to match existing checkout design
  - _Requirements: 5.1, 5.2, 5.10_

- [ ] 4.2 Add saved info selector to availtoday-checkout.php
  - Add HTML structure for saved info section (same as checkout.php)
  - Add dropdown selector with "Enter new information" default option
  - Add "Manage Saved Information" button

  - Add "Save this information" checkbox
  - Add optional label input field (hidden by default)
  - Style components to match existing checkout design
  - _Requirements: 5.1, 5.2, 5.10_





- [ ] 4.3 Create manage saved information modal
  - Create modal HTML structure with header and close button
  - Create saved entries list container
  - Create entry card template with all details display
  - Add primary badge display for primary entry
  - Add action buttons (Edit, Delete, Set as Primary)
  - Add "Add New" button (conditionally shown when < 3 entries)

  - Style modal to match existing modal designs
  - _Requirements: 5.3, 5.4, 5.5, 5.6, 5.7_

- [ ] 5. Implement modal functionality (saved-info-ui.js)
- [ ] 5.1 Create modal open/close functions
  - Implement openSavedInfoModal() function
  - Implement closeSavedInfoModal() function
  - Add click outside modal to close
  - Add ESC key to close modal
  - _Requirements: 5.3_

- [ ] 5.2 Implement modal content rendering
  - Implement renderSavedEntries() to display all entries
  - Implement renderEntryCard(entry) to create entry card HTML
  - Implement getIconForLabel(label) for visual icons
  - Show/hide "Add New" button based on entry count
  - Update modal content when entries change
  - _Requirements: 5.4, 5.5, 5.7_

- [ ] 5.3 Implement modal action handlers
  - Add event listener for Edit button (populate form and close modal)
  - Add event listener for Delete button (call deleteEntry and refresh)
  - Add event listener for Set as Primary button (call setPrimary and refresh)
  - Add event listener for Add New button (close modal, clear form)
  - Disable Set as Primary button for current primary entry
  - _Requirements: 5.6, 2.6, 2.7, 2.5_

- [ ] 6. Integrate with checkout form submission
- [ ] 6.1 Update checkout.php form submission
  - Check if "Save this information" checkbox is checked
  - If checked, call saveCurrentInfo() before/after order submission
  - Get label from label input field
  - Handle save errors gracefully
  - Show success message after save
  - _Requirements: 1.1, 1.7_

- [ ] 6.2 Update availtoday-checkout.php form submission
  - Check if "Save this information" checkbox is checked
  - If checked, call saveCurrentInfo() before/after order submission


  - Get label from label input field
  - Handle save errors gracefully
  - Show success message after save
  - _Requirements: 1.1, 1.7_

- [ ] 7. Implement delivery fee calculation integration
- [ ] 7.1 Update delivery fee calculation on entry selection
  - When saved entry is selected, trigger delivery location change event




  - Ensure delivery fee is calculated from delivery_locations table
  - Update shipping fee display in order summary
  - Handle pickup method (no delivery fee)
  - _Requirements: 2.4, 3.3, 3.8, 5.9_

- [ ] 7.2 Test delivery fee updates
  - Test switching between entries with different delivery locations
  - Verify delivery fee updates correctly
  - Test pickup method (should not show delivery fee)
  - Test delivery method with saved entry
  - _Requirements: 2.4, 3.8_

- [ ] 8. Add form field name/email support
- [ ] 8.1 Add name fields to checkout forms if not present
  - Check if first_name and last_name fields exist in checkout.php
  - Check if first_name and last_name fields exist in availtoday-checkout.php
  - Add fields if missing (currently only shows display name)
  - Update form submission to include name fields
  - _Requirements: 1.1, 1.2, 3.1_

- [ ] 8.2 Ensure email field is accessible
  - Verify email field exists and is accessible in both checkout pages
  - Update field IDs if needed for consistency
  - Ensure email is included in form submission
  - _Requirements: 1.1, 1.4, 3.1_

- [ ] 9. Add CSS styling
- [ ] 9.1 Create saved-info.css stylesheet
  - Style saved info selector section
  - Style dropdown with icons and primary badge
  - Style "Save this information" checkbox and label input
  - Style "Manage Saved Information" button
  - Ensure responsive design for mobile devices
  - _Requirements: 5.1, 5.2, 5.10_

- [ ] 9.2 Style manage saved information modal
  - Style modal overlay and content
  - Style entry cards with proper spacing
  - Style primary badge
  - Style action buttons (Edit, Delete, Set as Primary)
  - Style "Add New" button
  - Ensure responsive design for mobile devices
  - _Requirements: 5.3, 5.4, 5.5, 5.6, 5.7_

- [ ] 10. Implement validation and error handling
- [ ] 10.1 Add frontend validation
  - Validate email format before saving
  - Validate phone format before saving
  - Validate delivery location is selected
  - Validate complete address is not empty
  - Show inline error messages for invalid fields
  - Prevent save if validation fails
  - _Requirements: 1.4, 1.5, 1.6, 1.7_

- [ ] 10.2 Add backend validation
  - Sanitize all input data in API endpoints
  - Validate email format on server
  - Validate phone format on server
  - Check delivery_location_id exists
  - Verify entry ownership for delete/update operations
  - Return appropriate error messages
  - _Requirements: 1.4, 1.5, 1.6, 4.2, 4.3_

- [ ] 10.3 Add user feedback for operations
  - Show loading spinner during API calls
  - Show success toast/message after save
  - Show error alert for API failures
  - Disable buttons during operations
  - Re-enable buttons after completion
  - _Requirements: 1.1, 2.6, 2.7, 2.5_

- [ ] 11. Test complete feature
- [ ] 11.1 Test basic functionality
  - Test saving first entry
  - Test auto-fill on page reload
  - Test adding second and third entries
  - Test switching between entries
  - Test editing entries
  - Test deleting entries
  - Test setting primary entry
  - _Requirements: All_

- [ ] 11.2 Test edge cases
  - Test max limit (3 entries)
  - Test deleting primary entry
  - Test with no saved entries
  - Test with invalid data
  - Test with pickup method (no delivery location)
  - Test delivery fee calculation
  - _Requirements: 1.3, 2.9, 3.4, 3.5, 3.6_

- [ ] 11.3 Test cross-page functionality
  - Save entry on checkout.php, verify on availtoday-checkout.php
  - Set primary on one page, verify auto-fill on other page
  - Delete entry on one page, verify removed on other page
  - Test delivery fee updates on both pages
  - _Requirements: 3.1, 3.2, 5.1, 5.8_

- [ ] 12. Documentation and deployment
- [ ] 12.1 Create user documentation
  - Document how to save information
  - Document how to manage saved entries
  - Document how to set primary entry
  - Create FAQ for common questions
  - _Requirements: All_

- [ ] 12.2 Deploy to production
  - Run database migration script
  - Deploy backend API files
  - Deploy frontend JavaScript files
  - Deploy CSS files
  - Update checkout.php and availtoday-checkout.php
  - Test on production environment
  - Monitor for errors
  - _Requirements: All_
