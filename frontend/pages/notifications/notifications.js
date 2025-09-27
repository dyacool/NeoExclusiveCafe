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
        return fetch('/frontend/pages/notifications/mark-notif.php', {
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
        // Update modal content
        document.getElementById('notificationTitle').textContent = notification.title || notification.message;
        document.getElementById('notificationMessage').textContent = notification.message;
        document.getElementById('notificationTimestamp').textContent = 
            'Received: ' + new Date(notification.created_at).toLocaleString();

        // Handle image
        const imageContainer = document.getElementById('notificationImageContainer');
        const image = document.getElementById('notificationImage');
        if (notification.image_url) {
            image.src = notification.image_url;
            imageContainer.style.display = 'block';
        } else {
            imageContainer.style.display = 'none';
        }

        // Handle order details
        const orderDetailsContainer = document.getElementById('orderDetailsContainer');
        const orderDetails = document.getElementById('orderDetails');
        if (notification.type === 'order' && notification.order_details) {
            const order = notification.order_details;
            orderDetails.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Order ID:</strong> #${order.id}</p>
                        <p><strong>Customer:</strong> ${order.customer_name}</p>
                        <p><strong>Email:</strong> ${order.customer_email}</p>
                        <p><strong>Phone:</strong> ${order.customer_phone}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="badge bg-primary">${order.status}</span></p>
                        <p><strong>Total Amount:</strong> ₱${parseFloat(order.total_amount).toFixed(2)}</p>
                        <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <p><strong>Items:</strong> ${order.items}</p>
                        <p><strong>Delivery Address:</strong> ${order.delivery_address}</p>
                    </div>
                </div>
            `;
            orderDetailsContainer.style.display = 'block';
        } else {
            orderDetailsContainer.style.display = 'none';
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
        modal.show();
    }

    // Handle notification click - mark as read and show modal
    function handleNotificationClick(notificationId) {
        // Mark notification as read first
        markNotificationAsRead(notificationId)
            .then(() => {
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
        function fetchAllNotifications() {
            fetch('/frontend/pages/notifications/fetch-notif.php')
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

                            const message = document.createElement("div");
                            message.className = "notification-message";
                            message.textContent = notif.message.substring(0, 50) + (notif.message.length > 50 ? '...' : '');

                            const time = document.createElement("div");
                            time.className = "notification-time";
                            time.textContent = new Date(notif.created_at).toLocaleString([], {
                                short: 'short'
                            });

                            const contentDiv = document.createElement('div');
                            contentDiv.className = 'notification-content';
                            contentDiv.appendChild(title);
                            contentDiv.appendChild(message);
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
                    // Reload the page or refresh notifications list
                    if (notificationList) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error("Error marking all notifications as read:", error);
                    alert('Error marking all notifications as read.');
                });
        });
    }
});

/*              const title = document.createElement("h4");
                title.textContent = notif.title;

                const message = document.createElement("p");
                message.textContent = notif.message;

                const timestamp = document.createElement("small");
                timestamp.textContent = new Date(notif.created_at).toLocaleString();

                content.appendChild(title);
                content.appendChild(message);
                content.appendChild(timestamp);

                li.appendChild(img);
                li.appendChild(content);

                if (notif.link) {
                    const link = document.createElement("a");
                    link.href = notif.link;
                    link.textContent = "View Details";
                    link.className = "notification-link";
                    li.appendChild(link);
                }

                li.addEventListener("click", () => markNotificationAsRead(notif.id));

                notificationList.appendChild(li);
            });
        }
    }

    function fetchNotifications() {
        fetch("/php/users/fetch-notif.php")
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    renderGroupedNotifications(data.grouped_notifications);
                } else {
                    console.error("Failed to fetch notifications:", data.message);
                }
            })
            .catch(error => console.error("Error fetching notifications:", error));
    }

    // Initial fetch
    fetchNotifications();

    // Poll every 30 seconds
    setInterval(fetchNotifications, 30000);

    // Mark all as read button handler
    const markAllReadBtn = document.getElementById("markAllRead");
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener("click", function() {
            fetch("/php/users/mark-notif.php", { method: "POST" })
                .then(() => fetchNotifications());
        });
    }
});*/
