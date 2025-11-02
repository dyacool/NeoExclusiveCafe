# Implementation Plan

- [x] 1. Create database schema and migration



  - Create SQL migration file for `order_status_settings` table with fields: id, admin_id, auto_status_enabled, updated_at
  - Add database indexes to orders table for delivery_date, pickup_date, and status columns
  - Create composite index on (delivery_method, delivery_date, status) for query optimization
  - Execute migration and verify table creation





  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 2. Implement toggle auto-status API endpoint
  - Create `backend/pages/orders/toggle-auto-status.php` file
  - Implement POST handler to save auto-status preference to database
  - Implement GET handler to retrieve current auto-status preference



  - Add admin session authentication check
  - Return JSON responses with success/error status
  - Handle database errors gracefully with appropriate error messages
  - _Requirements: 1.2, 1.5, 6.5_



- [ ] 3. Add toggle switch UI to order list page
  - Update `backend/pages/orders/order-list.php` to include toggle switch HTML



  - Position toggle switch on the right side of filter status container
  - Add CSS styling for toggle switch with smooth animations
  - Implement JavaScript event handler for toggle state changes
  - Add AJAX call to save toggle state via API endpoint
  - Display success/error notifications to user
  - Load and apply saved toggle state on page load
  - _Requirements: 1.1, 1.2, 1.4, 1.5_

- [ ] 4. Implement automatic status update cron job
  - Create `backend/api/auto-update-order-status.php` file
  - Check if auto-status is enabled before processing updates
  - Implement SQL query to update same-day pickup orders to "Ready for Pick-up" status (order_date = pickup_date = today)
  - Implement SQL query to update same-day delivery orders to "Ready for Delivery" status (order_date = delivery_date = today)
  - Implement SQL query to update pickup orders due tomorrow to "Preparing" status
  - Implement SQL query to update pickup orders due today to "Ready for Pick-up" status
  - Implement SQL query to update delivery orders due tomorrow to "Preparing" status
  - Implement SQL query to update delivery orders due today to "Ready for Delivery" status
  - Integrate existing email notification system for each status change
  - Integrate existing in-app notification system for each status change
  - Log all automatic status changes using activity logger
  - Return JSON summary of updated orders
  - _Requirements: 2.1, 2.2, 2.5, 3.1, 3.2, 3.6, 7.1, 7.2, 7.3, 7.4, 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 5. Enhance order sorting with priority-based queueing
  - Modify SQL query in `order-list.php` to add priority calculation using CASE statement
  - Implement priority level 1 for overdue orders (past due date, not completed)



  - Implement priority level 2 for orders due today
  - Implement priority level 3 for orders due tomorrow
  - Implement priority level 4 for future orders
  - Sort orders by priority ASC, then due_date ASC, then due_time ASC
  - Maintain priority sorting when status filters are applied
  - Maintain priority sorting across pagination
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 6. Implement overdue order detection and display
  - Update order list query to detect overdue orders based on due date comparison
  - Add "Overdue" badge with red styling for orders past due date
  - Ensure overdue orders appear at top of list (priority 1)
  - Handle orders with specific delivery/pickup times for overdue calculation
  - Handle orders without specific times using business hours logic
  - Update JavaScript table rendering to display overdue badges
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 7. Create Windows Task Scheduler configuration
  - Document Windows Task Scheduler setup instructions
  - Create batch file to execute auto-update-order-status.php
  - Configure hourly schedule between 6 AM and 10 PM
  - Test cron job execution manually
  - Verify cron job logs are created correctly
  - _Requirements: 2.1, 2.2, 2.5, 3.1, 3.2, 3.6_

- [ ] 8. Add error handling and logging
  - Implement try-catch blocks in toggle API for database errors
  - Implement error logging in auto-update cron job
  - Add retry logic for database timeouts (3 attempts with exponential backoff)
  - Log email notification failures separately without blocking status updates
  - Create dedicated log file for auto-update operations
  - Add error notifications for repeated cron job failures
  - _Requirements: 6.5, 7.1, 7.3_

- [ ]* 9. Create OrderStatusSettings helper class
  - Create `backend/includes/order-status-settings.php` file
  - Implement isAutoStatusEnabled() method to check toggle state
  - Implement setAutoStatus() method to update toggle state
  - Add database connection handling
  - Add error handling for database operations
  - _Requirements: 1.2, 1.5, 6.4_

- [ ]* 10. Create OrderPriority helper class
  - Create `backend/includes/order-priority.php` file
  - Implement calculatePriority() method for priority calculation
  - Implement getPriorityBadge() method to generate badge HTML
  - Define priority constants (OVERDUE, DUE_TODAY, DUE_TOMORROW, FUTURE)
  - Add date comparison logic for priority determination
  - _Requirements: 4.1, 4.2, 4.3, 5.1, 5.2, 5.3_

- [ ]* 11. Write integration tests for toggle API
  - Test POST request saves auto-status setting correctly
  - Test GET request retrieves auto-status setting correctly
  - Test unauthorized access returns 403 error
  - Test invalid input returns 400 error
  - Test default value is false when no setting exists
  - _Requirements: 1.2, 1.5, 6.4_

- [ ]* 12. Write integration tests for auto-update cron job
  - Test cron job only runs when auto-status is enabled
  - Test same-day pickup orders immediately transition to "Ready for Pick-up"
  - Test same-day delivery orders immediately transition to "Ready for Delivery"
  - Test pickup orders due tomorrow transition to "Preparing"
  - Test pickup orders due today transition to "Ready for Pick-up"
  - Test delivery orders due tomorrow transition to "Preparing"
  - Test delivery orders due today transition to "Ready for Delivery"
  - Test email notifications are sent for each update
  - Test in-app notifications are created for each update
  - Test activity logging works correctly
  - Test manual statuses (Picked-up, Delivered) are not auto-updated
  - _Requirements: 2.1, 2.2, 2.3, 2.5, 3.1, 3.2, 3.3, 3.4, 3.6, 7.1, 7.2, 7.3, 7.4, 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ]* 13. Write integration tests for order priority sorting
  - Test overdue orders appear first in list
  - Test due today orders appear second in list
  - Test due tomorrow orders appear third in list
  - Test future orders appear last in list
  - Test orders with same priority are sorted by due time
  - Test priority sorting is maintained with status filters
  - Test priority sorting is maintained across pagination
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 14. Create documentation for feature usage
  - Document how to enable/disable auto-status toggle
  - Document automatic status transition rules for pickup orders
  - Document automatic status transition rules for delivery orders
  - Document overdue order detection logic
  - Document priority-based order queueing behavior
  - Document Windows Task Scheduler setup steps
  - Document troubleshooting steps for common issues
  - _Requirements: 1.1, 1.3, 1.4, 2.1, 2.2, 3.1, 3.2, 4.1, 4.2, 5.1_
