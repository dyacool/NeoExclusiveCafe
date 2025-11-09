<?php
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../admin-includes/database.php";

if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get filter parameters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Base query
    $sql = "SELECT * FROM orders";
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Add status filter if not 'all'
    if ($status_filter !== 'all') {
        $where_clauses[] = "status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    // Add search filter if provided
    if (!empty($search)) {
        $where_clauses[] = "(customer_name LIKE ? OR order_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ss";
    }
    
    // Combine where clauses if any
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Add order by
    $sql .= " ORDER BY order_date DESC";
    
    // Prepare and execute the statement
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Count orders by status for filter badges
    $status_counts = [
        'all' => 0,
        'Pending' => 0,
        'Preparing' => 0,
        'Ready for Delivery' => 0,
        'Out for Delivery' => 0,
        'Ready for Pick-up' => 0,
        'Picked-up' => 0,
        'Delivered' => 0
    ];
    
    $count_sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $count_result = mysqli_query($conn, $count_sql);
    
    while ($count_row = mysqli_fetch_assoc($count_result)) {
        if (isset($status_counts[$count_row['status']])) {
            $status_counts[$count_row['status']] = $count_row['count'];
            $status_counts['all'] += $count_row['count'];
        }
    }
    
    // Fetch orders
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    
    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'orders' => $orders,
            'status_counts' => $status_counts,
            'filters' => [
                'status_filter' => $status_filter,
                'search' => $search
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