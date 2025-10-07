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
      "transactions.php": "Transactions",
      "admin-blog.php": "Blog",
      "admin-profile.php": "Profile",
      "archive.php": "Archive",
      "userShop.php": "View Shop",
      "user-content-settings.php": "Content Management",
      "manage-carousel-images.php": "Dashboard Hero Images",
      "manage-carousel-settings.php": "Dashboard Hero Text",
      "admin-service-edit.php": "Service",
      "promotions-settings.php": "Coupons & Promotions",
      "about-settings.php": "User About Management",
      "terms-conditions-settings.php": "User Terms & Conditions Management",
      "privacy-policy-settings.php": "User Privacy Policy Management",
      "footer-settings.php": "Footer Settings Management",
      "calendar.php": "Calendar Management",
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
