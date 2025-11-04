# Requirements Document

## Introduction

This feature refactors the product-dashboard.php (customer-facing product page) to adopt the simplified dual-capability product model from the admin product-list changes. The current code has complex, nested logic for determining product availability and display. This refactor will simplify the code structure, improve maintainability, and ensure consistent handling of products that support both pre-order and same-day ordering.

## Glossary

- **Product_Dashboard**: The customer-facing page that displays all available products
- **Dual_Capability_Product**: A product with both pre-order (status_id 1/2/3 with quantity) AND same-day order (availtoday_status_id set) capabilities
- **Pre_Order_Product**: A product with status_id 1, 2, or 3 that has quantity available for advance ordering
- **Same_Day_Product**: A product with status_id 4 OR a dual-capability product with same-day dates available
- **Availability_Logic**: The system that determines if a product is available, unavailable, or should be hidden
- **Product_Badge**: Visual indicator showing the ordering type(s) available for a product
- **Stock_Check**: Logic to determine if a product has available inventory

## Requirements

### Requirement 1

**User Story:** As a customer, I want to clearly see which products are available for pre-order, same-day order, or both, so that I can make informed purchasing decisions.

#### Acceptance Criteria

1. WHEN a product has only pre-order capability (status_id 1/2/3 with quantity > 0), THE Product_Dashboard SHALL display a "Pre-Order" badge
2. WHEN a product has only same-day capability (status_id 4 with dates and stock), THE Product_Dashboard SHALL display a "Same Day Order" badge
3. WHEN a product has both capabilities (status_id 1/2/3 with quantity > 0 AND availtoday_status_id with dates and stock), THE Product_Dashboard SHALL display a "Same Day & Pre-Order" badge and prioritize same-day ordering in the UI
4. WHEN a product is unavailable (no quantity or not available today), THE Product_Dashboard SHALL display an "Unavailable" badge with the reason

### Requirement 2

**User Story:** As a developer, I want simplified availability logic that is easy to understand and maintain, so that I can quickly fix bugs and add features.

#### Acceptance Criteria

1. THE Availability_Logic SHALL use a clear, linear decision flow instead of nested conditionals
2. THE Stock_Check SHALL separately evaluate pre-order stock and same-day stock
3. THE Availability_Logic SHALL determine product capabilities before checking availability
4. THE code SHALL use descriptive variable names that clearly indicate product capabilities

### Requirement 3

**User Story:** As a customer, I want products with dual capabilities to show correctly even when same-day stock is depleted, so that I can still place a pre-order.

#### Acceptance Criteria

1. WHEN a dual-capability product has pre-order stock but no same-day stock, THE Product_Dashboard SHALL display the product with a "Pre-Order" badge
2. WHEN a dual-capability product has same-day stock and pre-order stock, THE Product_Dashboard SHALL display the product with a "Same Day & Pre-Order" badge
3. WHEN a dual-capability product has no pre-order stock but has same-day stock, THE Product_Dashboard SHALL display the product with a "Same Day Order" badge
4. WHEN a dual-capability product has neither stock type, THE Product_Dashboard SHALL mark the product as unavailable

### Requirement 4

**User Story:** As a developer, I want the availability determination logic extracted into a clear, well-documented function, so that I can easily test and modify it.

#### Acceptance Criteria

1. THE Product_Dashboard SHALL use a refactored `determineProductAvailability()` function with simplified logic
2. THE function SHALL return a structured array with keys: `has_preorder`, `has_sameday`, `is_unavailable`, `unavailable_reason`, `should_display`
3. THE function SHALL include inline comments explaining each decision point
4. THE function SHALL handle all edge cases (null values, missing dates, etc.) gracefully

### Requirement 5

**User Story:** As a customer, I want the product sorting to prioritize products I can order today, so that I see the most relevant products first.

#### Acceptance Criteria

1. THE Product_Dashboard SHALL maintain the existing sort order: Same Day Order → Featured → Pre-Order → Unavailable
2. THE Product_Dashboard SHALL verify the sorting logic correctly handles dual-capability products
3. THE Product_Dashboard SHALL sort dual-capability products in the "Same Day Order" group when same-day stock is available
4. THE Product_Dashboard SHALL sort dual-capability products in the "Pre-Order" group when only pre-order stock is available
5. WITHIN each group, THE Product_Dashboard SHALL sort alphabetically by product name

### Requirement 6

**User Story:** As a developer, I want the badge display logic simplified and consolidated, so that badge rendering is consistent and maintainable.

#### Acceptance Criteria

1. THE Product_Dashboard SHALL use the capability flags (`has_preorder`, `has_sameday`) to determine badge display
2. THE badge logic SHALL be a simple if-else chain based on capability combinations
3. THE Product_Dashboard SHALL eliminate duplicate badge logic
4. THE badge display SHALL match the admin product-list badge conventions

### Requirement 7

**User Story:** As a customer, I want products to display correctly regardless of their configuration complexity, so that I always see accurate availability information.

#### Acceptance Criteria

1. WHEN a product has status_id 4 (same-day only), THE Product_Dashboard SHALL only check same-day stock and dates
2. WHEN a product has status_id 1/2/3 without availtoday_status_id, THE Product_Dashboard SHALL only check pre-order stock
3. WHEN a product has status_id 1/2/3 with availtoday_status_id, THE Product_Dashboard SHALL check both stock types independently
4. THE Product_Dashboard SHALL handle missing or null values without errors

### Requirement 8

**User Story:** As a customer, I want the quantity modal to clearly show which ordering method I'm using, so that I understand what type of order I'm placing.

#### Acceptance Criteria

1. WHEN a product has both pre-order and same-day capabilities, THE quantity modal SHALL display options for both "Pre-Order Quantity" (from p.quantity) and "Same Day Order Quantity" (from quantity_per_day_sdo)
2. WHEN a product has only pre-order capability, THE quantity modal SHALL display a header indicating "Pre-Order" and show only pre-order quantity input
3. WHEN a product has only same-day capability, THE quantity modal SHALL display a header indicating "Same Day Order" and show only same-day quantity input
4. THE quantity modal SHALL clearly label each quantity input field with its corresponding order method
