<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . "/../admin-includes/config.php";
    require_once __DIR__ . "/../admin-includes/database.php";
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
