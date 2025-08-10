<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Check if business_hours table exists
    $checkTableQuery = "SHOW TABLES LIKE 'business_hours'";
    $tableExists = $conn->query($checkTableQuery);
    
    if ($tableExists->num_rows == 0) {
        // Table doesn't exist, return default values
        echo json_encode([
            'success' => true, 
            'businessHours' => [
                'opening_time' => '08:00',
                'closing_time' => '17:00'
            ]
        ]);
        exit;
    }
    
    // Get business hours
    $query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $businessHours = $result->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'businessHours' => $businessHours
        ]);
    } else {
        // No records found, return default values
        echo json_encode([
            'success' => true, 
            'businessHours' => [
                'opening_time' => '08:00',
                'closing_time' => '17:00'
            ]
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
