# Requirements Document

## Introduction

This feature updates the same-day order system to properly separate delivery and pickup order limits, and to modify business hours logic so that closing time only affects same-day product availability rather than closing the entire store. The system currently uses the `availtoday_order_limit` table to manage same-day orders, but needs refinement to handle delivery vs pickup orders differently and to allow regular pre-order products to remain available 24/7.

## Glossary

- **Same-Day Order System**: The system that handles orders for products available for same-day fulfillment, managed through `availtoday_order_limit` table
- **Regular Order System**: The pre-order system that handles future orders, managed through `order_limits` table
- **Fulfillment Method**: The method by which an order is completed - either "delivery" or "pickup"
- **Business Hours**: The operational hours during which same-day products are available for ordering
- **Order Limit**: A numerical constraint on the maximum number of orders that can be accepted
- **Date Limit**: An admin-configured block that prevents orders on specific dates
- **Product Dashboard**: The interface that displays available products to customers
- **Store Closure**: The state where no products are available for ordering

## Requirements

### Requirement 1

**User Story:** As a customer, I want to place unlimited same-day pickup orders, so that I can order as many items as needed for pickup without hitting order limits

#### Acceptance Criteria

1. WHEN a customer places a same-day pickup order, THE Same-Day Order System SHALL NOT apply the `availtoday_order_limit` constraint
2. WHEN a customer places a same-day delivery order, THE Same-Day Order System SHALL apply the `availtoday_order_limit` constraint
3. WHEN validating a same-day order, THE Same-Day Order System SHALL check the `date_limits` table for admin blocks regardless of fulfillment method
4. WHEN the `availtoday_order_limit` is reached for delivery orders, THE Same-Day Order System SHALL reject additional same-day delivery orders
5. WHEN the `availtoday_order_limit` is reached for delivery orders, THE Same-Day Order System SHALL continue accepting same-day pickup orders

### Requirement 2

**User Story:** As a customer, I want to browse and order regular pre-order products at any time of day, so that I can place future orders even when the store's same-day service is closed

#### Acceptance Criteria

1. WHEN business hours end, THE Product Dashboard SHALL hide same-day products from the display
2. WHEN business hours end, THE Product Dashboard SHALL continue displaying regular pre-order products
3. WHEN business hours are active, THE Product Dashboard SHALL display both same-day and regular pre-order products
4. THE Product Dashboard SHALL NOT trigger a complete store closure based on business hours alone
5. WHEN a customer attempts to order a same-day product outside business hours, THE Same-Day Order System SHALL reject the order with an appropriate message

### Requirement 3

**User Story:** As an administrator, I want the system to respect date limits for both delivery and pickup same-day orders, so that I can block orders on specific dates when needed

#### Acceptance Criteria

1. WHEN an admin sets a date limit, THE Same-Day Order System SHALL block both delivery and pickup orders for that date
2. WHEN validating a same-day order, THE Same-Day Order System SHALL query the `date_limits` table before processing
3. WHEN a date limit exists for the current date, THE Same-Day Order System SHALL reject the order with a clear message
4. THE Same-Day Order System SHALL apply date limits independently from order count limits
5. WHEN a date limit is removed, THE Same-Day Order System SHALL immediately allow orders for that date

### Requirement 4

**User Story:** As a developer, I want clear separation between same-day and regular order validation logic, so that changes to one system do not affect the other

#### Acceptance Criteria

1. THE Same-Day Order System SHALL use the `availtoday_order_limit` table exclusively for same-day delivery order limits
2. THE Regular Order System SHALL use the `order_limits` table exclusively for regular delivery order limits
3. THE Same-Day Order System SHALL NOT reference or modify the `order_limits` table
4. THE Regular Order System SHALL NOT reference or modify the `availtoday_order_limit` table
5. WHEN processing an order, THE System SHALL determine which validation logic to apply based on the order type (same-day vs regular)
