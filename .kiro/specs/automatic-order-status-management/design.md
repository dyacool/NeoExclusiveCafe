# Design Document: Automatic Order Status Management

## Overview

This design implements an intelligent automatic order status management system that updates order statuses based on delivery/pickup dates and order types. The system includes a toggle switch for enabling/disabling automatic updates, priority-based order queueing, overdue detection, and seamless integration with existing email notifications.

The solution consists of:
1. A new database table for storing auto-status preferences
2. A background cron job/scheduled task for automatic status updates
3. UI enhancements to the order-list.php page
4. API endpoints for toggle state management
5. Enhanced order sorting logic with priority queueing

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Order List UI                            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Filter Controls  │  [Toggle Auto-Status] Switch     │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Priority-Sorted Order Table                   │   │
│  │  - Overdue Orders (Red Badge)                        │   │
│  │  - Due Today Orders (Yellow Badge)                   │   │
│  │  - Due Tomorrow Orders (Orange Badge)                │   │
│  │  - Future Orders                                     │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   API Layer                                  │
│  - toggle-auto-status.php (Save/Load Toggle State)          │
│  - auto-update-order-status.php (Cron Job Endpoint)         │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  Database Layer                              │
│  - order_status_settings (New Table)                         │
│  - orders (Existing Table)                                   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Email Notification System                       │
│  - Existing mailer.php integration                           │
│  - Existing Notification class integration                   │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Database Schema

#### New Table: `order_status_settings`

```sql
CREATE TABLE IF NOT EXISTS `order_status_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) DEFAULT NULL,
  `auto_status_enabled` TINYINT(1) DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Fields:**
- `id`: Primary key
- `admin_id`: Foreign key to admin user (NULL for global setting)
- `auto_status_enabled`: Boolean flag (0 = manual, 1 = automatic)
- `updated_at`: Timestamp of last update

**Design Decision:** Using a single global setting (admin_id = NULL) for simplicity. Can be extended to per-admin preferences if needed.

### 2. UI Components

#### Toggle Switch Component

**Location:** `backend/pages/orders/order-list.php`

**HTML Structure:**
```html
<div class="controls-section">
    <div class="filter-group">
        <!-- Existing filter buttons -->
    </div>
    <div class="auto-status-toggle-container">
        <label class="toggle-label">
            <span class="toggle-text">Toggle auto-status</span>
            <div class="toggle-switch">
                <input type="checkbox" id="auto-status-toggle" class="toggle-input">
                <span class="toggle-slider"></span>
            </div>
        </label>
    </div>
</div>
```

**CSS Styling:**
```css
.auto-status-toggle-container {
    display: flex;
    align-items: center;
    margin-left: auto;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 24px;
}

.toggle-input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

.toggle-input:checked + .toggle-slider {
    background-color: #667eea;
}

.toggle-input:checked + .toggle-slider:before {
    transform: translateX(26px);
}
```

**JavaScript Handler:**
```javascript
document.getElementById('auto-status-toggle').addEventListener('change', function() {
    const enabled = this.checked;
    
    fetch('toggle-auto-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ enabled: enabled })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Auto-status ' + (enabled ? 'enabled' : 'disabled'));
        } else {
            // Revert toggle on error
            this.checked = !enabled;
            showNotification('Error updating setting', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        this.checked = !enabled;
        showNotification('Error updating setting', 'error');
    });
});
```

### 3. API Endpoints

#### A. Toggle Auto-Status API

**File:** `backend/pages/orders/toggle-auto-status.php`

**Purpose:** Save and retrieve auto-status toggle preference

**Request:**
```json
POST /backend/pages/orders/toggle-auto-status.php
{
  "enabled": true
}
```

**Response:**
```json
{
  "success": true,
  "enabled": true,
  "message": "Auto-status setting updated"
}
```

**Implementation:**
```php
<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../admin-includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $enabled = isset($input['enabled']) ? (int)$input['enabled'] : 0;
    
    // Use global setting (admin_id = NULL)
    $sql = "INSERT INTO order_status_settings (admin_id, auto_status_enabled) 
            VALUES (NULL, ?) 
            ON DUPLICATE KEY UPDATE auto_status_enabled = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $enabled, $enabled);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'enabled' => (bool)$enabled,
            'message' => 'Auto-status setting updated'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update setting'
        ]);
    }
    
    mysqli_stmt_close($stmt);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get current setting
    $sql = "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'enabled' => (bool)$row['auto_status_enabled']
        ]);
    } else {
        // Default to disabled
        echo json_encode([
            'success' => true,
            'enabled' => false
        ]);
    }
}
?>
```

#### B. Auto-Update Order Status API

**File:** `backend/api/auto-update-order-status.php`

**Purpose:** Cron job endpoint that automatically updates order statuses based on due dates

**Request:**
```
GET /backend/api/auto-update-order-status.php
```

**Response:**
```json
{
  "success": true,
  "updated_count": 5,
  "orders_updated": [123, 124, 125, 126, 127]
}
```

**Implementation Logic:**
1. Check if auto-status is enabled
2. Query orders that need status updates
3. Update statuses based on delivery method and due date
4. Trigger email notifications for each update
5. Return summary of updates

### 4. Order Sorting and Priority Logic

#### Enhanced SQL Query

**File:** `backend/pages/orders/order-list.php`

**Sorting Logic:**
```sql
SELECT *,
    CASE 
        -- Overdue orders (highest priority)
        WHEN (
            (delivery_method = 'Delivery' AND delivery_date < CURDATE()) OR
            (delivery_method = 'Pick-up' AND pickup_date < CURDATE())
        ) AND status NOT IN ('Delivered', 'Picked-up') THEN 1
        
        -- Due today orders
        WHEN (
            (delivery_method = 'Delivery' AND delivery_date = CURDATE()) OR
            (delivery_method = 'Pick-up' AND pickup_date = CURDATE())
        ) THEN 2
        
        -- Due tomorrow orders
        WHEN (
            (delivery_method = 'Delivery' AND delivery_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)) OR
            (delivery_method = 'Pick-up' AND pickup_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY))
        ) THEN 3
        
        -- Future orders
        ELSE 4
    END AS priority,
    
    COALESCE(delivery_date, pickup_date) AS due_date,
    COALESCE(delivery_time, pickup_time, '00:00:00') AS due_time
    
FROM orders
WHERE [existing filters]
ORDER BY 
    priority ASC,
    due_date ASC,
    due_time ASC,
    order_date DESC
LIMIT ? OFFSET ?
```

**Design Decision:** Using CASE statement for priority calculation ensures efficient sorting at the database level rather than in PHP.

### 5. Automatic Status Update Logic

#### Status Transition Rules

**For Pickup Orders:**
```
Confirmed → Preparing (when due_date = tomorrow)
Preparing → Ready for Pick-up (when due_date = today)
Ready for Pick-up → Picked-up (manual only)
```

**For Delivery Orders:**
```
Confirmed → Preparing (when due_date = tomorrow)
Preparing → Ready for Delivery (when due_date = today)
Ready for Delivery → Out for Delivery (manual only)
Out for Delivery → Delivered (manual only, by rider)
```

#### Cron Job Implementation

**File:** `backend/api/auto-update-order-status.php`

**Pseudo-code:**
```php
// Check if auto-status is enabled
if (!isAutoStatusEnabled()) {
    exit('Auto-status is disabled');
}

// Get current date/time
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Update pickup orders due tomorrow to "Preparing"
$sql = "UPDATE orders 
        SET status = 'Preparing' 
        WHERE delivery_method = 'Pick-up' 
        AND pickup_date = ? 
        AND status = 'Confirmed'";
executeAndNotify($sql, [$tomorrow]);

// Update pickup orders due today to "Ready for Pick-up"
$sql = "UPDATE orders 
        SET status = 'Ready for Pick-up' 
        WHERE delivery_method = 'Pick-up' 
        AND pickup_date = ? 
        AND status = 'Preparing'";
executeAndNotify($sql, [$today]);

// Update delivery orders due tomorrow to "Preparing"
$sql = "UPDATE orders 
        SET status = 'Preparing' 
        WHERE delivery_method = 'Delivery' 
        AND delivery_date = ? 
        AND status = 'Confirmed'";
executeAndNotify($sql, [$tomorrow]);

// Update delivery orders due today to "Ready for Delivery"
$sql = "UPDATE orders 
        SET status = 'Ready for Delivery' 
        WHERE delivery_method = 'Delivery' 
        AND delivery_date = ? 
        AND status = 'Preparing'";
executeAndNotify($sql, [$today]);

function executeAndNotify($sql, $params) {
    // Execute update
    // For each updated order:
    //   - Send email notification
    //   - Create in-app notification
    //   - Log activity
}
```

**Cron Schedule:** Run every hour during business hours (e.g., 6 AM - 10 PM)

**Windows Task Scheduler Command:**
```
php C:\path\to\backend\api\auto-update-order-status.php
```

**Schedule:** Hourly between 06:00 and 22:00

## Data Models

### Order Status Settings Model

```php
class OrderStatusSettings {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function isAutoStatusEnabled() {
        $sql = "SELECT auto_status_enabled FROM order_status_settings 
                WHERE admin_id IS NULL LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return (bool)$row['auto_status_enabled'];
        }
        
        return false; // Default to disabled
    }
    
    public function setAutoStatus($enabled) {
        $enabled_int = $enabled ? 1 : 0;
        $sql = "INSERT INTO order_status_settings (admin_id, auto_status_enabled) 
                VALUES (NULL, ?) 
                ON DUPLICATE KEY UPDATE auto_status_enabled = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $enabled_int, $enabled_int);
        
        return mysqli_stmt_execute($stmt);
    }
}
```

### Order Priority Model

```php
class OrderPriority {
    const OVERDUE = 1;
    const DUE_TODAY = 2;
    const DUE_TOMORROW = 3;
    const FUTURE = 4;
    
    public static function calculatePriority($order) {
        $due_date = $order['delivery_method'] === 'Delivery' 
            ? $order['delivery_date'] 
            : $order['pickup_date'];
        
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // Overdue check
        if ($due_date < $today && 
            !in_array($order['status'], ['Delivered', 'Picked-up'])) {
            return self::OVERDUE;
        }
        
        // Due today
        if ($due_date === $today) {
            return self::DUE_TODAY;
        }
        
        // Due tomorrow
        if ($due_date === $tomorrow) {
            return self::DUE_TOMORROW;
        }
        
        return self::FUTURE;
    }
    
    public static function getPriorityBadge($priority) {
        switch ($priority) {
            case self::OVERDUE:
                return '<span class="warning-badge critical">OVERDUE</span>';
            case self::DUE_TODAY:
                return '<span class="warning-badge today">DUE TODAY</span>';
            case self::DUE_TOMORROW:
                return '<span class="warning-badge urgent">DUE TOMORROW</span>';
            default:
                return '';
        }
    }
}
```

## Error Handling

### Toggle State Errors

1. **Database Connection Failure**
   - Fallback: Keep current toggle state
   - User Message: "Unable to save setting. Please try again."
   - Log: Error details to error log

2. **Invalid Input**
   - Validation: Ensure boolean value
   - Response: 400 Bad Request with error message

### Auto-Update Errors

1. **Cron Job Failure**
   - Logging: Log all errors to dedicated log file
   - Notification: Email admin on repeated failures
   - Fallback: Manual status updates still available

2. **Email Notification Failure**
   - Behavior: Continue with status update
   - Logging: Log email failure separately
   - User Impact: Status updated but customer not notified

3. **Database Lock/Timeout**
   - Retry: Attempt update 3 times with exponential backoff
   - Fallback: Skip order and continue with next
   - Logging: Log skipped orders for manual review

### UI Error Handling

1. **Toggle API Unreachable**
   - Behavior: Revert toggle to previous state
   - User Message: "Connection error. Please check your internet."

2. **Page Load Failure**
   - Fallback: Load toggle state from last known value
   - Default: Disabled if no previous state

## Testing Strategy

### Unit Tests

1. **OrderStatusSettings Class**
   - Test: isAutoStatusEnabled() returns correct value
   - Test: setAutoStatus() updates database correctly
   - Test: Default value is false when no setting exists

2. **OrderPriority Class**
   - Test: calculatePriority() for overdue orders
   - Test: calculatePriority() for due today orders
   - Test: calculatePriority() for due tomorrow orders
   - Test: calculatePriority() for future orders
   - Test: getPriorityBadge() returns correct HTML

3. **Status Update Logic**
   - Test: Pickup orders transition correctly
   - Test: Delivery orders transition correctly
   - Test: Manual statuses are not auto-updated
   - Test: Completed orders are not modified

### Integration Tests

1. **Toggle API**
   - Test: POST request saves setting
   - Test: GET request retrieves setting
   - Test: Unauthorized access is blocked
   - Test: Invalid input is rejected

2. **Auto-Update Cron Job**
   - Test: Updates only when enabled
   - Test: Sends email notifications
   - Test: Creates in-app notifications
   - Test: Logs activity correctly
   - Test: Handles multiple orders in batch

3. **Order Sorting**
   - Test: Overdue orders appear first
   - Test: Due today orders appear second
   - Test: Due tomorrow orders appear third
   - Test: Future orders appear last
   - Test: Same-priority orders sorted by time

### Manual Testing Checklist

1. **Toggle Functionality**
   - [ ] Toggle switch appears in correct location
   - [ ] Toggle state persists across page refreshes
   - [ ] Toggle state saves successfully
   - [ ] Error message displays on save failure

2. **Automatic Status Updates**
   - [ ] Orders due tomorrow change to "Preparing"
   - [ ] Orders due today change to "Ready for Pickup/Delivery"
   - [ ] Manual statuses remain unchanged
   - [ ] Email notifications are sent
   - [ ] In-app notifications are created

3. **Order Priority Display**
   - [ ] Overdue badge displays correctly (red)
   - [ ] Due today badge displays correctly (yellow)
   - [ ] Due tomorrow badge displays correctly (orange)
   - [ ] Orders sorted by priority
   - [ ] Filtering maintains priority sorting

4. **Manual Override**
   - [ ] Admin can manually change status when toggle is on
   - [ ] Admin can manually change status when toggle is off
   - [ ] Manual changes trigger notifications
   - [ ] Manual changes are logged

## Performance Considerations

1. **Database Indexing**
   - Add index on `orders.delivery_date`
   - Add index on `orders.pickup_date`
   - Add index on `orders.status`
   - Composite index on `(delivery_method, delivery_date, status)`

2. **Cron Job Optimization**
   - Batch updates in single transaction
   - Limit to orders within 2-day window
   - Use prepared statements for efficiency

3. **UI Performance**
   - Cache toggle state in localStorage
   - Debounce toggle switch changes
   - Lazy load order details on row click

## Security Considerations

1. **Authentication**
   - Verify admin session for all API calls
   - Use CSRF tokens for toggle updates

2. **Authorization**
   - Only admins can access toggle functionality
   - Cron job uses internal authentication

3. **Input Validation**
   - Sanitize all user inputs
   - Validate date formats
   - Prevent SQL injection with prepared statements

4. **Audit Trail**
   - Log all automatic status changes
   - Log all manual status changes
   - Log toggle state changes with timestamp

## Migration Plan

1. **Database Migration**
   - Create `order_status_settings` table
   - Add indexes to `orders` table
   - Verify table creation success

2. **Code Deployment**
   - Deploy new API endpoints
   - Update order-list.php with toggle UI
   - Deploy cron job script

3. **Cron Job Setup**
   - Configure Windows Task Scheduler
   - Test cron job execution
   - Monitor logs for errors

4. **Rollback Plan**
   - Keep backup of original order-list.php
   - Document manual status update process
   - Disable cron job if issues arise
