<?php
// Load database connection first (it starts session)
require_once "../../../backend/pages/admin-includes/database.php";

// Then load SessionManager (it will use existing session)
require_once "../../../includes/session-manager.php";

$page_title = "Blog";
$additional_css = [
    "../blog/blog-dashboard.css"
];
require_once __DIR__ . "/../../user-includes/user-header.php";

// Define preview mode (after session is started)
$is_preview_mode = SessionManager::isPreviewMode();

// Include navigation after session is established
require_once "../../user-includes/navbar/customer-navigation.php";

// Only redirect to login if trying to access protected features
$current_page = basename($_SERVER['PHP_SELF']);
$protected_pages = [
    'profile.php',
    'orders.php',
    'cart.php',
    'checkout.php'
];

if (!$is_preview_mode && in_array($current_page, $protected_pages)) {
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}

// Check verification only for logged-in users
if (!$is_preview_mode && (!isset($_SESSION['is_verified']) || $_SESSION['is_verified'] !== true)) {
    header("Location: ../../pages/auth/verification-page.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="blog-dashboard.css">
</head>
<body>
    
<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>
    <div class="main">
        <div class="content">
            <!-- Blog Header Section -->
            <div class="blog-header">
                <div class="header-content">
                    <h1 class="blog-title">Blog</h1>
                    <p class="subtitle">Welcome to Neo Cafe's Blog Page! Discover stories, tips, and reviews straight from the bakery and our beloved customers.</p>
                </div>
            </div>
            
            <!-- Blog Categories Grid -->
            <div class="categories-container">
                <div class="container">
                    <a href="blog-list.php" class="image-link">
                        <div class="category-content">
                            <h3 class="category-title">Neo Cafe's Corner</h3>
                            <p class="category-description">Read our latest stories, behind-the-scenes content, and updates from the bakery</p>
                        </div>
                    </a>
                    
                    <a href="user-blog.php" class="image-link">
                        <div class="category-content">
                            <h3 class="category-title">Customer Testimonials</h3>
                            <p class="category-description">See what our amazing customers are saying about their Neo Cafe experience</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Page Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading...</div>
        </div>
    </div>

    <div id="footer-container">
        <?php require_once "../../user-includes/user-footer.php"; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all category links and loading overlay
            const categoryLinks = document.querySelectorAll('.image-link');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Prevent default navigation temporarily
                    e.preventDefault();
                    
                    // Store the original href
                    const originalHref = this.href;
                    
                    // Show full page loading overlay
                    loadingOverlay.classList.add('active');
                    
                    // Show loading for a minimum time (for better UX)
                    setTimeout(() => {
                        // Navigate to the original URL
                        window.location.href = originalHref;
                    }, 800); // 800ms minimum loading time for better UX
                });
            });
        });
    </script>
</body>
</html>