# Requirements Document

## Introduction

This document specifies the requirements for an automatic order status management system that intelligently updates order statuses based on delivery/pickup dates and order types. The system includes a toggle switch to enable/disable automatic status updates, priority-based order queueing, overdue order detection, and maintains existing email notification functionality.

## Glossary

- **Order_Management_System**: The backend system that manages customer orders, including status tracking and updates
- **Auto_Status_Toggle**: A UI switch control that enables or disables automatic status updates
- **Order_Queue**: A prioritized list of orders sorted by due date and time proximity
- **Overdue_Order**: An order that has passed its scheduled delivery/pickup date without being completed
- **Pickup_Order**: An order where the customer collects the product from the business location
- **Delivery_Order**: An order where the product is delivered to the customer's location
- **Order_Status**: The current state of an order in the fulfillment process
- **Due_Date**: The scheduled date for order delivery or pickup
- **Status_Settings_Table**: A database table storing the auto-status toggle preference per admin user
- **Same_Day_Order**: An order placed with a Due_Date equal to the current date (order date = due date)

## Requirements

### Requirement 1: Auto-Status Toggle Control

**User Story:** As an admin, I want to toggle between automatic and manual order status management, so that I can choose the workflow that best fits my operational needs.

#### Acceptance Criteria

1. WHEN the admin views the order list page, THE Order_Management_System SHALL display a toggle switch labeled "Toggle auto-status" on the right side of the filter status container
2. WHEN the admin clicks the toggle switch, THE Order_Management_System SHALL save the preference to the Status_Settings_Table
3. WHEN the toggle is enabled, THE Order_Management_System SHALL automatically update order statuses based on due dates
4. WHEN the toggle is disabled, THE Order_Management_System SHALL allow manual status updates via dropdown selection
5. WHEN the page loads, THE Order_Management_System SHALL retrieve and apply the saved toggle preference from the Status_Settings_Table

### Requirement 2: Automatic Status Updates for Pickup Orders

**User Story:** As an admin, I want pickup orders to automatically progress through status stages based on due dates, so that I can focus on fulfillment rather than manual status updates.

#### Acceptance Criteria

1. WHERE the auto-status toggle is enabled, WHEN a Pickup_Order has a Due_Date of tomorrow, THE Order_Management_System SHALL set the Order_Status to "Preparing"
2. WHERE the auto-status toggle is enabled, WHEN a Pickup_Order has a Due_Date of today, THE Order_Management_System SHALL set the Order_Status to "Ready for Pick-up"
3. WHERE the auto-status toggle is enabled, THE Order_Management_System SHALL require manual status update to "Picked-up"
4. WHERE the auto-status toggle is disabled, THE Order_Management_System SHALL allow manual status selection for all Pickup_Order statuses
5. WHEN a Pickup_Order status changes automatically, THE Order_Management_System SHALL trigger the existing email notification system

### Requirement 3: Automatic Status Updates for Delivery Orders

**User Story:** As an admin, I want delivery orders to automatically progress through status stages based on due dates, so that the system handles routine status transitions while I manage exceptions.

#### Acceptance Criteria

1. WHERE the auto-status toggle is enabled, WHEN a Delivery_Order has a Due_Date of tomorrow, THE Order_Management_System SHALL set the Order_Status to "Preparing"
2. WHERE the auto-status toggle is enabled, WHEN a Delivery_Order has a Due_Date of today, THE Order_Management_System SHALL set the Order_Status to "Ready for Delivery"
3. WHERE the auto-status toggle is enabled, THE Order_Management_System SHALL require manual status update to "Out for Delivery"
4. WHERE the auto-status toggle is enabled, THE Order_Management_System SHALL require manual status update to "Delivered" by the rider
5. WHERE the auto-status toggle is disabled, THE Order_Management_System SHALL allow manual status selection for all Delivery_Order statuses
6. WHEN a Delivery_Order status changes automatically, THE Order_Management_System SHALL trigger the existing email notification system

### Requirement 4: Overdue Order Detection and Display

**User Story:** As an admin, I want overdue orders to be clearly marked and prioritized, so that I can immediately address delayed fulfillments.

#### Acceptance Criteria

1. WHEN an order's Due_Date has passed AND the Order_Status is not "Picked-up" or "Delivered", THE Order_Management_System SHALL mark the order as an Overdue_Order
2. WHEN displaying the order list, THE Order_Management_System SHALL show Overdue_Order entries at the top of the Order_Queue
3. WHEN an order becomes overdue, THE Order_Management_System SHALL display an "Overdue" badge with red styling
4. WHEN calculating overdue status, THE Order_Management_System SHALL compare the current date and time against the Due_Date and delivery/pickup time
5. WHEN an Overdue_Order status is manually updated to "Picked-up" or "Delivered", THE Order_Management_System SHALL remove the overdue designation

### Requirement 5: Priority-Based Order Queueing

**User Story:** As an admin, I want orders sorted by urgency based on due dates, so that I can prioritize fulfillment of time-sensitive orders.

#### Acceptance Criteria

1. WHEN displaying the order list, THE Order_Management_System SHALL sort orders with the nearest Due_Date at the top of the Order_Queue
2. WHEN multiple orders have the same Due_Date, THE Order_Management_System SHALL sort by delivery/pickup time with earliest times first
3. WHEN an order is marked as Overdue_Order, THE Order_Management_System SHALL display it above all non-overdue orders
4. WHEN the Order_Queue is updated, THE Order_Management_System SHALL maintain the priority sorting across page refreshes
5. WHEN filtering by status, THE Order_Management_System SHALL maintain priority sorting within the filtered results

### Requirement 6: Database Schema for Status Settings

**User Story:** As a system administrator, I want the auto-status toggle preference stored persistently, so that admin users retain their workflow preferences across sessions.

#### Acceptance Criteria

1. THE Order_Management_System SHALL create a Status_Settings_Table if it does not exist
2. THE Status_Settings_Table SHALL store the auto_status_enabled field as a boolean value
3. THE Status_Settings_Table SHALL store a timestamp of the last preference update
4. WHEN no preference exists for an admin user, THE Order_Management_System SHALL default the auto-status toggle to disabled
5. WHEN the admin updates the toggle preference, THE Order_Management_System SHALL update the Status_Settings_Table within 1 second

### Requirement 7: Same-Day Order Immediate Status

**User Story:** As an admin, I want same-day orders to immediately be marked as ready for fulfillment, so that urgent orders can be processed without delay.

#### Acceptance Criteria

1. WHERE the auto-status toggle is enabled, WHEN a Same_Day_Order is placed for pickup, THE Order_Management_System SHALL immediately set the Order_Status to "Ready for Pick-up"
2. WHERE the auto-status toggle is enabled, WHEN a Same_Day_Order is placed for delivery, THE Order_Management_System SHALL immediately set the Order_Status to "Ready for Delivery"
3. THE Order_Management_System SHALL skip the "Confirmed" and "Preparing" statuses for Same_Day_Order
4. WHEN a Same_Day_Order status is set automatically, THE Order_Management_System SHALL trigger the existing email notification system
5. WHERE the auto-status toggle is disabled, THE Order_Management_System SHALL set Same_Day_Order to "Confirmed" status like regular orders

### Requirement 8: Email Notification Preservation

**User Story:** As an admin, I want email notifications to continue working with automatic status updates, so that customers remain informed about their order progress.

#### Acceptance Criteria

1. WHEN an order status changes automatically, THE Order_Management_System SHALL trigger the existing email notification function
2. WHEN an order status changes manually, THE Order_Management_System SHALL trigger the existing email notification function
3. THE Order_Management_System SHALL preserve all existing email notification logic without modification
4. WHEN the auto-status toggle is enabled or disabled, THE Order_Management_System SHALL continue sending email notifications for all status changes
5. THE Order_Management_System SHALL include the updated Order_Status in the email notification content
