<?php
require_once __DIR__ . '/../admin-includes/database.php';

echo "Cleaning up duplicate records...\n";
mysqli_query($conn, 'DELETE FROM order_status_settings WHERE id > 1');
echo "Done. Remaining records:\n";

$result = mysqli_query($conn, 'SELECT * FROM order_status_settings');
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']}, Enabled: {$row['auto_status_enabled']}\n";
}
?>
