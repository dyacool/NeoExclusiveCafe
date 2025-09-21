<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";

header('Content-Type: application/json');

try {
    $checkTableQuery = "SHOW TABLES LIKE 'business_hours'";
    $tableExists = $conn->query($checkTableQuery);
    
    if ($tableExists->num_rows == 0) {
        echo json_encode([
            'success' => true, 
            'businessHours' => [
                'opening_time' => '08:00',
                'closing_time' => '17:00'
            ]
        ]);
        exit;
    }
    
    $query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $businessHours = $result->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'businessHours' => $businessHours
        ]);
    } else {
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
