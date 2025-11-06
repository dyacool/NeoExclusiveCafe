# Design Document

## Overview

This design replaces the existing WebSocket/SSE-based realtime notification system with a lightweight AJAX polling architecture. The system will periodically fetch order updates from the server and refresh the order list container without requiring persistent connections. This approach simplifies the infrastructure, reduces server resource usage, and provides a more maintainable solution for near-realtime updates.

## Architecture

### High-Level Architecture

```
┌─────────────────┐         AJAX Polling (every 5s)        ┌──────────────────┐
│                 │ ────────────────────────────────────>   │                  │
│  Admin Browser  │                                         │   PHP Backend    │
│  (order-list)   │ <────────────────────────────────────   │   (API Endpoint) │
│                 │         JSON Response (orders)          │                  │
└─────────────────┘                                         └──────────────────┘
        │                                                            │
        │                                                            │
        v                                                            v
┌─────────────────┐                                         ┌──────────────────┐
│  JavaScript     │                                         │   MySQL Database │
│  Polling Loop   │                                         │   (orders table) │
└─────────────────┘                                         └──────────────────┘
```

### Components to Remove

1. **Backend Files**:
   - `backend/api/event-broadcaster.php` - Event broadcasting logic
   - `backend/api/event-queue.php` - Event queue management
   - `backend/api/sse-stream.php` - SSE streaming endpoint
   - `backend/api/test-sse-client.html` - SSE testing client
   - `backend/api/test-event-broadcaster.php` - Event broadcaster tests
   - `backend/api/test-event-broadcasting.php` - Event broadcasting tests
   - `backend/api/test-event-broadcasting-api.php` - API tests
   - `backend/api/test-event-queue.php` - Event queue tests
   - `backend/api/test-sse-diagnostics.html` - SSE diagnostics
   - `backend/api/test-notifications-api.php` - Notification API tests
   - `backend/api/REALTIME_NOTIFICATIONS_SETUP.md` - Setup documentation

2. **Frontend Files**:
   - `frontend/assets/js/realtime-notifications.js` - SSE client connection logic
   - `frontend/assets/js/realtime-notifications-ui.js` - UI handlers for SSE events
   - `frontend/assets/js/customer-realtime-notifications.js` - Customer-side SSE
   - `frontend/assets/js/product-dashboard-realtime.js` - Product dashboard SSE
   - `frontend/assets/css/realtime-notifications.css` - Notification styling
   - `backend/assets/js/admin-realtime-notifications.js` - Admin-side SSE

3. **Code References to Remove**:
   - All `require_once` statements for event-broadcaster.php and event-queue.php
   - All `EventBroadcaster::broadcast*()` method calls
   - All `EventQueue::*()` method calls
   - All SSE connection initialization code in HTML files
   - All `<link>` and `<script>` tags referencing realtime notification files

## Components and Interfaces

### 1. AJAX Polling Client (JavaScript)

**File**: `backend/assets/js/order-list-polling.js`

**Responsibilities**:
- Initialize polling loop when order list page loads
- Make periodic AJAX requests to fetch order updates
- Update the orders container with new data
- Handle errors and implement exponential backoff
- Stop polling when user navigates away

**Key Functions**:
```javascript
class OrderListPoller {
    constructor(options) {
        this.pollInterval = options.pollInterval || 5000; // 5 seconds
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = 2;
        this.maxBackoff = 30000; // 30 seconds
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastUpdateTimestamp = null;
        this.currentFilters = {};
        this.ordersContainer = null; // Reference to .orders-container element
    }

    start() { /* Start polling loop */ }
    stop() { /* Stop polling and cleanup */ }
    poll() { /* Make AJAX request */ }
    updateOrdersContainer(data) { /* Update ONLY .orders-container DOM element */ }
    handleError(error) { /* Error handling with backoff */ }
    resetBackoff() { /* Reset backoff on success */ }
}
```

**Important**: The polling system will ONLY update the `.orders-container` element (which contains the table and pagination). It will NOT refresh the entire page, header, filters, or any other elements. This ensures a smooth user experience without disrupting the admin's current view or interactions.

### 2. Order List API Endpoint (PHP)

**File**: `backend/api/get-order-list.php`

**Responsibilities**:
- Authenticate admin user
- Accept filter parameters (status, search, page, since_timestamp)
- Query orders table with filters
- Return JSON response with order data and metadata

**Request Parameters**:
- `status` (string, optional): Filter by order status
- `search` (string, optional): Search by order ID or customer name
- `page` (int, optional): Pagination page number
- `since` (timestamp, optional): Only return orders created/updated after this timestamp

**Response Format**:
```json
{
    "success": true,
    "timestamp": "2025-11-05 14:30:00",
    "orders": [
        {
            "order_id": 123,
            "order_date": "2025-11-05 14:25:00",
            "customer_name": "John Doe",
            "customer_contact": "09123456789",
            "total_items": 3,
            "total_amount": 450.00,
            "payment_method": "GCash",
            "delivery_method": "Delivery",
            "delivery_date": "2025-11-06",
            "delivery_time": "14:00:00",
            "status": "Pending",
            "is_new": true
        }
    ],
    "status_counts": {
        "all": 45,
        "Pending": 12,
        "Preparing": 8,
        "Ready for Delivery": 5,
        "Out for Delivery": 3,
        "Delivered": 17
    },
    "total_pages": 3,
    "current_page": 1
}
```

### 3. Order List Page Updates

**File**: `backend/pages/orders/order-list.php`

**Changes**:
- Remove all SSE/WebSocket script includes
- Add new polling script include
- Initialize polling on page load
- Pass current filter state to polling client
- Add loading indicator element
- Add last update timestamp display

### 4. Order Processing Updates

**Files**: 
- `frontend/pages/cart/process_order.php`
- `backend/pages/orders/update-status.php`
- `frontend/pages/cart/payment-return.php`

**Changes**:
- Remove all `EventBroadcaster` and `EventQueue` calls
- Remove `require_once` statements for event-broadcaster.php and event-queue.php
- Keep existing email and in-app notification logic
- Orders will be picked up by polling automatically

### 5. Payment Success Trigger

**File**: `frontend/pages/cart/payment-return.php`

**Responsibilities**:
- After successful payment processing, trigger an immediate poll on admin order list
- Use a flag/timestamp mechanism to signal new order availability
- Admin polling will detect the new order on next poll cycle

**Implementation Approach**:
Since the payment success happens on the customer side and the admin order list is on a different page/session, we'll rely on the polling mechanism to pick up new orders within the 5-second polling interval. No additional trigger mechanism is needed - the polling system will automatically detect new orders through the `since` timestamp parameter.

## Data Models

### Order List Response Model

```typescript
interface OrderListResponse {
    success: boolean;
    timestamp: string;
    orders: Order[];
    status_counts: StatusCounts;
    total_pages: number;
    current_page: number;
    error?: string;
}

interface Order {
    order_id: number;
    order_date: string;
    customer_name: string;
    customer_contact: string;
    total_items: number;
    total_amount: number;
    payment_method: string;
    delivery_method: string;
    delivery_date?: string;
    delivery_time?: string;
    pickup_date?: string;
    pickup_time?: string;
    status: string;
    is_new: boolean;
}

interface StatusCounts {
    all: number;
    Pending: number;
    Preparing: number;
    "Ready for Delivery": number;
    "Out for Delivery": number;
    "Ready for Pick-up": number;
    "Picked-up": number;
    Delivered: number;
}
```

## Error Handling

### Client-Side Error Handling

1. **Network Errors**:
   - Implement exponential backoff (starting at 5s, max 30s)
   - Display subtle error indicator in UI
   - Automatically retry with backoff
   - Reset backoff on successful request

2. **Authentication Errors (401/403)**:
   - Stop polling immediately
   - Redirect to login page
   - Clear any stored session data

3. **Server Errors (500)**:
   - Log error to console
   - Implement backoff strategy
   - Display error message to user
   - Allow manual refresh

### Server-Side Error Handling

1. **Database Errors**:
   - Return 500 status code with error message
   - Log error details to PHP error log
   - Return empty orders array with error flag

2. **Invalid Parameters**:
   - Return 400 status code with validation errors
   - Provide clear error messages

3. **Authentication Failures**:
   - Return 401 status code
   - Clear session if invalid

## UI/UX Considerations

### Loading Indicators

1. **Subtle Loading State**:
   - Small spinner icon in top-right corner of orders container
   - Fade in/out animation (300ms)
   - Does not block view of existing orders
   - Only visible during active request

2. **Last Update Timestamp**:
   - Display "Last updated: X seconds ago" below filter buttons
   - Update every second
   - Format: "Just now", "30 seconds ago", "2 minutes ago"

### New Order Highlighting

1. **Visual Highlight**:
   - New orders get a subtle yellow background
   - Fade-in animation when added to list
   - Highlight fades out after 3 seconds
   - CSS class: `.order-row-new`

2. **Scroll Behavior**:
   - Maintain current scroll position during refresh
   - Don't auto-scroll to new orders
   - Preserve user's viewing context

3. **Partial DOM Update**:
   - Only the `.orders-container` element is replaced with new HTML
   - Filter buttons, search bar, and page header remain untouched
   - Status count badges are updated separately without full page refresh
   - Preserves all user interactions outside the orders container

### Filter Preservation

1. **State Management**:
   - Preserve active status filter during polling
   - Preserve search query during polling
   - Preserve pagination state
   - Update status count badges in real-time

## Performance Considerations

### Polling Optimization

1. **Conditional Requests**:
   - Send `since` timestamp with each request
   - Server only returns orders created/updated after timestamp
   - Reduces response payload size
   - Improves database query performance

2. **Debouncing**:
   - If user changes filters, cancel current poll
   - Wait 500ms before starting new poll
   - Prevents rapid-fire requests during filter changes

3. **Page Visibility API**:
   - Pause polling when tab is not visible
   - Resume polling when tab becomes visible
   - Reduces unnecessary server load

### Database Optimization

1. **Indexed Queries**:
   - Ensure `order_date` column is indexed
   - Ensure `status` column is indexed
   - Use composite index for common filter combinations

2. **Query Limits**:
   - Always use LIMIT clause
   - Default to 15 orders per page
   - Maximum 100 orders per request

## Testing Strategy

### Unit Tests

1. **JavaScript Polling Client**:
   - Test polling loop initialization
   - Test backoff calculation
   - Test error handling
   - Test DOM updates
   - Test filter preservation

2. **PHP API Endpoint**:
   - Test authentication checks
   - Test filter parameter handling
   - Test query construction
   - Test JSON response format
   - Test error responses

### Integration Tests

1. **End-to-End Polling**:
   - Test complete polling cycle
   - Test new order detection
   - Test status update detection
   - Test filter changes during polling
   - Test pagination during polling

2. **Error Scenarios**:
   - Test network failure handling
   - Test server error handling
   - Test authentication expiry
   - Test database connection loss

### Manual Testing

1. **User Experience**:
   - Verify smooth updates without flicker
   - Verify scroll position preservation
   - Verify new order highlighting
   - Verify loading indicators
   - Verify filter preservation

2. **Performance**:
   - Monitor network traffic
   - Verify polling interval accuracy
   - Check server resource usage
   - Verify database query performance

## Migration Strategy

### Phase 1: Create New Polling System

1. Create new polling JavaScript file
2. Create new API endpoint
3. Test polling system in isolation
4. Verify all functionality works

### Phase 2: Update Order List Page

1. Add polling script to order-list.php
2. Keep SSE system running in parallel
3. Test both systems side-by-side
4. Verify polling provides same functionality

### Phase 3: Remove SSE System

1. Remove SSE script includes from order-list.php
2. Remove EventBroadcaster calls from process_order.php
3. Remove EventBroadcaster calls from update-status.php
4. Test order creation and status updates
5. Verify polling picks up changes

### Phase 4: Cleanup

1. Delete all SSE/WebSocket files
2. Remove unused CSS
3. Remove test files
4. Update documentation
5. Final testing

## Security Considerations

1. **Authentication**:
   - Verify admin session on every API request
   - Return 401 if session invalid
   - Use CSRF tokens for state-changing operations

2. **Input Validation**:
   - Sanitize all filter parameters
   - Validate timestamp format
   - Prevent SQL injection with prepared statements
   - Limit query result sizes

3. **Rate Limiting**:
   - Consider implementing rate limiting on API endpoint
   - Prevent abuse from rapid polling
   - Log suspicious activity

## Future Enhancements

1. **WebSocket Fallback**:
   - Could add WebSocket support as optional enhancement
   - Use polling as fallback for compatibility
   - Detect WebSocket support and upgrade connection

2. **Push Notifications**:
   - Could integrate browser push notifications
   - Notify admins even when tab is not visible
   - Requires user permission

3. **Multi-Page Support**:
   - Extend polling to other admin pages
   - Product dashboard
   - Calendar view
   - Customer notifications

4. **Optimistic Updates**:
   - Update UI immediately on status change
   - Confirm with server response
   - Rollback if server rejects
