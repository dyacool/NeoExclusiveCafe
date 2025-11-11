<?php
// Load database first (it starts session)
require_once __DIR__ . "/../../pages/admin-includes/database.php";

// Then load SessionManager and config
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../../pages/admin-includes/config.php";

if (strpos($_SERVER["REQUEST_URI"], "admin-login.php") === false) {
    $_SESSION["admin_redirect_url"] = $_SERVER["REQUEST_URI"];
}

// Check if admin is logged in using SessionManager
if (!SessionManager::isAdminLoggedIn()) {
    // Redirect to admin login page with error message
    header("Location: /backend/login/admin/admin-login.php?error=unauthorized");
    exit();
}

// Verify admin status from database (database.php already loaded above)
$adminData = SessionManager::getAdminData();
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ? AND is_admin = TRUE");
$stmt->bind_param("i", $adminData['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User is not an admin in the database
    SessionManager::destroySession();
    header("Location: /backend/login/admin/admin-login.php?error=unauthorized");
    exit();
}
?> 