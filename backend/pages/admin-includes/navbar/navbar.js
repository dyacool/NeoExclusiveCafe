document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const mobileMenuToggle = document.querySelector(".mobile-menu-toggle");
  const floatingCloseBtn = document.querySelector(".floating-close-btn");
  const productsToggle = document.querySelector(".products-toggle");
  const productsDropdown = document.querySelector(".products-dropdown");

  const currentPage = window.location.pathname.split("/").pop();

  const productPages = ["product-list.php", "add-product.php"];
  const isProductPage = productPages.includes(currentPage);

  const DROPDOWN_STATE_KEY = "navbar_products_dropdown_state";
  const SIDEBAR_STATE_KEY = "navbar_sidebar_state";

  let dropdownStateRestored = false;

  function openSidebar() {
    if (window.innerWidth <= 1024) {
      sidebar.classList.remove("mobile-hidden");
      localStorage.setItem(SIDEBAR_STATE_KEY, "open");
    }
  }

  function closeSidebar() {
    if (window.innerWidth <= 1024) {
      sidebar.classList.add("mobile-hidden");
      localStorage.setItem(SIDEBAR_STATE_KEY, "closed");
    }
  }

  function restoreSidebarState() {
    if (window.innerWidth <= 1024) {
      const savedState = localStorage.getItem(SIDEBAR_STATE_KEY);
      if (savedState === "open") {
        sidebar.classList.remove("mobile-hidden");
      } else {
        sidebar.classList.add("mobile-hidden");
      }
    }
  }

  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      openSidebar();
    });
  }

  if (floatingCloseBtn) {
    floatingCloseBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeSidebar();
    });
  }

  document.addEventListener("click", (e) => {
    if (window.innerWidth <= 1024) {
      if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
        closeSidebar();
      }
    }
  });

  sidebar.addEventListener("click", (e) => {
    e.stopPropagation();
  });

  function toggleDropdown(shouldOpen = null) {
    if (!productsToggle || !productsDropdown) return;

    const isCurrentlyActive = productsDropdown.classList.contains("active");
    const newState = shouldOpen !== null ? shouldOpen : !isCurrentlyActive;

    if (newState !== isCurrentlyActive) {
      if (newState) {
        productsDropdown.classList.add("active");
        productsToggle.classList.add("active");
        localStorage.setItem(DROPDOWN_STATE_KEY, "open");
      } else {
        productsDropdown.classList.remove("active");
        productsToggle.classList.remove("active");
        localStorage.setItem(DROPDOWN_STATE_KEY, "closed");
      }
    }
  }

  function restoreDropdownState() {
    if (!productsToggle || !productsDropdown) return;

    if (dropdownStateRestored) return;

    const savedState = localStorage.getItem(DROPDOWN_STATE_KEY);
    const shouldRestoreOpen = savedState === "open";

    if (shouldRestoreOpen) {
      productsDropdown.classList.add("active");
      productsToggle.classList.add("active");
    } else {
      productsDropdown.classList.remove("active");
      productsToggle.classList.remove("active");
    }

    dropdownStateRestored = true;
  }

  if (productsToggle && productsDropdown) {
    productsToggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleDropdown();
    });

    const dropdownLinks = document.querySelectorAll(".dropdown-link");
    dropdownLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (link.classList.contains("active")) {
          return false;
        }

        const href = link.getAttribute("data-href");
        if (href) {
          window.location.href = href;
        }
      });
    });

    productsDropdown.addEventListener("click", (e) => {
      e.stopPropagation();
    });
  }

  const allNavLinks = document.querySelectorAll("a[href]:not([data-href])");
  allNavLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      const href = link.getAttribute("href");

      if (href.startsWith("http") || href.includes("logout")) {
        return;
      }

      if (window.innerWidth <= 1024) {
        setTimeout(() => {
          closeSidebar();
        }, 100);
      }
    });
  });

  function updatePageTitle() {
    const pageTitles = {
      "admin-homepage.php": "Dashboard",
      "dashboard.php": "Dashboard",
      "order-list.php": "Order Management",
      "product-list.php": "Product Management",
      "add-product.php": "Add Product",
      "transactions.php": "Sales Report",
      "admin-blog.php": "Blog",
      "admin-profile.php": "Profile",
      "archive.php": "Archive",
      "userShop.php": "View Shop",
      "user-content-settings.php": "Content Management",
      "manage-carousel-images.php": "Dashboard Images",
      "manage-carousel-settings.php": "Dashboard Text",
      "admin-service-edit.php": "Service",
      "promotions-settings.php": "Coupons & Promotions",
      "about-settings.php": "About Management",
      "terms-and-condition-management.php": "Terms & Conditions Management",
      "privacy-policy-management.php": "Privacy Policy Management",
      "footer-settings.php": "Footer Management",
      "calendar.php": "Calendar Management",
      "refund-request-lists.php": "Refund Requests",
      "bulk-order-lists.php": "Bulk Orders",
      "bulk-order.php": "Bulk Order Details",
      "view-orders.php": "Order Details",
      "refund-details.php": "Refund Request Detail",
      "activity-logs.php": "Activity Logs",
      "all-notifications.php": "Notifications",
      "manage-categories.php": "Product Categories",
      "delivery-locations.php": "Delivery Locations",
      "cb-knowledge-settings.php": "Chatbot Knowledge",
      "reset-password.php": "Reset Password",
      "blog-details.php": "Blog Post Details",
      "admin-blog-createpost.php": "Create Blog Post",
      "expense.php": "Expense Tracker",
      "bulk-payment-setup.php": "Bulk Payments",
    };

    const pageTitle = pageTitles[currentPage] || "Neo Cafe Admin";

    const desktopTitle = document.getElementById("page-title");
    if (desktopTitle) {
      desktopTitle.textContent = pageTitle;
    }

    const mobileTitle = document.getElementById("mobile-page-title");
    if (mobileTitle) {
      mobileTitle.textContent = pageTitle;
    }

    document.title = `${pageTitle} - Neo Cafe Admin`;
  }

  function addProductButton() {
    if (!window.location.pathname.includes("product-list.php")) return;

    let headerActions = document.querySelector(".header-actions");

    if (!headerActions) {
      headerActions = document.createElement("div");
      headerActions.className = "header-actions";
      const header = document.querySelector(".header");
      if (header) {
        header.appendChild(headerActions);
      }
    }

    const addProductButton = document.createElement("button");
    addProductButton.className = "btn add-product-button action-button";
    addProductButton.onclick = () => {
      window.location.href = "add-product.php";
    };

    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", "20");
    svg.setAttribute("height", "20");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("fill", "none");
    svg.setAttribute("stroke", "currentColor");
    svg.setAttribute("stroke-width", "2");

    const line1 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    line1.setAttribute("x1", "12");
    line1.setAttribute("y1", "5");
    line1.setAttribute("x2", "12");
    line1.setAttribute("y2", "19");

    const line2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    line2.setAttribute("x1", "5");
    line2.setAttribute("y1", "12");
    line2.setAttribute("x2", "19");
    line2.setAttribute("y2", "12");

    svg.appendChild(line1);
    svg.appendChild(line2);
    addProductButton.appendChild(svg);
    addProductButton.appendChild(document.createTextNode(" Add Product"));

    headerActions.innerHTML = "";
    headerActions.appendChild(addProductButton);

    let mobileHeaderActions = document.querySelector(".mobile-header-actions");

    if (!mobileHeaderActions) {
      mobileHeaderActions = document.createElement("div");
      mobileHeaderActions.className = "mobile-header-actions";
      const mobileHeaderBottom = document.querySelector(
        ".mobile-header-bottom"
      );
      if (mobileHeaderBottom) {
        mobileHeaderBottom.appendChild(mobileHeaderActions);
      }
    }

    const mobileAddProductButton = document.createElement("button");
    mobileAddProductButton.className =
      "btn add-product-button action-button mobile-action-button";
    mobileAddProductButton.onclick = () => {
      window.location.href = "add-product.php";
    };

    const mobileSvg = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "svg"
    );
    mobileSvg.setAttribute("width", "20");
    mobileSvg.setAttribute("height", "20");
    mobileSvg.setAttribute("viewBox", "0 0 24 24");
    mobileSvg.setAttribute("fill", "none");
    mobileSvg.setAttribute("stroke", "currentColor");
    mobileSvg.setAttribute("stroke-width", "2");

    const mobileLine1 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    mobileLine1.setAttribute("x1", "12");
    mobileLine1.setAttribute("y1", "5");
    mobileLine1.setAttribute("x2", "12");
    mobileLine1.setAttribute("y2", "19");

    const mobileLine2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    mobileLine2.setAttribute("x1", "5");
    mobileLine2.setAttribute("y1", "12");
    mobileLine2.setAttribute("x2", "19");
    mobileLine2.setAttribute("y2", "12");

    mobileSvg.appendChild(mobileLine1);
    mobileSvg.appendChild(mobileLine2);
    mobileAddProductButton.appendChild(mobileSvg);
    mobileAddProductButton.appendChild(document.createTextNode(" Add Product"));

    mobileHeaderActions.innerHTML = "";
    mobileHeaderActions.appendChild(mobileAddProductButton);
  }

  function addBlogPostButton() {
    if (!window.location.pathname.includes("admin-blog.php")) return;

    let headerActions = document.querySelector(".header-actions");

    if (!headerActions) {
      headerActions = document.createElement("div");
      headerActions.className = "header-actions";
      const header = document.querySelector(".header");
      if (header) {
        header.appendChild(headerActions);
      }
    }

    const addPostButton = document.createElement("button");
    addPostButton.className = "btn add-product-button action-button";
    addPostButton.onclick = () => {
      window.location.href = "admin-blog-createpost.php";
    };

    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", "20");
    svg.setAttribute("height", "20");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("fill", "none");
    svg.setAttribute("stroke", "currentColor");
    svg.setAttribute("stroke-width", "2");

    const line1 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    line1.setAttribute("x1", "12");
    line1.setAttribute("y1", "5");
    line1.setAttribute("x2", "12");
    line1.setAttribute("y2", "19");

    const line2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    line2.setAttribute("x1", "5");
    line2.setAttribute("y1", "12");
    line2.setAttribute("x2", "19");
    line2.setAttribute("y2", "12");

    svg.appendChild(line1);
    svg.appendChild(line2);
    addPostButton.appendChild(svg);
    addPostButton.appendChild(document.createTextNode(" Add Post"));

    headerActions.innerHTML = "";
    headerActions.appendChild(addPostButton);

    let mobileHeaderActions = document.querySelector(".mobile-header-actions");

    if (!mobileHeaderActions) {
      mobileHeaderActions = document.createElement("div");
      mobileHeaderActions.className = "mobile-header-actions";
      const mobileHeaderBottom = document.querySelector(
        ".mobile-header-bottom"
      );
      if (mobileHeaderBottom) {
        mobileHeaderBottom.appendChild(mobileHeaderActions);
      }
    }

    const mobileAddPostButton = document.createElement("button");
    mobileAddPostButton.className =
      "btn add-product-button action-button mobile-action-button";
    mobileAddPostButton.onclick = () => {
      window.location.href = "admin-blog-createpost.php";
    };

    const mobileSvg = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "svg"
    );
    mobileSvg.setAttribute("width", "20");
    mobileSvg.setAttribute("height", "20");
    mobileSvg.setAttribute("viewBox", "0 0 24 24");
    mobileSvg.setAttribute("fill", "none");
    mobileSvg.setAttribute("stroke", "currentColor");
    mobileSvg.setAttribute("stroke-width", "2");

    const mobileLine1 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    mobileLine1.setAttribute("x1", "12");
    mobileLine1.setAttribute("y1", "5");
    mobileLine1.setAttribute("x2", "12");
    mobileLine1.setAttribute("y2", "19");

    const mobileLine2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    mobileLine2.setAttribute("x1", "5");
    mobileLine2.setAttribute("y1", "12");
    mobileLine2.setAttribute("x2", "19");
    mobileLine2.setAttribute("y2", "12");

    mobileSvg.appendChild(mobileLine1);
    mobileSvg.appendChild(mobileLine2);
    mobileAddPostButton.appendChild(mobileSvg);
    mobileAddPostButton.appendChild(document.createTextNode(" Add Post"));

    mobileHeaderActions.innerHTML = "";
    mobileHeaderActions.appendChild(mobileAddPostButton);
  }

  function addNotificationButton() {
    if (
      !window.location.pathname.includes("dashboard.php") &&
      !window.location.pathname.includes("admin-homepage.php")
    )
      return;

    let headerActions = document.querySelector(".header-actions");

    if (!headerActions) {
      headerActions = document.createElement("div");
      headerActions.className = "header-actions";
      const header = document.querySelector(".header");
      if (header) {
        header.appendChild(headerActions);
      }
    }

    // Create notification container
    const notificationContainer = document.createElement("div");
    notificationContainer.className = "notification-bell-container";
    notificationContainer.id = "navbarNotificationContainer";

    // Create notification button
    const notificationButton = document.createElement("button");
    notificationButton.className = "btn notification-button action-button";
    notificationButton.id = "navbarNotificationBtn";
    notificationButton.setAttribute("aria-label", "Notifications");

    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", "20");
    svg.setAttribute("height", "20");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("fill", "none");
    svg.setAttribute("stroke", "currentColor");
    svg.setAttribute("stroke-width", "2");
    svg.setAttribute("stroke-linecap", "round");
    svg.setAttribute("stroke-linejoin", "round");

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", "M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9");

    const path2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "path"
    );
    path2.setAttribute("d", "M13.73 21a2 2 0 0 1-3.46 0");

    svg.appendChild(path);
    svg.appendChild(path2);
    notificationButton.appendChild(svg);

    // Create notification badge
    const badge = document.createElement("span");
    badge.className = "notification-badge";
    badge.id = "navbarNotificationBadge";
    badge.style.display = "none";
    notificationButton.appendChild(badge);

    // Create dropdown
    const dropdown = document.createElement("div");
    dropdown.className = "notification-dropdown";
    dropdown.id = "navbarNotificationDropdown";
    dropdown.innerHTML = `
      <div class="notification-dropdown-header">
        <h3>Notifications</h3>
        <button class="mark-all-read-btn" id="navbarMarkAllRead">Mark all as read</button>
      </div>
      <div class="notification-list" id="navbarNotificationList">
        <div class="notification-loading">Loading...</div>
      </div>
      <div class="notification-dropdown-footer">
        <a href="/backend/pages/notifications/all-notifications.php" class="view-all-btn">View all notifications</a>
      </div>
    `;

    notificationContainer.appendChild(notificationButton);
    notificationContainer.appendChild(dropdown);

    headerActions.innerHTML = "";
    headerActions.appendChild(notificationContainer);

    // Initialize notification functionality
    initNotificationDropdown();

    // Mobile version
    let mobileHeaderActions = document.querySelector(".mobile-header-actions");

    if (!mobileHeaderActions) {
      mobileHeaderActions = document.createElement("div");
      mobileHeaderActions.className = "mobile-header-actions";
      const mobileHeaderBottom = document.querySelector(
        ".mobile-header-bottom"
      );
      if (mobileHeaderBottom) {
        mobileHeaderBottom.appendChild(mobileHeaderActions);
      }
    }

    // Create mobile notification container
    const mobileNotificationContainer = document.createElement("div");
    mobileNotificationContainer.className =
      "notification-bell-container mobile-notification-container";
    mobileNotificationContainer.id = "mobileNotificationContainer";

    const mobileNotificationButton = document.createElement("button");
    mobileNotificationButton.className =
      "btn notification-button action-button mobile-action-button";
    mobileNotificationButton.id = "mobileNotificationBtn";
    mobileNotificationButton.setAttribute("aria-label", "Notifications");

    const mobileSvg = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "svg"
    );
    mobileSvg.setAttribute("width", "20");
    mobileSvg.setAttribute("height", "20");
    mobileSvg.setAttribute("viewBox", "0 0 24 24");
    mobileSvg.setAttribute("fill", "none");
    mobileSvg.setAttribute("stroke", "currentColor");
    mobileSvg.setAttribute("stroke-width", "2");
    mobileSvg.setAttribute("stroke-linecap", "round");
    mobileSvg.setAttribute("stroke-linejoin", "round");

    const mobilePath = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "path"
    );
    mobilePath.setAttribute("d", "M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9");

    const mobilePath2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "path"
    );
    mobilePath2.setAttribute("d", "M13.73 21a2 2 0 0 1-3.46 0");

    mobileSvg.appendChild(mobilePath);
    mobileSvg.appendChild(mobilePath2);
    mobileNotificationButton.appendChild(mobileSvg);

    const mobileBadge = document.createElement("span");
    mobileBadge.className = "notification-badge";
    mobileBadge.id = "mobileNotificationBadge";
    mobileBadge.style.display = "none";
    mobileNotificationButton.appendChild(mobileBadge);

    // Create mobile dropdown
    const mobileDropdown = document.createElement("div");
    mobileDropdown.className =
      "notification-dropdown mobile-notification-dropdown";
    mobileDropdown.id = "mobileNotificationDropdown";
    mobileDropdown.innerHTML = `
      <div class="notification-dropdown-header">
        <h3>Notifications</h3>
        <button class="mark-all-read-btn" id="mobileMarkAllRead">Mark all as read</button>
      </div>
      <div class="notification-list" id="mobileNotificationList">
        <div class="notification-loading">Loading...</div>
      </div>
      <div class="notification-dropdown-footer">
        <a href="/backend/pages/notifications/all-notifications.php" class="view-all-btn">View all notifications</a>
      </div>
    `;

    mobileNotificationContainer.appendChild(mobileNotificationButton);
    mobileNotificationContainer.appendChild(mobileDropdown);

    mobileHeaderActions.innerHTML = "";
    mobileHeaderActions.appendChild(mobileNotificationContainer);

    // Initialize mobile notification dropdown
    initMobileNotificationDropdown();
  }

  function initNotificationDropdown() {
    const btn = document.getElementById("navbarNotificationBtn");
    const dropdown = document.getElementById("navbarNotificationDropdown");
    const badge = document.getElementById("navbarNotificationBadge");
    const mobileBadge = document.getElementById("mobileNotificationBadge");
    const list = document.getElementById("navbarNotificationList");
    const markAllReadBtn = document.getElementById("navbarMarkAllRead");

    if (!btn || !dropdown) return;

    let isOpen = false;

    // Toggle dropdown
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (isOpen) {
        dropdown.classList.remove("show");
        isOpen = false;
      } else {
        dropdown.classList.add("show");
        isOpen = true;
        loadNotifications();
      }
    });

    // Close on outside click
    document.addEventListener("click", (e) => {
      if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.classList.remove("show");
        isOpen = false;
      }
    });

    // Mark all as read
    markAllReadBtn.addEventListener("click", () => {
      fetch(
        "/backend/pages/admin-includes/notifications/notification.php?action=mark_all_as_read",
        {
          method: "POST",
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            loadNotifications();
            updateBadge(0);
          }
        })
        .catch((error) => console.error("Error:", error));
    });

    // Load notifications
    function loadNotifications() {
      list.innerHTML = '<div class="notification-loading">Loading...</div>';

      fetch(
        "/backend/pages/admin-includes/notifications/notification.php?action=get_notifications&limit=10",
        {
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.notifications) {
            renderNotifications(data.notifications);
          } else {
            list.innerHTML =
              '<div class="notification-empty">No notifications</div>';
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          list.innerHTML =
            '<div class="notification-empty">Error loading notifications</div>';
        });
    }

    // Render notifications
    function renderNotifications(notifications) {
      if (notifications.length === 0) {
        list.innerHTML =
          '<div class="notification-empty">No notifications</div>';
        return;
      }

      const html = notifications
        .map((notif) => {
          const icon = getNotificationIcon(notif.notif_type);
          const timeAgo = formatTimeAgo(notif.created_at);

          return `
          <div class="notification-item ${notif.is_read ? "" : "unread"}" 
               data-id="${notif.notif_id}" 
               data-link="${notif.notif_link || ""}"
               onclick="handleNotificationClick(${notif.notif_id}, '${
            notif.notif_link || ""
          }', ${notif.is_read})">
            <div class="notification-content">
              <div class="notification-title">${escapeHtml(
                notif.notif_title
              )}</div>
              <div class="notification-message">${escapeHtml(
                notif.notif_message
              )}</div>
              <div class="notification-time">${timeAgo}</div>
            </div>
          </div>
        `;
        })
        .join("");

      list.innerHTML = html;
    }

    // Update badge
    function updateBadge(count) {
      if (count > 0) {
        badge.textContent = count > 99 ? "99+" : count;
        badge.style.display = "block";
        if (mobileBadge) {
          mobileBadge.textContent = count > 99 ? "99+" : count;
          mobileBadge.style.display = "block";
        }
      } else {
        badge.style.display = "none";
        if (mobileBadge) {
          mobileBadge.style.display = "none";
        }
      }
    }

    // Get notification icon
    function getNotificationIcon(type) {
      // Return empty string - no icons
      return "";
    }

    // Format time ago
    function formatTimeAgo(timestamp) {
      const time = new Date(timestamp).getTime();
      const diff = Date.now() - time;
      const seconds = Math.floor(diff / 1000);
      const minutes = Math.floor(seconds / 60);
      const hours = Math.floor(minutes / 60);
      const days = Math.floor(hours / 24);

      if (seconds < 60) return "Just now";
      if (minutes < 60) return minutes + " min ago";
      if (hours < 24) return hours + " hr ago";
      if (days < 7) return days + " days ago";
      return new Date(timestamp).toLocaleDateString();
    }

    // Escape HTML
    function escapeHtml(text) {
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    }

    // Load unread count initially and periodically
    function loadUnreadCount() {
      fetch(
        "/backend/pages/admin-includes/notifications/notification.php?action=get_unread_count",
        {
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            updateBadge(data.count);
          }
        })
        .catch((error) => console.error("Error:", error));
    }

    loadUnreadCount();
    setInterval(loadUnreadCount, 5000); // Update every 5 seconds for faster updates
  }

  // Mobile notification dropdown functionality
  function initMobileNotificationDropdown() {
    const btn = document.getElementById("mobileNotificationBtn");
    const dropdown = document.getElementById("mobileNotificationDropdown");
    const badge = document.getElementById("mobileNotificationBadge");
    const list = document.getElementById("mobileNotificationList");
    const markAllReadBtn = document.getElementById("mobileMarkAllRead");

    if (!btn || !dropdown) return;

    let isOpen = false;

    // Toggle dropdown
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (isOpen) {
        dropdown.classList.remove("show");
        isOpen = false;
      } else {
        dropdown.classList.add("show");
        isOpen = true;
        loadMobileNotifications();
      }
    });

    // Close on outside click
    document.addEventListener("click", (e) => {
      if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.classList.remove("show");
        isOpen = false;
      }
    });

    // Mark all as read
    markAllReadBtn.addEventListener("click", () => {
      fetch(
        "/backend/pages/admin-includes/notifications/notification.php?action=mark_all_as_read",
        {
          method: "POST",
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            loadMobileNotifications();
            updateMobileBadge(0);
          }
        })
        .catch((error) => console.error("Error:", error));
    });

    // Load notifications
    function loadMobileNotifications() {
      list.innerHTML = '<div class="notification-loading">Loading...</div>';

      fetch(
        "/backend/pages/admin-includes/notifications/notification.php?action=get_notifications&limit=10",
        {
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.notifications) {
            renderMobileNotifications(data.notifications);
          } else {
            list.innerHTML =
              '<div class="notification-empty">No notifications</div>';
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          list.innerHTML =
            '<div class="notification-empty">Error loading notifications</div>';
        });
    }

    // Render notifications
    function renderMobileNotifications(notifications) {
      if (notifications.length === 0) {
        list.innerHTML =
          '<div class="notification-empty">No notifications</div>';
        return;
      }

      const html = notifications
        .map((notif) => {
          const timeAgo = formatTimeAgo(notif.created_at);

          return `
          <div class="notification-item ${notif.is_read ? "" : "unread"}" 
               data-id="${notif.notif_id}" 
               data-link="${notif.notif_link || ""}"
               onclick="handleNotificationClick(${notif.notif_id}, '${
            notif.notif_link || ""
          }', ${notif.is_read})">
            <div class="notification-content">
              <div class="notification-title">${escapeHtml(
                notif.notif_title
              )}</div>
              <div class="notification-message">${escapeHtml(
                notif.notif_message
              )}</div>
              <div class="notification-time">${timeAgo}</div>
            </div>
          </div>
        `;
        })
        .join("");

      list.innerHTML = html;
    }

    // Update badge
    function updateMobileBadge(count) {
      if (count > 0) {
        badge.textContent = count > 99 ? "99+" : count;
        badge.style.display = "block";
      } else {
        badge.style.display = "none";
      }
    }

    // Format time ago
    function formatTimeAgo(timestamp) {
      const time = new Date(timestamp).getTime();
      const diff = Date.now() - time;
      const seconds = Math.floor(diff / 1000);
      const minutes = Math.floor(seconds / 60);
      const hours = Math.floor(minutes / 60);
      const days = Math.floor(hours / 24);

      if (seconds < 60) return "Just now";
      if (minutes < 60) return minutes + " min ago";
      if (hours < 24) return hours + " hr ago";
      if (days < 7) return days + " days ago";
      return new Date(timestamp).toLocaleDateString();
    }

    // Escape HTML
    function escapeHtml(text) {
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    }

    // Load unread count initially and periodically
    function loadMobileUnreadCount() {
      fetch(
        "/backend/pages/admin-includes/notifications/notification.php?action=get_unread_count",
        {
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            updateMobileBadge(data.count);
          }
        })
        .catch((error) => console.error("Error:", error));
    }

    loadMobileUnreadCount();
    setInterval(loadMobileUnreadCount, 5000); // Update every 5 seconds
  }

  // Global function to handle notification clicks
  window.handleNotificationClick = function (notifId, link, isRead) {
    // Mark as read if unread
    if (!isRead) {
      fetch(
        "/backend/pages/admin-includes/notifications/notification.php?action=mark_as_read",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ids: [notifId] }),
          credentials: "include",
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success && link) {
            window.location.href = link;
          }
        })
        .catch((error) => console.error("Error:", error));
    } else if (link) {
      window.location.href = link;
    }
  };

  function setActiveStates() {
    document
      .querySelectorAll(".nav-link, .footer-link, .dropdown-link")
      .forEach((link) => {
        link.classList.remove("active");
      });

    if (productsToggle && !productPages.includes(currentPage)) {
      productsToggle.classList.remove("has-active-child");
    }

    const pageActiveSelectors = {
      "order-list.php": 'a[href*="order-list.php"]',
      "admin-homepage.php":
        'a[href*="dashboard.php"], a[href*="admin-homepage.php"]',
      "dashboard.php":
        'a[href*="dashboard.php"], a[href*="admin-homepage.php"]',
      "transactions.php": 'a[href*="transactions.php"]',
      "admin-blog.php": 'a[href*="admin-blog.php"]',
      "admin-profile.php": 'a[href*="admin-profile.php"]',
      "archive.php": 'a[href*="archive.php"]',
      "user-content-settings.php": 'a[href*="user-content-settings.php"]',
      "calendar.php": 'a[href*="calendar.php"]',
      "promotions-settings.php": 'a[href*="promotions-settings.php"]',
      "refund-request-lists.php": 'a[href*="refund-request-lists.php"]',
      "bulk-order-lists.php": 'a[href*="bulk-order-lists.php"]',
    };

    if (currentPage === "product-list.php") {
      const productListLink = document.querySelector(
        '[data-href*="product-list.php"]'
      );
      if (productListLink) {
        productListLink.classList.add("active");
        if (productsToggle) {
          productsToggle.classList.add("has-active-child");
        }
      }
    } else if (currentPage === "add-product.php") {
      const addProductLink = document.querySelector(
        '[data-href*="add-product.php"]'
      );
      if (addProductLink) {
        addProductLink.classList.add("active");
        if (productsToggle) {
          productsToggle.classList.add("has-active-child");
        }
      }
    } else {
      const selector = pageActiveSelectors[currentPage];
      if (selector) {
        const activeLink = document.querySelector(selector);
        if (activeLink) {
          activeLink.classList.add("active");
        }
      }
    }

    if (window.location.pathname.includes("product-dashboard.php")) {
      const shopLink = document.querySelector(
        'a[href*="product-dashboard.php"]'
      );
      if (shopLink) shopLink.classList.add("active");
    }
  }

  function preventActiveClicks() {
    document
      .querySelectorAll(".nav-link, .footer-link, .dropdown-link")
      .forEach((link) => {
        link.addEventListener("click", (e) => {
          if (
            link.classList.contains("active") &&
            !link.classList.contains("logout")
          ) {
            e.preventDefault();
            return false;
          }
        });
      });
  }

  function handleResize() {
    if (window.innerWidth > 1024) {
      sidebar.classList.remove("mobile-hidden");
      localStorage.removeItem(SIDEBAR_STATE_KEY);
    } else {
      restoreSidebarState();
    }
  }

  function handleVisibilityChange() {
    if (document.visibilityState === "visible") {
      if (window.innerWidth <= 1024) {
        restoreSidebarState();
      }
    }
  }

  function initialize() {
    document.body.classList.add("navbar-loading");

    updatePageTitle();
    addProductButton();
    addBlogPostButton();
    addNotificationButton();
    setActiveStates();
    preventActiveClicks();
    handleResize();

    restoreDropdownState();

    if (window.innerWidth <= 1024) {
      restoreSidebarState();
    }

    setTimeout(() => {
      document.body.classList.remove("navbar-loading");
    }, 100);
  }

  window.addEventListener("resize", handleResize);
  document.addEventListener("visibilitychange", handleVisibilityChange);

  initialize();
});
