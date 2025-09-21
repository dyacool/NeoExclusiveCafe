<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . "/../backend/pages/admin-includes/database.php";
    
    // Check if cart_availtoday table exists
    $checkTableQuery = "SHOW TABLES LIKE 'cart_availtoday'";
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
            'message' => 'cart_availtoday table does not exist'
        ]);
        exit;
    }
    
    // Table exists, check its structure
    $structureQuery = "DESCRIBE cart_availtoday";
    $structureResult = $conn->query($structureQuery);
    
    $columns = [];
    if ($structureResult) {
        while ($row = $structureResult->fetch_assoc()) {
            $columns[] = $row;
        }
    }
    
    // Check if there's any data
    $dataQuery = "SELECT COUNT(*) as row_count FROM cart_availtoday";
    $dataResult = $conn->query($dataQuery);
    
    $rowCount = 0;
    if ($dataResult) {
        $row = $dataResult->fetch_assoc();
        $rowCount = $row['row_count'];
    }
    
    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'columns' => $columns,
        'row_count' => $rowCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
