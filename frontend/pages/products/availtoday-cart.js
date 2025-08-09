/**
 * Available Today Cart Management
 * Handles cart functionality specifically for Available Today products (status_id = 3)
 */

// Available Today Cart State
let availableTodayCart = [];
let availableTodayCartTotal = 0;

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
    
    // Verify this is an Available Today product
    const statusAttribute = productCard.getAttribute('data-status');
    const statusElement = productCard.querySelector('.status-badge');
    const isAvailableToday = (statusAttribute === 'Available Today') || 
                             (statusElement && statusElement.classList.contains('status-available-today'));
    
    if (!isAvailableToday) {
        console.log('Product is not Available Today, skipping cart addition');
        return;
    }
    
    // Add to database via API
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('../../../backend/pages/cart/availtoday-cart-api.php', {
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
function updateLocalCart(productId, quantity, productCard) {
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
}

/**
 * Update the Available Today cart display
 */
function updateAvailableTodayCartDisplay() {
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
}

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
        
        fetch('../../../backend/pages/cart/availtoday-cart-api.php', {
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
    if (availableTodayCart.length === 0) {
        showNotification('Your Available Today cart is empty', 'error');
        return;
    }
    
    console.log('Processing Available Today checkout:', availableTodayCart);
    
    // Here you can implement the checkout logic
    // For now, we'll show a confirmation
    const confirmCheckout = confirm(`Checkout ${availableTodayCart.length} Available Today items for ₱${availableTodayCartTotal.toFixed(2)}?`);
    
    if (confirmCheckout) {
        // Redirect to checkout page or process the order
        // You can modify this to match your checkout flow
        window.location.href = '../../pages/cart/cart.php';
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
function syncWithServer() {
    fetch('../../../backend/pages/cart/availtoday-cart-api.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update local cart with server data
                availableTodayCart = data.cart_items.map(item => ({
                    id: item.product_id,
                    name: item.product_name,
                    price: item.price,
                    quantity: item.quantity
                }));
                
                availableTodayCartTotal = data.total;
                
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
}

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
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initAvailableTodayCart();
    
    // Try to sync with server first, fallback to localStorage
    syncWithServer();
    
    // Load from localStorage as backup
    setTimeout(() => {
        if (availableTodayCart.length === 0) {
            loadAvailableTodayCartFromStorage();
        }
    }, 1000);
});
