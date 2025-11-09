<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

header('Content-Type: application/json');

if (!SessionManager::isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/settings-helper.php";

// Get the global available days from settings
$globalDays = getSetting('global_available_days', []);

echo json_encode([
    'success' => true,
    'days' => $globalDays
]);
?>
