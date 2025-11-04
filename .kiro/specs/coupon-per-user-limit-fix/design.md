# Design Document

## Overview

This design implements per-user coupon usage tracking for the NeoCafe coupon system. The solution adds a new database table to track coupon usage by user, updates the validation logic to check per-user limits, and records usage when orders are completed.

## Architecture

### High-Level Flow

```
User applies coupon → validate-coupon.php checks:
  1. Coupon exists and is active
  2. Global usage limit not exceeded
  3. Per-user usage limit not exceeded (NEW)
  4. Minimum purchase met
  5. No other coupon already applied (NEW)
  ↓
Coupon applied → UI updates:
  1. Disable coupon input field (NEW)
  2. Show remove button (NEW)
  3. Display applied coupon info (NEW)
  ↓
User clicks disabled field → Show chat bubble "1 coupon already applied!" (NEW)
  ↓
User removes coupon → UI updates:
  1. Re-enable coupon input field (NEW)
  2. Hide remove button (NEW)
  3. Clear applied coupon info (NEW)
  ↓
Order completed → process-checkout.php records:
  1. Coupon usage in coupon_usage table (NEW)
  2. Updates global used_count
```

### Components Modified

1. **Database Schema** - Add `coupon_usage` table
2. **validate-coupon.php** - Add per-user limit checking and single coupon enforcement
3. **process-checkout.php** - Record coupon usage
4. **process-availtoday-checkout.php** - Record coupon usage
5. **database-config.php** - Add table creation function
6. **checkout.php / availtoday-checkout.php** - Add UI for single coupon enforcement (NEW)
7. **checkout JavaScript** - Add client-side coupon management logic (NEW)
8. **checkout CSS** - Add styling for disabled state and chat bubble (NEW)

## Components and Interfaces

### 1. Database Schema

#### New Table: `coupon_usage`

```sql
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    coupon_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_coupon_order (user_id, coupon_id, order_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES promotions(id) ON DELETE CASCADE,
    INDEX idx_user_coupon (user_id, coupon_id),
    INDEX idx_coupon (coupon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Design Decisions:**
- `UNIQUE KEY` prevents duplicate entries for same user/coupon/order
- Foreign keys ensure referential integrity
- Indexes optimize lookup queries
- `order_id` can be NULL initially, updated after order creation
- Cascade delete removes usage records if user/coupon deleted

### 2. Validation Logic Enhancement

#### validate-coupon.php Changes

**New Function: `checkPerUserUsage()`**

```php
function checkPerUserUsage($conn, $user_id, $coupon_id, $per_user_limit) {
    if ($per_user_limit === null || $per_user_limit <= 0) {
        return ['allowed' => true];
    }
    
    $sql = "SELECT COUNT(*) as usage_count FROM coupon_usage 
            WHERE user_id = ? AND coupon_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $coupon_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $usage_count = intval($row['usage_count']);
    
    if ($usage_count >= $per_user_limit) {
        return [
            'allowed' => false,
            'message' => 'You have already used this coupon the maximum number of times allowed'
        ];
    }
    
    return ['allowed' => true, 'usage_count' => $usage_count];
}
```

**Integration Point:**
- Called after global usage limit check
- Requires user to be logged in (check session)
- Returns early if per-user limit not set

**Single Coupon Enforcement:**

Add check at the beginning of validation logic:

```php
// Check if a coupon is already applied
if (isset($_SESSION['applied_coupon']) && $_SESSION['applied_coupon']['code'] !== $coupon_code) {
    echo json_encode([
        'success' => false,
        'message' => 'Please remove the current coupon before applying a new one'
    ]);
    exit;
}
```

**Design Decision:**
- Check session for existing applied coupon
- Allow re-validation of same coupon (for page refresh scenarios)
- Reject different coupon codes when one is already applied

### 3. Usage Recording

#### New Function: `recordCouponUsage()`

```php
function recordCouponUsage($conn, $user_id, $coupon_id, $order_id = null) {
    try {
        $sql = "INSERT INTO coupon_usage (user_id, coupon_id, order_id) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE used_at = CURRENT_TIMESTAMP";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $coupon_id, $order_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        error_log("Error recording coupon usage: " . $e->getMessage());
        return false;
    }
}
```

**Design Decisions:**
- Uses `ON DUPLICATE KEY UPDATE` to handle race conditions
- Non-blocking: logs errors but doesn't fail order
- Can be called with or without order_id
- Wrapped in try-catch for resilience

#### Integration in Checkout Files

**process-checkout.php & process-availtoday-checkout.php:**

```php
// After order is successfully created
if (isset($_SESSION['applied_coupon'])) {
    $coupon_data = $_SESSION['applied_coupon'];
    $user_id = $_SESSION['user_id'];
    $coupon_id = $coupon_data['id'];
    
    // Record usage
    recordCouponUsage($conn, $user_id, $coupon_id, $order_id);
    
    // Update global used_count
    $update_sql = "UPDATE promotions SET used_count = used_count + 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $coupon_id);
    $update_stmt->execute();
    $update_stmt->close();
}
```

## Data Models

### Coupon Usage Record

```php
[
    'id' => int,              // Auto-increment primary key
    'user_id' => int,         // User who used the coupon
    'coupon_id' => int,       // Coupon that was used
    'order_id' => int|null,   // Associated order (null if not yet created)
    'used_at' => timestamp    // When the coupon was used
]
```

### Session Data

```php
$_SESSION['applied_coupon'] = [
    'id' => int,
    'code' => string,
    'type' => string,
    'value' => float,
    'discount_amount' => float,
    // ... other coupon data
];
```

## Error Handling

### Validation Errors

| Scenario | HTTP Status | Response |
|----------|-------------|----------|
| User not logged in (per-user limit set) | 200 | `{"success": false, "message": "Please log in to use this coupon"}` |
| Per-user limit exceeded | 200 | `{"success": false, "message": "You have already used this coupon..."}` |
| Database error during check | 200 | `{"success": false, "message": "Error validating coupon. Please try again."}` |

### Recording Errors

- **Non-blocking**: If `recordCouponUsage()` fails, order still completes
- **Logging**: All errors logged to PHP error log
- **Retry**: No automatic retry (admin can manually adjust if needed)

### Edge Cases

1. **Guest Users**: Reject coupons with per-user limits if not logged in
2. **Race Conditions**: UNIQUE constraint prevents duplicate records
3. **Cancelled Orders**: Usage record remains (intentional - prevents abuse)
4. **Database Unavailable**: Validation fails gracefully, logs error

## Testing Strategy

### Unit Testing Approach

1. **Database Functions**
   - Test `createCouponUsageTable()` creates table correctly
   - Test `checkPerUserUsage()` with various limits
   - Test `recordCouponUsage()` inserts and updates correctly

2. **Validation Logic**
   - Test per-user limit enforcement
   - Test unlimited per-user (NULL/0 limit)
   - Test guest user rejection
   - Test logged-in user acceptance

3. **Integration Points**
   - Test coupon application flow end-to-end
   - Test order completion records usage
   - Test duplicate prevention

### Manual Testing Checklist

1. Create coupon with `usage_limit_per_user = 1`
2. Log in as user A, apply coupon, complete order
3. Try to apply same coupon again → should be rejected
4. Log in as user B, apply same coupon → should work
5. Check `coupon_usage` table has correct records
6. Test with unlimited per-user limit (NULL)
7. Test as guest user with per-user limit

### Database Testing

```sql
-- Verify table structure
DESCRIBE coupon_usage;

-- Check usage records
SELECT cu.*, u.email, p.code 
FROM coupon_usage cu
JOIN users u ON cu.user_id = u.id
JOIN promotions p ON cu.coupon_id = p.id
ORDER BY cu.used_at DESC;

-- Check per-user usage count
SELECT user_id, coupon_id, COUNT(*) as usage_count
FROM coupon_usage
GROUP BY user_id, coupon_id;
```

## Performance Considerations

### Query Optimization

- **Indexes**: Added on `(user_id, coupon_id)` for fast lookups
- **Query Complexity**: Simple COUNT query, O(1) with index
- **Expected Load**: Low (only during coupon validation)

### Caching Strategy

- No caching needed (usage must be real-time)
- Session stores applied coupon to avoid re-validation

### Scalability

- Table will grow with usage but remains manageable
- Consider archiving old records (>1 year) if needed
- Current design supports thousands of users/coupons

## Security Considerations

1. **SQL Injection**: All queries use prepared statements
2. **Session Validation**: Check user is logged in before recording
3. **Data Integrity**: Foreign keys and unique constraints
4. **Audit Trail**: Complete history of coupon usage
5. **No PII Exposure**: Only user_id stored, not personal data

## Migration Plan

### Deployment Steps

1. **Database Migration**
   - Add `createCouponUsageTable()` to `database-config.php`
   - Function auto-runs on page load (existing pattern)
   - No downtime required

2. **Code Deployment**
   - Update `validate-coupon.php`
   - Update `process-checkout.php`
   - Update `process-availtoday-checkout.php`
   - Deploy all files simultaneously

3. **Verification**
   - Check table created: `SHOW TABLES LIKE 'coupon_usage'`
   - Test coupon validation with per-user limits
   - Monitor error logs for issues

### Rollback Plan

If issues occur:
1. Revert code changes to previous version
2. Table can remain (won't cause issues)
3. Or drop table: `DROP TABLE IF EXISTS coupon_usage`

### Backward Compatibility

- Existing coupons without per-user limits work unchanged
- No changes to admin UI required
- No changes to coupon creation flow
- Fully backward compatible

## Future Enhancements

1. **Admin Dashboard**: Show per-user usage statistics
2. **Usage History**: User-facing page showing their coupon history
3. **Reset Usage**: Admin ability to reset usage for specific users
4. **Time-Based Limits**: "1 per user per month" instead of lifetime
5. **Analytics**: Track which users use which coupons most

## Dependencies

- **PHP**: 7.4+ (existing requirement)
- **MySQL**: 5.7+ (existing requirement)
- **Session Management**: User must be logged in for per-user limits
- **Existing Tables**: `users`, `promotions`, `orders`

### 4. UI/UX Components for Single Coupon Application

#### Frontend Changes

**HTML Structure Updates (checkout.php & availtoday-checkout.php):**

```html
<div class="coupon-section">
    <div class="coupon-input-wrapper">
        <input type="text" id="coupon-code" placeholder="Enter coupon code" />
        <button id="apply-coupon-btn">Apply</button>
    </div>
    
    <!-- Applied coupon display (hidden by default) -->
    <div id="applied-coupon-display" style="display: none;">
        <span id="applied-coupon-text"></span>
        <button id="remove-coupon-btn">Remove</button>
    </div>
    
    <!-- Chat bubble tooltip (hidden by default) -->
    <div id="coupon-tooltip" class="coupon-chat-bubble" style="display: none;">
        1 coupon already applied!
    </div>
</div>
```

**JavaScript Logic:**

```javascript
let couponApplied = false;

// Apply coupon
function applyCoupon() {
    const couponCode = document.getElementById('coupon-code').value;
    
    fetch('validate-coupon.php', {
        method: 'POST',
        body: JSON.stringify({ code: couponCode }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Disable input and show applied state
            disableCouponInput();
            showAppliedCoupon(couponCode, data.discount_amount);
            couponApplied = true;
        } else {
            alert(data.message);
        }
    });
}

// Disable coupon input
function disableCouponInput() {
    const input = document.getElementById('coupon-code');
    const applyBtn = document.getElementById('apply-coupon-btn');
    
    input.disabled = true;
    input.classList.add('disabled');
    applyBtn.disabled = true;
    
    // Add click listener for tooltip
    input.addEventListener('click', showCouponTooltip);
}

// Show tooltip when clicking disabled field
function showCouponTooltip() {
    const tooltip = document.getElementById('coupon-tooltip');
    tooltip.style.display = 'block';
    
    // Hide after 2 seconds
    setTimeout(() => {
        tooltip.style.display = 'none';
    }, 2000);
}

// Show applied coupon display
function showAppliedCoupon(code, discount) {
    const display = document.getElementById('applied-coupon-display');
    const text = document.getElementById('applied-coupon-text');
    
    text.textContent = `Coupon "${code}" applied (-$${discount})`;
    display.style.display = 'flex';
}

// Remove coupon
function removeCoupon() {
    fetch('remove-coupon.php', { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            enableCouponInput();
            hideAppliedCoupon();
            couponApplied = false;
            // Recalculate totals
            updateOrderTotal();
        }
    });
}

// Enable coupon input
function enableCouponInput() {
    const input = document.getElementById('coupon-code');
    const applyBtn = document.getElementById('apply-coupon-btn');
    
    input.disabled = false;
    input.classList.remove('disabled');
    input.value = '';
    applyBtn.disabled = false;
    
    // Remove click listener
    input.removeEventListener('click', showCouponTooltip);
}

// Hide applied coupon display
function hideAppliedCoupon() {
    const display = document.getElementById('applied-coupon-display');
    display.style.display = 'none';
}
```

**CSS Styling:**

```css
/* Disabled input state */
.coupon-input-wrapper input.disabled {
    background-color: #f0f0f0;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Applied coupon display */
#applied-coupon-display {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background-color: #e8f5e9;
    border-radius: 4px;
    margin-top: 10px;
}

#applied-coupon-text {
    color: #2e7d32;
    font-weight: 500;
}

#remove-coupon-btn {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

#remove-coupon-btn:hover {
    background-color: #d32f2f;
}

/* Chat bubble tooltip */
.coupon-chat-bubble {
    position: absolute;
    background-color: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 14px;
    margin-top: 5px;
    z-index: 1000;
    animation: fadeInOut 2s ease-in-out;
}

.coupon-chat-bubble::before {
    content: '';
    position: absolute;
    top: -5px;
    left: 20px;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-bottom: 5px solid #333;
}

@keyframes fadeInOut {
    0% { opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { opacity: 0; }
}
```

#### Backend Support for Coupon Removal

**New File: remove-coupon.php**

```php
<?php
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['applied_coupon'])) {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No coupon to remove']);
}
?>
```

**Design Decisions:**
- Simple session clearing for coupon removal
- Returns JSON for consistent API response
- No database interaction needed (coupon not yet recorded)

## Files to Modify

1. `backend/pages/user-page-content/database-config.php` - Add table creation
2. `backend/pages/user-page-content/validate-coupon.php` - Add per-user check and single coupon enforcement
3. `frontend/pages/cart/process-checkout.php` - Record usage
4. `frontend/pages/cart/process-availtoday-checkout.php` - Record usage
5. `frontend/pages/cart/checkout.php` - Add UI for single coupon application
6. `frontend/pages/cart/availtoday-checkout.php` - Add UI for single coupon application
7. `frontend/pages/cart/checkout-additional.css` (or relevant CSS file) - Add styling for disabled state and chat bubble

## Files to Create

1. `frontend/pages/cart/remove-coupon.php` - Handle coupon removal from session
