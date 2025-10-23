<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
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
