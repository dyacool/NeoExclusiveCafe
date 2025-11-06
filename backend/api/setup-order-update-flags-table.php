<?php
/**
 * Setup script for order_update_flags table
 * Run this once to create the table for real-time order notifications
 */

require_once __DIR__ . '/../pages/admin-includes/database.php';

header('Content-Type: application/json');

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/create-order-update-flags-table.sql');
    
    if ($sql === false) {
        throw new Exception('Failed to read SQL file');
    }
    
    // Split by semicolons to execute multiple statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $results = [];
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        $result = mysqli_query($conn, $statement);
        
        if ($result === false) {
            $results[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'success' => false,
                'error' => mysqli_error($conn)
            ];
        } else {
            $results[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'success' => true
            ];
        }
    }
    
    // Check if table was created
    $check_query = "SHOW TABLES LIKE 'order_update_flags'";
    $check_result = mysqli_query($conn, $check_query);
    $table_exists = mysqli_num_rows($check_result) > 0;
    
    echo json_encode([
        'success' => true,
        'table_exists' => $table_exists,
        'results' => $results,
        'message' => $table_exists ? 'Table created successfully!' : 'Table creation may have failed'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
