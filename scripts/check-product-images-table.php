<?php
require_once __DIR__ . '/../backend/pages/admin-includes/database.php';

echo "Checking product_images table structure...\n\n";

$sql = "DESCRIBE product_images";
$result = $conn->query($sql);

if ($result) {
    echo "product_images table columns:\n";
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
    
    // Also check carousel_images
    echo "\n\nChecking carousel_images table structure...\n\n";
    $sql2 = "DESCRIBE carousel_images";
    $result2 = $conn->query($sql2);
    
    if ($result2) {
        echo "carousel_images table columns:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-30s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $result2->fetch_assoc()) {
            printf("%-30s %-20s %-10s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key']
            );
        }
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
