# Requirements Document

## Introduction

This feature enhances the email notification system to distinguish between different order types by customizing the email subject line and title. Currently, all order notifications use a generic "New Order Notification" title. This enhancement will display "Sameday Order Notification" or "Pre-Order Notification" based on the order type, improving clarity for administrators and staff who receive these notifications.

## Glossary

- **Email Notification System**: THE system component responsible for sending order confirmation emails to administrators
- **Order Type**: A classification indicating whether an order is for same-day fulfillment or pre-order (future date)
- **Sameday Order**: An order where the pickup/delivery date matches the current date
- **Pre-Order**: An order where the pickup/delivery date is scheduled for a future date
- **Email Title**: The heading displayed within the email body content
- **Email Subject**: The subject line of the email message
- **Bulk Order**: A special order type for large quantity orders (to be implemented in a future phase)

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to see the order type in the email notification title, so that I can quickly identify whether an order is for same-day fulfillment or a future date

#### Acceptance Criteria

1. WHEN THE Email Notification System sends an order notification for a Sameday Order, THE Email Notification System SHALL display "Sameday Order Notification" as the email title
2. WHEN THE Email Notification System sends an order notification for a Pre-Order, THE Email Notification System SHALL display "Pre-Order Notification" as the email title
3. THE Email Notification System SHALL determine the order type by comparing the pickup date with the current date
4. THE Email Notification System SHALL maintain all existing email content and formatting except for the title modification

### Requirement 2

**User Story:** As an administrator, I want the email subject line to reflect the order type, so that I can filter and prioritize emails in my inbox

#### Acceptance Criteria

1. WHEN THE Email Notification System sends an order notification for a Sameday Order, THE Email Notification System SHALL set the email subject to "Sameday Order Notification - Order #[ORDER_ID]"
2. WHEN THE Email Notification System sends an order notification for a Pre-Order, THE Email Notification System SHALL set the email subject to "Pre-Order Notification - Order #[ORDER_ID]"
3. THE Email Notification System SHALL include the order ID in the subject line for reference

### Requirement 3

**User Story:** As a developer, I want the order type determination logic to be centralized and reusable, so that it can be consistently applied across the system

#### Acceptance Criteria

1. THE Email Notification System SHALL implement a function that accepts a pickup date and returns the order type classification
2. THE Email Notification System SHALL classify an order as "Sameday Order" when the pickup date equals the current date
3. THE Email Notification System SHALL classify an order as "Pre-Order" when the pickup date is after the current date
4. THE Email Notification System SHALL use date comparison that accounts for timezone consistency

### Requirement 4

**User Story:** As a system maintainer, I want bulk order notifications to be excluded from this initial implementation, so that they can be designed separately with their specific requirements

#### Acceptance Criteria

1. THE Email Notification System SHALL not modify bulk order notification titles in this implementation phase
2. THE Email Notification System SHALL maintain existing bulk order notification behavior
3. THE Email Notification System SHALL provide a clear extension point for future bulk order notification customization
