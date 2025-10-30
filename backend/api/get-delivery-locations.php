<?php
@error_reporting(0);
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('log_errors', '1');

ob_start();

try {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $servername = "mysql-neoexclusivecafe.alwaysdata.net";
    $username = "429123";
    $password = "NeoCafe123";
    $dbname = "neoexclusivecafe_crud";
    
    $conn = @new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    @$conn->set_charset("utf8");
    
    $table_check = @$conn->query("SHOW TABLES LIKE 'delivery_locations'");
    if (!$table_check || $table_check->num_rows == 0) {
        throw new Exception('Table does not exist');
    }
    
    $sql = "SELECT delivery_id, municipality, city, postal_code, delivery_fee FROM delivery_locations ORDER BY city ASC, municipality ASC";
    $result = @$conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed');
    }
    
    $locations = [];
    $grouped_locations = [];
    
    while ($row = $result->fetch_assoc()) {
        $location = [
            'id' => (int)$row['delivery_id'],
            'municipality' => (string)$row['municipality'],
            'city' => (string)$row['city'],
            'postal_code' => (string)$row['postal_code'],
            'delivery_fee' => (float)$row['delivery_fee'],
            'display_text' => $row['municipality'] . ', ' . $row['city'] . ' ' . $row['postal_code'],
            'value' => $row['municipality'] . ', ' . $row['city'] . ' ' . $row['postal_code']
        ];
        
        $locations[] = $location;
        
        if (!isset($grouped_locations[$row['city']])) {
            $grouped_locations[$row['city']] = [];
        }
        $grouped_locations[$row['city']][] = $location;
    }
    
    $conn->close();
    
    $response = [
        'success' => true,
        'locations' => $locations,
        'grouped' => $grouped_locations
    ];
    
    ob_end_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;
