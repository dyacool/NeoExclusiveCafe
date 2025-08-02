<?php
require_once "../../php/includes/database.php";

// Function to execute SQL and handle errors
function executeSQL($conn, $sql) {
    if (!$conn->query($sql)) {
        echo "Error executing SQL: " . $conn->error . "\n";
        echo "SQL: " . $sql . "\n";
        return false;
    }
    return true;
}

// Create customers table if it doesn't exist
$createCustomersTable = "CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    contact VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (executeSQL($conn, $createCustomersTable)) {
    echo "Customers table created or already exists\n";
}

// Add missing columns to orders table
$addPickupTime = "ALTER TABLE orders 
                  ADD COLUMN IF NOT EXISTS pickup_time TIME DEFAULT NULL";

$addCustomerId = "ALTER TABLE orders 
                  ADD COLUMN IF NOT EXISTS customer_id INT,
                  ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id)";

if (executeSQL($conn, $addPickupTime)) {
    echo "Added pickup_time column to orders table\n";
}

if (executeSQL($conn, $addCustomerId)) {
    echo "Added customer_id column to orders table\n";
}

// Verify the changes
$checkOrders = "DESCRIBE orders";
$result = $conn->query($checkOrders);
if ($result) {
    echo "\nOrders table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

$checkCustomers = "DESCRIBE customers";
$result = $conn->query($checkCustomers);
if ($result) {
    echo "\nCustomers table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

echo "\nDatabase setup completed. Please check the output above for any errors.\n";
?> 