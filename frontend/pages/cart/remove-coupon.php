<?php
// Match session configuration from other checkout files
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => ''
]);
session_start();

// Prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Log for debugging
error_log("=== REMOVE COUPON REQUEST ===");
error_log("Session ID: " . session_id());
error_log("Applied coupon before removal: " . (isset($_SESSION['applied_coupon']) ? json_encode($_SESSION['applied_coupon']) : 'NOT SET'));

$removed_coupon = 'none';
$had_coupon = false;

if (isset($_SESSION['applied_coupon'])) {
    $removed_coupon = $_SESSION['applied_coupon']['code'] ?? 'unknown';
    $had_coupon = true;
}

// Aggressively clear any coupon-related session data
unset($_SESSION['applied_coupon']);

// Also clear any other potential coupon keys
if (isset($_SESSION['coupon'])) {
    unset($_SESSION['coupon']);
}
if (isset($_SESSION['discount'])) {
    unset($_SESSION['discount']);
}

error_log("Coupon '$removed_coupon' removed from session");

// Force session write and regenerate to ensure clean state
session_write_close();

// Start session again to verify it's cleared
session_start();
error_log("Session after removal - applied_coupon exists: " . (isset($_SESSION['applied_coupon']) ? 'YES (PROBLEM!)' : 'NO (GOOD)'));
session_write_close();

echo json_encode([
    'success' => true,
    'removed_coupon' => $removed_coupon,
    'had_coupon' => $had_coupon
]);
?>
