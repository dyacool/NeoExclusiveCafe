<?php
$hostname = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "crud";

// Create a connection
$conn = new mysqli($hostname, $dbUser, $dbPassword, $dbName);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Return the connection object
return $conn;
?>
