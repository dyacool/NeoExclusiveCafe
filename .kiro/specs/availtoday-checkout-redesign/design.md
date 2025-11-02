# Design Document

## Overview

This design document outlines the redesign of `availtoday-checkout.php` to match the design, flow, and payment processing of `checkout.php`. The goal is to create a unified checkout experience where both same-day and pre-order checkouts look identical, with the only difference being that same-day checkout doesn't have a calendar (since the date is always today).

The redesign will copy the entire HTML structure, CSS styling, JavaScript functionality, and PayMongo payment integration from `checkout.php`, while maintaining the same-day specific logic (no calendar, today's date only).

## Architecture

### Current System

**checkout.php (Pre-Order):**
- Modern card-based layout
- Auto-loads saved customer info
- Calendar for date selection
- Coupon system
- PayMongo payment integration
- Redirects to payment-success.php

**availtoday-checkout.php (Same-Day):**
- Different/older layout
- Basic form structure
- No calendar (correct - same day only)
- Coupon system exists
- NO PayMongo integration (processes directly)
- Different confirmation flow

### Proposed System

**Both checkouts will have:**
- Identical HTML structure and CSS styling
- Same section layout (User Info, Shipping, Order Summary, Payment)
- Auto-load saved customer info
- Coupon system
- PayMongo payment integration
- Unified payment-success.php

**Differences:**
- `checkout.php`: Has calendar for date selection
- `availtoday-checkout.php`: No calendar, date = today

## Components and Interfaces

### 1. HTML Structure (Copy from checkout.php)

**Sections to copy:**
```html
<div class="checkout-container">
    <!-- Section 1: User Information -->
    <div class="section-card user-information">
        - Name display with "Load Contacts" button
        - Email display
        - Contact number input
    </div>
    
    <!-- Section 2: Shipping Options & Details -->
    <div class="section-card shipping-details">
        - Pickup/Delivery radio buttons
        - Pickup details (NO CALENDAR for availtoday)
        - Delivery details (address, set location button)
        - Time selection
    </div>
    
    <!-- Section 3: Order Summary -->
    <div class="section-card order-summary">
        - Coupon code section
        - Cart items list
        - Subtotal, Shipping, Discount, Total
    </div>
    
    <!-- Section 4: Payment Method -->
    <div class="section-card payment-method">
        - GCash radio button
        - Place Order button
    </div>
</div>
```

**Key Difference:**
- Remove the calendar section from shipping details
- Set pickup_date and delivery_date to today's date automatically

### 2. CSS Styling (Copy from checkout.php)

**Files to reference:**
- `checkout.css` - Main checkout styles
- `saved-info.css` - Saved customer info modal styles
- Inline styles in checkout.php

**Styling elements:**
- `.checkout-container` - Main container
- `.section-card` - Card-based sections
- `.user-information` - User info section
- `.shipping-details` - Shipping section
- `.order-summary` - Order summary section
- `.payment-method` - Payment section
- Button styles, form inputs, radio buttons
- Loading states, validation styles

### 3. JavaScript Functionality (Copy from checkout.php)

**Core Functions to Copy:**

1. **Saved Customer Info Integration**
```javascript
// Auto-load saved customer info
window.savedInfoManager = new SavedInfoManager();
savedInfoManager.loadEntries();

// Load primary address for delivery
loadPrimaryCustomerAddress();
```

2. **Shipping Method Toggle**
```javascript
// Handle pickup/delivery switching
pickupRadio.addEventListener('change', updateVisibility);
deliveryRadio.addEventListener('change', updateVisibility);

// Update shipping fee and totals
updateTotalAmount(shippingFee);
```

3. **Coupon System**
```javascript
// Apply coupon
applyCoupon();

// Remove coupon
removeCoupon();

// Calculate discount
calculateDiscount(coupon, subtotal);
```

4. **Form Validation**
```javascript
// Validate required fields
checkoutForm.checkValidity();

// Show validation errors
checkoutForm.reportValidity();
```

5. **PayMongo Payment Integration**
```javascript
// Submit to PayMongo
const paymentData = {
    payment_method: 'gcash',
    order_type: 'availtoday',  // Changed from 'regular'
    amount: finalAmount,
    order_data: orderData
};

const response = await fetch('process-payment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(paymentData)
});

// Redirect to PayMongo
if (result.payment_url) {
    window.location.href = result.payment_url;
}
```

### 4. Date Handling for Same-Day Orders

**Automatic Date Setting:**
```javascript
// Set today's date automatically (no user selection)
const today = new Date();
const todayStr = today.getFullYear() + '-' + 
               String(today.getMonth() + 1).padStart(2, '0') + '-' + 
               String(today.getDate()).padStart(2, '0');

// Set hidden inputs
document.getElementById('pickup_date').value = todayStr;
document.getElementById('delivery_date').value = todayStr;
```

**Display to User:**
```html
<div class="same-day-notice">
    <p><strong>Same-Day Order:</strong> This order is for today, <?= date('F j, Y') ?></p>
</div>
```

### 5. PayMongo Integration Flow

**Current Flow (checkout.php):**
```
User submits form
    ↓
Validate form data
    ↓
Prepare order data
    ↓
Send to process-payment.php
    ↓
Create PayMongo payment intent
    ↓
Redirect to PayMongo payment page
    ↓
User completes payment
    ↓
PayMongo redirects to payment-return.php
    ↓
Verify payment status
    ↓
Create order in database
    ↓
Send email notification
    ↓
Redirect to payment-success.php
```

**New Flow (availtoday-checkout.php):**
```
User submits form
    ↓
Validate form data
    ↓
Prepare order data (with order_type: 'availtoday')
    ↓
Send to process-payment.php
    ↓
Create PayMongo payment intent
    ↓
Redirect to PayMongo payment page
    ↓
User completes payment
    ↓
PayMongo redirects to payment-return.php?type=availtoday
    ↓
Verify payment status
    ↓
Create order in database (same-day order)
    ↓
Send email notification
    ↓
Redirect to payment-success.php?type=availtoday
```

### 6. Process Payment Integration

**process-payment.php modifications:**

Already handles both order types, just need to ensure `order_type: 'availtoday'` is passed:

```php
$order_type = $payment_data['order_type'] ?? 'regular';

// Store in session with type
$_SESSION['pending_payment'] = [
    'order_data' => $order_data,
    'amount' => $amount,
    'type' => $order_type,  // 'regular' or 'availtoday'
    'source_id' => $source_id
];
```

### 7. Payment Return Handler

**payment-return.php modifications:**

Already handles type parameter, ensure it processes availtoday orders correctly:

```php
$type = $_GET['type'] ?? 'regular';

// Process order based on type
if ($type === 'availtoday') {
    // Use availtoday-specific logic
    // Date is always today
    // Check same-day order limits
} else {
    // Regular pre-order logic
}
```

### 8. Payment Success Page

**payment-success.php modifications:**

```php
$type = $_GET['type'] ?? 'regular';
$order_type_display = $type === 'availtoday' ? 'Available Today' : 'Pre-Order';

// Display order type
echo '<p>Order Type: ' . $order_type_display . '</p>';
```

## Data Models

### Order Data Structure (Same for Both)

```javascript
{
    cart_items: [...],
    cart_total: 460.00,
    user_name: "John Doe",
    user_email: "john@example.com",
    contact_number: "09123456789",
    delivery_method: "pickup" | "delivery",
    delivery_address: "123 Main St, City",
    pickup_date: "2025-11-02",  // Today for availtoday
    delivery_date: "2025-11-02",  // Today for availtoday
    pickup_time: "10:00",
    delivery_time: "10:00",
    payment_method: "gcash",
    shipping_fee: 0 | 50,
    discount_amount: 0,
    applied_coupon: {...} | null,
    order_notes: "Special instructions"
}
```

## Error Handling

### Form Validation Errors

```javascript
// Required field validation
if (!contactNumber.value) {
    alert('Please enter your contact number');
    return;
}

// Delivery address validation
if (isDelivery && !deliveryAddress.value) {
    alert('Please enter your delivery address');
    return;
}
```

### Payment Processing Errors

```javascript
try {
    const response = await fetch('process-payment.php', {...});
    const result = await response.json();
    
    if (!result.success) {
        throw new Error(result.message || 'Payment processing failed');
    }
} catch (error) {
    alert('An error occurred: ' + error.message);
    setLoadingState(false);
}
```

### Same-Day Order Limit Errors

```php
// Check if same-day order limit reached
if ($current_orders >= $limit) {
    throw new Exception('Same-day order limit reached for today');
}
```

## Testing Strategy

### Visual Testing

1. **Layout Comparison**
   - Open checkout.php and availtoday-checkout.php side by side
   - Verify identical styling, spacing, colors
   - Check responsive design on mobile

2. **Section Verification**
   - Verify all sections appear in same order
   - Check that calendar is absent in availtoday-checkout
   - Verify "Same-Day Order" notice is displayed

### Functional Testing

1. **Auto-Load User Info**
   - Test with user who has saved info
   - Test with user who has no saved info
   - Verify "Load Contacts" button works

2. **Coupon System**
   - Apply percentage discount coupon
   - Apply fixed amount discount coupon
   - Apply free shipping coupon
   - Remove coupon and verify totals update

3. **Payment Flow**
   - Submit form with GCash payment
   - Verify redirect to PayMongo
   - Complete payment in sandbox
   - Verify redirect to payment-success.php?type=availtoday
   - Check order created in database
   - Verify email sent to admin

4. **Date Handling**
   - Verify pickup_date is set to today
   - Verify delivery_date is set to today
   - Check that no calendar is shown

### Integration Testing

1. **End-to-End Flow**
   - Add same-day products to cart
   - Go to availtoday-checkout
   - Fill form (auto-loaded info)
   - Apply coupon
   - Select delivery method
   - Submit payment
   - Complete PayMongo payment
   - Verify order confirmation

2. **Cross-Browser Testing**
   - Test in Chrome, Firefox, Safari
   - Test on mobile devices
   - Verify PayMongo redirect works

## Implementation Notes

### File Modifications

**Files to Modify:**
1. `frontend/pages/cart/availtoday-checkout.php` - Complete redesign
2. `frontend/pages/cart/process-payment.php` - Ensure handles availtoday type
3. `frontend/pages/cart/payment-return.php` - Ensure handles availtoday type
4. `frontend/pages/cart/payment-success.php` - Display correct order type

**Files to Reference (Copy From):**
1. `frontend/pages/cart/checkout.php` - HTML structure, JavaScript
2. `frontend/pages/cart/checkout.css` - Styling
3. `frontend/pages/cart/saved-info.css` - Saved info modal
4. `frontend/pages/cart/saved-info-manager.js` - Customer info management
5. `frontend/pages/cart/saved-info-ui.js` - UI functions

### Code Reuse Strategy

1. **Copy entire HTML structure** from checkout.php
2. **Remove calendar section** (lines with `<div id="calendar">`)
3. **Add same-day notice** where calendar was
4. **Copy all JavaScript** for form handling, validation, payment
5. **Update order_type** to 'availtoday' in payment submission
6. **Keep existing** process-availtoday-checkout.php for direct processing fallback

### Backward Compatibility

- Keep `process-availtoday-checkout.php` functional for any direct form submissions
- Ensure payment-return.php handles both old and new flows
- Maintain database schema compatibility

## Deployment Considerations

### Pre-Deployment Checklist

- [ ] Backup current availtoday-checkout.php
- [ ] Test PayMongo sandbox integration
- [ ] Verify email notifications work
- [ ] Test with real same-day products
- [ ] Check order limits enforcement
- [ ] Verify coupon system works
- [ ] Test on mobile devices

### Rollback Plan

If issues arise:
1. Restore backup of availtoday-checkout.php
2. System falls back to direct processing
3. No data loss risk
4. Orders can still be placed

### Monitoring

**Metrics to Track:**
- Same-day checkout completion rate
- PayMongo payment success rate
- Average checkout time
- Error rate during payment processing
- Customer feedback on new design

## Future Enhancements

### Phase 2 Improvements

1. **One-Click Checkout**
   - Save payment method for returning customers
   - Pre-fill all information automatically

2. **Real-Time Inventory**
   - Show live stock availability
   - Prevent overselling

3. **Multiple Payment Methods**
   - Add credit card option
   - Add PayPal integration

4. **Order Tracking**
   - Real-time order status updates
   - SMS notifications

