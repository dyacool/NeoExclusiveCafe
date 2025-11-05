# Design Document: Realtime Notification System

## Overview

This system implements a realtime notification infrastructure using Server-Sent Events (SSE) to push database updates to connected clients. The architecture consists of three main components:

1. **SSE Event Stream Server** - PHP endpoint maintaining persistent connections
2. **Event Broadcasting Service** - PHP service that detects database changes and broadcasts events
3. **Client-Side Event Handlers** - JavaScript modules that subscribe to events and update the UI

The system is designed for online/production environments only and uses file-based event queuing for simplicity and reliability without requiring additional infrastructure.

## Architecture

### High-Level Architecture

```
┌─────────────────┐
│  Database       │
│  (MySQL)        │
└────────┬────────┘
         │
         │ Triggers/Updates
         ▼
┌─────────────────────────────────┐
│  Event Broadcasting Service     │
│  - Detects DB changes           │
│  - Writes to event queue        │
└────────┬────────────────────────┘
         │
         │ Event Queue (File-based)
         ▼
┌─────────────────────────────────┐
│  SSE Event Stream Server        │
│  - Maintains client connections │
│  - Reads from event queue       │
│  - Broadcasts to subscribers    │
└────────┬────────────────────────┘
         │
         │ HTTP/SSE
         ▼
┌─────────────────────────────────┐
│  Client-Side Event Handlers     │
│  - Subscribes to event streams  │
│  - Updates UI in realtime       │
│  - Handles reconnection         │
└─────────────────────────────────┘
```

### Component Interaction Flow

**Order Status Update Example:**
1. Admin updates order status in database
2. Update script calls `EventBroadcaster::broadcast('order_status', $data)`
3. Event is written to queue file with timestamp and channel
4. SSE server reads queue and sends to all connected clients subscribed to 'order_status' channel
5. Client receives event, filters by order_id/user_id, updates UI

## Components and Interfaces

### 1. SSE Event Stream Server

**Location:** `backend/api/sse-stream.php`

**Purpose:** Maintains persistent HTTP connections with clients and streams events

**Interface:**
```php
// GET /backend/api/sse-stream.php?channels=orders,products,notifications&auth_token=xxx

// Response Headers:
Content-Type: text/event-stream
Cache-Control: no-cache
Connection: keep-alive
X-Accel-Buffering: no

// Event Format:
event: order_status
data: {"order_id": 123, "status": "Ready for Delivery", "customer_id": 45, "timestamp": "2025-11-05 14:30:00"}

event: product_inventory
data: {"product_id": 67, "quantity": 15, "available": true, "product_name": "Coffee Beans"}

event: notification
data: {"notification_id": 89, "user_id": 45, "message": "Your order is ready", "type": "success", "read": false}

event: keepalive
data: {"timestamp": "2025-11-05 14:30:30"}
```

**Key Features:**
- Authentication via session or token
- Channel-based subscriptions (clients specify which event types they want)
- Automatic keepalive every 30 seconds
- Connection timeout after 5 minutes of inactivity
- Maximum 3 connections per user
- Graceful shutdown on client disconnect

**Implementation Details:**
```php
<?php
// Pseudo-code structure
session_start();

// Validate authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

// Get requested channels
$channels = explode(',', $_GET['channels'] ?? 'notifications');
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Disable output buffering
ob_end_flush();

// Track last event ID to avoid duplicates
$last_event_id = $_SERVER['HTTP_LAST_EVENT_ID'] ?? 0;

// Main event loop
$start_time = time();
while (true) {
    // Check connection timeout (5 minutes)
    if (time() - $start_time > 300) {
        break;
    }
    
    // Read events from queue
    $events = EventQueue::getEvents($channels, $last_event_id);
    
    foreach ($events as $event) {
        // Filter events based on user permissions
        if (canUserReceiveEvent($user_id, $user_role, $event)) {
            echo "id: {$event['id']}\n";
            echo "event: {$event['type']}\n";
            echo "data: " . json_encode($event['data']) . "\n\n";
            flush();
            $last_event_id = $event['id'];
        }
    }
    
    // Send keepalive every 30 seconds
    if (time() % 30 == 0) {
        echo "event: keepalive\n";
        echo "data: " . json_encode(['timestamp' => date('Y-m-d H:i:s')]) . "\n\n";
        flush();
    }
    
    // Check if client disconnected
    if (connection_aborted()) {
        break;
    }
    
    sleep(1); // Poll every second
}
?>
```

### 2. Event Broadcasting Service

**Location:** `backend/api/event-broadcaster.php`

**Purpose:** Provides a simple API for broadcasting events to all connected clients

**Interface:**
```php
class EventBroadcaster {
    /**
     * Broadcast an event to all connected clients
     * 
     * @param string $channel Event channel (orders, products, notifications)
     * @param array $data Event payload
     * @param array $filters Optional filters (user_id, role, etc.)
     */
    public static function broadcast($channel, $data, $filters = []) {
        // Implementation
    }
    
    /**
     * Broadcast to specific user(s)
     */
    public static function broadcastToUser($user_id, $channel, $data) {
        // Implementation
    }
    
    /**
     * Broadcast to specific role(s)
     */
    public static function broadcastToRole($role, $channel, $data) {
        // Implementation
    }
}
```

**Usage Examples:**
```php
// In order status update script
EventBroadcaster::broadcast('order_status', [
    'order_id' => $order_id,
    'status' => $new_status,
    'customer_id' => $customer_id,
    'timestamp' => date('Y-m-d H:i:s')
]);

// In checkout completion
EventBroadcaster::broadcastToRole('admin', 'new_order', [
    'order_id' => $order_id,
    'customer_name' => $customer_name,
    'order_type' => $order_type,
    'total' => $total_amount,
    'timestamp' => date('Y-m-d H:i:s')
]);

// In product inventory update
EventBroadcaster::broadcast('product_inventory', [
    'product_id' => $product_id,
    'quantity' => $new_quantity,
    'available' => $new_quantity > 0,
    'product_name' => $product_name
]);

// Send notification to specific user
EventBroadcaster::broadcastToUser($user_id, 'notification', [
    'notification_id' => $notification_id,
    'message' => 'Your order is ready for pickup',
    'type' => 'success',
    'read' => false
]);
```

### 3. Event Queue System

**Location:** `backend/api/event-queue.php`

**Purpose:** File-based queue for storing and retrieving events

**Storage:** `backend/api/events/queue.json` (rotated hourly)

**Structure:**
```json
{
  "events": [
    {
      "id": 1001,
      "channel": "order_status",
      "data": {
        "order_id": 123,
        "status": "Ready for Delivery",
        "customer_id": 45
      },
      "filters": {
        "user_id": 45
      },
      "timestamp": "2025-11-05 14:30:00"
    },
    {
      "id": 1002,
      "channel": "new_order",
      "data": {
        "order_id": 124,
        "customer_name": "John Doe",
        "total": 1250.00
      },
      "filters": {
        "role": "admin"
      },
      "timestamp": "2025-11-05 14:31:15"
    }
  ],
  "last_id": 1002
}
```

**Key Features:**
- Auto-incrementing event IDs
- File locking for concurrent access
- Automatic cleanup of events older than 1 hour
- Queue rotation to prevent file bloat

### 4. Client-Side Event Handler

**Location:** `frontend/assets/js/realtime-notifications.js`

**Purpose:** JavaScript module for subscribing to SSE streams and handling events

**Interface:**
```javascript
class RealtimeNotifications {
    constructor(channels = ['notifications']) {
        this.channels = channels;
        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        this.handlers = {};
    }
    
    /**
     * Connect to SSE stream
     */
    connect() {
        const channelsParam = this.channels.join(',');
        this.eventSource = new EventSource(`/backend/api/sse-stream.php?channels=${channelsParam}`);
        
        this.eventSource.onopen = () => {
            console.log('SSE connection established');
            this.reconnectAttempts = 0;
            this.updateConnectionStatus('connected');
        };
        
        this.eventSource.onerror = (error) => {
            console.error('SSE connection error:', error);
            this.handleReconnect();
        };
        
        // Register event listeners
        this.registerEventListeners();
    }
    
    /**
     * Register handler for specific event type
     */
    on(eventType, handler) {
        if (!this.handlers[eventType]) {
            this.handlers[eventType] = [];
        }
        this.handlers[eventType].push(handler);
    }
    
    /**
     * Disconnect from SSE stream
     */
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
    
    /**
     * Handle reconnection with exponential backoff
     */
    handleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            this.updateConnectionStatus('failed');
            this.showReconnectPrompt();
            return;
        }
        
        this.updateConnectionStatus('reconnecting');
        this.reconnectAttempts++;
        
        const delay = Math.min(this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1), 30000);
        setTimeout(() => this.connect(), delay);
    }
    
    /**
     * Register event listeners for all event types
     */
    registerEventListeners() {
        // Order status updates
        this.eventSource.addEventListener('order_status', (e) => {
            const data = JSON.parse(e.data);
            this.triggerHandlers('order_status', data);
        });
        
        // Product inventory updates
        this.eventSource.addEventListener('product_inventory', (e) => {
            const data = JSON.parse(e.data);
            this.triggerHandlers('product_inventory', data);
        });
        
        // New order notifications
        this.eventSource.addEventListener('new_order', (e) => {
            const data = JSON.parse(e.data);
            this.triggerHandlers('new_order', data);
        });
        
        // General notifications
        this.eventSource.addEventListener('notification', (e) => {
            const data = JSON.parse(e.data);
            this.triggerHandlers('notification', data);
        });
        
        // Keepalive
        this.eventSource.addEventListener('keepalive', (e) => {
            // Just acknowledge, no action needed
        });
    }
    
    /**
     * Trigger all registered handlers for an event type
     */
    triggerHandlers(eventType, data) {
        if (this.handlers[eventType]) {
            this.handlers[eventType].forEach(handler => handler(data));
        }
    }
    
    /**
     * Update connection status indicator
     */
    updateConnectionStatus(status) {
        const indicator = document.getElementById('connection-status');
        if (indicator) {
            indicator.className = `connection-status ${status}`;
            indicator.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        }
    }
    
    /**
     * Show prompt to refresh page after failed reconnection
     */
    showReconnectPrompt() {
        // Show user-friendly message
        const message = 'Connection lost. Please refresh the page to reconnect.';
        // Implementation depends on your notification system
    }
}

// Usage example
const notifications = new RealtimeNotifications(['orders', 'products', 'notifications']);

// Register handlers
notifications.on('order_status', (data) => {
    console.log('Order status updated:', data);
    updateOrderStatusUI(data);
});

notifications.on('product_inventory', (data) => {
    console.log('Product inventory updated:', data);
    updateProductQuantityUI(data);
});

notifications.on('new_order', (data) => {
    console.log('New order received:', data);
    showNewOrderNotification(data);
    playNotificationSound();
});

notifications.on('notification', (data) => {
    console.log('Notification received:', data);
    addNotificationToCenter(data);
    updateNotificationBadge();
});

// Connect
notifications.connect();
```

## Data Models

### Event Queue Entry
```php
[
    'id' => int,              // Auto-incrementing event ID
    'channel' => string,      // Event channel (orders, products, notifications)
    'data' => array,          // Event payload
    'filters' => array,       // Optional filters (user_id, role, etc.)
    'timestamp' => string     // ISO 8601 timestamp
]
```

### Order Status Event
```php
[
    'order_id' => int,
    'status' => string,       // New status
    'customer_id' => int,
    'timestamp' => string
]
```

### Product Inventory Event
```php
[
    'product_id' => int,
    'quantity' => int,
    'available' => bool,
    'product_name' => string
]
```

### New Order Event
```php
[
    'order_id' => int,
    'customer_name' => string,
    'order_type' => string,   // 'delivery' or 'pickup'
    'total' => float,
    'timestamp' => string
]
```

### Notification Event
```php
[
    'notification_id' => int,
    'user_id' => int,
    'message' => string,
    'type' => string,         // 'info', 'warning', 'success', 'error'
    'read' => bool,
    'timestamp' => string
]
```

### Database Schema Addition

**New Table: `notifications`**
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);
```

## Error Handling

### Connection Errors
- **Client disconnects**: SSE server detects via `connection_aborted()` and cleans up
- **Network timeout**: Client automatically reconnects with exponential backoff
- **Authentication failure**: Return 401 status, client redirects to login

### Event Broadcasting Errors
- **File lock timeout**: Retry up to 3 times with 100ms delay
- **Queue file corruption**: Create new queue file, log error
- **Invalid event data**: Log error, skip event, continue processing

### Client-Side Errors
- **EventSource not supported**: Show fallback message, suggest modern browser
- **Reconnection failure**: After 5 attempts, prompt user to refresh page
- **Handler exception**: Catch and log, don't break event processing

## Testing Strategy

### Unit Tests
1. **EventBroadcaster Tests**
   - Test event writing to queue
   - Test filtering logic
   - Test concurrent access handling

2. **EventQueue Tests**
   - Test event retrieval by channel
   - Test event cleanup
   - Test file rotation

3. **Client Handler Tests**
   - Test event parsing
   - Test reconnection logic
   - Test handler registration

### Integration Tests
1. **End-to-End Flow**
   - Broadcast event → SSE server → Client receives
   - Test with multiple concurrent clients
   - Test channel filtering

2. **Authentication Tests**
   - Unauthenticated access blocked
   - User receives only authorized events
   - Admin receives all events

3. **Performance Tests**
   - 50 concurrent connections
   - 100 events per minute
   - Memory usage monitoring

### Manual Testing Scenarios
1. **Order Status Update**
   - Admin updates order status
   - Customer sees update in realtime
   - Rider sees delivery assignment

2. **Product Inventory**
   - Admin updates product quantity
   - All users on product dashboard see update
   - Out-of-stock indicator appears

3. **New Order Notification**
   - Customer completes checkout
   - Admin receives notification with sound
   - Notification badge updates

4. **Connection Resilience**
   - Disconnect network
   - Verify reconnection attempts
   - Verify missed events are caught up

## Integration Points

### 1. Order Status Updates
**Files to modify:**
- `backend/api/auto-update-order-status.php`
- Any admin order management scripts

**Integration:**
```php
// After updating order status
$update_sql = "UPDATE orders SET status = ? WHERE order_id = ?";
// ... execute update ...

// Broadcast event
require_once 'event-broadcaster.php';
EventBroadcaster::broadcast('order_status', [
    'order_id' => $order_id,
    'status' => $new_status,
    'customer_id' => $customer_id,
    'timestamp' => date('Y-m-d H:i:s')
]);
```

### 2. Checkout Completion
**Files to modify:**
- `frontend/pages/cart/checkout.php`
- `frontend/pages/cart/availtoday-checkout.php`
- `frontend/pages/cart/process-availtoday-checkout.php`

**Integration:**
```php
// After order is created successfully
require_once '../../../backend/api/event-broadcaster.php';
EventBroadcaster::broadcastToRole('admin', 'new_order', [
    'order_id' => $order_id,
    'customer_name' => $customer_name,
    'order_type' => $delivery_method,
    'total' => $total_amount,
    'timestamp' => date('Y-m-d H:i:s')
]);
```

### 3. Product Inventory Updates
**Files to modify:**
- `backend/pages/products/update-sdo-quantities.php`
- Any product management scripts

**Integration:**
```php
// After updating product quantity
require_once '../../api/event-broadcaster.php';
EventBroadcaster::broadcast('product_inventory', [
    'product_id' => $product_id,
    'quantity' => $new_quantity,
    'available' => $new_quantity > 0,
    'product_name' => $product_name
]);
```

### 4. Frontend Pages
**Pages to integrate:**
- Product dashboard: Subscribe to `product_inventory` channel
- Order tracking: Subscribe to `order_status` channel
- Admin panel: Subscribe to `new_order` and `order_status` channels
- All pages: Subscribe to `notifications` channel

**Integration:**
```html
<script src="/frontend/assets/js/realtime-notifications.js"></script>
<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const notifications = new RealtimeNotifications(['products', 'notifications']);
    
    notifications.on('product_inventory', (data) => {
        // Update product quantity in UI
        const productElement = document.querySelector(`[data-product-id="${data.product_id}"]`);
        if (productElement) {
            productElement.querySelector('.quantity').textContent = data.quantity;
            productElement.classList.toggle('out-of-stock', !data.available);
        }
    });
    
    notifications.connect();
});
</script>
```

## Security Considerations

1. **Authentication**: All SSE connections require valid session
2. **Authorization**: Events filtered based on user role and ownership
3. **Rate Limiting**: Maximum 3 concurrent connections per user
4. **Data Filtering**: Sensitive data excluded from broadcasts
5. **Connection Timeout**: Automatic disconnect after 5 minutes idle
6. **File Permissions**: Event queue files readable only by web server user

## Performance Considerations

1. **Event Queue Cleanup**: Automatic deletion of events older than 1 hour
2. **File Locking**: Minimal lock duration to prevent blocking
3. **Polling Interval**: 1-second polling balances responsiveness and CPU usage
4. **Connection Limits**: Per-user limits prevent resource exhaustion
5. **Event Batching**: Multiple events sent in single flush when available

## Deployment Notes

1. **File Permissions**: Ensure `backend/api/events/` directory is writable by web server
2. **PHP Configuration**: Increase `max_execution_time` for SSE endpoint (set to 0 or 600)
3. **Web Server**: Disable output buffering for SSE endpoint
4. **HTTPS**: Required for reliable SSE connections in production
5. **Monitoring**: Log connection counts and event throughput for capacity planning
