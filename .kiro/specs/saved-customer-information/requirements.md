# Requirements Document

## Introduction

This feature enables customers to save up to 3 sets of checkout information (name, email, contact number, and delivery location with address) to streamline the checkout process. Customers will no longer need to re-enter their information for every order, improving user experience and reducing checkout friction for both regular pre-orders and same-day orders. The system integrates with the existing delivery_locations table to automatically apply delivery fees based on the saved location.

## Glossary

- **Checkout System**: The order processing interface where customers provide delivery information and complete purchases (checkout.php and availtoday-checkout.php)
- **Saved Information Entry**: A stored set of customer name, email, contact number, delivery location ID, and complete address that can be reused during checkout
- **Primary Entry**: The default saved information entry that is auto-selected during checkout
- **Customer**: A logged-in user with role 'user' who can place orders
- **Delivery Location**: A predefined location from the delivery_locations table containing municipality, city, postal code, and delivery fee
- **Complete Address**: The detailed address provided by the customer (house number, street, subdivision, landmarks, etc.)

## Requirements

### Requirement 1: Save Customer Information

**User Story:** As a customer, I want to save my name, email, contact number, and delivery location with address during checkout, so that I don't have to re-enter this information for future orders.

#### Acceptance Criteria

1. WHEN the Customer completes the checkout form with name, email, contact number, delivery location, and complete address, THE Checkout System SHALL provide an option to save the information for future use
2. WHEN the Customer chooses to save their information, THE Checkout System SHALL store the first name, last name, email, contact number, delivery_location_id (foreign key to delivery_locations table), and complete address associated with the Customer's account
3. WHEN the Customer saves information and already has 3 saved entries, THE Checkout System SHALL display an error message indicating the maximum limit has been reached
4. THE Checkout System SHALL validate that the email address is in valid email format before saving
5. THE Checkout System SHALL validate that the contact number follows the pattern "(\+63|0)9\d{9}" before saving
6. THE Checkout System SHALL validate that delivery location is selected from the delivery_locations dropdown before saving
7. THE Checkout System SHALL validate that complete address is provided before saving
8. THE Checkout System SHALL allow the Customer to provide an optional label for each saved entry (e.g., "Home", "Office", "Mom's House")

### Requirement 2: Manage Multiple Saved Entries

**User Story:** As a customer, I want to manage up to 3 different saved information sets, so that I can easily switch between different recipients or delivery locations.

#### Acceptance Criteria

1. THE Checkout System SHALL allow the Customer to store a maximum of 3 saved information entries
2. WHEN the Customer views their saved entries, THE Checkout System SHALL display all saved entries with label (if provided), name, email, contact number, delivery location (municipality, city), and complete address preview
3. WHEN the Customer selects a saved entry, THE Checkout System SHALL populate the checkout form with the saved name, email, contact number, delivery location dropdown selection, and complete address
4. WHEN the Customer selects a saved entry with a delivery location, THE Checkout System SHALL automatically calculate and display the delivery fee from the delivery_locations table
5. THE Checkout System SHALL allow the Customer to designate one entry as the primary entry
6. THE Checkout System SHALL allow the Customer to edit any saved entry including label, name, email, contact number, delivery location, and complete address
7. THE Checkout System SHALL allow the Customer to delete any saved entry
8. WHEN the Customer deletes a saved entry, THE Checkout System SHALL remove the entry from storage permanently
9. WHEN the Customer deletes the primary entry, THE Checkout System SHALL automatically set another saved entry as primary if available

### Requirement 3: Auto-populate Checkout Forms

**User Story:** As a customer, I want my primary saved information to automatically fill the checkout form, so that I can complete my order quickly.

#### Acceptance Criteria

1. WHEN the Customer navigates to the checkout page (checkout.php or availtoday-checkout.php), THE Checkout System SHALL automatically populate the name, email, and contact number fields with the primary saved entry if one exists
2. WHEN the Customer selects delivery method on the checkout page, THE Checkout System SHALL automatically populate the delivery location dropdown and complete address field with the primary saved entry if one exists
3. WHEN the Customer selects delivery method and a saved entry with delivery location is loaded, THE Checkout System SHALL automatically calculate and display the delivery fee
4. WHEN the Customer selects pickup method on the checkout page, THE Checkout System SHALL populate the name, email, and contact number fields from the primary saved entry
5. THE Checkout System SHALL allow the Customer to manually override auto-populated fields
6. WHEN the Customer has no saved entries, THE Checkout System SHALL display empty form fields as currently implemented
7. THE Checkout System SHALL provide a dropdown or selection interface for the Customer to choose a different saved entry during checkout
8. WHEN the Customer switches between saved entries during checkout, THE Checkout System SHALL update all form fields and the delivery fee display accordingly

### Requirement 4: Data Persistence and Security

**User Story:** As a customer, I want my saved information to be securely stored and accessible only to me, so that my personal data remains private.

#### Acceptance Criteria

1. THE Checkout System SHALL store saved information entries in a dedicated database table linked to the Customer's user ID
2. THE Checkout System SHALL retrieve saved information only for the authenticated Customer
3. THE Checkout System SHALL prevent access to saved information when the Customer is not logged in
4. THE Checkout System SHALL maintain saved information entries until explicitly deleted by the Customer
5. THE Checkout System SHALL ensure that saved information is not shared between different Customer accounts

### Requirement 5: User Interface Integration

**User Story:** As a customer, I want an intuitive interface to manage my saved information, so that I can easily add, edit, or select my delivery information.

#### Acceptance Criteria

1. THE Checkout System SHALL display a "Saved Information" dropdown or selection interface on both checkout pages (checkout.php and availtoday-checkout.php)
2. THE Checkout System SHALL display a "Manage Saved Information" button or link on both checkout pages
3. WHEN the Customer clicks "Manage Saved Information", THE Checkout System SHALL display a modal showing all saved entries
4. THE Checkout System SHALL display each saved entry with custom label (if provided) or default label (e.g., "Info 1"), name, email, contact number preview, delivery location (municipality, city), and complete address preview
5. THE Checkout System SHALL display a "Primary" badge on the primary saved entry
6. THE Checkout System SHALL provide "Edit", "Delete", and "Set as Primary" actions for each saved entry
7. THE Checkout System SHALL provide an "Add New" button when fewer than 3 entries are saved
8. WHEN the Customer selects a saved entry from the dropdown during checkout, THE Checkout System SHALL immediately populate the name, email, contact number, delivery location dropdown, and complete address fields
9. WHEN the Customer selects a saved entry with delivery location during checkout, THE Checkout System SHALL immediately update the shipping fee display
10. THE Checkout System SHALL provide a "Save this information" checkbox on the checkout form for new customers or when entering new information


## Database Schema

### Table: saved_customer_info

This table stores saved customer checkout information for quick reuse during future orders.

```sql
CREATE TABLE saved_customer_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) DEFAULT NULL COMMENT 'Optional custom label like "Home", "Office", etc.',
    first_name VARCHAR(100) NOT NULL COMMENT 'Recipient first name',
    last_name VARCHAR(100) NOT NULL COMMENT 'Recipient last name',
    email VARCHAR(255) NOT NULL COMMENT 'Recipient email address',
    phone VARCHAR(20) NOT NULL COMMENT 'Contact number in format (+63|0)9xxxxxxxxx',
    delivery_location_id INT NOT NULL COMMENT 'Foreign key to delivery_locations table',
    complete_address TEXT NOT NULL COMMENT 'Detailed address (house number, street, subdivision, landmarks)',
    is_primary TINYINT(1) DEFAULT 0 COMMENT '1 if this is the primary/default entry, 0 otherwise',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_location_id) REFERENCES delivery_locations(delivery_id) ON DELETE RESTRICT,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_primary (is_primary),
    INDEX idx_user_primary (user_id, is_primary),
    
    CONSTRAINT chk_max_entries CHECK (
        (SELECT COUNT(*) FROM saved_customer_info WHERE user_id = user_id) <= 3
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Table Relationships:**
- `user_id` → `users.id` (CASCADE DELETE: when user is deleted, all saved info is deleted)
- `delivery_location_id` → `delivery_locations.delivery_id` (RESTRICT DELETE: cannot delete delivery location if it's referenced in saved info)

**Constraints:**
- Maximum 3 entries per user (enforced at application level)
- Only one primary entry per user (enforced at application level)
- Email validation enforced at application level
- Phone number validation enforced at application level
- All fields except `label` are required

**Notes:**
- When a user sets a new primary entry, the application must update all other entries for that user to set `is_primary = 0`
- The `label` field is optional and defaults to NULL; the UI can display "Info 1", "Info 2", etc. when label is not provided
- The `first_name`, `last_name`, and `email` fields allow customers to save information for different recipients (e.g., sending gifts to family members)
- The `complete_address` field stores the detailed address text entered by the customer
- Delivery fee is retrieved from the `delivery_locations` table via the `delivery_location_id` foreign key
