# Design Document

## Overview

This design document outlines the implementation approach for replacing the single "Shipping Method" dropdown in the product edit modal with a checkbox-based interface. The new design allows administrators to configure products for pre-order (status_id 1, 2, or 3) and/or same-day order (status_id 4) independently, making the product configuration workflow more intuitive and flexible.

The implementation will modify the existing modal structure in `backend/pages/products/product-list.php` and update the JavaScript logic in `backend/pages/products/product-list.js` to handle the new checkbox interface while maintaining backward compatibility with existing product data.

## Architecture

### Component Structure

The solution consists of three main components:

1. **Modal HTML Structure** (`product-list.php`)
   - Replace the single status dropdown with checkbox-based UI
   - Add conditional dropdown containers for pre-order and same-day order options
   - Maintain existing calendar component integration

2. **JavaScript Controller** (`product-list.js`)
   - Update `openEditModal()` function to handle checkbox state initialization
   - Add event listeners for checkbox changes
   - Implement validation logic
   - Update form submission logic

3. **Backend API** (existing `update-product.php`)
   - No changes required - existing API already handles status_id and availtoday_status_id

### Data Flow

```
User Action (Check/Uncheck) 
  → JavaScript Event Handler
  → Show/Hide Conditional Dropdowns
  → Update Form State
  → Form Submission
  → Backend API (update-product.php)
  → Database Update (products table)
  → Page Refresh/Update
```

## Components and Interfaces

### 1. Modal HTML Structure

#### Current Structure (to be replaced)
```html
<div class="form-group">
    <label for="editProductStatus">Shipping Method</label>
    <select id="editProductStatus">
        <option value="1">Pick Up</option>
        <option value="2">Delivery</option>
        <option value="3">Delivery or Pick Up</option>
        <option value="4">Same Day Order</option>
    </select>
</div>
```

#### New Structure
```html
<div class="form-group">
    <label>Order Types</label>
    
    <!-- Pre-order Checkbox -->
    <div class="checkbox-item">
        <input type="checkbox" id="editPreOrderCheckbox" onchange="handlePreOrderCheckboxChange()">
        <label for="editPreOrderCheckbox">Pre-order</label>
    </div>
    
    <!-- Pre-order Dropdown (conditional) -->
    <div id="editPreOrderOptions" style="display: none; margin-left: 24px; margin-top: 8px;">
        <label for="editPreOrderStatus">Pre-order Shipping Method:</label>
        <select id="editPreOrderStatus">
            <option value="1">Pick Up</option>
            <option value="2">Delivery</option>
            <option value="3">Delivery or Pick Up</option>
        </select>
    </div>
    
    <!-- Same-day Order Checkbox -->
    <div class="checkbox-item" style="margin-top: 12px;">
        <input type="checkbox" id="editSameDayCheckbox" onchange="handleSameDayCheckboxChange()">
        <label for="editSameDayCheckbox">Same-day order</label>
    </div>
    
    <!-- Same-day Order Dropdown (conditional) -->
    <div id="editSameDayOptions" style="display: none; margin-left: 24px; margin-top: 8px;">
        <label for="editSameDayStatus">Same-day Order Shipping Method:</label>
        <select id="editSameDayStatus">
            <option value="1">Pick Up</option>
            <option value="2">Delivery</option>
            <option value="3">Delivery and Pick Up</option>
        </select>
    </div>
</div>

<!-- Calendar container (existing - visibility controlled by same-day checkbox) -->
<div class="form-group" id="sameDayCalendarContainer" style="display: none;">
    <label>Select dates for same day order:</label>
    <div id="sameDayCalendar"></div>
    <input type="hidden" id="sameDayDates" name="same_day_dates">
</div>
```

### 2. JavaScript Functions

#### Function: `openEditModal()`
**Purpose**: Initialize modal with product data and set checkbox states

**Logic**:
```javascript
function openEditModal(id, name, description, price, status, ...) {
    // ... existing code ...
    
    // Determine checkbox states
    const isPreOrder = (status == 1 || status == 2 || status == 3);
    const isSameDay = (status == 4 || (availtodayStatusId && availtodayStatusId !== 'null'));
    
    // Set pre-order checkbox
    document.getElementById('editPreOrderCheckbox').checked = isPreOrder;
    if (isPreOrder) {
        document.getElementById('editPreOrderOptions').style.display = 'block';
        document.getElementById('editPreOrderStatus').value = status;
    }
    
    // Set same-day checkbox
    document.getElementById('editSameDayCheckbox').checked = isSameDay;
    if (isSameDay) {
        document.getElementById('editSameDayOptions').style.display = 'block';
        document.getElementById('editSameDayStatus').value = availtodayStatusId || '1';
        document.getElementById('sameDayCalendarContainer').style.display = 'block';
    }
    
    // ... rest of existing code ...
}
```

#### Function: `handlePreOrderCheckboxChange()`
**Purpose**: Show/hide pre-order dropdown when checkbox changes

**Logic**:
```javascript
function handlePreOrderCheckboxChange() {
    const checkbox = document.getElementById('editPreOrderCheckbox');
    const optionsDiv = document.getElementById('editPreOrderOptions');
    const dropdown = document.getElementById('editPreOrderStatus');
    
    if (checkbox.checked) {
        optionsDiv.style.display = 'block';
        // Set default value if not already set
        if (!dropdown.value) {
            dropdown.value = '1'; // Default to Pick Up
        }
    } else {
        optionsDiv.style.display = 'none';
    }
    
    // Update quantity field state
    updateQuantityFieldState();
}
```

#### Function: `handleSameDayCheckboxChange()`
**Purpose**: Show/hide same-day dropdown and calendar when checkbox changes

**Logic**:
```javascript
function handleSameDayCheckboxChange() {
    const checkbox = document.getElementById('editSameDayCheckbox');
    const optionsDiv = document.getElementById('editSameDayOptions');
    const dropdown = document.getElementById('editSameDayStatus');
    const calendarContainer = document.getElementById('sameDayCalendarContainer');
    
    if (checkbox.checked) {
        optionsDiv.style.display = 'block';
        calendarContainer.style.display = 'block';
        // Set default value if not already set
        if (!dropdown.value) {
            dropdown.value = '1'; // Default to Pick Up
        }
        // Initialize calendar if needed
        if (window.modalCalendarHandler) {
            window.modalCalendarHandler.initializeSameDayCalendar();
        }
    } else {
        optionsDiv.style.display = 'none';
        calendarContainer.style.display = 'none';
    }
    
    // Update quantity field state
    updateQuantityFieldState();
}
```

#### Function: `updateQuantityFieldState()`
**Purpose**: Enable/disable quantity field based on checkbox states

**Logic**:
```javascript
function updateQuantityFieldState() {
    const preOrderChecked = document.getElementById('editPreOrderCheckbox').checked;
    const sameDayChecked = document.getElementById('editSameDayCheckbox').checked;
    const quantityField = document.getElementById('editProductQuantity');
    const unavailableRadio = document.getElementById('editUnavailable');
    
    // Disable quantity if:
    // 1. Product is unavailable, OR
    // 2. Only same-day is checked (not pre-order)
    if (unavailableRadio && unavailableRadio.checked) {
        quantityField.value = '0';
        quantityField.disabled = true;
        quantityField.style.opacity = '0.5';
        quantityField.style.cursor = 'not-allowed';
    } else if (sameDayChecked && !preOrderChecked) {
        quantityField.value = '0';
        quantityField.disabled = true;
        quantityField.style.opacity = '0.5';
        quantityField.style.cursor = 'not-allowed';
    } else {
        quantityField.disabled = false;
        quantityField.style.opacity = '1';
        quantityField.style.cursor = 'text';
    }
}
```

#### Function: `validateCheckboxSelection()`
**Purpose**: Ensure at least one checkbox is selected before form submission

**Logic**:
```javascript
function validateCheckboxSelection() {
    const preOrderChecked = document.getElementById('editPreOrderCheckbox').checked;
    const sameDayChecked = document.getElementById('editSameDayCheckbox').checked;
    
    if (!preOrderChecked && !sameDayChecked) {
        alert('Please select at least one order type (Pre-order or Same-day order)');
        return false;
    }
    return true;
}
```

#### Function: `handleFormSubmit()` (updated)
**Purpose**: Process form data and determine status_id and availtoday_status_id values

**Logic**:
```javascript
function handleFormSubmit(event) {
    event.preventDefault();
    
    // Validate checkbox selection
    if (!validateCheckboxSelection()) {
        return;
    }
    
    const preOrderChecked = document.getElementById('editPreOrderCheckbox').checked;
    const sameDayChecked = document.getElementById('editSameDayCheckbox').checked;
    
    let statusId = null;
    let availtodayStatusId = null;
    
    if (preOrderChecked && sameDayChecked) {
        // Both checked: status_id = pre-order value, availtoday_status_id = same-day value
        statusId = document.getElementById('editPreOrderStatus').value;
        availtodayStatusId = document.getElementById('editSameDayStatus').value;
    } else if (preOrderChecked) {
        // Only pre-order: status_id = pre-order value, availtoday_status_id = NULL
        statusId = document.getElementById('editPreOrderStatus').value;
        availtodayStatusId = null;
    } else if (sameDayChecked) {
        // Only same-day: status_id = 4, availtoday_status_id = same-day value
        statusId = 4;
        availtodayStatusId = document.getElementById('editSameDayStatus').value;
    }
    
    // Build form data
    const formData = new FormData();
    formData.append('id', document.getElementById('editProductId').value);
    formData.append('status_id', statusId);
    formData.append('availtoday_status_id', availtodayStatusId || '');
    // ... append other form fields ...
    
    // Submit via AJAX
    submitProductUpdate(formData);
}
```

### 3. Calendar Integration

The existing calendar component will be controlled by the same-day checkbox:

- **When same-day checkbox is checked**: Show calendar container
- **When same-day checkbox is unchecked**: Hide calendar container
- **Calendar initialization**: Use existing `modalCalendarHandler` functions
- **Date selection**: Maintain existing date selection logic

The calendar will populate either:
- `todays_product_dates` (if status_id = 4)
- `regular_today_dates` (if status_id = 1, 2, or 3 with availtoday_status_id set)

## Data Models

### Products Table (existing)

```sql
products (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    status_id INT,  -- 1=Pick Up, 2=Delivery, 3=Delivery or Pick Up, 4=Same Day Order
    availtoday_status_id INT NULL,  -- 1=Pick Up, 2=Delivery, 3=Delivery and Pick Up
    quantity INT,
    ...
)
```

### Status Mapping Logic

| Pre-order Checked | Same-day Checked | status_id | availtoday_status_id |
|-------------------|------------------|-----------|---------------------|
| Yes (Pick Up) | No | 1 | NULL |
| Yes (Delivery) | No | 2 | NULL |
| Yes (Delivery or Pick Up) | No | 3 | NULL |
| No | Yes (Pick Up) | 4 | 1 |
| No | Yes (Delivery) | 4 | 2 |
| No | Yes (Delivery and Pick Up) | 4 | 3 |
| Yes (Pick Up) | Yes (Pick Up) | 1 | 1 |
| Yes (Delivery) | Yes (Delivery) | 2 | 2 |
| Yes (Delivery or Pick Up) | Yes (Delivery and Pick Up) | 3 | 3 |

## Error Handling

### Validation Errors

1. **No checkbox selected**
   - **Error**: "Please select at least one order type (Pre-order or Same-day order)"
   - **Action**: Prevent form submission, show alert

2. **Same-day selected without dates**
   - **Error**: "Please select at least one date for same-day order"
   - **Action**: Prevent form submission, highlight calendar

3. **Network error during submission**
   - **Error**: Display server error message
   - **Action**: Keep modal open, allow retry

### Edge Cases

1. **Product with both status_id=4 and availtoday_status_id set**
   - **Handling**: Treat as same-day only (check only same-day checkbox)

2. **Product with status_id 1/2/3 and availtoday_status_id set**
   - **Handling**: Check both checkboxes, populate both dropdowns

3. **Unavailable product**
   - **Handling**: Disable quantity field regardless of checkbox states

## Testing Strategy

### Unit Tests (Manual Testing)

1. **Checkbox State Initialization**
   - Test: Open modal with pre-order only product (status_id=1)
   - Expected: Pre-order checkbox checked, dropdown visible with value=1

2. **Checkbox State Initialization**
   - Test: Open modal with same-day only product (status_id=4, availtoday_status_id=2)
   - Expected: Same-day checkbox checked, dropdown visible with value=2

3. **Checkbox State Initialization**
   - Test: Open modal with both types (status_id=2, availtoday_status_id=3)
   - Expected: Both checkboxes checked, both dropdowns visible

4. **Checkbox Toggle**
   - Test: Check pre-order checkbox
   - Expected: Pre-order dropdown appears with default value

5. **Checkbox Toggle**
   - Test: Uncheck pre-order checkbox
   - Expected: Pre-order dropdown hides

6. **Checkbox Toggle**
   - Test: Check same-day checkbox
   - Expected: Same-day dropdown and calendar appear

7. **Checkbox Toggle**
   - Test: Uncheck same-day checkbox
   - Expected: Same-day dropdown and calendar hide

8. **Validation**
   - Test: Uncheck both checkboxes and submit
   - Expected: Alert shown, form not submitted

9. **Form Submission**
   - Test: Check only pre-order (Pick Up), submit
   - Expected: status_id=1, availtoday_status_id=NULL

10. **Form Submission**
    - Test: Check only same-day (Delivery), submit
    - Expected: status_id=4, availtoday_status_id=2

11. **Form Submission**
    - Test: Check both (pre-order=Delivery or Pick Up, same-day=Delivery and Pick Up), submit
    - Expected: status_id=3, availtoday_status_id=3

### Integration Tests

1. **Product List Display**
   - Test: Save product with pre-order only
   - Expected: Product list shows pre-order badge only

2. **Product List Display**
   - Test: Save product with same-day only
   - Expected: Product list shows "Same Day Order" badge

3. **Product List Display**
   - Test: Save product with both types
   - Expected: Product list shows both badges

4. **Filter Functionality**
   - Test: Filter by "Pick Up"
   - Expected: Shows products with status_id=1

5. **Filter Functionality**
   - Test: Filter by "Same Day Order"
   - Expected: Shows products with status_id=4 OR availtoday_status_id not null

6. **Calendar Integration**
   - Test: Check same-day checkbox, select dates, submit
   - Expected: Dates saved to appropriate table (todays_products_dates or regular_products_today_dates)

### Browser Compatibility

- Test on Chrome, Firefox, Safari, Edge
- Test on mobile devices (iOS Safari, Chrome Mobile)
- Verify checkbox styling and dropdown behavior

## Implementation Notes

### Backward Compatibility

- Existing products will load correctly based on their current status_id and availtoday_status_id values
- No database migration required
- Existing API endpoints remain unchanged

### Performance Considerations

- Checkbox event handlers are lightweight (DOM manipulation only)
- No additional API calls required
- Calendar initialization only when needed (same-day checkbox checked)

### Accessibility

- Checkboxes have associated labels with `for` attributes
- Dropdowns have descriptive labels
- Keyboard navigation supported (tab through checkboxes and dropdowns)
- Screen reader friendly (semantic HTML)

### CSS Styling

- Reuse existing checkbox styles from `edit-modal.css`
- Add indentation for conditional dropdowns (margin-left: 24px)
- Maintain consistent spacing and alignment
- Ensure mobile responsiveness

## Migration Path

### Phase 1: Update Modal HTML
- Replace dropdown with checkbox interface
- Add conditional dropdown containers
- Test modal opening with various product states

### Phase 2: Update JavaScript Logic
- Implement checkbox event handlers
- Update `openEditModal()` function
- Add validation logic
- Update form submission logic

### Phase 3: Testing
- Manual testing of all scenarios
- Browser compatibility testing
- Mobile device testing

### Phase 4: Deployment
- Deploy to production
- Monitor for issues
- Gather user feedback

## Future Enhancements

1. **Bulk Edit**: Allow changing order types for multiple products at once
2. **Quick Toggle**: Add quick toggle buttons in product list to change order types without opening modal
3. **Status History**: Track changes to product order types over time
4. **Smart Defaults**: Suggest order types based on product category or past selections
