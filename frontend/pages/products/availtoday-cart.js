/**
 * Available Today Cart Management
 * Handles cart functionality specifically for Available Today products (status_id = 3)
 */

// Checkout functions
window.closeCheckoutConfirmModal = function() {
    console.log('Modal closed');
    document.getElementById('checkoutConfirmModal').style.display = 'none';
};

window.confirmCheckout = function() {
    console.log('Checkout confirmed, redirecting...');
    document.getElementById('checkoutConfirmModal').style.display = 'none';
    window.location.href = '../cart/availtoday-checkout.php';
};

// Attach event listener directly to button on load
document.addEventListener('DOMContentLoaded', function() {
    const proceedBtn = document.getElementById('proceedCheckoutBtn');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Button clicked via event listener!');
            console.log('Attempting redirect to checkout...');
            
            // Close modal
            const modal = document.getElementById('checkoutConfirmModal');
            if (modal) modal.style.display = 'none';
            
            // Build absolute URL
            const currentPath = window.location.pathname;
            console.log('Current path:', currentPath);
            
            // Get the base path (remove product-dashboard.php)
            const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
            const checkoutPath = basePath.replace('/products', '/cart') + '/availtoday-checkout.php';
            
            console.log('Calculated checkout path:', checkoutPath);
            console.log('Full URL will be:', window.location.origin + checkoutPath);
            
            // Force navigation
            window.location.assign(checkoutPath);
        }, true);
        console.log('Event listener attached to proceed button');
    }
});

// Available Today Cart State
let availableTodayCart = [];
let availableTodayCartTotal = 0;

/**
 * Check business hours and clear cart if closed
 */
function checkBusinessHoursAndClearCart() {
    fetch('get-business-hours.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.businessHours) {
                const now = new Date();
                const currentTime = now.getHours() * 60 + now.getMinutes(); // Convert to minutes
                
                // Parse business hours
                const [openingHour, openingMinute] = data.businessHours.opening_time.split(':').map(Number);
                const [closingHour, closingMinute] = data.businessHours.closing_time.split(':').map(Number);
                
                const openingTime = openingHour * 60 + openingMinute;
                const closingTime = closingHour * 60 + closingMinute;
                
                // Check if current time is after closing time
                if (currentTime > closingTime) {
                    console.log('Business hours closed, clearing Available Today cart');
                    clearAvailableTodayCart();
                    showNotification('Business hours closed. Cart has been cleared.', 'info');
                    
                    // Disable add to cart buttons
                    disableAddToCartButtons();
                    
                    // Truncate the cart_availtoday table
                    truncateCartAvailToday();
                } else if (currentTime < openingTime) {
                    // Business not yet open
                    disableAddToCartButtons();
                } else {
                    // Business is open
                    enableAddToCartButtons();
                }
            }
        })
        .catch(error => {
            console.error('Error checking business hours:', error);
        });
}

/**
 * Truncate the cart_availtoday table when business hours are closed
 */
function truncateCartAvailToday() {
    fetch('truncate-cart-availtoday.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Cart truncated successfully:', data.message);
                if (data.action === 'truncated') {
                    showNotification('Cart has been cleared for the day.', 'info');
                }
            } else {
                console.error('Failed to truncate cart:', data.error);
            }
        })
        .catch(error => {
            console.error('Error truncating cart:', error);
        });
}

/**
 * Check if business hours are currently open
 * @returns {Promise<boolean>} True if open, false if closed
 */
function isBusinessOpen() {
    return fetch('get-business-hours.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.businessHours) {
                const now = new Date();
                const currentTime = now.getHours() * 60 + now.getMinutes();
                
                const [openingHour, openingMinute] = data.businessHours.opening_time.split(':').map(Number);
                const [closingHour, closingMinute] = data.businessHours.closing_time.split(':').map(Number);
                
                const openingTime = openingHour * 60 + openingMinute;
                const closingTime = closingHour * 60 + closingMinute;
                
                return currentTime >= openingTime && currentTime <= closingTime;
            }
            return false;
        })
        .catch(error => {
            console.error('Error checking business hours:', error);
            return false;
        });
}

/**
 * Disable all add to cart buttons
 */
function disableAddToCartButtons() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.disabled = true;
        button.textContent = 'Closed';
        button.classList.add('closed');
    });
}

/**
 * Enable all add to cart buttons
 */
function enableAddToCartButtons() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.disabled = false;
        button.textContent = 'Add to Cart';
        button.classList.remove('closed');
    });
}

/**
 * Clear the Available Today cart
 */
function clearAvailableTodayCart() {
    // Clear local cart
    availableTodayCart = [];
    availableTodayCartTotal = 0;
    
    // Clear localStorage
    localStorage.removeItem('availableTodayCart');
    localStorage.removeItem('availableTodayCartTotal');
    
    // Update display
    updateAvailableTodayCartDisplay();
    
    // Clear server cart
    fetch('availtoday-cart-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Server cart cleared successfully');
        } else {
            console.error('Failed to clear server cart:', data.error);
        }
    })
    .catch(error => {
        console.error('Error clearing server cart:', error);
    });
}

/**
 * Initialize Available Today Cart
 */
function initAvailableTodayCart() {
    console.log('Available Today Cart initialized');
    updateAvailableTodayCartDisplay();
    
    // Add event listener for checkout button
    const checkoutBtn = document.getElementById('availableTodayCheckoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', handleAvailableTodayCheckout);
    }
    
    // Check business hours and clear cart if closed
    checkBusinessHoursAndClearCart();
    
    // Set up periodic checking of business hours
    setInterval(checkBusinessHoursAndClearCart, 60000); // Check every minute
}

/**
 * Add product to Available Today cart
 * @param {number} productId - Product ID
 * @param {number} quantity - Quantity to add
 * @param {HTMLElement} button - Button element that was clicked
 */
function addToAvailableTodayCart(productId, quantity, button) {
    // Find the product card to get product details
    const productCard = button ? button.closest('.product-card') : null;
    if (!productCard) {
        console.log('Product card not found (likely called from modal), using API-only approach');
        
        // When called from modal, we don't have product card details
        // The API call will handle the cart addition, just return
        return;
    }
    
    // Verify this is an Available Today product (from product-dashboard.php)
    // Products on the Available Today page have availtoday-badge, not a specific status
    const hasAvailTodayBadge = productCard.querySelector('.availtoday-badge');
    const isOnAvailTodayPage = window.location.pathname.includes('product-dashboard');
    
    // Only allow adding to cart if on the Available Today page OR product has availtoday badge
    if (!isOnAvailTodayPage && !hasAvailTodayBadge) {
        console.log('Product is not Available Today, skipping cart addition');
        showNotification('Only Available Today products can be added to this cart', 'error');
        return;
    }
    
    // Add to database via API
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('availtoday-cart-api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(`Added product to Available Today cart: ${data.product_name}`);
            
            // Also update local cart for immediate UI feedback
            updateLocalCart(productId, quantity, productCard);
            updateAvailableTodayCartDisplay();
            
            // Optionally sync with server
            syncWithServer();
        } else {
            console.error('Failed to add to Available Today cart:', data.error);
            showNotification(`Error: ${data.error}`, 'error');
        }
    })
    .catch(error => {
        console.error('API Error:', error);
        
        // Fallback to local storage if API fails
        updateLocalCart(productId, quantity, productCard);
        updateAvailableTodayCartDisplay();
        saveAvailableTodayCartToStorage();
        
        showNotification('Added to cart (offline mode)', 'info');
    });
}

/**
 * Update local cart array for immediate UI feedback
 * @param {number} productId - Product ID
 * @param {number} quantity - Quantity to add
 * @param {HTMLElement} productCard - Product card element
 */
window.updateLocalCart = function(productId, quantity, productCard) {
    // Extract product information
    const productName = productCard.querySelector('h3').textContent;
    const priceText = productCard.querySelector('.price').textContent;
    const productPrice = parseFloat(priceText.replace('₱', '').replace(',', ''));
    
    // Check if product already exists in Available Today cart
    const existingItem = availableTodayCart.find(item => item.id == productId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
        console.log(`Updated local quantity for product ${productId}: ${existingItem.quantity}`);
    } else {
        availableTodayCart.push({
            id: productId,
            name: productName,
            price: productPrice,
            quantity: quantity
        });
        console.log(`Added new product to local cart: ${productName}`);
    }
};

/**
 * Update the Available Today cart display
 */
window.updateAvailableTodayCartDisplay = function() {
    const cartCount = document.getElementById('availableTodayCartCount');
    const cartItems = document.getElementById('availableTodayCartItems');
    const cartTotal = document.getElementById('availableTodayCartTotal');
    const checkoutBtn = document.getElementById('availableTodayCheckoutBtn');
    
    if (!cartCount || !cartItems || !cartTotal || !checkoutBtn) {
        console.error('Available Today cart elements not found');
        return;
    }
    
    // Update cart count
    const totalItems = availableTodayCart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    // Update cart total
    availableTodayCartTotal = availableTodayCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    cartTotal.textContent = `Total: ₱${availableTodayCartTotal.toFixed(2)}`;
    
    // Update cart items display
    if (availableTodayCart.length === 0) {
        cartItems.innerHTML = '<p class="empty-cart">No items in cart</p>';
        checkoutBtn.disabled = true;
    } else {
        cartItems.innerHTML = '';
        availableTodayCart.forEach(item => {
            const cartItemDiv = document.createElement('div');
            cartItemDiv.className = 'cart-item';
            cartItemDiv.innerHTML = `
                <div class="cart-item-details">
                    <div class="cart-item-name">${escapeHtml(item.name)}</div>
                    <div class="cart-item-price">₱${item.price.toFixed(2)}</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="qty-btn" onclick="updateAvailableTodayCartQuantity(${item.id}, -1)" title="Decrease quantity">-</button>
                    <span>${item.quantity}</span>
                    <button class="qty-btn" onclick="updateAvailableTodayCartQuantity(${item.id}, 1)" title="Increase quantity">+</button>
                </div>
            `;
            cartItems.appendChild(cartItemDiv);
        });
        checkoutBtn.disabled = false;
    }
};

/**
 * Update quantity of a specific product in Available Today cart
 * @param {number} productId - Product ID
 * @param {number} change - Change in quantity (+1 or -1)
 */
function updateAvailableTodayCartQuantity(productId, change) {
    const item = availableTodayCart.find(item => item.id == productId);
    if (item) {
        const newQuantity = item.quantity + change;
        
        // Update via API
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', newQuantity);
        
        fetch('availtoday-cart-api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update local cart
                if (newQuantity <= 0) {
                    availableTodayCart = availableTodayCart.filter(item => item.id != productId);
                    console.log(`Removed product ${productId} from Available Today cart`);
                } else {
                    item.quantity = newQuantity;
                    console.log(`Updated quantity for product ${productId}: ${newQuantity}`);
                }
                
                updateAvailableTodayCartDisplay();
            } else {
                console.error('Failed to update cart quantity:', data.error);
                showNotification(`Error: ${data.error}`, 'error');
            }
        })
        .catch(error => {
            console.error('API Error:', error);
            
            // Fallback to local update
            if (newQuantity <= 0) {
                availableTodayCart = availableTodayCart.filter(item => item.id != productId);
            } else {
                item.quantity = newQuantity;
            }
            
            updateAvailableTodayCartDisplay();
            saveAvailableTodayCartToStorage();
        });
    }
}

/**
 * Remove a specific product from Available Today cart
 * @param {number} productId - Product ID to remove
 */
function removeFromAvailableTodayCart(productId) {
    availableTodayCart = availableTodayCart.filter(item => item.id != productId);
    console.log(`Removed product ${productId} from Available Today cart`);
    updateAvailableTodayCartDisplay();
    saveAvailableTodayCartToStorage();
}

/**
 * Clear all items from Available Today cart
 */
function clearAvailableTodayCart() {
    availableTodayCart = [];
    availableTodayCartTotal = 0;
    console.log('Cleared Available Today cart');
    updateAvailableTodayCartDisplay();
    saveAvailableTodayCartToStorage();
}

/**
 * Handle checkout for Available Today cart
 */
function handleAvailableTodayCheckout() {
    console.log('Starting checkout...');
    
    if (availableTodayCart.length === 0) {
        alert('Your cart is empty');
        return;
    }
    
    // Show modal
    const modal = document.getElementById('checkoutConfirmModal');
    if (modal) {
        document.getElementById('confirmItemCount').textContent = availableTodayCart.length;
        document.getElementById('confirmTotal').textContent = availableTodayCartTotal.toFixed(2);
        modal.style.display = 'block';
        console.log('Modal shown');
    } else {
        console.error('ERROR: Modal not found!');
    }
}

/**
 * Save Available Today cart to localStorage
 */
function saveAvailableTodayCartToStorage() {
    try {
        localStorage.setItem('availableTodayCart', JSON.stringify(availableTodayCart));
        localStorage.setItem('availableTodayCartTotal', availableTodayCartTotal.toString());
    } catch (error) {
        console.error('Error saving Available Today cart to storage:', error);
    }
}

/**
 * Load Available Today cart from localStorage
 */
function loadAvailableTodayCartFromStorage() {
    try {
        const savedCart = localStorage.getItem('availableTodayCart');
        const savedTotal = localStorage.getItem('availableTodayCartTotal');
        
        if (savedCart) {
            availableTodayCart = JSON.parse(savedCart);
        }
        
        if (savedTotal) {
            availableTodayCartTotal = parseFloat(savedTotal);
        }
        
        console.log('Loaded Available Today cart from storage:', availableTodayCart);
        updateAvailableTodayCartDisplay();
    } catch (error) {
        console.error('Error loading Available Today cart from storage:', error);
    }
}

/**
 * Sync cart with server data
 */
window.syncWithServer = function() {
    fetch('availtoday-cart-api.php?action=get', {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update local cart with server data
                availableTodayCart = data.cart_items.map(item => ({
                    id: item.product_id,
                    name: item.name || '',
                    price: parseFloat(item.price) || 0,
                    quantity: parseInt(item.quantity) || 0,
                    image_url: item.image_url || ''
                }));
                
                // Calculate total
                availableTodayCartTotal = availableTodayCart.reduce((sum, item) => {
                    return sum + (item.price * item.quantity);
                }, 0);
                
                console.log('Synced with server cart:', availableTodayCart);
                updateAvailableTodayCartDisplay();
                saveAvailableTodayCartToStorage();
            } else {
                console.error('Failed to sync with server:', data.error);
            }
        })
        .catch(error => {
            console.error('Sync error:', error);
        });
};

/**
 * Get Available Today cart summary
 * @returns {Object} Cart summary with items, total, and count
 */
function getAvailableTodayCartSummary() {
    return {
        items: availableTodayCart,
        total: availableTodayCartTotal,
        count: availableTodayCart.reduce((sum, item) => sum + item.quantity, 0)
    };
}

/**
 * Utility function to escape HTML
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Show notification (placeholder - should match your notification system)
 * @param {string} message - Message to show
 * @param {string} type - Type of notification ('success', 'error', 'info')
 */
function showNotification(message, type = 'info') {
    // This should match your existing notification system
    if (typeof showConfirmation === 'function') {
        showConfirmation(message, type === 'error');
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
        alert(message);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initAvailableTodayCart();
    checkBusinessHoursAndClearCart();
    syncWithServer();
    
    setTimeout(() => {
        if (availableTodayCart.length === 0) {
            loadAvailableTodayCartFromStorage();
        }
    }, 1000);
});
