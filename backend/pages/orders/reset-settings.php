<?php
require_once __DIR__ . '/../admin-includes/database.php';

echo "Resetting order_status_settings table...\n";

// Delete all records
mysqli_query($conn, 'DELETE FROM order_status_settings');
echo "✓ Deleted all records\n";

// Insert single default record
mysqli_query($conn, "INSERT INTO order_status_settings (admin_id, auto_status_enabled) VALUES (NULL, 0)");
echo "✓ Inserted default record\n";

// Verify
$result = mysqli_query($conn, 'SELECT * FROM order_status_settings');
echo "\nCurrent records:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']}, Admin ID: " . ($row['admin_id'] ?? 'NULL') . ", Enabled: {$row['auto_status_enabled']}, Updated: {$row['updated_at']}\n";
}

echo "\n✓ Reset complete\n";
?>
