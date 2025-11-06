<?php
// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Include necessary files using relative paths
require_once "../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Clear any previous output
if (ob_get_length()) ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

// Function to handle errors
function handleError($message) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

try {
    // Get default limit
    $default_limit = 10; // Default fallback value
    $default_query = "SELECT default_limit FROM order_limits WHERE id = 1";
    $default_result = $conn->query($default_query);
    if ($default_result && $default_result->num_rows > 0) {
        $default_limit = $default_result->fetch_assoc()['default_limit'];
    }

    // Handle get_default parameter
    if (isset($_GET['get_default'])) {
        echo json_encode([
            'success' => true,
            'default_limit' => $default_limit
        ]);
        exit;
    }

    $dates = [];
    
    if (isset($_GET['start']) && isset($_GET['end'])) {
        $start_date = $conn->real_escape_string($_GET['start']);
        $end_date = $conn->real_escape_string($_GET['end']);
        
        // Simplified query to get date limits
        $query = "SELECT 
            dl.date,
            COALESCE(dl.limit_value, ?) as limit_value,
            COALESCE(dl.not_accepting_orders, 0) as not_accepting_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'Pending' THEN o.order_id END) as active_orders
        FROM (
            SELECT DATE_ADD(?, INTERVAL n DAY) as date
            FROM (
                SELECT @row := @row + 1 as n
                FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t1,
                     (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) t2,
                     (SELECT @row := -1) t3
            ) numbers
            WHERE DATE_ADD(?, INTERVAL n DAY) <= ?
        ) d
        LEFT JOIN date_limits dl ON d.date = dl.date
        LEFT JOIN orders o ON (d.date = o.pickup_date OR d.date = o.delivery_date)
        GROUP BY d.date, dl.limit_value, dl.not_accepting_orders
        ORDER BY d.date";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $default_limit, $start_date, $start_date, $end_date);
        
        if (!$stmt->execute()) {
            handleError("Failed to execute query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $limit = intval($row['limit_value']);
            $active_orders = intval($row['active_orders']);
            $notAccepting = intval($row['not_accepting_orders']);
            
            $dates[] = [
                'date' => $row['date'],
                'limit' => $limit,
                'active_orders' => $active_orders,
                'is_full' => $active_orders >= $limit,
                'remaining_slots' => max(0, $limit - $active_orders),
                'status' => $notAccepting === 1 ? 'not_accepting' : 'accepting'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'dates' => $dates,
            'default_limit' => $default_limit
        ]);
        
    } else if (isset($_GET['date'])) {
        $date = $conn->real_escape_string($_GET['date']);
        
        $query = "SELECT 
            COALESCE(dl.limit_value, ?) as limit_value,
            COALESCE(dl.not_accepting_orders, 0) as not_accepting_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'Pending' THEN o.order_id END) as active_orders
        FROM (SELECT ? as date) d
        LEFT JOIN date_limits dl ON d.date = dl.date
        LEFT JOIN orders o ON (d.date = o.pickup_date OR d.date = o.delivery_date)
        GROUP BY dl.limit_value, dl.not_accepting_orders";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $default_limit, $date);
        
        if (!$stmt->execute()) {
            handleError("Failed to execute query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $limit = intval($row['limit_value']);
        $active_orders = intval($row['active_orders']);
        $notAccepting = intval($row['not_accepting_orders']);
        
        echo json_encode([
            'success' => true,
            'dates' => [[
                'date' => $date,
                'limit' => $limit,
                'active_orders' => $active_orders,
                'is_full' => $active_orders >= $limit,
                'remaining_slots' => max(0, $limit - $active_orders),
                'status' => $notAccepting === 1 ? 'not_accepting' : 'accepting'
            ]],
            'default_limit' => $default_limit
        ]);
    } else {
        handleError("Missing required parameters");
    }
    
} catch (Exception $e) {
    handleError("Server error: " . $e->getMessage());
}
?> 