<?php
session_start();

// Include the config file
require_once __DIR__ . "/../../backend/pages/admin-includes/config.php";

// Don't store user login page as redirect URL to prevent loops
if (strpos($_SERVER["REQUEST_URI"], "login-signup.php") === false) {
    $_SESSION["user_redirect_url"] = $_SERVER["REQUEST_URI"];
}

// Check if user is logged in using separate session keys
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user") {
    // Redirect to user login page with error message
    header("Location: /frontend/login/user/login-signup.php?error=unauthorized");
    exit();
}

// Verify user status from database
require_once __DIR__ . "/database.php";
$stmt = $conn->prepare("SELECT is_verified, is_admin FROM users WHERE id = ? AND is_admin = FALSE");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User is not found or is an admin
    session_unset();
    session_destroy();
    header("Location: /frontend/login/user/login-signup.php?error=unauthorized");
    exit();
}

$user = $result->fetch_assoc();
if (!$user["is_verified"]) {
    // User is not verified
    header("Location: /frontend/login/user/verification-page.php");
    exit();
}
?> 