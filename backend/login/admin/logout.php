<?php
session_start();

// Clear only admin session variables
unset($_SESSION["admin_id"]);
unset($_SESSION["admin_username"]);
unset($_SESSION["is_admin"]);
unset($_SESSION["admin_firstname"]);
unset($_SESSION["admin_lastname"]);
unset($_SESSION["admin_role"]);
unset($_SESSION["admin_redirect_url"]);

// Destroy the session completely to ensure security
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/'); // Ensure session is fully removed

header("Location: admin-login.php");
exit();
?>
