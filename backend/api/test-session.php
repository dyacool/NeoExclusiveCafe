<?php
/**
 * Session Test - Check if session is accessible
 */

// Configure session with same settings
$domain = (strpos($_SERVER['HTTP_HOST'], 'neocafe.shop') !== false) ? 'neocafe.shop' : 'neocafe.cafe';
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => $domain
]);

session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'host' => $_SERVER['HTTP_HOST'],
    'domain' => $domain,
    'user_id' => $_SESSION['user_id'] ?? null,
    'is_admin' => $_SESSION['is_admin'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE
], JSON_PRETTY_PRINT);
?>
