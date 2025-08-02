<?php
// Prevent errors from being displayed directly
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";

// Set content type to html for debugging output
header('Content-Type: text/html');

echo "<h1>Database Tables Repair Tool</h1>";

// Check if tables exist
$tables_to_check = ['order_limits', 'date_limits', 'orderdate_status', 'orders'];
$missing_tables = [];

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
        echo "<p>Table '$table' does not exist.</p>";
    } else {
        echo "<p>Table '$table' exists.</p>";
        
        // If it's the orders table, check if it has the required columns
        if ($table === 'orders') {
            $required_columns = [
                'pickup_date' => 'DATE',
                'delivery_date' => 'DATE',
                'status' => 'VARCHAR(50)'
            ];
            
            $columns_result = $conn->query("SHOW COLUMNS FROM orders");
            $existing_columns = [];
            
            while ($column = $columns_result->fetch_assoc()) {
                $existing_columns[] = $column['Field'];
            }
            
            echo "<p>Checking required columns for orders table...</p>";
            $missing_columns = [];
            
            foreach ($required_columns as $column => $type) {
                if (!in_array($column, $existing_columns)) {
                    $missing_columns[$column] = $type;
                    echo "<p>Column '$column' is missing.</p>";
                }
            }
            
            if (!empty($missing_columns)) {
                echo "<p>Adding missing columns to orders table...</p>";
                
                foreach ($missing_columns as $column => $type) {
                    $alter_query = "ALTER TABLE orders ADD COLUMN $column $type";
                    if ($conn->query($alter_query)) {
                        echo "<p>Added column '$column' to orders table.</p>";
                    } else {
                        echo "<p style='color:red'>Failed to add column '$column': " . $conn->error . "</p>";
                    }
                }
            } else {
                echo "<p>All required columns exist in orders table.</p>";
            }
        }
    }
}

// Create missing tables
if (!empty($missing_tables)) {
    echo "<h2>Creating missing tables...</h2>";
    
    try {
        // Create order_limits table (for default limit)
        if (in_array('order_limits', $missing_tables)) {
            $conn->query("
                CREATE TABLE IF NOT EXISTS order_limits (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    default_limit INT NOT NULL DEFAULT 10,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "<p>Created order_limits table.</p>";
            
            // Insert default limit
            $conn->query("INSERT INTO order_limits (id, default_limit) VALUES (1, 10)");
            echo "<p>Default order limit of 10 has been set.</p>";
        }

        // Create date_limits table (for date-specific limits)
        if (in_array('date_limits', $missing_tables)) {
            $conn->query("
                CREATE TABLE IF NOT EXISTS date_limits (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    date DATE NOT NULL,
                    limit_value INT NOT NULL DEFAULT 0,
                    not_accepting_orders BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_date (date)
                )
            ");
            echo "<p>Created date_limits table.</p>";
        }

        // Create orderdate_status table (for date statuses)
        if (in_array('orderdate_status', $missing_tables)) {
            $conn->query("
                CREATE TABLE IF NOT EXISTS orderdate_status (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    date DATE NOT NULL,
                    status ENUM('accepting', 'not_accepting') NOT NULL DEFAULT 'accepting',
                    reason VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_date (date)
                )
            ");
            echo "<p>Created orderdate_status table.</p>";
        }

        // Create minimal orders table if it doesn't exist
        if (in_array('orders', $missing_tables)) {
            $conn->query("
                CREATE TABLE IF NOT EXISTS orders (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    order_id INT UNIQUE NOT NULL,
                    customer_name VARCHAR(255) NOT NULL,
                    customer_contact VARCHAR(50),
                    customer_address TEXT,
                    payment_method VARCHAR(50),
                    delivery_method VARCHAR(50),
                    delivery_date DATE,
                    pickup_date DATE,
                    delivery_time TIME,
                    notes TEXT,
                    total_items INT,
                    total_amount DECIMAL(10,2),
                    status VARCHAR(50) DEFAULT 'Pending',
                    order_date DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            echo "<p>Created orders table with minimal structure.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Error creating tables: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2>All required tables exist.</h2>";
}

// Test the query from get-date-limits.php
echo "<h2>Testing query from get-date-limits.php...</h2>";

try {
    // Get default limit
    $default_query = "SELECT default_limit FROM order_limits WHERE id = 1";
    $default_result = $conn->query($default_query);
    
    if (!$default_result) {
        throw new Exception("Error in default_limit query: " . $conn->error);
    }
    
    $default_limit = $default_result->fetch_assoc()['default_limit'] ?? 10;
    echo "<p>Default limit: $default_limit</p>";

    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+30 days'));
    
    echo "<p>Testing date range query for $start_date to $end_date</p>";
    
    // Test the query that's failing
    $query = "SELECT 
        d.date,
        COALESCE(dl.limit_value, $default_limit) as limit_value,
        COUNT(o.order_id) as count,
        COUNT(CASE WHEN o.status IN ('Pending', 'Accepted') THEN 1 END) as active_orders,
        os.status as order_status,
        os.reason as status_reason
    FROM (
        SELECT DATE_ADD('$start_date', INTERVAL n DAY) as date
        FROM (
            SELECT @row := @row + 1 as n
            FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t1,
                 (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t2,
                 (SELECT @row := -1) t3
        ) numbers
        WHERE DATE_ADD('$start_date', INTERVAL n DAY) <= '$end_date'
    ) d
    LEFT JOIN date_limits dl ON d.date = dl.date
    LEFT JOIN orders o ON d.date = o.pickup_date OR d.date = o.delivery_date
    LEFT JOIN orderdate_status os ON d.date = os.date
    GROUP BY d.date, dl.limit_value, os.status, os.reason
    ORDER BY d.date";

    // Test the query
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Error in date range query: " . $conn->error);
    }
    
    echo "<p style='color:green'>Date range query executed successfully!</p>";
    echo "<p>Number of rows returned: " . $result->num_rows . "</p>";
    
    // Add a test date with a limit
    $test_date = date('Y-m-d', strtotime('+5 days'));
    $conn->query("INSERT INTO date_limits (date, limit_value) VALUES ('$test_date', 5) ON DUPLICATE KEY UPDATE limit_value = 5");
    echo "<p>Added test date limit: $test_date with limit of 5</p>";
    
    // Add a test date that's not accepting orders
    $test_date_no_orders = date('Y-m-d', strtotime('+7 days'));
    $conn->query("INSERT INTO orderdate_status (date, status, reason) VALUES ('$test_date_no_orders', 'not_accepting', 'Test: Not accepting orders') ON DUPLICATE KEY UPDATE status = 'not_accepting', reason = 'Test: Not accepting orders'");
    echo "<p>Added test not-accepting date: $test_date_no_orders</p>";
    
    // Show a few rows
    $rows = [];
    $i = 0;
    while ($row = $result->fetch_assoc()) {
        if ($i++ < 5) {
            $rows[] = $row;
        }
    }
    
    echo "<pre>";
    print_r($rows);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error testing query: " . $e->getMessage() . "</p>";
}

echo "<h2>Repair completed!</h2>";
echo "<p>Please navigate back to the <a href='/NeoExclusiveCafe/pages/admin/admin-homepage.php'>Admin Dashboard</a> to verify the fix.</p>";
?> 