document.addEventListener("DOMContentLoaded", function() {
    const notificationList = document.getElementById("notificationList");
    const notificationCountElem = document.getElementById("notificationCount"); // Make sure you have an element with this ID for the count badge

    function fetchNotifications() {
        fetch("/NeoExclusiveCafe/php/users/fetch-notif.php")
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    notificationList.innerHTML = "";
                    let unreadCount = 0;
                    data.notifications.forEach(notif => {
                        const li = document.createElement("li");
                        li.className = notif.is_read ? "read" : "unread";

                        // Product image
                        if (notif.product_image) {
                            const img = document.createElement("img");
                            img.src = notif.product_image;
                            img.alt = notif.title || "Notification Image";
                            img.className = "notif-image";
                            img.style.width = "40px";
                            img.style.height = "40px";
                            img.style.objectFit = "cover";
                            img.style.marginRight = "10px";
                            li.appendChild(img);
                        }

                        // Notification content
                        const contentDiv = document.createElement("div");
                        contentDiv.className = "notif-content";

                        // Title
                        const title = document.createElement("strong");
                        title.textContent = notif.title || notif.message;
                        contentDiv.appendChild(title);

                        // Description/message
                        if (notif.description && notif.description !== notif.title) {
                            const desc = document.createElement("div");
                            desc.textContent = notif.description;
                            desc.className = "notif-desc";
                            contentDiv.appendChild(desc);
                        }

                        // 'View Details' link
                        if (notif.link) {
                            const viewLink = document.createElement("a");
                            viewLink.href = notif.link;
                            viewLink.textContent = "View Details";
                            viewLink.className = "notif-link";
                            viewLink.style.marginLeft = "10px";
                            viewLink.style.textDecoration = "underline";
                            viewLink.style.color = "#007bff";
                            contentDiv.appendChild(viewLink);
                        }

                        li.appendChild(contentDiv);

                        // Timestamp
                        const small = document.createElement("small");
                        small.textContent = new Date(notif.created_at).toLocaleString();
                        li.appendChild(small);

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
    fetchNotifications();

    // Poll every 30 seconds
    setInterval(fetchNotifications, 30000);

    // Mark all as read button handler
    const markAllReadBtn = document.getElementById("markAllRead");
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener("click", function() {
            fetch("/NeoExclusiveCafe/php/users/mark-notif.php", { method: "POST" })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        fetchNotifications();
                    } else {
                        console.error("Failed to mark notifications as read:", data.message);
                    }
                })
                .catch(error => console.error("Error marking notifications as read:", error));
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
