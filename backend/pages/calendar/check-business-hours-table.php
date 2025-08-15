<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . "/../admin-includes/config.php";
    require_once __DIR__ . "/../admin-includes/database.php";
    
    // Check if business_hours table exists
    $checkTableQuery = "SHOW TABLES LIKE 'business_hours'";
    $tableExists = $conn->query($checkTableQuery);
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to check table: ' . $conn->error
        ]);
        exit;
    }
    
    if ($tableExists->num_rows == 0) {
        echo json_encode([
            'success' => true,
            'table_exists' => false,
            'message' => 'business_hours table does not exist'
        ]);
        exit;
    }
    
    // Table exists, check its structure
    $structureQuery = "DESCRIBE business_hours";
    $structureResult = $conn->query($structureQuery);
    
    $columns = [];
    if ($structureResult) {
        while ($row = $structureResult->fetch_assoc()) {
            $columns[] = $row;
        }
    }
    
    // Check if there's any data
    $dataQuery = "SELECT * FROM business_hours ORDER BY id DESC LIMIT 5";
    $dataResult = $conn->query($dataQuery);
    
    $data = [];
    if ($dataResult) {
        while ($row = $dataResult->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'columns' => $columns,
        'data' => $data,
        'row_count' => $dataResult ? $dataResult->num_rows : 0
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
