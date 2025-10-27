# Requirements Document

## Introduction

This feature integrates the existing coupon and voucher system into the availtoday checkout process. Currently, the coupon/voucher system is functional in the regular checkout flow but is not available in the availtoday checkout, preventing users from applying discounts when ordering same-day available products. This integration will provide a consistent discount experience across all checkout flows.

## Glossary

- **Coupon System**: The existing promotional discount system that validates and applies discount codes to orders
- **Voucher**: A type of coupon code that can be sent to users via email, typically for refunds or promotional purposes
- **AvailToday Checkout**: The checkout process specifically for same-day available products
- **Regular Checkout**: The standard checkout process for pre-order products
- **Discount Amount**: The monetary value deducted from the order total based on the applied coupon
- **Validation API**: The backend endpoint (validate-coupon.php) that verifies coupon validity and calculates discounts

## Requirements

### Requirement 1

**User Story:** As a customer, I want to apply coupon codes during availtoday checkout, so that I can receive discounts on same-day available products

#### Acceptance Criteria

1. WHEN a customer views the availtoday checkout page, THE Checkout Interface SHALL display a coupon input section with an input field and a "Check Coupon" button
2. WHEN a customer enters a coupon code and clicks the "Check Coupon" button, THE Checkout System SHALL validate the coupon against the existing validation API without refreshing the page
3. WHEN a valid coupon is applied, THE Checkout Interface SHALL display the applied coupon information including code and discount amount
4. WHEN a coupon is successfully applied, THE Checkout System SHALL recalculate the order total by subtracting the discount amount from the subtotal plus shipping
5. WHEN a customer clicks the remove button on an applied coupon, THE Checkout System SHALL remove the discount and recalculate the order total to the original amount

### Requirement 2

**User Story:** As a customer, I want to see clear feedback when applying coupons, so that I understand whether my coupon was accepted or why it was rejected

#### Acceptance Criteria

1. WHEN a customer applies an invalid coupon code, THE Checkout Interface SHALL display an error message indicating the coupon is invalid or expired
2. WHEN a customer applies a coupon that does not meet minimum purchase requirements, THE Checkout Interface SHALL display an error message showing the required minimum purchase amount
3. WHEN a customer successfully applies a coupon, THE Checkout Interface SHALL display a success message with the discount details
4. WHEN the coupon validation API is processing, THE Checkout Interface SHALL disable the apply button and show a loading state
5. WHEN a network error occurs during coupon validation, THE Checkout Interface SHALL display an error message prompting the user to try again

### Requirement 3

**User Story:** As a customer, I want my applied coupon to be included in my order, so that the discount is properly recorded and applied to my final payment

#### Acceptance Criteria

1. WHEN a customer submits an order with an applied coupon, THE Order Processing System SHALL include the coupon code in the order data sent to the backend
2. WHEN processing an order with a coupon, THE Order Processing System SHALL include the discount amount in the order data
3. WHEN saving an order to the database, THE Order Processing System SHALL store the coupon information in the order notes field
4. WHEN an order with a coupon is created, THE Order Record SHALL contain the coupon code and discount amount for reference
5. WHEN calculating the final order total, THE Order Processing System SHALL subtract the discount amount from the subtotal plus shipping fee

### Requirement 4

**User Story:** As a customer, I want the coupon system to work consistently across both checkout flows, so that I have the same discount experience regardless of product type

#### Acceptance Criteria

1. WHEN a customer uses a coupon in availtoday checkout, THE Coupon System SHALL use the same validation logic as the regular checkout
2. WHEN displaying coupon UI elements, THE Checkout Interface SHALL use consistent styling between regular and availtoday checkout
3. WHEN applying discount calculations, THE Checkout System SHALL use the same calculation methods for both checkout flows
4. WHEN handling coupon errors, THE Error Handling System SHALL display consistent error messages across both checkout flows
5. WHERE a coupon includes free shipping, THE Checkout System SHALL apply the free shipping benefit in availtoday checkout

### Requirement 5

**User Story:** As a business owner, I want vouchers to be prevented from reuse after order completion, so that customers cannot apply the same voucher to multiple orders

#### Acceptance Criteria

1. WHEN an order is successfully completed with an applied coupon, THE Order Processing System SHALL increment the coupon's used_count in the database
2. WHEN a coupon reaches its usage_limit, THE Coupon Validation System SHALL reject subsequent attempts to use that coupon with an appropriate error message
3. WHEN a customer attempts to use a coupon that has reached its usage limit, THE Checkout Interface SHALL display a message indicating the coupon is no longer available
4. WHEN validating a coupon, THE Coupon Validation System SHALL check the current used_count against the usage_limit before allowing application
5. WHEN processing an order with a coupon, THE Order Processing System SHALL update the coupon usage count after successful order creation
