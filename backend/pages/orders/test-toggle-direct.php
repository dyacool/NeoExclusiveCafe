<?php
/**
 * Direct test for toggle-auto-status.php API
 */

session_start();
$_SESSION["is_admin"] = true; // Simulate admin session

echo "=== Testing Toggle Auto-Status API (Direct) ===\n\n";

// Test GET
echo "Test 1: GET request\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include 'toggle-auto-status.php';
$response = ob_get_clean();
echo "Response: $response\n\n";

// Test POST - Enable
echo "Test 2: POST request (enable)\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
file_put_contents('php://input', json_encode(['enabled' => true]));
ob_start();
include 'toggle-auto-status.php';
$response = ob_get_clean();
echo "Response: $response\n\n";

echo "=== Tests Complete ===\n";
?>
