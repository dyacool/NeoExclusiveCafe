<?php
// Script to update availToday_status for products based on current day
// This script can be run manually or via cron job to update daily
// Uses the separate availToday_status table

require_once __DIR__ . "/../admin-includes/config.php";

// Get current day of the week
$currentDay = date('l'); // Returns: Monday, Tuesday, Wednesday, etc.

echo "Updating availToday_status for $currentDay...\n";

try {
    $conn = new mysqli("localhost", "root", "", "crud");
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // First, set all Delivery and Pick Up products to not available today (0)
    $reset_sql = "UPDATE availToday_status ats 
                   INNER JOIN products p ON ats.product_id = p.id 
                   SET ats.is_available_today = 0 
                   WHERE p.status_id IN (1, 2) AND p.deleted_at IS NULL";
    
    $reset_result = $conn->query($reset_sql);
    if (!$reset_result) {
        throw new Exception("Error resetting availability status: " . $conn->error);
    }
    
    $reset_count = $conn->affected_rows;
    echo "Reset availability to 0 for $reset_count products\n";
    
    // Then, set products to available today (1) if they have the current day in their available_days
    $update_sql = "UPDATE availToday_status ats 
                   INNER JOIN products p ON ats.product_id = p.id 
                   INNER JOIN product_day pd ON p.id = pd.product_id 
                   SET ats.is_available_today = 1 
                   WHERE p.status_id IN (1, 2) 
                   AND p.deleted_at IS NULL 
                   AND pd.day_of_week = ?";
    
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        throw new Exception("Error preparing update statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $currentDay);
    $stmt->execute();
    
    $update_count = $stmt->affected_rows;
    echo "Set availability to 1 for $update_count products available on $currentDay\n";
    
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo "Successfully updated availToday_status!\n";
    echo "Total products available today: $update_count\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo "Update completed at " . date('Y-m-d H:i:s') . "\n";
?>
