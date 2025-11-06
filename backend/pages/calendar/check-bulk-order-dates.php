<?php
// This script checks for bulk orders and returns dates that have active bulk orders
// Frontend will handle UI updates (checkbox auto-check, etc.)

require_once "../admin-includes/database.php";

try {
    // Get all bulk orders with statuses that should block the date
    $query = "SELECT date_needed, status, id, unique_order_id
              FROM bulk_orders 
              WHERE status IN ('approved', 'payment_received', 'ready_for_delivery', 'completed')
              AND date_needed >= CURDATE()
              ORDER BY date_needed ASC";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $bulkOrderDates = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $date = $row['date_needed'];
            if (!isset($bulkOrderDates[$date])) {
                $bulkOrderDates[$date] = [];
            }
            $bulkOrderDates[$date][] = [
                'id' => $row['id'],
                'unique_order_id' => $row['unique_order_id'],
                'status' => $row['status']
            ];
        }
        
        // Return the bulk order dates
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'bulk_order_dates' => $bulkOrderDates,
            'dates' => array_keys($bulkOrderDates),
            'count' => count($bulkOrderDates)
        ]);
    } else {
        throw new Exception(mysqli_error($conn));
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
