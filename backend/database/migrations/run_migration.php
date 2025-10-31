<?php
/**
 * Database Migration Runner
 * Executes the saved_customer_info table migration
 */

// Include database connection
require_once '../../pages/admin-includes/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Saved Customer Info Table Migration ===\n\n";

// Read the migration SQL file
$migrationFile = __DIR__ . '/create_saved_customer_info_table.sql';

if (!file_exists($migrationFile)) {
    die("ERROR: Migration file not found: $migrationFile\n");
}

$sql = file_get_contents($migrationFile);

if ($sql === false) {
    die("ERROR: Could not read migration file\n");
}

echo "Migration file loaded successfully.\n";
echo "Executing migration...\n\n";

try {
    // Execute the migration
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
            // Check if there are more results
            if ($conn->more_results()) {
                echo ".";
            }
        } while ($conn->next_result());
        
        echo "\n\n✓ Migration executed successfully!\n\n";
        
        // Verify table was created
        $checkTable = "SHOW TABLES LIKE 'saved_customer_info'";
        $result = $conn->query($checkTable);
        
        if ($result && $result->num_rows > 0) {
            echo "✓ Table 'saved_customer_info' created successfully.\n";
            
            // Show table structure
            echo "\nTable Structure:\n";
            echo "================\n";
            $describe = $conn->query("DESCRIBE saved_customer_info");
            if ($describe) {
                while ($row = $describe->fetch_assoc()) {
                    echo sprintf(
                        "%-25s %-20s %-10s %-10s\n",
                        $row['Field'],
                        $row['Type'],
                        $row['Null'],
                        $row['Key']
                    );
                }
            }
            
            // Show indexes
            echo "\nIndexes:\n";
            echo "========\n";
            $indexes = $conn->query("SHOW INDEX FROM saved_customer_info");
            if ($indexes) {
                while ($row = $indexes->fetch_assoc()) {
                    echo sprintf(
                        "%-30s %-20s\n",
                        $row['Key_name'],
                        $row['Column_name']
                    );
                }
            }
            
            // Show foreign keys
            echo "\nForeign Keys:\n";
            echo "=============\n";
            $fks = $conn->query("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME,
                    DELETE_RULE
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = 'neoexclusivecafe_crud'
                AND TABLE_NAME = 'saved_customer_info'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            if ($fks) {
                while ($row = $fks->fetch_assoc()) {
                    echo sprintf(
                        "%-30s: %s -> %s.%s (ON DELETE %s)\n",
                        $row['CONSTRAINT_NAME'],
                        $row['COLUMN_NAME'],
                        $row['REFERENCED_TABLE_NAME'],
                        $row['REFERENCED_COLUMN_NAME'],
                        $row['DELETE_RULE']
                    );
                }
            }
            
        } else {
            echo "✗ ERROR: Table was not created.\n";
        }
        
    } else {
        throw new Exception($conn->error);
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Migration Complete ===\n";

$conn->close();
