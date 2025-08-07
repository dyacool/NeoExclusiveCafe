# Automatic Product Status Update to Unavailable

## Overview
This implementation automatically sets product status to "Unavailable" when the product quantity reaches 0. This ensures that products with no stock are properly marked as unavailable to customers.

## Implementation Details

### 1. Application-Level Logic

#### A. Order Confirmation (`frontend/pages/cart/order-confirmation.php`)
- **Location**: Lines 398-410
- **Function**: When orders are placed and product stock is reduced
- **Logic**: After updating product quantity, checks if the new quantity is 0 and automatically sets status to unavailable (status_id = 3)

```php
// Check if product quantity reached 0 and update status to Unavailable
$check_quantity_sql = "SELECT quantity FROM products WHERE id = ?";
$check_quantity_stmt = $conn->prepare($check_quantity_sql);
$check_quantity_stmt->bind_param("i", $item['product_id']);
$check_quantity_stmt->execute();
$quantity_result = $check_quantity_stmt->get_result();
$current_quantity = $quantity_result->fetch_assoc()['quantity'];
$check_quantity_stmt->close();

// If quantity is 0, set status to Unavailable (status_id = 3)
if ($current_quantity <= 0) {
    $update_status_sql = "UPDATE products SET status_id = 3 WHERE id = ?";
    $update_status_stmt = $conn->prepare($update_status_sql);
    $update_status_stmt->bind_param("i", $item['product_id']);
    $update_status_stmt->execute();
    $update_status_stmt->close();
}
```

#### B. Product Update (`backend/pages/products/update-product.php`)
- **Location**: Lines 48-54
- **Function**: When admins manually update product details
- **Logic**: Checks if quantity is set to 0 and automatically sets status to unavailable

```php
// Check if quantity is 0 and automatically set status to Unavailable
if ($quantity <= 0 && $status_id != 3) {
    $auto_status_sql = "UPDATE products SET status_id = 3 WHERE id = ?";
    $auto_status_stmt = $conn->prepare($auto_status_sql);
    $auto_status_stmt->bind_param("i", $id);
    $auto_status_stmt->execute();
    $auto_status_stmt->close();
}
```

### 2. Database-Level Triggers (Optional Safety Net)

#### A. Update Trigger (`sql_configs/auto_unavailable_trigger.sql`)
- **Trigger**: `auto_set_unavailable_on_zero_quantity`
- **Event**: AFTER UPDATE on products table
- **Function**: Automatically sets status to unavailable when quantity reaches 0

```sql
CREATE TRIGGER auto_set_unavailable_on_zero_quantity
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF NEW.quantity <= 0 AND NEW.status_id != 3 THEN
        UPDATE products SET status_id = 3 WHERE id = NEW.id;
    END IF;
END
```

#### B. Insert Trigger
- **Trigger**: `auto_set_unavailable_on_insert`
- **Event**: BEFORE INSERT on products table
- **Function**: Ensures new products with 0 quantity are set to unavailable

```sql
CREATE TRIGGER auto_set_unavailable_on_insert
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
    IF NEW.quantity <= 0 THEN
        SET NEW.status_id = 3;
    END IF;
END
```

## Product Status IDs
- **1**: Pick Up
- **2**: Delivery  
- **3**: Unavailable

## Testing and Maintenance

### Test Scripts

#### 1. Test Implementation (`test_auto_unavailable.php`)
- Tests current products with quantity 0
- Simulates product updates with quantity 0
- Verifies automatic status updates work correctly

#### 2. Fix Existing Data (`fix_zero_quantity_products.php`)
- Finds and fixes existing products with quantity 0 but wrong status
- Provides summary of all products with quantity 0

### Usage Instructions

1. **Run the fix script first** (if needed):
   ```bash
   php fix_zero_quantity_products.php
   ```

2. **Test the implementation**:
   ```bash
   php test_auto_unavailable.php
   ```

3. **Apply database triggers** (optional):
   ```sql
   source sql_configs/auto_unavailable_trigger.sql
   ```

## Benefits

1. **Automatic Management**: No manual intervention required
2. **Consistent Status**: All products with 0 quantity are properly marked
3. **Customer Experience**: Customers see accurate availability status
4. **Inventory Accuracy**: Prevents orders for out-of-stock items
5. **Multiple Safety Nets**: Both application and database-level protection

## Edge Cases Handled

1. **Order Processing**: When orders reduce stock to 0
2. **Manual Updates**: When admins set quantity to 0
3. **New Products**: When creating products with 0 quantity
4. **Existing Data**: Scripts to fix inconsistent existing data

## Monitoring

The system automatically handles status updates, but you can monitor:
- Products with quantity 0 in the admin panel
- Order processing logs for any issues
- Database trigger execution (if using triggers)

## Future Enhancements

1. **Email Notifications**: Alert admins when products become unavailable
2. **Restock Alerts**: Notify when products are restocked
3. **Audit Logging**: Track when status changes occur
4. **Bulk Operations**: Handle multiple product updates efficiently
