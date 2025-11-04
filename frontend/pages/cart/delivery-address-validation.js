/**
 * Delivery Address Validation
 * Makes delivery address required only when delivery method is selected
 */

(function() {
    'use strict';
    
    /**
     * Initialize delivery address validation
     */
    function initDeliveryAddressValidation() {
        console.log('Initializing delivery address validation');
        
        // Get form and delivery method radio buttons
        const form = document.querySelector('form[action*="process"]');
        const deliveryRadio = document.querySelector('input[name="shipping_method"][value="delivery"]');
        const pickupRadio = document.querySelector('input[name="shipping_method"][value="pickup"]');
        
        // Get delivery address field (try multiple possible selectors)
        const deliveryAddressField = document.getElementById('delivery_address') || 
                                     document.querySelector('input[name="delivery_address"]') ||
                                     document.querySelector('input[name="address"]');
        
        if (!deliveryAddressField) {
            console.warn('Delivery address field not found');
            return;
        }
        
        console.log('Delivery address field found:', deliveryAddressField);
        
        /**
         * Update required status based on shipping method
         */
        function updateAddressRequired() {
            const isDelivery = deliveryRadio && deliveryRadio.checked;
            
            if (isDelivery) {
                deliveryAddressField.setAttribute('required', 'required');
                console.log('Delivery address set to REQUIRED');
                
                // Add visual indicator
                const label = document.querySelector('label[for="delivery_address"]') ||
                             deliveryAddressField.closest('.form-group')?.querySelector('label');
                if (label && !label.textContent.includes('*')) {
                    label.innerHTML += ' <span style="color: red;">*</span>';
                }
            } else {
                deliveryAddressField.removeAttribute('required');
                console.log('Delivery address set to OPTIONAL');
                
                // Remove visual indicator
                const label = document.querySelector('label[for="delivery_address"]') ||
                             deliveryAddressField.closest('.form-group')?.querySelector('label');
                if (label) {
                    label.innerHTML = label.innerHTML.replace(/ <span style="color: red;">\*<\/span>/, '');
                }
            }
        }
        
        // Listen for shipping method changes
        if (deliveryRadio) {
            deliveryRadio.addEventListener('change', updateAddressRequired);
        }
        if (pickupRadio) {
            pickupRadio.addEventListener('change', updateAddressRequired);
        }
        
        // Set initial state
        updateAddressRequired();
        
        // Add form validation
        if (form) {
            form.addEventListener('submit', function(e) {
                const isDelivery = deliveryRadio && deliveryRadio.checked;
                const addressValue = deliveryAddressField.value.trim();
                
                if (isDelivery && !addressValue) {
                    e.preventDefault();
                    alert('Please set your delivery location before placing the order.');
                    
                    // Scroll to the address field
                    deliveryAddressField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    deliveryAddressField.focus();
                    
                    // Add error styling
                    deliveryAddressField.style.borderColor = 'red';
                    setTimeout(() => {
                        deliveryAddressField.style.borderColor = '';
                    }, 3000);
                    
                    return false;
                }
            });
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDeliveryAddressValidation);
    } else {
        initDeliveryAddressValidation();
    }
})();
