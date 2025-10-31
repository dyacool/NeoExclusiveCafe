# Saved Customer Information Feature - Complete Implementation Guide

## 📋 Overview
This document provides a comprehensive guide to the Saved Customer Information feature implemented in the NeoCafe system. This feature allows users to save up to 3 customer information entries (name, email, phone, delivery location, complete address) for quick checkout.

---

## 🗄️ Database Schema

### Table: `saved_customer_info`

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
    INDEX idx_user_primary (user_id, is_primary),
    INDEX idx_user_created (user_id, created_at)
);
```

**Key Constraints:**
- Maximum 3 entries per user (enforced in application logic)
- Each user must have exactly 1 primary entry (if they have any entries)
- First entry is automatically set as primary
- Foreign keys ensure data integrity

---

## 🔧 Backend API Endpoints

### 1. **GET** `/backend/api/get-saved-info.php`
**Purpose:** Retrieve all saved customer info entries for the logged-in user

**Response:**
```json
{
  "success": true,
  "entries": [
    {
      "id": 1,
      "label": "Home",
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "09123456789",
      "delivery_location_id": 5,
      "delivery_location": "Sta. Rosa, Laguna 4026",
      "delivery_fee": 50.00,
      "complete_address": "123 Main St, Subdivision",
      "is_primary": 1
    }
  ],
  "count": 1
}
```

**Auto-Fix Logic:**
- Checks if user has entries but no primary
- Automatically sets oldest entry as primary if none exists

---

### 2. **GET** `/backend/api/get-primary-info.php`
**Purpose:** Retrieve only the primary customer info entry

**Response:**
```json
{
  "success": true,
  "entry": {
    "id": 1,
    "label": "Home",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "09123456789",
    "delivery_location_id": 5,
    "delivery_location": "Sta. Rosa, Laguna 4026",
    "delivery_fee": 50.00,
    "complete_address": "123 Main St, Subdivision",
    "is_primary": 1
  }
}
```

**Auto-Fix Logic:**
- Same as get-saved-info.php
- Returns only the primary entry

---

### 3. **POST** `/backend/api/save-customer-info.php`
**Purpose:** Create new or update existing customer info entry

**Request Body:**
```json
{
  "id": null,  // null for new, number for update
  "label": "Home",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "09123456789",
  "delivery_location_id": 5,
  "complete_address": "123 Main St, Subdivision",
  "set_as_primary": false
}
```

**Validation:**
- First name: Required
- Last name: Required
- Email: Required, valid email format
- Phone: Required, format `09xxxxxxxxx` (11 digits)
- Delivery location: Required, must exist in database
- Complete address: Required
- Maximum 3 entries per user

**Auto-Primary Logic:**
- If this is the user's first entry, automatically sets `is_primary = 1`
- If `set_as_primary = true`, sets all other entries to 0 first

**Response:**
```json
{
  "success": true,
  "message": "Information saved successfully",
  "entry_id": 1,
  "is_primary": 1
}
```

---

### 4. **POST** `/backend/api/set-primary-info.php`
**Purpose:** Set a specific entry as primary

**Request Body:**
```json
{
  "id": 2
}
```

**Logic:**
1. Sets all user's entries to `is_primary = 0`
2. Sets specified entry to `is_primary = 1`

**Response:**
```json
{
  "success": true,
  "message": "Primary entry updated successfully"
}
```

---

### 5. **POST** `/backend/api/delete-saved-info.php`
**Purpose:** Delete a saved customer info entry

**Request Body:**
```json
{
  "id": 2
}
```

**Auto-Promote Logic:**
- If deleted entry was primary AND other entries exist
- Automatically promotes oldest remaining entry to primary

**Response:**
```json
{
  "success": true,
  "message": "Entry deleted successfully",
  "was_primary": 1,
  "new_primary_id": 3
}
```

---

## 🎨 Frontend Components

### Files Structure:
```
frontend/pages/cart/
├── saved-info-manager.js    # Core logic & API calls
├── saved-info-ui.js          # UI rendering & modal management
├── saved-info.css            # Styling
├── checkout.php              # Regular checkout integration
└── availtoday-checkout.php   # Same-day checkout integration
```

---

### `saved-info-manager.js` - Core Functions

#### **Class: SavedInfoManager**

**Key Methods:**

1. **`loadEntries()`**
   - Fetches all saved entries from API
   - Populates selector dropdown
   - Auto-fills primary entry
   - Updates name display

2. **`autofillPrimary()`**
   - Finds primary entry
   - Fills form fields automatically
   - Updates name display with badge

3. **`fillForm(entry)`**
   - Populates all form fields with entry data
   - Triggers delivery location change event
   - Updates delivery fee

4. **`saveCurrentInfo(label, setAsPrimary)`**
   - Validates form data
   - Calls save API
   - Reloads entries list

5. **`deleteEntry(entryId)`**
   - Confirms deletion
   - Calls delete API
   - Reloads entries list

6. **`setPrimary(entryId)`**
   - Calls set-primary API
   - Reloads entries list

7. **`autoSaveOnFirstCheckout()`**
   - Checks if user has 0 entries
   - Auto-saves current form data as primary
   - Labels as "My First Address"

8. **`loadPrimaryInfoAutomatically()`**
   - Fetches primary info
   - Auto-fills form
   - Used for automatic loading

---

### `saved-info-ui.js` - UI Functions

**Key Functions:**

1. **`openSavedInfoModal()`**
   - Opens management modal
   - Renders all saved entries
   - Provides edit/delete/set-primary actions

2. **`renderSavedEntries()`**
   - Creates HTML for each entry
   - Shows primary badge
   - Adds action buttons

3. **`editEntry(entryId)`**
   - Loads entry data into inline form
   - Allows editing all fields
   - Saves changes via API

4. **`deleteEntryUI(entryId)`**
   - Confirms deletion
   - Calls manager's delete method
   - Updates UI

5. **`setPrimaryUI(entryId)`**
   - Calls manager's set-primary method
   - Updates UI with new primary

---

## 🔄 Auto-Load & Auto-Save Features

### 1. **Auto-Load on Page Load**
**Location:** `checkout.php`, `availtoday-checkout.php`

**Flow:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    showUserInfoLoading();
    window.savedInfoManager = new SavedInfoManager();
    window.savedInfoManager.loadEntries().then(() => {
        hideUserInfoLoading();
    });
});
```

**What Happens:**
1. Shows loading spinner
2. Creates SavedInfoManager instance
3. Loads all entries
4. Auto-fills primary entry (via `autofillPrimary()`)
5. Hides loading spinner

---

### 2. **Auto-Load Delivery Address**
**Location:** `checkout.php`

**Flow:**
```javascript
async function loadPrimaryCustomerAddress() {
    const response = await fetch('../../../backend/api/get-primary-info.php');
    const data = await response.json();
    
    if (data.success && data.entry && data.entry.complete_address) {
        // Auto-fill delivery address
        deliveryAddressInput.value = `${data.entry.complete_address}, ${data.entry.delivery_location}`;
        // Update delivery fee
        shippingFeeElement.textContent = '₱' + data.entry.delivery_fee.toFixed(2);
        // Update total
        updateTotalWithShipping(data.entry.delivery_fee);
    }
}

// Trigger on delivery radio selection
deliveryRadioBtn.addEventListener('change', function() {
    if (this.checked) {
        loadPrimaryCustomerAddress();
    }
});
```

**What Happens:**
1. User selects "Delivery" radio button
2. System fetches primary customer info
3. If complete_address exists, auto-fills delivery address field
4. Calculates and displays delivery fee
5. Updates order total

---

### 3. **Auto-Update Primary with Location**
**Location:** `checkout.php`

**Flow:**
```javascript
async function updatePrimaryWithLocation(deliveryLocationId, completeAddress) {
    // Check if user has primary info
    const checkResponse = await fetch('../../../backend/api/get-primary-info.php');
    const checkData = await checkResponse.json();
    
    if (checkData.success && checkData.entry) {
        // Check if complete_address is empty
        if (!checkData.entry.complete_address || checkData.entry.complete_address.trim() === '') {
            // Update primary info with new location
            await fetch('../../../backend/api/save-customer-info.php', {
                method: 'POST',
                body: JSON.stringify({
                    id: checkData.entry.id,
                    // ... other fields
                    delivery_location_id: deliveryLocationId,
                    complete_address: completeAddress,
                    set_as_primary: true
                })
            });
        }
    }
}
```

**What Happens:**
1. User enters delivery location for first time
2. System checks if primary info has empty address
3. If empty, updates primary info with new location
4. Future checkouts will have address pre-filled

---

### 4. **Auto-Save on First Order**
**Location:** `payment-return.php`

**Function:** `autoSaveCustomerInfo()`

**Flow:**
```php
function autoSaveCustomerInfo($customer_name, $customer_email, $customer_contact, $customer_address, $order_data) {
    // Check if user has any saved info
    $check_sql = "SELECT COUNT(*) as count FROM saved_customer_info WHERE user_id = ?";
    // ... execute query
    
    if ($existing_count === 0) {
        // User has no saved info, create first entry
        // Parse name into first/last
        // Extract delivery location
        // Insert as primary (is_primary = 1)
    }
}
```

**What Happens:**
1. User completes first order via PayMongo
2. Payment succeeds
3. System checks if user has saved info
4. If none, automatically creates entry from order data
5. Sets as primary
6. Next checkout will have info pre-filled

---

## 🎯 Primary Management Rules

### **Rule 1: First Entry is Always Primary**
- When user saves their first entry, `is_primary = 1` automatically
- Implemented in: `save-customer-info.php`

### **Rule 2: Auto-Promote if No Primary**
- If user has entries but no primary, oldest becomes primary
- Implemented in: `get-saved-info.php`, `get-primary-info.php`, `payment-return.php`

### **Rule 3: Only One Primary at a Time**
- When setting new primary, all others set to 0 first
- Implemented in: `set-primary-info.php`, `save-customer-info.php`

### **Rule 4: Auto-Promote on Deletion**
- When deleting primary, oldest remaining becomes primary
- Implemented in: `delete-saved-info.php`

### **Rule 5: Every User Must Have One Primary**
- If user has entries, exactly one must be primary
- Self-healing logic in all API endpoints

---

## 📱 User Interface Elements

### 1. **Load Contacts Button**
```html
<button id="loadContactsBtn" class="btn-load-contacts">
    📋 Load Contacts and Address
</button>
```
- Opens saved info modal
- Shows all saved entries
- Allows selection, editing, deletion

### 2. **User Name Display**
```html
<span id="user-name">John Doe <span class="saved-info-badge">2 saved</span></span>
```
- Shows current customer name
- Badge indicates number of saved entries
- Updates when entries are loaded

### 3. **Save This Info Checkbox**
```html
<input type="checkbox" id="saveThisInfo">
<label>Save this information for future orders</label>
```
- Allows saving current form data
- Shows label input when checked
- Provides "Save Now" button

### 4. **Saved Info Selector**
```html
<select id="savedInfoSelector">
    <option value="">-- Enter new information --</option>
    <option value="1">🏠 Home (Primary)</option>
    <option value="2">🏢 Office</option>
</select>
```
- Dropdown to select saved entry
- Shows icons based on label
- Indicates primary entry

### 5. **Saved Info Modal**
```html
<div id="savedInfoModal" class="saved-info-modal">
    <!-- Entry cards with edit/delete/set-primary buttons -->
</div>
```
- Shows all saved entries as cards
- Inline editing capability
- Delete and set-primary actions

---

## 🔐 Security & Validation

### **Backend Validation:**
- Session authentication required
- User role must be 'user'
- Entry ownership verified before operations
- SQL injection prevention (prepared statements)
- XSS prevention (input sanitization)

### **Frontend Validation:**
- Email format validation
- Phone number format: `09xxxxxxxxx`
- Required field checks
- Delivery location existence check
- Maximum 3 entries enforcement

### **Data Integrity:**
- Foreign key constraints
- Cascade delete on user deletion
- Restrict delete on delivery location
- Automatic primary management
- Transaction support for critical operations

---

## 🧪 Testing Scenarios

### **Scenario 1: New User First Checkout**
1. User has no saved info
2. User fills checkout form
3. User completes order
4. System auto-saves info as primary
5. ✅ Next checkout: info auto-loaded

### **Scenario 2: User with Empty Address**
1. User has primary info but no address
2. User selects delivery
3. User enters location
4. System updates primary with location
5. ✅ Next checkout: address auto-loaded

### **Scenario 3: User with Multiple Entries**
1. User has 3 saved entries
2. User opens saved info modal
3. User sets entry #2 as primary
4. System updates all entries
5. ✅ Entry #2 is now primary

### **Scenario 4: Delete Primary Entry**
1. User has 2 entries, #1 is primary
2. User deletes entry #1
3. System auto-promotes entry #2
4. ✅ Entry #2 is now primary

### **Scenario 5: Data Corruption Recovery**
1. User has 3 entries, none primary (data issue)
2. User loads checkout page
3. System detects no primary
4. System auto-sets oldest as primary
5. ✅ Data integrity restored

---

## 📊 Database Queries Reference

### **Get All Entries:**
```sql
SELECT sci.*, 
       CONCAT(dl.municipality, ', ', dl.city, ' ', dl.postal_code) as delivery_location,
       dl.delivery_fee
FROM saved_customer_info sci
JOIN delivery_locations dl ON sci.delivery_location_id = dl.delivery_id
WHERE sci.user_id = ?
ORDER BY sci.is_primary DESC, sci.created_at ASC
LIMIT 3;
```

### **Get Primary Entry:**
```sql
SELECT sci.*, 
       CONCAT(dl.municipality, ', ', dl.city, ' ', dl.postal_code) as delivery_location,
       dl.delivery_fee
FROM saved_customer_info sci
JOIN delivery_locations dl ON sci.delivery_location_id = dl.delivery_id
WHERE sci.user_id = ? AND sci.is_primary = 1
LIMIT 1;
```

### **Check Primary Status:**
```sql
SELECT COUNT(*) as total, SUM(is_primary) as primary_count
FROM saved_customer_info
WHERE user_id = ?;
```

### **Set First as Primary:**
```sql
UPDATE saved_customer_info
SET is_primary = 1
WHERE user_id = ?
ORDER BY created_at ASC
LIMIT 1;
```

### **Clear All Primary:**
```sql
UPDATE saved_customer_info
SET is_primary = 0
WHERE user_id = ?;
```

---

## 🚀 Deployment Checklist

- [x] Database table created
- [x] Foreign keys configured
- [x] Indexes added for performance
- [x] API endpoints implemented
- [x] Frontend components created
- [x] Auto-load functionality added
- [x] Auto-save functionality added
- [x] Primary management logic implemented
- [x] Validation rules enforced
- [x] Error handling implemented
- [x] Logging added for debugging
- [x] Integration with checkout pages
- [x] Integration with payment processing
- [x] Test files removed
- [x] Documentation created

---

## 🐛 Troubleshooting

### **Issue: No primary entry found**
**Solution:** Auto-fix logic will set oldest as primary automatically

### **Issue: Multiple primary entries**
**Solution:** Use set-primary API to reset, it clears all then sets one

### **Issue: Address not auto-loading**
**Solution:** Check browser console for API errors, verify primary entry exists

### **Issue: Can't save more than 3 entries**
**Solution:** This is by design, user must delete one first

### **Issue: Delivery fee not calculating**
**Solution:** Verify delivery_location_id exists in delivery_locations table

---

## 📝 Future Enhancements

1. **Address Validation:** Integrate with Google Maps API for address verification
2. **Default Labels:** Suggest labels based on delivery location
3. **Address History:** Track address usage frequency
4. **Bulk Operations:** Allow exporting/importing saved addresses
5. **Address Sharing:** Allow sharing addresses between family members
6. **Mobile Optimization:** Improve mobile UI/UX
7. **Address Autocomplete:** Suggest addresses as user types

---

## 📞 Support & Maintenance

**Key Files to Monitor:**
- `backend/api/save-customer-info.php` - Entry creation/updates
- `frontend/pages/cart/saved-info-manager.js` - Core logic
- `frontend/pages/cart/payment-return.php` - Auto-save on order

**Logging:**
- All API operations logged with `error_log()`
- Check PHP error logs for issues
- Browser console for frontend errors

**Database Maintenance:**
- Monitor `saved_customer_info` table size
- Check for orphaned entries (user deleted)
- Verify primary integrity periodically

---

## ✅ Summary

The Saved Customer Information feature provides a complete, automatic, and user-friendly system for managing customer checkout information. With intelligent auto-loading, auto-saving, and primary management, users enjoy a seamless checkout experience while the system maintains data integrity automatically.

**Key Benefits:**
- ✅ Faster checkout process
- ✅ Reduced data entry errors
- ✅ Improved user experience
- ✅ Automatic data management
- ✅ Self-healing data integrity
- ✅ Secure and validated

---

**Document Version:** 1.0  
**Last Updated:** 2025-10-31  
**Author:** Kiro AI Assistant  
**Status:** Production Ready ✅
