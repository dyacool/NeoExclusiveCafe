<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user information if logged in - check for both user and admin sessions
$user = null;
$is_user_logged_in = isset($_SESSION['user_id']);
$is_admin_logged_in = isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';

if ($is_user_logged_in) {
    // Use session data for user
    $user = [
        'firstname' => $_SESSION['user_firstname'] ?? '',
        'lastname' => $_SESSION['user_lastname'] ?? '',
        'profile_image' => $_SESSION['user_profile_image'] ?? ''
    ];

    // Fallback: fetch from database if profile image (or names) missing
    if (($user['profile_image'] ?? '') === '' || ($user['firstname'] ?? '') === '' || ($user['lastname'] ?? '') === '') {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        if ($user_id > 0) {
            // Include database connection
            $db_path = __DIR__ . '/../database.php';
            if (file_exists($db_path)) {
                require_once $db_path;
                if (isset($conn) && $conn instanceof mysqli) {
                    $stmt = mysqli_prepare($conn, "SELECT firstname, lastname, profile_image, cloud_url, cloud_public_id FROM users WHERE id = ?");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        if ($result && ($row = mysqli_fetch_assoc($result))) {
                            $user['firstname'] = $user['firstname'] !== '' ? $user['firstname'] : ($row['firstname'] ?? '');
                            $user['lastname'] = $user['lastname'] !== '' ? $user['lastname'] : ($row['lastname'] ?? '');
                            
                            // Prioritize Cloudinary URL over legacy profile_image
                            if (!empty(trim($row['cloud_url'] ?? ''))) {
                                $user['profile_image'] = trim($row['cloud_url']);
                            } elseif ($user['profile_image'] === '' && !empty(trim($row['profile_image'] ?? ''))) {
                                $user['profile_image'] = trim($row['profile_image']);
                            }
                            
                            // Update session for future requests
                            if (!empty($user['profile_image'])) {
                                $_SESSION['user_profile_image'] = $user['profile_image'];
                            }
                            if (!empty($user['firstname'])) {
                                $_SESSION['user_firstname'] = $user['firstname'];
                            }
                            if (!empty($user['lastname'])) {
                                $_SESSION['user_lastname'] = $user['lastname'];
                            }
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
} elseif ($is_admin_logged_in) {
    // Use session data for admin
    $user = [
        'firstname' => $_SESSION['admin_firstname'] ?? '',
        'lastname' => $_SESSION['admin_lastname'] ?? '',
        'profile_image' => $_SESSION['admin_profile_image'] ?? ''
    ];
}

$current_page = basename($_SERVER['PHP_SELF']);

// Ensure database connection for categories with comprehensive error handling
$navbar_conn = null;
if (isset($conn) && $conn instanceof mysqli) {
    try {
        // Check if existing connection is still valid
        if ($conn->ping() && $conn->thread_id !== null) {
            $navbar_conn = $conn;
        }
    } catch (Exception $e) {
        // Existing connection is not usable
        error_log("Navbar existing connection error: " . $e->getMessage());
    }
}

// If no valid connection, try to create a new one
if (!$navbar_conn) {
    $db_path = $_SERVER['DOCUMENT_ROOT'] . '/backend/pages/admin-includes/database.php';
    if (file_exists($db_path)) {
        try {
            // Temporarily store the old conn value
            $old_conn = isset($conn) ? $conn : null;
            
            // Include database.php to get a fresh connection
            require_once $db_path;
            
            // Check if we got a valid new connection
            if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
                $navbar_conn = $conn;
            } else {
                // Restore old conn value if new connection failed
                $conn = $old_conn;
            }
        } catch (Exception $e) {
            error_log("Navbar new connection error: " . $e->getMessage());
            // Restore old conn value
            $conn = $old_conn;
        }
    }
}
?>
<link rel="stylesheet" href="/frontend/user-includes/navbar/customer-navigation.css">
<script>
// Immediate classification - runs before DOM ready to prevent flash
(function() {
    const isUserDashboard = window.location.pathname.includes('user-dashboard.php') || 
                           window.location.pathname.endsWith('/') || 
                           window.location.pathname.includes('home');
    
    if (isUserDashboard) {
        document.documentElement.classList.add('homepage-animation');
        document.body.classList.add('homepage-animation');
    } else {
        document.documentElement.classList.add('non-homepage');
        document.body.classList.add('non-homepage');
        
        // Immediately ensure navbar is visible
        const style = document.createElement('style');
        style.textContent = `
            .announcement-bar, .main-nav {
                opacity: 1 !important;
                visibility: visible !important;
                transition: none !important;
            }
            .page-entry-animation {
                display: none !important;
            }
        `;
        document.head.appendChild(style);
    }
})();
</script>
<div class="header-wrapper">
    <!-- Page Entry Animation Container -->
    <div class="page-entry-animation">
        <div class="logo-animation">
            <img src="/assets/images/user-logo.png" alt="NeoCafe Logo" class="animated-logo">
        </div>
    </div>

    <div class="announcement-bar">
        <div class="announcement-text"> Products Available for same-day and pre-order purchases.</div>
    </div>

    <nav class="main-nav">
        <!-- Hamburger menu for mobile/tablet -->
        <div class="nav-content">
            <button class="mobile-menu-toggle" aria-label="Toggle Menu">
                <span class="hamburger-icon">☰</span>
            </button>
            <div class="nav-left">
                <a href="../../../frontend/pages/home/user-dashboard.php" class="nav-link smooth-nav <?php echo $current_page === 'user-dashboard.php' ? 'active' : ''; ?>" data-target="../../../frontend/pages/home/user-dashboard.php">
                    <span class="link-text">Home</span>
                    <span class="link-underline"></span>
                </a>
                <a href="/frontend/pages/bulk/bulk-form.php"class="nav-link smooth-nav <?php echo $current_page === 'bulk-forn.php' ? 'active' : ''; ?>" data-target="../../../frontend/pages/about/bulk-form.php">
                    <span class="link-text">Bulk Order</span>
                    <span class="link-underline"></span>
                </a>

                <div class="products-container">
                    <a href="/frontend/pages/products/product-dashboard.php" class="nav-link smooth-nav <?php echo $current_page === 'product-dashboard.php' ? 'active' : ''; ?>" data-target="/frontend/pages/products/product-dashboard.php">
                        <span class="link-text">Products</span>
                        <span class="link-underline"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dropdown-arrow">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </a>
                    <div class="products-dropdown">
                        <!-- All Products link -->
                        <a href="/frontend/pages/products/product-dashboard.php">All Products</a>
                        <?php
                        // Fetch categories from database using the navbar connection
                        if ($navbar_conn) {
                            try {
                                $category_query = "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
                                $category_result = mysqli_query($navbar_conn, $category_query);
                                
                                if ($category_result && mysqli_num_rows($category_result) > 0) {
                                    while ($category = mysqli_fetch_assoc($category_result)) {
                                        $category_url = '/frontend/pages/products/product-dashboard.php?category=' . urlencode($category['slug']);
                                        echo '<a href="' . htmlspecialchars($category_url) . '">' . htmlspecialchars($category['name']) . '</a>';
                                    }
                                }
                            } catch (Exception $e) {
                                // Log error and continue without categories
                                error_log("Navbar categories error: " . $e->getMessage());
                            }
                        }
                        ?>
                    </div>
                    <!-- Mobile Products Dropdown - Inside nav-left for better visibility -->
                    <div class="mobile-products-dropdown">
                        <!-- All Products link for mobile -->
                        <a href="/frontend/pages/products/product-dashboard.php" class="mobile-dropdown-item">All Products</a>
                        <?php
                        // Fetch categories for mobile dropdown using the navbar connection
                        if ($navbar_conn) {
                            try {
                                $mobile_category_query = "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
                                $mobile_category_result = mysqli_query($navbar_conn, $mobile_category_query);
                                
                                if ($mobile_category_result && mysqli_num_rows($mobile_category_result) > 0) {
                                    while ($category = mysqli_fetch_assoc($mobile_category_result)) {
                                        $category_url = '/frontend/pages/products/product-dashboard.php?category=' . urlencode($category['slug']);
                                        echo '<a href="' . htmlspecialchars($category_url) . '" class="mobile-dropdown-item">' . htmlspecialchars($category['name']) . '</a>';
                                    }
                                }
                            } catch (Exception $e) {
                                // Log error and continue without categories
                                error_log("Navbar mobile categories error: " . $e->getMessage());
                            }
                        }
                        ?>
                    </div>
                </div>
                <a href="../../../frontend/pages/blog/blog-dashboard.php" class="nav-link smooth-nav <?php echo $current_page === 'blog-page.php' ? 'active' : ''; ?>" data-target="../../../frontend/pages/blog/blog-dashboard.php">
                    <span class="link-text">Blog</span>
                    <span class="link-underline"></span>
                </a>
                <a href="../../../frontend/pages/about/about-page.php" class="nav-link smooth-nav <?php echo $current_page === 'about-page.php' ? 'active' : ''; ?>" data-target="../../../frontend/pages/about/about-page.php">
                    <span class="link-text">About</span>
                    <span class="link-underline"></span>
                </a>
            </div>

            <div class="nav-center">
                <a href="../../../frontend/pages/home/user-dashboard.php" class="logo-container">
                    <img src="https://res.cloudinary.com/dvdccumbs/image/upload/v1761594924/neocafegoldlogo_i4a6cz.png" alt="NeoCafe Logo" class="logo">
                </a>
            </div>

            <div class="nav-right">
                <div class="search-container">
                    <button class="search-toggle" aria-label="Toggle search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                    <!-- Desktop Search Box - Positioned relative to search icon -->
                    <form action="../../../frontend/search/search-results.php" method="GET" class="desktop-search-box">
                        <input type="text" name="query" placeholder="Search..." class="search-input" required>
                        <button type="submit" class="search-btn">Search</button>
                    </form>
                </div>
                <a href="<?php echo isset($_SESSION['user_id']) ? '../../../frontend/pages/cart/cart.php' : '../../../frontend/login/user/login-signup.php'; ?>" class="cart-link">
                    <div class="icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon cart-icon">
                            <path d="M5 8h14l1 13H4L5 8z"></path>
                            <path d="M7 8V6a5 5 0 0110 0v2"></path>
                            <path d="M3 8h18"></path>
                        </svg>
                        <span class="icon-effect"></span>
                    </div>
                </a>
                <div class="notification-container">
                    <?php if ($is_user_logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
                    <a href="#" class="notification-link" aria-label="View notifications">
                        <div class="icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon notification-icon">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span class="badge" id="notifCount" style="display: none;"></span>
                            <span class="icon-effect"></span>
                        </div>
                    </a>
                    <div class="notification-dropdown" id="notifDropdown">
                        <div class="dropdown-header">
                            <h3>Notifications</h3>
                            <button id="markAllRead" class="mark-read" title="Mark all as read">Mark all as read</button>
                        </div>
                        <ul id="notificationList" class="notification-list">
                            <!-- Notifications will appear dynamically -->
                        </ul>
                        <div class="no-notifications" id="noNotifications">
                            <p>No new notifications.</p>
                        </div>
                        <div class="dropdown-footer">
                            <a href="/frontend/pages/notifications/notifications.php" class="view-all-link">View All</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="../../../frontend/login/user/login-signup.php" class="notification-link" aria-label="Login to view notifications">
                        <div class="icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon notification-icon">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span class="icon-effect"></span>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>

                <style>
                    .hide-on-login {
                        display: none !important;
                    }
                </style>
                <?php if ($user): ?>
                    <div class="profile-container auth-buttons">
                    <a href="<?php echo $is_admin_logged_in ? '/backend/pages/homepage/admin-homepage.php' : '/frontend/pages/profile/profile.php'; ?>"class="profile-link" id="profile-trigger">
                            <div class="profile-avatar">
                                <?php 
                                $sessionProfileImage = isset($user['profile_image']) ? trim($user['profile_image']) : '';
                                if ($sessionProfileImage !== '') {
                                    if ($sessionProfileImage[0] !== '/') { $sessionProfileImage = '/' . $sessionProfileImage; }
                                    echo '<img src="' . htmlspecialchars($sessionProfileImage) . '" alt="Profile Image">';
                                } else {
                                    echo '<span class="profile-initial">' . substr(htmlspecialchars($user['firstname']), 0, 1) . '</span>';
                                }
                                ?>
                            </div>
                            <span class="profile-name"><?php echo htmlspecialchars($user['firstname']); ?></span>
                        </a>
                        <div class="dropdown-menu">
                            <?php if ($is_admin_logged_in): ?>
                                <a href="/backend/pages/homepage/admin-homepage.php">Admin Panel</a>
                                <a href="/backend/login/admin/logout.php">Logout</a>
                            <?php else: ?>
                                <a href="/frontend/pages/profile/profile.php">Profile</a>
                                <a href="/frontend/pages/profile/account-settings.php">Account Settings</a>
                                <a href="/frontend/pages/blog/user-blog-post.php">View Post</a>
                                <a href="/frontend/login/user/logout.php">Logout</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../../login/user/login-signup.php" class="login-link auth-buttons">
                        <span>Login</span>
                    </a>
                <?php endif; ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Check if we're on the login/signup page
                        const isLoginPage = window.location.pathname.includes('/frontend/login/user/login-signup.php');
                        
                        // Get all auth buttons
                        const authButtons = document.querySelectorAll('.auth-buttons');
                        
                        // Add or remove the hide class based on the current page
                        authButtons.forEach(button => {
                            if (isLoginPage) {
                                button.classList.add('hide-on-login');
                            } else {
                                button.classList.remove('hide-on-login');
                            }
                        });
                    });
                </script>
            </div>
        </div>

        <!-- Mobile Search Box - Positioned below the navigation -->
        <form action="../../../frontend/search/search-results.php" method="GET" class="mobile-search-box">
            <input type="text" name="query" placeholder="Search..." class="search-input" required>
            <button type="submit" class="search-btn">Search</button>
        </form>
    </nav>
</div>


<div class="wrapper">
    <script>
        // SIMPLE IMMEDIATE IMPLEMENTATION - NO DOMContentLoaded delays
        
        // Wait just a moment for HTML to be ready, then attach handlers immediately
        setTimeout(() => {
            // SEARCH FUNCTIONALITY
            const searchToggle = document.querySelector('.search-toggle');
            const desktopSearchBox = document.querySelector('.desktop-search-box');
            const mobileSearchBox = document.querySelector('.mobile-search-box');
            
            if (searchToggle) {
                searchToggle.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (window.innerWidth <= 1024) {
                        // Mobile
                        if (mobileSearchBox) {
                            mobileSearchBox.classList.toggle('active');
                        }
                    } else {
                        // Desktop
                        if (desktopSearchBox) {
                            desktopSearchBox.classList.toggle('active');
                        }
                    }
                };
            }
            
            // MOBILE MENU FUNCTIONALITY (1024px and below)
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const navLeft = document.querySelector('.nav-left');
            
            if (mobileMenuToggle && navLeft) {
                mobileMenuToggle.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle mobile menu
                    navLeft.classList.toggle('active');
                    mobileMenuToggle.classList.toggle('active');
                    
                    // Toggle hamburger icon
                    const hamburgerIcon = mobileMenuToggle.querySelector('.hamburger-icon');
                    if (hamburgerIcon) {
                        hamburgerIcon.textContent = navLeft.classList.contains('active') ? '✕' : '☰';
                    }
                };
            }
            
            // PRODUCTS DROPDOWN FOR MOBILE (1024px and below)
            const productsContainer = document.querySelector('.products-container');
            const productsDropdown = document.querySelector('.mobile-products-dropdown');
            
            if (productsContainer && productsDropdown) {
                const productsLink = productsContainer.querySelector('.nav-link');
                if (productsLink) {
                    productsLink.onclick = function(e) {
                        // Only prevent default on mobile (1024px and below)
                        if (window.innerWidth <= 1024) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // Toggle mobile products dropdown
                            productsDropdown.classList.toggle('active');
                            
                            // Toggle arrow direction
                            const arrow = productsLink.querySelector('.dropdown-arrow');
                            if (arrow) {
                                arrow.style.transform = productsDropdown.classList.contains('active') 
                                    ? 'rotate(180deg)' 
                                    : 'rotate(0deg)';
                            }
                        }
                    };
                }
            }
            
            // NOTIFICATION FUNCTIONALITY  
            const notifLink = document.querySelector('.notification-link');
            const notifDropdown = document.getElementById('notifDropdown');
            
            if (notifLink && notifDropdown) {
                notifLink.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Check if mobile device (1024px breakpoint)
                    if (window.innerWidth <= 1024) {
                        // Mobile: redirect to notifications page instead of dropdown
                        window.location.href = '/frontend/pages/notifications/notifications.php';
                        return;
                    }
                    
                    // Desktop: show dropdown
                    const isActive = notifDropdown.classList.toggle('active');
                    
                    // Fetch notifications when dropdown is opened
                    if (isActive) {
                        fetchNotifications();
                    }
                };
            }
            
            // NOTIFICATION FETCHING FUNCTION
            function fetchNotifications() {
                const notificationList = document.getElementById("notificationList");
                const noNotifications = document.getElementById("noNotifications");
                const notifCount = document.getElementById("notifCount");
                
                if (!notificationList || !noNotifications) return;
                
                // Use global function if available, otherwise fetch directly
                if (window.fetchDropdownNotifications) {
                    window.fetchDropdownNotifications()
                        .then(notifications => {
                            updateNotificationDisplay(notifications);
                        })
                        .catch(error => {
                            console.error('Error fetching notifications:', error);
                            showNoNotifications();
                        });
                } else {
                    // Direct fetch
                    fetch('/frontend/pages/notifications/fetch-notif.php?dropdown=true')
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                updateNotificationDisplay(data.notifications || []);
                            } else {
                                showNoNotifications();
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching notifications:', error);
                            showNoNotifications();
                        });
                }
                
                function updateNotificationDisplay(notifications) {
                    notificationList.innerHTML = '';
                    
                    if (notifications && notifications.length > 0) {
                        // Hide "no notifications" message
                        noNotifications.style.display = "none";
                        
                        // Show notifications
                        notifications.forEach(notif => {
                            const listItem = document.createElement("li");
                            listItem.className = `notification-item ${notif.is_read ? "read" : "unread"}`;
                            listItem.dataset.notificationId = notif.id;
                            
                            const title = document.createElement("div");
                            title.className = "notification-title";
                            title.textContent = notif.title;

                            const message = document.createElement("div");
                            message.className = "notification-message";
                            message.textContent = notif.message.substring(0, 50) + (notif.message.length > 50 ? '...' : '');

                            const time = document.createElement("div");
                            time.className = "notification-time";
                            time.textContent = new Date(notif.created_at).toLocaleString([], { short: 'short' });

                            const contentDiv = document.createElement('div');
                            contentDiv.className = 'notification-content';
                            contentDiv.appendChild(title);
                            contentDiv.appendChild(message);
                            contentDiv.appendChild(time);

                            listItem.appendChild(contentDiv);
                            notificationList.appendChild(listItem);
                        });

                        // Update notification count
                        const unreadCount = notifications.filter(n => !n.is_read).length;
                        if (notifCount) {
                            if (unreadCount > 0) {
                                notifCount.textContent = unreadCount;
                                notifCount.style.display = "block";
                            } else {
                                notifCount.style.display = "none";
                            }
                        }
                    } else {
                        showNoNotifications();
                    }
                }
                
                function showNoNotifications() {
                    notificationList.innerHTML = '';
                    noNotifications.style.display = "block";
                    if (notifCount) {
                        notifCount.style.display = "none";
                    }
                }
            }
            
            // CLOSE DROPDOWNS WHEN CLICKING OUTSIDE
            document.onclick = function(e) {
                // Close mobile menu
                if (navLeft && mobileMenuToggle && 
                    !navLeft.contains(e.target) && 
                    !mobileMenuToggle.contains(e.target)) {
                    navLeft.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    
                    const hamburgerIcon = mobileMenuToggle.querySelector('.hamburger-icon');
                    if (hamburgerIcon) {
                        hamburgerIcon.textContent = '☰';
                    }
                }
                
                // Close mobile products dropdown
                if (productsDropdown && productsContainer && 
                    !productsContainer.contains(e.target)) {
                    productsDropdown.classList.remove('active');
                    const arrow = productsContainer.querySelector('.dropdown-arrow');
                    if (arrow) {
                        arrow.style.transform = 'rotate(0deg)';
                    }
                }
                
                // Close search
                if (desktopSearchBox && searchToggle && !searchToggle.contains(e.target) && !desktopSearchBox.contains(e.target)) {
                    desktopSearchBox.classList.remove('active');
                }
                if (mobileSearchBox && searchToggle && !searchToggle.contains(e.target) && !mobileSearchBox.contains(e.target)) {
                    mobileSearchBox.classList.remove('active');
                }
                
                // Close notifications
                if (notifDropdown && notifLink && !notifLink.contains(e.target) && !notifDropdown.contains(e.target)) {
                    notifDropdown.classList.remove('active');
                }
            };
            
        }, 100); // Just 100ms delay to ensure HTML is parsed
        
    </script>
    
    <!-- Include the global notification JavaScript -->
    <script src="/frontend/pages/notifications/notifications.js"></script>
</div>