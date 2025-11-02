# Requirements Document

## Introduction

This feature redesigns the same-day order checkout (`availtoday-checkout.php`) to match the design, flow, and payment processing of the regular pre-order checkout (`checkout.php`). Currently, the same-day checkout has a different UI, lacks PayMongo payment integration, and uses a different order confirmation flow. This redesign will create a consistent checkout experience across both order types while maintaining their distinct functionality (same-day vs pre-order with calendar).

## Glossary

- **Same-Day Checkout**: THE checkout page for products available for same-day pickup or delivery (availtoday-checkout.php)
- **Pre-Order Checkout**: THE checkout page for products requiring advance ordering with date selection (checkout.php)
- **PayMongo Integration**: THE payment gateway system that processes online payments
- **Unified Payment Success**: A single payment confirmation page used for both checkout types
- **Auto-Load User Info**: THE system feature that automatically populates customer information from saved_customer_info table
- **Coupon System**: THE discount code functionality available during checkout

## Requirements

### Requirement 1

**User Story:** As a customer, I want the same-day checkout to look and feel identical to the pre-order checkout, so that I have a consistent shopping experience

#### Acceptance Criteria

1. THE Same-Day Checkout SHALL use the same CSS styling as Pre-Order Checkout
2. THE Same-Day Checkout SHALL use the same HTML structure and layout as Pre-Order Checkout
3. THE Same-Day Checkout SHALL display sections in the same order as Pre-Order Checkout (User Information, Shipping Options, Order Summary, Payment Method)
4. THE Same-Day Checkout SHALL use the same fonts, colors, spacing, and visual elements as Pre-Order Checkout

### Requirement 2

**User Story:** As a customer, I want my saved information to auto-load in same-day checkout, so that I don't have to re-enter my details

#### Acceptance Criteria

1. WHEN THE Same-Day Checkout loads, THE Same-Day Checkout SHALL automatically fetch and display the user's saved customer information
2. THE Same-Day Checkout SHALL display the "Load Contacts" button to manage saved addresses
3. WHEN a user selects delivery, THE Same-Day Checkout SHALL automatically populate the delivery address from saved_customer_info
4. THE Same-Day Checkout SHALL display the "Set Location" button for delivery address selection

### Requirement 3

**User Story:** As a customer, I want to use discount coupons during same-day checkout, so that I can save money on my order

#### Acceptance Criteria

1. THE Same-Day Checkout SHALL display a coupon code input field in the Order Summary section
2. THE Same-Day Checkout SHALL validate coupon codes against the promotions table
3. WHEN a valid coupon is applied, THE Same-Day Checkout SHALL update the order total with the discount
4. THE Same-Day Checkout SHALL support percentage discounts, fixed amount discounts, and free shipping coupons

### Requirement 4

**User Story:** As a customer, I want to pay for same-day orders using PayMongo, so that I can complete my purchase securely online

#### Acceptance Criteria

1. THE Same-Day Checkout SHALL integrate with PayMongo payment gateway
2. THE Same-Day Checkout SHALL display GCash as a payment option
3. WHEN a user submits the checkout form, THE Same-Day Checkout SHALL create a PayMongo payment intent
4. THE Same-Day Checkout SHALL redirect users to PayMongo's payment page for GCash payment
5. WHEN payment is successful, THE Same-Day Checkout SHALL redirect to payment-success.php with type=availtoday parameter

### Requirement 5

**User Story:** As a customer, I want same-day checkout to NOT show a calendar, so that I understand these orders are for today only

#### Acceptance Criteria

1. THE Same-Day Checkout SHALL NOT display a date selection calendar
2. THE Same-Day Checkout SHALL automatically set the pickup/delivery date to today's date
3. THE Same-Day Checkout SHALL display a clear indication that the order is for same-day fulfillment
4. THE Same-Day Checkout SHALL only show time selection for pickup/delivery

### Requirement 6

**User Story:** As a customer, I want a unified order confirmation page, so that I have a consistent post-purchase experience

#### Acceptance Criteria

1. THE Same-Day Checkout SHALL redirect to payment-success.php after successful payment
2. THE payment-success.php page SHALL accept a type parameter to distinguish order types
3. WHEN type=availtoday, THE payment-success.php SHALL display "Available Today" as the order type
4. THE payment-success.php SHALL display the same information for both same-day and pre-orders

### Requirement 7

**User Story:** As a developer, I want the same-day checkout to use the same payment processing flow as pre-order checkout, so that the codebase is maintainable

#### Acceptance Criteria

1. THE Same-Day Checkout SHALL use process-payment.php for PayMongo integration
2. THE Same-Day Checkout SHALL use payment-return.php for handling payment callbacks
3. THE Same-Day Checkout SHALL store order data in session before redirecting to PayMongo
4. THE Same-Day Checkout SHALL use the same order data structure as Pre-Order Checkout

### Requirement 8

**User Story:** As a customer, I want the checkout form to validate my information before processing payment, so that I don't encounter errors

#### Acceptance Criteria

1. THE Same-Day Checkout SHALL validate required fields (name, email, contact, address for delivery)
2. THE Same-Day Checkout SHALL display validation errors inline with form fields
3. THE Same-Day Checkout SHALL prevent form submission if validation fails
4. THE Same-Day Checkout SHALL show a loading state during payment processing

