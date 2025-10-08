# NeoCafe Admin Notification System

A comprehensive floating notification system for the NeoCafe admin dashboard, featuring real-time notifications, responsive design, and seamless integration with order and bulk order systems.

## Features

### 🔔 Real-time Notifications

- Live notification updates every 30 seconds
- Unread notification badge with count
- Desktop dropdown and mobile redirect behavior
- Automatic notification creation for orders and bulk orders

### 📱 Responsive Design

- **Desktop (>768px)**: Floating dropdown modal with detailed notification list
- **Mobile (≤768px)**: Direct navigation to full notifications page
- Comprehensive breakpoints: 1920px, 1440px, 1024px, 768px, 640px, 480px

### 🎨 Design Integration

- Matches product-list.php styling and theme
- Inter font family with consistent color scheme
- CSS custom properties (--green-_, --gray-_, --blue-\*)
- Smooth animations and hover effects

### 📊 Notification Types

- **Order Notifications**: `order_new`, `order_status`, `order_warning`
- **Bulk Order Notifications**: `bulk_new`, `bulk_status`, `bulk_payment`
- Custom notification types supported

## File Structure

```
backend/pages/admin-includes/notifications/
├── notifications.css              # Main styling with responsive design
├── notifications.js               # JavaScript functionality
├── notification.php               # Backend notification handler class
├── api.php                        # AJAX API endpoints
├── all-notifications.php          # Full notification management page
├── notification-integration.php   # Integration helper functions
├── notification-template.html     # HTML template for admin pages
├── test.php                       # Testing interface
├── test-api.php                   # Testing API endpoints
└── create_admin_notifications_table.sql  # Database schema
```

## Installation

### 1. Database Setup

```sql
-- Run the SQL schema to create the notifications table
source backend/pages/admin-includes/notifications/create_admin_notifications_table.sql;
```

### 2. Include in Admin Pages

Add these lines to your admin page templates:

```html
<!-- In the <head> section -->
<link
  rel="stylesheet"
  href="/backend/pages/admin-includes/notifications/notifications.css"
/>

<!-- Before closing </body> tag -->
<script src="/backend/pages/admin-includes/notifications/notifications.js"></script>
```

### 3. Integration with Order Systems

```php
// Include the integration helper
require_once __DIR__ . '/path/to/notification-integration.php';

// Create notification integration instance
$notifier = new NotificationIntegration($your_database_connection);

// Create notifications for orders
$notifier->notifyNewOrder($order_id, $customer_name, $username, $delivery_method, $delivery_date, $delivery_time);
$notifier->notifyOrderStatusChange($order_id, $customer_name, $username, $new_status);

// Create notifications for bulk orders
$notifier->notifyNewBulkOrder($bulk_order_id, $customer_name, $username);
$notifier->notifyBulkOrderStatusChange($bulk_order_id, $customer_name, $username, $new_status);
$notifier->notifyBulkOrderPayment($bulk_order_id, $customer_name, $username);
```

## API Endpoints

### GET /backend/pages/admin-includes/notifications/api.php

**Get Recent Notifications**

```
?action=get_recent
```

Returns: Recent 10 notifications with unread count

**Get Unread Count**

```
?action=get_unread_count
```

Returns: Current unread notification count

### POST /backend/pages/admin-includes/notifications/api.php

**Mark Notifications as Read**

```json
{
  "action": "mark_read",
  "notif_ids": [1, 2, 3]
}
```

**Mark All as Read**

```json
{
  "action": "mark_all_read"
}
```

**Delete Notifications**

```json
{
  "action": "delete",
  "notif_ids": [1, 2, 3]
}
```

## Notification Types & Messages

### Order Notifications

#### New Order (`order_new`)

- **Title**: "Order #1001 - New Order Placed"
- **Message**: "User @username placed an order for pickup today at 2:00 PM"
- **Link**: `/backend/pages/orders/view-orders.php?order_id=1001`

#### Order Status Update (`order_status`)

- **Title**: "Order #1001 - Status Updated"
- **Message**: "User @username order status has been updated to Confirmed"
- **Link**: `/backend/pages/orders/view-orders.php?order_id=1001`

#### Order Warning (`order_warning`)

- **Title**: "Order #1001 - ⚠️ Delivery Alert"
- **Message**: "⚠️ Heads up! User @username placed an order for delivery tomorrow at 10:00 AM — make sure everything is ready in time."
- **Link**: `/backend/pages/orders/view-orders.php?order_id=1001`

### Bulk Order Notifications

#### New Bulk Order (`bulk_new`)

- **Title**: "Bulk Order #501 - New Request"
- **Message**: "User @username submitted a bulk order request for review."
- **Link**: `/backend/pages/bulks/bulk-order.php?id=501`

#### Bulk Status Update (`bulk_status`)

- **Title**: "Bulk Order #501 - Status Updated"
- **Message**: "User @username bulk order status has been updated to Approved"
- **Link**: `/backend/pages/bulks/bulk-order.php?id=501`

#### Bulk Payment (`bulk_payment`)

- **Title**: "Bulk Order #501 - Payment Submitted"
- **Message**: "User @username uploaded proof of payment. Please verify the details."
- **Link**: `/backend/pages/bulks/bulk-order.php?id=501`

## Responsive Behavior

### Desktop (>768px)

- Notification bell with floating dropdown
- Hover effects and smooth animations
- Click to mark as read, navigate to links
- Mark all as read and view all buttons

### Mobile (≤768px)

- Notification bell redirects to all-notifications.php
- Touch-optimized interface
- Simplified navigation
- Full-screen notification management

## CSS Custom Properties

The notification system uses the following CSS variables that should match your existing admin theme:

```css
:root {
  --green-50: #f0fdf4;
  --green-100: #dcfce7;
  --green-600: #16a34a;
  --green-700: #15803d;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  --blue-50: #eff6ff;
  --blue-500: #3b82f6;
  --red-100: #fee2e2;
  --red-600: #dc2626;
  --red-700: #b91c1c;
  --red-800: #991b1b;
}
```

## Testing

Visit `/backend/pages/admin-includes/notifications/test.php` to:

- Create sample notifications
- Test all notification types
- Clear all notifications
- Verify system functionality

## Integration Examples

### Order Creation Script

```php
if ($order_inserted_successfully) {
    $notifier = new NotificationIntegration($conn);
    $notifier->notifyNewOrder(
        $order_id,
        $customer_name,
        $username ?? null,
        $delivery_method,
        $delivery_date,
        $delivery_time
    );
}
```

### Bulk Order Status Update

```php
if ($status_updated_successfully) {
    $notifier = new NotificationIntegration($conn);
    $notifier->notifyBulkOrderStatusChange(
        $bulk_order_id,
        $customer_name,
        $username ?? null,
        $new_status
    );
}
```

### Custom Notification

```php
$handler = new NotificationHandler($conn);
$handler->create(
    'order_warning',
    'Special Alert: Large Order Received',
    'A large order of 50+ items has been placed by John Doe. Please prioritize preparation.',
    '/backend/pages/orders/view-orders.php?order_id=123',
    123
);
```

## Browser Support

- Modern browsers with ES6+ support
- CSS Grid and Flexbox support
- Fetch API support
- Mobile responsive design

## Security

- Admin authentication required for all endpoints
- SQL injection prevention with prepared statements
- XSS protection with HTML escaping
- CSRF protection through session validation

## Performance

- Automatic notification polling every 30 seconds
- Efficient database queries with proper indexing
- Minimal JavaScript footprint
- Responsive CSS with mobile-first approach
- Lazy loading of notification content

## Troubleshooting

### Notifications Not Appearing

1. Check database connection in `/backend/pages/admin-includes/notifications/notification.php`
2. Verify admin session is active
3. Check browser console for JavaScript errors
4. Ensure CSS and JS files are properly included

### Database Issues

1. Run the SQL schema file to create the table
2. Check database permissions
3. Verify connection settings

### Styling Issues

1. Ensure CSS custom properties are defined
2. Check for CSS conflicts with existing styles
3. Verify font loading (Inter font family)

## Future Enhancements

- Push notifications support
- Email notification integration
- Advanced filtering and search
- Notification scheduling
- User preference settings
- Multi-language support

---

**Last Updated**: December 2024  
**Version**: 1.0.0  
**Compatible with**: NeoCafe Admin Dashboard
