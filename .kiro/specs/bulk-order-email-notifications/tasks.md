# Implementation Plan

- [x] 1. Create bulk order email notification function in mailer.php


  - Add `sendBulkOrderNotificationEmail($bulkOrderId, $conn)` function to `backend/pages/admin-includes/mailer.php`
  - Implement database queries to fetch bulk order details from `bulk_orders` table
  - Implement database queries to fetch order items from `bulk_order_items` table
  - Calculate totals (regular total, discount total, final total)
  - Get admin email using existing `getAdminEmail()` function
  - Create email subject: "Bulk Order Notification - Order #[BULK_ORDER_ID]"
  - Call `createBulkOrderEmailBody()` to generate email HTML
  - Send email using existing `sendEmail()` function
  - Add comprehensive error logging for debugging
  - Wrap in try-catch to prevent breaking order processing flow
  - Return boolean success/failure status
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 5.1, 5.3_


- [ ] 2. Create bulk order email template function
  - Add `createBulkOrderEmailBody($bulkOrder)` function to `backend/pages/admin-includes/mailer.php`
  - Reuse CSS styling from existing `createOrderEmailBody()` function
  - Create email header with "Bulk Order Notification" title and order ID
  - Add customer information section (name, email, contact, billing address)
  - Add order details section (order type, delivery address, purpose, date/time needed, status)
  - Create order items table with columns: Item, Quantity, Price, Discount Price, Subtotal
  - Display regular total and final total (with discounts if applicable)
  - Add customer notes section (conditionally displayed if notes exist)
  - Add admin notes section (conditionally displayed if admin notes exist)
  - Create prominent CTA button linking to bulk order details page
  - Construct URL using `getBaseUrl()` and bulk order ID
  - Add email footer with automated notification message
  - Sanitize all output using `htmlspecialchars()` for security



  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 4.4, 5.2, 5.3_

- [ ] 3. Integrate email notifications into bulk order processing workflow
  - Add `require_once __DIR__ . "/../admin-includes/mailer.php";` at the top of `backend/pages/bulks/bulk-order.php` if not present
  - Add email trigger after discount price save (line ~70) when status becomes 'approved'
  - Add email trigger after status update (line ~130) when new status is 'approved'
  - Add email trigger after customer info save (line ~400) when order is auto-approved
  - Add email trigger after order details save (line ~430) when order is auto-approved
  - Add email trigger after save all operation (line ~460) when order is auto-approved
  - Add email trigger after duplicate discount handler (line ~235) when order is auto-approved
  - Wrap all email calls in try-catch blocks to prevent breaking order processing
  - Log email sending results (success or failure)
  - Ensure email sending failures don't prevent order processing from completing
  - _Requirements: 1.1, 1.3, 5.4, 5.5_

- [ ]* 4. Test bulk order email functionality
  - Test email sending with valid bulk order ID
  - Test email sending with invalid bulk order ID (should fail gracefully)
  - Test email body generation with complete bulk order data
  - Test email body with missing optional fields (note, admin_notes)
  - Test email body with discount applied
  - Test email body without discount
  - Test CTA button URL generation
  - Test email triggering when discount is saved
  - Test email triggering when status changes to 'approved'
  - Test email triggering when customer info is saved
  - Test email triggering when order details are saved
  - Test email rendering in Gmail
  - Test email rendering in Outlook
  - Test CTA button click redirects to correct bulk order page
  - Test error handling when SMTP server is unavailable
  - Test error handling when database connection fails
  - Verify order processing continues even if email fails
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 4.4_
