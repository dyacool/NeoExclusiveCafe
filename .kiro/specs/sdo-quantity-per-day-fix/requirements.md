# Requirements Document

## Introduction

This document outlines the requirements for fixing the Same Day Order (SDO) quantity per day saving functionality. Currently, when editing a product with Same Day Order status, the quantity per day values are not being saved to the database. This feature ensures that SDO products properly save their date-specific inventory quantities.

## Glossary

- **SDO**: Same Day Order - Products available for immediate delivery/pickup on specific dates
- **Pre-Order**: Products with status_id 1, 2, or 3 (Pick Up, Delivery, or Delivery or Pick Up)
- **Product Status**: The delivery/availability type of a product (status_id in products table)
- **Quantity Per Day**: Date-specific inventory quantities stored in quantity_per_day_sdo table
- **Product Edit Modal**: The UI modal where administrators edit product details
- **Save Handler**: The JavaScript function that collects and submits product data

## Requirements

### Requirement 1

**User Story:** As an administrator, I want SDO quantity per day values to be saved when I edit a product, so that inventory is accurately tracked for each date.

#### Acceptance Criteria

1. WHEN the administrator edits a product with Same Day Order status AND sets quantity values for selected dates, THE Save Handler SHALL collect the quantity per day data before submitting the form.

2. WHEN the Save Handler collects product data, THE Save Handler SHALL include the SDO quantities in the payload sent to the update endpoint.

3. WHEN the update-product.php receives SDO quantity data, THE Backend SHALL save the quantities to the quantity_per_day_sdo table.

4. WHEN the Backend saves SDO quantities successfully, THE Backend SHALL also update the corresponding dates in todays_products_dates or regular_products_today_dates table based on product status.

5. WHEN the product save operation completes, THE System SHALL display the updated quantities in the product list view.

### Requirement 2

**User Story:** As an administrator, I want the system to handle Pre-Order products with Same Day Order correctly, so that both order types are properly configured.

#### Acceptance Criteria

1. WHEN a product has Pre-Order status (status_id 1, 2, or 3) AND has Same Day Order enabled (availtoday_status_id is set), THE System SHALL save SDO quantities to quantity_per_day_sdo table.

2. WHEN a product has Pre-Order status AND has Same Day Order enabled, THE System SHALL save selected dates to regular_products_today_dates table.

3. WHEN a product has only Same Day Order status (status_id 4), THE System SHALL save selected dates to todays_products_dates table.

4. WHEN a product transitions from Pre-Order with SDO to Pre-Order only, THE System SHALL delete all entries from regular_products_today_dates and quantity_per_day_sdo tables for that product.

5. WHEN a product transitions from Same Day Order only to Pre-Order only, THE System SHALL delete all entries from todays_products_dates and quantity_per_day_sdo tables for that product.

### Requirement 3

**User Story:** As an administrator, I want the system to validate SDO quantity data before saving, so that invalid data does not corrupt the database.

#### Acceptance Criteria

1. WHEN the Save Handler collects SDO quantities, THE Save Handler SHALL validate that all quantity values are non-negative integers.

2. WHEN the Save Handler collects SDO quantities, THE Save Handler SHALL validate that all dates are in valid YYYY-MM-DD format.

3. IF any quantity value is invalid, THEN THE Save Handler SHALL display an error message and prevent form submission.

4. IF any date value is invalid, THEN THE Save Handler SHALL display an error message and prevent form submission.

5. WHEN validation passes, THE Save Handler SHALL proceed with the save operation.
