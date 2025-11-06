<?php
/**
 * Setup Script for Pending Payments Table
 * Run this once to create the table that handles PayMongo session loss
 */

require_once '../pages/admin-includes/database.php';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Setup Pending Payments Table</title></head><body>\n";
echo "<h1>Setting up Pending Payments Table</h1>\n";

try {
    // Create the table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS pending_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        payment_id VARCHAR(255) NOT NULL UNIQUE COMMENT 'PayMongo source_id or payment_intent_id',
        payment_type ENUM('source', 'payment_intent') NOT NULL COMMENT 'Type of PayMongo payment',
        order_type ENUM('regular', 'availtoday') NOT NULL COMMENT 'Order type',
        amount DECIMAL(10,2) NOT NULL COMMENT 'Payment amount in PHP',
        payment_method VARCHAR(50) NOT NULL COMMENT 'gcash, paymaya, or card',
        order_data TEXT NOT NULL COMMENT 'JSON encoded order data',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 1 HOUR) COMMENT 'Auto-expire after 1 hour',
        
        INDEX idx_payment_id (payment_id),
        INDEX idx_user_id (user_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Backup storage for pending PayMongo payments to handle session loss'";
    
    if ($conn->query($create_table_sql)) {
        echo "<p style='color: green;'>✓ Table 'pending_payments' created successfully!</p>\n";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    // Try to create the cleanup event (may fail if EVENT scheduler is not enabled)
    $create_event_sql = "CREATE EVENT IF NOT EXISTS cleanup_expired_pending_payments
        ON SCHEDULE EVERY 1 HOUR
        DO DELETE FROM pending_payments WHERE expires_at < NOW()";
    
    if ($conn->query($create_event_sql)) {
        echo "<p style='color: green;'>✓ Cleanup event created successfully!</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠ Could not create cleanup event (this is optional): " . $conn->error . "</p>\n";
        echo "<p style='color: orange;'>You can manually clean up old records with: DELETE FROM pending_payments WHERE expires_at < NOW()</p>\n";
    }
    
    // Test the table
    $test_sql = "SELECT COUNT(*) as count FROM pending_payments";
    $test_result = $conn->query($test_sql);
    if ($test_result) {
        $test_row = $test_result->fetch_assoc();
        echo "<p style='color: green;'>✓ Table is working! Current records: " . $test_row['count'] . "</p>\n";
    }
    
    echo "<h2>Setup Complete!</h2>\n";
    echo "<p>The PayMongo session loss fix is now active.</p>\n";
    echo "<p><strong>What this fixes:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Handles session loss during PayMongo redirects</li>\n";
    echo "<li>Stores payment data in database as backup</li>\n";
    echo "<li>Automatically recovers payment data if session is lost</li>\n";
    echo "<li>Cleans up old records automatically</li>\n";
    echo "</ul>\n";
    echo "<p><a href='../../frontend/pages/cart/checkout.php'>Test Payment Flow</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
}

echo "</body></html>\n";
?>
