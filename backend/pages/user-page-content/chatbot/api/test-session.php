<?php
// Include database which starts session
require_once __DIR__ . '/../../../admin-includes/database.php';

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_status' => session_status(),
    'session_save_path' => session_save_path(),
    'cookie_params' => session_get_cookie_params(),
    'session_data' => $_SESSION,
    'cookies_received' => $_COOKIE,
    'request_uri' => $_SERVER['REQUEST_URI'],
    'script_name' => $_SERVER['SCRIPT_NAME']
]);
