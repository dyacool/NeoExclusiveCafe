<?php
// Test script to check coupon in database
require_once 'backend/pages/user-page-content/database-config.php';

$coupon_code = 'VCHR-1E5631D0';

try {
    $conn = getDBConnection();
    
    echo "Testing coupon: $coupon_code\n\n";
    
    // Check if coupon exists at all
    $sql1 = "SELECT * FROM promotions WHERE code = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("s", $coupon_code);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    
    if ($result1->num_rows === 0) {
        echo "❌ Coupon NOT FOUND in database\n";
    } else {
        $coupon = $result1->fetch_assoc();
        echo "✅ Coupon FOUND in database\n\n";
        echo "Details:\n";
        echo "- ID: " . $coupon['id'] . "\n";
        echo "- Code: " . $coupon['code'] . "\n";
        echo "- Title: " . $coupon['title'] . "\n";
        echo "- Status: " . $coupon['status'] . "\n";
        echo "- Type: " . $coupon['type'] . "\n";
        echo "- Value: " . $coupon['value'] . "\n";
        echo "- Activation Date: " . $coupon['activation_date'] . "\n";
        echo "- Expiration Date: " . $coupon['expiration_date'] . "\n";
        echo "- Used Count: " . $coupon['used_count'] . "\n";
        echo "- Usage Limit: " . $coupon['usage_limit'] . "\n";
        echo "- Min Purchase: " . $coupon['min_purchase'] . "\n\n";
        
        // Check validation conditions
        echo "Validation Checks:\n";
        
        // Status check
        if ($coupon['status'] === 'active') {
            echo "✅ Status is active\n";
        } else {
            echo "❌ Status is NOT active (current: " . $coupon['status'] . ")\n";
        }
        
        // Date checks
        $today = date('Y-m-d');
        echo "- Today's date: $today\n";
        
        if ($coupon['activation_date'] <= $today) {
            echo "✅ Activation date is valid (activated on or before today)\n";
        } else {
            echo "❌ Activation date is in the future\n";
        }
        
        if ($coupon['expiration_date'] >= $today) {
            echo "✅ Expiration date is valid (expires on or after today)\n";
        } else {
            echo "❌ Coupon has expired\n";
        }
        
        // Usage limit check
        if ($coupon['usage_limit'] > 0) {
            if ($coupon['used_count'] < $coupon['usage_limit']) {
                echo "✅ Usage limit OK (" . $coupon['used_count'] . "/" . $coupon['usage_limit'] . ")\n";
            } else {
                echo "❌ Usage limit reached (" . $coupon['used_count'] . "/" . $coupon['usage_limit'] . ")\n";
            }
        } else {
            echo "✅ No usage limit\n";
        }
    }
    
    $stmt1->close();
    
    // Now test with the actual validation query
    echo "\n\nTesting with actual validation query:\n";
    $sql2 = "SELECT * FROM promotions WHERE code = ? AND status = 'active' AND activation_date <= CURDATE() AND expiration_date >= CURDATE()";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $coupon_code);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($result2->num_rows === 0) {
        echo "❌ Coupon FAILED validation query\n";
    } else {
        echo "✅ Coupon PASSED validation query\n";
    }
    
    $stmt2->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
