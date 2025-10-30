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
  ↓
Order completed → process-checkout.php records:
  1. Coupon usage in coupon_usage table (NEW)
  2. Updates global used_count
```

### Components Modified

1. **Database Schema** - Add `coupon_usage` table
2. **validate-coupon.php** - Add per-user limit checking
3. **process-checkout.php** - Record coupon usage
4. **process-availtoday-checkout.php** - Record coupon usage
5. **database-config.php** - Add table creation function

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

## Files to Modify

1. `backend/pages/user-page-content/database-config.php` - Add table creation
2. `backend/pages/user-page-content/validate-coupon.php` - Add per-user check
3. `frontend/pages/cart/process-checkout.php` - Record usage
4. `frontend/pages/cart/process-availtoday-checkout.php` - Record usage

## Files to Create

None (all changes are modifications to existing files)
