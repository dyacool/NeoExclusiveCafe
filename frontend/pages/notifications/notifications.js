// Global function to open order details modal (defined outside DOMContentLoaded)
window.openOrderDetailsModal = function(orderId) {
    console.log('Opening order details modal for order:', orderId);
    
    // Create modal if it doesn't exist
    let orderModal = document.getElementById('orderDetailsModal');
    if (!orderModal) {
        // Create modal structure
        const modalHTML = `
            <div class="modal-overlay" id="orderDetailsModal" style="display: none;">
                <div class="modal-container modal-large">
                    <div class="modal-header">
                        <h3 class="modal-title">Order Details</h3>
                        <button class="modal-close" onclick="closeOrderDetailsModal()">&times;</button>
                    </div>
                    <div class="modal-body" id="orderDetailsContent">
                        <div class="loading-spinner">Loading order details...</div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        orderModal = document.getElementById('orderDetailsModal');
        
        // Add click outside to close
        orderModal.addEventListener('click', function(e) {
            if (e.target === orderModal) {
                closeOrderDetailsModal();
            }
        });
    }

    // Show modal
    orderModal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    // Fetch order details
    const orderDetailsContent = document.getElementById('orderDetailsContent');
    orderDetailsContent.innerHTML = '<div class="loading-spinner">Loading order details...</div>';

    fetch(`/frontend/pages/cart/get-order-details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.order, data.items);
            } else {
                orderDetailsContent.innerHTML = `<div class="error-message">Failed to load order details: ${data.message || 'Unknown error'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            orderDetailsContent.innerHTML = '<div class="error-message">Failed to load order details. Please try again.</div>';
        });
};

window.closeOrderDetailsModal = function() {
    const orderModal = document.getElementById('orderDetailsModal');
    if (orderModal) {
        orderModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

function displayOrderDetails(order, items) {
    let itemsHTML = '';
    let subtotal = 0;

    if (items && items.length > 0) {
        items.forEach(item => {
            const price = parseFloat(item.price || 0);
            const quantity = parseInt(item.quantity || 0);
            const total = price * quantity;
            subtotal += total;

            itemsHTML += `
                <tr>
                    <td>${item.product_name || 'Unknown'}</td>
                    <td>${quantity}</td>
                    <td>₱${price.toFixed(2)}</td>
                    <td>₱${total.toFixed(2)}</td>
                </tr>
            `;
        });
    } else {
        itemsHTML = '<tr><td colspan="4">No items found</td></tr>';
    }

    const orderDate = new Date(order.order_date || order.created_at);
    const formattedDate = orderDate.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    const orderDetailsContent = document.getElementById('orderDetailsContent');
    orderDetailsContent.innerHTML = `
        <div class="order-details-container">
            <h2>Order #${order.order_id}</h2>
            
            <div class="order-info-grid">
                <div class="info-item">
                    <strong>Status:</strong>
                    <span class="status-badge status-${order.status?.toLowerCase().replace(/\s+/g, '-')}">${order.status}</span>
                </div>
                <div class="info-item">
                    <strong>Order Date:</strong>
                    <span>${formattedDate}</span>
                </div>
                <div class="info-item">
                    <strong>Customer:</strong>
                    <span>${order.customer_name || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    <span>${order.customer_email || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <strong>Phone:</strong>
                    <span>${order.customer_phone || 'N/A'}</span>
                </div>
                <div class="info-item">
                    <strong>Delivery Method:</strong>
                    <span>${order.delivery_method || 'N/A'}</span>
                </div>
                <div class="info-item full-width">
                    <strong>Delivery Address:</strong>
                    <span>${order.delivery_address || 'N/A'}</span>
                </div>
            </div>

            <h3>Order Items</h3>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHTML}
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3"><strong>Total Amount:</strong></td>
                        <td><strong>₱${(order.total_amount || subtotal).toFixed(2)}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
}

document.addEventListener("DOMContentLoaded", function() {
    // Global notification functions for use across the site
    
    // Fetch notifications for dropdown (latest 5)
    function fetchDropdownNotifications() {
        return fetch('/frontend/pages/notifications/fetch-notif.php?dropdown=true')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success") {
                    return data.notifications || [];
                } else {
                    throw new Error(data.message || 'Failed to fetch notifications');
                }
            });
    }

    // Fetch notification details by ID
    function fetchNotificationDetails(notificationId) {
        return fetch(`/frontend/pages/notifications/fetch-notif.php?id=${notificationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    return data.notification;
                } else {
                    throw new Error(data.message || 'Failed to fetch notification details');
                }
            });
    }

    // Mark notification as read
    function markNotificationAsRead(notificationId) {
        return fetch('/frontend/pages/notifications/mark-notif.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                throw new Error(data.message || 'Failed to mark notification as read');
            }
            return data;
        });
    }

    // Mark all notifications as read
    function markAllNotificationsAsRead() {
        return fetch('.../../pages/notifications/mark-notif.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'mark_all=true'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                throw new Error(data.message || 'Failed to mark all notifications as read');
            }
            return data;
        });
    }

    // Show notification modal
    function showNotificationModal(notification) {
        // Ensure modal elements exist before updating
        const titleElement = document.getElementById('notificationTitle');
        const messageElement = document.getElementById('notificationMessage');
        const timestampElement = document.getElementById('notificationTimestamp');
        
        if (!titleElement || !messageElement || !timestampElement) {
            console.error('Required modal elements not found');
            return;
        }

        // Update modal content with proper error handling
        titleElement.textContent = notification.title || notification.message || 'Notification';
        
        // Build message with link if available
        let messageHtml = notification.message || 'No message available';
         if (notification.link && notification.order_id) {
            // For order notifications, show a button that opens modal
            messageHtml += `<br><br><button onclick="openOrderDetailsModal(${notification.order_id}); return false;" class="notif-link btn btn-primary btn-sm" style="color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; display: inline-block; margin-top: 10px; cursor: pointer; border: none;">View Order Details</button>`;
        } else if (notification.link) {
            // For non-order links, show regular anchor
            messageHtml += `<br><br><a href="${notification.link}" class="notif-link btn btn-primary btn-sm" style="color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; display: inline-block; margin-top: 10px;">View Details</a>`;
        }
        messageElement.innerHTML = messageHtml;
        
        timestampElement.textContent = 'Received: ' + new Date(notification.created_at).toLocaleString();

        // Handle image with better error handling
        const imageContainer = document.getElementById('notificationImageContainer');
        const image = document.getElementById('notificationImage');
        if (imageContainer && image) {
            if (notification.image_url && 
                notification.image_url !== '.../../assets/images/default-product.png' &&
                notification.image_url !== '' &&
                notification.image_url !== null) {
                image.src = notification.image_url;
                image.alt = notification.title || 'Notification Image';
                imageContainer.style.display = 'block';
            } else {
                imageContainer.style.display = 'none';
            }
        }

        // Handle notification details based on type
        const orderDetailsContainer = document.getElementById('orderDetailsContainer');
        const orderDetails = document.getElementById('orderDetails');
        
        if (!orderDetailsContainer || !orderDetails) {
            console.error('Order details container not found');
            return;
        }
        
        // Clear previous content
        orderDetails.innerHTML = '';
        
        // Determine notification type and display appropriate details
        const notificationType = notification.type || 'general';
        
        if (notificationType === 'order' || notificationType === 'order_update') {
            // Handle order notifications
            if (notification.order_details && Object.keys(notification.order_details).length > 0) {
                const order = notification.order_details;
                orderDetails.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order ID:</strong> #${order.id || 'N/A'}</p>
                            <p><strong>Customer:</strong> ${order.customer_name || 'N/A'}</p>
                            <p><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
                            <p><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <span class="badge bg-primary">${order.status || 'Unknown'}</span></p>
                            <p><strong>Total Amount:</strong> ₱${parseFloat(order.total_amount || 0).toFixed(2)}</p>
                            <p><strong>Order Date:</strong> ${order.order_date ? new Date(order.order_date).toLocaleString() : 'N/A'}</p>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <p><strong>Items:</strong> ${order.items || 'No items found'}</p>
                            <p><strong>Delivery Address:</strong> ${order.delivery_address || 'N/A'}</p>
                        </div>
                    </div>
                `;
            } else {
                // Fallback for order notifications without details
                orderDetails.innerHTML = `
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Order Notification:</strong> ${notification.title || 'Order Update'}</p>
                            <p><strong>Message:</strong> ${notification.message || 'No additional details available'}</p>
                            <p><strong>Type:</strong> <span class="badge bg-primary">Order</span></p>
                            <p><strong>Notification ID:</strong> ${notification.id || 'N/A'}</p>
                        </div>
                    </div>
                `;
            }
            orderDetailsContainer.style.display = 'block';
        } else if (notificationType === 'promotion') {
            // Handle promotion notifications
            orderDetails.innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <p><strong>Promotion Type:</strong> ${notification.title || 'Promotion'}</p>
                        <p><strong>Description:</strong> ${notification.message || 'No description available'}</p>
                        <p><strong>Notification Type:</strong> <span class="badge bg-success">Promotion</span></p>
                        <p><strong>Created:</strong> ${new Date(notification.created_at).toLocaleString()}</p>
                        <p><strong>Notification ID:</strong> ${notification.id || 'N/A'}</p>
                    </div>
                </div>
            `;
            orderDetailsContainer.style.display = 'block';
        } else if (notificationType === 'system_alert' || notificationType === 'system') {
            // Handle system alert notifications
            orderDetails.innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <p><strong>System Alert:</strong> ${notification.title || 'System Notification'}</p>
                        <p><strong>Message:</strong> ${notification.message || 'No message available'}</p>
                        <p><strong>Type:</strong> <span class="badge bg-info">System</span></p>
                        <p><strong>Created:</strong> ${new Date(notification.created_at).toLocaleString()}</p>
                        <p><strong>Notification ID:</strong> ${notification.id || 'N/A'}</p>
                    </div>
                </div>
            `;
            orderDetailsContainer.style.display = 'block';
        } else {
            // Handle other notification types
            orderDetails.innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <p><strong>Notification Type:</strong> <span class="badge bg-secondary">${notificationType.charAt(0).toUpperCase() + notificationType.slice(1)}</span></p>
                        <p><strong>Title:</strong> ${notification.title || 'Notification'}</p>
                        <p><strong>Message:</strong> ${notification.message || 'No message available'}</p>
                        <p><strong>Created:</strong> ${new Date(notification.created_at).toLocaleString()}</p>
                        <p><strong>Notification ID:</strong> ${notification.id || 'N/A'}</p>
                    </div>
                </div>
            `;
            orderDetailsContainer.style.display = 'block';
        }

        // Show modal with proper error handling
        const modalElement = document.getElementById('notificationModal');
        if (modalElement) {
            try {
                if (!window.__notificationModalInstance) {
                    window.__notificationModalInstance = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true
                    });
                    // Ensure cleanup so navbar remains clickable
                    modalElement.addEventListener('hidden.bs.modal', () => {
                        document.body.classList.remove('modal-open');
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(b => b.parentNode && b.parentNode.removeChild(b));
                    });
                }
                window.__notificationModalInstance.show();
            } catch (error) {
                console.error('Error showing modal:', error);
                // Fallback: try to show modal without options
                const fallback = new bootstrap.Modal(modalElement);
                fallback.show();
            }
        } else {
            console.error('Notification modal not found on this page');
            alert('Unable to display notification details. Please refresh the page and try again.');
        }
    }

    // Handle notification click - mark as read and show modal
    function handleNotificationClick(notificationId) {
        // Mark notification as read first
        markNotificationAsRead(notificationId)
            .then(() => {
                // Update the UI to show the notification as read
                updateNotificationStatus(notificationId, true);
                // Then fetch details and show modal
                return fetchNotificationDetails(notificationId);
            })
            .then(notification => {
                showNotificationModal(notification);
            })
            .catch(error => {
                console.error('Error handling notification click:', error);
                // Still try to show the modal even if marking as read fails
                fetchNotificationDetails(notificationId)
                    .then(notification => {
                        showNotificationModal(notification);
                    })
                    .catch(modalError => {
                        console.error('Error fetching notification details:', modalError);
                    });
            });
    }

    // Update notification status in the UI
    function updateNotificationStatus(notificationId, isRead) {
        const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (notificationItem) {
            if (isRead) {
                notificationItem.classList.remove('unread');
                notificationItem.classList.add('read');
                // Update status badge
                const statusBadge = notificationItem.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Read';
                    statusBadge.classList.remove('unread');
                    statusBadge.classList.add('read');
                }
            } else {
                notificationItem.classList.remove('read');
                notificationItem.classList.add('unread');
                // Update status badge
                const statusBadge = notificationItem.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.textContent = 'New';
                    statusBadge.classList.remove('read');
                    statusBadge.classList.add('unread');
                }
            }
        }
    }

    // Make functions globally available
    window.fetchDropdownNotifications = fetchDropdownNotifications;
    window.fetchNotificationDetails = fetchNotificationDetails;
    window.markNotificationAsRead = markNotificationAsRead;
    window.markAllNotificationsAsRead = markAllNotificationsAsRead;
    window.showNotificationModal = showNotificationModal;
    window.handleNotificationClick = handleNotificationClick;

    // Page-specific functionality for notifications.php
    const notificationList = document.getElementById("notificationList");
    const notificationCountElem = document.getElementById("notificationCount");

    if (notificationList) {
        // Fetch all notifications for the notifications page
        function fetchAllNotifications(page = 1) {
            fetch(`/frontend/pages/notifications/fetch-notif.php?page=${page}&per_page=10`)
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    notificationList.innerHTML = "";
                    let unreadCount = 0;
                    data.notifications.forEach(notif => {
                        const li = document.createElement("li");
                            li.className = `notification-item ${notif.is_read ? "read" : "unread"}`;
                            li.dataset.notificationId = notif.id;
                            
                            // Click handler to open modal
                            li.addEventListener('click', () => {
                                handleNotificationClick(notif.id);
                            });

                            const title = document.createElement("div");
                            title.className = "notification-title";
                            title.textContent = notif.title || notif.message;

                            const time = document.createElement("div");
                            time.className = "notification-time";
                            time.textContent = new Date(notif.created_at).toLocaleString([], {
                                short: 'short'
                            });

                            const contentDiv = document.createElement('div');
                            contentDiv.className = 'notification-content';
                            contentDiv.appendChild(title);
                            contentDiv.appendChild(time);

                        li.appendChild(contentDiv);
                            notificationList.appendChild(li);
                            
                        if (!notif.is_read) unreadCount++;
                    });
                        
                    // Update notification count display
                    if (notificationCountElem) {
                        if (unreadCount > 0) {
                            notificationCountElem.textContent = unreadCount;
                            notificationCountElem.style.display = "inline-block";
                        } else {
                            notificationCountElem.textContent = "";
                            notificationCountElem.style.display = "none";
                        }
                    }
                    // Simple pagination controls
                    const pagination = document.getElementById('notificationPagination');
                    if (pagination) {
                        pagination.innerHTML = '';
                        const prev = document.createElement('button');
                        prev.textContent = 'Prev';
                        prev.disabled = (data.page || 1) <= 1;
                        prev.addEventListener('click', () => fetchAllNotifications((data.page || 1) - 1));
                        const next = document.createElement('button');
                        next.textContent = 'Next';
                        next.disabled = !data.has_more;
                        next.addEventListener('click', () => fetchAllNotifications((data.page || 1) + 1));
                        pagination.appendChild(prev);
                        const info = document.createElement('span');
                        info.style.margin = '0 8px';
                        info.textContent = `Page ${data.page || 1}`;
                        pagination.appendChild(info);
                        pagination.appendChild(next);
                    }
                } else {
                    console.error("Failed to fetch notifications:", data.message);
                }
            })
            .catch(error => console.error("Error fetching notifications:", error));
    }

    // Initial fetch
        fetchAllNotifications();

    // Poll every 30 seconds
        setInterval(fetchAllNotifications, 30000);
    }

    // Mark all as read button handler
    const markAllReadBtn = document.getElementById("markAllRead");
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener("click", function() {
            markAllNotificationsAsRead()
                .then(() => {
                    // Update all notification items in the UI to show as read
                    const allNotificationItems = document.querySelectorAll('.notification-item');
                    allNotificationItems.forEach(item => {
                        item.classList.remove('unread');
                        item.classList.add('read');
                        
                        // Update status badge
                        const statusBadge = item.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Read';
                            statusBadge.classList.remove('unread');
                            statusBadge.classList.add('read');
                        }
                    });
                    
                    // Show success message
                    const originalText = markAllReadBtn.textContent;
                    markAllReadBtn.textContent = 'All Marked as Read!';
                    markAllReadBtn.style.backgroundColor = '#28a745';
                    
                    setTimeout(() => {
                        markAllReadBtn.textContent = originalText;
                        markAllReadBtn.style.backgroundColor = '';
                    }, 2000);
                })
                .catch(error => {
                    console.error("Error marking all notifications as read:", error);
                    alert('Error marking all notifications as read.');
                });
        });
    }
});
