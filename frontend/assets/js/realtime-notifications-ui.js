/**
 * Realtime Notifications UI Helpers
 * 
 * Helper functions for displaying notifications and updating UI
 */

/**
 * Show a toast notification
 * 
 * @param {string} message Notification message
 * @param {string} type Notification type (success, error, warning, info)
 * @param {number} duration Duration in milliseconds (0 = no auto-close)
 */
function showRealtimeToast(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `realtime-notification-toast ${type}`;
    
    const typeIcons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    const typeTitles = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Information'
    };
    
    toast.innerHTML = `
        <div class="realtime-notification-toast-header">
            <span class="realtime-notification-toast-title">
                ${typeIcons[type] || 'ℹ'} ${typeTitles[type] || 'Notification'}
            </span>
            <button class="realtime-notification-toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
        <div class="realtime-notification-toast-body">${message}</div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-close after duration
    if (duration > 0) {
        setTimeout(() => {
            toast.classList.add('closing');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    return toast;
}

/**
 * Update notification badge count
 * 
 * @param {string} elementId Element ID of the badge
 * @param {number} count Notification count
 */
function updateNotificationBadge(elementId, count) {
    const badge = document.getElementById(elementId);
    if (!badge) return;
    
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }
}

/**
 * Highlight an element temporarily
 * 
 * @param {string|HTMLElement} element Element or selector
 * @param {string} className CSS class to add
 * @param {number} duration Duration in milliseconds
 */
function highlightElement(element, className = 'product-updated', duration = 2000) {
    const el = typeof element === 'string' ? document.querySelector(element) : element;
    if (!el) return;
    
    el.classList.add(className);
    setTimeout(() => el.classList.remove(className), duration);
}

/**
 * Play notification sound
 * 
 * @param {string} soundFile Path to sound file
 */
function playNotificationSound(soundFile = '/frontend/assets/sounds/notification.mp3') {
    try {
        const audio = new Audio(soundFile);
        audio.volume = 0.5;
        audio.play().catch(error => {
            console.warn('[RealtimeNotifications] Could not play sound:', error);
        });
    } catch (error) {
        console.warn('[RealtimeNotifications] Sound playback error:', error);
    }
}

/**
 * Update order status in UI
 * 
 * @param {object} data Order status data
 */
function updateOrderStatusUI(data) {
    const { order_id, status } = data;
    
    // Find order status element
    const statusElement = document.querySelector(`[data-order-id="${order_id}"] .order-status`);
    if (statusElement) {
        statusElement.textContent = status;
        highlightElement(statusElement.closest('[data-order-id]'), 'order-status-updated');
    }
    
    // Show toast notification
    showRealtimeToast(`Order #${order_id} status updated to: ${status}`, 'info', 5000);
}

/**
 * Update product quantity in UI
 * 
 * @param {object} data Product inventory data
 */
function updateProductQuantityUI(data) {
    const { product_id, quantity, available, product_name } = data;
    
    // Find product element
    const productElement = document.querySelector(`[data-product-id="${product_id}"]`);
    if (productElement) {
        // Update quantity display
        const quantityElement = productElement.querySelector('.product-quantity');
        if (quantityElement) {
            quantityElement.textContent = quantity;
        }
        
        // Update availability status
        if (available) {
            productElement.classList.remove('out-of-stock');
            productElement.classList.add('in-stock');
        } else {
            productElement.classList.remove('in-stock');
            productElement.classList.add('out-of-stock');
        }
        
        // Highlight the change
        highlightElement(productElement, 'product-updated');
    }
    
    // Show toast for significant changes
    if (!available) {
        showRealtimeToast(`${product_name} is now out of stock`, 'warning', 5000);
    } else if (quantity <= 5) {
        showRealtimeToast(`${product_name} - Only ${quantity} left!`, 'warning', 5000);
    }
}

/**
 * Show new order notification
 * 
 * @param {object} data New order data
 */
function showNewOrderNotification(data) {
    const { order_id, customer_name, order_type, total } = data;
    
    const message = `
        New ${order_type} order from ${customer_name}<br>
        Order #${order_id} - ₱${parseFloat(total).toFixed(2)}
    `;
    
    showRealtimeToast(message, 'success', 0); // Don't auto-close
    playNotificationSound();
    
    // Update new orders badge
    const currentCount = parseInt(document.getElementById('new-orders-badge')?.textContent || '0');
    updateNotificationBadge('new-orders-badge', currentCount + 1);
}

/**
 * Add notification to notification center
 * 
 * @param {object} data Notification data
 */
function addNotificationToCenter(data) {
    const { notification_id, message, type, read } = data;
    
    const notificationsList = document.getElementById('notifications-list');
    if (!notificationsList) return;
    
    const notificationItem = document.createElement('div');
    notificationItem.className = `notification-item ${type} ${read ? 'read' : 'unread'}`;
    notificationItem.setAttribute('data-notification-id', notification_id);
    
    const typeIcons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    notificationItem.innerHTML = `
        <div class="notification-icon">${typeIcons[type] || 'ℹ'}</div>
        <div class="notification-content">
            <div class="notification-message">${message}</div>
            <div class="notification-time">${new Date().toLocaleTimeString()}</div>
        </div>
    `;
    
    // Add click handler to mark as read
    notificationItem.addEventListener('click', () => {
        markNotificationAsRead(notification_id);
        notificationItem.classList.add('read');
        notificationItem.classList.remove('unread');
    });
    
    // Insert at the top
    notificationsList.insertBefore(notificationItem, notificationsList.firstChild);
    
    // Update unread count
    if (!read) {
        const currentCount = parseInt(document.getElementById('notifications-badge')?.textContent || '0');
        updateNotificationBadge('notifications-badge', currentCount + 1);
    }
    
    // Show toast
    showRealtimeToast(message, type, 5000);
}

/**
 * Mark notification as read (placeholder - implement API call)
 * 
 * @param {number} notificationId Notification ID
 */
function markNotificationAsRead(notificationId) {
    // TODO: Implement API call to mark notification as read
    console.log('[RealtimeNotifications] Mark notification as read:', notificationId);
    
    // Update badge count
    const badge = document.getElementById('notifications-badge');
    if (badge) {
        const currentCount = parseInt(badge.textContent || '0');
        updateNotificationBadge('notifications-badge', Math.max(0, currentCount - 1));
    }
}

/**
 * Create connection status indicator
 * 
 * @param {string} containerId Container element ID
 * @returns {HTMLElement} Status indicator element
 */
function createConnectionStatusIndicator(containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn('[RealtimeNotifications] Container not found:', containerId);
        return null;
    }
    
    const indicator = document.createElement('span');
    indicator.id = 'realtime-connection-status';
    indicator.className = 'realtime-connection-status disconnected';
    indicator.textContent = 'Disconnected';
    
    container.appendChild(indicator);
    return indicator;
}

/**
 * Show delivery assignment notification
 * 
 * @param {object} data Delivery assignment data
 */
function showDeliveryAssignment(data) {
    const { order_id, customer_address, delivery_time } = data;
    
    const message = `
        New delivery assignment!<br>
        Order #${order_id}<br>
        Address: ${customer_address}<br>
        Time: ${delivery_time}
    `;
    
    showRealtimeToast(message, 'info', 0); // Don't auto-close
    playNotificationSound();
}
