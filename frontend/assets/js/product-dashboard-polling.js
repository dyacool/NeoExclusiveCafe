/**
 * ProductDashboardPoller - AJAX polling client for product dashboard updates
 * Polls the server every 5 seconds to fetch updated product data
 * Updates product stock and availability SILENTLY (no loading indicators)
 */
class ProductDashboardPoller {
    constructor(options = {}) {
        // Configuration
        this.pollInterval = options.pollInterval || 5000; // 5 seconds default
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = 2;
        this.maxBackoff = 30000; // 30 seconds max
        this.apiEndpoint = options.apiEndpoint || '/NeoCafe/frontend/api/get-product-list.php';
        
        // State
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastUpdateTimestamp = null;
        this.currentCategory = options.initialCategory || null;
        
        // DOM references
        this.productsContainer = null;
        
        // Store current product data for comparison
        this.currentProducts = new Map();
        
        // Bind methods
        this.poll = this.poll.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        this.handleBeforeUnload = this.handleBeforeUnload.bind(this);
        
        console.log('[ProductDashboardPoller] Initialized with options:', options);
    }
    
    /**
     * Initialize and start polling
     */
    start() {
        if (this.isPolling) {
            console.warn('[ProductDashboardPoller] Already polling');
            return;
        }
        
        // Get DOM references
        this.productsContainer = document.getElementById('productScroll');
        
        if (!this.productsContainer) {
            console.error('[ProductDashboardPoller] Products container not found');
            return;
        }
        
        // Store initial product data
        this.storeCurrentProducts();
        
        // Set initial timestamp
        this.lastUpdateTimestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');
        
        // Start polling
        this.isPolling = true;
        console.log('[ProductDashboardPoller] Starting polling loop');
        this.schedulePoll();
        
        // Listen for page visibility changes
        document.addEventListener('visibilitychange', this.handleVisibilityChange);
        
        // Listen for page unload
        window.addEventListener('beforeunload', this.handleBeforeUnload);
    }
    
    /**
     * Stop polling and cleanup
     */
    stop() {
        if (!this.isPolling) {
            return;
        }
        
        console.log('[ProductDashboardPoller] Stopping polling');
        this.isPolling = false;
        
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
        
        // Remove event listeners
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        window.removeEventListener('beforeunload', this.handleBeforeUnload);
    }
    
    /**
     * Schedule next poll
     */
    schedulePoll() {
        if (!this.isPolling) {
            return;
        }
        
        // Don't poll if page is hidden
        if (document.hidden) {
            console.log('[ProductDashboardPoller] Page hidden, skipping poll');
            this.pollTimer = setTimeout(() => this.schedulePoll(), this.pollInterval);
            return;
        }
        
        this.pollTimer = setTimeout(() => {
            this.poll();
        }, this.currentBackoff);
    }
    
    /**
     * Make AJAX request to fetch product updates
     */
    async poll() {
        if (!this.isPolling) {
            return;
        }
        
        try {
            // Build query parameters
            const params = new URLSearchParams({
                since: this.lastUpdateTimestamp
            });
            
            // Add category filter if set
            if (this.currentCategory) {
                params.append('category', this.currentCategory);
            }
            
            const url = `${this.apiEndpoint}?${params.toString()}`;
            
            console.log('[ProductDashboardPoller] Polling:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Unknown error');
            }
            
            console.log('[ProductDashboardPoller] Received data:', data);
            
            // Update timestamp for next poll
            this.lastUpdateTimestamp = data.timestamp;
            
            // Update products silently
            this.updateProducts(data.products);
            
            // Reset backoff on success
            this.resetBackoff();
            
            // Schedule next poll
            this.schedulePoll();
            
        } catch (error) {
            console.error('[ProductDashboardPoller] Poll error:', error);
            this.handleError(error);
        }
    }
    
    /**
     * Store current product data from DOM
     */
    storeCurrentProducts() {
        const productCards = this.productsContainer.querySelectorAll('.product-card[data-product-id]');
        
        productCards.forEach(card => {
            const productId = parseInt(card.dataset.productId);
            const stockEl = card.querySelector('.stock');
            const statusEl = card.querySelector('.status-badge');
            const addToCartBtn = card.querySelector('.add-to-cart');
            
            this.currentProducts.set(productId, {
                quantity: stockEl ? this.extractQuantity(stockEl.textContent) : 0,
                isAvailable: addToCartBtn ? !addToCartBtn.disabled : true,
                statusText: statusEl ? statusEl.textContent.trim() : ''
            });
        });
        
        console.log('[ProductDashboardPoller] Stored', this.currentProducts.size, 'products');
    }
    
    /**
     * Extract quantity number from text like "Stock: 45"
     */
    extractQuantity(text) {
        const match = text.match(/\d+/);
        return match ? parseInt(match[0]) : 0;
    }
    
    /**
     * Update products silently (no loading indicators)
     */
    updateProducts(products) {
        if (!this.productsContainer) {
            console.error('[ProductDashboardPoller] Products container not found');
            return;
        }
        
        // Save current scroll position
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        let updatedCount = 0;
        
        // Update each product
        products.forEach(product => {
            const updated = this.updateProductCard(product);
            if (updated) {
                updatedCount++;
            }
        });
        
        // Restore scroll position
        window.scrollTo(0, scrollTop);
        
        if (updatedCount > 0) {
            console.log('[ProductDashboardPoller] Updated', updatedCount, 'product(s) silently');
        }
    }
    
    /**
     * Update individual product card
     * Returns true if any changes were made
     */
    updateProductCard(productData) {
        const card = this.productsContainer.querySelector(`[data-product-id="${productData.id}"]`);
        
        if (!card) {
            // Product not in current view (might be filtered out)
            return false;
        }
        
        let hasChanges = false;
        const oldData = this.currentProducts.get(productData.id) || {};
        
        // Update stock quantity
        const stockEl = card.querySelector('.stock');
        if (stockEl && productData.quantity !== oldData.quantity) {
            stockEl.textContent = `Stock: ${productData.quantity}`;
            hasChanges = true;
            console.log(`[ProductDashboardPoller] Product ${productData.id}: Stock ${oldData.quantity} → ${productData.quantity}`);
        }
        
        // Update availability status
        const statusEl = card.querySelector('.status-badge');
        const newStatusText = productData.is_available ? 'Available' : productData.unavailable_reason || 'Unavailable';
        
        if (statusEl && newStatusText !== oldData.statusText) {
            statusEl.textContent = newStatusText;
            
            // Update status badge classes
            statusEl.classList.remove('status-available', 'status-unavailable');
            if (productData.is_available) {
                statusEl.classList.add('status-available');
            } else {
                statusEl.classList.add('status-unavailable');
            }
            
            hasChanges = true;
            console.log(`[ProductDashboardPoller] Product ${productData.id}: Status "${oldData.statusText}" → "${newStatusText}"`);
        }
        
        // Update "Add to Cart" button state
        const addToCartBtn = card.querySelector('.add-to-cart');
        if (addToCartBtn) {
            const shouldBeDisabled = !productData.is_available;
            
            if (addToCartBtn.disabled !== shouldBeDisabled) {
                addToCartBtn.disabled = shouldBeDisabled;
                
                if (shouldBeDisabled) {
                    addToCartBtn.classList.add('unavailable');
                    addToCartBtn.textContent = 'Unavailable';
                } else {
                    addToCartBtn.classList.remove('unavailable');
                    addToCartBtn.textContent = 'Add to Cart';
                }
                
                hasChanges = true;
                console.log(`[ProductDashboardPoller] Product ${productData.id}: Button ${shouldBeDisabled ? 'disabled' : 'enabled'}`);
            }
        }
        
        // Update stored data
        if (hasChanges) {
            this.currentProducts.set(productData.id, {
                quantity: productData.quantity,
                isAvailable: productData.is_available,
                statusText: newStatusText
            });
        }
        
        return hasChanges;
    }
    
    /**
     * Handle polling errors with exponential backoff
     */
    handleError(error) {
        this.retryCount++;
        
        if (this.retryCount >= this.maxRetries) {
            // Implement exponential backoff
            this.currentBackoff = Math.min(
                this.currentBackoff * this.backoffMultiplier,
                this.maxBackoff
            );
            console.warn(`[ProductDashboardPoller] Backing off to ${this.currentBackoff}ms`);
        }
        
        // Schedule next poll with backoff
        this.schedulePoll();
    }
    
    /**
     * Reset backoff to normal interval
     */
    resetBackoff() {
        if (this.currentBackoff !== this.pollInterval) {
            console.log('[ProductDashboardPoller] Resetting backoff to normal interval');
        }
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
    }
    
    /**
     * Update category filter
     */
    updateCategory(category) {
        console.log('[ProductDashboardPoller] Updating category:', category);
        this.currentCategory = category;
        
        // Re-store current products after category change
        setTimeout(() => {
            this.storeCurrentProducts();
        }, 500);
    }
    
    /**
     * Handle page visibility changes
     */
    handleVisibilityChange() {
        if (document.hidden) {
            console.log('[ProductDashboardPoller] Page hidden, pausing polling');
        } else {
            console.log('[ProductDashboardPoller] Page visible, resuming polling');
            // Poll immediately when page becomes visible
            if (this.isPolling && !this.pollTimer) {
                this.poll();
            }
        }
    }
    
    /**
     * Handle page unload
     */
    handleBeforeUnload() {
        this.stop();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductDashboardPoller;
}
