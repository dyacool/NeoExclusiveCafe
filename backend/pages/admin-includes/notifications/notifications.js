class NotificationSystem {
  constructor() {
    this.isOpen = false;
    this.unreadCount = 0;
    this.notifications = [];
    this.updateInterval = null;

    this.init();
  }

  init() {
    this.createHTML();
    this.bindEvents();
    // Load unread count first for badge, then load full notifications
    setTimeout(() => {
      this.loadUnreadCount();
    }, 100);
    this.startAutoUpdate();
  }

  createHTML() {
    // Check if notification system already exists
    if (document.getElementById("notification-bell")) {
      return;
    }

    // Create the notification bell and dropdown HTML
    const html = `
            <div class="notification-container">
                <button class="notification-bell" id="notification-bell" aria-label="Notifications">
                    <svg class="bell-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                </button>
                
                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <div class="notification-actions">
                            <button class="action-btn mark-all-read" id="mark-all-read" title="Mark all as read">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <button class="action-btn view-all" id="view-all" title="View all notifications">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="notification-list" id="notification-list">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <span>Loading notifications...</span>
                        </div>
                    </div>
                    
                    <div class="notification-footer">
                        <a href="/backend/pages/admin-includes/notifications/all-notifications.php" class="view-all-link">
                            View All Notifications
                        </a>
                    </div>
                </div>
            </div>
        `;

    // Insert into the page
    const targetContainer =
      document.querySelector(".header-actions") ||
      document.querySelector(".admin-header") ||
      document.querySelector("body");

    if (targetContainer) {
      targetContainer.insertAdjacentHTML("beforeend", html);
    }
  }

  bindEvents() {
    // Bell click event
    document.addEventListener("click", (e) => {
      const bell = document.getElementById("notification-bell");
      const dropdown = document.getElementById("notification-dropdown");

      if (e.target.closest("#notification-bell")) {
        e.preventDefault();
        e.stopPropagation();
        this.toggleDropdown();
      } else if (!e.target.closest("#notification-dropdown")) {
        // Click outside - close dropdown
        if (this.isOpen) {
          this.closeDropdown();
        }
      }
    });

    // Mark all as read
    document.addEventListener("click", (e) => {
      if (e.target.closest("#mark-all-read")) {
        e.preventDefault();
        this.markAllAsRead();
      }
    });

    // View all notifications
    document.addEventListener("click", (e) => {
      if (e.target.closest("#view-all")) {
        e.preventDefault();
        this.navigateToAllNotifications();
      }
    });

    // Notification item clicks
    document.addEventListener("click", (e) => {
      const notifItem = e.target.closest(".notification-item");
      if (notifItem) {
        const notifId = parseInt(notifItem.dataset.notifId);
        const link = notifItem.dataset.link;

        // Mark as read
        if (!notifItem.classList.contains("read")) {
          this.markAsRead([notifId]);
        }

        // Navigate to link
        if (link) {
          if (this.isMobile()) {
            this.closeDropdown();
            setTimeout(() => {
              window.location.href = link;
            }, 100);
          } else {
            window.location.href = link;
          }
        }
      }
    });

    // Handle mobile behavior
    if (this.isMobile()) {
      // On mobile, bell click goes directly to all-notifications page
      document.addEventListener("click", (e) => {
        if (e.target.closest("#notification-bell")) {
          e.preventDefault();
          e.stopPropagation();
          window.location.href =
            "/backend/pages/admin-includes/notifications/all-notifications.php";
        }
      });
    }

    // Handle escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.isOpen) {
        this.closeDropdown();
      }
    });

    // Handle window resize
    window.addEventListener("resize", () => {
      if (this.isOpen && this.isMobile()) {
        this.closeDropdown();
      }
    });
  }

  isMobile() {
    return window.innerWidth <= 768;
  }

  toggleDropdown() {
    if (this.isMobile()) {
      // On mobile, navigate to all-notifications page
      window.location.href =
        "/backend/pages/admin-includes/notifications/all-notifications.php";
      return;
    }

    if (this.isOpen) {
      this.closeDropdown();
    } else {
      this.openDropdown();
    }
  }

  openDropdown() {
    if (this.isMobile()) return;

    const dropdown = document.getElementById("notification-dropdown");
    dropdown.classList.add("show");
    this.isOpen = true;

    // Refresh notifications when opening
    this.loadNotifications();
  }

  closeDropdown() {
    const dropdown = document.getElementById("notification-dropdown");
    dropdown.classList.remove("show");
    this.isOpen = false;
  }

  loadNotifications() {
    this.showLoading(true);

    fetch(
      "/backend/pages/admin-includes/notifications/api.php?action=get_recent",
      { credentials: "include" }
    )
      .then((response) => {
        if (response.status === 401) {
          this.showError("Please log in to view notifications.");
          this.stopAutoUpdate();
          return { success: false, error: "Unauthorized" };
        }
        return response.json();
      })
      .then((data) => {
        if (data && data.success) {
          this.notifications = data.notifications;
          this.unreadCount = data.unread_count;
          this.updateBadge();
          this.renderNotifications();
        } else {
          if (data && data.error === "Unauthorized") {
            this.showError("Please log in to view notifications.");
          } else {
            this.showError("Failed to load notifications");
          }
        }
      })
      .catch((error) => {
        console.error("Error loading notifications:", error);
        this.showError("Error loading notifications");
      })
      .finally(() => {
        this.showLoading(false);
      });
  }

  loadUnreadCount() {
    console.log("🔔 loadUnreadCount() called");
    // Load just the unread count for the badge
    fetch(
      "/backend/pages/admin-includes/notifications/api.php?action=get_unread_count",
      { credentials: "include" }
    )
      .then((response) => {
        console.log("🔔 API Response Status:", response.status);
        if (response.status === 401) {
          console.log("🔔 Unauthorized - stopping auto update");
          this.stopAutoUpdate();
          return { success: false, error: "Unauthorized" };
        }
        return response.json();
      })
      .then((data) => {
        console.log("🔔 API Response Data:", data);
        if (data && data.success !== undefined) {
          this.unreadCount = data.unread_count || 0;
          console.log("🔔 Setting unread count to:", this.unreadCount);
          this.updateBadge();
        } else {
          console.log("🔔 Invalid data format received");
        }
      })
      .catch((error) => {
        console.error("🔔 Error loading unread count:", error);
      });
  }

  renderNotifications() {
    const listContainer = document.getElementById("notification-list");

    if (this.notifications.length === 0) {
      listContainer.innerHTML = `
                <div class="no-notifications">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>No notifications yet</p>
                </div>
            `;
      return;
    }

    const html = this.notifications
      .map(
        (notif) => `
            <div class="notification-item ${notif.is_read ? "read" : "unread"}" 
                 data-notif-id="${notif.id}" 
                 data-link="${notif.link || ""}"
                 ${notif.link ? 'style="cursor: pointer;"' : ""}>
                <div class="notification-icon">
                    ${notif.icon}
                </div>
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHtml(
                      notif.title
                    )}</div>
                    <div class="notification-message">${this.escapeHtml(
                      notif.message
                    )}</div>
                    <div class="notification-time">${notif.time_ago}</div>
                </div>
                ${!notif.is_read ? '<div class="unread-indicator"></div>' : ""}
            </div>
        `
      )
      .join("");

    listContainer.innerHTML = html;
  }

  updateBadge() {
    console.log("🔔 updateBadge() called with count:", this.unreadCount);
    const badge = document.getElementById("notification-badge");
    if (!badge) {
      console.error("🔔 Badge element not found!");
      return;
    }
    console.log("🔔 Badge element found:", badge);

    if (this.unreadCount > 0) {
      const displayCount = this.unreadCount > 99 ? "99+" : this.unreadCount;
      badge.textContent = displayCount;
      badge.style.display = "block";
      console.log("🔔 Badge updated - showing count:", displayCount);
    } else {
      badge.style.display = "none";
      console.log("🔔 Badge hidden - no unread notifications");
    }
  }

  markAsRead(notifIds) {
    const formData = new FormData();
    formData.append("action", "mark_read");
    formData.append("notif_ids", JSON.stringify(notifIds));

    fetch("/backend/pages/admin-includes/notifications/api.php", {
      method: "POST",
      credentials: "include",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.unreadCount = data.unread_count;
          this.updateBadge();

          // Update notification items
          notifIds.forEach((id) => {
            const notifElement = document.querySelector(
              `[data-notif-id="${id}"]`
            );
            if (notifElement) {
              notifElement.classList.remove("unread");
              notifElement.classList.add("read");
              const indicator = notifElement.querySelector(".unread-indicator");
              if (indicator) {
                indicator.remove();
              }
            }
          });
        }
      })
      .catch((error) => {
        console.error("Error marking notifications as read:", error);
      });
  }

  markAllAsRead() {
    fetch("/backend/pages/admin-includes/notifications/api.php", {
      method: "POST",
      credentials: "include",
      body: new FormData(
        Object.assign(document.createElement("form"), {
          innerHTML: '<input name="action" value="mark_all_read">',
        })
      ),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.unreadCount = 0;
          this.updateBadge();

          // Update all notification items
          document
            .querySelectorAll(".notification-item.unread")
            .forEach((item) => {
              item.classList.remove("unread");
              item.classList.add("read");
              const indicator = item.querySelector(".unread-indicator");
              if (indicator) {
                indicator.remove();
              }
            });
        }
      })
      .catch((error) => {
        console.error("Error marking all notifications as read:", error);
      });
  }

  navigateToAllNotifications() {
    window.location.href =
      "/backend/pages/admin-includes/notifications/all-notifications.php";
  }

  showLoading(show) {
    const listContainer = document.getElementById("notification-list");
    if (show) {
      listContainer.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <span>Loading notifications...</span>
                </div>
            `;
    }
  }

  showError(message) {
    const listContainer = document.getElementById("notification-list");
    listContainer.innerHTML = `
            <div class="error-message">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/>
                    <line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/>
                </svg>
                <p>${this.escapeHtml(message)}</p>
            </div>
        `;
  }

  startAutoUpdate() {
    // Update unread count every 30 seconds
    this.updateInterval = setInterval(() => {
      // Check for due orders first (only once per hour)
      const now = new Date();
      const lastDueCheck = localStorage.getItem("lastDueOrderCheck");
      const hoursSinceLastCheck = lastDueCheck
        ? (now.getTime() - parseInt(lastDueCheck)) / (1000 * 60 * 60)
        : 999; // Force check if no previous check

      if (hoursSinceLastCheck >= 1) {
        // Check for due orders every hour
        fetch("/backend/api/check-due-orders.php", {
          credentials: "include",
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              localStorage.setItem(
                "lastDueOrderCheck",
                now.getTime().toString()
              );
              console.log("Due orders check completed");
            }
          })
          .catch((error) => {
            console.error("Error checking due orders:", error);
          });
      }

      // Update notification count
      fetch(
        "/backend/pages/admin-includes/notifications/api.php?action=get_unread_count",
        { credentials: "include" }
      )
        .then((response) => {
          if (response.status === 401) {
            this.stopAutoUpdate();
            return { success: false, error: "Unauthorized" };
          }
          return response.json();
        })
        .then((data) => {
          if (data && data.success && data.unread_count !== this.unreadCount) {
            this.unreadCount = data.unread_count;
            this.updateBadge();

            // If dropdown is open, refresh notifications
            if (this.isOpen) {
              this.loadNotifications();
            }
          }
        })
        .catch((error) => {
          console.error("Error updating notification count:", error);
          this.stopAutoUpdate();
        });
    }, 30000); // 30 seconds
  }

  stopAutoUpdate() {
    if (this.updateInterval) {
      clearInterval(this.updateInterval);
      this.updateInterval = null;
    }
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Public method to manually create notifications (for integration)
  static createNotification(type, title, message, reference_id = null) {
    const formData = new FormData();
    formData.append("action", "create");
    formData.append("type", type);
    formData.append("title", title);
    formData.append("message", message);
    if (reference_id) {
      formData.append("reference_id", reference_id);
    }

    return fetch("/backend/pages/admin-includes/notifications/api.php", {
      method: "POST",
      body: formData,
    }).then((response) => response.json());
  }
}

// Initialize notification system when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Only initialize on specific pages: dashboard, orders, refund, and bulk orders
  const allowedPages = [
    "dashboard.php",
    "order-list.php",
    "view-orders.php",
    "refund-request-lists.php",
    "bulk-order-lists.php",
    "bulk-order.php",
  ];

  const currentPage = window.location.pathname.split("/").pop();
  const isAllowedPage = allowedPages.some((page) => currentPage.includes(page));

  if (
    isAllowedPage &&
    (document.body.classList.contains("admin-page") ||
      window.location.pathname.includes("/backend/") ||
      document.querySelector(".admin-header") ||
      document.querySelector(".header-actions"))
  ) {
    window.notificationSystem = new NotificationSystem();
  }
});

// Cleanup on page unload
window.addEventListener("beforeunload", function () {
  if (window.notificationSystem) {
    window.notificationSystem.stopAutoUpdate();
  }
});

// ============================================
// ALL NOTIFICATIONS PAGE FUNCTIONALITY
// ============================================
if (document.querySelector(".notifications-page")) {
  class AllNotificationsPage {
    constructor() {
      this.selectAllCheckbox = document.getElementById("selectAll");
      this.notificationCheckboxes = document.querySelectorAll(
        ".notification-checkbox"
      );
      this.markReadBtn = document.getElementById("markSelectedRead");
      this.deleteBtn = document.getElementById("deleteSelected");

      this.init();
    }

    init() {
      // Select all functionality
      this.selectAllCheckbox?.addEventListener("change", (e) => {
        this.notificationCheckboxes.forEach((cb) => {
          cb.checked = e.target.checked;
        });
      });

      // Mark selected as read
      this.markReadBtn?.addEventListener("click", () => {
        this.markSelectedAsRead();
      });

      // Delete selected
      this.deleteBtn?.addEventListener("click", () => {
        this.deleteSelected();
      });
    }

    getSelectedIds() {
      return Array.from(this.notificationCheckboxes)
        .filter((cb) => cb.checked)
        .map((cb) => cb.value);
    }

    async markSelectedAsRead() {
      const ids = this.getSelectedIds();
      if (ids.length === 0) {
        alert("Please select notifications to mark as read");
        return;
      }

      try {
        const formData = new FormData();
        formData.append("action", "mark_read");
        formData.append("notif_ids", JSON.stringify(ids));

        await fetch("/backend/pages/admin-includes/notifications/api.php", {
          method: "POST",
          credentials: "include",
          body: formData,
        });

        location.reload();
      } catch (error) {
        console.error("Error marking as read:", error);
        alert("Failed to mark notifications as read");
      }
    }

    async deleteSelected() {
      const ids = this.getSelectedIds();
      if (ids.length === 0) {
        alert("Please select notifications to delete");
        return;
      }

      if (
        !confirm(
          `Are you sure you want to delete ${ids.length} notification(s)?`
        )
      ) {
        return;
      }

      try {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("notif_ids", JSON.stringify(ids));

        await fetch("/backend/pages/admin-includes/notifications/api.php", {
          method: "POST",
          credentials: "include",
          body: formData,
        });

        location.reload();
      } catch (error) {
        console.error("Error deleting notifications:", error);
        alert("Failed to delete notifications");
      }
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    new AllNotificationsPage();
  });
}
