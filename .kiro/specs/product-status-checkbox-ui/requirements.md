# Requirements Document

## Introduction

This feature updates the product edit modal in the admin product list to replace the single "Shipping Method" dropdown with a checkbox-based interface. The new interface allows administrators to easily configure products for pre-order (status_id 1, 2, or 3) and/or same-day order (status_id 4) by checking boxes and selecting specific statuses from conditional dropdowns. This simplifies the product configuration workflow and makes it clearer when products are available for both ordering types.

## Glossary

- **Product_Edit_Modal**: The modal dialog in the admin interface used to edit product details
- **Pre_Order_Status**: Product statuses with status_id 1 (Pick Up), 2 (Delivery), or 3 (Delivery or Pick Up)
- **Same_Day_Order_Status**: Product status with status_id 4 that enables same-day ordering
- **AvailToday_Status**: The specific shipping method for same-day orders (status_id 1, 2, or 3 in availtoday_status_id field)
- **Status_Checkbox_Interface**: The new checkbox-based UI for selecting product order types
- **Calendar_Component**: The existing date picker component for selecting specific availability dates
- **Admin_User**: An authenticated administrator managing products

## Requirements

### Requirement 1

**User Story:** As an admin user, I want to see checkboxes for "Pre-order" and "Same-day order" instead of a single dropdown, so that I can quickly understand and configure which ordering types are available for a product.

#### Acceptance Criteria

1. WHEN the Admin_User opens the Product_Edit_Modal, THE Product_Edit_Modal SHALL display two checkboxes labeled "Pre-order" and "Same-day order" in place of the existing "Shipping Method" dropdown
2. WHEN the Product_Edit_Modal loads with an existing product, THE Product_Edit_Modal SHALL check the "Pre-order" checkbox IF the product status_id is 1, 2, or 3
3. WHEN the Product_Edit_Modal loads with an existing product, THE Product_Edit_Modal SHALL check the "Same-day order" checkbox IF the product status_id is 4 OR the product has a non-null availtoday_status_id
4. WHEN both checkboxes are checked, THE Product_Edit_Modal SHALL indicate that the product is available for both pre-order and same-day order

### Requirement 2

**User Story:** As an admin user, I want to select a specific pre-order shipping method when the pre-order checkbox is active, so that I can configure whether the product is available for pickup, delivery, or both.

#### Acceptance Criteria

1. WHEN the Admin_User checks the "Pre-order" checkbox, THE Product_Edit_Modal SHALL display a dropdown with options "Pick Up" (status_id 1), "Delivery" (status_id 2), and "Delivery or Pick Up" (status_id 3)
2. WHEN the "Pre-order" checkbox is unchecked, THE Product_Edit_Modal SHALL hide the pre-order shipping method dropdown
3. WHEN the Product_Edit_Modal loads with a product having status_id 1, 2, or 3, THE Product_Edit_Modal SHALL pre-select the corresponding option in the pre-order dropdown
4. WHEN the Admin_User changes the pre-order dropdown selection, THE Product_Edit_Modal SHALL update the product status_id to the selected value upon save

### Requirement 3

**User Story:** As an admin user, I want to select a specific same-day order shipping method when the same-day order checkbox is active, so that I can configure the delivery options for same-day orders.

#### Acceptance Criteria

1. WHEN the Admin_User checks the "Same-day order" checkbox, THE Product_Edit_Modal SHALL display a dropdown with options "Pick Up" (availtoday_status_id 1), "Delivery" (availtoday_status_id 2), and "Delivery and Pick Up" (availtoday_status_id 3)
2. WHEN the "Same-day order" checkbox is unchecked, THE Product_Edit_Modal SHALL hide the same-day order shipping method dropdown
3. WHEN the Product_Edit_Modal loads with a product having a non-null availtoday_status_id, THE Product_Edit_Modal SHALL pre-select the corresponding option in the same-day order dropdown
4. WHEN the Admin_User changes the same-day order dropdown selection, THE Product_Edit_Modal SHALL update the product availtoday_status_id to the selected value upon save

### Requirement 4

**User Story:** As an admin user, I want to see the calendar for selecting specific availability dates when the same-day order checkbox is active, so that I can configure which dates the product is available for same-day ordering.

#### Acceptance Criteria

1. WHEN the Admin_User checks the "Same-day order" checkbox, THE Product_Edit_Modal SHALL display the Calendar_Component for selecting same-day order availability dates
2. WHEN the "Same-day order" checkbox is unchecked, THE Product_Edit_Modal SHALL hide the Calendar_Component
3. WHEN the Product_Edit_Modal loads with a product having status_id 4, THE Product_Edit_Modal SHALL display the Calendar_Component with existing todays_product_dates pre-selected
4. WHEN the Product_Edit_Modal loads with a product having status_id 1, 2, or 3 AND a non-null availtoday_status_id, THE Product_Edit_Modal SHALL display the Calendar_Component with existing regular_today_dates pre-selected

### Requirement 5

**User Story:** As an admin user, I want the system to correctly save my checkbox selections and dropdown choices, so that products are configured with the appropriate status_id and availtoday_status_id values.

#### Acceptance Criteria

1. WHEN the Admin_User checks only the "Pre-order" checkbox and saves, THE Product_Edit_Modal SHALL set the product status_id to the selected pre-order dropdown value AND set availtoday_status_id to NULL
2. WHEN the Admin_User checks only the "Same-day order" checkbox and saves, THE Product_Edit_Modal SHALL set the product status_id to 4 AND set availtoday_status_id to the selected same-day order dropdown value
3. WHEN the Admin_User checks both checkboxes and saves, THE Product_Edit_Modal SHALL set the product status_id to the selected pre-order dropdown value AND set availtoday_status_id to the selected same-day order dropdown value
4. WHEN the Admin_User unchecks both checkboxes, THE Product_Edit_Modal SHALL prevent saving and display a validation error message indicating at least one order type must be selected

### Requirement 6

**User Story:** As an admin user, I want the existing product list display and filtering to continue working correctly with the new checkbox interface, so that I can still view and filter products by their order types.

#### Acceptance Criteria

1. WHEN a product is saved with only pre-order enabled, THE product list SHALL display the product with the appropriate pre-order status badge
2. WHEN a product is saved with only same-day order enabled, THE product list SHALL display the product with the "Same Day Order" status badge
3. WHEN a product is saved with both pre-order and same-day order enabled, THE product list SHALL display both the pre-order status badge AND the same-day order status badge
4. WHEN the Admin_User applies status filters, THE product list SHALL correctly filter products based on their status_id and availtoday_status_id values
