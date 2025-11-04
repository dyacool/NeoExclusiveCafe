# Design Document

## Overview

This design document outlines the refactoring approach for product-dashboard.php to simplify the product availability logic and improve code maintainability. The refactor adopts the cleaner dual-capability product model from the admin product-list changes, ensuring consistent handling of products that support both pre-order and same-day ordering.

The main goals are:
1. Simplify the `determineProductAvailability()` function with clearer logic
2. Improve badge display logic to handle dual-capability products correctly
3. Enhance the quantity modal to clearly indicate the ordering method
4. Maintain existing sort order and functionality while improving code clarity

## Architecture

### Component Structure

The refactor focuses on three main areas:

1. **Availability Determination Logic** (`determineProductAvailability()` function)
   - Simplify the nested conditionals
   - Add capability flags for clearer logic flow
   - Improve variable naming for better readability

2. **Badge Display Logic** (Product card rendering)
   - Use capability flags to determine badge type
   - Consolidate duplicate badge logic
   - Ensure consistency with admin product-list

3. **Quantity Modal** (Add to cart modal)
   - Add order method header
   - Show appropriate quantity inputs based on product capabilities
   - Clearly label pre-order vs same-day quantities

### Data Flow

```
Product Query (SQL)
  ↓
determineProductAvailability()
  ↓
Capability Flags (has_preorder, has_sameday)
  ↓
Badge Display Logic
  ↓
Product Card Rendering
  ↓
User Clicks Product
  ↓
Quantity Modal (with order method context)
```

## Components and Interfaces

### 1. Refactored `determineProductAvailability()` Function

#### Current Issues
- Complex nested conditionals
- Unclear variable names
- Difficult to follow logic flow
- Mixes capability determination with availability checking

#### Improved Design

```php
function determineProductAvailability($product_row, $today_date) {
    $result = [
        'has_preorder' => false,
        'has_sameday' => false,
        'is_unavailable' => false,
        'unavailable_reason' => '',
        'should_display' => true
    ];
    
    // Extract data
    $status_id = $product_row['status_id'];
    $preorder_stock = $product_row['quantity'] ?? 0;
    $sameday_stock = $product_row['sameday_stock_today'] ?? 0;
    $has_availtoday_config = !empty($product_row['availtoday_status_id']);
    $todays_dates = $product_row['todays_product_dates'] ? explode(', ', $product_row['todays_product_dates']) : [];
    $regular_dates = $product_row['regular_today_dates'] ? explode(', ', $product_row['regular_today_dates']) : [];
    $show_when_unavailable = (bool)($product_row['show_when_unavailable'] ?? 0);
    $hide_when_unavailable = (bool)($product_row['hide_when_unavailable'] ?? 0);
    
    // STEP 1: Determine product capabilities
    // Pre-order capability: status 1/2/3 with stock
    $result['has_preorder'] = in_array($status_id, [1, 2, 3]) && $preorder_stock > 0;
    
    // Same-day capability: status 4 OR (status 1/2/3 with availtoday config)
    if ($status_id == 4) {
        // Pure same-day product: needs stock and valid date
        $has_valid_date = in_array($today_date, $todays_dates);
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    } elseif (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
        // Dual-capability product: needs stock and valid date for same-day
        $has_valid_date = in_array($today_date, $regular_dates);
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    }
    
    // STEP 2: Determine availability
    // Product is unavailable if it has NO capabilities
    $result['is_unavailable'] = !$result['has_preorder'] && !$result['has_sameday'];
    
    if ($result['is_unavailable']) {
        // Determine reason
        if ($status_id == 4) {
            // Same-day only product
            if ($sameday_stock <= 0) {
                $result['unavailable_reason'] = 'Out of Stock';
            } else {
                $result['unavailable_reason'] = 'Not Available Today';
            }
        } elseif (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
            // Dual-capability product with no stock
            $result['unavailable_reason'] = 'Out of Stock';
        } else {
            // Pre-order only product
            $result['unavailable_reason'] = 'Out of Stock';
        }
    }
    
    // STEP 3: Apply visibility rules
    if ($result['is_unavailable']) {
        if ($hide_when_unavailable) {
            $result['should_display'] = false;
        } elseif ($show_when_unavailable) {
            $result['should_display'] = true;
        } else {
            $result['should_display'] = false; // Default: hide
        }
    }
    
    return $result;
}
```

#### Key Improvements
- **Capability flags first**: Determine what the product CAN do before checking availability
- **Clear variable names**: `has_preorder`, `has_sameday` are self-documenting
- **Linear flow**: Step 1 → Step 2 → Step 3, no nested conditions
- **Simplified logic**: Each capability is checked independently

### 2. Badge Display Logic

#### Current Code
```php
if ($is_available_today) {
    echo "<div class='today-badge-left'>Same Day Order</div>";
} else {
    echo "<div class='preorder-badge-left'>Pre-Order</div>";
}
```

#### Improved Code
```php
if ($is_unavailable) {
    echo "<div class='unavailable-badge-left'>" . htmlspecialchars($unavailable_reason) . "</div>";
} else {
    // Use capability flags from availability check
    if ($availability['has_sameday'] && $availability['has_preorder']) {
        // Dual capability
        echo "<div class='today-badge-left'>Same Day & Pre-Order</div>";
    } elseif ($availability['has_sameday']) {
        // Same-day only
        echo "<div class='today-badge-left'>Same Day Order</div>";
    } elseif ($availability['has_preorder']) {
        // Pre-order only
        echo "<div class='preorder-badge-left'>Pre-Order</div>";
    }
}
```

#### Key Improvements
- Uses capability flags from `determineProductAvailability()`
- Clear if-else chain based on capabilities
- Handles all three cases: dual, same-day only, pre-order only
- Matches admin product-list badge logic

### 3. Quantity Modal Enhancement

#### Current Modal Structure
The quantity modal currently doesn't clearly indicate which ordering method is being used.

#### Improved Modal Structure

```html
<!-- Quantity Modal -->
<div id="quantityModal" class="modal">
    <div class="modal-content">
        <!-- Order Method Header -->
        <div class="order-method-header">
            <h3 id="orderMethodTitle">Select Quantity</h3>
            <span id="orderMethodBadge" class="order-method-badge"></span>
        </div>
        
        <!-- Product Info -->
        <div class="product-info">
            <img id="modalProductImage" src="" alt="">
            <h4 id="modalProductName"></h4>
        </div>
        
        <!-- Quantity Inputs -->
        <div id="quantityInputsContainer">
            <!-- For dual-capability products -->
            <div id="sameDayQuantitySection" style="display: none;">
                <label for="sameDayQuantityInput">
                    Same Day Order Quantity
                    <span class="stock-info" id="sameDayStockInfo"></span>
                </label>
                <input type="number" id="sameDayQuantityInput" min="0" value="0">
            </div>
            
            <div id="preOrderQuantitySection" style="display: none;">
                <label for="preOrderQuantityInput">
                    Pre-Order Quantity
                    <span class="stock-info" id="preOrderStockInfo"></span>
                </label>
                <input type="number" id="preOrderQuantityInput" min="0" value="0">
            </div>
        </div>
        
        <!-- Actions -->
        <div class="modal-actions">
            <button onclick="closeQuantityModal()">Cancel</button>
            <button onclick="addToCart()">Add to Cart</button>
        </div>
    </div>
</div>
```

#### Modal Initialization Logic

```javascript
function openQuantityModal(productData) {
    const modal = document.getElementById('quantityModal');
    const titleEl = document.getElementById('orderMethodTitle');
    const badgeEl = document.getElementById('orderMethodBadge');
    const sameDaySection = document.getElementById('sameDayQuantitySection');
    const preOrderSection = document.getElementById('preOrderQuantitySection');
    
    // Determine product capabilities
    const hasPreorder = productData.has_preorder;
    const hasSameday = productData.has_sameday;
    
    // Set header and show appropriate sections
    if (hasSameday && hasPreorder) {
        // Dual capability
        titleEl.textContent = 'Select Quantities';
        badgeEl.textContent = 'Same Day & Pre-Order';
        badgeEl.className = 'order-method-badge dual';
        sameDaySection.style.display = 'block';
        preOrderSection.style.display = 'block';
    } else if (hasSameday) {
        // Same-day only
        titleEl.textContent = 'Same Day Order';
        badgeEl.textContent = 'Same Day Order';
        badgeEl.className = 'order-method-badge sameday';
        sameDaySection.style.display = 'block';
        preOrderSection.style.display = 'none';
    } else if (hasPreorder) {
        // Pre-order only
        titleEl.textContent = 'Pre-Order';
        badgeEl.textContent = 'Pre-Order';
        badgeEl.className = 'order-method-badge preorder';
        sameDaySection.style.display = 'none';
        preOrderSection.style.display = 'block';
    }
    
    // Set stock info
    document.getElementById('sameDayStockInfo').textContent = 
        `(${productData.sameday_stock} available)`;
    document.getElementById('preOrderStockInfo').textContent = 
        `(${productData.preorder_stock} available)`;
    
    // Show modal
    modal.style.display = 'flex';
}
```

### 4. Product Data Structure

Update the product data passed to JavaScript to include capability flags:

```php
$productData = [
    'id' => $row['id'],
    'name' => $row['name'],
    'price' => $row['price'],
    'status_id' => $row['status_id'],
    'preorder_stock' => $row['quantity'],
    'sameday_stock' => $row['sameday_stock_today'] ?? 0,
    'has_preorder' => $availability['has_preorder'],
    'has_sameday' => $availability['has_sameday'],
    'is_unavailable' => $availability['is_unavailable'],
    // ... other fields
];
```

## Data Models

### Product Availability Result Structure

```php
[
    'has_preorder' => bool,      // Product can be pre-ordered
    'has_sameday' => bool,       // Product available for same-day
    'is_unavailable' => bool,    // Product has no capabilities
    'unavailable_reason' => string, // Why product is unavailable
    'should_display' => bool     // Should show on page
]
```

### Product Capability Matrix

| status_id | quantity | availtoday_status_id | sameday_stock | has_preorder | has_sameday |
|-----------|----------|---------------------|---------------|--------------|-------------|
| 1/2/3     | > 0      | NULL                | N/A           | true         | false       |
| 1/2/3     | 0        | NULL                | N/A           | false        | false       |
| 1/2/3     | > 0      | SET                 | > 0 + date    | true         | true        |
| 1/2/3     | > 0      | SET                 | 0 or no date  | true         | false       |
| 1/2/3     | 0        | SET                 | > 0 + date    | false        | true        |
| 4         | N/A      | SET                 | > 0 + date    | false        | true        |
| 4         | N/A      | SET                 | 0 or no date  | false        | false       |

## Error Handling

### Edge Cases

1. **Missing stock data**: Default to 0
2. **Missing dates**: Treat as no valid dates
3. **Null availtoday_status_id**: Product doesn't have same-day capability
4. **Empty date arrays**: No dates available

### Validation

- Always check for null/undefined before array operations
- Use `??` operator for default values
- Validate date format before comparison

## Testing Strategy

### Manual Testing Scenarios

1. **Pre-order only product**
   - Create product with status_id=1, quantity=10, no availtoday_status_id
   - Verify "Pre-Order" badge shows
   - Verify quantity modal shows only pre-order input

2. **Same-day only product**
   - Create product with status_id=4, sameday stock, valid date
   - Verify "Same Day Order" badge shows
   - Verify quantity modal shows only same-day input

3. **Dual-capability product**
   - Create product with status_id=2, quantity=10, availtoday_status_id=2, sameday stock, valid date
   - Verify "Same Day & Pre-Order" badge shows
   - Verify quantity modal shows both inputs

4. **Unavailable product**
   - Create product with no stock
   - Verify "Unavailable" badge shows
   - Verify product appears at bottom of list

5. **Dual-capability with only pre-order stock**
   - Create product with status_id=2, quantity=10, availtoday_status_id=2, sameday stock=0
   - Verify "Pre-Order" badge shows
   - Verify product still displays

### Sort Order Verification

1. Products with same-day stock appear first
2. Featured products appear next
3. Pre-order only products appear next
4. Unavailable products appear last

## Implementation Notes

### Code Organization

1. Keep `determineProductAvailability()` function at top of PHP section
2. Add inline comments for each step
3. Use consistent variable naming throughout
4. Extract magic numbers to constants if needed

### Performance Considerations

- No additional database queries needed
- Capability determination is O(1) operation
- Badge logic is simplified, reducing CPU cycles

### Backward Compatibility

- Maintains existing database schema
- No changes to product_statuses or related tables
- Existing products work without modification

## Migration Path

### Phase 1: Refactor Availability Function
- Update `determineProductAvailability()` with new logic
- Add capability flags to return value
- Test with existing products

### Phase 2: Update Badge Display
- Modify badge rendering to use capability flags
- Test badge display for all product types

### Phase 3: Enhance Quantity Modal
- Add order method header
- Show/hide appropriate quantity inputs
- Update add-to-cart logic

### Phase 4: Testing & Validation
- Manual testing of all scenarios
- Verify sort order
- Check edge cases
