/**
 * Coupon Management JavaScript
 * Handles single coupon application with UI state management
 */

let couponApplied = false;
let appliedCouponData = null;

/**
 * Initialize coupon management
 * Call this when the page loads
 */
function initCouponManagement() {
    const applyBtn = document.getElementById('apply-coupon-btn');
    const removeBtn = document.getElementById('remove-coupon-btn');
    const couponInput = document.getElementById('coupon-code');
    
    if (applyBtn) {
        applyBtn.addEventListener('click', applyCoupon);
    }
    
    if (removeBtn) {
        removeBtn.addEventListener('click', removeCoupon);
    }
    
    // Check if a coupon is already applied (from session)
    checkExistingCoupon();
    
    // Add emergency clear function to window for debugging
    window.clearCouponSession = function() {
        fetch('clear-coupon-session.php', { method: 'POST' })
            .then(() => {
                location.reload();
            });
    };
}

/**
 * Check if a coupon is already applied in the session
 */
function checkExistingCoupon() {
    // This would typically be populated from PHP session data
    // You can pass this data via a data attribute or inline script
    const existingCoupon = window.appliedCouponFromSession || null;
    
    if (existingCoupon) {
        couponApplied = true;
        appliedCouponData = existingCoupon;
        disableCouponInput();
        showAppliedCoupon(existingCoupon.code, existingCoupon.discount_amount);
    }
}

/**
 * Apply coupon code
 */
function applyCoupon() {
    const couponInput = document.getElementById('coupon-code');
    const couponCode = couponInput.value.trim();
    
    if (!couponCode) {
        showMessage('Please enter a coupon code', 'error');
        return;
    }
    
    // Get cart total for validation
    const subtotal = getCartSubtotal();
    const cartItems = getCartItems();
    
    // Show loading state
    const applyBtn = document.getElementById('apply-coupon-btn');
    const originalText = applyBtn.textContent;
    applyBtn.disabled = true;
    applyBtn.textContent = 'Applying...';
    
    // Call validation endpoint
    fetch('../../../backend/pages/user-page-content/validate-coupon.php', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
            'Content-Type': 'application/json',
            'Cache-Control': 'no-cache'
        },
        body: JSON.stringify({
            coupon_code: couponCode,
            subtotal: subtotal,
            cart_items: cartItems
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Apply coupon response:', data);
        applyBtn.disabled = false;
        applyBtn.textContent = originalText;
        
        if (data.success) {
            // Store coupon data
            couponApplied = true;
            appliedCouponData = data.coupon;
            
            // Store in window object for session persistence
            window.appliedCouponFromSession = data.coupon;
            
            // Update UI
            disableCouponInput();
            showAppliedCoupon(data.coupon.code, data.discount_amount);
            
            // Update order totals
            updateOrderTotals(data.discount_amount);
            
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error applying coupon:', error);
        applyBtn.disabled = false;
        applyBtn.textContent = originalText;
        showMessage('Error applying coupon. Please try again.', 'error');
    });
}

/**
 * Remove applied coupon
 */
function removeCoupon() {
    // Show loading state
    const removeBtn = document.getElementById('remove-coupon-btn');
    const originalText = removeBtn.textContent;
    removeBtn.disabled = true;
    removeBtn.textContent = 'Removing...';
    
    // Store discount amount before clearing
    const discountAmount = appliedCouponData ? appliedCouponData.discount_amount : 0;
    
    // Call removal endpoint - use the aggressive clear endpoint
    fetch('remove-coupon.php', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Remove coupon response:', data);
        
        // Always clear client-side state regardless of server response
        couponApplied = false;
        appliedCouponData = null;
        
        // Clear from window object if it exists
        if (window.appliedCouponFromSession) {
            delete window.appliedCouponFromSession;
        }
        
        // Update UI
        enableCouponInput();
        hideAppliedCoupon();
        
        // Update order totals (remove discount)
        updateOrderTotals(0, discountAmount);
        
        removeBtn.disabled = false;
        removeBtn.textContent = originalText;
        
        if (data.success) {
            showMessage('Coupon removed successfully', 'success');
        } else {
            // Even if server says no coupon, we cleared client side
            console.warn('Server response:', data.message);
        }
    })
    .catch(error => {
        console.error('Error removing coupon:', error);
        
        // Still clear client-side even on error
        couponApplied = false;
        appliedCouponData = null;
        if (window.appliedCouponFromSession) {
            delete window.appliedCouponFromSession;
        }
        enableCouponInput();
        hideAppliedCoupon();
        updateOrderTotals(0, discountAmount);
        
        removeBtn.disabled = false;
        removeBtn.textContent = originalText;
        showMessage('Coupon removed (with errors)', 'success');
    });
}

/**
 * Disable coupon input field
 */
function disableCouponInput() {
    console.log('Disabling coupon input');
    const input = document.getElementById('coupon-code');
    const applyBtn = document.getElementById('apply-coupon-btn');
    
    if (input) {
        input.disabled = true;
        input.classList.add('disabled');
        console.log('Input disabled, classes:', input.className);
        
        // Add click listener for tooltip
        input.addEventListener('click', showCouponTooltip);
    }
    
    if (applyBtn) {
        applyBtn.disabled = true;
    }
}

/**
 * Enable coupon input field
 */
function enableCouponInput() {
    console.log('Enabling coupon input');
    const input = document.getElementById('coupon-code');
    const applyBtn = document.getElementById('apply-coupon-btn');
    
    if (input) {
        input.disabled = false;
        input.classList.remove('disabled');
        input.value = '';
        console.log('Input enabled, classes:', input.className);
        
        // Remove click listener
        input.removeEventListener('click', showCouponTooltip);
    }
    
    if (applyBtn) {
        applyBtn.disabled = false;
    }
}

/**
 * Show tooltip when clicking disabled field
 */
function showCouponTooltip(event) {
    const tooltip = document.getElementById('coupon-tooltip');
    
    if (tooltip) {
        tooltip.style.display = 'block';
        
        // Hide after 2 seconds
        setTimeout(() => {
            tooltip.style.display = 'none';
        }, 2000);
    }
}

/**
 * Show applied coupon display
 */
function showAppliedCoupon(code, discount) {
    const display = document.getElementById('applied-coupon-display');
    const text = document.getElementById('applied-coupon-text');
    
    if (display && text) {
        text.textContent = `Coupon "${code}" applied (-₱${parseFloat(discount).toFixed(2)})`;
        display.style.display = 'flex';
    }
}

/**
 * Hide applied coupon display
 */
function hideAppliedCoupon() {
    const display = document.getElementById('applied-coupon-display');
    
    if (display) {
        display.style.display = 'none';
    }
}

/**
 * Update order totals with discount
 * @param {number} discountAmount - New discount amount
 * @param {number} previousDiscount - Previous discount to remove (optional)
 */
function updateOrderTotals(discountAmount, previousDiscount = 0) {
    // Get current subtotal
    const subtotalElement = document.querySelector('.subtotal-amount');
    const totalElement = document.querySelector('.total-amount');
    const discountElement = document.querySelector('.discount-amount');
    
    if (!subtotalElement || !totalElement) {
        console.warn('Could not find total elements to update');
        return;
    }
    
    // Parse current values
    let subtotal = parseFloat(subtotalElement.textContent.replace(/[^0-9.-]+/g, ''));
    let currentTotal = parseFloat(totalElement.textContent.replace(/[^0-9.-]+/g, ''));
    
    // Remove previous discount if any
    if (previousDiscount > 0) {
        currentTotal += previousDiscount;
    }
    
    // Apply new discount
    const newTotal = currentTotal - discountAmount;
    
    // Update discount display
    if (discountElement) {
        if (discountAmount > 0) {
            discountElement.textContent = `-₱${discountAmount.toFixed(2)}`;
            discountElement.parentElement.style.display = 'flex';
        } else {
            discountElement.parentElement.style.display = 'none';
        }
    }
    
    // Update total
    totalElement.textContent = `₱${newTotal.toFixed(2)}`;
}

/**
 * Get cart subtotal
 * Override this function based on your page structure
 */
function getCartSubtotal() {
    const subtotalElement = document.querySelector('.subtotal-amount');
    if (subtotalElement) {
        return parseFloat(subtotalElement.textContent.replace(/[^0-9.-]+/g, ''));
    }
    
    // Fallback: calculate from cart items
    const items = document.querySelectorAll('.cart-item');
    let total = 0;
    items.forEach(item => {
        const price = parseFloat(item.querySelector('.item-price')?.textContent.replace(/[^0-9.-]+/g, '') || 0);
        const quantity = parseInt(item.querySelector('.item-quantity')?.textContent || 1);
        total += price * quantity;
    });
    
    return total;
}

/**
 * Get cart items data
 * Override this function based on your page structure
 */
function getCartItems() {
    // This should return cart items in the format expected by the backend
    // You may need to customize this based on your actual cart structure
    const items = [];
    const cartItemElements = document.querySelectorAll('.cart-item');
    
    cartItemElements.forEach(item => {
        items.push({
            product_id: item.dataset.productId,
            name: item.querySelector('.item-name')?.textContent,
            price: parseFloat(item.querySelector('.item-price')?.textContent.replace(/[^0-9.-]+/g, '') || 0),
            quantity: parseInt(item.querySelector('.item-quantity')?.textContent || 1)
        });
    });
    
    return items;
}

/**
 * Show message to user
 */
function showMessage(message, type = 'info') {
    // You can customize this to use your existing notification system
    // For now, using a simple alert
    if (type === 'error') {
        alert(message);
    } else {
        // You might want to show success messages differently
        console.log(message);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCouponManagement);
} else {
    initCouponManagement();
}
