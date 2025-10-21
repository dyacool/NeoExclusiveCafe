<?php
function getDBConnection() {
    $servername = "mysql-neoexclusivecafe.alwaysdata.net";
    $username = "429123";
    $password = "NeoCafe123";
    $dbname = "neoexclusivecafe_crud";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

function createPromotionsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        code VARCHAR(20) UNIQUE NOT NULL,
        application_method ENUM('voucher_code', 'automatic_discount') NOT NULL DEFAULT 'voucher_code',
        type ENUM('percentage', 'fixed', 'free_shipping') NOT NULL,
        value DECIMAL(10,2) DEFAULT NULL,
        min_purchase DECIMAL(10,2) DEFAULT 0,
        applicable_to ENUM('all', 'delivery', 'pickup', 'special') NOT NULL DEFAULT 'all',
        usage_limit INT DEFAULT NULL,
        usage_limit_per_user INT DEFAULT NULL,
        used_count INT DEFAULT 0,
        activation_date DATE NOT NULL,
        expiration_date DATE NOT NULL,
        status ENUM('active', 'inactive', 'expired', 'upcoming') DEFAULT 'active',
        include_free_shipping TINYINT(1) DEFAULT 0,
        prevent_discounted TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
    } else {
        error_log("Error creating promotions table: " . $conn->error);
    }
    
    $result = $conn->query("SHOW TABLES LIKE 'promotions'");
    if ($result->num_rows > 0) {
        $columns_to_add = [
            "application_method ENUM('voucher_code', 'automatic_discount') NOT NULL DEFAULT 'voucher_code'",
            "type ENUM('percentage', 'fixed', 'free_shipping') NOT NULL",
            "value DECIMAL(10,2) DEFAULT NULL",
            "min_purchase DECIMAL(10,2) DEFAULT 0",
            "activation_date DATE NOT NULL",
            "expiration_date DATE NOT NULL",
            "include_free_shipping TINYINT(1) DEFAULT 0",
            "prevent_discounted TINYINT(1) DEFAULT 0"
        ];
        
        foreach ($columns_to_add as $column_def) {
            $column_name = explode(' ', $column_def)[0];
            $check_column = $conn->query("SHOW COLUMNS FROM promotions LIKE '$column_name'");
            
            if ($check_column->num_rows == 0) {
                $alter_sql = "ALTER TABLE promotions ADD COLUMN $column_def";
                if (!$conn->query($alter_sql)) {
                    error_log("Error adding column $column_name: " . $conn->error);
                }
            }
        }
        
        $column_mappings = [
            'discount_type' => 'type',
            'discount_value' => 'value', 
            'min_spend' => 'min_purchase',
            'start_date' => 'activation_date',
            'end_date' => 'expiration_date'
        ];
        
        foreach ($column_mappings as $old_name => $new_name) {
            $check_old = $conn->query("SHOW COLUMNS FROM promotions LIKE '$old_name'");
            $check_new = $conn->query("SHOW COLUMNS FROM promotions LIKE '$new_name'");
            
            if ($check_old->num_rows > 0 && $check_new->num_rows == 0) {
                $alter_sql = "ALTER TABLE promotions CHANGE COLUMN $old_name $new_name";
                if (!$conn->query($alter_sql)) {
                    error_log("Error renaming column $old_name to $new_name: " . $conn->error);
                }
            }
        }
    }
}
?>