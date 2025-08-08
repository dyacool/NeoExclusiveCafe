# Unavailable Status Implementation with Radio Buttons

## Overview

The product management system has been updated to include a new unavailable status feature using radio buttons. This allows admins to easily mark products as unavailable while maintaining their original status (Pick Up, Delivery, Available Today).

## Database Changes

### 1. **New Column Added**
- **Table**: `products`
- **Column**: `unavailable_status_id`
- **Type**: `INT NULL`
- **Purpose**: Links to `unavail_products_status` table
- **Foreign Key**: References `unavail_products_status(id)`

### 2. **SQL Scripts**
```sql
-- Add the new column
-- File: sql_configs/add_unavailable_status_column.sql

-- Create the unavailable statuses table
-- File: sql_configs/create_availToday_table.sql

-- Restructure product statuses
-- File: sql_configs/restructure_product_statuses.sql
```

## New Table Structure

### **`unavail_products_status` table**
```sql
id    name
1     Unavailable Pick Up
2     Unavailable Delivery
3     Unavailable Today
```

### **Updated `product_statuses` table**
```sql
id    name
1     Pick Up
2     Delivery
3     Available Today
```

## UI Changes

### **Edit Modal Updates**
- **Added Radio Buttons**: "Available" and "Unavailable" options
- **Dynamic Dropdown**: Shows unavailable type when "Unavailable" is selected
- **Unavailable Types**: Unavailable Pick Up, Unavailable Delivery, Unavailable Today

### **Radio Button Behavior**
- **Available**: Sets `unavailable_status_id` to `NULL`
- **Unavailable**: Shows dropdown to select unavailable type
- **Auto-hide**: Unavailable type dropdown hides when "Available" is selected

## Code Updates

### **Files Modified**

1. **`backend/pages/products/product-list.php`**
   - Updated SQL queries to include `unavailable_status_id` and `unavailable_status_name`
   - Added radio button HTML structure
   - Updated `openEditModal` function call to pass unavailable status data

2. **`backend/pages/products/product-list.js`**
   - Updated `openEditModal` function to handle unavailable status
   - Added radio button event listeners
   - Updated `handleFormSubmit` to include unavailable status data
   - Fixed filtering logic to work with new structure
   - Updated `filterUnavailableByType` function

3. **`backend/pages/products/update-product.php`**
   - Added handling for `unavailable_status_id`
   - Updated SQL UPDATE query to include new column
   - Modified auto-status logic for quantity = 0

4. **`backend/pages/products/product-list.css`**
   - Added styling for radio buttons
   - Added styling for unavailable type dropdown
   - Responsive design for new elements

## Functionality

### **Admin Workflow**
1. **Edit Product**: Click edit button on any product
2. **Set Availability**: Choose "Available" or "Unavailable" radio button
3. **Select Type**: If "Unavailable", choose from dropdown:
   - Unavailable Pick Up
   - Unavailable Delivery
   - Unavailable Today
4. **Save Changes**: Product status is updated accordingly

### **Automatic Behavior**
- **Quantity = 0**: Automatically sets unavailable status based on current status
- **Pick Up + Quantity 0**: Sets to "Unavailable Pick Up"
- **Delivery + Quantity 0**: Sets to "Unavailable Delivery"
- **Other + Quantity 0**: Sets to "Unavailable Today"

### **Filtering**
- **Unavailable Filter**: Shows all products with `unavailable_status_id` NOT NULL
- **Type Filtering**: Filter by specific unavailable types
- **Count Display**: Shows number of unavailable products

## Database Queries

### **To check unavailable products:**
```sql
SELECT p.*, ps.name as status_name, ups.name as unavailable_status_name
FROM products p
LEFT JOIN product_statuses ps ON p.status_id = ps.id
LEFT JOIN unavail_products_status ups ON p.unavailable_status_id = ups.id
WHERE p.unavailable_status_id IS NOT NULL;
```

### **To find products by unavailable type:**
```sql
SELECT p.*, ups.name as unavailable_type
FROM products p
JOIN unavail_products_status ups ON p.unavailable_status_id = ups.id
WHERE ups.id = 1; -- 1 = Unavailable Pick Up, 2 = Unavailable Delivery, 3 = Unavailable Today
```

## Benefits

1. **Flexible Management**: Products can be unavailable while keeping their original status
2. **Better Organization**: Clear separation between product type and availability
3. **Improved UX**: Intuitive radio button interface
4. **Automatic Logic**: Smart handling of quantity-based availability
5. **Enhanced Filtering**: Better filtering and search capabilities

## Usage Examples

### **Scenario 1: Temporary Unavailability**
- Product: "Sourdough Bread" (Delivery)
- Admin sets: "Unavailable" → "Unavailable Delivery"
- Result: Product remains Delivery type but is marked as unavailable

### **Scenario 2: Stock Depletion**
- Product: "Croissant" (Pick Up) with quantity = 0
- System automatically sets: "Unavailable Pick Up"
- Result: Product is automatically marked unavailable

### **Scenario 3: Status Change**
- Product: "Cake" (Available Today) → Admin changes to "Unavailable"
- Admin selects: "Unavailable Today"
- Result: Product type changes and availability is updated

## Migration Notes

### **Existing Products**
- Products with old unavailable statuses (IDs 4, 5) are automatically converted
- Status ID 4 → `unavailable_status_id = 1` (Unavailable Pick Up)
- Status ID 5 → `unavailable_status_id = 2` (Unavailable Delivery)

### **Data Integrity**
- Foreign key constraints ensure data consistency
- NULL values indicate available products
- NOT NULL values indicate unavailable products with specific type

## Future Enhancements

1. **Bulk Operations**: Select multiple products and set availability
2. **Scheduled Unavailability**: Set products to be unavailable on specific dates
3. **Availability History**: Track when products become available/unavailable
4. **Notifications**: Alert when products become unavailable
5. **Integration**: Connect with inventory management system

## Troubleshooting

### **Common Issues**
1. **Radio buttons not working**: Check JavaScript console for errors
2. **Filter not showing products**: Verify `unavailable_status_id` is set correctly
3. **Dropdown not appearing**: Ensure radio button event listeners are working
4. **Database errors**: Check foreign key constraints and table structure

### **Debug Queries**
```sql
-- Check for orphaned unavailable statuses
SELECT p.id, p.name, p.unavailable_status_id 
FROM products p 
WHERE p.unavailable_status_id IS NOT NULL 
AND p.unavailable_status_id NOT IN (SELECT id FROM unavail_products_status);

-- Check product status distribution
SELECT ps.name as status, COUNT(*) as count
FROM products p
JOIN product_statuses ps ON p.status_id = ps.id
GROUP BY ps.name;
```
