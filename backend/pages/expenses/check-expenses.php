<?php
require_once __DIR__ . '/../../login/admin/admin-auth.php';
require_once __DIR__ . "/../admin-includes/database.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Expenses Data</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .empty { color: red; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Expense Database Diagnostics</h1>
        
        <div class="info">
            <strong>Purpose:</strong> This page shows the raw data from the expenses table to diagnose why categories aren't displaying.
        </div>

        <?php
        // Check if table exists
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'expenses'");
        
        if (mysqli_num_rows($table_check) === 0) {
            echo "<p style='color: red;'>❌ The expenses table does not exist!</p>";
            echo "<p><a href='create-table.php'>Click here to create the table</a></p>";
            exit;
        }
        
        echo "<p style='color: green;'>✅ Table exists</p>";
        
        // Get table structure
        echo "<h2>Table Structure</h2>";
        $structure = mysqli_query($conn, "DESCRIBE expenses");
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($structure)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Get last 10 expenses
        echo "<h2>Last 10 Expenses (Raw Data)</h2>";
        $result = mysqli_query($conn, "SELECT * FROM expenses ORDER BY created_at DESC LIMIT 10");
        
        if (mysqli_num_rows($result) === 0) {
            echo "<p>No expenses found in the database.</p>";
        } else {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Amount</th><th>Note</th><th>Created At</th></tr>";
            
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                
                // Highlight empty categories
                $category_display = empty($row['category']) ? '<span class="empty">EMPTY</span>' : htmlspecialchars($row['category']);
                echo "<td>$category_display</td>";
                
                echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($row['note'] ?? '') . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show raw data dump
            echo "<h2>Raw Data Dump (Last Record)</h2>";
            mysqli_data_seek($result, 0);
            $last = mysqli_fetch_assoc($result);
            echo "<pre>" . print_r($last, true) . "</pre>";
        }
        
        mysqli_close($conn);
        ?>
        
        <p style="margin-top: 30px;">
            <a href="expense.php">← Back to Expenses</a>
        </p>
    </div>
</body>
</html>
