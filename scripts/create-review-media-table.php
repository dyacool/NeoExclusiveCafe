<?php
/**
 * Create review_media table for storing review images and videos
 * Run this script once to add the table to your database
 */

require_once __DIR__ . '/../backend/pages/admin-includes/database.php';

echo "Creating review_media table...\n\n";

// Read SQL file
$sql_file = __DIR__ . '/create-review-media-table.sql';
if (!file_exists($sql_file)) {
    die("Error: SQL file not found at $sql_file\n");
}

$sql = file_get_contents($sql_file);

// Split into individual statements (separated by semicolons)
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    try {
        if ($conn->query($statement)) {
            $success_count++;
            echo "✓ Statement executed successfully\n";
        } else {
            $error_count++;
            echo "✗ Error: " . $conn->error . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
        }
    } catch (Exception $e) {
        $error_count++;
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "Migration completed!\n";
echo "Successful statements: $success_count\n";
echo "Failed statements: $error_count\n";

// Verify table was created
$result = $conn->query("SHOW TABLES LIKE 'review_media'");
if ($result && $result->num_rows > 0) {
    echo "\n✓ Table 'review_media' successfully created!\n";
    
    // Show table structure
    echo "\nTable structure:\n";
    $structure = $conn->query("DESCRIBE review_media");
    if ($structure) {
        echo str_repeat("-", 70) . "\n";
        printf("%-20s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
        echo str_repeat("-", 70) . "\n";
        while ($row = $structure->fetch_assoc()) {
            printf("%-20s %-20s %-10s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key']
            );
        }
        echo str_repeat("-", 70) . "\n";
    }
} else {
    echo "\n✗ Table 'review_media' was not created. Please check the errors above.\n";
}

$conn->close();
?>
