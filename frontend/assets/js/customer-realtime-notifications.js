/**
 * Customer Realtime Notifications Integration
 * 
 * Integrates SSE realtime updates with existing customer notification UI
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomerRealtimeNotifications);
    } else {
        initCustomerRealtimeNotifications();
    }
    
    function initCustomerRealtimeNotifications() {
        // Only initialize if user is logged in
        const notifCount = document.getElementById('notifCount');
        if (!notifCount) {
            console.log('[Customer Realtime] Not logged in, skipping initialization');
            return;
        }
        
        console.log('[Customer Realtime] Initializing...');
        
        // Initialize realtime connection
        const realtimeNotifications = new RealtimeNotifications(['order_status', 'notifications']);
        
        // Handle order status updates
        realtimeNotifications.on('order_status', function(data) {
            console.log('[Customer Realtime] Order status update:', data);
            
            // Show toast notification
            showRealtimeToast(
                `Order #${data.order_id} status updated to: ${data.status}`,
                'info',
                5000
            );
            
            // Refresh notification dropdown if open
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown && dropdown.classList.contains('active')) {
                refreshNotificationDropdown();
            } else {
                // Just increment badge count
                incrementNotificationBadge();
            }
        });
        
        // Handle general notifications
        realtimeNotifications.on('notification', function(data) {
            console.log('[Customer Realtime] New notification:', data);
            
            // Show toast
            showRealtimeToast(data.message, data.type || 'info', 5000);
            
            // Update notification UI
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown && dropdown.classList.contains('active')) {
                refreshNotificationDropdown();
            } else {
                incrementNotificationBadge();
            }
        });
        
        // Connect to SSE stream
        realtimeNotifications.connect();
        
        console.log('[Customer Realtime] Connected to SSE stream');
    }
    
    function incrementNotificationBadge() {
        const badge = document.getElementById('notifCount');
        if (!badge) return;
        
        const currentCount = parseInt(badge.textContent) || 0;
        const newCount = currentCount + 1;
        
        badge.textContent = newCount;
        badge.style.display = 'inline-block';
        
        // Add animation
        badge.style.animation = 'none';
        setTimeout(() => {
            badge.style.animation = 'badgePop 0.3s ease';
        }, 10);
    }
    
    function refreshNotificationDropdown() {
        // Trigger the existing fetch function if available
        if (typeof fetchNotifications === 'function') {
            fetchNotifications();
        } else if (typeof window.fetchDropdownNotifications === 'function') {
            window.fetchDropdownNotifications()
                .then(notifications => {
                    console.log('[Customer Realtime] Refreshed notifications');
                })
                .catch(error => {
                    console.error('[Customer Realtime] Error refreshing:', error);
                });
        }
    }
})();
