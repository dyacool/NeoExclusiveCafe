<?php
session_start();
header('Content-Type: application/json');

// Simple test to check if data is being received
echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'session_admin' => isset($_SESSION["is_admin"]) ? $_SESSION["is_admin"] : 'not set',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
