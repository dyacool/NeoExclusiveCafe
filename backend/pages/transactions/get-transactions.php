<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Default date range (last 30 days)
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $selected_period = '30days';
    
    // Handle custom date range and period selection
    if (isset($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        $selected_period = 'custom';
    } else if (isset($_GET['period'])) {
        $selected_period = $_GET['period'];
        switch ($_GET['period']) {
            case '7days':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d', strtotime('-90 days'));
                break;
        }
    }
    
    // Get sort parameters
    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'order_date';
    $sort_direction = isset($_GET['direction']) ? $_GET['direction'] : 'desc';
    
    // Validate sort field to prevent SQL injection
    $allowed_sort_fields = ['order_id', 'order_date', 'customer_name', 'payment_method', 'total_amount', 'status'];
    if (!in_array($sort_field, $allowed_sort_fields)) {
        $sort_field = 'order_date';
    }
    
    // Validate sort direction
    if ($sort_direction != 'asc' && $sort_direction != 'desc') {
        $sort_direction = 'desc';
    }
    
    $sql = "SELECT o.order_id, o.order_date, o.customer_name, o.payment_method, o.total_amount, o.status, o.delivery_method as order_type,
            o.pickup_date, o.delivery_date, o.customer_contact, o.customer_address
            FROM orders o
            WHERE (o.status IN ('Delivered', 'Picked-up'))
            AND (DATE(o.order_date) BETWEEN ? AND ?)
            ORDER BY o.$sort_field $sort_direction";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Calculate totals
    $total_revenue = 0;
    $total_orders = 0;
    $transactions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_revenue += $row['total_amount'];
        $total_orders++;
        $transactions[] = $row;
    }
    
    // Calculate average order value
    $average_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
    
    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'transactions' => $transactions,
            'summary' => [
                'total_revenue' => $total_revenue,
                'total_orders' => $total_orders,
                'average_order_value' => $average_order_value
            ],
            'filters' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'selected_period' => $selected_period,
                'sort_field' => $sort_field,
                'sort_direction' => $sort_direction
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 