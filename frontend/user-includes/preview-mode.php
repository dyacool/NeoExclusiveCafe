<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define preview mode - check for both user and admin sessions
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
$is_user_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
$is_admin_logged_in = isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';

// Only redirect to login if trying to access protected features
$current_page = basename($_SERVER['PHP_SELF']);
$protected_pages = [
    'profile.php',
    'orders.php',
    'cart.php',
    'checkout.php'
];

if (!$is_user_logged_in && in_array($current_page, $protected_pages)) {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

// Check verification only for logged-in users
if ($is_user_logged_in && (!isset($_SESSION['is_verified']) || $_SESSION['is_verified'] !== true)) {
    header("Location: ../../login/user/verification-page.php");
    exit();
}
?> 