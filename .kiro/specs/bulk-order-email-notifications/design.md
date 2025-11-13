# Design Document

## Overview

This feature adds email notification functionality for bulk orders when they are processed (approved) by administrators. The system currently sends email notifications for regular orders (same-day and pre-orders) using the existing mailer.php infrastructure. This enhancement will extend that functionality to bulk orders, sending an email to the admin whenever a bulk order is approved, with complete order details and a direct link to the bulk order management page.

The implementation will:
1. Create a new email function specifically for bulk orders in `mailer.php`
2. Create a bulk order email template following the same structure as regular order emails
3. Integrate the email trigger into the bulk order processing workflow in `bulk-order.php`
4. Include a prominent call-to-action button linking to the bulk order details page

## Architecture

### Current System

The email notification system consists of:
- **mailer.php**: Contains email sending logic using PHPMailer, including `sendOrderNotificationEmail()` for regular orders
- **bulk-order.php**: Handles bulk order management and processing by administrators
- **PHPMailer**: Third-party library for SMTP email sending

### Proposed Changes

1. Add `sendBulkOrderNotificationEmail()` function to `mailer.php`
2. Add `createBulkOrderEmailBody()` function to `mailer.php` for email template generation
3. Integrate email trigger into bulk order approval workflow in `bulk-order.php`
4. Reuse existing `sendEmail()`, `getEmailConfig()`, and `getAdminEmail()` functions

### Data Flow

```
Bulk Order Processing (bulk-order.php)
    ↓
Status updated to 'approved' OR discount applied OR customer info updated
    ↓
Fetch complete bulk order details (order + items)
    ↓
sendBulkOrderNotificationEmail($bulkOrderDetails)
    ↓
createBulkOrderEmailBody($bulkOrder) → Generates HTML with order details and CTA button
    ↓
sendEmail() → Sends email to admin
```

### Integration Points

The email notification will be triggered at multiple points in `bulk-order.php`:

1. **Discount Price Update** (line ~60-70): When admin saves discount prices
2. **Status Update** (line ~110-130): When status is changed to 'approved'
3. **Customer Info Update** (line ~390-400): When customer info is saved (auto-approves)
4. **Order Details Update** (line ~415-425): When order details are saved (auto-approves)
5. **Save All** (line ~445-460): When all fields are saved together (auto-approves)
6. **Duplicate Discount Update Handler** (line ~205-240): Secondary discount handler

## Components and Interfaces

### 1. Bulk Order Email Notification Function

**Function**: `sendBulkOrderNotificationEmail($bulkOrderId, $conn)`

**Location**: `backend/pages/admin-includes/mailer.php`

**Purpose**: Fetch bulk order details and send email notification to admin

**Input**:
```php
$bulkOrderId = int; // The bulk order ID
$conn = mysqli_connection; // Database connection
```

**Output**: Boolean - true on success, false on failure

**Logic**:
1. Fetch bulk order details from `bulk_orders` table
2. Fetch all order items from `bulk_order_items` table
3. Calculate totals (regular total, discount total, final total)
4. Get admin email from configuration
5. Create email subject: "Bulk Order Notification - Order #[BULK_ORDER_ID]"
6. Generate email body using `createBulkOrderEmailBody()`
7. Send email using existing `sendEmail()` function
8. Log success/failure
9. Return result

**Error Handling**:
- If order not found, log error and return false
- If email sending fails, log error and return false
- Wrap in try-catch to prevent breaking the order processing flow

### 2. Bulk Order Email Body Generation

**Function**: `createBulkOrderEmailBody($bulkOrder)`

**Location**: `backend/pages/admin-includes/mailer.php`

**Purpose**: Generate HTML email template for bulk order notifications

**Input**:
```php
$bulkOrder = [
    'id' => int,
    'unique_order_id' => string,
    'name' => string,
    'contact' => string,
    'email' => string,
    'billing_address' => string,
    'order_type' => 'pickup' | 'delivery',
    'delivery_address' => string | null,
    'purpose' => string,
    'date_needed' => 'YYYY-MM-DD',
    'time_needed' => 'HH:MM:SS',
    'note' => string | null,
    'total_amount' => float,
    'discount_total' => float | null,
    'total_items' => int,
    'status' => string,
    'admin_notes' => string | null,
    'created_at' => datetime,
    'items' => [
        [
            'product_name' => string,
            'product_price' => float,
            'discount_price' => float | null,
            'quantity' => int,
            'subtotal' => float
        ],
        ...
    ]
]
```

**Output**: String - HTML email body

**Template Structure**:
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Notification</title>
    <style>
        /* Same styling as regular order emails */
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Bulk Order Notification</h1>
            <p>Order #[BULK_ORDER_ID]</p>
            <p>[Current Date/Time]</p>
        </div>
        
        <!-- Customer Information -->
        <div class="section">
            <h2>Customer Information</h2>
            <p><strong>Name:</strong> [name]</p>
            <p><strong>Email:</strong> [email]</p>
            <p><strong>Contact:</strong> [contact]</p>
            <p><strong>Billing Address:</strong> [billing_address]</p>
        </div>
        
        <!-- Order Details -->
        <div class="section">
            <h2>Order Details</h2>
            <p><strong>Order Type:</strong> [order_type]</p>
            <p><strong>Delivery Address:</strong> [delivery_address] (if delivery)</p>
            <p><strong>Purpose:</strong> [purpose]</p>
            <p><strong>Date Needed:</strong> [date_needed]</p>
            <p><strong>Time Needed:</strong> [time_needed]</p>
            <p><strong>Status:</strong> [status]</p>
        </div>
        
        <!-- Order Items Table -->
        <div class="section">
            <h2>Order Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Discount Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loop through items -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Regular Total:</td>
                        <td>₱[total_amount]</td>
                    </tr>
                    <tr> (if discount applied)
                        <td colspan="4">Final Total (with discounts):</td>
                        <td>₱[final_total]</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Customer Notes (if any) -->
        <div class="section"> (if note exists)
            <h2>Customer Notes</h2>
            <p>[note]</p>
        </div>
        
        <!-- Admin Notes (if any) -->
        <div class="section"> (if admin_notes exists)
            <h2>Admin Notes</h2>
            <p>[admin_notes]</p>
        </div>
        
        <!-- Call-to-Action Button -->
        <div class="section" style="text-align: center;">
            <a href="[BASE_URL]/backend/pages/bulks/bulk-order.php?id=[BULK_ORDER_ID]" 
               class="cta-button">
                View Bulk Order Details
            </a>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is an automated notification from Neo Exclusive Cafe's ordering system.</p>
            <p>© [YEAR] Neo Exclusive Cafe. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
```

**CSS Styling**:
- Reuse existing email styles from `createOrderEmailBody()`
- Add CTA button styling:
  ```css
  .cta-button {
      display: inline-block;
      padding: 12px 24px;
      background-color: #2f603c;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
      margin: 20px 0;
  }
  .cta-button:hover {
      background-color: #234a2e;
  }
  ```

### 3. Integration into Bulk Order Processing

**File**: `backend/pages/bulks/bulk-order.php`

**Changes**: Add email notification trigger after successful order approval

**Trigger Points**:

1. **After discount price save** (line ~70):
```php
if ($ok) {
    logAdminActivity(...);
    
    // Send email notification
    require_once __DIR__ . "/../admin-includes/mailer.php";
    sendBulkOrderNotificationEmail($order_id, $conn);
    
    $success_message = "...";
}
```

2. **After status update to 'approved'** (line ~130):
```php
if ($ok && $new_status === 'approved') {
    logAdminActivity(...);
    
    // Send email notification
    require_once __DIR__ . "/../admin-includes/mailer.php";
    sendBulkOrderNotificationEmail($target_id, $conn);
    
    // Create notification...
}
```

3. **After customer info save** (line ~400):
```php
if ($ok) {
    logAdminActivity(...);
    
    // Send email notification
    require_once __DIR__ . "/../admin-includes/mailer.php";
    sendBulkOrderNotificationEmail($target_id, $conn);
}
```

4. **After order details save** (line ~430):
```php
if ($ok) {
    logAdminActivity(...);
    
    // Send email notification
    require_once __DIR__ . "/../admin-includes/mailer.php";
    sendBulkOrderNotificationEmail($target_id, $conn);
}
```

5. **After save all** (line ~460):
```php
if ($ok) {
    logAdminActivity(...);
    
    // Send email notification
    require_once __DIR__ . "/../admin-includes/mailer.php";
    sendBulkOrderNotificationEmail($orderId, $conn);
}
```

6. **After duplicate discount handler** (line ~235):
```php
if ($ok) {
    logAdminActivity(...);
    
    // Send email notification
    require_once __DIR__ . "/../admin-includes/mailer.php";
    sendBulkOrderNotificationEmail($target_id, $conn);
}
```

**Important**: Only send email when status becomes 'approved' or when order is auto-approved. Don't send for other status changes.

## Data Models

### Bulk Order Details Structure

```php
[
    // From bulk_orders table
    'id' => int,
    'unique_order_id' => string,
    'user_id' => int,
    'name' => string,
    'contact' => string,
    'email' => string,
    'billing_address' => string,
    'order_type' => 'pickup' | 'delivery',
    'delivery_address' => string | null,
    'purpose' => string,
    'date_needed' => 'YYYY-MM-DD',
    'time_needed' => 'HH:MM:SS',
    'note' => string | null,
    'total_amount' => float,
    'total_items' => int,
    'status' => string,
    'admin_updated' => datetime | null,
    'admin_notes' => string | null,
    'created_at' => datetime,
    'updated_at' => datetime,
    'discount_total' => float | null,
    
    // From bulk_order_items table (array)
    'items' => [
        [
            'id' => int,
            'bulk_order_id' => int,
            'product_id' => int | null,
            'product_name' => string,
            'product_price' => float,
            'discount_price' => float | null,
            'quantity' => int,
            'subtotal' => float
        ],
        ...
    ]
]
```

No database schema changes are required.

## Error Handling

### Email Sending Failures

1. **Database Query Failures**:
   - If bulk order fetch fails, log error and return false
   - If items fetch fails, log error and return false
   - Don't break the order processing flow

2. **Email Configuration Issues**:
   - Handled by existing `sendEmail()` function
   - Errors logged to PHP error log
   - Returns false on failure

3. **Missing Data**:
   - If bulk order ID is invalid, log error and return false
   - If admin email is not configured, use fallback email
   - If items array is empty, still send email with "No items" message

### Graceful Degradation

- Email sending failures should NOT prevent order processing
- Wrap email calls in try-catch blocks
- Log all errors for debugging
- Continue order processing even if email fails

### Logging Strategy

```php
error_log("Starting bulk order email notification for order #$bulkOrderId");
error_log("Bulk order details fetched: " . print_r($bulkOrder, true));
error_log("Email sent successfully to: " . $adminEmail);
// OR
error_log("Failed to send bulk order email: " . $e->getMessage());
```

## Testing Strategy

### Unit Testing Scenarios

1. **Email Function**:
   - Test with valid bulk order ID → email sent successfully
   - Test with invalid bulk order ID → returns false, logs error
   - Test with missing database connection → returns false, logs error
   - Test with empty items array → email sent with "No items" message

2. **Email Body Generation**:
   - Test with complete bulk order data → HTML generated correctly
   - Test with missing optional fields (note, admin_notes) → sections omitted
   - Test with discount applied → discount total displayed
   - Test without discount → only regular total displayed
   - Test CTA button URL → correct link generated

3. **Integration Points**:
   - Test discount save → email triggered
   - Test status change to 'approved' → email triggered
   - Test status change to other status → email NOT triggered
   - Test customer info save → email triggered
   - Test order details save → email triggered

### Integration Testing

1. **End-to-End Flow**:
   - Admin approves bulk order → email received with correct details
   - Admin applies discount → email received with discount information
   - Admin updates customer info → email received with updated info
   - Click CTA button in email → redirects to correct bulk order page

2. **Email Rendering**:
   - Check email appearance in Gmail
   - Check email appearance in Outlook
   - Verify mobile email client rendering
   - Verify CTA button is clickable and prominent

3. **Error Scenarios**:
   - SMTP server unavailable → order processing continues, error logged
   - Invalid admin email → fallback email used
   - Database connection lost → error logged, processing continues

### Manual Testing Checklist

- [ ] Approve a bulk order and verify email received
- [ ] Apply discount to bulk order and verify email received
- [ ] Update customer info and verify email received
- [ ] Verify email subject line format
- [ ] Verify email body contains all order details
- [ ] Verify CTA button links to correct page
- [ ] Verify email styling matches regular order emails
- [ ] Test with bulk order containing multiple items
- [ ] Test with bulk order containing discount
- [ ] Test with bulk order without discount
- [ ] Test with bulk order with customer notes
- [ ] Test with bulk order with admin notes

## Implementation Notes

### File Modifications

**File 1**: `backend/pages/admin-includes/mailer.php`

**Changes**:
1. Add `sendBulkOrderNotificationEmail($bulkOrderId, $conn)` function after `sendOrderNotificationEmail()`
2. Add `createBulkOrderEmailBody($bulkOrder)` function after `createOrderEmailBody()`
3. Reuse existing helper functions: `sendEmail()`, `getEmailConfig()`, `getAdminEmail()`, `getBaseUrl()`

**File 2**: `backend/pages/bulks/bulk-order.php`

**Changes**:
1. Add `require_once __DIR__ . "/../admin-includes/mailer.php";` at the top (if not already present)
2. Add email trigger after each successful approval operation (6 locations)
3. Wrap email calls in try-catch to prevent breaking order processing

### Code Style

- Follow existing PHP coding style in both files
- Use consistent error logging format
- Maintain existing comment structure
- Keep function documentation clear and concise
- Use same HTML/CSS structure as regular order emails

### Performance Considerations

- Email sending is synchronous and may add 1-3 seconds to processing time
- Consider adding email to a queue for async processing in future (out of scope)
- SMTP connection reuse is already handled by PHPMailer's `SMTPKeepAlive` setting

### Security Considerations

- Sanitize all output in email template using `htmlspecialchars()`
- Use prepared statements for database queries (already implemented)
- Don't expose sensitive admin credentials in email
- Validate bulk order ID before processing

### Deployment Considerations

1. **No Database Changes**: This is a code-only change
2. **No Configuration Changes**: Uses existing email configuration
3. **Backward Compatible**: Doesn't affect existing functionality
4. **Zero Downtime**: Can be deployed without service interruption
5. **Testing**: Test email configuration before deployment

## Future Enhancements

### Phase 2 Improvements

1. **Customer Email Notifications**:
   - Send email to customer when bulk order is approved
   - Include order summary and next steps
   - Add payment instructions

2. **Email Templates**:
   - Admin-configurable email templates
   - Support for multiple languages
   - Custom branding options

3. **Notification Preferences**:
   - Allow admins to configure which events trigger emails
   - Support for multiple admin recipients
   - Email digest option (daily summary)

4. **Async Email Queue**:
   - Implement background job queue for email sending
   - Retry failed emails automatically
   - Email delivery status tracking

5. **Rich Notifications**:
   - Include product images in email
   - Add order timeline/history
   - Attach PDF invoice

### Additional Features

- SMS notifications for urgent bulk orders
- Slack/Discord integration for team notifications
- Email analytics (open rate, click rate)
- Automated follow-up emails based on order status
