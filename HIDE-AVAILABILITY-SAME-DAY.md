# Hide Availability for Same Day Order

## ✅ Feature Implemented

When "Same Day Order" (status_id 4) is selected in the product edit modal, the Availability section is now hidden.

## Changes Made

### 1. product-list.php

**Added ID to Availability Container:**
```html
<div class="form-group" id="availabilityContainer">
    <label>Availability:</label>
    ...
</div>
```

This allows JavaScript to target and hide/show this section.

### 2. modal-calendar-handler.js

**Updated `handleEditStatusChange()` function:**

Added logic to hide/show the availability container based on selected status:

- **Status 1, 2, 3** (Pick Up, Delivery, Delivery or Pick Up):
  - ✅ Show Availability section
  - ✅ Show regular days
  - ✅ Show "Set to same day order too" option

- **Status 4** (Same Day Order):
  - ❌ Hide Availability section
  - ✅ Show calendar for date selection
  - ✅ Show Same Day Order shipping method options
  - ❌ Hide regular days

- **Other statuses**:
  - ✅ Show Availability section
  - ❌ Hide all other options

## Logic Flow

```
User selects status in edit modal
    ↓
handleEditStatusChange() triggered
    ↓
┌─────────────────────────────────────┐
│ Status ID = 4 (Same Day Order)?     │
└─────────────────────────────────────┘
    ↓                    ↓
   YES                  NO
    ↓                    ↓
Hide Availability    Show Availability
Show Calendar        Show Regular Days
Show SDO Options     Show SDO Toggle
```

## Why This Makes Sense

**Same Day Order products:**
- Are managed through date-specific quantities
- Don't use the traditional Available/Unavailable system
- Have their own calendar-based availability
- Availability is determined by whether dates are selected

**Regular products (Status 1, 2, 3):**
- Use the Available/Unavailable toggle
- Can optionally be set for same-day order too
- Have regular availability days

## User Experience

### Before:
- Availability section shown for all statuses
- Confusing for Same Day Order products
- Unclear which availability system applies

### After:
- Availability section only shown when relevant
- Cleaner interface for Same Day Order
- Clear separation of availability systems

## Testing Checklist

- [ ] Select Status 1 (Pick Up) → Availability section visible
- [ ] Select Status 2 (Delivery) → Availability section visible
- [ ] Select Status 3 (Delivery or Pick Up) → Availability section visible
- [ ] Select Status 4 (Same Day Order) → Availability section hidden
- [ ] Calendar appears when Status 4 selected
- [ ] SDO shipping method options appear when Status 4 selected
- [ ] Switching between statuses shows/hides correctly

## Technical Details

**Container ID:** `availabilityContainer`

**Controlled by:** `handleEditStatusChange()` in `modal-calendar-handler.js`

**Triggered by:** Change event on `editProductStatus` select element

**Display Logic:**
```javascript
if (selectedValue === '4') {
    availabilityContainer.style.display = 'none'; // Hide for Same Day Order
} else {
    availabilityContainer.style.display = 'block'; // Show for others
}
```

## Benefits

✅ **Cleaner UI** - Only shows relevant options
✅ **Less confusion** - Clear which availability system applies
✅ **Better UX** - Reduces cognitive load for admins
✅ **Logical grouping** - Same Day Order has its own section

## Notes

- The Availability section includes:
  - Available/Unavailable radio buttons
  - Unavailable type container
- This section is now context-aware
- Same Day Order products use calendar-based availability instead
- Regular products can still use both systems (regular + same-day)
