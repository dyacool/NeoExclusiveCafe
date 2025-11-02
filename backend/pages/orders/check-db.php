<?php
require_once __DIR__ . '/../admin-includes/database.php';

$result = mysqli_query($conn, 'SELECT * FROM order_status_settings');
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: {$row['id']}, Admin ID: " . ($row['admin_id'] ?? 'NULL') . ", Enabled: {$row['auto_status_enabled']}, Updated: {$row['updated_at']}\n";
}
?>
