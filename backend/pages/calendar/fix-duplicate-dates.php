<?php
// Fix duplicate dates in date_limits table
require_once "../admin-includes/database.php";

echo "Starting date_limits table fix...\n\n";

try {
    // Step 1: Show current duplicates
    echo "Step 1: Checking for duplicate dates...\n";
    $duplicates = $conn->query("
        SELECT date, COUNT(*) as count 
        FROM date_limits 
        GROUP BY date 
        HAVING count > 1
    ");
    
    $dupCount = $duplicates->num_rows;
    echo "Found $dupCount duplicate date(s):\n";
    
    while ($row = $duplicates->fetch_assoc()) {
        echo "  - Date: {$row['date']} (appears {$row['count']} times)\n";
    }
    echo "\n";
    
    // Step 2: Delete older duplicates (keep most recent by id)
    echo "Step 2: Removing duplicate entries (keeping the most recent)...\n";
    $result = $conn->query("
        DELETE t1 FROM date_limits t1
        INNER JOIN date_limits t2 
        WHERE t1.id < t2.id 
        AND t1.date = t2.date
    ");
    
    if ($result) {
        echo "Successfully removed " . $conn->affected_rows . " duplicate row(s).\n\n";
    } else {
        throw new Exception("Failed to remove duplicates: " . $conn->error);
    }
    
    // Step 3: Check if UNIQUE constraint already exists
    echo "Step 3: Checking for existing UNIQUE constraint...\n";
    $indexes = $conn->query("SHOW INDEXES FROM date_limits WHERE Key_name = 'unique_date'");
    
    if ($indexes->num_rows > 0) {
        echo "UNIQUE constraint already exists on 'date' column. Skipping...\n\n";
    } else {
        echo "Adding UNIQUE constraint to 'date' column...\n";
        $result = $conn->query("ALTER TABLE date_limits ADD UNIQUE KEY unique_date (date)");
        
        if ($result) {
            echo "Successfully added UNIQUE constraint.\n\n";
        } else {
            throw new Exception("Failed to add UNIQUE constraint: " . $conn->error);
        }
    }
    
    // Step 4: Verify final state
    echo "Step 4: Verifying table structure...\n";
    $verification = $conn->query("
        SELECT date, COUNT(*) as count 
        FROM date_limits 
        GROUP BY date 
        HAVING count > 1
    ");
    
    if ($verification->num_rows === 0) {
        echo "✓ No duplicate dates found. Table is clean!\n\n";
    } else {
        echo "⚠ WARNING: Still found duplicates:\n";
        while ($row = $verification->fetch_assoc()) {
            echo "  - Date: {$row['date']} (appears {$row['count']} times)\n";
        }
    }
    
    // Show current table structure
    echo "\nCurrent date_limits indexes:\n";
    $indexes = $conn->query("SHOW INDEXES FROM date_limits");
    while ($row = $indexes->fetch_assoc()) {
        echo "  - {$row['Key_name']}: {$row['Column_name']} (Unique: " . ($row['Non_unique'] ? 'No' : 'Yes') . ")\n";
    }
    
    echo "\n✓ Fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
