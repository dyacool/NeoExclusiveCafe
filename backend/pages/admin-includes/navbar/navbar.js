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

  // Sidebar management
  function openSidebar() {
    if (window.innerWidth <= 768) {
      sidebar.classList.remove("mobile-hidden");
    }
  }

  function closeSidebar() {
    if (window.innerWidth <= 768) {
      sidebar.classList.add("mobile-hidden");
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

  // FIXED: Product dropdown with localStorage persistence
  if (productsToggle && productsDropdown) {
    productsToggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      // Toggle dropdown
      productsDropdown.classList.toggle("active");
      productsToggle.classList.toggle("active");

      // Save state to localStorage
      const isActive = productsDropdown.classList.contains("active");
      localStorage.setItem("isDropdownActive", isActive);
    });

    // Handle dropdown links - maintain state
    const dropdownLinks = document.querySelectorAll(".dropdown-link");
    dropdownLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        // If link is already active, do nothing
        if (link.classList.contains("active")) {
          return false;
        }

        // Get the target page
        const href = link.getAttribute("data-href");
        if (href) {
          // Check if navigating to a product page
          const targetIsProductPage = productPages.some((page) =>
            href.includes(page)
          );

          // If navigating to non-product page, clear dropdown state
          if (!targetIsProductPage) {
            localStorage.setItem("isDropdownActive", "false");
          }

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

  // FIXED: Clear dropdown state when navigating to non-product pages via other links
  const allNavLinks = document.querySelectorAll("a[href]:not([data-href])");
  allNavLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      const href = link.getAttribute("href");
      // If navigating to non-product page, clear dropdown state
      const targetIsProductPage = productPages.some((page) =>
        href.includes(page)
      );
      if (!targetIsProductPage) {
        localStorage.setItem("isDropdownActive", "false");
      }
    });
  });

  // Page title management
  function updatePageTitle() {
    const pageTitles = {
      "admin-homepage.php": "Dashboard",
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
  }

  // Add "Add Product" button dynamically
  if (window.location.pathname.includes("product-list.php")) {
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
  }

  // UPDATED: Set active navigation states with dropdown persistence
  function setActiveStates() {
    // Remove all active classes
    document
      .querySelectorAll(".nav-link, .footer-link, .dropdown-link")
      .forEach((link) => {
        link.classList.remove("active");
      });

    // Remove parent active state
    if (productsToggle) {
      productsToggle.classList.remove("has-active-child");
    }

    // Set active based on current page
    if (currentPage === "order-list.php") {
      const ordersLink = document.querySelector('a[href="order-list.php"]');
      if (ordersLink) ordersLink.classList.add("active");
    }

    if (currentPage === "product-list.php") {
      const productListLink = document.querySelector(
        '[data-href="product-list.php"]'
      );
      if (productListLink) {
        productListLink.classList.add("active");
        // Add active state to parent toggle
        if (productsToggle) {
          productsToggle.classList.add("has-active-child");
        }
      }
    }

    if (currentPage === "add-product.php") {
      const addProductLink = document.querySelector(
        '[data-href="add-product.php"]'
      );
      if (addProductLink) {
        addProductLink.classList.add("active");
        // Add active state to parent toggle
        if (productsToggle) {
          productsToggle.classList.add("has-active-child");
        }
      }
    }

    // Handle other pages
    const pageSelectors = {
      "admin-homepage.php": 'a[href*="admin-homepage.php"]',
      "transactions.php": 'a[href="transactions.php"]',
      "admin-blog.php": 'a[href="admin-blog.php"]',
      "admin-profile.php": 'a[href="admin-profile.php"]',
      "archive.php": 'a[href="archive.php"]',
      "user-content-settings.php": 'a[href="user-content-settings.php"]',
      "userShop.php": 'a[href*="product-dashboard.php"]',
    };

    const selector = pageSelectors[currentPage];
    if (selector) {
      const activeLink = document.querySelector(selector);
      if (activeLink) activeLink.classList.add("active");
    }
  }

  // Prevent active links from being clicked (except logout)
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

  // Handle window resize
  function handleResize() {
    if (window.innerWidth > 768) {
      sidebar.classList.remove("mobile-hidden");
    } else {
      sidebar.classList.add("mobile-hidden");
    }
  }

  // FIXED: Only restore dropdown state if on a product page
  if (isProductPage && localStorage.getItem("isDropdownActive") === "true") {
    if (productsDropdown && productsToggle) {
      productsDropdown.classList.add("active");
      productsToggle.classList.add("active");
    }
  } else if (!isProductPage) {
    // Clear dropdown state if not on product page
    localStorage.setItem("isDropdownActive", "false");
    // Ensure dropdown is closed
    if (productsDropdown && productsToggle) {
      productsDropdown.classList.remove("active");
      productsToggle.classList.remove("active");
    }
  }

  // Initialize everything
  updatePageTitle();
  setActiveStates();
  preventActiveClicks();
  handleResize();

  // Listen for resize
  window.addEventListener("resize", handleResize);

  // Special fix for order-list page navigation
  const ordersLink = document.querySelector('a[href="order-list.php"]');
  if (ordersLink) {
    ordersLink.addEventListener("click", () => {
      // Close sidebar after navigation on mobile
      setTimeout(() => {
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      }, 100);
    });
  }
});
