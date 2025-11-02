# Design Document

## Overview

This feature enhances the existing email notification system to differentiate between order types (Sameday Order vs Pre-Order) in both the email subject line and the email body title. The system currently sends generic "New Order Notification" emails for all orders. This enhancement will provide immediate visual distinction between same-day and pre-order notifications, improving workflow efficiency for administrators.

The implementation will modify the `mailer.php` file to include order type determination logic and update the email template generation to reflect the appropriate order type.

## Architecture

### Current System

The email notification system consists of:
- **mailer.php**: Contains email sending logic using PHPMailer
- **process_order.php**: Handles pre-order checkout and calls `sendOrderNotificationEmail()`
- **process-availtoday-checkout.php**: Handles same-day order checkout and calls `sendOrderNotificationEmail()`

### Proposed Changes

1. Add order type determination function to `mailer.php`
2. Modify `createOrderEmailBody()` to accept and use order type
3. Update `sendOrderNotificationEmail()` to determine order type and pass it to email generation
4. Modify email subject line generation to include order type

### Data Flow

```
Order Processing (process_order.php or process-availtoday-checkout.php)
    ↓
sendOrderNotificationEmail($orderDetails)
    ↓
determineOrderType($orderDetails) → Returns "Sameday Order" or "Pre-Order"
    ↓
createOrderEmailBody($order, $orderType) → Generates HTML with appropriate title
    ↓
sendEmail() → Sends email with order type in subject
```

## Components and Interfaces

### 1. Order Type Determination Function

**Function**: `determineOrderType($orderDetails)`

**Location**: `backend/pages/admin-includes/mailer.php`

**Purpose**: Centralized logic to classify orders as same-day or pre-order

**Input**:
```php
$orderDetails = [
    'pickup_date' => 'YYYY-MM-DD',
    'delivery_date' => 'YYYY-MM-DD',
    'delivery_method' => 'Pick-up' | 'Delivery'
]
```

**Output**: String - "Sameday Order" or "Pre-Order"

**Logic**:
1. Determine the relevant date based on delivery method:
   - If `delivery_method` is "Pick-up", use `pickup_date`
   - If `delivery_method` is "Delivery", use `delivery_date`
2. Get current date in 'Y-m-d' format
3. Compare relevant date with current date:
   - If dates match → return "Sameday Order"
   - If relevant date is after current date → return "Pre-Order"
   - Default → return "Pre-Order" (safety fallback)

### 2. Email Body Generation Update

**Function**: `createOrderEmailBody($order, $orderType)`

**Changes**:
- Add `$orderType` parameter (default: "New Order" for backward compatibility)
- Update header section to use `$orderType` instead of hardcoded "New Order Notification"

**Modified HTML Structure**:
```html
<div class="header">
    <h1>{$orderType} Notification</h1>
    <p>Order #{order_id}</p>
    <p>{current_date_time}</p>
</div>
```

### 3. Email Subject Line Update

**Function**: `sendOrderNotificationEmail($orderDetails)`

**Changes**:
- Call `determineOrderType()` to get order classification
- Update subject line format to include order type
- Pass order type to `createOrderEmailBody()`

**New Subject Format**:
```
{OrderType} Notification - Order #{order_id}
```

Examples:
- "Sameday Order Notification - Order #23"
- "Pre-Order Notification - Order #24"

## Data Models

### Order Details Structure

The existing `$orderDetails` array already contains all necessary information:

```php
[
    'order_id' => int,
    'customer_name' => string,
    'user_email' => string,
    'customer_contact' => string,
    'customer_address' => string,
    'delivery_method' => 'Pick-up' | 'Delivery',
    'pickup_date' => 'YYYY-MM-DD' | null,
    'pickup_time' => 'HH:MM:SS' | null,
    'delivery_date' => 'YYYY-MM-DD' | null,
    'delivery_time' => 'HH:MM:SS' | null,
    'payment_method' => string,
    'cart_items' => array,
    'cart_total' => float,
    'shipping_fee' => float,
    'total_amount' => float,
    'order_notes' => string,
    'discount_amount' => float,
    'applied_coupon' => array | null
]
```

No database schema changes are required.

## Error Handling

### Date Comparison Edge Cases

1. **Missing Date Fields**:
   - If both `pickup_date` and `delivery_date` are null/empty
   - Fallback: Return "Pre-Order" as safe default
   - Log warning for investigation

2. **Invalid Date Format**:
   - Use PHP's `strtotime()` for flexible date parsing
   - If parsing fails, fallback to "Pre-Order"
   - Log error with order details

3. **Timezone Considerations**:
   - Use server's default timezone (already configured in PHP)
   - Both order dates and current date use same timezone
   - No timezone conversion needed

### Backward Compatibility

1. **Function Signature**:
   - `createOrderEmailBody($order, $orderType = "New Order")` 
   - Default parameter ensures existing calls continue to work

2. **Existing Email Calls**:
   - All existing calls through `sendOrderNotificationEmail()` will automatically use new logic
   - No changes required in calling code

## Testing Strategy

### Unit Testing Scenarios

1. **Order Type Determination**:
   - Test with pickup order on current date → "Sameday Order"
   - Test with delivery order on current date → "Sameday Order"
   - Test with pickup order on future date → "Pre-Order"
   - Test with delivery order on future date → "Pre-Order"
   - Test with missing dates → "Pre-Order" (fallback)
   - Test with invalid date formats → "Pre-Order" (fallback)

2. **Email Subject Generation**:
   - Verify subject includes order type
   - Verify subject includes order ID
   - Verify format matches specification

3. **Email Body Generation**:
   - Verify header displays correct order type
   - Verify all other email content remains unchanged
   - Verify HTML structure is valid

### Integration Testing

1. **Pre-Order Flow**:
   - Place a pre-order through `process_order.php`
   - Verify email received with "Pre-Order Notification" title
   - Verify email subject contains "Pre-Order Notification"

2. **Same-Day Order Flow**:
   - Place a same-day order through `process-availtoday-checkout.php`
   - Verify email received with "Sameday Order Notification" title
   - Verify email subject contains "Sameday Order Notification"

3. **Mixed Scenarios**:
   - Test pickup orders for today
   - Test delivery orders for today
   - Test pickup orders for future dates
   - Test delivery orders for future dates

### Manual Testing

1. **Email Rendering**:
   - Check email appearance in Gmail
   - Check email appearance in Outlook
   - Verify mobile email client rendering

2. **Admin Workflow**:
   - Verify admins can quickly identify order type from inbox
   - Verify email filtering by subject line works correctly

## Implementation Notes

### File Modifications

**File**: `backend/pages/admin-includes/mailer.php`

**Changes**:
1. Add `determineOrderType()` function after `sendOrderNotificationEmail()`
2. Modify `sendOrderNotificationEmail()` to call `determineOrderType()` and update subject
3. Update `createOrderEmailBody()` signature to accept `$orderType` parameter
4. Replace hardcoded "New Order Notification" with `$orderType . " Notification"`

### Code Style

- Follow existing PHP coding style in the file
- Use consistent error logging format
- Maintain existing comment structure
- Keep function documentation clear and concise

### Deployment Considerations

1. **No Database Changes**: This is a code-only change
2. **No Configuration Changes**: Uses existing email configuration
3. **Backward Compatible**: Existing functionality preserved
4. **Zero Downtime**: Can be deployed without service interruption

## Future Enhancements

### Bulk Order Support (Phase 2)

When bulk order notifications are implemented:

1. Add "Bulk Order" as third order type classification
2. Update `determineOrderType()` to check for bulk order flag
3. Add bulk-specific email template variations if needed
4. Consider separate email styling for bulk orders

### Additional Order Type Indicators

Potential future enhancements:
- Color-coded email headers based on order type
- Priority indicators for same-day orders
- Estimated preparation time in email
- Direct links to order management interface

### Email Template Customization

Future admin panel features:
- Customizable email templates per order type
- Admin-configurable order type labels
- Email preview functionality
- A/B testing for email formats
