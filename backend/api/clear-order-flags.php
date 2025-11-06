<?php
// Suppress database debug output for API responses
$suppress_db_debug = true;

session_start();

// Authentication check
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(401);
    exit();
}

require_once __DIR__ . '/../pages/admin-includes/database.php';

// Clear flags older than 10 seconds
$clear_sql = "DELETE FROM order_update_flags 
              WHERE created_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)";
mysqli_query($conn, $clear_sql);

http_response_code(200);
?>
