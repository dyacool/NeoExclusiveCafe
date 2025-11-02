<?php
/**
 * Verify toggle API functionality
 */

require_once __DIR__ . '/../admin-includes/database.php';

echo "=== Verifying Toggle API Functionality ===\n\n";

// Test 1: Check current state
echo "1. Current auto-status state:\n";
$sql = "SELECT auto_status_enabled, updated_at FROM order_status_settings WHERE admin_id IS NULL";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "   Enabled: " . ($row['auto_status_enabled'] ? 'Yes' : 'No') . "\n";
    echo "   Updated: {$row['updated_at']}\n";
} else {
    echo "   No setting found (default: disabled)\n";
}

// Test 2: Enable auto-status
echo "\n2. Enabling auto-status...\n";
$enabled = 1;
$sql = "INSERT INTO order_status_settings (admin_id, auto_status_enabled) 
        VALUES (NULL, ?) 
        ON DUPLICATE KEY UPDATE auto_status_enabled = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $enabled, $enabled);
if (mysqli_stmt_execute($stmt)) {
    echo "   ✓ Successfully enabled\n";
} else {
    echo "   ✗ Failed: " . mysqli_stmt_error($stmt) . "\n";
}
mysqli_stmt_close($stmt);

// Test 3: Verify enabled state
echo "\n3. Verifying enabled state:\n";
$result = mysqli_query($conn, "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL ORDER BY updated_at DESC LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "   Current value: {$row['auto_status_enabled']}\n";
    if ($row['auto_status_enabled'] == 1) {
        echo "   ✓ Auto-status is enabled\n";
    } else {
        echo "   ✗ Auto-status is still disabled (value: {$row['auto_status_enabled']})\n";
    }
}

// Test 4: Disable auto-status
echo "\n4. Disabling auto-status...\n";
$enabled = 0;
$sql = "INSERT INTO order_status_settings (admin_id, auto_status_enabled) 
        VALUES (NULL, ?) 
        ON DUPLICATE KEY UPDATE auto_status_enabled = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $enabled, $enabled);
if (mysqli_stmt_execute($stmt)) {
    echo "   ✓ Successfully disabled\n";
} else {
    echo "   ✗ Failed: " . mysqli_stmt_error($stmt) . "\n";
}
mysqli_stmt_close($stmt);

// Test 5: Verify disabled state
echo "\n5. Verifying disabled state:\n";
$result = mysqli_query($conn, "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    if ($row['auto_status_enabled'] == 0) {
        echo "   ✓ Auto-status is disabled\n";
    } else {
        echo "   ✗ Auto-status is still enabled\n";
    }
}

echo "\n=== Verification Complete ===\n";
?>
