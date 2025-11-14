<?php
/**
 * Real-time Payment Flow Monitor
 * Run this script in a separate terminal while testing payment flow
 */

$log_file = __DIR__ . '/logs/payment_errors.log';

if (!file_exists($log_file)) {
    die("Log file not found: $log_file\n");
}

echo "=== PAYMONGO PAYMENT FLOW MONITOR ===\n";
echo "Watching: $log_file\n";
echo "Press Ctrl+C to stop\n";
echo str_repeat("=", 50) . "\n\n";

// Get current file size
$last_size = filesize($log_file);

// Monitor in real-time
while (true) {
    clearstatcache();
    $current_size = filesize($log_file);
    
    if ($current_size > $last_size) {
        // New content added
        $handle = fopen($log_file, 'r');
        fseek($handle, $last_size);
        
        while (($line = fgets($handle)) !== false) {
            // Color code important lines
            if (strpos($line, 'PROCESS-PAYMENT') !== false) {
                echo "\033[1;36m" . $line . "\033[0m"; // Cyan
            } elseif (strpos($line, 'CREATE-PM') !== false) {
                echo "\033[1;33m" . $line . "\033[0m"; // Yellow
            } elseif (strpos($line, 'ATTACH-PM') !== false) {
                echo "\033[1;35m" . $line . "\033[0m"; // Magenta
            } elseif (strpos($line, 'PAYMENT-RETURN') !== false) {
                echo "\033[1;32m" . $line . "\033[0m"; // Green
            } elseif (strpos($line, 'ERROR') !== false || strpos($line, '✗') !== false) {
                echo "\033[1;31m" . $line . "\033[0m"; // Red
            } elseif (strpos($line, '✓') !== false || strpos($line, 'success') !== false) {
                echo "\033[1;32m" . $line . "\033[0m"; // Green
            } else {
                echo $line;
            }
        }
        
        fclose($handle);
        $last_size = $current_size;
    }
    
    usleep(100000); // 0.1 second
}
?>
