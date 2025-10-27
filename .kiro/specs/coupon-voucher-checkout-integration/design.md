# Design Document: Coupon/Voucher Integration for AvailToday Checkout

## Overview

This design document outlines the technical approach for integrating the existing coupon/voucher system into the availtoday checkout flow. The implementation will mirror the functionality already present in the regular checkout (checkout.php) while adapting it to the availtoday checkout context (availtoday-checkout.php and process-availtoday-checkout.php).

The integration will be achieved by:
1. Adding coupon UI components to the availtoday checkout page
2. Implementing AJAX-based coupon validation without page refresh
3. Integrating discount calculations into the order total
4. Passing coupon data to the order processing backend
5. Tracking coupon usage to prevent reuse

## Architecture

### Component Overview

```
┌─────────────────────────────────────────────────────────────┐
│                  AvailToday Checkout Page                    │
│              (availtoday-checkout.php)                       │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Coupon Input Section                              │    │
│  │  - Input field for coupon code                     │    │
│  │  - "Check Coupon" button                           │    │
│  │  - Message display area                            │    │
│  │  - Applied coupon display                          │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Order Summary                                     │    │
│  │  - Subtotal                                        │    │
│  │  - Discount (if coupon applied)                    │    │
│  │  - Shipping Fee                                    │    │
│  │  - Total                                           │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ AJAX Request
                            ▼
┌─────────────────────────────────────────────────────────────┐
│           Coupon Validation API                              │
│     (validate-coupon.php)                                    │
│                                                              │
│  - Validates coupon code                                     │
│  - Checks expiration and usage limits                        │
│  - Calculates discount amount                                │
│  - Returns coupon data                                       │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ Response
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              JavaScript Handler                              │
│                                                              │
│  - Updates UI with coupon status                             │
│  - Recalculates order total                                  │
│  - Stores coupon data for order submission                   │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ Form Submission
                            ▼
┌─────────────────────────────────────────────────────────────┐
│         Order Processing Backend                             │
│     (process-availtoday-checkout.php)                        │
│                                                              │
│  - Receives coupon data with order                           │
│  - Applies discount to total                                 │
│  - Increments coupon usage count                             │
│  - Stores coupon info in order notes                         │
│  - Creates order record                                      │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Frontend UI Components (availtoday-checkout.php)

#### 1.1 Coupon Input Section HTML
Location: Insert after order summary, before payment section

```html
<div class="coupon-section">
    <h3>Have a Coupon?</h3>
    <div class="coupon-input-group">
        <input type="text" 
               id="coupon_code" 
               class="coupon-input" 
               placeholder="Enter coupon code"
               maxlength="50">
        <button type="button" 
                id="check_coupon_btn" 
                class="btn-check-coupon"
                onclick="checkCoupon()">
            Check Coupon
        </button>
    </div>
    <div id="coupon_message" class="coupon-message"></div>
    <div id="coupon_applied" class="coupon-applied" style="display: none;">
        <div class="applied-coupon-info">
            <div>
                <span class="coupon-code-display"></span>
                <span class="coupon-discount"></span>
            </div>
            <button type="button" 
                    class="btn-remove-coupon" 
                    onclick="removeCoupon()">
                Remove
            </button>
        </div>
    </div>
</div>
```

#### 1.2 Updated Order Summary HTML
Add discount row to order summary:

```html
<div class="order-summary">
    <div class="summary-row">
        <span>Subtotal:</span>
        <span id="subtotal">₱<?= number_format($cart_total, 2) ?></span>
    </div>
    <div class="summary-row" id="discount-row" style="display: none;">
        <span>Discount:</span>
        <span id="discount_amount" style="color: #28a745;">-₱0.00</span>
    </div>
    <div class="summary-row">
        <span>Shipping Fee:</span>
        <span id="shipping_fee">₱0.00</span>
    </div>
    <div class="summary-row total-row">
        <span>Total:</span>
        <span id="total_amount">₱<?= number_format($cart_total, 2) ?></span>
    </div>
</div>
```

#### 1.3 CSS Styles
Copy coupon-related styles from checkout.php (lines 990-1126) to availtoday-checkout.php

Key classes:
- `.coupon-section`
- `.coupon-input-group`
- `.coupon-input`
- `.btn-check-coupon` (renamed from `.btn-apply-coupon`)
- `.coupon-message`
- `.coupon-applied`
- `.applied-coupon-info`
- `.btn-remove-coupon`

### 2. JavaScript Functions (availtoday-checkout.php)

#### 2.1 Global Variables
```javascript
let appliedCoupon = null;
let discountAmount = 0;
const subtotal = <?= json_encode($cart_total) ?>;
```

#### 2.2 checkCoupon() Function
```javascript
async function checkCoupon() {
    const couponInput = document.getElementById('coupon_code');
    const checkBtn = document.getElementById('check_coupon_btn');
    const couponCode = couponInput.value.trim().toUpperCase();
    
    if (!couponCode) {
        showCouponMessage('Please enter a coupon code', false);
        return;
    }
    
    // Disable button during request
    checkBtn.disabled = true;
    checkBtn.textContent = 'Checking...';
    
    try {
        const response = await fetch('../../../backend/pages/user-page-content/validate-coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: couponCode,
                subtotal: subtotal,
                cart_items: [] // Can be populated if needed for product-specific coupons
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            appliedCoupon = result.coupon;
            discountAmount = result.discount_amount;
            
            // Show applied coupon
            showAppliedCoupon(appliedCoupon);
            showCouponMessage(result.message, true);
            
            // Update totals
            updateOrderTotal();
            
            // Show discount row
            const discountRow = document.getElementById('discount-row');
            const discountAmountElement = document.getElementById('discount_amount');
            if (discountRow && discountAmountElement) {
                discountRow.style.display = 'flex';
                discountAmountElement.textContent = `-₱${discountAmount.toFixed(2)}`;
            }
            
            // Clear input
            couponInput.value = '';
        } else {
            showCouponMessage(result.message || 'Invalid coupon code', false);
        }
    } catch (error) {
        console.error('Error checking coupon:', error);
        showCouponMessage('Error checking coupon. Please try again.', false);
    } finally {
        checkBtn.disabled = false;
        checkBtn.textContent = 'Check Coupon';
    }
}
```

#### 2.3 Helper Functions
```javascript
function showCouponMessage(message, isSuccess) {
    const messageElement = document.getElementById('coupon_message');
    if (messageElement) {
        messageElement.textContent = message;
        messageElement.className = 'coupon-message ' + (isSuccess ? 'success' : 'error');
        messageElement.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            messageElement.style.display = 'none';
        }, 5000);
    }
}

function showAppliedCoupon(coupon) {
    const appliedElement = document.getElementById('coupon_applied');
    const codeDisplay = appliedElement.querySelector('.coupon-code-display');
    const discountDisplay = appliedElement.querySelector('.coupon-discount');
    
    if (codeDisplay) {
        codeDisplay.textContent = `Coupon: ${coupon.code}`;
    }
    
    if (discountDisplay) {
        let discountText = '';
        if (coupon.type === 'percentage') {
            discountText = `-${coupon.value}% (₱${discountAmount.toFixed(2)})`;
        } else if (coupon.type === 'fixed') {
            discountText = `-₱${discountAmount.toFixed(2)}`;
        } else if (coupon.type === 'free_shipping') {
            discountText = 'Free Shipping';
        }
        discountDisplay.textContent = discountText;
    }
    
    appliedElement.style.display = 'block';
}

function hideAppliedCoupon() {
    const appliedElement = document.getElementById('coupon_applied');
    if (appliedElement) {
        appliedElement.style.display = 'none';
    }
}

function removeCoupon() {
    appliedCoupon = null;
    discountAmount = 0;
    
    // Hide applied coupon
    hideAppliedCoupon();
    
    // Hide discount row
    const discountRow = document.getElementById('discount-row');
    if (discountRow) {
        discountRow.style.display = 'none';
    }
    
    // Update totals
    updateOrderTotal();
    
    showCouponMessage('Coupon removed successfully', true);
}

function updateOrderTotal() {
    const totalElement = document.getElementById('total_amount');
    const shippingFee = parseFloat(document.getElementById('shipping_fee').textContent.replace('₱', '').replace(',', '')) || 0;
    
    const total = subtotal - discountAmount + shippingFee;
    
    if (totalElement) {
        totalElement.textContent = '₱' + total.toFixed(2);
    }
}
```

#### 2.4 Form Submission Update
Modify the form submission to include coupon data:

```javascript
// Add hidden inputs for coupon data before form submission
if (appliedCoupon) {
    const couponInput = document.createElement('input');
    couponInput.type = 'hidden';
    couponInput.name = 'applied_coupon';
    couponInput.value = JSON.stringify(appliedCoupon);
    form.appendChild(couponInput);
    
    const discountInput = document.createElement('input');
    discountInput.type = 'hidden';
    discountInput.name = 'discount_amount';
    discountInput.value = discountAmount;
    form.appendChild(discountInput);
}
```

### 3. Backend Processing (process-availtoday-checkout.php)

#### 3.1 Receive Coupon Data
Add after line 60 (after cart_items decoding):

```php
// Process coupon data if provided
$discount_amount = 0;
$applied_coupon = null;

if (!empty($_POST['applied_coupon'])) {
    $applied_coupon = json_decode($_POST['applied_coupon'], true);
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    
    error_log("Coupon applied: " . print_r($applied_coupon, true));
    error_log("Discount amount: " . $discount_amount);
}
```

#### 3.2 Update Total Calculation
Modify the cart_total calculation (around line 60):

```php
// Calculate final total with discount
$final_total = $cart_total - $discount_amount;

// Validate that total is not negative
if ($final_total < 0) {
    $final_total = 0;
}
```

#### 3.3 Update Order Notes
Modify the notes field to include coupon information (around line 100):

```php
$combined_notes = $special_instructions;

// Include coupon information in notes if applied
if ($applied_coupon) {
    $coupon_info = "\n\nCoupon Applied: " . $applied_coupon['code'] . 
                   " - Discount: ₱" . number_format($discount_amount, 2);
    $combined_notes .= $coupon_info;
}
```

#### 3.4 Update Coupon Usage Count
Add after successful order creation (after line 150, before commit):

```php
// Update coupon usage count if coupon was applied
if ($applied_coupon && isset($applied_coupon['id'])) {
    $update_coupon_sql = "UPDATE promotions SET used_count = used_count + 1 WHERE id = ?";
    $update_coupon_stmt = $conn->prepare($update_coupon_sql);
    
    if ($update_coupon_stmt) {
        $coupon_id = intval($applied_coupon['id']);
        $update_coupon_stmt->bind_param("i", $coupon_id);
        
        if (!$update_coupon_stmt->execute()) {
            error_log("Warning: Failed to update coupon usage count: " . $update_coupon_stmt->error);
            // Don't fail the order if coupon update fails, just log it
        } else {
            error_log("Successfully updated coupon usage count for coupon ID: " . $coupon_id);
        }
        
        $update_coupon_stmt->close();
    } else {
        error_log("Warning: Failed to prepare coupon update statement: " . $conn->error);
    }
}
```

#### 3.5 Update Order SQL
Modify the order insertion to use the discounted total:

```php
$order_stmt->bind_param("sssidsss", 
    $customer_full_name,
    $phone,
    $email,
    $full_address,
    $total_items,
    $final_total,  // Use final_total instead of cart_total
    $delivery_method_enum,
    $today_date,
    $pickup_time,
    $combined_notes
);
```

## Data Models

### Existing Database Tables

#### promotions table
```sql
- id (INT, PRIMARY KEY)
- code (VARCHAR) - Coupon code
- title (VARCHAR) - Coupon title/description
- type (ENUM: 'percentage', 'fixed', 'free_shipping')
- value (DECIMAL) - Discount value
- min_purchase (DECIMAL) - Minimum purchase requirement
- usage_limit (INT) - Maximum number of uses
- used_count (INT) - Current usage count
- activation_date (DATE)
- expiration_date (DATE)
- status (ENUM: 'active', 'inactive')
- applicable_to (VARCHAR)
- include_free_shipping (BOOLEAN)
- prevent_discounted (BOOLEAN)
- application_method (VARCHAR)
- usage_limit_per_user (INT)
```

#### orders table
```sql
- order_id (INT, PRIMARY KEY)
- customer_name (VARCHAR)
- customer_contact (VARCHAR)
- customer_email (VARCHAR)
- customer_address (TEXT)
- total_amount (DECIMAL) - Will include discount
- notes (TEXT) - Will store coupon information
- ... (other fields)
```

### Data Flow

1. **Coupon Validation Request**
```json
{
    "coupon_code": "SAVE20",
    "subtotal": 500.00,
    "cart_items": []
}
```

2. **Coupon Validation Response**
```json
{
    "success": true,
    "message": "Coupon applied successfully! You saved 20% (₱100.00)",
    "coupon": {
        "id": 1,
        "code": "SAVE20",
        "title": "20% Off",
        "type": "percentage",
        "value": 20,
        "discount_amount": 100.00,
        "min_purchase": 0,
        "applicable_to": "all",
        "include_free_shipping": false,
        "prevent_discounted": false
    },
    "discount_amount": 100.00
}
```

3. **Order Submission Data**
```php
$_POST = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '09123456789',
    'cart_total' => 500.00,
    'applied_coupon' => '{"id":1,"code":"SAVE20","type":"percentage","value":20}',
    'discount_amount' => 100.00,
    // ... other fields
];
```

## Error Handling

### Frontend Error Scenarios

1. **Empty Coupon Code**
   - Display: "Please enter a coupon code"
   - Action: Show error message, keep button enabled

2. **Invalid/Expired Coupon**
   - Display: "Invalid or expired coupon code"
   - Action: Show error message from API response

3. **Minimum Purchase Not Met**
   - Display: "Minimum purchase of ₱X required for this coupon"
   - Action: Show error message with required amount

4. **Usage Limit Reached**
   - Display: "This coupon has reached its usage limit"
   - Action: Show error message, prevent application

5. **Network Error**
   - Display: "Error checking coupon. Please try again."
   - Action: Log error to console, show generic message

### Backend Error Scenarios

1. **Coupon Update Failure**
   - Action: Log warning, continue with order processing
   - Rationale: Don't fail order if only coupon tracking fails

2. **Invalid Discount Amount**
   - Action: Validate discount doesn't exceed subtotal
   - Set to 0 if negative total would result

3. **Missing Coupon Data**
   - Action: Process order without discount
   - Log warning for debugging

## Testing Strategy

### Unit Testing Focus Areas

1. **Coupon Validation**
   - Test valid coupon codes
   - Test expired coupons
   - Test usage limit enforcement
   - Test minimum purchase requirements

2. **Discount Calculations**
   - Test percentage discounts
   - Test fixed amount discounts
   - Test free shipping coupons
   - Test discount doesn't exceed subtotal

3. **UI State Management**
   - Test coupon application flow
   - Test coupon removal flow
   - Test total recalculation
   - Test message display/hiding

### Integration Testing

1. **End-to-End Flow**
   - Apply coupon → Verify discount → Submit order → Verify order total
   - Apply coupon → Remove coupon → Submit order → Verify no discount

2. **Usage Tracking**
   - Apply coupon → Complete order → Verify used_count incremented
   - Apply coupon at limit → Verify rejection

3. **Cross-Browser Testing**
   - Test AJAX requests in different browsers
   - Test UI rendering consistency

### Manual Testing Checklist

- [ ] Coupon input accepts text and converts to uppercase
- [ ] "Check Coupon" button shows loading state
- [ ] Valid coupon displays success message
- [ ] Invalid coupon displays error message
- [ ] Applied coupon shows in UI with discount details
- [ ] Remove button clears coupon and recalculates total
- [ ] Order total updates correctly with discount
- [ ] Order submission includes coupon data
- [ ] Order notes contain coupon information
- [ ] Coupon usage count increments after order
- [ ] Coupon at usage limit is rejected
- [ ] Free shipping coupon works correctly
- [ ] Responsive design works on mobile

## Implementation Notes

### Code Reuse Strategy
- Copy coupon-related CSS from checkout.php (lines 990-1126)
- Adapt coupon JavaScript functions from checkout.php (lines 1569-1710)
- Use existing validate-coupon.php API without modifications
- Follow same data structure as process_order.php for consistency

### Button Label Change
- Changed from "Apply Coupon" to "Check Coupon" per requirements
- Maintains same functionality, just different label

### Backward Compatibility
- No changes to existing checkout.php
- No changes to validate-coupon.php API
- New functionality is additive only

### Performance Considerations
- AJAX validation prevents page reload
- Minimal database queries (1 for validation, 1 for usage update)
- Client-side calculation caching

### Security Considerations
- Coupon validation happens server-side
- Usage count updated in transaction with order
- Input sanitization on both frontend and backend
- SQL injection prevention via prepared statements
