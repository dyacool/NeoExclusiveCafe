# Design Document

## Overview

The order list page is experiencing a database error where the `status` column cannot be found in WHERE clauses. Based on the SQL backup files, the `orders` table does have a `status` column defined. This suggests either:
1. The actual database schema differs from the backup
2. There's a table name conflict or query syntax issue
3. The column exists but has different casing or whitespace

The solution involves diagnosing the actual database state and fixing any schema mismatches or query issues.

## Architecture

### Diagnostic Approach

1. **Database Schema Verification**: Create a diagnostic script to inspect the actual orders table structure
2. **Query Analysis**: Review all queries in order-list.php and get-orders.php for syntax issues
3. **Error Handling**: Improve error messages to provide actionable debugging information
4. **Schema Correction**: Apply necessary database migrations if schema is incorrect

### Components Involved

- `backend/pages/orders/order-list.php` - Main order listing page with filtering
- `backend/pages/orders/get-orders.php` - AJAX endpoint for polling order updates
- `orders` table in MySQL database
- Diagnostic script for schema verification

## Components and Interfaces

### 1. Database Diagnostic Script

**Purpose**: Verify the actual structure of the orders table

**Location**: `backend/pages/orders/diagnose-orders-table.php`

**Functionality**:
- Connect to database using existing database.php
- Execute `DESCRIBE orders` to get column information
- Execute `SHOW CREATE TABLE orders` to get full table definition
- Display results in readable format
- Check for status column specifically

**Output**:
```php
[
    'table_exists' => boolean,
    'columns' => [
        ['Field' => 'order_id', 'Type' => 'int(11)', ...],
        ['Field' => 'status', 'Type' => 'varchar(50)', ...],
        // ... other columns
    ],
    'has_status_column' => boolean,
    'create_statement' => string
]
```

### 2. Query Fix in order-list.php

**Current Issue**: Query uses backticks around column names but not table name

**Current Code**:
```php
$sql = "SELECT * FROM `orders`";
$where_clauses[] = "LOWER(TRIM(`status`)) = LOWER(?)";
```

**Potential Issues**:
- Table name might need explicit database prefix
- Column might have different casing
- Table might not exist or be named differently

**Solution Approach**:
- Verify table exists before querying
- Use consistent quoting (backticks for both table and columns, or neither)
- Add error handling with descriptive messages

### 3. Query Fix in get-orders.php

**Current Issue**: Similar query structure without backticks

**Current Code**:
```php
$sql = "SELECT * FROM orders";
$where_clauses[] = "status = ?";
```

**Solution**: Ensure consistency with order-list.php fixes

## Data Models

### Orders Table (Expected Schema)

```sql
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `customer_name` varchar(255) NOT NULL,
  `customer_contact` varchar(11) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `payment_method` varchar(100) NOT NULL,
  `total_items` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `delivery_method` enum('Delivery','Pick-up') NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `delivery_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Error Handling

### Current Error Handling

The current implementation has some error handling but doesn't gracefully handle missing columns:

```php
if ($stmt === false) {
    die("Error preparing statement: " . mysqli_error($conn));
}
```

### Improved Error Handling

1. **Pre-Query Validation**: Check if table and columns exist before executing queries
2. **Descriptive Error Messages**: Include SQL query and specific error details
3. **Graceful Degradation**: Show user-friendly message instead of fatal error
4. **Logging**: Log errors to file for debugging

**Implementation**:
```php
// Verify table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($table_check) === 0) {
    error_log("CRITICAL: orders table does not exist");
    die("Database configuration error. Please contact administrator.");
}

// Verify status column exists
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM `orders` LIKE 'status'");
if (mysqli_num_rows($column_check) === 0) {
    error_log("CRITICAL: status column missing from orders table");
    die("Database schema error. Please contact administrator.");
}
```

## Testing Strategy

### 1. Diagnostic Phase

- Run diagnostic script to verify actual database schema
- Compare with expected schema from SQL backup files
- Document any discrepancies

### 2. Fix Verification

- Test order list page loads without errors
- Test status filtering with each status value
- Test search functionality combined with status filters
- Test AJAX polling updates
- Verify status counts display correctly

### 3. Edge Cases

- Empty orders table
- Orders with NULL status values
- Orders with unexpected status values
- Concurrent updates during polling

### 4. Regression Testing

- Verify all existing order management features still work
- Test view-orders.php page
- Test status update functionality
- Test pagination with filters

## Implementation Steps

1. Create diagnostic script to inspect database schema
2. Run diagnostic and identify the actual issue
3. Apply appropriate fix based on findings:
   - If column missing: Add migration script to create it
   - If query syntax issue: Fix query construction
   - If table name issue: Update queries with correct table reference
4. Add pre-query validation to prevent future errors
5. Improve error messages for better debugging
6. Test all order management functionality
