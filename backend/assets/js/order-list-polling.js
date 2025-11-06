/**
 * OrderListPoller - AJAX polling client for order list updates
 * Polls the server every 5 seconds to fetch updated order data
 */
class OrderListPoller {
    constructor(options = {}) {
        // Configuration
        this.pollInterval = options.pollInterval || 5000; // 5 seconds default
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = 2;
        this.maxBackoff = 30000; // 30 seconds max
        this.apiEndpoint = options.apiEndpoint || '/NeoCafe/backend/api/get-order-list.php';
        
        // State
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastUpdateTimestamp = null;
        this.currentFilters = {
            status: options.initialStatus || 'all',
            search: options.initialSearch || '',
            page: options.initialPage || 1
        };
        
        // DOM references
        this.ordersContainer = null;
        this.loadingIndicator = null;
        
        // Bind methods
        this.poll = this.poll.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        this.handleBeforeUnload = this.handleBeforeUnload.bind(this);
        
        console.log('[OrderListPoller] Initialized with options:', options);
    }
    
    /**
     * Initialize and start polling
     */
    start() {
        if (this.isPolling) {
            console.warn('[OrderListPoller] Already polling');
            return;
        }
        
        // Get DOM references
        this.ordersContainer = document.querySelector('.orders-container');
        this.loadingIndicator = document.getElementById('polling-loading-indicator');
        
        if (!this.ordersContainer) {
            console.error('[OrderListPoller] Orders container not found');
            return;
        }
        
        // Set initial timestamp
        this.lastUpdateTimestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');
        
        // Start polling
        this.isPolling = true;
        console.log('[OrderListPoller] Starting polling loop');
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
        
        console.log('[OrderListPoller] Stopping polling');
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
            console.log('[OrderListPoller] Page hidden, skipping poll');
            this.pollTimer = setTimeout(() => this.schedulePoll(), this.pollInterval);
            return;
        }
        
        this.pollTimer = setTimeout(() => {
            this.poll();
        }, this.currentBackoff);
    }
    
    /**
     * Make AJAX request to fetch order updates
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
                status: this.currentFilters.status,
                search: this.currentFilters.search,
                page: this.currentFilters.page,
                since: this.lastUpdateTimestamp
            });
            
            const url = `${this.apiEndpoint}?${params.toString()}`;
            
            console.log('[OrderListPoller] Polling:', url);
            
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
                    console.error('[OrderListPoller] Authentication failed');
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
            
            console.log('[OrderListPoller] Received data:', data);
            
            // Update timestamp for next poll
            this.lastUpdateTimestamp = data.timestamp;
            
            // Update the orders container
            this.updateOrdersContainer(data);
            
            // Reset backoff on success
            this.resetBackoff();
            
            // Hide loading indicator
            this.hideLoading();
            
            // Check for new order flag - if present, poll immediately
            if (data.has_new_order_flag) {
                console.log('[OrderListPoller] New order flag detected! Polling immediately...');
                // Clear old flags from database
                this.clearOldFlags();
                // Poll again after short delay
                setTimeout(() => this.poll(), 500);
                return;
            }
            
            // Schedule next poll
            this.schedulePoll();
            
        } catch (error) {
            console.error('[OrderListPoller] Poll error:', error);
            this.handleError(error);
        }
    }
    
    /**
     * Update the orders container with new data
     */
    updateOrdersContainer(data) {
        if (!this.ordersContainer) {
            console.error('[OrderListPoller] Orders container not found');
            return;
        }
        
        // Save current scroll position
        const scrollTop = this.ordersContainer.scrollTop;
        
        // Update status count badges
        this.updateStatusCounts(data.status_counts);
        
        // Get the table body
        const tbody = document.getElementById('orders-tbody');
        if (!tbody) {
            console.error('[OrderListPoller] Table body not found');
            return;
        }
        
        // Build new table rows
        const newRows = this.buildOrderRows(data.orders);
        
        // Replace tbody content
        tbody.innerHTML = newRows;
        
        // Restore scroll position
        this.ordersContainer.scrollTop = scrollTop;
        
        // Update pagination if needed
        this.updatePagination(data.total_pages, data.current_page);
        
        console.log('[OrderListPoller] Updated orders container with', data.orders.length, 'orders');
    }
    
    /**
     * Build HTML for order table rows
     */
    buildOrderRows(orders) {
        if (!orders || orders.length === 0) {
            return `
                <tr>
                    <td colspan="10" class="no-orders">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <h3>No orders found</h3>
                            <p>No orders match your current filters</p>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        return orders.map(order => {
            const rowClass = order.is_new ? 'order-row-new' : '';
            const date = order.delivery_date || order.pickup_date;
            const time = order.delivery_time || order.pickup_time || '00:00:00';
            
            // Format date and time
            let dateTimeDisplay = 'Not specified';
            if (date && date !== '0000-00-00') {
                const dateObj = new Date(date);
                dateTimeDisplay = this.formatDate(dateObj);
                if (order.delivery_time && order.delivery_time !== '00:00:00') {
                    dateTimeDisplay += ' at ' + this.formatTime(order.delivery_time);
                }
            }
            
            // Calculate warning badges
            const warningBadge = this.calculateWarningBadge(order, date, time);
            
            // Determine status options based on delivery method
            const statusOptions = order.delivery_method === 'Pick-up' 
                ? ['Pending', 'Preparing', 'Ready for Pick-up', 'Picked-up']
                : ['Pending', 'Preparing', 'Ready for Delivery', 'Out for Delivery', 'Delivered'];
            
            const statusOptionsHtml = statusOptions.map(status => {
                const value = status === 'Pending' ? 'Confirmed' : status;
                const selected = order.status === status ? 'selected' : '';
                return `<option value="${value}" ${selected}>${status}</option>`;
            }).join('');
            
            const statusClass = order.db_status ? order.db_status.toLowerCase().replace(/ /g, '-') : '';
            
            return `
                <tr class="${rowClass}" onclick="window.location.href='view-orders.php?order_id=${order.order_id}'">
                    <td data-label="Order #">${this.escapeHtml(order.order_id)}</td>
                    <td data-label="Date Placed">${this.formatDate(new Date(order.order_date))}</td>
                    <td data-label="Customer">${this.escapeHtml(order.customer_name)}</td>
                    <td data-label="Contact">${this.escapeHtml(order.customer_contact)}</td>
                    <td data-label="Items">${order.total_items}</td>
                    <td data-label="Total">₱${this.formatCurrency(order.total_amount)}</td>
                    <td data-label="Payment">${this.escapeHtml(order.payment_method)}</td>
                    <td data-label="Delivery/Pickup">
                        <div class="delivery-info-wrapper">
                            <span class="delivery-method">${this.escapeHtml(order.delivery_method)}</span>
                            <span class="delivery-datetime">${dateTimeDisplay}</span>
                            ${warningBadge}
                        </div>
                    </td>
                    <td data-label="Status" onclick="event.stopPropagation();">
                        <form method="POST" action="update-status.php" class="status-form">
                            <input type="hidden" name="order_id" value="${order.order_id}">
                            <input type="hidden" name="redirect_to" value="order-list.php">
                            <select name="status" onchange="this.form.submit()" class="status-badge-select status-${statusClass}">
                                ${statusOptionsHtml}
                            </select>
                        </form>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    /**
     * Calculate warning badge for order
     */
    calculateWarningBadge(order, date, time) {
        if (!date || date === '0000-00-00') {
            return '';
        }
        
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const deliveryDate = new Date(date);
        const deliveryDateOnly = new Date(deliveryDate.getFullYear(), deliveryDate.getMonth(), deliveryDate.getDate());
        
        const status = order.db_status ? order.db_status.toLowerCase() : order.status.toLowerCase();
        const hasSpecificTime = time && time !== '00:00:00';
        
        if (hasSpecificTime) {
            const deliveryDateTime = new Date(date + ' ' + time);
            
            if (deliveryDateTime < now && ['confirmed', 'preparing', 'ready for delivery', 'ready for pick-up'].includes(status)) {
                return '<br><span class="warning-badge critical" style="background-color: red; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">OVERDUE</span>';
            } else if (deliveryDateOnly.getTime() === tomorrow.getTime() && ['confirmed', 'preparing'].includes(status)) {
                return '<br><span class="warning-badge urgent" style="background-color: orange; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TOMORROW</span>';
            } else if (deliveryDateOnly.getTime() === today.getTime() && ['confirmed', 'preparing'].includes(status)) {
                return '<br><span class="warning-badge today" style="background-color: yellow; color: black; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TODAY</span>';
            }
        } else {
            const currentHour = now.getHours();
            const businessEndHour = 21; // 9 PM
            
            if (['confirmed', 'preparing', 'ready for delivery', 'ready for pick-up'].includes(status)) {
                if (deliveryDateOnly < today) {
                    return '<br><span class="warning-badge critical" style="background-color: red; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">OVERDUE</span>';
                } else if (deliveryDateOnly.getTime() === today.getTime()) {
                    if (currentHour >= businessEndHour) {
                        return '<br><span class="warning-badge critical" style="background-color: red; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">OVERDUE</span>';
                    } else {
                        return '<br><span class="warning-badge today" style="background-color: yellow; color: black; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TODAY</span>';
                    }
                } else if (deliveryDateOnly.getTime() === tomorrow.getTime() && ['confirmed', 'preparing'].includes(status)) {
                    return '<br><span class="warning-badge urgent" style="background-color: orange; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TOMORROW</span>';
                }
            }
        }
        
        return '';
    }
    
    /**
     * Update status count badges
     */
    updateStatusCounts(statusCounts) {
        for (const [status, count] of Object.entries(statusCounts)) {
            const countElement = document.getElementById(`count-${status}`);
            if (countElement) {
                countElement.textContent = count;
            }
        }
    }
    
    /**
     * Update pagination (placeholder for now)
     */
    updatePagination(totalPages, currentPage) {
        // Pagination update logic can be added here if needed
        // For now, we'll keep the existing pagination as-is
    }
    
    /**
     * Format date as MM-DD-YYYY
     */
    formatDate(date) {
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const year = date.getFullYear();
        return `${month}-${day}-${year}`;
    }
    
    /**
     * Format time as HH:MM AM/PM
     */
    formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    }
    
    /**
     * Format currency with 2 decimal places
     */
    formatCurrency(amount) {
        return parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Handle polling errors with exponential backoff
     */
    handleError(error) {
        this.retryCount++;
        
        // Hide loading indicator
        this.hideLoading();
        
        if (this.retryCount >= this.maxRetries) {
            // Implement exponential backoff
            this.currentBackoff = Math.min(
                this.currentBackoff * this.backoffMultiplier,
                this.maxBackoff
            );
            console.warn(`[OrderListPoller] Backing off to ${this.currentBackoff}ms`);
        }
        
        // Schedule next poll with backoff
        this.schedulePoll();
    }
    
    /**
     * Reset backoff to normal interval
     */
    resetBackoff() {
        if (this.currentBackoff !== this.pollInterval) {
            console.log('[OrderListPoller] Resetting backoff to normal interval');
        }
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
    }
    
    /**
     * Update filter state
     */
    updateFilters(filters) {
        console.log('[OrderListPoller] Updating filters:', filters);
        this.currentFilters = { ...this.currentFilters, ...filters };
        
        // Cancel current poll and start fresh
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
        }
        
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
     * Clear old flags from database
     */
    async clearOldFlags() {
        try {
            await fetch('/NeoCafe/backend/api/clear-order-flags.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
        } catch (error) {
            console.error('[OrderListPoller] Error clearing flags:', error);
        }
    }
    
    /**
     * Handle page visibility changes
     */
    handleVisibilityChange() {
        if (document.hidden) {
            console.log('[OrderListPoller] Page hidden, pausing polling');
        } else {
            console.log('[OrderListPoller] Page visible, resuming polling');
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
    module.exports = OrderListPoller;
}
