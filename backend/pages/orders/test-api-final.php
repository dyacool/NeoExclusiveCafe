<?php
/**
 * Final API test using the actual toggle-auto-status.php endpoint
 */

// Start session and set admin
session_start();
$_SESSION["is_admin"] = true;

echo "=== Testing Toggle Auto-Status API (Final) ===\n\n";

// Helper function to call the API
function callAPI($method, $data = null) {
    $_SERVER['REQUEST_METHOD'] = $method;
    
    if ($data !== null) {
        // Simulate POST data
        $GLOBALS['_test_input'] = json_encode($data);
    }
    
    ob_start();
    include 'toggle-auto-status.php';
    $response = ob_get_clean();
    
    return json_decode($response, true);
}

// Test 1: GET initial state
echo "Test 1: GET initial state\n";
$response = callAPI('GET');
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: POST enable
echo "Test 2: POST enable auto-status\n";
file_put_contents('php://input', json_encode(['enabled' => true]));
$_SERVER['REQUEST_METHOD'] = 'POST';
ob_start();
include 'toggle-auto-status.php';
$response = ob_get_clean();
echo "Response: $response\n\n";

// Test 3: GET after enable
echo "Test 3: GET after enable\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include 'toggle-auto-status.php';
$response = ob_get_clean();
echo "Response: $response\n\n";

echo "=== Tests Complete ===\n";
?>
