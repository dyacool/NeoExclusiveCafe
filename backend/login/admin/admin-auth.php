<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Include the config file
require_once __DIR__ . "/../../pages/admin-includes/config.php";

// Don't store admin login page as redirect URL to prevent loops
if (strpos($_SERVER["REQUEST_URI"], "admin-login.php") === false) {
    $_SESSION["admin_redirect_url"] = $_SERVER["REQUEST_URI"];
}

// Check if user is logged in and is admin using separate session keys
if (!isset($_SESSION["admin_id"]) || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true || $_SESSION["admin_role"] !== "admin") {
    // Redirect to admin login page with error message
    header("Location: /login/admin/admin-login.php?error=unauthorized");
    exit();
}

// Verify admin status from database
require_once __DIR__ . "/../../pages/admin-includes/database.php";
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ? AND is_admin = TRUE");
$stmt->bind_param("i", $_SESSION["admin_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User is not an admin in the database
    session_unset();
    session_destroy();
    header("Location: /login/admin/admin-login.php?error=unauthorized");
    exit();
}
?> 