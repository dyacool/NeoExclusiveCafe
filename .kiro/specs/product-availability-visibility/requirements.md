# Requirements Document

## Introduction

This feature implements visibility rules for products in the product dashboard based on stock availability and admin-configured visibility settings. The system needs to determine when products should be shown or hidden based on their stock levels (both regular and same-day), and respect admin preferences for displaying unavailable products. This ensures customers see accurate product availability while giving administrators control over the shopping experience.

## Glossary

- **Product Dashboard**: The customer-facing interface that displays available products for ordering
- **Regular Stock**: The general inventory quantity stored in `products.quantity` used for pre-orders
- **Same-Day Stock**: The daily allocated quantity stored in `quantity_per_day_sdo.quantity` for same-day orders on specific dates
- **Unavailable Product**: A product with zero stock in both regular and same-day inventory, or without valid date availability
- **Visibility Flag**: An admin-configured setting that controls whether unavailable products are shown or hidden
- **Stock Check**: The process of determining if a product has available inventory
- **Order Type**: The classification of an order as either "regular pre-order" or "same-day order"
- **Date Availability**: The presence of a valid date record in `regular_products_today_dates` or `todays_products_dates` tables indicating the product is available for ordering on specific dates
- **Date Record**: An entry in date availability tables that enables a product for same-day ordering on a specific date

## Requirements

### Requirement 1

**User Story:** As a customer, I want to see only products that have stock available, so that I don't waste time trying to order items that cannot be fulfilled

#### Acceptance Criteria

1. WHEN displaying a product for regular pre-orders, THE Product Dashboard SHALL check if `products.quantity` is greater than zero
2. WHEN displaying a product for same-day orders, THE Product Dashboard SHALL check if `quantity_per_day_sdo.quantity` for the current date is greater than zero
3. WHEN both `products.quantity` equals zero AND `quantity_per_day_sdo.quantity` equals zero, THE Product Dashboard SHALL classify the product as unavailable
4. THE Product Dashboard SHALL perform stock checks before rendering each product in the display
5. WHEN a product's stock changes to zero, THE Product Dashboard SHALL update the product's visibility on the next page load or refresh

### Requirement 2

**User Story:** As an administrator, I want to control whether unavailable products are shown to customers, so that I can manage the shopping experience based on business needs

#### Acceptance Criteria

1. WHEN `products.show_when_unavailable` equals 1 for an unavailable product, THE Product Dashboard SHALL display the product
2. WHEN `products.hide_when_unavailable` equals 1 for an unavailable product, THE Product Dashboard SHALL hide the product
3. WHEN both `show_when_unavailable` and `hide_when_unavailable` are 0 for an unavailable product, THE Product Dashboard SHALL apply default visibility behavior (hide)
4. THE Product Dashboard SHALL prioritize `hide_when_unavailable` over `show_when_unavailable` when both flags are set to 1
5. WHEN a product has stock available, THE Product Dashboard SHALL display the product regardless of visibility flags

### Requirement 3

**User Story:** As a customer viewing same-day products, I want to see accurate stock and date availability for today's date, so that I know what I can order for same-day fulfillment

#### Acceptance Criteria

1. WHEN checking same-day stock, THE Product Dashboard SHALL query `quantity_per_day_sdo` with a date filter matching the current date
2. WHEN no record exists in `quantity_per_day_sdo` for the current date, THE Product Dashboard SHALL treat the same-day stock as zero
3. WHEN checking date availability for regular products with same-day option, THE Product Dashboard SHALL query `regular_products_today_dates` for a matching date record
4. WHEN checking date availability for same-day products, THE Product Dashboard SHALL query `todays_products_dates` for a matching date record
5. WHEN no date record exists in `regular_products_today_dates` OR the date has not yet arrived, THE Product Dashboard SHALL treat the product as unavailable for same-day ordering
6. WHEN no date record exists in `todays_products_dates` OR the date has not yet arrived, THE Product Dashboard SHALL treat the product as unavailable for same-day ordering
7. WHEN a product has regular stock but no same-day stock AND no valid date record, THE Product Dashboard SHALL show the product as unavailable for same-day orders
8. THE Product Dashboard SHALL display different stock indicators for regular vs same-day availability
9. WHEN the date changes, THE Product Dashboard SHALL re-evaluate same-day stock and date availability using the new current date

### Requirement 4

**User Story:** As a customer, I want the system to accurately determine product unavailability based on both stock levels and date availability, so that I only see products that can actually be ordered

#### Acceptance Criteria

1. WHEN `products.quantity` equals zero AND `quantity_per_day_sdo.quantity` equals zero (or no record for current date), THE Product Dashboard SHALL classify the product as unavailable
2. WHEN `products.quantity` equals zero AND no valid date exists in `regular_products_today_dates` for the current date, THE Product Dashboard SHALL classify the product as unavailable
3. WHEN `quantity_per_day_sdo.quantity` equals zero (or no record) AND no valid date exists in `todays_products_dates` for the current date, THE Product Dashboard SHALL classify the product as unavailable
4. WHEN a product is classified as unavailable AND `products.show_when_unavailable` equals 1, THE Product Dashboard SHALL display the product with unavailability indicators
5. WHEN a product is classified as unavailable AND `products.hide_when_unavailable` equals 1, THE Product Dashboard SHALL hide the product from display
6. WHEN a product is classified as unavailable AND both visibility flags are 0, THE Product Dashboard SHALL hide the product by default
7. WHEN `products.hide_when_unavailable` equals 1 AND `products.show_when_unavailable` equals 1, THE Product Dashboard SHALL prioritize hiding the product

### Requirement 5

**User Story:** As a developer, I want clear and efficient stock checking logic, so that the product dashboard performs well and is easy to maintain

#### Acceptance Criteria

1. THE Product Dashboard SHALL use database queries with LEFT JOIN to fetch regular stock, same-day stock, and date availability records in efficient operations
2. THE Product Dashboard SHALL implement stock and date checking logic in the backend PHP code rather than relying solely on frontend JavaScript
3. THE Product Dashboard SHALL cache stock availability results during a single page render to avoid redundant checks
4. THE Product Dashboard SHALL log visibility decisions for unavailable products to aid in debugging
5. WHEN rendering the product list, THE Product Dashboard SHALL apply visibility filters before sending data to the frontend

### Requirement 6

**User Story:** As an administrator, I want unavailable products to display with clear indicators when shown, so that customers understand the product status

#### Acceptance Criteria

1. WHEN an unavailable product is shown due to `show_when_unavailable` flag, THE Product Dashboard SHALL display an "Out of Stock" badge or similar indicator
2. WHEN an unavailable product is shown, THE Product Dashboard SHALL disable the "Add to Cart" or order action buttons
3. WHEN an unavailable product is shown, THE Product Dashboard SHALL display the product with reduced opacity or visual distinction
4. THE Product Dashboard SHALL provide a tooltip or message explaining why the product cannot be ordered (no stock, no date availability, etc.)
5. WHEN stock becomes available OR a valid date record is added for a previously unavailable product, THE Product Dashboard SHALL remove unavailability indicators on the next refresh
