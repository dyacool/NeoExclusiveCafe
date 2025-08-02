<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define preview mode
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);

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