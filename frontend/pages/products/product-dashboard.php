<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Products";
$additional_css = [
    "/frontend/pages/products/product-dashboard.css"
];

require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
require_once __DIR__ . "/../../user-includes/preview-mode.php";
require_once __DIR__ . "/../../user-includes/database.php";
?>

    <div class="main-container">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
        <div class="admin-controls">
            <a href="/NeoExclusiveCafe/pages/admin/admin-homepage.php" class="admin-back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
        <?php endif; ?>
        <div class="content">
            <div class="header animate-fade-in">
                <h1>Products</h1>
                <p>Explore our weekly available products and our complete product catalog.</p>
            </div>

            <div class="blog-container">
                <div class="blog-section animate-fade-in-left">
                <a href="weekly-product.php" class="blog-link">
                        <img src="../../../assets/images/43384560.jpg" alt="Weekly Available">
                        <div class="section-title">
                            <span>DELIVERY</span>
                        </div>
                    </a>
                </div>

                <div class="blog-section animate-fade-in-right">
                    <a href="user-products.php" class="blog-link">
                        <img src="../../../assets/images/43387632.JPG" alt="All Products">
                        <div class="section-title">
                            <span>PICK UP</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="footer">
            <a href="about-page.php">About Us</a>
            <a href="terms.php">Terms and Conditions</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
    </div>
</div>