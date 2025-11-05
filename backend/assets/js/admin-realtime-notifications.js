/**
 * Admin Realtime Notifications Integration
 * Integrates SSE realtime updates with existing admin notification UI
 */

(function() {
    'use strict';
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminRealtimeNotifications);
    } else {
        initAdminRealtimeNotifications();
    }
    
    function initAdminRealtimeNotifications() {
        const notifBadge = document.getElementById('notificationBadge');
        if (!notifBadge && !document.getElementById('notificationBellBtn')) {
            return; // Not on dashboard
        }
        
        // Delay connection to not block page load
        setTimeout(function() {
            console.log('[Admin Realtime] Connecting...');
            
            const realtimeNotifications = new RealtimeNotifications(['new_order', 'order_status', 'notifications']);
        
        realtimeNotifications.on('new_order', function(data) {
            console.log('[Admin Realtime] New order:', data);
            
            showNewOrderAlert(data);
            playNotificationSound();
            incrementAdminBadge();
            refreshAdminDropdown();
        });
        
        realtimeNotifications.on('order_status', function(data) {
            console.log('[Admin Realtime] Order status update:', data);
            showRealtimeToast(`Order #${data.order_id} → ${data.status}`, 'info', 5000);
        });
        
            realtimeNotifications.connect();
            console.log('[Admin Realtime] Connected');
        }, 2000); // Wait 2 seconds
    }
    
    function showNewOrderAlert(data) {
        const message = `New Order #${data.order_id} from ${data.customer_name}<br>₱${parseFloat(data.total).toFixed(2)} - ${data.order_type}`;
        showRealtimeToast(message, 'success', 0);
    }
    
    function incrementAdminBadge() {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return;
        
        const current = parseInt(badge.textContent) || 0;
        badge.textContent = current + 1;
        badge.style.display = 'inline-block';
    }
    
    function refreshAdminDropdown() {
        // Reload notifications if dropdown is open
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown && dropdown.classList.contains('active')) {
            loadAdminNotifications();
        }
    }
    
    function loadAdminNotifications() {
        fetch('/backend/pages/admin-includes/notifications/notification.php?action=get_notifications')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    console.log('[Admin Realtime] Refreshed notifications');
                }
            });
    }
})();
