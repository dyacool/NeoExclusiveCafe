/**
 * ProductListPoller - AJAX polling client for backend product list updates
 * Polls the server every 5 seconds to fetch updated product data
 * Shows visual feedback (loading indicator, highlights, timestamp)
 */
class ProductListPoller {
    constructor(options = {}) {
        // Configuration
        this.pollInterval = options.pollInterval || 5000; // 5 seconds default
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = 2;
        this.maxBackoff = 30000; // 30 seconds max
        this.apiEndpoint = options.apiEndpoint || '../api/get-product-list-admin.php';
        
        // State
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastUpdateTimestamp = null;
        this.currentFilters = {
            search: options.initialSearch || '',
            category: options.initialCategory || null,
            status: options.initialStatus || null,
            page: options.initialPage || 1
        };
        
        // DOM references
        this.productTableBody = null;
        this.loadingIndicator = null;
        this.lastUpdateElement = null;
        
        // Store current product data for comparison
        this.currentProducts = new Map();
        
        // Bind methods
        this.poll = this.poll.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        this.handleBeforeUnload = this.handleBeforeUnload.bind(this);
        this.updateLastUpdateTime = this.updateLastUpdateTime.bind(this);
        
        console.log('[ProductListPoller] Initialized with options:', options);
    }
    
    /**
     * Initialize and start polling
     */
    start() {
        if (this.isPolling) {
            console.warn('[ProductListPoller] Already polling');
            return;
        }
        
        // Get DOM references
        this.productTableBody = document.getElementById('products-tbody');
        this.loadingIndicator = document.getElementById('polling-loading-indicator');
        this.lastUpdateElement = document.getElementById('last-update-time');
        
        if (!this.productTableBody) {
            console.error('[ProductListPoller] Product table body not found');
            return;
        }
        
        // Store initial product data
        this.storeCurrentProducts();
        
        // Set initial timestamp
        this.lastUpdateTimestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');
        
        // Start polling
        this.isPolling = true;
        console.log('[ProductListPoller] Starting polling loop');
        this.schedulePoll();
        
        // Update "last updated" time every second
        if (this.lastUpdateElement) {
            this.lastUpdateInterval = setInterval(this.updateLastUpdateTime, 1000);
        }
        
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
        
        console.log('[ProductListPoller] Stopping polling');
        this.isPolling = false;
        
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
        
        if (this.lastUpdateInterval) {
            clearInterval(this.lastUpdateInterval);
            this.lastUpdateInterval = null;
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
            console.log('[ProductListPoller] Page hidden, skipping poll');
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
        
        // Show loading indicator
        this.showLoading();
        
        try {
            // Build query parameters
            const params = new URLSearchParams({
                since: this.lastUpdateTimestamp,
                page: this.currentFilters.page
            });
            
            // Add optional filters
            if (this.currentFilters.search) {
                params.append('search', this.currentFilters.search);
            }
            if (this.currentFilters.category) {
                params.append('category', this.currentFilters.category);
            }
            if (this.currentFilters.status) {
                params.append('status', this.currentFilters.status);
            }
            
            const url = `${this.apiEndpoint}?${params.toString()}`;
            
            console.log('[ProductListPoller] Polling:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                if (response.status === 401 || response.status === 403) {
                    // Authentication error - stop polling and redirect
                    console.error('[ProductListPoller] Authentication failed');
                    this.stop();
                    window.location.href = '/NeoCafe/frontend/login/admin/admin-login.php';
                    return;
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Unknown error');
            }
            
            console.log('[ProductListPoller] Received data:', data);
            
            // Update timestamp for next poll
            this.lastUpdateTimestamp = data.timestamp;
            this.lastUpdateTime = new Date();
            
            // Update products with visual feedback
            this.updateProductTable(data.products);
            
            // Reset backoff on success
            this.resetBackoff();
            
            // Hide loading indicator
            this.hideLoading();
            
            // Schedule next poll
            this.schedulePoll();
            
        } catch (error) {
            console.error('[ProductListPoller] Poll error:', error);
            this.hideLoading();
            this.handleError(error);
        }
    }
    
    /**
     * Store current product data from DOM
     */
    storeCurrentProducts() {
        const rows = this.productTableBody.querySelectorAll('tr[data-product-id]');
        
        rows.forEach(row => {
            const productId = parseInt(row.dataset.productId);
            const preorderStockEl = row.querySelector('.preorder-stock');
            const samedayStockEl = row.querySelector('.sameday-stock');
            
            this.currentProducts.set(productId, {
                preorderStock: preorderStockEl ? parseInt(preorderStockEl.textContent) || 0 : 0,
                samedayStock: samedayStockEl ? parseInt(samedayStockEl.textContent) || 0 : 0
            });
        });
        
        console.log('[ProductListPoller] Stored', this.currentProducts.size, 'products');
    }
    
    /**
     * Update product table with new data
     */
    updateProductTable(products) {
        if (!this.productTableBody) {
            console.error('[ProductListPoller] Product table body not found');
            return;
        }
        
        // Save current scroll position
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        let updatedCount = 0;
        
        // Update each product
        products.forEach(product => {
            const updated = this.updateProductRow(product);
            if (updated) {
                updatedCount++;
            }
        });
        
        // Restore scroll position
        window.scrollTo(0, scrollTop);
        
        if (updatedCount > 0) {
            console.log('[ProductListPoller] Updated', updatedCount, 'product(s)');
        }
    }
    
    /**
     * Update individual product row
     * Returns true if any changes were made
     */
    updateProductRow(productData) {
        const row = this.productTableBody.querySelector(`tr[data-product-id="${productData.id}"]`);
        
        if (!row) {
            // Product not in current view (might be on different page or filtered out)
            return false;
        }
        
        let hasChanges = false;
        const oldData = this.currentProducts.get(productData.id) || {};
        
        // Update preorder stock
        const preorderStockEl = row.querySelector('.preorder-stock');
        if (preorderStockEl && productData.quantity !== oldData.preorderStock) {
            preorderStockEl.textContent = productData.quantity;
            this.highlightChangedValue(preorderStockEl);
            hasChanges = true;
            console.log(`[ProductListPoller] Product ${productData.id}: Preorder stock ${oldData.preorderStock} → ${productData.quantity}`);
        }
        
        // Update same-day stock
        const samedayStockEl = row.querySelector('.sameday-stock');
        if (samedayStockEl && productData.sameday_stock_today !== oldData.samedayStock) {
            samedayStockEl.textContent = productData.sameday_stock_today;
            this.highlightChangedValue(samedayStockEl);
            hasChanges = true;
            console.log(`[ProductListPoller] Product ${productData.id}: Same-day stock ${oldData.samedayStock} → ${productData.sameday_stock_today}`);
        }
        
        // Update stored data
        if (hasChanges) {
            this.currentProducts.set(productData.id, {
                preorderStock: productData.quantity,
                samedayStock: productData.sameday_stock_today
            });
        }
        
        return hasChanges;
    }
    
    /**
     * Highlight changed value with brief animation
     */
    highlightChangedValue(element) {
        if (!element) return;
        
        // Add highlight class
        element.classList.add('stock-updated');
        
        // Remove highlight after 2 seconds
        setTimeout(() => {
            element.classList.remove('stock-updated');
        }, 2000);
    }
    
    /**
     * Update "Last updated" timestamp display
     */
    updateLastUpdateTime() {
        if (!this.lastUpdateElement || !this.lastUpdateTime) {
            return;
        }
        
        const now = new Date();
        const diffMs = now - this.lastUpdateTime;
        const diffSec = Math.floor(diffMs / 1000);
        
        let timeText;
        if (diffSec < 5) {
            timeText = 'Just now';
        } else if (diffSec < 60) {
            timeText = `${diffSec} seconds ago`;
        } else {
            const diffMin = Math.floor(diffSec / 60);
            timeText = diffMin === 1 ? '1 minute ago' : `${diffMin} minutes ago`;
        }
        
        this.lastUpdateElement.textContent = timeText;
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
            console.warn(`[ProductListPoller] Backing off to ${this.currentBackoff}ms`);
        }
        
        // Schedule next poll with backoff
        this.schedulePoll();
    }
    
    /**
     * Reset backoff to normal interval
     */
    resetBackoff() {
        if (this.currentBackoff !== this.pollInterval) {
            console.log('[ProductListPoller] Resetting backoff to normal interval');
        }
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
    }
    
    /**
     * Update filter state
     */
    updateFilters(filters) {
        console.log('[ProductListPoller] Updating filters:', filters);
        this.currentFilters = { ...this.currentFilters, ...filters };
        
        // Cancel current poll and start fresh
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
        }
        
        // Re-store current products after filter change
        setTimeout(() => {
            this.storeCurrentProducts();
        }, 500);
        
        // Poll immediately with new filters
        this.poll();
    }
    
    /**
     * Show loading indicator
     */
    showLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'flex';
        }
    }
    
    /**
     * Hide loading indicator
     */
    hideLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'none';
        }
    }
    
    /**
     * Handle page visibility changes
     */
    handleVisibilityChange() {
        if (document.hidden) {
            console.log('[ProductListPoller] Page hidden, pausing polling');
        } else {
            console.log('[ProductListPoller] Page visible, resuming polling');
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
    module.exports = ProductListPoller;
}
