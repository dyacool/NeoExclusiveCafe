<?php
// Database connection
$host = 'mysql-neoexclusivecafe.alwaysdata.net';
$username = '429123';
$password = 'NeoCafe123';
$database = 'neoexclusivecafe_crud';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS bulk_payment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_image VARCHAR(500),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table bulk_payment created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
