<?php
/**
 * Test script for toggle-auto-status.php API
 */

session_start();
$_SESSION["is_admin"] = true; // Simulate admin session

echo "=== Testing Toggle Auto-Status API ===\n\n";

// Test 1: GET request (should return default false)
echo "Test 1: GET request (initial state)\n";
$ch = curl_init('http://localhost/NeoCafe/backend/pages/orders/toggle-auto-status.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 2: POST request to enable auto-status
echo "Test 2: POST request (enable auto-status)\n";
$ch = curl_init('http://localhost/NeoCafe/backend/pages/orders/toggle-auto-status.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['enabled' => true]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 3: GET request (should return true now)
echo "Test 3: GET request (after enabling)\n";
$ch = curl_init('http://localhost/NeoCafe/backend/pages/orders/toggle-auto-status.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 4: POST request to disable auto-status
echo "Test 4: POST request (disable auto-status)\n";
$ch = curl_init('http://localhost/NeoCafe/backend/pages/orders/toggle-auto-status.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['enabled' => false]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 5: GET request (should return false now)
echo "Test 5: GET request (after disabling)\n";
$ch = curl_init('http://localhost/NeoCafe/backend/pages/orders/toggle-auto-status.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

echo "=== Tests Complete ===\n";
?>
