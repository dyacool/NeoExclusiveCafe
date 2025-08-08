# Product Statuses Restructuring

## Overview

The product statuses have been restructured to separate unavailable statuses into their own table. This provides better organization and allows for more flexible management of unavailable product types.

## Changes Made

### 1. **Removed from `product_statuses` table**
- **ID 4**: "Unavailable Pick Up" - REMOVED
- **ID 5**: "Unavailable Delivery" - REMOVED

### 2. **New `unavail_products_status` table**
```sql
CREATE TABLE unavail_products_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. **Data in new table**
- **ID 1**: "Unavailable Pick Up"
- **ID 2**: "Unavailable Delivery" 
- **ID 3**: "Unavailable Today"

## Current `product_statuses` table structure
```sql
id    name
1     Pick Up
2     Delivery
3     Available Today
```

## SQL Scripts

### **Main Restructuring Script**
```sql
-- File: sql_configs/restructure_product_statuses.sql
-- Run this to perform the restructuring
```

### **Rollback Script**
```sql
-- File: sql_configs/rollback_product_statuses.sql
-- Run this to undo the changes if needed
```

## Code Updates

### **Files Modified**
1. **`backend/pages/products/product-list.php`**
   - Removed unavailable status options from edit modal dropdown
   - Updated to only show: Pick Up, Delivery, Available Today

2. **`backend/pages/products/product-list.js`**
   - Commented out unavailable filtering logic
   - Updated filter counts to handle new structure
   - Unavailable filter will now show empty results

### **Impact on Functionality**
- **Unavailable Filter**: Currently disabled (shows empty results)
- **Edit Modal**: No longer allows setting unavailable statuses
- **Product Management**: Unavailable products are now handled differently

## Next Steps

### **Option 1: Implement New Unavailable Management**
If you want to continue using unavailable statuses, you'll need to:

1. **Create a new approach** for handling unavailable products
2. **Update queries** to join with the new `unavail_products_status` table
3. **Modify the UI** to work with the new structure
4. **Update JavaScript** filtering logic

### **Option 2: Remove Unavailable Functionality**
If you want to remove unavailable functionality entirely:

1. **Remove the "Unavailable" filter button** from the UI
2. **Clean up unused code** in JavaScript
3. **Update any references** to unavailable statuses

### **Option 3: Use Different Approach**
Consider alternative approaches for unavailable products:

1. **Use a separate "is_available" flag** in the products table
2. **Use the existing "Available Today" status** more effectively
3. **Implement a "hidden" or "archived" status** instead

## Database Queries

### **To check current product statuses:**
```sql
SELECT * FROM product_statuses ORDER BY id;
```

### **To check new unavailable statuses:**
```sql
SELECT * FROM unavail_products_status ORDER BY id;
```

### **To find products that were using removed statuses:**
```sql
SELECT id, name, status_id FROM products WHERE status_id IN (4, 5);
```

## Recommendations

1. **Review existing products** that were using unavailable statuses
2. **Decide on approach** for handling unavailable products going forward
3. **Update any hardcoded references** to status IDs 4 and 5
4. **Test thoroughly** after implementing your chosen approach
5. **Consider data migration** if you need to preserve unavailable product information

## Rollback Instructions

If you need to undo these changes:

1. **Run the rollback script**: `sql_configs/rollback_product_statuses.sql`
2. **Restore the original code** from version control
3. **Update any products** that were changed during restructuring

## Benefits of New Structure

1. **Better Organization**: Unavailable statuses are separate from main statuses
2. **Flexibility**: Can add more unavailable types without affecting main statuses
3. **Audit Trail**: Timestamps track when unavailable statuses were created/updated
4. **Scalability**: Easier to extend with additional unavailable categories
5. **Cleaner Code**: Main product statuses are simplified

## Questions to Consider

1. **How do you want to handle unavailable products now?**
2. **Should unavailable products be completely hidden or just marked differently?**
3. **Do you need the "Unavailable Today" status or can it be handled differently?**
4. **Should we implement a new approach for managing product availability?**
