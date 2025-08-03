<?php
session_start();

// Clear only user session variables
unset($_SESSION["user_id"]);
unset($_SESSION["user_username"]);
unset($_SESSION["is_verified"]);
unset($_SESSION["user_firstname"]);
unset($_SESSION["user_lastname"]);
unset($_SESSION["user_role"]);
unset($_SESSION["user_redirect_url"]);
unset($_SESSION["unverified_email"]);

// Destroy the session completely to ensure security
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to user dashboard (public view)
header("Location: /frontend/pages/home/user-dashboard.php");
exit();
?> 