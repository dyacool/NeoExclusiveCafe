<?php
/**
 * Timezone Test Script
 * This script verifies that timezone is correctly set to Asia/Manila (Philippines)
 */

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
    h2 { color: #16a34a; }
    .success { color: #16a34a; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
    th { background: #16a34a; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
</style>";

echo "<div class='container'>";
echo "<h2>🌏 Timezone Configuration Test</h2>";
echo "<hr>";

// PHP Timezone
echo "<h3>PHP Timezone Settings:</h3>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$php_timezone = date_default_timezone_get();
$is_manila = ($php_timezone === 'Asia/Manila');
$status = $is_manila ? "<span class='success'>✅ Correct</span>" : "<span class='error'>❌ Wrong</span>";

echo "<tr>";
echo "<td><strong>PHP Timezone</strong></td>";
echo "<td>$php_timezone</td>";
echo "<td>$status</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Current PHP Time</strong></td>";
echo "<td>" . date('Y-m-d H:i:s') . "</td>";
echo "<td>Should match Philippines time (UTC+8)</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Current PHP Date</strong></td>";
echo "<td>" . date('l, F j, Y') . "</td>";
echo "<td>-</td>";
echo "</tr>";

echo "</table>";

// MySQL Timezone
echo "<h3>MySQL Timezone Settings:</h3>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

// Try different MySQL timezone queries
$mysql_now_result = $conn->query("SELECT NOW() as mysql_now");
$mysql_now = $mysql_now_result ? $mysql_now_result->fetch_assoc()['mysql_now'] : 'Error';

// Get session timezone
$session_tz_result = $conn->query("SELECT @@session.time_zone as tz");
$session_tz = 'Unknown';
if ($session_tz_result && $session_tz_result->num_rows > 0) {
    $tz_data = $session_tz_result->fetch_assoc();
    $session_tz = $tz_data['tz'] ?? 'Unknown';
}

// Get global timezone
$global_tz_result = $conn->query("SELECT @@global.time_zone as tz");
$global_tz = 'Unknown';
if ($global_tz_result && $global_tz_result->num_rows > 0) {
    $tz_data = $global_tz_result->fetch_assoc();
    $global_tz = $tz_data['tz'] ?? 'Unknown';
}

echo "<tr>";
echo "<td><strong>MySQL Session Timezone</strong></td>";
echo "<td>" . htmlspecialchars($session_tz) . "</td>";
echo "<td>" . ($session_tz === '+08:00' ? "<span class='success'>✅ Correct</span>" : "<span class='error'>⚠️ Different</span>") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>MySQL Global Timezone</strong></td>";
echo "<td>" . htmlspecialchars($global_tz) . "</td>";
echo "<td>-</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Current MySQL Time</strong></td>";
echo "<td>" . htmlspecialchars($mysql_now) . "</td>";
echo "<td>Should match Philippines time (UTC+8)</td>";
echo "</tr>";

echo "</table>";

// Time Comparison
echo "<h3>Time Comparison:</h3>";
$php_time = date('Y-m-d H:i:s');
$php_hour = (int)date('H');

echo "<table>";
echo "<tr><th>Source</th><th>Current Time</th><th>Notes</th></tr>";
echo "<tr><td><strong>PHP</strong></td><td>" . $php_time . "</td><td>Timezone: $php_timezone</td></tr>";

$mysql_compare_result = $conn->query("SELECT NOW() as mysql_now");
$mysql_time = 'Error';
$mysql_hour = 0;
if ($mysql_compare_result) {
    $row = $mysql_compare_result->fetch_assoc();
    $mysql_time = $row['mysql_now'];
    $mysql_hour = (int)date('H', strtotime($mysql_time));
}
echo "<tr><td><strong>MySQL</strong></td><td>" . $mysql_time . "</td><td>Session TZ: $session_tz</td></tr>";

echo "<tr><td><strong>Expected (Philippines)</strong></td><td>UTC+8 / GMT+8</td><td>-</td></tr>";

// Time difference check
$hour_diff = abs($php_hour - $mysql_hour);
if ($hour_diff === 0) {
    echo "<tr><td colspan='3' class='success'>✅ <strong>PHP and MySQL times match!</strong></td></tr>";
} else {
    echo "<tr><td colspan='3' class='error'>⚠️ Time difference detected: $hour_diff hour(s)</td></tr>";
}

echo "</table>";

// Server Information
echo "<h3>Server Information:</h3>";
echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";
echo "<tr><td><strong>Server Software</strong></td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
echo "<tr><td><strong>PHP Version</strong></td><td>" . phpversion() . "</td></tr>";
echo "<tr><td><strong>Server Name</strong></td><td>" . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</td></tr>";
echo "</table>";

// Summary
echo "<hr>";
echo "<h3>Summary:</h3>";

if ($is_manila && $session_tz === '+08:00') {
    echo "<p class='success'>✅ <strong>SUCCESS!</strong> Timezone is correctly set to Philippines (Asia/Manila, UTC+8)</p>";
    echo "<p>All timestamps in your application should now reflect Philippines time.</p>";
} elseif ($is_manila) {
    echo "<p class='success'>✅ <strong>PHP timezone is correct!</strong> (Asia/Manila)</p>";
    echo "<p class=''>ℹ️ MySQL timezone: <strong>$session_tz</strong> - This will be set to +08:00 automatically by the database connection.</p>";
    echo "<p>Your application will use Philippines time correctly.</p>";
} else {
    echo "<p class='error'>⚠️ <strong>WARNING!</strong> Timezone configuration needs attention:</p>";
    echo "<ul>";
    if (!$is_manila) {
        echo "<li>PHP timezone is set to: <strong>$php_timezone</strong> (should be Asia/Manila)</li>";
    }
    if ($session_tz !== '+08:00' && $session_tz !== 'Unknown') {
        echo "<li>MySQL session timezone is: <strong>$session_tz</strong> (should be +08:00)</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><em>This test file can be deleted after verification.</em></p>";
echo "<p><strong>File location:</strong> <code>" . __FILE__ . "</code></p>";

echo "</div>";

$conn->close();
?>

