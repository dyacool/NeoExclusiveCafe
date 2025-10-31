# Requirements Document

## Introduction

This feature modifies the existing order limit system to differentiate between delivery and pick-up orders. Currently, both order types are constrained by the same limits. The new system will apply `order_limits` (daily delivery capacity) only to delivery orders, while both delivery and pick-up orders will respect `date_limits` (specific date blocks set by admin). Additionally, the availToday order limit functionality will be removed from the system.

## Glossary

- **Order Management System**: The NeoCafe system component that processes and validates customer orders
- **Delivery Order**: An order where the fulfillment_method is "Delivery"
- **Pick-up Order**: An order where the fulfillment_method is "Pick-up"
- **order_limits Table**: Database table storing the daily maximum number of delivery orders allowed system-wide
- **date_limits Table**: Database table storing specific dates that are blocked from accepting orders (applies to both delivery and pick-up)
- **availToday Order Limit**: Legacy functionality for limiting same-day orders (to be removed)
- **Checkout Calendar**: The user interface component displaying available dates for order placement
- **Date Availability API**: Backend endpoint that returns which dates are available for ordering

## Requirements

### Requirement 1: Apply Order Limits Only to Delivery Orders

**User Story:** As a customer placing a delivery order, I want the system to enforce daily delivery limits, so that the cafe can manage their delivery capacity effectively.

#### Acceptance Criteria

1. WHEN a customer attempts to place a delivery order, THE Order Management System SHALL validate the order count against the order_limits table
2. WHEN a customer attempts to place a pick-up order, THE Order Management System SHALL NOT validate against the order_limits table
3. WHEN counting existing orders for delivery limit validation, THE Order Management System SHALL count only delivery orders with active statuses (excluding Completed, Delivered, Picked-up, Cancelled)
4. IF the delivery order count reaches the daily limit, THEN THE Order Management System SHALL reject the new delivery order with an appropriate error message
5. WHEN a pick-up order is placed on any date, THE Order Management System SHALL bypass order_limits validation entirely

### Requirement 2: Apply Date Limits to Both Order Types

**User Story:** As an admin, I want to block specific dates from accepting any orders, so that I can manage cafe closures and special events for both delivery and pick-up services.

#### Acceptance Criteria

1. WHEN a customer attempts to place any order (delivery or pick-up), THE Order Management System SHALL validate the selected date against the date_limits table
2. IF the selected date exists in date_limits with accepting_orders set to false, THEN THE Order Management System SHALL reject the order
3. WHEN the Checkout Calendar loads, THE Date Availability API SHALL return blocked dates for both delivery and pick-up order types
4. THE Checkout Calendar SHALL display blocked dates with visual indicators (red background, X mark) for both order types
5. THE Checkout Calendar SHALL disable date selection for blocked dates regardless of fulfillment method

### Requirement 3: Remove availToday Order Limit Functionality

**User Story:** As a system administrator, I want the availToday order limit functionality removed, so that the system uses only the unified order limit approach.

#### Acceptance Criteria

1. THE Order Management System SHALL NOT validate orders against any availToday-specific order limits
2. THE Date Availability API SHALL NOT return availToday order limit data
3. THE Checkout Calendar SHALL NOT display availToday order limit information
4. THE Order Management System SHALL remove all code references to availToday order limit validation
5. THE system SHALL maintain the availToday cart functionality (separate from order limits)

### Requirement 4: Update Checkout Calendar Display Logic

**User Story:** As a customer, I want to see which dates are available based on my selected fulfillment method, so that I can choose an appropriate delivery or pick-up date.

#### Acceptance Criteria

1. WHEN a customer views the checkout calendar for delivery, THE Checkout Calendar SHALL display dates as unavailable if they exceed order_limits OR are blocked in date_limits
2. WHEN a customer views the checkout calendar for pick-up, THE Checkout Calendar SHALL display dates as unavailable only if they are blocked in date_limits
3. THE Checkout Calendar SHALL show remaining delivery slots and order limit information only when fulfillment method is delivery
4. WHEN the fulfillment method is pick-up, THE Checkout Calendar SHALL NOT display any order limit information or remaining slots
5. WHEN the fulfillment method changes between delivery and pick-up, THE Checkout Calendar SHALL update the available dates and displayed information accordingly

### Requirement 5: Update Backend Validation Logic

**User Story:** As a system administrator, I want order validation to occur on the backend, so that order limits cannot be bypassed through client-side manipulation.

#### Acceptance Criteria

1. WHEN processing a delivery order, THE Order Management System SHALL validate against both order_limits and date_limits tables
2. WHEN processing a pick-up order, THE Order Management System SHALL validate only against the date_limits table
3. THE Order Management System SHALL return specific error messages indicating whether the limit is due to delivery capacity or date blocking
4. IF validation fails, THEN THE Order Management System SHALL log the rejection reason
5. THE Order Management System SHALL perform validation before creating any order records in the database
