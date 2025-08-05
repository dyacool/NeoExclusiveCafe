<?php
// Simulate a POST request to add-to-cart.php
$_POST['product_id'] = 1; // Use a product ID that exists
$_POST['quantity'] = 1;

// Include the add-to-cart.php file
include 'frontend/pages/cart/add-to-cart.php';
?> 