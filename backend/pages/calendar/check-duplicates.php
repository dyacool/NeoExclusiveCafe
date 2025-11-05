<?php
// Check for duplicate dates in date_limits table
require_once "../admin-includes/database.php";
require_once "../../login/admin/admin-auth.php";

header('Content-Type: application/json');

try {
    // Check for duplicates
    $duplicateQuery = "
        SELECT date, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM date_limits 
        GROUP BY date 
        HAVING count > 1
    ";
    
    $result = $conn->query($duplicateQuery);
    $duplicates = [];
    
    while ($row = $result->fetch_assoc()) {
        $duplicates[] = [
            'date' => $row['date'],
            'count' => intval($row['count']),
            'ids' => $row['ids']
        ];
    }
    
    // Get all date limits
    $allQuery = "SELECT id, date, limit_value, not_accepting_orders FROM date_limits ORDER BY date, id";
    $result = $conn->query($allQuery);
    $allDates = [];
    
    while ($row = $result->fetch_assoc()) {
        $allDates[] = [
            'id' => intval($row['id']),
            'date' => $row['date'],
            'limit_value' => intval($row['limit_value']),
            'not_accepting_orders' => intval($row['not_accepting_orders'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'duplicates' => $duplicates,
        'all_dates' => $allDates,
        'total_records' => count($allDates)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
