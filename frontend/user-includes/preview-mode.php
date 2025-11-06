<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/session-manager.php";

// Define preview mode and authentication states using SessionManager
$is_preview_mode = SessionManager::isPreviewMode();
$is_user_logged_in = SessionManager::isUserLoggedIn();
$is_admin_logged_in = SessionManager::isAdminLoggedIn();

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