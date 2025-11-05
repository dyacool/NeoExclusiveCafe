# Implementation Plan

- [x] 1. Set up event queue infrastructure




  - Create `backend/api/events/` directory with proper permissions
  - Implement `EventQueue` class in `backend/api/event-queue.php` with file-based storage, auto-incrementing IDs, file locking, and automatic cleanup
  - Create queue file initialization and rotation logic



  - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.3, 6.6_

- [x] 2. Implement event broadcasting service



  - Create `EventBroadcaster` class in `backend/api/event-broadcaster.php` with `broadcast()`, `broadcastToUser()`, and `broadcastToRole()` methods
  - Implement event filtering logic based on user_id and role
  - Add event validation and error handling
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 6.2_

- [x] 3. Create SSE event stream server



  - Implement `backend/api/sse-stream.php` endpoint with SSE headers and connection management
  - Add session-based authentication and authorization checks
  - Implement channel-based subscription system
  - Add keepalive mechanism (30-second interval)
  - Implement connection timeout (5 minutes) and per-user connection limits (max 3)



  - Add event filtering based on user permissions
  - _Requirements: 1.2, 2.3, 3.3, 4.3, 5.1, 5.4, 6.1, 6.2, 6.3, 6.4, 6.6_

- [x] 4. Build client-side realtime notification handler



  - Create `frontend/assets/js/realtime-notifications.js` with `RealtimeNotifications` class
  - Implement EventSource connection management with automatic reconnection and exponential backoff
  - Add event handler registration system for different event types



  - Implement connection status indicator UI
  - Add reconnection failure handling with user prompt
  - _Requirements: 1.2, 1.4, 2.3, 3.3, 4.3, 5.1, 5.2, 5.4, 5.5_

- [x] 5. Create notifications database table and API



  - Create `notifications` table migration with user_id, message, type, is_read, and created_at fields
  - Implement notification creation API in `backend/api/create-notification.php`
  - Implement notification retrieval API in `backend/api/get-notifications.php`


  - Implement mark-as-read API in `backend/api/mark-notification-read.php`
  - _Requirements: 7.1, 7.2, 7.5_

- [ ] 6. Integrate order status update broadcasting
  - Modify `backend/api/auto-update-order-status.php` to broadcast events after status updates
  - Add event broadcasting to any admin order management scripts

  - Include order_id, status, customer_id, and timestamp in event payload
  - _Requirements: 1.1, 1.3_

- [ ] 7. Integrate new order notifications in checkout
  - Modify `frontend/pages/cart/checkout.php` to broadcast new order events after successful order creation
  - Modify `frontend/pages/cart/availtoday-checkout.php` to broadcast new order events
  - Modify `frontend/pages/cart/process-availtoday-checkout.php` to broadcast new order events
  - Include order_id, customer_name, order_type, total, and timestamp in event payload
  - Broadcast to admin role only
  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 8. Integrate product inventory update broadcasting
  - Modify `backend/pages/products/update-sdo-quantities.php` to broadcast inventory events
  - Add broadcasting to any other product quantity update scripts


  - Include product_id, quantity, available status, and product_name in event payload
  - _Requirements: 4.1, 4.2, 4.4_

- [ ] 9. Build notification center UI component
  - Create notification center dropdown component in navigation bar
  - Add unread notification count badge
  - Implement notification list display with message, type, and timestamp
  - Add click handler to mark notifications as read
  - Style notification types (info, warning, success, error) with appropriate colors
  - _Requirements: 7.3, 7.4, 7.5_

- [ ] 10. Implement product dashboard realtime updates
  - Add realtime notification script to `frontend/pages/products/product-dashboard.php`
  - Subscribe to `product_inventory` channel
  - Implement UI update handler to refresh product quantities and availability status
  - Add visual highlight for products that just changed status
  - _Requirements: 4.3, 4.4, 4.5_

- [ ] 11. Implement order tracking realtime updates
  - Add realtime notification script to customer order tracking pages
  - Subscribe to `order_status` channel
  - Implement UI update handler to refresh order status display
  - Add visual notification when status changes
  - Filter events to show only customer's own orders
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 12. Implement admin panel realtime notifications
  - Add realtime notification script to admin panel pages
  - Subscribe to `new_order` and `order_status` channels
  - Implement new order notification with visual badge and audio alert
  - Add notification sound file and audio playback functionality
  - Persist notification count until admin views orders page
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 13. Implement rider delivery assignment notifications
  - Add realtime notification script to rider dashboard/interface
  - Subscribe to `order_status` channel filtered for rider assignments
  - Implement delivery assignment notification with audio and visual alert
  - Display order details including customer address and delivery time
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ] 14. Add connection status indicator to all pages
  - Create reusable connection status indicator component
  - Add indicator to navigation bar or footer
  - Show connected, reconnecting, or disconnected states with appropriate styling
  - _Requirements: 5.4_

- [ ] 15. Configure PHP and web server for SSE
  - Update PHP configuration to set appropriate `max_execution_time` for SSE endpoint
  - Configure web server to disable output buffering for SSE endpoint
  - Set proper file permissions for `backend/api/events/` directory
  - Add deployment documentation for production setup
  - _Requirements: 6.3, 6.4, 6.5_

- [ ]* 16. Create integration tests
  - Write integration test for end-to-end event flow (broadcast → SSE → client)
  - Test authentication and authorization filtering
  - Test concurrent client connections
  - Test reconnection behavior
  - _Requirements: 5.1, 5.2, 6.1, 6.2_

- [ ]* 17. Add error logging and monitoring
  - Implement comprehensive error logging in EventBroadcaster
  - Add connection count logging in SSE server
  - Create event throughput monitoring
  - Add alerts for queue file issues
  - _Requirements: 6.5_
