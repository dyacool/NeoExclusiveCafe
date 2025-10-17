<?php
/**
 * Debug Cart Truncation System
 * This script helps diagnose why cart truncation isn't working
 */

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
    h2 { color: #16a34a; }
    .success { color: #16a34a; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
    th { background: #16a34a; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    pre { background: #f9f9f9; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .button { display: inline-block; padding: 10px 20px; background: #16a34a; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .button:hover { background: #15803d; }
    .button.danger { background: #dc2626; }
    .button.danger:hover { background: #b91c1c; }
</style>";

echo "<div class='container'>";
echo "<h2>🔍 Cart Truncation Debug Tool</h2>";
echo "<hr>";

// Step 1: Check Business Hours
echo "<h3>1. Business Hours Configuration</h3>";
$hours_query = "SELECT * FROM business_hours ORDER BY id DESC LIMIT 1";
$hours_result = $conn->query($hours_query);

if ($hours_result && $hours_result->num_rows > 0) {
    $hours = $hours_result->fetch_assoc();
    $opening_time = $hours['opening_time'];
    $closing_time = $hours['closing_time'];
    
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td><strong>Opening Time</strong></td><td>$opening_time</td></tr>";
    echo "<tr><td><strong>Closing Time</strong></td><td>$closing_time</td></tr>";
    echo "<tr><td><strong>Last Updated</strong></td><td>{$hours['updated_at']}</td></tr>";
    echo "</table>";
} else {
    echo "<p class='error'>❌ No business hours configured!</p>";
}

// Step 2: Current Time
echo "<h3>2. Current Time Check</h3>";
$php_time = date('H:i:s');
$php_date = date('Y-m-d H:i:s');
$php_timezone = date_default_timezone_get();

$mysql_result = $conn->query("SELECT NOW() as mysql_now, TIME(NOW()) as mysql_time");
$mysql_data = $mysql_result->fetch_assoc();
$mysql_time = $mysql_data['mysql_time'];
$mysql_datetime = $mysql_data['mysql_now'];

echo "<table>";
echo "<tr><th>Source</th><th>Full DateTime</th><th>Time Only</th></tr>";
echo "<tr><td><strong>PHP</strong></td><td>$php_date</td><td>$php_time</td></tr>";
echo "<tr><td><strong>MySQL</strong></td><td>$mysql_datetime</td><td>$mysql_time</td></tr>";
echo "<tr><td><strong>PHP Timezone</strong></td><td colspan='2'>$php_timezone</td></tr>";
echo "</table>";

// Step 3: Business Status Logic
echo "<h3>3. Business Status Logic</h3>";

$current_time = $php_time;
$current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
$closing_minutes = (intval(substr($closing_time, 0, 2)) * 60) + intval(substr($closing_time, 3, 2));

$is_closed = false;
$reason = '';

if ($current_minutes < $closing_minutes && $current_minutes < 600) { 
    $is_closed = true;
    $reason = "Past midnight (current time before 10 AM and before closing time)";
} else if ($current_minutes >= $closing_minutes) {
    $is_closed = true;
    $reason = "Current time is at or after closing time";
} else {
    $reason = "Current time is before closing time";
}

echo "<table>";
echo "<tr><th>Check</th><th>Value</th></tr>";
echo "<tr><td><strong>Current Time (HH:MM:SS)</strong></td><td>$current_time</td></tr>";
echo "<tr><td><strong>Closing Time (HH:MM:SS)</strong></td><td>$closing_time</td></tr>";
echo "<tr><td><strong>Current Minutes</strong></td><td>$current_minutes</td></tr>";
echo "<tr><td><strong>Closing Minutes</strong></td><td>$closing_minutes</td></tr>";
echo "<tr><td><strong>Business Status</strong></td><td class='" . ($is_closed ? "error" : "success") . "'>" . ($is_closed ? "CLOSED ❌" : "OPEN ✅") . "</td></tr>";
echo "<tr><td><strong>Reason</strong></td><td>$reason</td></tr>";
echo "</table>";

// Step 4: Check Cart Items
echo "<h3>4. AvailToday Cart Status</h3>";
$cart_query = "SELECT COUNT(*) as cart_count FROM availtoday_cart";
$cart_result = $conn->query($cart_query);
$cart_data = $cart_result->fetch_assoc();
$cart_count = $cart_data['cart_count'];

echo "<table>";
echo "<tr><th>Metric</th><th>Value</th></tr>";
echo "<tr><td><strong>Total Items in Cart</strong></td><td>" . ($cart_count > 0 ? "<span class='warning'>$cart_count items</span>" : "<span class='success'>0 items (empty)</span>") . "</td></tr>";
echo "</table>";

if ($cart_count > 0) {
    $items_query = "SELECT c.*, p.name FROM availtoday_cart c JOIN products p ON c.product_id = p.id LIMIT 10";
    $items_result = $conn->query($items_query);
    
    echo "<p><strong>Cart Items:</strong></p>";
    echo "<table>";
    echo "<tr><th>Cart ID</th><th>User ID</th><th>Product Name</th><th>Quantity</th><th>Added At</th></tr>";
    
    while ($item = $items_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['user_id']}</td>";
        echo "<td>{$item['name']}</td>";
        echo "<td>{$item['quantity']}</td>";
        echo "<td>{$item['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 5: Check Truncation Script
echo "<h3>5. Truncation Script Check</h3>";
$script_path = __DIR__ . "/NeoExclusiveCafe/truncate-availtoday-cart.php";
$script_exists = file_exists($script_path);

echo "<table>";
echo "<tr><th>Check</th><th>Status</th></tr>";
echo "<tr><td><strong>Script Exists</strong></td><td>" . ($script_exists ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</td></tr>";
echo "<tr><td><strong>Script Path</strong></td><td><code>$script_path</code></td></tr>";
echo "<tr><td><strong>Script Permissions</strong></td><td>" . ($script_exists ? (is_readable($script_path) ? "✅ Readable" : "❌ Not Readable") : "N/A") . "</td></tr>";
echo "</table>";

// Step 6: Log File Check
echo "<h3>6. Truncation Log Check</h3>";
$log_path = __DIR__ . "/NeoExclusiveCafe/cart-truncation.log";
$log_exists = file_exists($log_path);

echo "<table>";
echo "<tr><th>Check</th><th>Status</th></tr>";
echo "<tr><td><strong>Log File Exists</strong></td><td>" . ($log_exists ? "<span class='success'>✅ Yes</span>" : "<span class='warning'>⚠️ No (will be created on first run)</span>") . "</td></tr>";
echo "<tr><td><strong>Log Path</strong></td><td><code>$log_path</code></td></tr>";
echo "</table>";

if ($log_exists) {
    $log_content = file_get_contents($log_path);
    if (!empty($log_content)) {
        $log_lines = explode("\n", $log_content);
        $recent_logs = array_slice(array_filter($log_lines), -10);
        
        echo "<p><strong>Recent Log Entries (last 10):</strong></p>";
        echo "<pre>" . htmlspecialchars(implode("\n", $recent_logs)) . "</pre>";
    } else {
        echo "<p class='warning'>⚠️ Log file is empty - script hasn't run yet</p>";
    }
}

// Step 7: Decision
echo "<hr>";
echo "<h3>7. Truncation Decision</h3>";

if ($is_closed && $cart_count > 0) {
    echo "<p class='error'>🔴 <strong>SHOULD TRUNCATE:</strong> Business is CLOSED and cart has $cart_count items</p>";
    echo "<p>The cart should be truncated now. If it's not working, check:</p>";
    echo "<ul>";
    echo "<li>Cron job is not set up</li>";
    echo "<li>Script has errors</li>";
    echo "<li>Database permissions</li>";
    echo "</ul>";
} elseif ($is_closed && $cart_count === 0) {
    echo "<p class='success'>✅ <strong>Business is CLOSED</strong> but cart is already empty</p>";
} elseif (!$is_closed && $cart_count > 0) {
    echo "<p class='warning'>⚠️ <strong>Business is OPEN:</strong> Cart has $cart_count items (will not truncate yet)</p>";
} else {
    echo "<p class='success'>✅ <strong>Business is OPEN</strong> and cart is empty</p>";
}

// Step 8: Action Buttons
echo "<hr>";
echo "<h3>8. Manual Actions</h3>";

echo "<a href='http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php' target='_blank' class='button'>Test Auto-Truncation (Time Check)</a>";
echo "<a href='http://neocafe.cafe:8080/NeoExclusiveCafe/truncate-availtoday-cart.php?force=1' target='_blank' class='button danger'>Force Truncate Now (Bypass Time)</a>";
echo "<a href='http://neocafe.cafe:8080/frontend/pages/cart/shopping-cart-sameday.php' target='_blank' class='button'>View Shopping Cart</a>";

echo "<hr>";
echo "<p><em>Refresh this page to see updated status</em></p>";

echo "</div>";

$conn->close();
?>

