<?php
// Migration script to update the expenses table and convert old categories to new ones
require_once __DIR__ . "/../admin-includes/database.php";

echo "<h2>Expense Category Migration</h2>";
echo "<pre>";

// Step 1: Check if table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'expenses'");
if (mysqli_num_rows($table_check) === 0) {
    echo "❌ Expenses table does not exist. Please run create-table.php first.\n";
    exit;
}

echo "✓ Table exists\n\n";

// Step 2: Check current column type
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM expenses LIKE 'category'");
$column_info = mysqli_fetch_assoc($column_check);
echo "Current category column type: " . $column_info['Type'] . "\n\n";

// Step 3: Get all unique current categories
$categories = mysqli_query($conn, "SELECT DISTINCT category FROM expenses ORDER BY category");
echo "Current categories in database:\n";
$old_categories = [];
while ($row = mysqli_fetch_assoc($categories)) {
    $cat = $row['category'];
    $old_categories[] = $cat;
    echo "  - " . ($cat ?: '(empty)') . "\n";
}
echo "\n";

// Step 4: Change column to VARCHAR to accept any value temporarily
echo "Step 1: Converting category column to VARCHAR...\n";
$alter_sql = "ALTER TABLE expenses MODIFY COLUMN category VARCHAR(100)";
if (mysqli_query($conn, $alter_sql)) {
    echo "✓ Column converted to VARCHAR\n\n";
} else {
    echo "❌ Error: " . mysqli_error($conn) . "\n";
    exit;
}

// Step 5: Update existing data to match new categories
echo "Step 2: Mapping old categories to new categories...\n";

// Define mapping rules
$category_mapping = [
    // Map old values to new ENUM values
    'INGREDIENTS' => 'Variable Costs',
    'ELECTRIC BILL' => 'Fixed Costs',
    'FLOUR' => 'Variable Costs',
    'STAFF SALARY' => 'Fixed Costs',
    'SALARY' => 'Fixed Costs',
    'BILLS' => 'Fixed Costs',
    'PACKAGING' => 'Variable Costs',
    'UTILITIES' => 'Variable Costs',
    'EQUIPMENT MAINTENANCE' => 'Overhead Costs',
    'MAINTENANCE' => 'Overhead Costs',
    // Add empty/null handling
    '' => 'Variable Costs' // Default for empty categories
];

$updated_count = 0;
foreach ($category_mapping as $old_cat => $new_cat) {
    $update_sql = "UPDATE expenses SET category = ? WHERE category = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ss", $new_cat, $old_cat);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            echo "  ✓ Updated $affected records: '$old_cat' → '$new_cat'\n";
            $updated_count += $affected;
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle any remaining unmapped categories - set them to Variable Costs as default
$remaining_sql = "UPDATE expenses SET category = 'Variable Costs' 
                  WHERE category NOT IN ('Fixed Costs', 'Variable Costs', 'Overhead Costs')";
if (mysqli_query($conn, $remaining_sql)) {
    $affected = mysqli_affected_rows($conn);
    if ($affected > 0) {
        echo "  ✓ Updated $affected remaining records to 'Variable Costs' (default)\n";
        $updated_count += $affected;
    }
}

echo "\nTotal records updated: $updated_count\n\n";

// Step 6: Convert back to ENUM with new values
echo "Step 3: Converting category column to ENUM with new values...\n";
$enum_sql = "ALTER TABLE expenses MODIFY COLUMN category ENUM('Fixed Costs', 'Variable Costs', 'Overhead Costs') NOT NULL";
if (mysqli_query($conn, $enum_sql)) {
    echo "✓ Column converted to ENUM successfully\n\n";
} else {
    echo "❌ Error: " . mysqli_error($conn) . "\n";
    exit;
}

// Step 7: Verify the migration
echo "Step 4: Verifying migration...\n";
$verify = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM expenses GROUP BY category ORDER BY category");
echo "Current category distribution:\n";
while ($row = mysqli_fetch_assoc($verify)) {
    echo "  - " . $row['category'] . ": " . $row['count'] . " records\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ MIGRATION COMPLETE!\n";
echo str_repeat("=", 50) . "\n\n";
echo "You can now return to <a href='expense.php'>expense.php</a>\n";
echo "</pre>";

mysqli_close($conn);
?>
