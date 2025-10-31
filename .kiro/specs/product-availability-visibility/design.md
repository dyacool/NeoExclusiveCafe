# Design Document

## Overview

This design implements comprehensive product availability visibility logic for the product dashboard. The system determines whether products should be displayed or hidden based on multiple factors: regular stock levels (`products.quantity`), same-day stock levels (`quantity_per_day_sdo.quantity`), date availability records (`regular_products_today_dates` and `todays_products_dates`), and admin-configured visibility flags (`show_when_unavailable` and `hide_when_unavailable`).

The current implementation in `product-dashboard.php` already checks stock levels but doesn't fully integrate date availability checks or properly respect the visibility flags. This design will enhance the existing logic to create a complete availability determination system.

## Architecture

### System Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Product Dashboard Query                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Database Query with LEFT JOINs                       │  │
│  │  - products (p)                                        │  │
│  │  - quantity_per_day_sdo (qpd) ON date = CURDATE()    │  │
│  │  - todays_products_dates (tpd)                        │  │
│  │  - regular_products_today_dates (rptd)                │  │
│  │  - product_statuses, product_images, categories       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              Availability Determination Logic                │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  For Each Product:                                    │  │
│  │                                                        │  │
│  │  1. Determine Product Type:                           │  │
│  │     - Status 4: Same-day ONLY                         │  │
│  │     - Status 1/2/3 + availtoday_status_id: DUAL      │  │
│  │     - Status 1/2/3 (no availtoday): Pre-order ONLY   │  │
│  │                                                        │  │
│  │  2. Check Stock Availability:                         │  │
│  │     - Same-day ONLY: qpd.quantity > 0                 │  │
│  │     - Pre-order ONLY: p.quantity > 0                  │  │
│  │     - DUAL: p.quantity > 0 OR qpd.quantity > 0       │  │
│  │                                                        │  │
│  │  3. Check Date Availability:                          │  │
│  │     - Same-day products: todays_products_dates        │  │
│  │     - Pre-order with same-day: regular_products_      │  │
│  │       today_dates                                      │  │
│  │     - Check if CURDATE() exists in date records       │  │
│  │                                                        │  │
│  │  4. Determine Unavailability:                         │  │
│  │     - Product is unavailable if:                      │  │
│  │       * Stock is 0 (based on product type) AND       │  │
│  │       * No valid date record for today (if applicable)│  │
│  │                                                        │  │
│  │  5. Apply Visibility Rules:                           │  │
│  │     - If unavailable AND hide_when_unavailable = 1:   │  │
│  │       HIDE product                                     │  │
│  │     - If unavailable AND show_when_unavailable = 1    │  │
│  │       AND hide_when_unavailable = 0:                  │  │
│  │       SHOW product with unavailable indicators        │  │
│  │     - If unavailable AND both flags = 0:              │  │
│  │       HIDE product (default behavior)                 │  │
│  │     - If available: SHOW product normally             │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Frontend Rendering                        │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Available Products:                                  │  │
│  │  - Normal display with "Add to Cart" button          │  │
│  │  - Show stock levels                                  │  │
│  │  - Show availability badges                           │  │
│  │                                                        │  │
│  │  Unavailable Products (if shown):                     │  │
│  │  - "Out of Stock" badge                               │  │
│  │  - Unavailable overlay on image                       │  │
│  │  - Disabled "Unavailable" button                      │  │
│  │  - Reduced opacity styling                            │  │
│  │  - Sorted to bottom of product list                   │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Database Query Enhancement

**Current State:**
The query already includes LEFT JOINs for `quantity_per_day_sdo`, `todays_products_dates`, and `regular_products_today_dates`.

**Enhancement Needed:**
Add `hide_when_unavailable` column to the SELECT statement (currently only `show_when_unavailable` is selected).

```php
SELECT 
    p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id,
    ps.name AS status_name, 
    COALESCE(pi.cloud_url, pi.image_url) as image_url,
    p.quantity, 
    p.show_when_unavailable,
    p.hide_when_unavailable,  // ADD THIS
    p.availtoday_status_id, 
    ats.name AS availtoday_status_name,
    c.name AS category_name,
    GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
    GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates,
    qpd.quantity as sameday_stock_today
FROM products p
LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
...
```

### 2. Availability Determination Function

**Location:** `frontend/pages/products/product-dashboard.php`

**Function:** `determineProductAvailability($product_row, $today_date)`

**Logic:**
```php
function determineProductAvailability($product_row, $today_date) {
    $result = [
        'is_unavailable' => false,
        'unavailable_reason' => '',
        'should_display' => true
    ];
    
    // Extract data
    $status_id = $product_row['status_id'];
    $preorder_stock = $product_row['quantity'] ?? 0;
    $sameday_stock = $product_row['sameday_stock_today'] ?? 0;
    $has_availtoday = !empty($product_row['availtoday_status_id']);
    $todays_dates = $product_row['todays_product_dates'] ? explode(', ', $product_row['todays_product_dates']) : [];
    $regular_dates = $product_row['regular_today_dates'] ? explode(', ', $product_row['regular_today_dates']) : [];
    $show_when_unavailable = (bool)$product_row['show_when_unavailable'];
    $hide_when_unavailable = (bool)$product_row['hide_when_unavailable'];
    
    // Step 1: Check stock based on product type
    $stock_unavailable = false;
    
    if ($status_id == 4) {
        // Same-day ONLY product
        $stock_unavailable = ($sameday_stock == 0 || $sameday_stock === null);
    } elseif (in_array($status_id, [1, 2, 3])) {
        if ($has_availtoday) {
            // DUAL capability: unavailable if BOTH stocks are 0
            $stock_unavailable = ($preorder_stock == 0 && ($sameday_stock == 0 || $sameday_stock === null));
        } else {
            // Pre-order ONLY
            $stock_unavailable = ($preorder_stock == 0);
        }
    }
    
    // Step 2: Check date availability
    $date_unavailable = false;
    
    if ($status_id == 4) {
        // Same-day ONLY: must have date in todays_products_dates
        $date_unavailable = !in_array($today_date, $todays_dates);
    } elseif (in_array($status_id, [1, 2, 3]) && $has_availtoday) {
        // DUAL capability: check regular_products_today_dates for same-day option
        // Date unavailable only affects same-day ordering, not pre-orders
        // So we only mark as date_unavailable if trying to order same-day
        // For display purposes, if no date exists, same-day option won't be available
        // but product can still be ordered as pre-order
        $date_unavailable = false; // Pre-order products are not date-restricted for display
    }
    
    // Step 3: Determine overall unavailability
    // Product is unavailable if BOTH stock AND date conditions fail
    $result['is_unavailable'] = $stock_unavailable || $date_unavailable;
    
    if ($stock_unavailable) {
        $result['unavailable_reason'] = 'Out of Stock';
    } elseif ($date_unavailable) {
        $result['unavailable_reason'] = 'Not Available Today';
    }
    
    // Step 4: Apply visibility rules
    if ($result['is_unavailable']) {
        // Priority: hide_when_unavailable takes precedence
        if ($hide_when_unavailable) {
            $result['should_display'] = false;
        } elseif ($show_when_unavailable) {
            $result['should_display'] = true;
        } else {
            // Default: hide unavailable products
            $result['should_display'] = false;
        }
    } else {
        // Available products are always displayed
        $result['should_display'] = true;
    }
    
    return $result;
}
```

### 3. Product Rendering Logic

**Current State:**
Products are rendered in a loop with unavailability checks already in place.

**Enhancement:**
- Add `hide_when_unavailable` to product data
- Implement filtering before rendering (skip products where `should_display = false`)
- Enhance unavailability indicators

**Implementation:**
```php
// After fetching all products
$products_to_display = [];

foreach ($all_products as $row) {
    $availability = determineProductAvailability($row, $today_date);
    
    // Skip products that should not be displayed
    if (!$availability['should_display']) {
        continue;
    }
    
    // Add availability info to product data
    $row['is_unavailable'] = $availability['is_unavailable'];
    $row['unavailable_reason'] = $availability['unavailable_reason'];
    
    $products_to_display[] = $row;
}

// Sort products (existing sorting logic)
usort($products_to_display, function($a, $b) use ($today_date) {
    // Existing sort logic...
});

// Render products
foreach ($products_to_display as $row) {
    // Existing rendering code...
}
```

## Data Models

### Products Table
```sql
products
├── id (INT)
├── name (VARCHAR)
├── price (DECIMAL)
├── quantity (INT) -- Regular/pre-order stock
├── status_id (INT) -- 1,2,3 = pre-order, 4 = same-day only
├── availtoday_status_id (INT) -- If set, product has same-day capability
├── show_when_unavailable (TINYINT) -- 1 = show, 0 = don't show
├── hide_when_unavailable (TINYINT) -- 1 = hide, 0 = don't hide
└── ...
```

### Quantity Per Day SDO Table
```sql
quantity_per_day_sdo
├── id (INT)
├── product_id (INT)
├── date (DATE) -- Specific date for same-day stock
├── quantity (INT) -- Same-day stock for this date
└── ...
```

### Date Availability Tables
```sql
todays_products_dates
├── id (INT)
├── product_id (INT) -- For status_id = 4 products
├── available_date (DATE)
└── ...

regular_products_today_dates
├── id (INT)
├── product_id (INT) -- For status_id = 1,2,3 products with same-day option
├── available_date (DATE)
└── ...
```

## Error Handling

### Missing Data Scenarios

1. **No stock record in `quantity_per_day_sdo`:**
   - Treat as 0 stock for same-day orders
   - Product can still be available for pre-orders (if DUAL capability)

2. **No date record in date tables:**
   - Same-day ONLY products: Mark as unavailable
   - DUAL capability products: Same-day option disabled, pre-order still available

3. **Both visibility flags set to 1:**
   - `hide_when_unavailable` takes precedence
   - Product is hidden

4. **Database query errors:**
   - Log error to PHP error log
   - Continue processing other products
   - Display generic error message if no products can be loaded

### Logging Strategy

```php
// Log visibility decisions for debugging
if ($availability['is_unavailable'] && !$availability['should_display']) {
    error_log(sprintf(
        "[Product Visibility] Hidden - Product ID: %d, Name: %s, Reason: %s, hide_flag: %d, show_flag: %d",
        $row['id'],
        $row['name'],
        $availability['unavailable_reason'],
        $row['hide_when_unavailable'],
        $row['show_when_unavailable']
    ));
}
```

## Testing Strategy

### Unit Testing Scenarios

1. **Stock-based unavailability:**
   - Status 4 product with 0 same-day stock
   - Status 1/2/3 product with 0 pre-order stock
   - DUAL product with 0 in both stocks
   - DUAL product with stock in one but not the other

2. **Date-based unavailability:**
   - Status 4 product without today's date in `todays_products_dates`
   - Status 4 product with future date only
   - DUAL product without today's date in `regular_products_today_dates`

3. **Visibility flag combinations:**
   - `show_when_unavailable = 1, hide_when_unavailable = 0`: Show
   - `show_when_unavailable = 0, hide_when_unavailable = 1`: Hide
   - `show_when_unavailable = 1, hide_when_unavailable = 1`: Hide (priority)
   - `show_when_unavailable = 0, hide_when_unavailable = 0`: Hide (default)

4. **Combined scenarios:**
   - Unavailable due to stock + show flag = 1: Display with indicators
   - Unavailable due to date + hide flag = 1: Hidden
   - Available product with any flag combination: Always displayed

### Integration Testing

1. **Database query performance:**
   - Measure query execution time with 100+ products
   - Verify LEFT JOINs return correct data
   - Test with missing records in joined tables

2. **Frontend rendering:**
   - Verify hidden products don't appear in HTML
   - Verify unavailable indicators display correctly
   - Test sorting with mix of available/unavailable products

3. **User experience:**
   - Test "Add to Cart" button disabled state
   - Verify unavailable products can't be added to cart
   - Test modal display for unavailable products

## Performance Considerations

### Query Optimization

1. **Single query approach:**
   - Use LEFT JOINs to fetch all data in one query
   - Avoid N+1 query problem

2. **Indexing:**
   - Ensure indexes exist on:
     - `quantity_per_day_sdo.product_id` and `quantity_per_day_sdo.date`
     - `todays_products_dates.product_id` and `todays_products_dates.available_date`
     - `regular_products_today_dates.product_id` and `regular_products_today_dates.available_date`

3. **Caching:**
   - Cache availability results during single page render
   - Avoid recalculating for same product

### Frontend Performance

1. **Filtering before rendering:**
   - Remove hidden products from array before rendering HTML
   - Reduces DOM size and improves page load time

2. **Lazy loading:**
   - Existing lazy loading for images continues to work
   - Unavailable products (if shown) also benefit from lazy loading

## Security Considerations

1. **SQL Injection Prevention:**
   - Use prepared statements (already implemented)
   - Sanitize all user inputs (category filter)

2. **XSS Prevention:**
   - Use `htmlspecialchars()` for all output (already implemented)
   - Sanitize product data in JSON attributes

3. **Access Control:**
   - Verify user session before allowing cart operations
   - Validate product availability server-side during checkout

## Migration and Rollback

### Migration Steps

1. **Database verification:**
   - Verify `hide_when_unavailable` column exists in `products` table
   - If missing, add column: `ALTER TABLE products ADD COLUMN hide_when_unavailable TINYINT(1) DEFAULT 0`

2. **Code deployment:**
   - Deploy updated `product-dashboard.php`
   - No database migrations required (column should already exist)

3. **Testing:**
   - Test with various product configurations
   - Verify no products are incorrectly hidden

### Rollback Plan

1. **Code rollback:**
   - Revert `product-dashboard.php` to previous version
   - System returns to current behavior (stock-only checks)

2. **No database rollback needed:**
   - New column doesn't affect existing functionality
   - Can remain in database even if code is rolled back

## Future Enhancements

1. **Admin interface:**
   - Add UI to easily toggle `show_when_unavailable` and `hide_when_unavailable` flags
   - Bulk update visibility settings for multiple products

2. **Customer notifications:**
   - "Notify me when available" feature for hidden products
   - Email alerts when stock is replenished

3. **Analytics:**
   - Track how often unavailable products are viewed
   - Measure impact of showing vs hiding unavailable products on sales

4. **Advanced filtering:**
   - Allow customers to toggle "Show unavailable products"
   - Filter by availability status in product dashboard
