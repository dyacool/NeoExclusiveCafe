# Requirements Document

## Introduction

This feature adds email notification functionality for bulk orders when they are processed by administrators. Currently, the system sends email notifications for regular orders (same-day and pre-orders), but bulk orders do not trigger any email notifications. When an administrator processes a bulk order (approves it, applies discounts, or updates customer information), an email notification should be sent to the admin with all relevant order details and a direct link to view the bulk order in the admin panel.

## Glossary

- **Bulk Order**: A special order type for large quantity purchases that requires admin review and approval before processing
- **Email Notification System**: THE system component responsible for sending order-related emails to administrators
- **Bulk Order Processing**: The action of an administrator reviewing, modifying, and approving a bulk order request
- **Admin Panel**: The administrative interface where bulk orders are managed
- **Bulk Order ID**: The unique identifier for a bulk order in the system
- **Order Status**: The current state of a bulk order (pending, approved, payment_received, payment_rejected, ready_for_delivery, cancelled, rejected, completed)

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to receive an email notification when a bulk order is processed, so that I am immediately informed of new bulk order requests that need attention

#### Acceptance Criteria

1. WHEN a bulk order status changes to 'approved', THE Email Notification System SHALL send an email notification to the admin email address
2. THE Email Notification System SHALL include the bulk order ID in the email subject line
3. THE Email Notification System SHALL send the email immediately after the bulk order is processed
4. THE Email Notification System SHALL use the existing email configuration and SMTP settings

### Requirement 2

**User Story:** As an administrator, I want the bulk order email to contain all relevant order information, so that I can quickly review the order details without logging into the system

#### Acceptance Criteria

1. THE Email Notification System SHALL include customer information (name, contact, email, billing address) in the email body
2. THE Email Notification System SHALL include order details (purpose, date needed, time needed, delivery address) in the email body
3. THE Email Notification System SHALL include a list of all ordered items with product names, quantities, prices, and subtotals in the email body
4. THE Email Notification System SHALL display the total amount, discount total (if applicable), and final amount in the email body
5. THE Email Notification System SHALL include the order status in the email body
6. THE Email Notification System SHALL include admin notes (if any) in the email body

### Requirement 3

**User Story:** As an administrator, I want a direct link to the bulk order in the admin panel, so that I can quickly access the full order details and take action

#### Acceptance Criteria

1. THE Email Notification System SHALL include a clickable button or link in the email body that directs to the bulk order details page
2. THE Email Notification System SHALL construct the URL using the bulk order ID
3. THE Email Notification System SHALL ensure the link opens the correct bulk order in the admin panel
4. THE Email Notification System SHALL format the link as a prominent call-to-action button

### Requirement 4

**User Story:** As an administrator, I want the bulk order email to have a distinct subject line and title, so that I can easily identify bulk order notifications in my inbox

#### Acceptance Criteria

1. THE Email Notification System SHALL use "Bulk Order Notification" as the email title
2. THE Email Notification System SHALL format the email subject as "Bulk Order Notification - Order #[BULK_ORDER_ID]"
3. THE Email Notification System SHALL include the unique bulk order ID in the subject line for easy reference
4. THE Email Notification System SHALL maintain consistent styling with existing order notification emails

### Requirement 5

**User Story:** As a developer, I want the bulk order email functionality to integrate seamlessly with the existing email system, so that it follows the same patterns and is maintainable

#### Acceptance Criteria

1. THE Email Notification System SHALL reuse the existing `sendEmail()` function from mailer.php
2. THE Email Notification System SHALL follow the same email template structure as regular order notifications
3. THE Email Notification System SHALL use the same error logging and exception handling patterns
4. THE Email Notification System SHALL be triggered from the bulk order processing logic in bulk-order.php
5. THE Email Notification System SHALL not duplicate email configuration or SMTP setup code
