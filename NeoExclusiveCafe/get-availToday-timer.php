<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../backend/pages/admin-includes/database.php";

header('Content-Type: application/json');

try {
    // Check if table exists
    $checkTableQuery = "SHOW TABLES LIKE 'availToday_timer'";
    $tableExists = $conn->query($checkTableQuery);
    
    if ($tableExists->num_rows == 0) {
        echo json_encode([
            'success' => false, 
            'error' => 'availToday_timer table does not exist',
            'message' => 'Please run the SQL script to create the table first'
        ]);
        exit;
    }
    
    // Get the active timer value
    $query = "SELECT timer_value, description, updated_at FROM availToday_timer WHERE is_active = TRUE ORDER BY updated_at DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $timerData = $result->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'timer' => $timerData
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'No active timer found',
            'message' => 'Please check if there are any active timers in the database'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
