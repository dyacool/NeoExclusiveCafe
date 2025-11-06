# Implementation Plan

- [x] 1. Create AJAX polling API endpoint



  - Create `backend/api/get-order-list.php` that returns order data in JSON format
  - Implement authentication check to ensure only admin users can access
  - Accept filter parameters: status, search, page, since_timestamp
  - Query orders table with proper filtering and pagination
  - Return JSON response with orders array, status_counts, pagination metadata, and current timestamp
  - Include `is_new` flag for orders created after the `since` timestamp
  - _Requirements: 1.2, 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 2. Create JavaScript polling client


  - Create `backend/assets/js/order-list-polling.js` with OrderListPoller class
  - Implement polling loop that makes AJAX requests every 5 seconds
  - Implement exponential backoff for failed requests (max 30 seconds)
  - Store reference to `.orders-container` DOM element for updates
  - Pass current filter state and last update timestamp with each request
  - Handle successful responses by updating only the orders container
  - Handle errors gracefully without breaking the polling loop
  - Implement stop() method to cleanup when page unloads
  - _Requirements: 1.1, 1.2, 3.1, 3.2, 3.4, 3.5_

- [x] 3. Implement orders container update logic


  - In OrderListPoller class, create updateOrdersContainer() method
  - Replace only the `.orders-container` innerHTML with new order data
  - Preserve scroll position before and after update
  - Apply `.order-row-new` class to new orders for highlighting
  - Update status count badges separately without full refresh
  - Maintain active filters and pagination state
  - _Requirements: 1.1, 1.3, 1.4, 1.5, 5.2, 5.3_

- [x] 4. Add loading indicators and UI feedback


  - Add subtle loading spinner element to orders container
  - Show/hide spinner during active AJAX requests
  - Add "Last updated: X seconds ago" timestamp display
  - Create CSS for `.order-row-new` highlight effect with 3-second fade
  - Ensure loading indicator doesn't obstruct view of existing orders
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 5. Integrate polling into order-list.php


  - Add `<script>` tag to include `order-list-polling.js`
  - Initialize OrderListPoller on page load with current filter state
  - Pass initial timestamp to polling client
  - Add loading indicator HTML element
  - Add last update timestamp HTML element
  - Remove all SSE/WebSocket script includes from the page
  - _Requirements: 1.1, 1.2, 5.5_

- [x] 6. Remove EventBroadcaster from order processing


  - Remove `require_once` for event-broadcaster.php from `frontend/pages/cart/process_order.php`
  - Remove `require_once` for event-queue.php from `frontend/pages/cart/process_order.php`
  - Remove all `EventBroadcaster::broadcastNewOrder()` calls
  - Remove all `EventQueue::getEvents()` calls
  - Remove try-catch blocks related to event broadcasting
  - Keep existing email and in-app notification logic intact
  - _Requirements: 2.5_

- [x] 7. Remove EventBroadcaster from status updates


  - Remove `require_once` for event-broadcaster.php from `backend/pages/orders/update-status.php`
  - Remove `require_once` for event-queue.php from `backend/pages/orders/update-status.php`
  - Remove all `EventBroadcaster::broadcastOrderStatus()` calls
  - Remove all `EventQueue::getEvents()` calls
  - Remove try-catch blocks related to event broadcasting
  - Keep existing email and in-app notification logic intact
  - _Requirements: 2.5_

- [x] 8. Delete SSE and WebSocket infrastructure files


  - Delete `backend/api/event-broadcaster.php`
  - Delete `backend/api/event-queue.php`
  - Delete `backend/api/sse-stream.php`
  - Delete `backend/api/test-sse-client.html`
  - Delete `backend/api/test-event-broadcaster.php`
  - Delete `backend/api/test-event-broadcasting.php`
  - Delete `backend/api/test-event-broadcasting-api.php`
  - Delete `backend/api/test-event-queue.php`
  - Delete `backend/api/test-sse-diagnostics.html`
  - Delete `backend/api/test-notifications-api.php`
  - Delete `backend/api/REALTIME_NOTIFICATIONS_SETUP.md`
  - _Requirements: 2.1, 2.2, 2.5_

- [x] 9. Delete client-side realtime notification files


  - Delete `frontend/assets/js/realtime-notifications.js`
  - Delete `frontend/assets/js/realtime-notifications-ui.js`
  - Delete `frontend/assets/js/customer-realtime-notifications.js`
  - Delete `frontend/assets/js/product-dashboard-realtime.js`
  - Delete `backend/assets/js/admin-realtime-notifications.js`
  - Delete `frontend/assets/css/realtime-notifications.css`
  - _Requirements: 2.3, 2.4, 2.5_

- [x] 10. Test polling system end-to-end





  - Verify order list updates automatically when new order is placed
  - Verify order list updates when order status is changed
  - Verify filters are preserved during polling updates
  - Verify scroll position is maintained during updates
  - Verify new orders are highlighted with fade effect
  - Verify loading indicator appears during requests
  - Verify error handling with network failures
  - Verify polling stops when navigating away from page
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.2, 3.4, 3.5, 5.1, 5.2, 5.3_
