document.addEventListener("DOMContentLoaded", () => {
  // Core elements
  const sidebar = document.querySelector(".sidebar");
  const mobileMenuToggle = document.querySelector(".mobile-menu-toggle");
  const floatingCloseBtn = document.querySelector(".floating-close-btn");
  const productsToggle = document.querySelector(".products-toggle");
  const productsDropdown = document.querySelector(".products-dropdown");

  // Get current page
  const currentPage = window.location.pathname.split("/").pop();

  // Define product pages
  const productPages = ["product-list.php", "add-product.php"];
  const isProductPage = productPages.includes(currentPage);

  // Enhanced state management with better key
  const DROPDOWN_STATE_KEY = "navbar_products_dropdown_state";
  const SIDEBAR_STATE_KEY = "navbar_sidebar_state";

  // Flag to prevent multiple restoration calls
  let dropdownStateRestored = false;

  // Sidebar management with state persistence
  function openSidebar() {
    if (window.innerWidth <= 768) {
      sidebar.classList.remove("mobile-hidden");
      localStorage.setItem(SIDEBAR_STATE_KEY, "open");
    }
  }

  function closeSidebar() {
    if (window.innerWidth <= 768) {
      sidebar.classList.add("mobile-hidden");
      localStorage.setItem(SIDEBAR_STATE_KEY, "closed");
    }
  }

  // Restore sidebar state on mobile
  function restoreSidebarState() {
    if (window.innerWidth <= 768) {
      const savedState = localStorage.getItem(SIDEBAR_STATE_KEY);
      if (savedState === "open") {
        sidebar.classList.remove("mobile-hidden");
      } else {
        sidebar.classList.add("mobile-hidden");
      }
    }
  }

  // Mobile menu toggle - OPEN sidebar
  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      openSidebar();
    });
  }

  // Floating close button - CLOSE sidebar
  if (floatingCloseBtn) {
    floatingCloseBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeSidebar();
    });
  }

  // Close sidebar when clicking outside
  document.addEventListener("click", (e) => {
    if (window.innerWidth <= 768) {
      if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
        closeSidebar();
      }
    }
  });

  // Prevent sidebar from closing when clicking inside
  sidebar.addEventListener("click", (e) => {
    e.stopPropagation();
  });

  // Enhanced Product dropdown with better state management
  function toggleDropdown(shouldOpen = null) {
    if (!productsToggle || !productsDropdown) return;

    const isCurrentlyActive = productsDropdown.classList.contains("active");
    const newState = shouldOpen !== null ? shouldOpen : !isCurrentlyActive;

    // Only make changes if the state is actually different
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

    // Prevent multiple calls - only restore once per page load
    if (dropdownStateRestored) return;

    const savedState = localStorage.getItem(DROPDOWN_STATE_KEY);
    // Always restore if saved state is open, regardless of page type
    const shouldRestoreOpen = savedState === "open";

    // Apply state without animation to prevent flickering
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

    // Enhanced dropdown link handling
    const dropdownLinks = document.querySelectorAll(".dropdown-link");
    dropdownLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        // If link is already active, do nothing
        if (link.classList.contains("active")) {
          return false;
        }

        const href = link.getAttribute("data-href");
        if (href) {
          // Keep dropdown open when navigating - don't change state
          // The dropdown state will be preserved as is

          // Navigate to new page
          window.location.href = href;
        }
      });
    });

    // Prevent dropdown from closing when clicking inside
    productsDropdown.addEventListener("click", (e) => {
      e.stopPropagation();
    });
  }

  // Enhanced navigation link handling
  const allNavLinks = document.querySelectorAll("a[href]:not([data-href])");
  allNavLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      const href = link.getAttribute("href");

      // Don't interfere with external links or logout
      if (href.startsWith("http") || href.includes("logout")) {
        return;
      }

      // Don't change dropdown state when navigating to any page
      // The dropdown will remain open if it was open, closed if it was closed
      // Only the toggle click can change the state

      // Close mobile sidebar after navigation
      if (window.innerWidth <= 768) {
        setTimeout(() => {
          closeSidebar();
        }, 100);
      }
    });
  });

  // Enhanced page title management
  function updatePageTitle() {
    const pageTitles = {
      "admin-homepage.php": "Dashboard",
      "dashboard.php": "Dashboard",
      "order-list.php": "Order Management",
      "product-list.php": "Product Management",
      "add-product.php": "Add Product",
      "transactions.php": "Transactions",
      "admin-blog.php": "Blog",
      "admin-profile.php": "Profile",
      "archive.php": "Archive",
      "userShop.php": "View Shop",
      "user-content-settings.php": "User Page Contents Setting",
      "manage-carousel-images.php": "Dashboard Hero Images",
      "manage-carousel-settings.php": "Dashboard Hero Text",
      "admin-service-edit.php": "Service",
      "promotions-settings.php": "Coupons & Promotions",
      "about-settings.php": "User About",
      "terms-conditions-settings.php": "User Terms & Conditions",
      "privacy-policy-settings.php": "User Privacy Policy",
      "footer-settings.php": "Footer Settings",
      "calendar.php": "Calendar Management",
    };

    const pageTitle = pageTitles[currentPage] || "Neo Cafe Admin";

    // Update desktop title
    const desktopTitle = document.getElementById("page-title");
    if (desktopTitle) {
      desktopTitle.textContent = pageTitle;
    }

    // Update mobile title
    const mobileTitle = document.getElementById("mobile-page-title");
    if (mobileTitle) {
      mobileTitle.textContent = pageTitle;
    }

    // Update document title for better UX
    document.title = `${pageTitle} - Neo Cafe Admin`;
  }

  // Enhanced Add Product button for product-list page
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
      // Don't change dropdown state - let it preserve current state
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
  }

  // Enhanced active state management
  function setActiveStates() {
    // Remove all active classes from links only
    document
      .querySelectorAll(".nav-link, .footer-link, .dropdown-link")
      .forEach((link) => {
        link.classList.remove("active");
      });

    // Only remove parent active state if we're not on a product page
    if (productsToggle && !productPages.includes(currentPage)) {
      productsToggle.classList.remove("has-active-child");
    }

    // Set active states based on current page
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
    };

    // Handle product pages separately
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
      // Handle other pages
      const selector = pageActiveSelectors[currentPage];
      if (selector) {
        const activeLink = document.querySelector(selector);
        if (activeLink) {
          activeLink.classList.add("active");
        }
      }
    }

    // Special handling for "View Shop" link
    if (window.location.pathname.includes("product-dashboard.php")) {
      const shopLink = document.querySelector(
        'a[href*="product-dashboard.php"]'
      );
      if (shopLink) shopLink.classList.add("active");
    }
  }

  // Enhanced click prevention for active links
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

  // Enhanced window resize handling
  function handleResize() {
    if (window.innerWidth > 768) {
      sidebar.classList.remove("mobile-hidden");
      // Clear mobile sidebar state when switching to desktop
      localStorage.removeItem(SIDEBAR_STATE_KEY);
    } else {
      // Restore mobile sidebar state or default to closed
      restoreSidebarState();
    }
  }

  // Page visibility change handler to maintain state
  function handleVisibilityChange() {
    if (document.visibilityState === "visible") {
      // Only restore sidebar state on mobile, don't touch dropdown
      if (window.innerWidth <= 768) {
        restoreSidebarState();
      }
    }
  }

  // Initialize everything in correct order
  function initialize() {
    // Add loading class to prevent flicker during state restoration
    document.body.classList.add("navbar-loading");

    updatePageTitle();
    addProductButton();
    setActiveStates();
    preventActiveClicks();
    handleResize();

    // Restore dropdown state immediately and only once on page load
    restoreDropdownState();

    // Restore sidebar state for mobile
    if (window.innerWidth <= 768) {
      restoreSidebarState();
    }

    // Remove loading class after a brief delay
    setTimeout(() => {
      document.body.classList.remove("navbar-loading");
    }, 100);
  }

  // Event listeners
  window.addEventListener("resize", handleResize);
  document.addEventListener("visibilitychange", handleVisibilityChange);

  // Initialize
  initialize();

  // Note: Dropdown state is now only controlled by manual toggle clicks
  // No automatic cleanup - the dropdown stays in whatever state the user set it to
});
