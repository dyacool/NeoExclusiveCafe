# Design Document

## Overview

This design addresses three critical bugs in the payment and realtime notification systems: a white page error on payment return, non-functional SSE connections for realtime updates, and console errors for non-logged-in users. The solution involves systematic debugging, error logging enhancement, SSE connection verification, and conditional JavaScript initialization.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Payment Flow                              │
├─────────────────────────────────────────────────────────────┤
│  PayMongo Gateway → payment-return.php → Order Creation     │
│                          ↓                                   │
│                   Event Broadcasting                         │
│                          ↓                                   │
│                   Event Queue Storage                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              Realtime Notification Flow                      │
├─────────────────────────────────────────────────────────────┤
│  Event Queue → sse-stream.php → SSE Connection → Client     │
│                                       ↓                      │
│                              order-list.php                  │
│                                       ↓                      │
│                           Refresh Order List                 │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│           Customer Navigation Initialization                 │
├─────────────────────────────────────────────────────────────┤
│  Page Load → Check Session → Initialize Notifications       │
│                    ↓                                         │
│              (Only if logged in)                             │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Payment Return Error Debugging

**Component:** `frontend/pages/cart/payment-return.php`

**Current Issues:**
- White page displayed on successful payment return
- No visible error messages to users
- Insufficient error logging for debugging

**Design Solution:**

```php
// Enhanced error handling wrapper
try {
    // Enable detailed error logging
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../../logs/payment_errors.log');
    
    // Log all incoming parameters
    error_log("[PAYMENT-RETURN] === REQUEST START ===");
    error_log("[PAYMENT-RETURN] GET: " . json_encode($_GET));
    error_log("[PAYMENT-RETURN] Session pending_payment: " . json_encode($_SESSION['pending_payment'] ?? 'NOT SET'));
    
    // Validate session data exists
    if (!isset($_SESSION['pending_payment'])) {
        throw new Exception('No pending payment found in session');
    }
    
    // Validate database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
    }
    
    // Continue with order processing...
    
} catch (Exception $e) {
    // Log the full error with stack trace
    error_log("[PAYMENT-RETURN] ERROR: " . $e->getMessage());
    error_log("[PAYMENT-RETURN] Stack trace: " . $e->getTraceAsString());
    
    // Display user-friendly error page instead of white screen
    displayErrorPage($e->getMessage());
    exit();
}

function displayErrorPage($message) {
    // Show styled error page with support contact info
    echo "<!DOCTYPE html>...";
}
```

**Diagnostic Steps:**
1. Check PHP error logs at `logs/payment_errors.log`
2. Verify session data persistence across redirects
3. Validate database connection before queries
4. Test duplicate payment prevention logic
5. Verify event broadcasting is not blocking execution

### 2. SSE Connection Verification

**Component:** `backend/api/sse-stream.php` and `backend/pages/orders/order-list.php`

**Current Issues:**
- SSE connection not establishing
- No events reaching the client
- Silent failures with no error messages

**Design Solution:**

**Server-Side (sse-stream.php):**
```php
// Add connection diagnostics
error_log("[SSE] === CONNECTION ATTEMPT ===");
error_log("[SSE] User ID: " . ($userId ?? 'NOT SET'));
error_log("[SSE] User Role: " . ($userRole ?? 'NOT SET'));
error_log("[SSE] Requested Channels: " . implode(', ', $channels));
error_log("[SSE] Event Queue Status: " . (EventQueue::init() ? 'OK' : 'FAILED'));

// Test event queue accessibility
$lastEventId = EventQueue::getLastEventId();
error_log("[SSE] Last Event ID in queue: $lastEventId");

// Send diagnostic event immediately after connection
echo "event: diagnostic\n";
echo "data: " . json_encode([
    'status' => 'connected',
    'user_id' => $userId,
    'channels' => $channels,
    'last_event_id' => $lastEventId,
    'timestamp' => date('Y-m-d H:i:s')
]) . "\n\n";
flush();
```

**Client-Side (order-list.php):**
```javascript
// Enhanced SSE connection with detailed logging
const realtimeNotifications = new RealtimeNotifications(['new_order']);

// Add connection state tracking
realtimeNotifications.on('connected', function(data) {
    console.log('[Order List] ✓ SSE Connected:', data);
    showNotification('Realtime updates active', 'success');
});

realtimeNotifications.on('diagnostic', function(data) {
    console.log('[Order List] Diagnostic data:', data);
});

realtimeNotifications.on('error', function(error) {
    console.error('[Order List] ✗ SSE Error:', error);
    showNotification('Realtime updates unavailable', 'warning');
});

realtimeNotifications.on('keepalive', function(data) {
    console.log('[Order List] Keepalive received:', data.timestamp);
});

// Add retry logic with exponential backoff
let retryCount = 0;
const maxRetries = 3;

realtimeNotifications.on('disconnect', function() {
    if (retryCount < maxRetries) {
        const delay = Math.pow(2, retryCount) * 1000; // 1s, 2s, 4s
        console.log(`[Order List] Reconnecting in ${delay}ms (attempt ${retryCount + 1}/${maxRetries})`);
        setTimeout(() => {
            retryCount++;
            realtimeNotifications.connect();
        }, delay);
    } else {
        console.error('[Order List] Max reconnection attempts reached');
        showNotification('Unable to establish realtime connection', 'error');
    }
});
```

**Diagnostic Test Page:**
Use existing `backend/api/test-sse-client.html` to verify:
1. SSE connection establishes successfully
2. Events are received from the queue
3. Connection stays alive with keepalive messages
4. Reconnection works after disconnect

### 3. Event Broadcasting Verification

**Component:** Event broadcasting in order creation paths

**Current Status:**
- `payment-return.php` - ✓ Has broadcasting code
- `process_order.php` (COD) - ✓ Has broadcasting code
- `update-status.php` - ✓ Has broadcasting code
- `get-done-orders.php` - ✓ Has broadcasting code

**Design Solution:**

Add verification logging to confirm events are queued:

```php
// In payment-return.php after order creation
$eventId = EventBroadcaster::broadcastNewOrder(
    $order_id_created,
    $customer_name,
    $delivery_method,
    $total_amount,
    [
        'delivery_date' => $delivery_date,
        'pickup_date' => $pickup_date,
        'delivery_time' => $delivery_time,
        'pickup_time' => $pickup_time
    ]
);

if ($eventId !== false) {
    error_log("[PAYMENT-RETURN] ✓ Event broadcast successful. Event ID: $eventId");
    
    // Verify event was added to queue
    $queuedEvents = EventQueue::getEvents(['new_order'], $eventId - 1);
    if (!empty($queuedEvents)) {
        error_log("[PAYMENT-RETURN] ✓ Event verified in queue");
    } else {
        error_log("[PAYMENT-RETURN] ✗ WARNING: Event not found in queue after broadcast");
    }
} else {
    error_log("[PAYMENT-RETURN] ✗ ERROR: Event broadcast failed");
}
```

**Test Procedure:**
1. Use `backend/api/test-event-broadcaster.php` to manually broadcast test events
2. Monitor `backend/api/events/queue.json` to verify events are stored
3. Use `backend/api/test-sse-client.html` to verify events are received
4. Check PHP error logs for broadcast failures

### 4. Console Error Fix for Non-Logged-In Users

**Component:** `frontend/user-includes/navbar/customer-navigation.php`

**Current Issue:**
- JavaScript tries to initialize notification elements for all users
- Causes "Notification element not found" error for non-logged-in users
- Retry loop continues indefinitely

**Design Solution:**

```php
// In customer-navigation.php, wrap notification initialization in PHP session check
<?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
<script>
// Only initialize notifications for logged-in users
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Customer Nav] User logged in, initializing notifications');
    
    // Wait for notification element with timeout
    let retryCount = 0;
    const maxRetries = 3;
    
    function waitForNotificationElement() {
        const notificationElement = document.getElementById('notification-container');
        
        if (notificationElement) {
            console.log('[Customer Nav] ✓ Notification element found');
            initializeRealtimeNotifications();
        } else if (retryCount < maxRetries) {
            retryCount++;
            console.log(`[Customer Nav] Notification element not found, retry ${retryCount}/${maxRetries}`);
            setTimeout(waitForNotificationElement, 100);
        } else {
            console.warn('[Customer Nav] Notification element not found after max retries');
        }
    }
    
    waitForNotificationElement();
});
</script>
<?php else: ?>
<script>
// Non-logged-in users don't need notification initialization
console.log('[Customer Nav] User not logged in, skipping notification initialization');
</script>
<?php endif; ?>
```

## Data Models

### Error Log Entry Structure

```json
{
    "timestamp": "2025-11-05 14:30:45",
    "level": "ERROR",
    "component": "payment-return",
    "message": "Database connection failed",
    "context": {
        "user_id": 123,
        "payment_id": "pi_abc123",
        "session_data": {...}
    },
    "stack_trace": "..."
}
```

### SSE Diagnostic Event

```json
{
    "event": "diagnostic",
    "data": {
        "status": "connected",
        "user_id": 1,
        "channels": ["new_order", "order_status"],
        "last_event_id": 42,
        "queue_status": "ok",
        "timestamp": "2025-11-05 14:30:45"
    }
}
```

### Event Queue Verification

```json
{
    "events": [
        {
            "id": 42,
            "channel": "new_order",
            "data": {
                "order_id": 123,
                "customer_name": "John Doe",
                "order_type": "Delivery",
                "total": 500.00
            },
            "filters": {"role": "admin"},
            "timestamp": "2025-11-05 14:30:45"
        }
    ],
    "last_id": 42
}
```

## Error Handling

### Payment Return Error Handling

**Error Categories:**
1. **Session Errors** - Missing pending_payment data
2. **Database Errors** - Connection failures, query errors
3. **Validation Errors** - Invalid payment data, duplicate orders
4. **Broadcasting Errors** - Event queue failures

**Error Response Strategy:**
- Log all errors with full context
- Display user-friendly error pages (no white screens)
- Provide support contact information
- Preserve session data for retry attempts

### SSE Connection Error Handling

**Error Categories:**
1. **Authentication Errors** - User not logged in
2. **Connection Errors** - Network failures, timeouts
3. **Queue Errors** - Event queue inaccessible

**Error Response Strategy:**
- Retry with exponential backoff (max 3 attempts)
- Show user-friendly notifications
- Degrade gracefully (manual refresh still works)
- Log all connection attempts and failures

### Event Broadcasting Error Handling

**Error Categories:**
1. **Queue Write Errors** - File permission issues, disk full
2. **Lock Timeout Errors** - Queue file locked by another process
3. **Validation Errors** - Invalid channel or data

**Error Response Strategy:**
- Log broadcast failures but don't block order creation
- Continue with order processing even if broadcast fails
- Provide fallback notification methods (email, manual refresh)

## Testing Strategy

### 1. Payment Return Debugging Tests

**Test Cases:**
1. **Successful Payment Flow**
   - Place order with online payment
   - Complete payment on PayMongo
   - Verify redirect to payment-return.php
   - Verify order created in database
   - Verify no white page displayed

2. **Duplicate Payment Prevention**
   - Complete payment once
   - Attempt to process same payment_id again
   - Verify duplicate is detected and skipped
   - Verify redirect to existing order

3. **Session Data Validation**
   - Clear session before payment return
   - Verify error handling and redirect
   - Verify error logged with context

4. **Database Connection Failure**
   - Simulate database connection failure
   - Verify error page displayed
   - Verify error logged with details

### 2. SSE Connection Tests

**Test Cases:**
1. **Connection Establishment**
   - Open order-list.php as admin
   - Verify SSE connection established within 5 seconds
   - Verify "connected" event received
   - Verify diagnostic data logged to console

2. **Event Reception**
   - Establish SSE connection
   - Use test-event-broadcaster.php to send test event
   - Verify event received by client
   - Verify event logged to console

3. **Keepalive Messages**
   - Establish SSE connection
   - Wait 30 seconds
   - Verify keepalive event received
   - Verify connection stays alive

4. **Reconnection Logic**
   - Establish SSE connection
   - Simulate connection drop
   - Verify automatic reconnection attempt
   - Verify exponential backoff timing

5. **Max Retry Limit**
   - Establish SSE connection
   - Simulate persistent connection failures
   - Verify max 3 retry attempts
   - Verify error notification displayed

### 3. Event Broadcasting Tests

**Test Cases:**
1. **Order Creation Broadcasting**
   - Create order via payment-return.php
   - Verify event broadcast logged
   - Verify event added to queue
   - Verify event received by connected clients

2. **COD Order Broadcasting**
   - Create COD order via process_order.php
   - Verify event broadcast logged
   - Verify event added to queue
   - Verify event received by connected clients

3. **Order Status Update Broadcasting**
   - Update order status via update-status.php
   - Verify event broadcast logged
   - Verify event received by customer

4. **Queue Verification**
   - Broadcast multiple events
   - Check backend/api/events/queue.json
   - Verify all events stored correctly
   - Verify event IDs increment properly

### 4. Console Error Tests

**Test Cases:**
1. **Logged-In User**
   - Log in as customer
   - Navigate to any page
   - Open browser console
   - Verify no "Notification element not found" errors
   - Verify notifications initialize successfully

2. **Non-Logged-In User**
   - Log out or use incognito mode
   - Navigate to any page
   - Open browser console
   - Verify no "Notification element not found" errors
   - Verify notification initialization skipped

3. **Session Timeout**
   - Log in as customer
   - Wait for session timeout
   - Refresh page
   - Verify no console errors
   - Verify graceful degradation

### 5. Integration Tests

**Test Cases:**
1. **End-to-End Order Flow**
   - Place order with online payment
   - Complete payment
   - Verify order created
   - Verify event broadcast
   - Verify admin receives realtime notification
   - Verify order appears in order-list.php
   - Verify no errors in console or logs

2. **Concurrent Orders**
   - Place multiple orders simultaneously
   - Verify all orders created correctly
   - Verify all events broadcast
   - Verify all notifications received
   - Verify no race conditions or duplicates

## Diagnostic Tools

### 1. Test SSE Client
**File:** `backend/api/test-sse-client.html`

**Usage:**
- Open in browser while logged in as admin
- Verify connection status
- Monitor received events
- Test reconnection behavior

### 2. Test Event Broadcaster
**File:** `backend/api/test-event-broadcaster.php`

**Usage:**
- Access via browser or curl
- Manually broadcast test events
- Verify events added to queue
- Test different event types and channels

### 3. Event Queue Inspector
**File:** `backend/api/events/queue.json`

**Usage:**
- View raw queue data
- Verify events are stored
- Check event IDs and timestamps
- Monitor queue size and cleanup

### 4. PHP Error Logs
**Files:**
- `logs/payment_errors.log` - Payment-specific errors
- `logs/php_errors.log` - General PHP errors

**Usage:**
- Monitor for errors during testing
- Check for stack traces and context
- Verify error logging is working

## Implementation Notes

### Priority Order
1. **Fix payment-return.php white page** (Critical - blocks orders)
2. **Verify SSE connection** (High - affects admin UX)
3. **Fix console error** (Medium - cosmetic but annoying)
4. **Verify event broadcasting** (Medium - already implemented, just needs verification)

### Dependencies
- PHP error logging must be enabled
- Event queue directory must be writable
- Session cookies must persist across redirects
- Browser must support EventSource API for SSE

### Performance Considerations
- Error logging should not impact page load time
- SSE connections should timeout after 5 minutes
- Event queue should cleanup old events (1 hour TTL)
- Retry logic should use exponential backoff to avoid hammering server

### Security Considerations
- Error messages should not expose sensitive data
- SSE connections must verify authentication
- Event filters must prevent unauthorized access
- Payment IDs must be validated before processing
