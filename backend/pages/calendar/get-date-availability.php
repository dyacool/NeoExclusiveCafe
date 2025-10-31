<?php
/**
 * Get Date Availability API
 * Returns date limits and current order counts for checkout calendars
 */

header('Content-Type: application/json');

require_once __DIR__ . "/../admin-includes/database.php";

try {
    // Get date range from request (default to current month + 3 months ahead)
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t', strtotime('+3 months'));
    
    // Get fulfillment method from request (default to 'delivery' for backward compatibility)
    $fulfillment_method = $_GET['fulfillment_method'] ?? 'delivery';
    
    // Validate fulfillment method
    if (!in_array($fulfillment_method, ['delivery', 'pickup'])) {
        throw new Exception('Invalid fulfillment method. Must be "delivery" or "pickup".');
    }
    
    // Get default limit (only relevant for delivery orders)
    $default_limit = 10; // fallback
    if ($fulfillment_method === 'delivery') {
        $default_query = "SELECT default_limit FROM order_limits WHERE id = 1";
        $default_result = $conn->query($default_query);
        if ($default_result && $row = $default_result->fetch_assoc()) {
            $default_limit = intval($row['default_limit']);
        }
    }
    
    // Build query based on fulfillment method
    if ($fulfillment_method === 'delivery') {
        // For delivery: check both order_limits and date_limits, count only delivery orders
        $query = "SELECT 
            dates.date,
            COALESCE(dl.limit_value, ?) as limit_value,
            COALESCE(dl.not_accepting_orders, 0) as not_accepting_orders,
            COALESCE(os.status, 'accepting') as status,
            COUNT(DISTINCT o.order_id) as current_orders,
            CASE 
                WHEN os.status = 'not_accepting' OR dl.not_accepting_orders = 1 THEN 'disabled'
                WHEN COUNT(DISTINCT o.order_id) >= COALESCE(dl.limit_value, ?) THEN 'full'
                ELSE 'available'
            END as availability_status
        FROM (
            SELECT DATE_ADD(?, INTERVAL seq DAY) as date
            FROM (
                SELECT a.N + b.N * 10 + c.N * 100 as seq
                FROM 
                    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) c
            ) numbers
            WHERE DATE_ADD(?, INTERVAL seq DAY) <= ?
        ) dates
        LEFT JOIN date_limits dl ON dates.date = dl.date
        LEFT JOIN orderdate_status os ON dates.date = os.date
        LEFT JOIN orders o ON dates.date = o.delivery_date
            AND o.delivery_method = 'Delivery'
            AND o.status NOT IN ('Completed', 'Delivered', 'Picked-up', 'Cancelled')
        WHERE dates.date >= CURDATE()
        GROUP BY dates.date, dl.limit_value, dl.not_accepting_orders, os.status
        ORDER BY dates.date";
    } else {
        // For pickup: check only date_limits, ignore order_limits
        $query = "SELECT 
            dates.date,
            NULL as limit_value,
            COALESCE(dl.not_accepting_orders, 0) as not_accepting_orders,
            COALESCE(os.status, 'accepting') as status,
            0 as current_orders,
            CASE 
                WHEN os.status = 'not_accepting' OR dl.not_accepting_orders = 1 THEN 'disabled'
                ELSE 'available'
            END as availability_status
        FROM (
            SELECT DATE_ADD(?, INTERVAL seq DAY) as date
            FROM (
                SELECT a.N + b.N * 10 + c.N * 100 as seq
                FROM 
                    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) c
            ) numbers
            WHERE DATE_ADD(?, INTERVAL seq DAY) <= ?
        ) dates
        LEFT JOIN date_limits dl ON dates.date = dl.date
        LEFT JOIN orderdate_status os ON dates.date = os.date
        WHERE dates.date >= CURDATE()
        GROUP BY dates.date, dl.not_accepting_orders, os.status
        ORDER BY dates.date";
    }
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    // Bind parameters based on fulfillment method
    if ($fulfillment_method === 'delivery') {
        $stmt->bind_param("iisss", $default_limit, $default_limit, $start_date, $start_date, $end_date);
    } else {
        // For pickup, no limit parameters needed
        $stmt->bind_param("sss", $start_date, $start_date, $end_date);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $dates = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($fulfillment_method === 'delivery') {
            // For delivery: include order limit information
            $limit = intval($row['limit_value']);
            $current = intval($row['current_orders']);
            $remaining = max(0, $limit - $current);
            
            $dates[$row['date']] = [
                'date' => $row['date'],
                'limit' => $limit,
                'current_orders' => $current,
                'remaining_slots' => $remaining,
                'status' => $row['availability_status'],
                'is_available' => $row['availability_status'] === 'available',
                'message' => $row['availability_status'] === 'disabled' 
                    ? 'Not accepting orders' 
                    : ($row['availability_status'] === 'full' 
                        ? 'Fully booked' 
                        : "$remaining slots remaining")
            ];
        } else {
            // For pickup: exclude order limit information
            $dates[$row['date']] = [
                'date' => $row['date'],
                'limit' => null,
                'current_orders' => null,
                'remaining_slots' => null,
                'status' => $row['availability_status'],
                'is_available' => $row['availability_status'] === 'available',
                'message' => $row['availability_status'] === 'disabled' 
                    ? 'Not accepting orders' 
                    : 'Available'
            ];
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'fulfillment_method' => $fulfillment_method,
        'default_limit' => $fulfillment_method === 'delivery' ? $default_limit : null,
        'dates' => $dates
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
