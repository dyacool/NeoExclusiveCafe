<?php
require_once __DIR__ . '/../backend/pages/admin-includes/database.php';

echo "Checking products table structure...\n\n";

$sql = "DESCRIBE products";
$result = $conn->query($sql);

if ($result) {
    echo "Products table columns:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-30s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-30s %-20s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
