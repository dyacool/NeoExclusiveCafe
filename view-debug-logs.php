<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Log Viewer</h2>";
echo "<style>
    body { font-family: 'Courier New', monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
    h2 { color: #4ec9b0; }
    .log-entry { 
        padding: 8px;
        margin: 2px 0;
        border-left: 3px solid #555;
        background: #252526;
    }
    .log-entry.error { border-left-color: #f44336; background: #2d1f1f; }
    .log-entry.critical { border-left-color: #ff0000; background: #3d1f1f; font-weight: bold; }
    .log-entry.success { border-left-color: #4caf50; background: #1f2d1f; }
    .log-entry.debug { border-left-color: #2196f3; background: #1f252d; }
    .timestamp { color: #858585; }
    .highlight { background: #3a3d41; padding: 2px 4px; border-radius: 3px; }
    .section-header { 
        background: #007acc; 
        color: white; 
        padding: 10px; 
        margin: 20px 0 10px 0;
        border-radius: 4px;
        font-weight: bold;
    }
    .controls { 
        background: #2d2d30; 
        padding: 15px; 
        margin-bottom: 20px;
        border-radius: 4px;
    }
    button {
        background: #0e639c;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 10px;
    }
    button:hover { background: #1177bb; }
    .filter-input {
        padding: 8px;
        background: #3c3c3c;
        border: 1px solid #555;
        color: #d4d4d4;
        border-radius: 4px;
        width: 300px;
    }
</style>";

// Try to find PHP error log
$possible_log_locations = [
    ini_get('error_log'),
    '/var/log/php_errors.log',
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    __DIR__ . '/error.log',
    __DIR__ . '/php_errors.log',
];

$log_file = null;
foreach ($possible_log_locations as $location) {
    if ($location && file_exists($location) && is_readable($location)) {
        $log_file = $location;
        break;
    }
}

echo "<div class='controls'>";
echo "<h3 style='color: #4ec9b0; margin-top: 0;'>Log File Location</h3>";

if ($log_file) {
    echo "<p style='color: #4caf50;'>✓ Found log file: <span class='highlight'>" . htmlspecialchars($log_file) . "</span></p>";
} else {
    echo "<p style='color: #f44336;'>❌ Could not find PHP error log file</p>";
    echo "<p>Checked locations:</p><ul>";
    foreach ($possible_log_locations as $loc) {
        if ($loc) {
            echo "<li>" . htmlspecialchars($loc) . " - " . (file_exists($loc) ? "exists but not readable" : "not found") . "</li>";
        }
    }
    echo "</ul>";
    echo "<p>Current error_log setting: <span class='highlight'>" . htmlspecialchars(ini_get('error_log')) . "</span></p>";
}

echo "<form method='get' style='margin-top: 15px;'>";
echo "<input type='text' name='filter' class='filter-input' placeholder='Filter logs (e.g., PASSWORD RESET, LOGIN ATTEMPT)' value='" . htmlspecialchars($_GET['filter'] ?? '') . "'>";
echo "<button type='submit'>Filter</button>";
echo "<button type='submit' name='clear_filter'>Show All</button>";
echo "<button type='button' onclick='location.reload()'>Refresh</button>";
echo "</form>";
echo "</div>";

if ($log_file) {
    $filter = $_GET['filter'] ?? '';
    
    // Read last 500 lines
    $lines = [];
    $file = new SplFileObject($log_file);
    $file->seek(PHP_INT_MAX);
    $total_lines = $file->key();
    
    $start_line = max(0, $total_lines - 500);
    $file->seek($start_line);
    
    while (!$file->eof()) {
        $line = $file->current();
        $file->next();
        
        if (empty($filter) || stripos($line, $filter) !== false) {
            $lines[] = $line;
        }
    }
    
    echo "<div class='section-header'>Recent Log Entries (Last 500 lines" . ($filter ? ", filtered by: '$filter'" : "") . ")</div>";
    
    if (empty($lines)) {
        echo "<p style='color: #858585;'>No log entries found" . ($filter ? " matching filter" : "") . "</p>";
    } else {
        // Group related entries
        $in_debug_section = false;
        $debug_section_lines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Detect debug section boundaries
            if (strpos($line, '=== PASSWORD RESET DEBUG START ===') !== false || 
                strpos($line, '=== LOGIN ATTEMPT DEBUG START ===') !== false) {
                $in_debug_section = true;
                $debug_section_lines = [];
                echo "<div class='section-header' style='background: #0e639c;'>" . 
                     (strpos($line, 'PASSWORD RESET') !== false ? '🔐 PASSWORD RESET DEBUG' : '🔑 LOGIN ATTEMPT DEBUG') . 
                     "</div>";
                continue;
            }
            
            if (strpos($line, '=== PASSWORD RESET DEBUG END ===') !== false || 
                strpos($line, '=== LOGIN ATTEMPT DEBUG END ===') !== false) {
                $in_debug_section = false;
                continue;
            }
            
            // Classify log entry
            $class = 'log-entry';
            if (stripos($line, 'CRITICAL') !== false || stripos($line, 'FAIL') !== false) {
                $class .= ' critical';
            } elseif (stripos($line, 'error') !== false) {
                $class .= ' error';
            } elseif (stripos($line, 'PASS') !== false || stripos($line, 'success') !== false) {
                $class .= ' success';
            } elseif ($in_debug_section) {
                $class .= ' debug';
            }
            
            // Highlight important parts
            $line = htmlspecialchars($line);
            $line = preg_replace('/(\$2y\$\d+\$[^\s]+)/', '<span class="highlight">$1</span>', $line);
            $line = preg_replace('/(User ID: \d+)/', '<span class="highlight">$1</span>', $line);
            $line = preg_replace('/(Username: [^\s]+)/', '<span class="highlight">$1</span>', $line);
            $line = preg_replace('/(PASS|FAIL|SUCCESS|CRITICAL)/', '<strong>$1</strong>', $line);
            
            echo "<div class='$class'>$line</div>";
        }
    }
    
    echo "<div style='margin-top: 30px; padding: 15px; background: #2d2d30; border-radius: 4px;'>";
    echo "<h3 style='color: #4ec9b0;'>Instructions</h3>";
    echo "<ol style='color: #d4d4d4;'>";
    echo "<li>Try resetting a password using the forgot password flow</li>";
    echo "<li>Look for <strong>PASSWORD RESET DEBUG</strong> sections above</li>";
    echo "<li>Try logging in with the new password</li>";
    echo "<li>Look for <strong>LOGIN ATTEMPT DEBUG</strong> sections above</li>";
    echo "<li>Compare the hash values between reset and login</li>";
    echo "</ol>";
    echo "</div>";
}
?>
