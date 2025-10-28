<?php
require_once __DIR__ . '/../backend/pages/admin-includes/database.php';

echo "All tables in database:\n\n";

$result = $conn->query('SHOW TABLES');
while($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}

$conn->close();
?>
