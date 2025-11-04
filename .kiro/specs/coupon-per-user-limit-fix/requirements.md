# Requirements Document

## Introduction

The NeoCafe coupon system currently has a per-user usage limit field (`usage_limit_per_user`) in the promotions table, but this limit is not being enforced. Users can apply the same coupon multiple times even when the limit is set to 1 per user. This feature will implement proper per-user coupon usage tracking and validation to enforce the configured limits.

## Glossary

- **Coupon System**: The promotional discount system that allows users to apply coupon codes for discounts
- **Promotions Table**: Database table storing coupon configurations including usage limits
- **Coupon Usage Tracker**: New system component that records which users have used which coupons
- **Validation Endpoint**: The `validate-coupon.php` API that checks if a coupon can be applied
- **User Session**: The authenticated user session containing user identification
- **Order Processing**: The checkout flow where coupons are applied and recorded

## Requirements

### Requirement 1

**User Story:** As a customer, I want the system to enforce per-user coupon limits, so that I cannot use a coupon more times than allowed

#### Acceptance Criteria

1. WHEN a user attempts to apply a coupon, THE Validation Endpoint SHALL check how many times the user has previously used that coupon
2. IF the user has reached the per-user usage limit, THEN THE Validation Endpoint SHALL reject the coupon with message "You have already used this coupon the maximum number of times allowed"
3. WHEN a coupon has no per-user limit set (NULL or 0), THE Validation Endpoint SHALL allow unlimited uses per user
4. WHEN a user completes an order with a coupon, THE Order Processing SHALL record the coupon usage for that user
5. THE Coupon Usage Tracker SHALL store the user ID, coupon ID, and timestamp for each usage

### Requirement 2

**User Story:** As an admin, I want to see accurate usage statistics for coupons, so that I can understand how customers are using promotional codes

#### Acceptance Criteria

1. THE Coupon Usage Tracker SHALL maintain a complete history of all coupon uses by all users
2. WHEN an admin views coupon details, THE Coupon System SHALL display the total number of unique users who have used the coupon
3. THE Coupon Usage Tracker SHALL record the order ID associated with each coupon usage
4. THE Coupon Usage Tracker SHALL prevent duplicate entries for the same user and order

### Requirement 3

**User Story:** As a customer, I want to apply only one coupon at a time, so that I can clearly see which discount is being applied to my order

#### Acceptance Criteria

1. WHEN a user successfully applies a coupon, THE Coupon System SHALL disable the coupon input field
2. WHEN a user successfully applies a coupon, THE Coupon System SHALL display a remove button next to the applied coupon
3. WHEN a user clicks the remove button, THE Coupon System SHALL remove the applied coupon and re-enable the coupon input field
4. WHEN a user clicks on the disabled coupon input field, THE Coupon System SHALL display a chat bubble with message "1 coupon already applied!"
5. WHEN a user attempts to apply a second coupon while one is already applied, THE Coupon System SHALL reject the attempt with message "Please remove the current coupon before applying a new one"
6. WHEN the coupon input field is disabled, THE Coupon System SHALL provide visual indication that the field is disabled

### Requirement 4

**User Story:** As a developer, I want the coupon tracking system to handle edge cases properly, so that the system remains reliable

#### Acceptance Criteria

1. WHEN a guest user (not logged in) attempts to use a coupon with per-user limits, THE Validation Endpoint SHALL reject the coupon with message "Please log in to use this coupon"
2. WHEN an order is cancelled or refunded, THE Order Processing SHALL NOT remove the coupon usage record
3. IF database errors occur during usage tracking, THE Order Processing SHALL log the error but SHALL NOT block order completion
4. THE Validation Endpoint SHALL complete validation within 500 milliseconds under normal load
5. THE Coupon Usage Tracker SHALL use database transactions to ensure data consistency
