<?php
header('Content-Type: application/json');

echo "=== Force Cart Truncation ===\n";

try {
    require_once __DIR__ . "/../backend/pages/admin-includes/database.php";
    
    echo "Database connection: OK\n";
    
    // Check if cart_availtoday table exists
    $checkCartTableQuery = "SHOW TABLES LIKE 'cart_availtoday'";
    $cartTableExists = $conn->query($checkCartTableQuery);
    
    if (!$cartTableExists) {
        echo "ERROR: Failed to check cart_availtoday table: " . $conn->error . "\n";
        exit;
    }
    
    if ($cartTableExists->num_rows == 0) {
        echo "ERROR: cart_availtoday table does not exist\n";
        exit;
    }
    
    echo "cart_availtoday table exists\n";
    
    // Check if cart has items before truncating
    $count_query = "SELECT COUNT(*) as cart_count FROM cart_availtoday";
    $count_result = $conn->query($count_query);
    
    if (!$count_result) {
        echo "ERROR: Failed to count cart items: " . $conn->error . "\n";
        exit;
    }
    
    $count_data = $count_result->fetch_assoc();
    $cart_count = $count_data['cart_count'];
    echo "Cart currently has $cart_count items\n";
    
    if ($cart_count > 0) {
        echo "Proceeding to truncate cart...\n";
        
        // Truncate the cart_availtoday table
        $truncate_query = "TRUNCATE TABLE cart_availtoday";
        $truncate_result = $conn->query($truncate_query);
        
        if ($truncate_result) {
            echo "✓ SUCCESS: Cart truncated successfully - $cart_count items removed\n";
            
            // Verify truncation
            $verify_query = "SELECT COUNT(*) as cart_count FROM cart_availtoday";
            $verify_result = $conn->query($verify_query);
            if ($verify_result) {
                $verify_data = $verify_result->fetch_assoc();
                $new_count = $verify_data['cart_count'];
                echo "Verification: Cart now has $new_count items\n";
            }
            
        } else {
            echo "ERROR: Failed to truncate cart: " . $conn->error . "\n";
        }
    } else {
        echo "Cart is already empty - no action needed\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: Exception occurred: " . $e->getMessage() . "\n";
}

echo "\n=== Force Truncation Complete ===\n";
?>
