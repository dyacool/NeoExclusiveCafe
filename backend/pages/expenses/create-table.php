<?php
// Script to create the expenses table in the database
require_once __DIR__ . "/../admin-includes/database.php";

$sql = "CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` enum('Fixed Costs','Variable Costs','Overhead Costs') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `note` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql)) {
    echo "✓ Expenses table created successfully!<br>";
    echo "<br>Table structure:<br>";
    echo "- id (Primary Key)<br>";
    echo "- name (varchar 255)<br>";
    echo "- category (enum: Fixed Costs, Variable Costs, Overhead Costs)<br>";
    echo "- amount (decimal 10,2)<br>";
    echo "- note (varchar 100)<br>";
    echo "- created_at (datetime)<br>";
    echo "<br>";
    echo "You can now access the expense management page at: <a href='expense.php'>expense.php</a>";
} else {
    echo "✗ Error creating table: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?>
