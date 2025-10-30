<?php
// Simple test endpoint to verify JSON output works
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');

ob_end_clean();

echo json_encode([
    'success' => true,
    'message' => 'JSON endpoint is working correctly',
    'timestamp' => date('Y-m-d H:i:s')
]);
exit();
?>
