# Requirements Document

## Introduction

This feature replaces the existing WebSocket/SSE-based realtime update system with a polling-based architecture using AJAX and cron jobs. The system will provide near-realtime updates for order lists and other dynamic content without maintaining persistent connections. The initial focus is on the admin order list, which should automatically refresh when new orders are placed.

## Glossary

- **Polling System**: The client-side mechanism that periodically requests updated data from the server using AJAX
- **Order List Container**: The HTML element with ID `orders-container` in `backend/pages/orders/order-list.php` that displays the list of orders
- **Cron Job**: A scheduled task that runs periodically on the server to perform automated updates
- **AJAX Refresh**: An asynchronous HTTP request that updates page content without a full page reload
- **Legacy Realtime System**: The existing WebSocket/SSE-based notification system to be removed

## Requirements

### Requirement 1

**User Story:** As an admin, I want the order list to automatically refresh when new orders are placed, so that I can see new orders without manually refreshing the page

#### Acceptance Criteria

1. WHEN a user places an order, THE Polling System SHALL refresh the Order List Container within 5 seconds without requiring manual page refresh
2. THE Polling System SHALL use AJAX to fetch updated order data from the server
3. THE Order List Container SHALL update its content without causing a full page reload
4. THE Polling System SHALL maintain the current scroll position after refreshing the Order List Container
5. THE Polling System SHALL preserve any active filters or sorting applied to the order list during refresh

### Requirement 2

**User Story:** As a developer, I want to remove all WebSocket and SSE infrastructure, so that the system uses a simpler polling-based architecture

#### Acceptance Criteria

1. THE system SHALL remove all WebSocket server code and dependencies
2. THE system SHALL remove all SSE (Server-Sent Events) endpoints and streaming logic
3. THE system SHALL remove all client-side WebSocket connection code
4. THE system SHALL remove all client-side SSE connection code
5. THE system SHALL delete all files related to event broadcasting, event queues, and realtime notification infrastructure

### Requirement 3

**User Story:** As an admin, I want the polling to be efficient and not overload the server, so that the system remains performant

#### Acceptance Criteria

1. THE Polling System SHALL make requests at intervals no shorter than 3 seconds
2. THE Polling System SHALL stop polling when the admin navigates away from the order list page
3. THE Polling System SHALL include a timestamp parameter in requests to enable server-side caching
4. THE Polling System SHALL handle network errors gracefully without breaking the polling loop
5. THE Polling System SHALL implement exponential backoff when consecutive requests fail, with a maximum interval of 30 seconds

### Requirement 4

**User Story:** As a developer, I want a clean API endpoint for fetching order updates, so that the polling system has a reliable data source

#### Acceptance Criteria

1. THE system SHALL provide an API endpoint that returns order list data in JSON format
2. THE API endpoint SHALL accept optional filter parameters for order status, date range, and order type
3. THE API endpoint SHALL return only orders that have been created or updated since the last request timestamp
4. THE API endpoint SHALL include proper authentication checks to ensure only authorized admins can access order data
5. THE API endpoint SHALL return appropriate HTTP status codes and error messages for invalid requests

### Requirement 5

**User Story:** As an admin, I want visual feedback when the order list is updating, so that I know the system is working

#### Acceptance Criteria

1. WHEN the Polling System fetches new data, THE Order List Container SHALL display a subtle loading indicator
2. THE loading indicator SHALL not obstruct the view of existing orders
3. WHEN new orders are added to the list, THE system SHALL highlight them temporarily to draw attention
4. THE highlight effect SHALL fade out after 3 seconds
5. THE system SHALL display the last update timestamp in the order list interface
