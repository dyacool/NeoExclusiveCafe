<?php
session_start();

// Define preview mode
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);

$page_title = "Blog";
$additional_css = [
    "../blog/blog-dashboard.css"
];

require_once "../../user-includes/database.php";
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
    <div class="main">
        <div class="content">
            <h1>Blog</h1>
            <p class="subtitle">Welcome to Neo Cafe's Blog Page! Discover stories, tips, and reviews straight from the bakery and our beloved customers.</p>
            
            <div class="container">
                <a href="blog-list.php" class="image-link">
                    <div class="image-container">
                        <img src="../../../assets/images/20211115_233558.jpg" alt="Neo Cafe's Corner">
                        <div class="image-title">NEO CAFE'S CORNER</div>
                    </div>
                </a>
                
                <a href="user-blog.php" class="image-link">
                    <div class="image-container">
                        <img src="../../../assets/images/carousel/1746967636_44186560_Unknown.JPG" alt="Customer Reviews">
                        <div class="image-title">
                            CUSTOMER REVIEWS
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <?php require_once "../../user-includes/footer.php"; ?>
</body>
</html>