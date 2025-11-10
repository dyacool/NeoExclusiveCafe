<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/session-manager.php';

// Get user information if logged in using SessionManager
$user = null;
$is_user_logged_in = SessionManager::isUserLoggedIn();
$is_admin_logged_in = SessionManager::isAdminLoggedIn();

if ($is_user_logged_in) {
    // Use session data for user
    $user = [
        'firstname' => $_SESSION['user_firstname'] ?? '',
        'lastname' => $_SESSION['user_lastname'] ?? '',
        'profile_image' => $_SESSION['user_profile_image'] ?? ''
    ];

    // Always fetch from database to ensure we have the latest Cloudinary URL
    $user_id = SessionManager::getUserId();
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
                        // Update names if available
                        $user['firstname'] = !empty($row['firstname']) ? $row['firstname'] : ($user['firstname'] ?? '');
                        $user['lastname'] = !empty($row['lastname']) ? $row['lastname'] : ($user['lastname'] ?? '');
                        
                        // ALWAYS prioritize Cloudinary URL over legacy profile_image
                        if (!empty(trim($row['cloud_url'] ?? ''))) {
                            $user['profile_image'] = trim($row['cloud_url']);
                        } elseif (!empty(trim($row['profile_image'] ?? ''))) {
                            $user['profile_image'] = trim($row['profile_image']);
                        } else {
                            $user['profile_image'] = '';
                        }
                        
                        // Update session for future requests
                        $_SESSION['user_profile_image'] = $user['profile_image'];
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
        // Check if existing connection is still valid by checking thread_id
        if ($conn->thread_id !== null) {
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
            
            // Check if we got a valid new connection by checking thread_id
            if (isset($conn) && $conn instanceof mysqli && $conn->thread_id !== null) {
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

    <!-- Real-time badge styling -->
    <style>
        /* Cart Badge Styling */
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #8B4513;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
            min-width: 20px;
            padding: 0 2px;
        }
        
        /* Notification Badge Styling */
        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
            min-width: 20px;
            padding: 0 2px;
        }
        
        /* Animation for real-time updates */
        @keyframes badgePulse {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .cart-badge.updated, .badge.updated {
            animation: badgePulse 0.3s ease;
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.6);
        }
        
        /* Notification Loader Styles */
        .notification-loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            text-align: center;
        }
        
        .notification-loader .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #8B4513;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }
        
        .notification-loader p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Ensure proper positioning for icon containers */
        .icon-wrapper {
            position: relative;
        }
    </style>

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
                    <img src="https://res.cloudinary.com/dvdccumbs/image/upload/v1761594932/user-logo_zer35f.png" alt="NeoCafe Logo" class="logo">
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
                <a href="<?php echo isset($_SESSION['user_id']) ? '../../../frontend/pages/cart/cart.php' : '../../../frontend/login/user/login-signup.php'; ?>" class="cart-link" style="position: relative;">
                    <div class="icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon cart-icon">
                            <path d="M5 8h14l1 13H4L5 8z"></path>
                            <path d="M7 8V6a5 5 0 0110 0v2"></path>
                            <path d="M3 8h18"></path>
                        </svg>
                        <span class="cart-badge" id="cartCount" style="display: none;"></span>
                        <span class="icon-effect"></span>
                        <!-- Cart notification dot -->
                        <span id="cart-notification-dot" class="notification-dot" style="display: none;"></span>
                    </div>
                </a>
                <div class="notification-container">
                    <?php if ($is_user_logged_in): ?>
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
                            <button id="markAllRead" class="mark-read" title="Mark all as read" style="transition: all 0.3s ease;">Mark all as read</button>
                        </div>
                        <div class="notification-loader" id="notificationLoader" style="display: none;">
                            <div class="spinner"></div>
                            <p>Loading notifications...</p>
                        </div>
                        <ul id="notificationList" class="notification-list">
                            <!-- Notifications will appear dynamically -->
                        </ul>
                        <div class="no-notifications" id="noNotifications" style="display: none;">
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
                                // Define default profile image path
                                $profile_default_image_path = '/assets/images/profile.svg';
                                
                                // Determine profile image url - prioritize Cloudinary
                                $profile_image_url = $profile_default_image_path;
                                if (isset($user['profile_image']) && !empty(trim($user['profile_image']))) {
                                    $profile_image_url = trim($user['profile_image']);
                                }
                                
                                // Check if user has a profile image (not the default SVG)
                                $has_profile_image = ($profile_image_url !== $profile_default_image_path);
                                
                                if ($has_profile_image): ?>
                                    <img src="<?= htmlspecialchars($profile_image_url) ?>" alt="Profile Image" />
                                <?php else:
                                    // Show initials with randomized green color
                                    $initials = strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1));
                                    
                                    // Generate consistent random green-toned color based on user's name
                                    $seed = crc32($user['firstname'] . $user['lastname']);
                                    mt_srand($seed);
                                    
                                    // Green color ranges: hue 80-160 (yellow-green to blue-green)
                                    $hue = mt_rand(80, 160);
                                    $saturation = mt_rand(40, 70); // Medium saturation
                                    $lightness = mt_rand(35, 50); // Medium-dark for good contrast
                                    
                                    $color1 = "hsl($hue, {$saturation}%, $lightness%)";
                                    
                                    // Second color slightly different
                                    $hue2 = $hue + mt_rand(-15, 15);
                                    $lightness2 = $lightness + mt_rand(-5, 10);
                                    $color2 = "hsl($hue2, {$saturation}%, $lightness2%)";
                                    
                                    $gradient = "linear-gradient(135deg, $color1 0%, $color2 100%)";
                                    
                                    echo '<span class="profile-initial">' . htmlspecialchars($initials) . '</span>';
                                endif;
                                ?>
                            </div>
                            <span class="profile-name"><?php echo htmlspecialchars($user['firstname']); ?></span>
                        </a>
                        <div class="dropdown-menu">
                            <?php if ($is_admin_logged_in): ?>
                                <a href="/backend/pages/homepage/admin-homepage.php">Admin Panel</a>
                                <a href="#" onclick="confirmLogout('admin'); return false;">Logout</a>
                            <?php else: ?>
                                <a href="/frontend/pages/profile/profile.php">Profile</a>
                                <a href="/frontend/pages/profile/account-settings.php">Account Settings</a>
                                <a href="/frontend/pages/blog/user-blog-post.php">View Post</a>
                                <a href="#" onclick="confirmLogout('user'); return false;">Logout</a>
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

<!-- Mobile Notification Modal Overlay -->
<?php if ($is_user_logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
<div class="mobile-notification-overlay" id="mobileNotifOverlay" onclick="closeMobileNotifications();"></div>

<!-- Mobile Notification Dropdown - Modal Style -->
<div class="mobile-notification-dropdown" id="mobileNotifDropdown">
    <div class="dropdown-header">
        <h3>Notifications</h3>
        <div class="header-actions">
            <button id="mobileMarkAllRead" class="mark-read" title="Mark all as read" style="transition: all 0.3s ease;">Mark all as read</button>
            <button class="close-modal" id="closeMobileNotif" aria-label="Close notifications" onclick="closeMobileNotifications(); return false;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" x2="18" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
    <div class="notification-loader" id="mobileNotificationLoader" style="display: none;">
        <div class="spinner"></div>
        <p>Loading notifications...</p>
    </div>
    <ul id="mobileNotificationList" class="notification-list">
        <!-- Notifications will appear dynamically -->
    </ul>
    <div class="no-notifications" id="mobileNoNotifications" style="display: none;">
        <p>No new notifications.</p>
    </div>
    <div class="dropdown-footer">
        <a href="/frontend/pages/notifications/notifications.php" class="view-all-link">View All</a>
    </div>
</div>
<?php endif; ?>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="logout-modal" style="display: none;">
    <div class="logout-modal-overlay"></div>
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <h3>Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="logout-modal-actions">
            <button class="logout-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            <button class="logout-btn-confirm" onclick="proceedLogout()">Yes, Logout</button>
        </div>
    </div>
</div>


<div class="wrapper">
    <script>
        // GLOBAL FUNCTION - Define IMMEDIATELY for inline onclick to work
        function closeMobileNotifications() {
            const dropdown = document.getElementById('mobileNotifDropdown');
            const overlay = document.getElementById('mobileNotifOverlay');
            
            if (dropdown) dropdown.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // SIMPLE IMMEDIATE IMPLEMENTATION - Wait for DOM to be ready
        
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
            const mobileNotifDropdown = document.getElementById('mobileNotifDropdown');
            const mobileNotifOverlay = document.getElementById('mobileNotifOverlay');
            const closeMobileNotif = document.getElementById('closeMobileNotif');
            
            // Close button functionality - using addEventListener for better reliability
            if (closeMobileNotif) {
                closeMobileNotif.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Close button clicked!'); // Debug log
                    
                    if (mobileNotifDropdown && mobileNotifOverlay) {
                        mobileNotifDropdown.classList.remove('active');
                        mobileNotifOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                        console.log('Modal closed'); // Debug log
                    }
                });
            }
            
            // Also add event delegation in case button is added dynamically
            document.addEventListener('click', function(e) {
                if (e.target.closest('#closeMobileNotif')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Close button clicked via delegation!'); // Debug log
                    
                    const dropdown = document.getElementById('mobileNotifDropdown');
                    const overlay = document.getElementById('mobileNotifOverlay');
                    
                    if (dropdown && overlay) {
                        dropdown.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                        console.log('Modal closed via delegation'); // Debug log
                    }
                }
            });
            
            if (notifLink) {
                notifLink.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Check if mobile device (1024px breakpoint)
                    if (window.innerWidth <= 1024 && mobileNotifDropdown && mobileNotifOverlay) {
                        // Mobile: toggle mobile modal and overlay
                        const isActive = mobileNotifDropdown.classList.toggle('active');
                        mobileNotifOverlay.classList.toggle('active', isActive);
                        
                        // Prevent body scroll when modal is open
                        if (isActive) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                        
                        // Fetch notifications when dropdown is opened
                        if (isActive) {
                            const loader = document.getElementById('mobileNotificationLoader');
                            const notificationList = document.getElementById('mobileNotificationList');
                            const noNotifications = document.getElementById('mobileNoNotifications');
                            
                            if (loader) loader.style.display = 'flex';
                            if (notificationList) notificationList.style.display = 'none';
                            if (noNotifications) noNotifications.style.display = 'none';
                            
                            fetchNotifications('mobile');
                        }
                    } else if (notifDropdown) {
                        // Desktop: toggle desktop dropdown
                        const isActive = notifDropdown.classList.toggle('active');
                        
                        // Fetch notifications when dropdown is opened
                        if (isActive) {
                            const loader = document.getElementById('notificationLoader');
                            const notificationList = document.getElementById('notificationList');
                            const noNotifications = document.getElementById('noNotifications');
                            
                            if (loader) loader.style.display = 'flex';
                            if (notificationList) notificationList.style.display = 'none';
                            if (noNotifications) noNotifications.style.display = 'none';
                            
                            fetchNotifications('desktop');
                        }
                    }
                };
            }
            
            // MARK ALL AS READ FUNCTIONALITY FOR DROPDOWN
            const markAllReadBtn = document.getElementById('markAllRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Check if there are any unread notifications first
                    const unreadItems = document.querySelectorAll('#notificationList .notification-item.unread');
                    if (unreadItems.length === 0) {
                        // Show message if no unread notifications
                        markAllReadBtn.textContent = 'All read!';
                        markAllReadBtn.style.opacity = '0.7';
                        
                        setTimeout(() => {
                            markAllReadBtn.textContent = 'Mark all as read';
                            markAllReadBtn.style.opacity = '1';
                        }, 2000);
                        return;
                    }
                    
                    // Change button state
                    const originalText = markAllReadBtn.textContent;
                    markAllReadBtn.textContent = 'Marking...';
                    markAllReadBtn.disabled = true;
                    markAllReadBtn.style.opacity = '0.7';
                    
                    // Make API call to mark all as read (no response needed)
                    fetch('/frontend/pages/notifications/mark-all-notifications-read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    // Immediately update UI
                    markAllReadBtn.textContent = 'All marked!';
                    markAllReadBtn.style.color = '#28a745';
                    
                    // Update UI - mark all items as read
                    unreadItems.forEach(item => {
                        item.classList.remove('unread');
                        item.classList.add('read');
                    });
                    
                    // Update notification count badge
                    const notifCount = document.getElementById('notifCount');
                    if (notifCount) {
                        notifCount.style.display = 'none';
                    }
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        markAllReadBtn.textContent = originalText;
                        markAllReadBtn.disabled = false;
                        markAllReadBtn.style.opacity = '1';
                        markAllReadBtn.style.color = '';
                        
                        // Trigger notification update
                        if (window.dispatchEvent) {
                            window.dispatchEvent(new CustomEvent('notificationUpdated'));
                        }
                    }, 2000);
                });
            }
            
            // MOBILE MARK ALL AS READ FUNCTIONALITY
            const mobileMarkAllReadBtn = document.getElementById('mobileMarkAllRead');
            if (mobileMarkAllReadBtn) {
                mobileMarkAllReadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Check if there are any unread notifications first
                    const unreadItems = document.querySelectorAll('#mobileNotificationList .notification-item.unread');
                    if (unreadItems.length === 0) {
                        // Show message if no unread notifications
                        mobileMarkAllReadBtn.textContent = 'All read!';
                        mobileMarkAllReadBtn.style.opacity = '0.7';
                        
                        setTimeout(() => {
                            mobileMarkAllReadBtn.textContent = 'Mark all as read';
                            mobileMarkAllReadBtn.style.opacity = '1';
                        }, 2000);
                        return;
                    }
                    
                    // Change button state
                    const originalText = mobileMarkAllReadBtn.textContent;
                    mobileMarkAllReadBtn.textContent = 'Marking...';
                    mobileMarkAllReadBtn.disabled = true;
                    mobileMarkAllReadBtn.style.opacity = '0.7';
                    
                    // Make API call to mark all as read (no response needed)
                    fetch('/frontend/pages/notifications/mark-all-notifications-read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    // Immediately update UI
                    mobileMarkAllReadBtn.textContent = 'All marked!';
                    mobileMarkAllReadBtn.style.color = '#28a745';
                    
                    // Update UI - mark all items as read
                    unreadItems.forEach(item => {
                        item.classList.remove('unread');
                        item.classList.add('read');
                    });
                    
                    // Update notification count badge
                    const notifCount = document.getElementById('notifCount');
                    if (notifCount) {
                        notifCount.style.display = 'none';
                    }
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        mobileMarkAllReadBtn.textContent = originalText;
                        mobileMarkAllReadBtn.disabled = false;
                        mobileMarkAllReadBtn.style.opacity = '1';
                        mobileMarkAllReadBtn.style.color = '';
                        
                        // Trigger notification update
                        if (window.dispatchEvent) {
                            window.dispatchEvent(new CustomEvent('notificationUpdated'));
                        }
                    }, 2000);
                });
            }
            

            // NOTIFICATION FUNCTIONALITY - REAL-TIME
            function updateNotificationCount(notifications) {
                console.log('updateNotificationCount called with:', notifications);
                const notifCount = document.getElementById("notifCount");
                if (!notifCount) {
                    console.error('Notification count element not found!');
                    return;
                }
                
                const unreadCount = notifications ? notifications.filter(n => !n.is_read).length : 0;
                console.log('Unread notification count:', unreadCount);
                if (unreadCount > 0) {
                    notifCount.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    notifCount.style.display = "block";
                    notifCount.classList.add('updated');
                    console.log('Notification count updated to:', unreadCount);
                    
                    // Remove animation class after animation completes
                    setTimeout(() => {
                        notifCount.classList.remove('updated');
                    }, 300);
                } else {
                    notifCount.style.display = "none";
                    console.log('Notification count hidden (no unread notifications)');
                }
            }
            
            // Real-time notification updates
            function fetchNotificationsRealtime() {
                console.log('fetchNotificationsRealtime called at:', new Date().toLocaleTimeString());
                <?php if (isset($_SESSION['user_id'])): ?>
                console.log('Fetching notifications for user ID:', <?php echo $_SESSION['user_id']; ?>);
                fetch('/frontend/pages/notifications/fetch-notif.php?dropdown=true')
                    .then(response => {
                        console.log('Notification response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Notification data received:', data);
                        if (data.status === "success") {
                            updateNotificationCount(data.notifications || []);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching notification count:', error);
                    });
                <?php else: ?>
                console.log('User not logged in, skipping notification fetch');
                <?php endif; ?>
            }
            
            // Initial notification count fetch (with element check)
            function waitForNotificationElement() {
                const notifCount = document.getElementById('notifCount');
                if (notifCount) {
                    console.log('Notification element found, starting updates');
                    fetchNotificationsRealtime();
                    
                    // Real-time notification updates (every 5 seconds)
                    setInterval(fetchNotificationsRealtime, 5000);
                } else {
                    console.log('Notification element not found, retrying in 100ms');
                    setTimeout(waitForNotificationElement, 100);
                }
            }
            waitForNotificationElement();
            
            // Listen for notification events
            window.addEventListener('notificationUpdated', function() {
                fetchNotificationsRealtime();
                // Trigger storage event to sync with other tabs
                localStorage.setItem('notification_updated', Date.now());
                localStorage.removeItem('notification_updated');
            });
            
            // Listen for storage events for notifications
            window.addEventListener('storage', function(e) {
                if (e.key === 'notification_updated') {
                    fetchNotificationsRealtime();
                }
            });
            function fetchNotifications(type = 'desktop') {
                const isMobile = type === 'mobile';
                const prefix = isMobile ? 'mobile' : '';
                const notificationList = document.getElementById(prefix + (prefix ? 'N' : 'n') + 'otificationList');
                const noNotifications = document.getElementById(prefix + (prefix ? 'N' : 'n') + 'oNotifications');
                const notifCount = document.getElementById("notifCount");
                const loader = document.getElementById(prefix + (prefix ? 'N' : 'n') + 'otificationLoader');
                
                if (!notificationList || !noNotifications) return;
                
                // Show loader while fetching
                if (loader) {
                    loader.style.display = 'block';
                }
                notificationList.style.display = 'none';
                noNotifications.style.display = 'none';
                
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
                    // Hide loader
                    if (loader) {
                        loader.style.display = 'none';
                    }
                    
                    notificationList.innerHTML = '';
                    notificationList.style.display = 'block';
                    
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

                            const time = document.createElement("div");
                            time.className = "notification-time";
                            time.textContent = new Date(notif.created_at).toLocaleString([], { short: 'short' });

                            const contentDiv = document.createElement('div');
                            contentDiv.className = 'notification-content';
                            contentDiv.appendChild(title);
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
                    // Hide loader
                    if (loader) {
                        loader.style.display = 'none';
                    }
                    
                    notificationList.innerHTML = '';
                    notificationList.style.display = 'none';
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
                
                // Close desktop notifications
                if (notifDropdown && notifLink && !notifLink.contains(e.target) && !notifDropdown.contains(e.target)) {
                    notifDropdown.classList.remove('active');
                }
                
                // Close mobile notification modal when clicking overlay or outside modal
                const mobileNotifDropdownElement = document.getElementById('mobileNotifDropdown');
                const mobileNotifOverlayElement = document.getElementById('mobileNotifOverlay');
                
                if (mobileNotifOverlayElement && mobileNotifDropdownElement) {
                    // Close if clicking overlay OR if clicking outside both notification link and modal
                    if (e.target === mobileNotifOverlayElement || 
                        (mobileNotifDropdownElement.classList.contains('active') && 
                         !notifLink.contains(e.target) && 
                         !mobileNotifDropdownElement.contains(e.target))) {
                        mobileNotifDropdownElement.classList.remove('active');
                        mobileNotifOverlayElement.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                }
            };
            
        }, 500); // 500ms delay to ensure all HTML elements are parsed and ready
        
        // CRITICAL: Close button must work IMMEDIATELY - outside setTimeout
        document.addEventListener('click', function(e) {
            // Close mobile notification modal
            if (e.target.closest('#closeMobileNotif') || e.target.closest('.close-modal')) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = document.getElementById('mobileNotifDropdown');
                const overlay = document.getElementById('mobileNotifOverlay');
                
                if (dropdown) dropdown.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
                
                return false;
            }
        }, true); // Use capture phase to catch event early
        
        // LOGOUT CONFIRMATION FUNCTIONS
        let logoutType = '';
        
        window.confirmLogout = function(type) {
            logoutType = type;
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
            logoutType = '';
        };
        
        window.proceedLogout = function() {
            if (logoutType === 'admin') {
                window.location.href = '/backend/login/admin/logout.php';
            } else if (logoutType === 'user') {
                window.location.href = '/frontend/login/user/logout.php';
            }
        };
        
        // Close modal when clicking overlay
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('logoutModal');
            const modalContent = document.querySelector('.logout-modal-content');
            
            if (modal && e.target === modal && !modalContent.contains(e.target)) {
                closeLogoutModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });
        
        // AUTOMATIC NAVBAR HEIGHT CALCULATION
        function setNavbarHeight() {
            const headerWrapper = document.querySelector('.header-wrapper');
            if (headerWrapper) {
                const actualHeight = headerWrapper.offsetHeight;
                document.documentElement.style.setProperty('--navbar-height', actualHeight + 'px');
                
                // Debug log (remove in production)
                console.log('Navbar height set to:', actualHeight + 'px');
            }
        }
        
        // Set navbar height on load and resize
        setNavbarHeight();
        window.addEventListener('resize', setNavbarHeight);
        window.addEventListener('orientationchange', function() {
            setTimeout(setNavbarHeight, 100); // Small delay for orientation change
        });

        // Cart notification functionality
        function updateCartNotification() {
            fetch('/backend/api/get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartDot = document.getElementById('cart-notification-dot');
                    if (cartDot) {
                        if (data.hasItems && data.count > 0) {
                            cartDot.style.display = 'block';
                        } else {
                            cartDot.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.log('Cart notification update failed:', error);
                });
        }

        // Update cart notification on page load
        document.addEventListener('DOMContentLoaded', updateCartNotification);

        // Update cart notification every 30 seconds
        setInterval(updateCartNotification, 30000);

        // Listen for custom cart update events
        window.addEventListener('cartUpdated', updateCartNotification);
        
    </script>
    
    <!-- Include the global notification JavaScript -->
    <script src="/frontend/pages/notifications/notifications.js"></script>
</div>