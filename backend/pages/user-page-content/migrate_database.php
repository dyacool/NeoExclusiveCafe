<?php

require_once __DIR__ . '/database-config.php';

$conn = getDBConnection();

echo "Starting database migration...\n";

$result = $conn->query("SHOW TABLES LIKE 'promotions'");
if ($result->num_rows == 0) {
    echo "Creating new promotions table...\n";
    createPromotionsTable($conn);
    echo "Promotions table created successfully!\n";
} else {
    echo "Promotions table exists. Checking for missing columns...\n";
    
    $columns_result = $conn->query("SHOW COLUMNS FROM promotions");
    $existing_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "Existing columns: " . implode(', ', $existing_columns) . "\n";
    
    $columns_to_add = [
        "application_method" => "ENUM('voucher_code', 'automatic_discount') NOT NULL DEFAULT 'voucher_code'",
        "type" => "ENUM('percentage', 'fixed', 'free_shipping') NOT NULL",
        "value" => "DECIMAL(10,2) DEFAULT NULL",
        "min_purchase" => "DECIMAL(10,2) DEFAULT 0",
        "activation_date" => "DATE NOT NULL",
        "expiration_date" => "DATE NOT NULL",
        "include_free_shipping" => "TINYINT(1) DEFAULT 0",
        "prevent_discounted" => "TINYINT(1) DEFAULT 0"
    ];
    
    foreach ($columns_to_add as $column_name => $column_def) {
        if (!in_array($column_name, $existing_columns)) {
            echo "Adding column: $column_name\n";
            $alter_sql = "ALTER TABLE promotions ADD COLUMN $column_name $column_def";
            if ($conn->query($alter_sql)) {
                echo "✓ Column $column_name added successfully\n";
            } else {
                echo "✗ Error adding column $column_name: " . $conn->error . "\n";
            }
        } else {
            echo "✓ Column $column_name already exists\n";
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
        if (in_array($old_name, $existing_columns) && !in_array($new_name, $existing_columns)) {
            echo "Renaming column: $old_name -> $new_name\n";
            $alter_sql = "ALTER TABLE promotions CHANGE COLUMN $old_name $new_name";
            if ($conn->query($alter_sql)) {
                echo "✓ Column $old_name renamed to $new_name successfully\n";
            } else {
                echo "✗ Error renaming column $old_name: " . $conn->error . "\n";
            }
        }
    }
}

echo "\nFinal table structure:\n";
$result = $conn->query("DESCRIBE promotions");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
}

$conn->close();
echo "\nMigration completed!\n";
?>
