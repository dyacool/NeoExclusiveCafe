<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable mysqli exception mode for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Load database connection
require_once __DIR__ . '/../admin-includes/database.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Table Diagnostic</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .highlight {
            background-color: #fff3cd;
            font-weight: bold;
        }
        pre {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Orders Table Diagnostic Report</h1>
        <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

        <?php
        try {
            // Check if connection exists
            if (!isset($conn) || !$conn) {
                throw new Exception("Database connection not established");
            }

            echo '<div class="success">✓ Database connection successful</div>';

            // 1. Check if orders table exists
            echo '<h2>1. Table Existence Check</h2>';
            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
            
            if (mysqli_num_rows($table_check) > 0) {
                echo '<div class="success">✓ Table "orders" exists</div>';
            } else {
                echo '<div class="error">✗ Table "orders" does NOT exist!</div>';
                echo '<div class="warning">This is the root cause of the error. The orders table needs to be created.</div>';
                exit;
            }

            // 2. Get column information
            echo '<h2>2. Column Structure</h2>';
            $columns_result = mysqli_query($conn, "DESCRIBE orders");
            
            if ($columns_result) {
                echo '<table>';
                echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
                echo '<tbody>';
                
                $has_status_column = false;
                $status_column_info = null;
                
                while ($column = mysqli_fetch_assoc($columns_result)) {
                    $row_class = '';
                    if ($column['Field'] === 'status') {
                        $row_class = 'class="highlight"';
                        $has_status_column = true;
                        $status_column_info = $column;
                    }
                    
                    echo "<tr $row_class>";
                    echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
                    echo "</tr>";
                }
                
                echo '</tbody></table>';
                
                // Status column check
                echo '<h2>3. Status Column Analysis</h2>';
                if ($has_status_column) {
                    echo '<div class="success">✓ Status column EXISTS <span class="badge badge-success">FOUND</span></div>';
                    echo '<div class="info">';
                    echo '<strong>Status Column Details:</strong><br>';
                    echo 'Type: ' . htmlspecialchars($status_column_info['Type']) . '<br>';
                    echo 'Nullable: ' . htmlspecialchars($status_column_info['Null']) . '<br>';
                    echo 'Default: ' . htmlspecialchars($status_column_info['Default'] ?? 'NULL') . '<br>';
                    echo '</div>';
                } else {
                    echo '<div class="error">✗ Status column does NOT exist! <span class="badge badge-danger">MISSING</span></div>';
                    echo '<div class="warning">This is the root cause of the error. The status column needs to be added to the orders table.</div>';
                }
            }

            // 3. Get CREATE TABLE statement
            echo '<h2>4. Full Table Definition</h2>';
            $create_result = mysqli_query($conn, "SHOW CREATE TABLE orders");
            
            if ($create_result) {
                $create_row = mysqli_fetch_assoc($create_result);
                echo '<pre>' . htmlspecialchars($create_row['Create Table']) . '</pre>';
            }

            // 4. Check for any data in the table
            echo '<h2>5. Data Check</h2>';
            $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
            $count_row = mysqli_fetch_assoc($count_result);
            
            echo '<div class="info">';
            echo '<strong>Total Orders:</strong> ' . number_format($count_row['total']) . ' records';
            echo '</div>';

            // 5. Test the problematic query
            echo '<h2>6. Query Test</h2>';
            echo '<p>Testing the query that was causing the error...</p>';
            
            try {
                $test_sql = "SELECT * FROM `orders` WHERE LOWER(TRIM(`status`)) = LOWER(?) LIMIT 1";
                $test_stmt = mysqli_prepare($conn, $test_sql);
                
                if ($test_stmt) {
                    $test_status = 'Pending';
                    mysqli_stmt_bind_param($test_stmt, "s", $test_status);
                    
                    if (mysqli_stmt_execute($test_stmt)) {
                        echo '<div class="success">✓ Query executed successfully!</div>';
                        echo '<div class="info">The query syntax is correct and the status column is accessible.</div>';
                    } else {
                        echo '<div class="error">✗ Query execution failed: ' . mysqli_stmt_error($test_stmt) . '</div>';
                    }
                    
                    mysqli_stmt_close($test_stmt);
                } else {
                    echo '<div class="error">✗ Query preparation failed: ' . mysqli_error($conn) . '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">✗ Query test failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            // 6. Check for status values
            if ($has_status_column) {
                echo '<h2>7. Status Values Distribution</h2>';
                $status_dist = mysqli_query($conn, "SELECT `status`, COUNT(*) as count FROM `orders` GROUP BY `status` ORDER BY count DESC");
                
                if ($status_dist && mysqli_num_rows($status_dist) > 0) {
                    echo '<table>';
                    echo '<thead><tr><th>Status Value</th><th>Count</th></tr></thead>';
                    echo '<tbody>';
                    
                    while ($status_row = mysqli_fetch_assoc($status_dist)) {
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($status_row['status'] ?? 'NULL') . '</strong></td>';
                        echo '<td>' . number_format($status_row['count']) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<div class="info">No status values found (table might be empty)</div>';
                }
            }

            // Summary
            echo '<h2>8. Diagnostic Summary</h2>';
            if ($has_status_column) {
                echo '<div class="success">';
                echo '<strong>✓ DIAGNOSIS COMPLETE</strong><br><br>';
                echo 'The orders table exists and has a status column. The database schema appears correct.<br><br>';
                echo '<strong>Possible causes of the error:</strong><br>';
                echo '• The error might be intermittent or already resolved<br>';
                echo '• There might be a caching issue<br>';
                echo '• The error might be occurring in a different context or query<br>';
                echo '• Check the error logs for the exact query that failed<br>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<strong>✗ ISSUE IDENTIFIED</strong><br><br>';
                echo 'The status column is missing from the orders table.<br><br>';
                echo '<strong>Recommended action:</strong><br>';
                echo '• Run a database migration to add the status column<br>';
                echo '• Or restore from the SQL backup file that includes the status column<br>';
                echo '</div>';
            }

        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>✗ Diagnostic Error:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            
            echo '<pre>';
            echo htmlspecialchars($e->getTraceAsString());
            echo '</pre>';
        }
        ?>
    </div>
</body>
</html>
