# Requirements Document

## Introduction

This feature fixes a critical bug in the order notification system where customer email and delivery address are not being displayed in admin email notifications. Currently, admin emails show "Not provided" for email and "N/A" for address, even though customers enter this information during checkout. The root cause is that customer information is stored in the `saved_customer_info` table but is not being properly retrieved and passed to the email notification system.

## Glossary

- **Order Notification System**: THE system component responsible for sending order confirmation emails to administrators
- **Saved Customer Info Table**: A database table that stores customer contact information including email, phone, and complete address
- **Admin Email Notification**: An email sent to administrators containing order details and customer information
- **Checkout Process**: THE system workflow where customers enter their information and complete an order
- **Order Details Array**: A data structure containing all order information passed to the email notification function

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to see the customer's email address in order notification emails, so that I can contact customers about their orders

#### Acceptance Criteria

1. WHEN THE Order Notification System sends an admin email for any order type, THE Order Notification System SHALL display the customer email address from the saved_customer_info table
2. WHEN a customer email is not available in saved_customer_info, THE Order Notification System SHALL display "Not provided" as a fallback
3. THE Order Notification System SHALL retrieve the primary email address when multiple saved customer info records exist
4. WHEN no primary email exists, THE Order Notification System SHALL retrieve the most recently updated email address

### Requirement 2

**User Story:** As an administrator, I want to see the customer's delivery address in order notification emails for delivery orders, so that I can fulfill delivery orders correctly

#### Acceptance Criteria

1. WHEN THE Order Notification System sends an admin email for a delivery order, THE Order Notification System SHALL display the complete delivery address from the saved_customer_info table
2. WHEN THE Order Notification System sends an admin email for a pickup order, THE Order Notification System SHALL display "N/A" or "Pickup" for the address field
3. THE Order Notification System SHALL retrieve the primary address when multiple saved customer info records exist
4. WHEN no primary address exists, THE Order Notification System SHALL retrieve the most recently updated address

### Requirement 3

**User Story:** As a developer, I want the customer information retrieval logic to be executed at the point where order details are constructed, so that the data is available when the email is sent

#### Acceptance Criteria

1. THE Checkout Process SHALL retrieve customer email and address from saved_customer_info before constructing the order details array
2. THE Checkout Process SHALL populate the order details array with the retrieved customer information
3. THE Checkout Process SHALL log successful and failed retrieval attempts for debugging purposes
4. THE Checkout Process SHALL use the retrieved information for both database insertion and email notification

### Requirement 4

**User Story:** As a system maintainer, I want consistent customer information retrieval across all order types, so that the system behaves predictably

#### Acceptance Criteria

1. THE Checkout Process SHALL use identical customer information retrieval logic for same-day orders and pre-orders
2. THE Checkout Process SHALL retrieve both email and complete_address fields in a single database query when possible
3. THE Checkout Process SHALL prioritize records where is_primary equals 1
4. THE Checkout Process SHALL fall back to the most recent record based on updated_at timestamp when no primary record exists

