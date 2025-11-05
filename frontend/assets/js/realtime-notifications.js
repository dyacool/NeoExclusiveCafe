/**
 * Realtime Notifications Client
 * 
 * JavaScript module for subscribing to SSE streams and handling realtime events
 * Provides automatic reconnection with exponential backoff
 */

class RealtimeNotifications {
    constructor(channels = ['notifications']) {
        this.channels = Array.isArray(channels) ? channels : [channels];
        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000; // 3 seconds
        this.handlers = {};
        this.isConnected = false;
        this.reconnectTimeout = null;
        this.lastEventId = 0;
    }
    
    /**
     * Connect to SSE stream
     */
    connect() {
        if (this.eventSource) {
            console.warn('[RealtimeNotifications] Already connected');
            return;
        }
        
        const channelsParam = this.channels.join(',');
        const url = `/backend/api/sse-stream.php?channels=${channelsParam}`;
        
        console.log('[RealtimeNotifications] Connecting to:', url);
        
        try {
            this.eventSource = new EventSource(url);
            
            this.eventSource.onopen = () => {
                console.log('[RealtimeNotifications] Connection established');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.updateConnectionStatus('connected');
                this.triggerHandlers('connection', { status: 'connected' });
            };
            
            this.eventSource.onerror = (error) => {
                console.error('[RealtimeNotifications] Connection error:', error);
                this.isConnected = false;
                
                if (this.eventSource.readyState === EventSource.CLOSED) {
                    this.updateConnectionStatus('disconnected');
                    this.handleReconnect();
                } else {
                    this.updateConnectionStatus('reconnecting');
                }
                
                this.triggerHandlers('connection', { status: 'error', error });
            };
            
            // Register event listeners
            this.registerEventListeners();
            
        } catch (error) {
            console.error('[RealtimeNotifications] Failed to create EventSource:', error);
            this.handleReconnect();
        }
    }
    
    /**
     * Disconnect from SSE stream
     */
    disconnect() {
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
            this.reconnectTimeout = null;
        }
        
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        
        this.isConnected = false;
        this.updateConnectionStatus('disconnected');
        this.triggerHandlers('connection', { status: 'disconnected' });
        
        console.log('[RealtimeNotifications] Disconnected');
    }
    
    /**
     * Register handler for specific event type
     * 
     * @param {string} eventType Event type to listen for
     * @param {function} handler Callback function
     */
    on(eventType, handler) {
        if (typeof handler !== 'function') {
            console.error('[RealtimeNotifications] Handler must be a function');
            return;
        }
        
        if (!this.handlers[eventType]) {
            this.handlers[eventType] = [];
        }
        
        this.handlers[eventType].push(handler);
        console.log(`[RealtimeNotifications] Registered handler for '${eventType}'`);
    }
    
    /**
     * Remove handler for specific event type
     * 
     * @param {string} eventType Event type
     * @param {function} handler Handler function to remove
     */
    off(eventType, handler) {
        if (!this.handlers[eventType]) {
            return;
        }
        
        if (handler) {
            this.handlers[eventType] = this.handlers[eventType].filter(h => h !== handler);
        } else {
            delete this.handlers[eventType];
        }
    }
    
    /**
     * Handle reconnection with exponential backoff
     */
    handleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('[RealtimeNotifications] Max reconnection attempts reached');
            this.updateConnectionStatus('failed');
            this.showReconnectPrompt();
            this.triggerHandlers('connection', { status: 'failed' });
            return;
        }
        
        this.updateConnectionStatus('reconnecting');
        this.reconnectAttempts++;
        
        const delay = Math.min(
            this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1),
            30000 // Max 30 seconds
        );
        
        console.log(`[RealtimeNotifications] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
        
        this.reconnectTimeout = setTimeout(() => {
            this.disconnect();
            this.connect();
        }, delay);
    }
    
    /**
     * Register event listeners for all event types
     */
    registerEventListeners() {
        if (!this.eventSource) {
            return;
        }
        
        // Connected event
        this.eventSource.addEventListener('connected', (e) => {
            try {
                const data = JSON.parse(e.data);
                console.log('[RealtimeNotifications] Connected:', data);
            } catch (error) {
                console.error('[RealtimeNotifications] Error parsing connected event:', error);
            }
        });
        
        // Order status updates
        this.eventSource.addEventListener('order_status', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.lastEventId = parseInt(e.lastEventId) || this.lastEventId;
                console.log('[RealtimeNotifications] Order status update:', data);
                this.triggerHandlers('order_status', data);
            } catch (error) {
                console.error('[RealtimeNotifications] Error parsing order_status event:', error);
            }
        });
        
        // Product inventory updates
        this.eventSource.addEventListener('product_inventory', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.lastEventId = parseInt(e.lastEventId) || this.lastEventId;
                console.log('[RealtimeNotifications] Product inventory update:', data);
                this.triggerHandlers('product_inventory', data);
            } catch (error) {
                console.error('[RealtimeNotifications] Error parsing product_inventory event:', error);
            }
        });
        
        // New order notifications
        this.eventSource.addEventListener('new_order', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.lastEventId = parseInt(e.lastEventId) || this.lastEventId;
                console.log('[RealtimeNotifications] New order:', data);
                this.triggerHandlers('new_order', data);
            } catch (error) {
                console.error('[RealtimeNotifications] Error parsing new_order event:', error);
            }
        });
        
        // General notifications
        this.eventSource.addEventListener('notification', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.lastEventId = parseInt(e.lastEventId) || this.lastEventId;
                console.log('[RealtimeNotifications] Notification:', data);
                this.triggerHandlers('notification', data);
            } catch (error) {
                console.error('[RealtimeNotifications] Error parsing notification event:', error);
            }
        });
        
        // Delivery assignment
        this.eventSource.addEventListener('delivery_assignment', (e) => {
            try {
                const data = JSON.parse(e.data);
                this.lastEventId = parseInt(e.lastEventId) || this.lastEventId;
                console.log('[RealtimeNotifications] Delivery assignment:', data);
                this.triggerHandlers('delivery_assignment', data);
            } catch (error) {
                console.error('[RealtimeNotifications] Error parsing delivery_assignment event:', error);
            }
        });
        
        // Keepalive
        this.eventSource.addEventListener('keepalive', (e) => {
            // Silent keepalive, just acknowledge
            this.triggerHandlers('keepalive', { timestamp: new Date().toISOString() });
        });
        
        // Timeout
        this.eventSource.addEventListener('timeout', (e) => {
            console.warn('[RealtimeNotifications] Connection timeout');
            this.disconnect();
            this.connect();
        });
    }
    
    /**
     * Trigger all registered handlers for an event type
     * 
     * @param {string} eventType Event type
     * @param {object} data Event data
     */
    triggerHandlers(eventType, data) {
        if (this.handlers[eventType]) {
            this.handlers[eventType].forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    console.error(`[RealtimeNotifications] Error in ${eventType} handler:`, error);
                }
            });
        }
    }
    
    /**
     * Update connection status indicator
     * 
     * @param {string} status Connection status (connected, disconnected, reconnecting, failed)
     */
    updateConnectionStatus(status) {
        const indicator = document.getElementById('realtime-connection-status');
        if (indicator) {
            indicator.className = `realtime-connection-status ${status}`;
            indicator.setAttribute('data-status', status);
            
            const statusText = {
                'connected': 'Connected',
                'disconnected': 'Disconnected',
                'reconnecting': 'Reconnecting...',
                'failed': 'Connection Failed'
            };
            
            indicator.textContent = statusText[status] || status;
        }
        
        // Only log important status changes
        if (status === 'connected' || status === 'failed') {
            console.log('[RealtimeNotifications] Status:', status);
        }
        
        // Trigger custom event for other components
        const event = new CustomEvent('realtimeConnectionStatus', {
            detail: { status }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Show prompt to refresh page after failed reconnection
     */
    showReconnectPrompt() {
        const message = 'Connection lost. Please refresh the page to reconnect.';
        
        // Try to use existing notification system if available
        if (typeof showNotification === 'function') {
            showNotification(message, 'error');
        } else {
            // Fallback to alert
            if (confirm(message + '\n\nRefresh now?')) {
                window.location.reload();
            }
        }
    }
    
    /**
     * Check if currently connected
     * 
     * @returns {boolean} Connection status
     */
    isConnectedToStream() {
        return this.isConnected && this.eventSource && this.eventSource.readyState === EventSource.OPEN;
    }
    
    /**
     * Get current connection state
     * 
     * @returns {string} Connection state (CONNECTING, OPEN, CLOSED)
     */
    getConnectionState() {
        if (!this.eventSource) {
            return 'CLOSED';
        }
        
        const states = {
            [EventSource.CONNECTING]: 'CONNECTING',
            [EventSource.OPEN]: 'OPEN',
            [EventSource.CLOSED]: 'CLOSED'
        };
        
        return states[this.eventSource.readyState] || 'UNKNOWN';
    }
}

// Check if EventSource is supported
if (typeof EventSource === 'undefined') {
    console.error('[RealtimeNotifications] EventSource is not supported in this browser');
}
