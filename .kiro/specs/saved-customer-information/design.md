# Design Document: Saved Customer Information

## Overview

This design document outlines the implementation approach for the saved customer information feature, which allows customers to store up to 3 sets of checkout information (name, email, contact number, delivery location, and address) for quick reuse during future orders. The feature integrates seamlessly with both checkout pages (checkout.php and availtoday-checkout.php) and leverages the existing delivery_locations infrastructure.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend Layer                           │
├─────────────────────────────────────────────────────────────┤
│  • checkout.php (Pre-order Checkout)                        │
│  • availtoday-checkout.php (Same-day Checkout)              │
│  • saved-info-manager.js (Client-side logic)                │
│  • saved-info-ui.js (UI components & modal)                 │
└─────────────────────────────────────────────────────────────┘
                            ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                     Backend API Layer                        │
├─────────────────────────────────────────────────────────────┤
│  • get-saved-info.php (Retrieve saved entries)              │
│  • save-customer-info.php (Create/Update entry)             │
│  • delete-saved-info.php (Delete entry)                     │
│  • set-primary-info.php (Set primary entry)                 │
└─────────────────────────────────────────────────────────────┘
                            ↓ ↑
┌─────────────────────────────────────────────────────────────┐
│                     Database Layer                           │
├─────────────────────────────────────────────────────────────┤
│  • saved_customer_info (Main table)                         │
│  • delivery_locations (Reference table)                     │
│  • users (Reference table)                                  │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

**1. Page Load Flow:**
```
User loads checkout page
    → Frontend checks for saved entries (AJAX call to get-saved-info.php)
    → Backend retrieves entries for user_id
    → Frontend receives entries with delivery location details
    → If primary entry exists, auto-populate form fields
    → Display saved info dropdown/selector
```

**2. Save Information Flow:**
```
User fills checkout form + checks "Save this information"
    → User clicks save/checkout button
    → Frontend validates all fields
    → AJAX call to save-customer-info.php with form data
    → Backend validates (max 3 entries, valid data)
    → Insert into saved_customer_info table
    → Return success/error response
    → Frontend updates UI (refresh saved entries list)
```

**3. Select Saved Entry Flow:**
```
User selects entry from dropdown
    → Frontend retrieves entry data from local cache
    → Populate name, email, phone fields
    → Set delivery location dropdown value
    → Populate complete address field
    → Trigger delivery fee calculation
    → Update shipping fee display
```

## Components and Interfaces

### 1. Database Schema

**saved_customer_info Table:**
```sql
CREATE TABLE saved_customer_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) DEFAULT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    delivery_location_id INT NOT NULL,
    complete_address TEXT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_location_id) REFERENCES delivery_locations(delivery_id) ON DELETE RESTRICT,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_primary (is_primary),
    INDEX idx_user_primary (user_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Backend API Endpoints

#### A. Get Saved Information (get-saved-info.php)

**Purpose:** Retrieve all saved information entries for the logged-in user

**Request:**
- Method: GET
- Authentication: Session-based (user_id from $_SESSION)
- Parameters: None

**Response:**
```json
{
  "success": true,
  "entries": [
    {
      "id": 1,
      "label": "Home",
      "first_name": "Juan",
      "last_name": "Dela Cruz",
      "email": "juan@example.com",
      "phone": "09171234567",
      "delivery_location_id": 5,
      "delivery_location": "Sta. Rosa, Laguna 4026",
      "delivery_fee": 150.00,
      "complete_address": "123 Main St, Greenville Subd.",
      "is_primary": 1
    },
    {
      "id": 2,
      "label": "Office",
      "first_name": "Juan",
      "last_name": "Dela Cruz",
      "email": "juan.work@example.com",
      "phone": "09181234567",
      "delivery_location_id": 8,
      "delivery_location": "Cabuyao, Laguna 4025",
      "delivery_fee": 120.00,
      "complete_address": "456 Business Park, Tower A",
      "is_primary": 0
    }
  ],
  "count": 2
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

**Implementation Notes:**
- Join with delivery_locations table to get location details and delivery_fee
- Order by is_primary DESC, created_at ASC
- Only return entries for the authenticated user

#### B. Save Customer Information (save-customer-info.php)

**Purpose:** Create or update a saved information entry

**Request:**
- Method: POST
- Authentication: Session-based (user_id from $_SESSION)
- Parameters:
```json
{
  "id": null,  // null for new entry, integer for update
  "label": "Home",  // optional
  "first_name": "Juan",
  "last_name": "Dela Cruz",
  "email": "juan@example.com",
  "phone": "09171234567",
  "delivery_location_id": 5,
  "complete_address": "123 Main St, Greenville Subd.",
  "set_as_primary": true  // optional, default false
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Information saved successfully",
  "entry_id": 1,
  "is_primary": true
}
```

**Response (Error - Max Limit):**
```json
{
  "success": false,
  "error": "Maximum 3 saved entries allowed. Please delete an existing entry first."
}
```

**Response (Error - Validation):**
```json
{
  "success": false,
  "error": "Invalid phone number format",
  "field": "phone"
}
```

**Validation Rules:**
- Check user has < 3 entries (for new entries)
- Validate email format
- Validate phone format: `(\+63|0)9\d{9}`
- Validate delivery_location_id exists in delivery_locations table
- Validate all required fields are present
- If set_as_primary is true, update all other entries for user to is_primary = 0

#### C. Delete Saved Information (delete-saved-info.php)

**Purpose:** Delete a saved information entry

**Request:**
- Method: POST
- Authentication: Session-based (user_id from $_SESSION)
- Parameters:
```json
{
  "id": 1
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Entry deleted successfully",
  "was_primary": true,
  "new_primary_id": 2  // if another entry was set as primary
}
```

**Implementation Notes:**
- Verify the entry belongs to the authenticated user
- If deleted entry was primary and other entries exist, automatically set the oldest remaining entry as primary
- Return the new primary entry ID if applicable

#### D. Set Primary Information (set-primary-info.php)

**Purpose:** Set a specific entry as the primary entry

**Request:**
- Method: POST
- Authentication: Session-based (user_id from $_SESSION)
- Parameters:
```json
{
  "id": 2
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Primary entry updated successfully"
}
```

**Implementation Notes:**
- Verify the entry belongs to the authenticated user
- Set all other entries for user to is_primary = 0
- Set specified entry to is_primary = 1

### 3. Frontend Components

#### A. Saved Information Selector (UI Component)

**Location:** Both checkout.php and availtoday-checkout.php

**HTML Structure:**
```html
<div class="saved-info-section">
    <div class="saved-info-header">
        <label>Use Saved Information</label>
        <button type="button" id="manageSavedInfoBtn" class="btn-link">
            Manage Saved Information
        </button>
    </div>
    
    <select id="savedInfoSelector" class="form-control">
        <option value="">-- Enter new information --</option>
        <option value="1" data-primary="true">🏠 Home (Primary)</option>
        <option value="2">🏢 Office</option>
        <option value="3">📍 Mom's House</option>
    </select>
    
    <div class="save-current-info">
        <label>
            <input type="checkbox" id="saveThisInfo" name="save_this_info">
            Save this information for future orders
        </label>
        <input type="text" id="infoLabel" name="info_label" 
               placeholder="Label (optional, e.g., Home, Office)" 
               maxlength="50" style="display: none;">
    </div>
</div>
```

**Behavior:**
- On page load, fetch saved entries and populate dropdown
- When user selects an entry, populate all form fields
- When "Save this information" is checked, show label input field
- Primary entry is marked with badge/icon

#### B. Manage Saved Information Modal

**HTML Structure:**
```html
<div id="savedInfoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Manage Saved Information</h2>
            <span class="close-btn">&times;</span>
        </div>
        <div class="modal-body">
            <div class="saved-entries-list">
                <!-- Entry Card -->
                <div class="saved-entry-card" data-entry-id="1">
                    <div class="entry-header">
                        <h3>🏠 Home</h3>
                        <span class="primary-badge">Primary</span>
                    </div>
                    <div class="entry-details">
                        <p><strong>Name:</strong> Juan Dela Cruz</p>
                        <p><strong>Email:</strong> juan@example.com</p>
                        <p><strong>Phone:</strong> 09171234567</p>
                        <p><strong>Location:</strong> Sta. Rosa, Laguna 4026</p>
                        <p><strong>Address:</strong> 123 Main St, Greenville Subd.</p>
                    </div>
                    <div class="entry-actions">
                        <button class="btn-edit" data-entry-id="1">Edit</button>
                        <button class="btn-delete" data-entry-id="1">Delete</button>
                        <button class="btn-set-primary" data-entry-id="1" disabled>
                            Set as Primary
                        </button>
                    </div>
                </div>
                
                <!-- Add New Button (if < 3 entries) -->
                <button id="addNewEntryBtn" class="btn-add-new">
                    + Add New Information
                </button>
            </div>
        </div>
    </div>
</div>
```

**Features:**
- Display all saved entries with full details
- Edit button opens form with pre-filled data
- Delete button shows confirmation dialog
- Set as Primary button (disabled for current primary)
- Add New button (hidden if 3 entries exist)

#### C. JavaScript Module: saved-info-manager.js

**Core Functions:**

```javascript
class SavedInfoManager {
    constructor() {
        this.entries = [];
        this.currentEntryId = null;
    }
    
    // Load all saved entries from backend
    async loadEntries() {
        const response = await fetch('backend/api/get-saved-info.php');
        const data = await response.json();
        if (data.success) {
            this.entries = data.entries;
            this.populateSelector();
            this.autofillPrimary();
        }
    }
    
    // Populate the saved info dropdown
    populateSelector() {
        const selector = document.getElementById('savedInfoSelector');
        selector.innerHTML = '<option value="">-- Enter new information --</option>';
        
        this.entries.forEach(entry => {
            const option = document.createElement('option');
            option.value = entry.id;
            option.textContent = this.getEntryLabel(entry);
            option.dataset.entryData = JSON.stringify(entry);
            selector.appendChild(option);
        });
    }
    
    // Get display label for entry
    getEntryLabel(entry) {
        const icon = this.getIconForLabel(entry.label);
        const label = entry.label || `Info ${entry.id}`;
        const primary = entry.is_primary ? ' (Primary)' : '';
        return `${icon} ${label}${primary}`;
    }
    
    // Auto-fill form with primary entry
    autofillPrimary() {
        const primary = this.entries.find(e => e.is_primary === 1);
        if (primary) {
            this.fillForm(primary);
        }
    }
    
    // Fill form fields with entry data
    fillForm(entry) {
        // Fill name fields (check if they exist - user info section)
        const firstNameField = document.getElementById('first_name');
        const lastNameField = document.getElementById('last_name');
        if (firstNameField) firstNameField.value = entry.first_name;
        if (lastNameField) lastNameField.value = entry.last_name;
        
        // Fill email
        const emailField = document.getElementById('customer_email') || 
                          document.getElementById('email');
        if (emailField) emailField.value = entry.email;
        
        // Fill phone
        const phoneField = document.getElementById('contact_number') || 
                          document.getElementById('phone');
        if (phoneField) phoneField.value = entry.phone;
        
        // Fill delivery location dropdown
        const locationSelect = document.getElementById('delivery_location');
        if (locationSelect) {
            locationSelect.value = this.getLocationValue(entry.delivery_location_id);
            // Trigger change event to update delivery fee
            locationSelect.dispatchEvent(new Event('change'));
        }
        
        // Fill complete address
        const addressField = document.getElementById('complete_address') || 
                            document.getElementById('delivery_address');
        if (addressField) addressField.value = entry.complete_address;
        
        this.currentEntryId = entry.id;
    }
    
    // Get location dropdown value from delivery_location_id
    getLocationValue(locationId) {
        const locationSelect = document.getElementById('delivery_location');
        const options = locationSelect.querySelectorAll('option');
        
        for (let option of options) {
            if (option.dataset.locationId == locationId) {
                return option.value;
            }
        }
        return '';
    }
    
    // Save current form data as new entry
    async saveCurrentInfo(label = null) {
        const formData = this.getFormData();
        formData.label = label;
        
        const response = await fetch('backend/api/save-customer-info.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        if (data.success) {
            await this.loadEntries(); // Refresh entries
            return true;
        } else {
            alert(data.error);
            return false;
        }
    }
    
    // Get current form data
    getFormData() {
        return {
            first_name: document.getElementById('first_name')?.value || '',
            last_name: document.getElementById('last_name')?.value || '',
            email: (document.getElementById('customer_email') || 
                   document.getElementById('email'))?.value || '',
            phone: (document.getElementById('contact_number') || 
                   document.getElementById('phone'))?.value || '',
            delivery_location_id: this.getSelectedLocationId(),
            complete_address: (document.getElementById('complete_address') || 
                             document.getElementById('delivery_address'))?.value || ''
        };
    }
    
    // Get selected delivery location ID
    getSelectedLocationId() {
        const locationSelect = document.getElementById('delivery_location');
        const selectedOption = locationSelect.options[locationSelect.selectedIndex];
        return selectedOption?.dataset.locationId || null;
    }
    
    // Delete entry
    async deleteEntry(entryId) {
        if (!confirm('Are you sure you want to delete this saved information?')) {
            return false;
        }
        
        const response = await fetch('backend/api/delete-saved-info.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: entryId })
        });
        
        const data = await response.json();
        if (data.success) {
            await this.loadEntries(); // Refresh entries
            return true;
        } else {
            alert(data.error);
            return false;
        }
    }
    
    // Set entry as primary
    async setPrimary(entryId) {
        const response = await fetch('backend/api/set-primary-info.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: entryId })
        });
        
        const data = await response.json();
        if (data.success) {
            await this.loadEntries(); // Refresh entries
            return true;
        } else {
            alert(data.error);
            return false;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    window.savedInfoManager = new SavedInfoManager();
    window.savedInfoManager.loadEntries();
    
    // Setup event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // Saved info selector change
    document.getElementById('savedInfoSelector')?.addEventListener('change', function(e) {
        if (e.target.value) {
            const entry = JSON.parse(e.target.selectedOptions[0].dataset.entryData);
            window.savedInfoManager.fillForm(entry);
        }
    });
    
    // Save this info checkbox
    document.getElementById('saveThisInfo')?.addEventListener('change', function(e) {
        const labelInput = document.getElementById('infoLabel');
        labelInput.style.display = e.target.checked ? 'block' : 'none';
    });
    
    // Manage saved info button
    document.getElementById('manageSavedInfoBtn')?.addEventListener('click', function() {
        openSavedInfoModal();
    });
}
```

## Data Models

### SavedInfoEntry Model

```javascript
{
    id: number,
    user_id: number,
    label: string | null,
    first_name: string,
    last_name: string,
    email: string,
    phone: string,
    delivery_location_id: number,
    delivery_location: string,  // Formatted: "Municipality, City PostalCode"
    delivery_fee: number,
    complete_address: string,
    is_primary: boolean (0 or 1),
    created_at: string,
    updated_at: string
}
```

## Error Handling

### Frontend Validation

1. **Before Saving:**
   - Validate email format using regex: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
   - Validate phone format: `/^(\+63|0)9\d{9}$/`
   - Ensure delivery location is selected
   - Ensure complete address is not empty
   - Check if user already has 3 entries (if adding new)

2. **User Feedback:**
   - Display inline error messages below invalid fields
   - Show toast/alert for API errors
   - Disable save button during API calls
   - Show loading spinner during operations

### Backend Validation

1. **Authentication:**
   - Check if user is logged in (session exists)
   - Verify user_id in session
   - Return 401 Unauthorized if not authenticated

2. **Data Validation:**
   - Sanitize all input data
   - Validate email format
   - Validate phone format
   - Check delivery_location_id exists in delivery_locations table
   - Verify entry ownership (user_id matches)
   - Check max entries limit (3 per user)

3. **Database Errors:**
   - Catch and log SQL errors
   - Return generic error message to frontend
   - Log detailed error for debugging

## Testing Strategy

### Unit Tests

1. **Backend API Tests:**
   - Test get-saved-info.php with valid user
   - Test get-saved-info.php with no entries
   - Test save-customer-info.php with valid data
   - Test save-customer-info.php with invalid email
   - Test save-customer-info.php with invalid phone
   - Test save-customer-info.php exceeding max limit
   - Test delete-saved-info.php with valid entry
   - Test delete-saved-info.php with non-existent entry
   - Test set-primary-info.php with valid entry

2. **Frontend JavaScript Tests:**
   - Test SavedInfoManager.loadEntries()
   - Test SavedInfoManager.fillForm()
   - Test SavedInfoManager.saveCurrentInfo()
   - Test form validation functions
   - Test delivery fee calculation trigger

### Integration Tests

1. **End-to-End Flows:**
   - User saves first entry → becomes primary → auto-fills on next visit
   - User saves second entry → can switch between entries
   - User saves third entry → "Add New" button disappears
   - User deletes primary entry → another entry becomes primary
   - User selects saved entry → delivery fee updates correctly
   - User edits entry → changes persist and reflect immediately

2. **Cross-Page Tests:**
   - Save entry on checkout.php → appears on availtoday-checkout.php
   - Set primary on one page → auto-fills on other page
   - Delete entry on one page → removed from other page

### Manual Testing Checklist

- [ ] Load checkout page with no saved entries
- [ ] Save first entry with all fields
- [ ] Verify entry appears in dropdown
- [ ] Reload page and verify auto-fill
- [ ] Add second entry with different data
- [ ] Switch between entries using dropdown
- [ ] Verify delivery fee updates when switching
- [ ] Set second entry as primary
- [ ] Reload and verify new primary auto-fills
- [ ] Add third entry
- [ ] Verify "Add New" button disappears
- [ ] Try to add fourth entry (should fail)
- [ ] Edit an entry
- [ ] Delete an entry
- [ ] Delete primary entry and verify new primary
- [ ] Test on both checkout.php and availtoday-checkout.php
- [ ] Test with pickup method (no delivery location)
- [ ] Test validation errors (invalid email, phone)

## Security Considerations

1. **Authentication:**
   - All API endpoints must verify user session
   - Never expose other users' saved information
   - Use prepared statements for all database queries

2. **Data Sanitization:**
   - Sanitize all input data before database insertion
   - Use htmlspecialchars() for output
   - Validate data types (integers, strings, etc.)

3. **SQL Injection Prevention:**
   - Use prepared statements with parameter binding
   - Never concatenate user input into SQL queries

4. **XSS Prevention:**
   - Escape all user-generated content before display
   - Use Content Security Policy headers
   - Sanitize label and address fields

5. **CSRF Protection:**
   - Implement CSRF tokens for state-changing operations
   - Validate tokens on save, delete, and update operations

## Performance Considerations

1. **Database Optimization:**
   - Index on user_id for fast lookups
   - Index on (user_id, is_primary) for primary entry queries
   - Limit query results to 3 entries per user

2. **Frontend Optimization:**
   - Cache loaded entries in JavaScript
   - Only reload entries after modifications
   - Use event delegation for dynamic elements
   - Minimize DOM manipulations

3. **API Response Size:**
   - Only return necessary fields
   - Use pagination if needed (though max 3 entries per user)
   - Compress JSON responses

## Deployment Plan

1. **Database Migration:**
   - Create saved_customer_info table
   - Add foreign key constraints
   - Create indexes
   - Test rollback procedure

2. **Backend Deployment:**
   - Deploy API endpoints
   - Test each endpoint individually
   - Monitor error logs

3. **Frontend Deployment:**
   - Add saved info selector to checkout.php
   - Add saved info selector to availtoday-checkout.php
   - Deploy JavaScript modules
   - Deploy CSS styles
   - Test on staging environment

4. **Rollout Strategy:**
   - Deploy to staging first
   - Test all functionality
   - Deploy to production during low-traffic period
   - Monitor for errors
   - Have rollback plan ready

## Future Enhancements

1. **Address Validation:**
   - Integrate with Google Maps API for address validation
   - Auto-complete address suggestions
   - Geocoding for delivery route optimization

2. **Multiple Recipients:**
   - Allow saving recipient information separate from user account
   - Gift sending feature with saved recipient addresses

3. **Address Book Sync:**
   - Import addresses from other platforms
   - Export addresses for backup

4. **Smart Suggestions:**
   - Suggest delivery location based on complete address
   - Auto-detect duplicate entries
   - Suggest label based on address patterns
