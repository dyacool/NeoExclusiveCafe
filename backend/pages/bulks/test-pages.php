<?php
// Simple test script to check for PHP syntax errors
echo "Testing bulk order pages...\n";

// Test bulk-order-lists.php
echo "Checking bulk-order-lists.php syntax: ";
$output = shell_exec('php -l bulk-order-lists.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "✓ OK\n";
} else {
    echo "✗ ERROR\n";
    echo $output . "\n";
}

// Test bulk-order.php  
echo "Checking bulk-order.php syntax: ";
$output = shell_exec('php -l bulk-order.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "✓ OK\n";
} else {
    echo "✗ ERROR\n";
    echo $output . "\n";
}

echo "\nTest completed!\n";
?>
