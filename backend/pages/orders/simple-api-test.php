<?php
/**
 * Simple test - manually toggle and check database
 */

require_once __DIR__ . '/../admin-includes/database.php';

echo "=== Simple API Logic Test ===\n\n";

// Simulate enabling
echo "1. Enabling auto-status...\n";
$enabled = 1;
$check_sql = "SELECT id FROM order_status_settings WHERE admin_id IS NULL ORDER BY updated_at DESC LIMIT 1";
$check_result = mysqli_query($conn, $check_sql);

if ($check_result && mysqli_num_rows($check_result) > 0) {
    $row = mysqli_fetch_assoc($check_result);
    $record_id = $row['id'];
    $sql = "UPDATE order_status_settings SET auto_status_enabled = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $enabled, $record_id);
    mysqli_stmt_execute($stmt);
    echo "   Updated record ID: $record_id\n";
}

// Check result
$result = mysqli_query($conn, "SELECT * FROM order_status_settings WHERE admin_id IS NULL ORDER BY updated_at DESC LIMIT 1");
$row = mysqli_fetch_assoc($result);
echo "   Current state: Enabled = {$row['auto_status_enabled']}, ID = {$row['id']}\n";

// Simulate disabling
echo "\n2. Disabling auto-status...\n";
$enabled = 0;
$check_result = mysqli_query($conn, $check_sql);
if ($check_result && mysqli_num_rows($check_result) > 0) {
    $row = mysqli_fetch_assoc($check_result);
    $record_id = $row['id'];
    $sql = "UPDATE order_status_settings SET auto_status_enabled = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $enabled, $record_id);
    mysqli_stmt_execute($stmt);
    echo "   Updated record ID: $record_id\n";
}

// Check result
$result = mysqli_query($conn, "SELECT * FROM order_status_settings WHERE admin_id IS NULL ORDER BY updated_at DESC LIMIT 1");
$row = mysqli_fetch_assoc($result);
echo "   Current state: Enabled = {$row['auto_status_enabled']}, ID = {$row['id']}\n";

// Check for duplicates
echo "\n3. Checking for duplicates...\n";
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM order_status_settings WHERE admin_id IS NULL");
$row = mysqli_fetch_assoc($result);
echo "   Total records with admin_id = NULL: {$row['count']}\n";

if ($row['count'] == 1) {
    echo "   ✓ No duplicates created!\n";
} else {
    echo "   ✗ Duplicates found\n";
}

echo "\n=== Test Complete ===\n";
?>
