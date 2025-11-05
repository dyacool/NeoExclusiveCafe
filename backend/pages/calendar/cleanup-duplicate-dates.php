<?php
// Clean up existing duplicate dates (keep the most recent entry for each date)
require_once "../admin-includes/database.php";

echo "Cleaning up duplicate date entries...\n\n";

try {
    // Show duplicates before cleanup
    echo "Duplicate dates BEFORE cleanup:\n";
    $result = $conn->query("
        SELECT date, GROUP_CONCAT(id ORDER BY id) as ids, COUNT(*) as count, 
               GROUP_CONCAT(limit_value ORDER BY id) as limits,
               GROUP_CONCAT(not_accepting_orders ORDER BY id) as not_accepting
        FROM date_limits 
        GROUP BY date 
        HAVING count > 1
        ORDER BY date
    ");
    
    $totalDuplicates = 0;
    while ($row = $result->fetch_assoc()) {
        echo "  Date: {$row['date']} - IDs: [{$row['ids']}] - Limits: [{$row['limits']}] - Not Accepting: [{$row['not_accepting']}]\n";
        $totalDuplicates += ($row['count'] - 1); // -1 because we keep one
    }
    echo "Total duplicate rows to remove: $totalDuplicates\n\n";
    
    if ($totalDuplicates > 0) {
        // Delete duplicates, keeping only the highest ID (most recent) for each date
        echo "Removing duplicates...\n";
        $deleteQuery = "
            DELETE t1 FROM date_limits t1
            INNER JOIN (
                SELECT date, MAX(id) as max_id
                FROM date_limits
                GROUP BY date
            ) t2 ON t1.date = t2.date
            WHERE t1.id < t2.max_id
        ";
        
        if ($conn->query($deleteQuery)) {
            echo "Successfully removed $totalDuplicates duplicate row(s).\n\n";
        } else {
            throw new Exception("Failed to remove duplicates: " . $conn->error);
        }
    } else {
        echo "No duplicates found!\n\n";
    }
    
    // Show final state
    echo "Verification - Dates AFTER cleanup:\n";
    $result = $conn->query("
        SELECT date, id, limit_value, not_accepting_orders 
        FROM date_limits 
        ORDER BY date
    ");
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['not_accepting_orders'] ? 'NOT ACCEPTING' : 'accepting';
        echo "  Date: {$row['date']} | ID: {$row['id']} | Limit: {$row['limit_value']} | Status: $status\n";
    }
    
    echo "\n✓ Cleanup completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
