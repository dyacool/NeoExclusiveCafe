<?php
/**
 * Quick Domain Debug Script
 * Shows exactly what's being detected
 */

// Include domain configuration
$config = require_once 'config/domain-config.php';

$current_domain = $_SERVER['HTTP_HOST'];

header('Content-Type: text/plain');

echo "=== DOMAIN DEBUG INFO ===\n\n";
echo "Current Domain: {$current_domain}\n";
echo "Environment: " . getEnvironment() . "\n\n";

echo "=== DOMAIN CHECKS ===\n";
echo "isAdminDomain(): " . (isAdminDomain($current_domain) ? 'TRUE' : 'FALSE') . "\n";
echo "isRiderDomain(): " . (isRiderDomain($current_domain) ? 'TRUE' : 'FALSE') . "\n\n";

echo "=== CONFIG VALUES ===\n";
echo "Admin Domain: {$config['admin_domain']}\n";
echo "Rider Domain: {$config['rider_domain']}\n";
echo "Main Domain: {$config['main_domain']}\n\n";

echo "=== EXPECTED REDIRECT ===\n";
if (isAdminDomain($current_domain)) {
    echo "Would redirect to: {$config['admin_path']}\n";
} elseif (isRiderDomain($current_domain)) {
    echo "Would redirect to: {$config['rider_path']}\n";
} else {
    echo "Would redirect to: {$config['default_path']}\n";
}

echo "\n=== REGEX TESTS ===\n";
echo "Starts with 'admin.': " . (preg_match('/^admin\./', $current_domain) ? 'TRUE' : 'FALSE') . "\n";
echo "Starts with 'rider.': " . (preg_match('/^rider\./', $current_domain) ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== PORT STRIPPING ===\n";
$domain_without_port = preg_replace('/:\d+$/', '', $current_domain);
echo "Domain without port: {$domain_without_port}\n";
echo "Config rider without port: " . preg_replace('/:\d+$/', '', $config['rider_domain']) . "\n";
echo "Match: " . ($domain_without_port === preg_replace('/:\d+$/', '', $config['rider_domain']) ? 'TRUE' : 'FALSE') . "\n";
?>
