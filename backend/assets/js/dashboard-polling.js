/**
 * DashboardPoller - AJAX polling client for dashboard real-time updates
 * Polls the server every 5 seconds to check for new orders and refresh stats
 */
class DashboardPoller {
    constructor(options = {}) {
        // Configuration
        this.pollInterval = options.pollInterval || 5000; // 5 seconds default
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = 2;
        this.maxBackoff = 30000; // 30 seconds max
        this.apiEndpoint = options.apiEndpoint || '../../api/get-dashboard-stats.php';
        
        // State
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastUpdateTimestamp = null;
        
        // DOM references
        this.loadingIndicator = null;
        
        // Bind methods
        this.poll = this.poll.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        this.handleBeforeUnload = this.handleBeforeUnload.bind(this);
        
        console.log('[DashboardPoller] Initialized with options:', options);
    }
    
    /**
     * Initialize and start polling
     */
    start() {
        if (this.isPolling) {
            console.warn('[DashboardPoller] Already polling');
            return;
        }
        
        // Get DOM references
        this.loadingIndicator = document.getElementById('dashboard-loading-indicator');
        
        // Set initial timestamp
        this.lastUpdateTimestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');
        
        // Start polling
        this.isPolling = true;
        console.log('[DashboardPoller] Starting polling loop');
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
        
        console.log('[DashboardPoller] Stopping polling');
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
            console.log('[DashboardPoller] Page hidden, skipping poll');
            this.pollTimer = setTimeout(() => this.schedulePoll(), this.pollInterval);
            return;
        }
        
        this.pollTimer = setTimeout(() => {
            this.poll();
        }, this.currentBackoff);
    }
    
    /**
     * Make AJAX request to fetch dashboard updates
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
            
            const url = `${this.apiEndpoint}?${params.toString()}`;
            
            console.log('[DashboardPoller] Polling:', url);
            
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
                    console.error('[DashboardPoller] Authentication failed');
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
            
            console.log('[DashboardPoller] Received data:', data);
            
            // Check if there are new orders
            if (data.has_new_orders || data.has_new_order_flag) {
                console.log('[DashboardPoller] New orders detected! Refreshing dashboard...');
                this.showLoading('New order received! Refreshing...');
                
                // Show loading spinners on all containers
                this.showContainerSpinners();
                
                // Refresh the page to update all stats
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
                
                return; // Stop polling since we're reloading
            }
            
            // Update timestamp for next poll
            this.lastUpdateTimestamp = data.timestamp;
            
            // Reset backoff on success
            this.resetBackoff();
            
            // Schedule next poll
            this.schedulePoll();
            
        } catch (error) {
            console.error('[DashboardPoller] Poll error:', error);
            this.handleError(error);
        }
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
            console.warn(`[DashboardPoller] Backing off to ${this.currentBackoff}ms`);
        }
        
        // Schedule next poll with backoff
        this.schedulePoll();
    }
    
    /**
     * Reset backoff to normal interval
     */
    resetBackoff() {
        if (this.currentBackoff !== this.pollInterval) {
            console.log('[DashboardPoller] Resetting backoff to normal interval');
        }
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
    }
    
    /**
     * Show loading indicator
     */
    showLoading(message = 'Updating...') {
        if (this.loadingIndicator) {
            const textSpan = this.loadingIndicator.querySelector('span');
            if (textSpan) {
                textSpan.textContent = message;
            }
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
     * Show loading spinners on all containers
     */
    showContainerSpinners() {
        // Add 'updating' class to all stat cards
        const statCards = document.querySelectorAll('.service-card[data-stat]');
        statCards.forEach(card => card.classList.add('updating'));
        
        // Add 'updating' class to all chart cards
        const chartCards = document.querySelectorAll('.chart-card[data-container]');
        chartCards.forEach(card => card.classList.add('updating'));
        
        // Add 'updating' class to all table cards
        const tableCards = document.querySelectorAll('.table-card[data-container]');
        tableCards.forEach(card => card.classList.add('updating'));
        
        console.log('[DashboardPoller] Showing loading spinners on all containers');
    }
    
    /**
     * Hide loading spinners on all containers
     */
    hideContainerSpinners() {
        // Remove 'updating' class from all containers
        const allContainers = document.querySelectorAll('.service-card, .chart-card, .table-card');
        allContainers.forEach(container => container.classList.remove('updating'));
        
        console.log('[DashboardPoller] Hiding loading spinners');
    }
    
    /**
     * Handle page visibility changes
     */
    handleVisibilityChange() {
        if (document.hidden) {
            console.log('[DashboardPoller] Page hidden, pausing polling');
        } else {
            console.log('[DashboardPoller] Page visible, resuming polling');
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
    module.exports = DashboardPoller;
}
