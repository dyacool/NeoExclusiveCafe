<?php
// Suppress database debug output for API responses
$suppress_db_debug = true;

require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../../includes/session-manager.php';

// Authentication check
if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(401);
    exit();
}

// Clear flags older than 10 seconds
$clear_sql = "DELETE FROM order_update_flags 
              WHERE created_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)";
mysqli_query($conn, $clear_sql);

http_response_code(200);
?>
