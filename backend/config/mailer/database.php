<?php
$host = 'mysql-neoexclusivecafe.alwaysdata.net';
$dbname = 'neoexclusivecafe_crud';
$username = '429123';
$password = 'NeoCafe123';

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Return the connection object
return $conn;
?>
