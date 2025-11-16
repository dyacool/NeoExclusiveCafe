<?php
/**
 * Create Product Reviews Table Migration Script
 * Run this script to create the product_reviews table in the database
 */

require_once __DIR__ . '/../config/database-config.php';

$conn = getDatabaseConnection();

// Read SQL file
$sql_file = __DIR__ . '/../sql_configs/create_product_reviews_table.sql';
$sql = file_get_contents($sql_file);

if ($sql === false) {
    die("Error: Could not read SQL file: $sql_file\n");
}

// Split SQL into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    }
);

echo "Creating product_reviews table...\n\n";

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    // Skip comments and empty statements
    if (empty(trim($statement)) || strpos(trim($statement), '--') === 0) {
        continue;
    }
    
    // Execute statement
    if ($conn->multi_query($statement)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        
        $success_count++;
        echo "✓ Executed statement successfully\n";
    } else {
        $error_count++;
        echo "✗ Error executing statement: " . $conn->error . "\n";
        echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
    }
}

echo "\n";
echo "Migration completed!\n";
echo "Successful statements: $success_count\n";
echo "Errors: $error_count\n";

if ($error_count === 0) {
    echo "\n✓ Product reviews table created successfully!\n";
} else {
    echo "\n✗ Some errors occurred. Please check the output above.\n";
}

$conn->close();
?>

