<?php
header('Content-Type: application/json');

echo "=== Database Tables Check ===\n";

try {
    require_once __DIR__ . "/../backend/pages/admin-includes/database.php";
    
    echo "Database connection: OK\n";
    
    // Get all tables
    $tables_query = "SHOW TABLES";
    $tables_result = $conn->query($tables_query);
    
    if (!$tables_result) {
        echo "ERROR: Failed to get tables: " . $conn->error . "\n";
        exit;
    }
    
    echo "Tables in database:\n";
    while ($row = $tables_result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
    
    // Check for cart-related tables specifically
    echo "\nChecking for cart-related tables:\n";
    
    $cart_tables = [
        'cart_availtoday',
        'cart_availToday',
        'cart_available_today',
        'cart_availabletoday',
        'availtoday_cart',
        'available_today_cart'
    ];
    
    foreach ($cart_tables as $table_name) {
        $check_query = "SHOW TABLES LIKE '$table_name'";
        $check_result = $conn->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            echo "✓ Found table: $table_name\n";
            
            // Check table structure
            $structure_query = "DESCRIBE $table_name";
            $structure_result = $conn->query($structure_query);
            
            if ($structure_result) {
                echo "  Columns:\n";
                while ($col = $structure_result->fetch_assoc()) {
                    echo "    - " . $col['Field'] . " (" . $col['Type'] . ")\n";
                }
            }
            
            // Check row count
            $count_query = "SELECT COUNT(*) as row_count FROM $table_name";
            $count_result = $conn->query($count_query);
            
            if ($count_result) {
                $count_data = $count_result->fetch_assoc();
                echo "  Row count: " . $count_data['row_count'] . "\n";
            }
            
        } else {
            echo "✗ Table not found: $table_name\n";
        }
    }
    
    // Check business hours table
    echo "\nChecking business hours table:\n";
    $business_hours_check = "SHOW TABLES LIKE 'business_hours'";
    $business_hours_result = $conn->query($business_hours_check);
    
    if ($business_hours_result && $business_hours_result->num_rows > 0) {
        echo "✓ business_hours table exists\n";
        
        // Get business hours data
        $hours_query = "SELECT * FROM business_hours ORDER BY id DESC LIMIT 5";
        $hours_result = $conn->query($hours_query);
        
        if ($hours_result) {
            echo "  Business hours data:\n";
            while ($hour = $hours_result->fetch_assoc()) {
                echo "    - ID: " . $hour['id'] . ", Opening: " . $hour['opening_time'] . ", Closing: " . $hour['closing_time'] . "\n";
            }
        }
    } else {
        echo "✗ business_hours table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: Exception occurred: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
