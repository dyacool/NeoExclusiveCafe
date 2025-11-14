<?php
require_once 'backend/pages/admin-includes/database.php';

echo "=== PENDING PAYMENTS TABLE CHECK ===\n\n";

// Check if table exists
$check_table = $conn->query("SHOW TABLES LIKE 'pending_payments'");
if ($check_table->num_rows > 0) {
    echo "✓ pending_payments table exists\n\n";
    
    // Get table structure
    echo "Table Structure:\n";
    $structure = $conn->query("DESCRIBE pending_payments");
    while ($row = $structure->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
    
    // Get recent records for user 13
    echo "Recent pending payments for user 13:\n";
    $result = $conn->query("SELECT * FROM pending_payments WHERE user_id = 13 ORDER BY created_at DESC LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "\nPayment ID: {$row['payment_id']}\n";
            echo "  Type: {$row['payment_type']}\n";
            echo "  Order Type: {$row['order_type']}\n";
            echo "  Amount: {$row['amount']}\n";
            echo "  Created: {$row['created_at']}\n";
            echo "  Expires: {$row['expires_at']}\n";
            echo "  Is Expired: " . (strtotime($row['expires_at']) < time() ? "YES" : "NO") . "\n";
        }
    } else {
        echo "  No records found for user 13\n";
    }
    
    // Check all recent records
    echo "\n\nAll recent pending payments (last 10):\n";
    $all_result = $conn->query("SELECT payment_id, user_id, payment_type, created_at, expires_at FROM pending_payments ORDER BY created_at DESC LIMIT 10");
    
    if ($all_result && $all_result->num_rows > 0) {
        while ($row = $all_result->fetch_assoc()) {
            $expired = strtotime($row['expires_at']) < time() ? "EXPIRED" : "ACTIVE";
            echo "  {$row['payment_id']} | User: {$row['user_id']} | Type: {$row['payment_type']} | {$expired}\n";
        }
    } else {
        echo "  No records found\n";
    }
    
} else {
    echo "✗ pending_payments table does NOT exist!\n";
    echo "\nYou need to create the table first.\n";
}

$conn->close();
?>
