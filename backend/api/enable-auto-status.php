<?php
/**
 * Quick script to enable auto-status
 * Run this once to enable the auto-status feature
 */

require_once '../pages/admin-includes/database.php';

// Enable auto-status
$sql = "INSERT INTO order_status_settings (admin_id, auto_status_enabled) 
        VALUES (NULL, 1) 
        ON DUPLICATE KEY UPDATE auto_status_enabled = 1";

if (mysqli_query($conn, $sql)) {
    echo "✅ Auto-status ENABLED successfully!\n";
    echo "The cron job will now automatically update order statuses.\n";
} else {
    echo "❌ Error: " . mysqli_error($conn) . "\n";
}

// Check current status
$check_sql = "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL LIMIT 1";
$result = mysqli_query($conn, $check_sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $status = $row['auto_status_enabled'] ? 'ENABLED' : 'DISABLED';
    echo "\nCurrent status: $status\n";
} else {
    echo "\nNo setting found in database.\n";
}

mysqli_close($conn);
?>
