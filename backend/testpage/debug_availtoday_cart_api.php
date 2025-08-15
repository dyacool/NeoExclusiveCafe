<?php
// Simulate a POST request to availtoday-cart-api.php for debugging
session_start();

// Set a test user session (simulate logged-in user)
$_SESSION['user_id'] = 1;

// Simulate POST data
$_POST['action'] = 'test';

// Set up server variables to mimic a real web request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['SCRIPT_NAME'] = '/backend/pages/cart/availtoday-cart-api.php';

// Capture output
ob_start();
include __DIR__ . '/../pages/cart/availtoday-cart-api.php';
$output = ob_get_clean();

// Output the result for debugging
header('Content-Type: application/json');
echo $output;
?>
