# Requirements Document

## Introduction

This feature adds payment method selection UI to the checkout pages (both regular checkout and available today checkout). The backend already supports GCash, Maya (PayMaya), and Bank Transfer payment methods through PayMongo integration. This enhancement will add the frontend UI to allow users to select their preferred payment method during checkout.

## Glossary

- **Checkout System**: The frontend pages where users complete their purchase (checkout.php and availtoday-checkout.php)
- **PayMongo**: The payment gateway service used for processing payments
- **Payment Method**: The mode of payment selected by the user (GCash, Maya, or Bank Transfer)
- **Payment Processing Handler**: The backend script (process-payment.php) that creates payment sources/intents with PayMongo
- **Payment Options Container**: The existing HTML element with class/id "payment-options" where payment method selections are displayed

## Requirements

### Requirement 1

**User Story:** As a customer, I want to select my preferred payment method (GCash, Maya, or Bank Transfer) during checkout, so that I can pay using my preferred payment service.

#### Acceptance Criteria

1. WHEN a user views the checkout page, THE Checkout System SHALL display payment method options within the payment-options container with three radio button options: GCash, Maya, and Bank Transfer
2. WHEN a user selects a payment method radio button, THE Checkout System SHALL visually indicate the selected payment method
3. WHEN a user attempts to place an order without selecting a payment method, THE Checkout System SHALL display a validation error message
4. WHEN a user submits the checkout form with a selected payment method, THE Checkout System SHALL include the payment method value in the form submission
5. WHERE the user is on the available today checkout page, THE Checkout System SHALL display the same payment method selection options within the payment-options container

### Requirement 2

**User Story:** As a customer, I want the payment method selection to be consistent across both regular and available today checkout pages, so that I have a familiar experience regardless of which checkout flow I use.

#### Acceptance Criteria

1. THE Checkout System SHALL display identical payment method selection UI on both checkout.php and availtoday-checkout.php
2. THE Checkout System SHALL use the same styling and layout for payment method selection on both pages
3. THE Checkout System SHALL apply the same validation rules for payment method selection on both pages
4. WHEN a user selects GCash on either checkout page, THE Payment Processing Handler SHALL create a GCash payment source
5. WHEN a user selects Maya on either checkout page, THE Payment Processing Handler SHALL create a Maya payment source
6. WHEN a user selects Bank Transfer on either checkout page, THE Payment Processing Handler SHALL create a Bank Transfer payment source

### Requirement 3

**User Story:** As a developer, I want the payment method selection to integrate seamlessly with the existing PayMongo payment processing, so that no backend changes are required.

#### Acceptance Criteria

1. THE Checkout System SHALL send the payment_method value as 'gcash', 'paymaya', or 'card' to match the existing backend implementation
2. THE Payment Processing Handler SHALL accept 'gcash', 'paymaya', and 'card' as valid payment method values
3. WHEN the payment method is 'gcash' or 'paymaya', THE Payment Processing Handler SHALL create a payment source with the appropriate type
4. WHEN the payment method is 'card', THE Payment Processing Handler SHALL create a payment intent and use PayMongo's hosted payment UI
5. THE Checkout System SHALL maintain all existing payment processing functionality without modification to process-payment.php
6. THE Checkout System SHALL use PayMongo sandbox environment for testing card payments
