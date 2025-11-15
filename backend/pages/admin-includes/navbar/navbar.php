<?php
require_once __DIR__ . '/../../../../includes/session-manager.php';

// Check if user is logged in as admin
if (!SessionManager::isAdminLoggedIn()) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

$isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1;

// Include database connection only if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/../database.php';
}

// Include order count helper
require_once __DIR__ . '/order-count-helper.php';

// Get order counts only if connection is valid
$order_counts = ['total' => 0, 'active' => 0, 'pending' => 0];
$bulk_counts = ['total' => 0, 'active' => 0];
$refund_counts = ['total' => 0, 'active' => 0];

if (isset($conn) && $conn instanceof mysqli) {
    try {
        // Check if connection is actually open by testing thread_id
        if ($conn->thread_id !== null) {
            $order_counts = getOrderCounts($conn);
            $bulk_counts = getBulkOrderCounts($conn);
            $refund_counts = getRefundCounts($conn);
        }
    } catch (Exception $e) {
        // Connection is closed or invalid, use default values
        error_log("Navbar connection error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/notifications/notifications.css">
    <script src="../admin-includes/navbar/navbar.js" defer></script>
    <script src="../admin-includes/notifications/notifications.js" defer></script>
    <title>Neo Cafe Admin</title>
</head>
<body>
    <!-- Mobile Header with Two-Row Structure -->
    <div class="mobile-header">
        <!-- Top Row: Hamburger | Logo | ADMIN -->
        <div class="mobile-header-top">
            <button class="mobile-menu-toggle" title="Toggle Sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="mobile-logo">
                <div class="logo-circle">
                    <img src="../../assets/images/user-logo.png" alt="Neo Cafe Logo">
                </div>
                <span class="mobile-logo-text">Admin</span>
            </div>
        </div>
        
        <!-- Bottom Row: Page Title (below border) -->
        <div class="mobile-header-bottom">
            <h1 id="mobile-page-title">Neo Cafe Admin</h1>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar mobile-hidden">
        <!-- Floating Close Button (only visible on mobile) -->
        <button class="floating-close-btn mobile-menu-toggle-inside" title="Close Sidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Sidebar Header (Hidden on mobile) -->
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo-circle">
                    <img src="../../assets/images/user-logo.png" alt="Neo Cafe Logo">
                </div>
                <span class="admin-text">Admin</span>
            </div>
        </div>

        <!-- Sidebar Content -->
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="../dashboard/dashboard.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            <span class="nav-text">Home</span>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <div class="nav-link products-toggle" role="button" tabindex="0">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m7.5 4.27 9 5.15"></path>
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                                <path d="m3.3 7 8.7 5 8.7-5"></path>
                                <path d="M12 22V12"></path>
                            </svg>
                            <span class="nav-text">Products</span>
                            <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                        </div>
                        <ul class="dropdown-menu products-dropdown">
                            <li><span class="dropdown-link" data-href="../products/product-list.php">
                                <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="8" y1="6" x2="21" y2="6"></line>
                                    <line x1="8" y1="12" x2="21" y2="12"></line>
                                    <line x1="8" y1="18" x2="21" y2="18"></line>
                                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                </svg>
                                Product List
                            </span></li>
                            <li><span class="dropdown-link" data-href="../products/add-product.php">
                                <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Product
                            </span></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="../calendar/calendar.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <span class="nav-text">Calendar</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../orders/order-list.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="m1 1 4 4 2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="nav-text">Orders</span>
                            <?php if ($order_counts['active'] > 0): ?>
                            <span class="nav-count-badge"><?php echo $order_counts['active']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../bulks/bulk-order-lists.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10,9 9,9 8,9"></polyline>
                            </svg>
                            <span class="nav-text">Bulk Orders</span>
                            <?php if ($bulk_counts['active'] > 0): ?>
                            <span class="nav-count-badge"><?php echo $bulk_counts['active']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../refund/refund-request-lists.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <span class="nav-text">Refund Requests</span>
                            <?php if ($refund_counts['active'] > 0): ?>
                            <span class="nav-count-badge"><?php echo $refund_counts['active']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../transactions/transactions.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <span class="nav-text">Sales Report</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../expenses/expense.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <span class="nav-text">Expenses</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../blog/admin-blog.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m12 19 7-7 3 3-7 7-3-3z"></path>
                                <path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                                <path d="m2 2 7.586 7.586"></path>
                                <circle cx="11" cy="11" r="2"></circle>
                            </svg>
                            <span class="nav-text">Blog</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="../user-page-content/user-content-settings.php" class="nav-link">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <span class="nav-text">Manage Content</span>
                        </a>
                    </li>

                </ul>
            </nav>
            
            <!-- Mobile Footer Content (hidden on desktop, shown on mobile within sidebar-content) -->
            <div class="mobile-footer-content">
                <ul class="mobile-footer-menu">
                    <li class="mobile-footer-item">
                        <a href="../account/admin-profile.php" class="mobile-footer-link">
                            <svg class="mobile-footer-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="mobile-footer-text">Account</span>
                        </a>
                    </li>

                    <li class="mobile-footer-item">
                        <a href="#" class="mobile-footer-link logout" onclick="showLogoutModal(event)">
                            <svg class="mobile-footer-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16,17 21,12 16,7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span class="mobile-footer-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <ul class="footer-menu">
                <li class="footer-item">
                    <a href="../account/admin-profile.php" class="footer-link">
                        <svg class="footer-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="footer-text">Account</span>
                    </a>
                </li>

                <li class="footer-item">
                    <a href="#" class="footer-link logout" onclick="showLogoutModal(event)">
                        <svg class="footer-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16,17 21,12 16,7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span class="footer-text">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <div class="logout-modal-header">
                <h3>Confirm Logout</h3>
            </div>
            <div class="logout-modal-body">
                <p>Are you sure you want to log out?</p>
            </div>
            <div class="logout-modal-footer">
                <button class="logout-btn-cancel" onclick="hideLogoutModal()">Cancel</button>
                <button class="logout-btn-confirm" onclick="confirmLogout()">Logout</button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="Main">
        <header class="header">
            <h1 id="page-title">Neo Cafe Admin</h1>
            <div class="header-actions">
                <?php if ($isAdmin): ?>
                    <a href="../../pages/admin/admin-homepage.php" class="admin-home-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        Admin Home
                    </a>
                <?php endif; ?>
                <!-- Notification bell will be inserted here by JavaScript -->
            </div>
        </header>
        <div class="content-wrapper">
</body>
</html>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get the page title from the desktop version
    const pageTitleElement = document.getElementById("page-title");
    const mobileTitleElement = document.getElementById("mobile-page-title");
    
    if (pageTitleElement && mobileTitleElement) {
        // Set the mobile title to match the desktop title
        mobileTitleElement.textContent = pageTitleElement.textContent;
        
        // Keep them in sync if the desktop title changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === "childList") {
                    mobileTitleElement.textContent = pageTitleElement.textContent;
                }
            });
        });
        
        observer.observe(pageTitleElement, { childList: true });
    }
});

// Logout Modal Functions
function showLogoutModal(event) {
    event.preventDefault();
    document.getElementById('logoutModal').style.display = 'flex';
}

function hideLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function confirmLogout() {
    window.location.href = '../../login/admin/logout.php';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target === modal) {
        hideLogoutModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        hideLogoutModal();
    }
});
</script>
