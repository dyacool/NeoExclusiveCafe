<?php
// Get user information if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_query = "SELECT firstname, lastname FROM users WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="../../../frontend/user-includes/navbar/customer-navigation.css">

<div class="header-wrapper">
    <!-- Page Entry Animation Container -->
    <div class="page-entry-animation">
        <div class="logo-animation">
            <img src="../../../assets/images/user-logo.png" alt="NeoCafe Logo" class="animated-logo">
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
                <a href="../../../frontend/pages/home/user-dashboard.php" class="nav-link <?php echo $current_page === 'user-dashboard.php' ? 'active' : ''; ?>">
                    <span class="link-text">Home</span>
                    <span class="link-underline"></span>
                </a>
                <a href="../../../frontend/pages/products/product-dashboard.php" class="nav-link <?php echo $current_page === 'product-dashboard.php' ? 'active' : ''; ?>">
                    <span class="link-text">Products</span>
                    <span class="link-underline"></span>
                </a>
                <a href="../../../frontend/pages/blog/blog-dashboard.php" class="nav-link <?php echo $current_page === 'blog-page.php' ? 'active' : ''; ?>">
                    <span class="link-text">Blog</span>
                    <span class="link-underline"></span>
                </a>
                <a href="../../../frontend/pages/about/about-page.php" class="nav-link <?php echo $current_page === 'about-page.php' ? 'active' : ''; ?>">
                    <span class="link-text">About</span>
                    <span class="link-underline"></span>
                </a>
            </div>

            <div class="nav-center">
                <a href="../../../frontend/pages/home/user-dashboard.php" class="logo-container">
                    <img src="../../../assets/images/user-logo.png" alt="NeoCafe Logo" class="logo">
                </a>
            </div>

            <div class="nav-right">
                <div class="search-container">
                    <button class="search-toggle" aria-label="Toggle search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </div>
                <a href="../../../frontend/pages/cart/cart.php" class="cart-link">
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
                    <a href="#" class="notification-link">
                        <div class="icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon notification-icon">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span class="badge" id="notifCount"></span>
                            <span class="icon-effect"></span>
                        </div>
                    </a>
                    <div class="notification-dropdown" id="notifDropdown">
                        <div class="dropdown-header">
                            <h3>Notifications</h3>
                        </div>
                        <ul id="notificationList">
                            <!-- Notifications will appear dynamically -->
                        </ul>
                        <div class="no-notifications" id="noNotifications">
                            <p style="color:black;"> No new notifications at the moment.</p>
                        </div>
                        <div class="dropdown-footer">
                            <button id="markAllRead" class="mark-read" style="color:black;">Mark all as read</button>
                            <button class="viewall" onclick="window.location.href='../../../frontend/pages/notifications/notifications.php'">View all</button>
                        </div>
                    </div>
                </div>

                <?php if ($user): ?>
                    <div class="profile-container">
                        <a href="../../../frontend/pages/profile/account-settings.php" class="profile-link" id="profile-trigger">
                            <div class="profile-avatar">
                                <span class="profile-initial"><?php echo substr(htmlspecialchars($user['firstname']), 0, 1); ?></span>
                            </div>
                            <span class="profile-name"><?php echo htmlspecialchars($user['firstname']); ?></span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="../../../frontend/pages/profile/account-settings.php">Account Settings</a>
                            <a href="../../../frontend/pages/blog/blog-list.php">View Post</a>
                            <a href="../../../login/user/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../../../login/user/login-signup.php" class="login-link">
                        <span>Login</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mobile Search Box - Positioned below the navigation -->
        <form action="../../../frontend/search/search-results.php" method="GET" class="mobile-search-box">
            <input type="text" name="query" placeholder="Search..." class="search-input" required>
            <button type="submit" class="search-btn">Search</button>
        </form>

        <!-- Desktop Search Box - Popup style -->
        <form action="../../../frontend/search/search-results.php" method="GET" class="desktop-search-box">
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
            const markAllReadButton = document.getElementById('markAllRead');
            const profileTrigger = document.getElementById('profile-trigger');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            // ===== SEARCH FUNCTIONALITY =====
            const searchToggle = document.querySelector('.search-toggle');
            const mobileSearchBox = document.querySelector('.mobile-search-box');
            const desktopSearchBox = document.querySelector('.desktop-search-box');
            const mobileSearchInput = mobileSearchBox.querySelector('.search-input');
            const desktopSearchInput = desktopSearchBox.querySelector('.search-input');
            
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
            });
            
            // ===== PAGE ENTRY ANIMATION =====
            // Check if this is the first visit to the page in this session
            if (!sessionStorage.getItem('navigationAnimationPlayed')) {
                pageEntryAnimation.classList.add('play-animation');
                
                // Hide the animation after it completes
                setTimeout(() => {
                    pageEntryAnimation.classList.remove('play-animation');
                    pageEntryAnimation.classList.add('animation-completed');
                }, 2000); // Match this timing with your CSS animation duration
                
                // Mark that we've played the animation in this session
                sessionStorage.setItem('navigationAnimationPlayed', 'true');
            } else {
                // If we've already played the animation, hide it
                pageEntryAnimation.classList.add('animation-completed');
            }
            
            // ===== NOTIFICATIONS =====
            function fetchNotifications() {
                fetch('../../../frontend/pages/notifications/fetch-notif.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    let notificationList = document.getElementById("notificationList");
                    notificationList.innerHTML = '';

                    if (data.status === "success") {
                        // Check if there are notifications
                        if (data.notifications && data.notifications.length > 0) {
                            document.getElementById("noNotifications").style.display = "none";
                            
                            data.notifications.forEach(notif => {
                                let newNotif = document.createElement("li");
                                newNotif.className = notif.is_read ? "read" : "unread";

                                // Create clickable link for notification message
                                let link = document.createElement("a");
                                link.href = `../../../frontend/pages/profile/order-details.php?order_id=${notif.order_id}`;
                                link.textContent = notif.message;
                                link.style.textDecoration = "none";
                                link.style.color = "inherit";

                                newNotif.appendChild(link);

                                // Add timestamp
                                let small = document.createElement("small");
                                small.textContent = new Date(notif.created_at).toLocaleString();
                                newNotif.appendChild(small);

                                notificationList.appendChild(newNotif);
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
                    document.getElementById("noNotifications").innerHTML = 
                        '<p style="color:black;">Unable to load notifications. Please try again later.</p>';
                        document.getElementById("notifCount").style.display = "none";
                });
            }

            // Show/hide dropdown on bell icon hover - modified to check screen width
            if (notifLink && notifDropdown) {
                notifLink.addEventListener('mouseenter', () => {
                    // Only show dropdown on hover for screens larger than 768px
                    if (window.innerWidth > 768) {
                        notifDropdown.classList.add('active');
                        fetchNotifications(); // Refresh notifications on hover
                    }
                });

                notifLink.addEventListener('mouseleave', () => {
                    // Only hide on mouseleave for screens larger than 768px
                    if (window.innerWidth > 768) {
                        // Delay hiding to allow hover on dropdown
                        setTimeout(() => {
                            if (!notifDropdown.matches(':hover')) {
                                notifDropdown.classList.remove('active');
                            }
                        }, 300);
                    }
                });

                notifDropdown.addEventListener('mouseleave', () => {
                    // Only hide on mouseleave for screens larger than 768px
                    if (window.innerWidth > 768) {
                        notifDropdown.classList.remove('active');
                    }
                });

                notifDropdown.addEventListener('mouseenter', () => {
                    // Only show on mouseenter for screens larger than 768px
                    if (window.innerWidth > 768) {
                        notifDropdown.classList.add('active');
                    }
                });

                // Mark all notifications as read when bell icon clicked
                notifLink.addEventListener("click", function(e) {
                    e.preventDefault();
                    if (window.innerWidth <= 768) {
                        notifDropdown.classList.toggle('active');
                        fetchNotifications(); // Refresh notifications when toggling on mobile
                    } else {
                        fetch('../../../frontend/pages/notifications/mark-notif.php', { 
                            method: 'POST',
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Failed to mark notifications as read');
                            window.location.href = "../../../frontend/pages/notifications/notifications.php";
                        })
                        .catch(error => {
                            console.error('Error marking notifications as read:', error);
                        });
                    }
                });
            }

            if (markAllReadButton) {
                markAllReadButton.addEventListener('click', () => {
                    fetch('../../../frontend/pages/notifications/mark-notif.php', { 
                        method: 'POST',
                        credentials: 'same-origin'
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

            // Initial fetch and polling
            fetchNotifications();
            setInterval(fetchNotifications, 30000);
            
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
</div>