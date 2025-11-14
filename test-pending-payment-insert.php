<?php
require_once 'backend/pages/admin-includes/database.php';

echo "=== TESTING PENDING PAYMENTS INSERT ===\n\n";

// Test data
$user_id = 13;
$payment_id = 'pi_TEST_' . time();
$payment_type = 'payment_intent';
$order_type = 'regular';
$amount = 100.00;
$payment_method = 'card';
$order_data_json = json_encode(['test' => 'data']);

echo "Attempting to insert test pending payment:\n";
echo "  User ID: $user_id\n";
echo "  Payment ID: $payment_id\n\n";

$save_sql = "INSERT INTO pending_payments 
             (user_id, payment_id, payment_type, order_type, amount, payment_method, order_data)
             VALUES (?, ?, ?, ?, ?, ?, ?)";

$save_stmt = $conn->prepare($save_sql);
if ($save_stmt) {
    $save_stmt->bind_param("isssdss", 
        $user_id,
        $payment_id,
        $payment_type,
        $order_type,
        $amount,
        $payment_method,
        $order_data_json
    );
    
    if ($save_stmt->execute()) {
        echo "✓ Insert successful!\n";
        echo "  Affected rows: " . $save_stmt->affected_rows . "\n";
        echo "  Insert ID: " . $save_stmt->insert_id . "\n\n";
        
        // Verify it was inserted
        $verify = $conn->query("SELECT * FROM pending_payments WHERE payment_id = '$payment_id'");
        if ($verify && $verify->num_rows > 0) {
            echo "✓ Verification: Record found in database\n";
            $row = $verify->fetch_assoc();
            echo "  Created at: {$row['created_at']}\n";
            echo "  Expires at: {$row['expires_at']}\n";
            
            // Delete test record
            $conn->query("DELETE FROM pending_payments WHERE payment_id = '$payment_id'");
            echo "\n✓ Test record cleaned up\n";
        } else {
            echo "✗ Verification: Record NOT found!\n";
        }
    } else {
        echo "✗ Execute failed: " . $save_stmt->error . "\n";
    }
    $save_stmt->close();
} else {
    echo "✗ Prepare failed: " . $conn->error . "\n";
}

$conn->close();
?>
