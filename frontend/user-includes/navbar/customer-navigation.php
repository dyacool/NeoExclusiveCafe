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
                    $stmt = mysqli_prepare($conn, "SELECT firstname, lastname, profile_image FROM users WHERE id = ?");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        if ($result && ($row = mysqli_fetch_assoc($result))) {
                            $user['firstname'] = $user['firstname'] !== '' ? $user['firstname'] : ($row['firstname'] ?? '');
                            $user['lastname'] = $user['lastname'] !== '' ? $user['lastname'] : ($row['lastname'] ?? '');
                            $user['profile_image'] = $user['profile_image'] !== '' ? $user['profile_image'] : (trim($row['profile_image'] ?? ''));
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
?>
<link rel="stylesheet" href="/frontend/user-includes/navbar/customer-navigation.css">
<link rel="stylesheet" href="/frontend/pages/notifications/notifications.css">
<div class="header-wrapper">
    <!-- Page Entry Animation Container -->
    <div class="page-entry-animation">
        <div class="logo-animation">
            <img src="/assets/images/user-logo.png" alt="NeoCafe Logo" class="animated-logo">
        </div>
    </div>

    <div class="announcement-bar">
        <div class="announcement-text">All products are only available for pre-orders!</div>
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
                <div class="products-container">
                    <a href="/frontend/pages/products/products-categories.php" class="nav-link smooth-nav <?php echo $current_page === 'product-dashboard.php' ? 'active' : ''; ?>" data-target="/frontend/pages/products/product-dashboard.php">
                        <span class="link-text">Products</span>
                        <span class="link-underline"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dropdown-arrow">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </a>
                    <div class="products-dropdown">
                        <a href="/frontend/pages/products/product-dashboard.php">Same Day Order</a>
                        <a href="/frontend/pages/products/weekly-product.php">For Delivery</a>
                        <a href="/frontend/pages/products/user-products.php">For Pick Up</a>
                        <a href="/frontend/pages/bulk/bulk-form.php">Bulk Order</a>
                    </div>
                    <!-- Mobile Products Dropdown - Inside nav-left for better visibility -->
                    <div class="mobile-products-dropdown">
                        <a href="/frontend/pages/products/product-dashboard.php" class="mobile-dropdown-item">Special Offer</a>
                        <a href="/frontend/pages/products/weekly-product.php" class="mobile-dropdown-item">For Delivery</a>
                        <a href="/frontend/pages/products/user-products.php" class="mobile-dropdown-item">For Pick Up</a>
                        <a href="/frontend/pages/bulk/bulk-form.php" class="mobile-dropdown-item">Bulk Order</a>

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
                    <img src="/assets/images/user-logo.png" alt="NeoCafe Logo" class="logo">
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
                <a href="<?php echo isset($_SESSION['user_id']) ? '../../../frontend/pages/cart/shopping-cart-preorder.php' : '../../../frontend/login/user/login-signup.php'; ?>" class="cart-link">
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
                                <a href="/frontend/pages/blog/blog-list.php">View Post</a>
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
        document.addEventListener('DOMContentLoaded', function() {
            // ===== VARIABLES =====
            const pageEntryAnimation = document.querySelector('.page-entry-animation');
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const navLeft = document.querySelector('.nav-left');
            const hamburgerIcon = document.querySelector('.hamburger-icon');
            const notifLink = document.querySelector('.notification-link');
            const notifDropdown = document.getElementById('notifDropdown');
            const markAllReadBtn = document.getElementById('markAllRead');
            const profileTrigger = document.getElementById('profile-trigger');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            // ===== SEARCH FUNCTIONALITY =====
            const searchToggle = document.querySelector('.search-toggle');
            const mobileSearchBox = document.querySelector('.mobile-search-box');
            const desktopSearchBox = document.querySelector('.desktop-search-box');
            const mobileSearchInput = mobileSearchBox.querySelector('.search-input');
            const desktopSearchInput = desktopSearchBox.querySelector('.search-input');
            
            // ===== PRODUCTS DROPDOWN FUNCTIONALITY =====
            const productsContainer = document.querySelector('.products-container');
            const productsLink = productsContainer ? productsContainer.querySelector('.nav-link') : null;
            const mobileProductsDropdown = document.querySelector('.mobile-products-dropdown');
            const desktopProductsDropdown = productsContainer ? productsContainer.querySelector('.products-dropdown') : null;
            
            // Add click event to toggle search visibility
            searchToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Toggle active class on the search toggle button
                searchToggle.classList.toggle('active');
                
                // Different behavior based on screen width
                if (window.innerWidth <= 992) {
                    // Mobile behavior - toggle below navigation
                    mobileSearchBox.classList.toggle('active');
                    
                    // Focus on search input when opened
                    if (mobileSearchBox.classList.contains('active')) {
                        setTimeout(() => {
                            mobileSearchInput.focus();
                        }, 100);
                    }
                } else {
                    // Desktop behavior - popup
                    desktopSearchBox.classList.toggle('active');
                    
                    // Focus on search input when opened
                    if (desktopSearchBox.classList.contains('active')) {
                        setTimeout(() => {
                            desktopSearchInput.focus();
                        }, 100);
                    }
                }
            });
            
            // Close desktop search when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchToggle.contains(e.target) && !desktopSearchBox.contains(e.target) && window.innerWidth > 992) {
                    desktopSearchBox.classList.remove('active');
                    searchToggle.classList.remove('active');
                }
            });
            
            // Close search when pressing Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (window.innerWidth <= 992) {
                        mobileSearchBox.classList.remove('active');
                    } else {
                        desktopSearchBox.classList.remove('active');
                    }
                    searchToggle.classList.remove('active');
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                // Hide both search boxes when resizing across breakpoint
                if (window.innerWidth <= 992) {
                    desktopSearchBox.classList.remove('active');
                } else {
                    mobileSearchBox.classList.remove('active');
                }
                searchToggle.classList.remove('active');
                
                // Hide products dropdown when resizing across breakpoint
                if (window.innerWidth <= 992) {
                    // On mobile, hide desktop dropdown
                    if (desktopProductsDropdown) {
                        desktopProductsDropdown.style.display = 'none';
                    }
                } else {
                    // On desktop, hide mobile dropdown and restore desktop dropdown
                    if (mobileProductsDropdown) {
                        mobileProductsDropdown.classList.remove('active');
                    }
                    if (desktopProductsDropdown) {
                        desktopProductsDropdown.style.display = '';
                    }
                }
            });
            
            // ===== PRODUCTS DROPDOWN FUNCTIONALITY =====
            if (productsLink && mobileProductsDropdown) {
                productsLink.addEventListener('click', function(e) {
                    if (window.innerWidth <= 992) {
                        e.preventDefault(); // Prevent navigation on mobile
                        mobileProductsDropdown.classList.toggle('active');
                        
                        // Close search dropdown if open
                        mobileSearchBox.classList.remove('active');
                        searchToggle.classList.remove('active');
                    }
                    // On desktop, let the hover CSS handle the dropdown
                });
            }
            
            // Close mobile products dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 992 && mobileProductsDropdown && 
                    !productsContainer.contains(e.target) && 
                    !mobileProductsDropdown.contains(e.target)) {
                    mobileProductsDropdown.classList.remove('active');
                }
            });
            
            // Close products dropdown when pressing Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && window.innerWidth <= 992) {
                    if (mobileProductsDropdown) {
                        mobileProductsDropdown.classList.remove('active');
                    }
                }
            });
            
            // Hide/show appropriate dropdowns based on screen size
            function handleProductsDropdownVisibility() {
                if (window.innerWidth <= 992) {
                    // Mobile: hide desktop dropdown
                    if (desktopProductsDropdown) {
                        desktopProductsDropdown.style.display = 'none';
                    }
                } else {
                    // Desktop: hide mobile dropdown and show desktop dropdown
                    if (mobileProductsDropdown) {
                        mobileProductsDropdown.classList.remove('active');
                    }
                    if (desktopProductsDropdown) {
                        desktopProductsDropdown.style.display = '';
                    }
                }
            }
            
            // Initial setup
            handleProductsDropdownVisibility();
            
            // Handle window resize for products dropdown
            window.addEventListener('resize', handleProductsDropdownVisibility);
            
            // ===== PAGE ENTRY ANIMATION =====
            // Only show animation on user-dashboard.php page
            const isUserDashboard = window.location.pathname.includes('user-dashboard.php') || 
                                   window.location.pathname.endsWith('/') || 
                                   window.location.pathname.includes('home');
            
            if (isUserDashboard) {
                // Check if this is the first visit to the page in this session
                if (!sessionStorage.getItem('navigationAnimationPlayed')) {
                    pageEntryAnimation.classList.add('play-animation');
                    
                    // Hide the animation after it completes and show navbar content
                    setTimeout(() => {
                        pageEntryAnimation.classList.remove('play-animation');
                        pageEntryAnimation.classList.add('animation-completed');
                        
                        // Show navbar content then trigger animations
                        showNavbarContent();
                        setTimeout(() => {
                            triggerNavbarAnimations();
                        }, 100); // Small delay to ensure content is visible first
                    }, 2000); // Match this timing with your CSS animation duration
                    
                    // Mark that we've played the animation in this session
                    sessionStorage.setItem('navigationAnimationPlayed', 'true');
                } else {
                    // If we've already played the animation, show content immediately
                    pageEntryAnimation.classList.add('animation-completed');
                    showNavbarContent();
                    // Don't trigger animations on subsequent visits
                }
            } else {
                // On other pages, immediately hide logo animation and show navbar content
                pageEntryAnimation.classList.add('animation-completed');
                showNavbarContent();
                // Don't trigger animations on other pages
            }
            
            // Function to show navbar content
            function showNavbarContent() {
                const announcementBar = document.querySelector('.announcement-bar');
                const mainNav = document.querySelector('.main-nav');
                
                if (announcementBar) {
                    announcementBar.classList.add('show-content');
                }
                
                if (mainNav) {
                    mainNav.classList.add('show-content');
                }
            }
            
            // Function to trigger navbar animations
            function triggerNavbarAnimations() {
                const announcementText = document.querySelector('.announcement-text');
                const mainNav = document.querySelector('.main-nav');
                
                if (announcementText) {
                    announcementText.classList.add('animate-in');
                }
                
                if (mainNav) {
                    mainNav.classList.add('animate-in');
                }
            }
            
            // ===== NOTIFICATIONS =====
            function fetchNotifications() {
                // Use the global function if available, otherwise define locally
                if (window.fetchDropdownNotifications) {
                    window.fetchDropdownNotifications()
                        .then(notifications => {
                            const notificationList = document.getElementById("notificationList");
                            notificationList.innerHTML = '';
                            
                            if (notifications && notifications.length > 0) {
                                document.getElementById("noNotifications").style.display = "none";
                                
                                notifications.forEach(notif => {
                                    const listItem = document.createElement("li");
                                    listItem.className = `notification-item ${notif.is_read ? "read" : "unread"}`;
                                    listItem.dataset.notificationId = notif.id;
                                    
                                    // Click handler to open modal
                                    listItem.addEventListener('click', () => {
                                        if (window.handleNotificationClick) {
                                            window.handleNotificationClick(notif.id);
                                        }
                                    });

                                    const title = document.createElement("div");
                                    title.className = "notification-title";
                                    title.textContent = notif.title;

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

                                    listItem.appendChild(contentDiv);
                                    notificationList.appendChild(listItem);
                                });

                                const unreadCount = notifications.filter(n => !n.is_read).length;
                                if (unreadCount > 0) {
                                    document.getElementById("notifCount").textContent = unreadCount;
                                    document.getElementById("notifCount").style.display = "block";
                                } else {
                                    document.getElementById("notifCount").style.display = "none";
                                }
                            } else {
                                document.getElementById("noNotifications").style.display = "block";
                                document.getElementById("notifCount").style.display = "none";
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching notifications:', error);
                            document.getElementById("noNotifications").innerHTML = '<p>Could not load notifications.</p>';
                            document.getElementById("notifCount").style.display = "none";
                        });
                } else {
                    // Fallback to direct fetch if global function not available
                fetch('/frontend/pages/notifications/fetch-notif.php?dropdown=true')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const notificationList = document.getElementById("notificationList");
                    notificationList.innerHTML = '';

                    if (data.status === "success") {
                        if (data.notifications && data.notifications.length > 0) {
                            document.getElementById("noNotifications").style.display = "none";
                            
                            data.notifications.forEach(notif => {
                                const listItem = document.createElement("li");
                                listItem.className = `notification-item ${notif.is_read ? "read" : "unread"}`;
                                listItem.dataset.notificationId = notif.id;
                                
                                listItem.addEventListener('click', () => {
                                    handleNotificationClick(notif.id);
                                });

                                const title = document.createElement("div");
                                title.className = "notification-title";
                                title.textContent = notif.title;

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

                                listItem.appendChild(contentDiv);
                                notificationList.appendChild(listItem);
                            });

                            const unreadCount = data.notifications.filter(n => !n.is_read).length;
                            if (unreadCount > 0) {
                                document.getElementById("notifCount").textContent = unreadCount;
                                document.getElementById("notifCount").style.display = "block";
                            } else {
                                document.getElementById("notifCount").style.display = "none";
                            }
                        } else {
                            document.getElementById("noNotifications").style.display = "block";
                            document.getElementById("notifCount").style.display = "none";
                        }
                    } else {
                        throw new Error(data.message || 'Failed to fetch notifications');
                    }
                })
                .catch(error => {
                    console.error('Error fetching notifications:', error);
                    document.getElementById("noNotifications").innerHTML = '<p>Could not load notifications.</p>';
                    document.getElementById("notifCount").style.display = "none";
                });
                }
            }

            // Handle notification click - mark as read and show modal
            function handleNotificationClick(notificationId) {
                if (window.handleNotificationClick) {
                    window.handleNotificationClick(notificationId);
                } else {
                    // Fallback implementation
                fetch('/frontend/pages/notifications/mark-notif.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + notificationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        fetchNotifications(); 
                    }
                })
                .finally(() => fetchNotificationDetails(notificationId))
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                    fetchNotificationDetails(notificationId);
                });
                }
            }

            // Fetch notification details and show modal
            function fetchNotificationDetails(notificationId) {
                if (window.fetchNotificationDetails) {
                    window.fetchNotificationDetails(notificationId)
                        .then(notification => {
                            if (window.showNotificationModal) {
                                window.showNotificationModal(notification);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching notification details:', error);
                        });
                } else {
                    // Fallback implementation
                fetch(`/frontend/pages/notifications/fetch-notif.php?id=${notificationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotificationModal(data.notification);
                    } else {
                        console.error('Error fetching notification details:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching notification details:', error);
                });
                }
            }

            // Show notification modal with details
            function showNotificationModal(notification) {
                if (window.showNotificationModal) {
                    window.showNotificationModal(notification);
                } else {
                    // If global function doesn't exist, redirect to notifications page
                    window.location.href = '/frontend/pages/notifications/notifications.php';
                }
            }

            // Toggle dropdown on bell icon click
            if (notifLink && notifDropdown) {
                notifLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const isActive = notifDropdown.classList.toggle('active');
                    if (isActive) {
                        fetchNotifications();
                    }
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (notifDropdown && !notifDropdown.contains(e.target) && !notifLink.contains(e.target)) {
                    notifDropdown.classList.remove('active');
                }
            });

            // Close dropdown with Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && notifDropdown && notifDropdown.classList.contains('active')) {
                    notifDropdown.classList.remove('active');
                }
            });


            // Mark all as read button
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    fetch('/frontend/pages/notifications/mark-notif.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'mark_all=true'
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to mark notifications as read');
                        fetchNotifications();
                    })
                    .catch(error => {
                        console.error('Error marking notifications as read:', error);
                    });
                });
            }

            // Initial fetch and polling - only if user is properly logged in
            if (<?php echo ($is_user_logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user') ? 'true' : 'false'; ?>) {
                fetchNotifications(); // Initial fetch
                setInterval(fetchNotifications, 30000); // Poll every 30 seconds
            }

            // ===== MOBILE MENU =====
            // Mobile menu toggle with smooth transition
            if (mobileMenuToggle && navLeft) {
                mobileMenuToggle.addEventListener('click', function() {
                    navLeft.classList.toggle('active');
                    mobileMenuToggle.classList.toggle('active');
                    
                    // Change hamburger icon to X when active
                    if (mobileMenuToggle.classList.contains('active')) {
                        hamburgerIcon.textContent = '✕';
                    } else {
                        hamburgerIcon.textContent = '☰';
                    }
                });
            }

            // Close mobile menu when window is resized to desktop width
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    if (navLeft) navLeft.classList.remove('active');
                    if (mobileMenuToggle) {
                        mobileMenuToggle.classList.remove('active');
                        if (hamburgerIcon) hamburgerIcon.textContent = '☰';
                    }
                }
            });

            // ===== PROFILE DROPDOWN =====
            // Profile dropdown functionality on mobile
            if (profileTrigger && dropdownMenu) {
                profileTrigger.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        dropdownMenu.classList.toggle('show-mobile');
                    }
                });

                // Close dropdown when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768 && 
                        profileTrigger && dropdownMenu &&
                        !profileTrigger.contains(event.target) && 
                        !dropdownMenu.contains(event.target)) {
                        dropdownMenu.classList.remove('show-mobile');
                    }
                });
            }

            // ===== RIPPLE EFFECT =====
            // Add ripple effect to buttons and links
            const buttons = document.querySelectorAll('button, .nav-link, .login-link, .cart-link, .notification-link');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const x = e.clientX - e.target.getBoundingClientRect().left;
                    const y = e.clientY - e.target.getBoundingClientRect().top;
                    
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple-effect');
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
    </script>
    
    <!-- Include the global notification JavaScript -->
    <script src="/frontend/pages/notifications/notifications.js"></script>
</div>