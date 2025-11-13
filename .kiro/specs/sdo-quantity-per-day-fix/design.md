# Design Document

## Overview

This design addresses the bug where Same Day Order (SDO) quantity per day values are not being saved when editing products. The fix involves modifying the product save workflow to collect, validate, and persist SDO quantity data alongside other product information.

## Architecture

The solution follows the existing three-tier architecture:

1. **Presentation Layer** (JavaScript): Product edit modal and form handling
2. **Application Layer** (PHP): Product update endpoint and business logic
3. **Data Layer** (MySQL): Database tables for products, quantities, and dates

### Current Flow (Broken)

```
User edits product → Save button clicked → saveProductChanges() collects data
→ Data sent to update-product.php → Product saved
→ SDO quantities NOT saved ❌
```

### Fixed Flow

```
User edits product → Save button clicked → saveProductChanges() collects data
→ Collect SDO quantities from sdo-quantity-manager.js
→ Data sent to update-product.php → Product saved
→ update-product.php calls SDO quantity save logic
→ SDO quantities saved to quantity_per_day_sdo ✓
→ Dates saved to appropriate table ✓
```

## Components and Interfaces

### 1. Frontend: Save Handler Enhancement

**File:** `backend/pages/products/product-list.js` or inline script in `product-list.php`

**Current Issue:** The `saveProductChanges()` function doesn't exist or doesn't collect SDO quantities.

**Solution:** Create or modify the save handler to:
- Collect SDO quantities using `getSDOQuantities()` from sdo-quantity-manager.js
- Include quantities in the payload
- Validate data before submission

**Interface:**
```javascript
function saveProductChanges() {
    // Collect basic product data
    const productData = {
        id: document.getElementById('editProductId').value,
        name: document.getElementById('editProductName').value,
        // ... other fields
    };
    
    // Collect SDO quantities if applicable
    const statusId = parseInt(productData.status_id);
    const availtodayStatusId = parseInt(productData.availtoday_status_id);
    
    // Check if product has SDO enabled
    const hasSameDayOrder = (statusId === 4) || 
                           (availtodayStatusId && availtodayStatusId !== null);
    
    if (hasSameDayOrder) {
        const sdoQuantities = getSDOQuantities(); // From sdo-quantity-manager.js
        productData.sdo_quantities = JSON.stringify(sdoQuantities);
    }
    
    // Send to backend
    fetch('update-product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(productData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page or update UI
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}
```

### 2. Backend: Update Product Enhancement

**File:** `backend/pages/products/update-product.php`

**Current Issue:** The file saves product data but doesn't handle SDO quantities.

**Solution:** Add logic to process and save SDO quantities after product update.

**Interface:**
```php
// After successful product update...

// Handle SDO quantities
if (isset($input['sdo_quantities'])) {
    $sdo_quantities = json_decode($input['sdo_quantities'], true);
    
    if (!empty($sdo_quantities)) {
        // Delete existing quantities
        $delete_qty_stmt = $conn->prepare(
            "DELETE FROM quantity_per_day_sdo WHERE product_id = ?"
        );
        $delete_qty_stmt->bind_param("i", $id);
        $delete_qty_stmt->execute();
        $delete_qty_stmt->close();
        
        // Insert new quantities
        $insert_qty_stmt = $conn->prepare(
            "INSERT INTO quantity_per_day_sdo (product_id, date, quantity) 
             VALUES (?, ?, ?)"
        );
        
        foreach ($sdo_quantities as $date => $quantity) {
            $qty = intval($quantity);
            $insert_qty_stmt->bind_param("isi", $id, $date, $qty);
            $insert_qty_stmt->execute();
        }
        $insert_qty_stmt->close();
    }
}
```

### 3. Integration with Existing SDO Manager

**File:** `backend/pages/products/sdo-quantity-manager.js`

**Current State:** Already has `getSDOQuantities()` function that returns the quantities object.

**No Changes Needed:** The existing function works correctly. We just need to call it from the save handler.

## Data Models

### quantity_per_day_sdo Table

```sql
CREATE TABLE quantity_per_day_sdo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    date DATE NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_date (product_id, date),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

### Data Flow

1. **Save Operation:**
   - Frontend collects: `{ "2025-11-15": 10, "2025-11-16": 15 }`
   - Backend receives: `sdo_quantities: "{\"2025-11-15\":10,\"2025-11-16\":15}"`
   - Backend parses and saves to `quantity_per_day_sdo` table

2. **Load Operation:**
   - Backend queries `quantity_per_day_sdo` for product_id
   - Returns: `{ "2025-11-15": 10, "2025-11-16": 15 }`
   - Frontend calls `initializeSDOQuantities()` to populate UI

## Error Handling

### Frontend Validation

```javascript
function validateSDOQuantities(quantities) {
    for (const [date, quantity] of Object.entries(quantities)) {
        // Validate date format
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
            throw new Error(`Invalid date format: ${date}`);
        }
        
        // Validate quantity
        const qty = parseInt(quantity);
        if (isNaN(qty) || qty < 0) {
            throw new Error(`Invalid quantity for ${date}: ${quantity}`);
        }
    }
    return true;
}
```

### Backend Error Handling

```php
try {
    // Save quantities
    // ...
} catch (Exception $e) {
    error_log("Error saving SDO quantities: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save quantity per day data'
    ]);
    exit();
}
```

### Error Scenarios

| Scenario | Detection | Response |
|----------|-----------|----------|
| Invalid date format | Frontend validation | Show error message, prevent save |
| Negative quantity | Frontend validation | Show error message, prevent save |
| Database error | Backend try-catch | Rollback transaction, return error |
| Missing product_id | Backend validation | Return error response |
| Network failure | Frontend fetch error | Show error message, allow retry |

## Testing Strategy

### Unit Tests

1. **Frontend:**
   - Test `getSDOQuantities()` returns correct format
   - Test validation catches invalid dates
   - Test validation catches negative quantities

2. **Backend:**
   - Test quantities are saved correctly
   - Test existing quantities are deleted before insert
   - Test transaction rollback on error

### Integration Tests

1. **Save Flow:**
   - Create product with SDO status
   - Set quantities for multiple dates
   - Save and verify database entries
   - Reload page and verify quantities display correctly

2. **Update Flow:**
   - Edit existing SDO product
   - Change quantities
   - Save and verify updates
   - Verify old quantities are removed

3. **Status Transition:**
   - Change product from Pre-Order to SDO
   - Verify quantities can be set
   - Change back to Pre-Order only
   - Verify quantities are cleared

### Manual Testing Checklist

- [ ] Edit SDO product (status_id = 4)
- [ ] Set quantities for 3 different dates
- [ ] Save product
- [ ] Verify quantities saved in database
- [ ] Reload page and verify quantities display
- [ ] Edit Pre-Order product with SDO enabled
- [ ] Set quantities for dates
- [ ] Save and verify
- [ ] Remove SDO from Pre-Order product
- [ ] Verify quantities are cleared
- [ ] Test with invalid date format
- [ ] Test with negative quantity
- [ ] Test with network error

## Implementation Notes

### Key Considerations

1. **Backward Compatibility:** The fix should not break existing products without SDO quantities.

2. **Transaction Safety:** Use database transactions to ensure data consistency.

3. **Performance:** Batch insert operations where possible to minimize database calls.

4. **User Experience:** Show loading indicator during save operation.

### Potential Issues

1. **Race Conditions:** If user clicks save multiple times, ensure only one save operation proceeds.

2. **Stale Data:** After save, ensure UI reflects the latest database state.

3. **Calendar Sync:** Ensure selected dates in calendar match the dates with quantities.

### Dependencies

- Existing `sdo-quantity-manager.js` must be loaded before save handler
- `update-product.php` must have database connection available
- `quantity_per_day_sdo` table must exist in database

## Rollback Plan

If issues arise after deployment:

1. **Immediate:** Revert changes to `update-product.php` and save handler
2. **Data:** Existing data in `quantity_per_day_sdo` table remains intact
3. **Functionality:** Products will continue to work, just without quantity per day saving
4. **Recovery:** Re-deploy after fixing issues in development environment
