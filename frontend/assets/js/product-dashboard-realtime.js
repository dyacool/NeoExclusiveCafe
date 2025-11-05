/**
 * Product Dashboard Realtime Updates
 * Updates product inventory in realtime without page refresh
 */

(function() {
    'use strict';
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductDashboardRealtime);
    } else {
        initProductDashboardRealtime();
    }
    
    function initProductDashboardRealtime() {
        // Only initialize on product dashboard
        if (!document.getElementById('productScroll')) {
            return;
        }
        
        // Delay connection to not block page load
        setTimeout(function() {
            console.log('[Product Dashboard] Connecting...');
            
            const realtimeNotifications = new RealtimeNotifications(['product_inventory']);
        
        realtimeNotifications.on('product_inventory', function(data) {
            console.log('[Product Dashboard] Inventory update:', data);
            updateProductInventory(data);
        });
        
            realtimeNotifications.connect();
            console.log('[Product Dashboard] Connected');
        }, 3000); // Wait 3 seconds for product page
    }
    
    function updateProductInventory(data) {
        const { product_id, quantity, available, product_name } = data;
        
        // Find product card by data attribute
        const productCards = document.querySelectorAll('.product-card');
        let productCard = null;
        
        for (const card of productCards) {
            try {
                const productData = JSON.parse(card.getAttribute('data-product') || '{}');
                if (productData.id == product_id) {
                    productCard = card;
                    break;
                }
            } catch (e) {
                continue;
            }
        }
        
        if (!productCard) {
            console.log('[Product Dashboard] Product card not found for ID:', product_id);
            return;
        }
        
        // Update quantity display if exists
        const quantityElement = productCard.querySelector('.product-quantity, .quantity-display');
        if (quantityElement) {
            quantityElement.textContent = quantity;
        }
        
        // Update availability status
        if (available) {
            productCard.classList.remove('out-of-stock', 'unavailable-product');
            productCard.classList.add('in-stock');
        } else {
            productCard.classList.remove('in-stock');
            productCard.classList.add('out-of-stock', 'unavailable-product');
        }
        
        // Highlight the change
        highlightElement(productCard, 'product-updated', 2000);
        
        // Show toast for significant changes
        if (!available) {
            showRealtimeToast(`${product_name} is now out of stock`, 'warning', 5000);
        } else if (quantity <= 5 && quantity > 0) {
            showRealtimeToast(`${product_name} - Only ${quantity} left!`, 'warning', 5000);
        }
    }
})();
