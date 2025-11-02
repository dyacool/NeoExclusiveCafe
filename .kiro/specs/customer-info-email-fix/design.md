# Design Document

## Overview

This design addresses a critical bug where customer email and delivery address are not appearing in admin email notifications, despite being entered during checkout. The root cause is that while fallback logic exists to fetch email from `saved_customer_info`, the address is not being retrieved, and the timing of data retrieval may not align with when the order details array is constructed.

The solution consolidates customer information retrieval into a single, comprehensive query that fetches both email and address from `saved_customer_info` before constructing the order details array. This ensures all customer data is available when the email notification is sent.

## Architecture

### Current System Flow

```
Checkout Form Submission
    ↓
Extract POST data (email, address may be empty)
    ↓
Fallback: Fetch email from saved_customer_info (if empty)
    ↓
Insert order into database
    ↓
Construct orderDetails array (uses potentially empty address)
    ↓
sendOrderNotificationEmail(orderDetails)
    ↓
Admin receives email with "Not provided" / "N/A"
```

### Problem Identified

1. **Email fallback exists but may execute too late** - The email is fetched from `saved_customer_info` but the variable might not be properly updated before order insertion
2. **Address has no fallback logic** - The address from POST is used directly, but for delivery orders, the address should come from `saved_customer_info.complete_address`
3. **Separate queries are inefficient** - Email and address should be fetched in one query
4. **Timing issue** - Customer info must be retrieved BEFORE constructing the orderDetails array

### Proposed System Flow

```
Checkout Form Submission
    ↓
Extract POST data
    ↓
Fetch customer info from saved_customer_info (email + complete_address)
    ↓
Merge fetched data with POST data (fetched data takes precedence)
    ↓
Insert order into database with complete customer info
    ↓
Construct orderDetails array with verified customer data
    ↓
sendOrderNotificationEmail(orderDetails)
    ↓
Admin receives email with complete customer information
```

## Components and Interfaces

### 1. Customer Information Retrieval Function

**Function**: `fetchCustomerInfoFromSaved($conn, $user_id)`

**Location**: Both checkout files (`process-availtoday-checkout.php` and `process_order.php`)

**Purpose**: Centralized function to retrieve customer email and address from `saved_customer_info`

**Input**:
- `$conn`: Database connection object
- `$user_id`: Integer user ID from session

**Output**: Associative array or null
```php
[
    'email' => 'customer@example.com',
    'complete_address' => '123 Main St, Subdivision, Sta. Rosa, Laguna 4026',
    'phone' => '09123456789',
    'first_name' => 'John',
    'last_name' => 'Doe'
] 
// or null if no saved info exists
```

**Query Logic**:
```sql
SELECT 
    email, 
    complete_address, 
    phone, 
    first_name, 
    last_name,
    CONCAT(dl.municipality, ', ', dl.city, ' ', dl.postal_code) as delivery_location
FROM saved_customer_info sci
LEFT JOIN delivery_locations dl ON sci.delivery_location_id = dl.delivery_id
WHERE sci.user_id = ? 
ORDER BY sci.is_primary DESC, sci.updated_at DESC 
LIMIT 1
```

**Priority**:
1. Primary record (`is_primary = 1`)
2. Most recently updated record
3. Returns null if no records exist

### 2. Data Merging Strategy

**Function**: Inline logic in checkout processing

**Purpose**: Merge POST data with saved customer info, giving precedence to saved data

**Logic**:
```php
// Fetch saved customer info
$saved_info = fetchCustomerInfoFromSaved($conn, $_SESSION['user_id']);

// Merge with POST data - saved info takes precedence for email and address
$email = !empty($saved_info['email']) ? $saved_info['email'] : ($email ?? '');
$phone = !empty($saved_info['phone']) ? $saved_info['phone'] : ($phone ?? '');
$first_name = !empty($saved_info['first_name']) ? $saved_info['first_name'] : ($first_name ?? '');
$last_name = !empty($saved_info['last_name']) ? $saved_info['last_name'] : ($last_name ?? '');

// For delivery orders, use complete_address from saved info
if ($shipping_method === 'delivery' && !empty($saved_info['complete_address'])) {
    $full_address = $saved_info['complete_address'];
} else {
    // Construct from POST data
    $full_address = trim($address);
    if (!empty($city)) $full_address .= ', ' . $city;
    if (!empty($postal_code)) $full_address .= ' ' . $postal_code;
}
```

**Precedence Rules**:
- Saved customer info takes precedence over POST data
- For delivery orders, `complete_address` from saved info is used
- For pickup orders, address can be "N/A" or minimal
- Empty saved info falls back to POST data

### 3. Order Details Array Construction

**Location**: Both checkout files, before calling `sendOrderNotificationEmail()`

**Changes**: Ensure all customer fields use the merged data

**Updated Structure**:
```php
$orderDetails = [
    'order_id' => $order_id,
    'customer_name' => $customer_full_name,  // From merged first_name + last_name
    'user_email' => $email,                   // From merged data
    'customer_contact' => $phone,             // From merged data
    'customer_address' => $full_address,      // From merged data (complete_address for delivery)
    'delivery_method' => $delivery_method_enum,
    'pickup_date' => $today_date,
    'pickup_time' => $pickup_time,
    'delivery_date' => ($shipping_method === 'delivery') ? $today_date : null,
    'delivery_time' => ($shipping_method === 'delivery') ? $pickup_time : null,
    'payment_method' => 'Cash on Delivery',
    'cart_items' => $cart_items,
    'cart_total' => $cart_total,
    'shipping_fee' => $shipping_fee,
    'total_amount' => $final_total,
    'order_notes' => $combined_notes,
    'discount_amount' => $discount_amount,
    'applied_coupon' => $applied_coupon
];
```

### 4. Email Template Verification

**Location**: `backend/pages/admin-includes/mailer.php`

**Function**: `createOrderEmailBody()`

**Current Implementation**: Already uses null coalescing for email
```php
<p><strong>Email:</strong> ' . htmlspecialchars($order['user_email'] ?? 'Not provided') . '</p>
<p><strong>Address:</strong> ' . htmlspecialchars($order['customer_address'] ?? 'N/A') . '</p>
```

**No Changes Needed**: The email template already handles missing data gracefully. The fix is in ensuring data is present before reaching this point.

## Data Models

### Saved Customer Info Table Structure

```sql
saved_customer_info (
    id INT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    delivery_location_id INT NOT NULL,
    complete_address TEXT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)
```

**Key Fields for Email Fix**:
- `email`: Customer email address
- `complete_address`: Full delivery address
- `phone`: Contact number
- `first_name`, `last_name`: Customer name
- `is_primary`: Priority flag (1 = primary, 0 = not primary)
- `updated_at`: Timestamp for fallback ordering

### Orders Table Structure

```sql
orders (
    id INT PRIMARY KEY,
    customer_name VARCHAR(255),
    customer_contact VARCHAR(20),
    customer_email VARCHAR(255),
    customer_address TEXT,
    delivery_method ENUM('Pick-up', 'Delivery'),
    ...
)
```

**Fields Affected by Fix**:
- `customer_email`: Will now be populated from saved_customer_info
- `customer_address`: Will now be populated from saved_customer_info for delivery orders

## Error Handling

### Missing Saved Customer Info

**Scenario**: User has no saved customer info records

**Handling**:
```php
$saved_info = fetchCustomerInfoFromSaved($conn, $_SESSION['user_id']);

if ($saved_info === null) {
    error_log("No saved customer info found for user_id: " . $_SESSION['user_id']);
    // Fall back to POST data
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    // ... etc
}
```

**Result**: System falls back to POST data, no error thrown

### Database Query Failure

**Scenario**: Query to fetch saved info fails

**Handling**:
```php
function fetchCustomerInfoFromSaved($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT ...");
        if (!$stmt) {
            error_log("Failed to prepare saved info query: " . $conn->error);
            return null;
        }
        // ... execute query
    } catch (Exception $e) {
        error_log("Error fetching saved customer info: " . $e->getMessage());
        return null;
    }
}
```

**Result**: Returns null, falls back to POST data

### Empty Email After Merge

**Scenario**: Both saved info and POST data have empty email

**Handling**:
```php
if (empty($email)) {
    $errors[] = 'Email is required';
    error_log("CRITICAL: No email available from saved info or POST data for user_id: " . $_SESSION['user_id']);
}
```

**Result**: Validation error, order not created

### Empty Address for Delivery Orders

**Scenario**: Delivery order but no address available

**Handling**:
```php
if ($shipping_method === 'delivery' && empty($full_address)) {
    $errors[] = 'Delivery address is required for delivery orders';
    error_log("CRITICAL: No delivery address for delivery order, user_id: " . $_SESSION['user_id']);
}
```

**Result**: Validation error, order not created

## Testing Strategy

### Unit Testing Scenarios

1. **Fetch Customer Info - Primary Exists**
   - User has 3 saved entries, one is primary
   - Expected: Returns primary entry data

2. **Fetch Customer Info - No Primary**
   - User has 2 saved entries, none primary
   - Expected: Returns most recently updated entry

3. **Fetch Customer Info - No Saved Info**
   - User has no saved entries
   - Expected: Returns null

4. **Data Merge - Saved Info Available**
   - Saved info has email and address
   - POST data has different email and address
   - Expected: Saved info takes precedence

5. **Data Merge - Saved Info Empty**
   - Saved info returns null
   - POST data has email and address
   - Expected: POST data is used

6. **Data Merge - Partial Saved Info**
   - Saved info has email but no address
   - POST data has address
   - Expected: Email from saved, address from POST

### Integration Testing

1. **Same-Day Delivery Order**
   - User with saved info places same-day delivery order
   - Verify: Admin email shows correct email and complete address
   - Verify: Order record in database has correct email and address

2. **Same-Day Pickup Order**
   - User with saved info places same-day pickup order
   - Verify: Admin email shows correct email
   - Verify: Address shows "N/A" or pickup location

3. **Pre-Order Delivery**
   - User with saved info places pre-order for delivery
   - Verify: Admin email shows correct email and complete address
   - Verify: Order record has correct data

4. **Pre-Order Pickup**
   - User with saved info places pre-order for pickup
   - Verify: Admin email shows correct email
   - Verify: Address handling is appropriate

5. **New User First Order**
   - User with no saved info places order
   - Verify: System uses POST data
   - Verify: Admin email shows data from form submission

### Manual Testing

1. **Email Rendering**
   - Place test order with saved info
   - Check admin email in Gmail
   - Verify email and address are displayed correctly
   - Verify no "Not provided" or "N/A" for delivery orders

2. **Database Verification**
   - Place test order
   - Query orders table
   - Verify customer_email and customer_address are populated

3. **Error Log Review**
   - Place test orders
   - Review PHP error logs
   - Verify successful fetch messages appear
   - Verify no critical errors

## Implementation Notes

### File Modifications

**Files to Modify**:
1. `frontend/pages/cart/process-availtoday-checkout.php`
2. `frontend/pages/cart/process_order.php`

**Changes Required**:
1. Add `fetchCustomerInfoFromSaved()` function at top of file
2. Replace existing email fallback logic with comprehensive customer info fetch
3. Add data merging logic after fetching saved info
4. Update order insertion to use merged data
5. Update orderDetails array construction to use merged data
6. Add enhanced error logging

**No Changes Required**:
- `backend/pages/admin-includes/mailer.php` - Already handles missing data gracefully
- Database schema - No changes needed
- Email templates - Already use null coalescing

### Code Organization

**Function Placement**:
```php
<?php
// Session and includes
session_start();
require_once '...';

// Helper function - place at top
function fetchCustomerInfoFromSaved($conn, $user_id) {
    // Implementation
}

// Main processing logic
try {
    // Extract POST data
    // Fetch saved customer info
    // Merge data
    // Validate
    // Insert order
    // Send email
} catch (Exception $e) {
    // Error handling
}
?>
```

### Logging Strategy

**Key Log Points**:
1. Before fetching saved info: Log user_id
2. After fetching: Log success/failure and data retrieved
3. After merging: Log final email and address values
4. Before order insertion: Log complete customer data
5. After email send: Log success/failure

**Log Format**:
```php
error_log("=== CUSTOMER INFO FETCH START ===");
error_log("User ID: " . $user_id);
error_log("Saved info found: " . ($saved_info ? "YES" : "NO"));
if ($saved_info) {
    error_log("Email: " . $saved_info['email']);
    error_log("Address: " . $saved_info['complete_address']);
}
error_log("Final email: " . $email);
error_log("Final address: " . $full_address);
error_log("=== CUSTOMER INFO FETCH END ===");
```

## Deployment Considerations

### Backward Compatibility

- No database changes required
- Existing orders unaffected
- Email template unchanged
- No breaking changes to API

### Performance Impact

- Single additional query per checkout (minimal impact)
- Query uses indexed columns (`user_id`, `is_primary`)
- LEFT JOIN with delivery_locations is efficient
- Overall performance impact: negligible

### Rollback Plan

If issues arise:
1. Revert checkout files to previous version
2. System falls back to POST data only
3. No data corruption risk
4. No database rollback needed

### Monitoring

**Metrics to Track**:
- Admin email delivery success rate
- Percentage of emails with "Not provided" email
- Percentage of emails with "N/A" address for delivery orders
- Error log frequency for customer info fetch failures

**Success Criteria**:
- 0% of delivery order emails show "N/A" for address
- 0% of emails show "Not provided" for email (when user has saved info)
- No increase in checkout errors
- No increase in email send failures

## Future Enhancements

### Phase 2 Improvements

1. **Real-time Validation**
   - Validate saved customer info exists before allowing checkout
   - Prompt user to save info if none exists

2. **Address Verification**
   - Integrate with address validation API
   - Ensure complete_address is always populated

3. **Multi-address Support**
   - Allow user to select which saved address to use for delivery
   - Don't always use primary address

4. **Email Confirmation**
   - Send order confirmation to customer email
   - Verify email deliverability before order completion

5. **Admin Dashboard**
   - Show customer info completeness metrics
   - Alert when orders have missing customer data

