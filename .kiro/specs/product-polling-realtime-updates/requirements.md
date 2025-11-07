# Requirements Document

## Introduction

This feature extends the existing polling-based realtime update system to product pages. When a customer successfully places an order, product stock quantities are decremented. Both the frontend product dashboard (customer-facing) and backend product list (admin-facing) should automatically reflect these stock changes without requiring manual page refresh. The system will use the same AJAX polling architecture already implemented for order-list and admin-dashboard.

## Glossary

- **Product Dashboard**: The customer-facing page at `frontend/pages/products/product-dashboard.php` that displays available products
- **Product List**: The admin-facing page at `backend/pages/products/product-list.php` that manages products
- **Polling System**: The client-side mechanism that periodically requests updated product data from the server using AJAX
- **Stock Decrement**: The reduction of product quantity when an order is successfully placed
- **Payment Success Flow**: The process that occurs when `frontend/pages/cart/payment-return.php` confirms a successful payment
- **Silent Update**: Updating page content without showing loading indicators or disrupting user experience

## Requirements

### Requirement 1

**User Story:** As a customer viewing the product dashboard, I want to see updated stock quantities when other customers place orders, so that I know the current availability without refreshing the page

#### Acceptance Criteria

1. WHEN another customer successfully places an order, THE Polling System SHALL update the Product Dashboard stock quantities within 5 seconds
2. THE Polling System SHALL update product availability status (available/unavailable) based on current stock levels
3. THE Product Dashboard SHALL update silently without showing loading indicators
4. THE Polling System SHALL maintain the current scroll position during updates
5. THE Polling System SHALL preserve the active category filter during updates

### Requirement 2

**User Story:** As an admin viewing the product list, I want to see updated stock quantities when customers place orders, so that I can monitor inventory in real-time

#### Acceptance Criteria

1. WHEN a customer successfully places an order, THE Polling System SHALL update the Product List stock quantities within 5 seconds
2. THE Product List SHALL display updated preorder stock and same-day stock values
3. THE Polling System SHALL update the product list without causing a full page reload
4. THE Polling System SHALL maintain the current scroll position during updates
5. THE Polling System SHALL preserve any active filters or search queries during updates

### Requirement 3

**User Story:** As a developer, I want to reuse the existing polling infrastructure, so that the implementation is consistent and maintainable

#### Acceptance Criteria

1. THE system SHALL follow the same polling pattern used in order-list and admin-dashboard
2. THE system SHALL use AJAX to fetch updated product data every 5 seconds
3. THE Polling System SHALL stop polling when the user navigates away from the page
4. THE Polling System SHALL handle network errors gracefully without breaking the polling loop
5. THE Polling System SHALL implement exponential backoff when consecutive requests fail

### Requirement 4

**User Story:** As a developer, I want clean API endpoints for fetching product updates, so that the polling system has reliable data sources

#### Acceptance Criteria

1. THE system SHALL provide an API endpoint for frontend product dashboard that returns product data in JSON format
2. THE system SHALL provide an API endpoint for backend product list that returns product data in JSON format
3. THE API endpoints SHALL accept optional filter parameters for category, search, and pagination
4. THE API endpoints SHALL include proper authentication checks where required
5. THE API endpoints SHALL return appropriate HTTP status codes and error messages for invalid requests

### Requirement 5

**User Story:** As a customer, I want the product dashboard to update seamlessly, so that my browsing experience is not disrupted

#### Acceptance Criteria

1. THE Product Dashboard SHALL NOT display loading indicators during polling updates
2. THE Product Dashboard SHALL update stock quantities and availability status silently
3. WHEN a product becomes unavailable, THE system SHALL update its display state without page flicker
4. THE system SHALL preserve user interactions (scrolling, viewing modals) during updates
5. THE Polling System SHALL not interfere with add-to-cart operations in progress

### Requirement 6

**User Story:** As an admin, I want visual feedback when the product list updates, so that I know the data is current

#### Acceptance Criteria

1. WHEN the Polling System fetches new data, THE Product List SHALL display a subtle loading indicator
2. THE loading indicator SHALL not obstruct the view of existing products
3. WHEN product stock changes, THE system SHALL briefly highlight the updated values
4. THE highlight effect SHALL fade out after 2 seconds
5. THE system SHALL display the last update timestamp in the product list interface
