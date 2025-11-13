# Design Document

## Overview

This design adds payment method selection UI to both checkout pages (checkout.php and availtoday-checkout.php) by adding radio button options within the existing payment-options container. The implementation leverages the existing PayMongo integration in process-payment.php without requiring backend modifications.

## Architecture

### Frontend Components
- **Payment Options Section**: HTML section containing radio buttons for payment method selection
- **Form Validation**: JavaScript validation to ensure a payment method is selected before submission
- **Payment Processing Integration**: JavaScript code to include selected payment method in form submission

### Backend Components
- **Existing Payment Handler**: process-payment.php (no modifications needed)
- **PayMongo API Integration**: Existing PayMongoAPI class handles all payment types

### Data Flow
1. User selects payment method (GCash, Maya, or Bank Transfer) via radio button
2. User submits checkout form
3. JavaScript validates payment method selection
4. Form data including payment_method is sent to process-payment.php
5. process-payment.php creates appropriate PayMongo source/intent based on payment_method
6. User is redirected to PayMongo payment page or success page

## Components and Interfaces

### Payment Options UI Component

**Location**: Both checkout.php and availtoday-checkout.php

**HTML Structure**:
```html
<div class="section-card payment-options">
    <h2>Payment Method</h2>
    <div class="payment-methods">
        <label class="radio-option">
            <input type="radio" name="payment_method" value="gcash" required>
            <span>GCash</span>
        </label>
        <label class="radio-option">
            <input type="radio" name="payment_method" value="paymaya" required>
            <span>Maya (PayMaya)</span>
        </label>
        <label class="radio-option">
            <input type="radio" name="payment_method" value="card" required>
            <span>Bank Transfer / Credit Card</span>
        </label>
    </div>
</div>
```

**CSS Styling**:
- Reuse existing `.radio-option` styles from shipping method selection
- Consistent spacing and visual feedback
- Hover and selected states

### Form Submission Handler

**Modification to existing JavaScript**:

The existing form submission handler in both checkout pages already includes:
```javascript
const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
if (!paymentMethodEl) {
    throw new Error('Please select a payment method.');
}
formData.append('payment_method', paymentMethodEl.value);
```

This code will work automatically once the radio buttons are added to the HTML.

### Payment Processing Flow

**No changes required** - process-payment.php already handles:
- `gcash` → Creates PayMongo source with type 'gcash'
- `paymaya` → Creates PayMongo source with type 'paymaya'  
- `card` → Creates PayMongo payment intent

## Data Models

### Form Data Structure
```javascript
{
    payment_method: 'gcash' | 'paymaya' | 'card',
    // ... other existing form fields
}
```

### PayMongo Request Structure
Already implemented in process-payment.php:
```php
// For gcash/paymaya
$paymongo->createSource(
    $payment_method,  // 'gcash' or 'paymaya'
    $amount,
    'PHP',
    $return_url,
    $metadata
);

// For card
$paymongo->createPaymentIntent(
    $amount,
    'PHP',
    $description,
    $metadata
);
```

## Error Handling

### Client-Side Validation
- HTML5 `required` attribute on radio buttons
- JavaScript validation before form submission
- User-friendly error message if no payment method selected

### Server-Side Validation
Already implemented in process-payment.php:
```php
if (!in_array($payment_method, ['gcash', 'paymaya', 'card'])) {
    throw new Exception('Invalid payment method: ' . $payment_method);
}
```

## Testing Strategy

### Manual Testing
1. Test payment method selection on checkout.php
2. Test payment method selection on availtoday-checkout.php
3. Test form submission without selecting payment method (should show error)
4. Test GCash payment flow (sandbox)
5. Test Maya payment flow (sandbox)
6. Test Bank Transfer/Card payment flow (sandbox)
7. Verify PayMongo redirect URLs work correctly
8. Verify payment success/failure handling

### Integration Testing
- Verify payment_method value is correctly passed to process-payment.php
- Verify PayMongo API calls are made with correct payment type
- Verify redirect URLs are generated correctly for each payment type

## Implementation Notes

### Placement in HTML
The payment options section should be placed:
- After the shipping details section
- Before the order summary section
- Inside the checkout form

### Styling Consistency
- Use existing `.section-card` class for container
- Use existing `.radio-option` class for radio buttons
- Match the visual style of shipping method selection

### PayMongo Sandbox
- All payments will use PayMongo test/sandbox environment
- Test credentials are already configured in paymongo-config.php
- Card payments will redirect to PayMongo's hosted payment page

### No Backend Changes
- process-payment.php already supports all three payment methods
- No database schema changes required
- No API endpoint changes required
