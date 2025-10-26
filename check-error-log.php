<?php
// Check PHP error log location
echo "<h2>PHP Error Log Configuration</h2>";
echo "<p><strong>error_log setting:</strong> " . ini_get('error_log') . "</p>";
echo "<p><strong>log_errors:</strong> " . (ini_get('log_errors') ? 'Enabled' : 'Disabled') . "</p>";
echo "<p><strong>display_errors:</strong> " . (ini_get('display_errors') ? 'Enabled' : 'Disabled') . "</p>";

// Test writing to error log
error_log("=== TEST LOG ENTRY ===");
echo "<p>Test log entry written. Check your error log file.</p>";

// Try to find common log locations
echo "<h3>Common Log File Locations:</h3>";
$common_locations = [
    'C:\xampp\apache\logs\error.log',
    'C:\xampp\php\logs\php_error_log',
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/nginx/error.log',
    '/var/log/php-fpm/error.log',
    __DIR__ . '/error.log',
    __DIR__ . '/php_errors.log'
];

echo "<ul>";
foreach ($common_locations as $location) {
    if (file_exists($location)) {
        echo "<li><strong>FOUND:</strong> $location</li>";
    } else {
        echo "<li>Not found: $location</li>";
    }
}
echo "</ul>";
?>
