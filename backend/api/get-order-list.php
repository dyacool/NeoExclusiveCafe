<?php
// Suppress database debug output for API responses
$suppress_db_debug = true;

require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../../includes/session-manager.php';

// Authentication check
if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

try {
    // ====================================
    // AUTO-STATUS UPDATE CHECK
    // ====================================
    // Check if auto-status is enabled and run status updates if needed
    $check_sql = "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL LIMIT 1";
    $check_result = mysqli_query($conn, $check_sql);
    
    $auto_status_enabled = false;
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $row = mysqli_fetch_assoc($check_result);
        $auto_status_enabled = (bool)$row['auto_status_enabled'];
    }
    
    // If auto-status is enabled, perform automatic status updates
    if ($auto_status_enabled) {
        // Get current date
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // 1. Update pickup orders due today to "Ready for Pick-up"
        $sql = "UPDATE orders 
                SET status = 'Ready for Pick-up' 
                WHERE delivery_method = 'Pick-up' 
                AND pickup_date = ? 
                AND status IN ('Preparing', 'Confirmed')";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $today);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 2. Update delivery orders due today to "Ready for Delivery"
        $sql = "UPDATE orders 
                SET status = 'Ready for Delivery' 
                WHERE delivery_method = 'Delivery' 
                AND delivery_date = ? 
                AND status IN ('Preparing', 'Confirmed')";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $today);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 3. Update pickup orders due tomorrow to "Preparing"
        $sql = "UPDATE orders 
                SET status = 'Preparing' 
                WHERE delivery_method = 'Pick-up' 
                AND pickup_date = ? 
                AND status = 'Confirmed'";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $tomorrow);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 4. Update delivery orders due tomorrow to "Preparing"
        $sql = "UPDATE orders 
                SET status = 'Preparing' 
                WHERE delivery_method = 'Delivery' 
                AND delivery_date = ? 
                AND status = 'Confirmed'";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $tomorrow);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    // ====================================
    // END AUTO-STATUS UPDATE
    // ====================================
    
    // Get filter parameters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $since_timestamp = isset($_GET['since']) ? $_GET['since'] : null;
    
    // Pagination settings
    $orders_per_page = 15;
    $offset = ($current_page - 1) * $orders_per_page;
    
    // Base query
    $sql = "SELECT * FROM orders";
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Add status filter if not 'all'
    if ($status_filter !== 'all') {
        // Map display status to database status
        $db_status = ($status_filter == 'Pending') ? 'Confirmed' : $status_filter;
        $where_clauses[] = "LOWER(TRIM(status)) = LOWER(?)";
        $params[] = $db_status;
        $types .= "s";
    }
    
    // Add search filter if provided
    if (!empty($search)) {
        $where_clauses[] = "(customer_name LIKE ? OR order_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ss";
    }
    
    // NOTE: We don't filter by since_timestamp in the WHERE clause
    // Instead, we use it only to mark which orders are "new" in the response
    // This ensures we always return all orders matching the current filters
    
    // Combine where clauses if any
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Add complex ordering:
    // 1. Completed/Delivered orders go last
    // 2. Active orders sorted by delivery/pickup date (nearest first)
    // 3. Completed orders sorted by order date (oldest first)
    $sql .= " ORDER BY 
        CASE 
            WHEN LOWER(TRIM(status)) IN ('delivered', 'picked-up', 'completed') THEN 2
            ELSE 1
        END ASC,
        CASE 
            WHEN LOWER(TRIM(status)) IN ('delivered', 'picked-up', 'completed') THEN order_date
            ELSE COALESCE(
                NULLIF(delivery_date, '0000-00-00'),
                NULLIF(pickup_date, '0000-00-00'),
                order_date
            )
        END ASC,
        CASE 
            WHEN LOWER(TRIM(status)) NOT IN ('delivered', 'picked-up', 'completed') THEN 
                COALESCE(
                    NULLIF(delivery_time, '00:00:00'),
                    NULLIF(pickup_time, '00:00:00')
                )
            ELSE NULL
        END ASC,
        order_date ASC
        LIMIT ? OFFSET ?";
    
    // Add pagination parameters
    $params[] = $orders_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    // Prepare and execute the statement
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    // Build orders array
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Determine if this is a new order (created after since timestamp)
        $is_new = false;
        if (!empty($since_timestamp)) {
            // Use string comparison for more reliable timestamp comparison
            $is_new = ($row['order_date'] > $since_timestamp);
        }
        
        // Map database status to display status
        $display_status = ($row['status'] == 'Confirmed') ? 'Pending' : $row['status'];
        
        $orders[] = [
            'order_id' => (int)$row['order_id'],
            'order_date' => $row['order_date'],
            'customer_name' => $row['customer_name'],
            'customer_contact' => $row['customer_contact'],
            'customer_address' => $row['customer_address'],
            'total_items' => (int)$row['total_items'],
            'total_amount' => (float)$row['total_amount'],
            'payment_method' => $row['payment_method'],
            'delivery_method' => $row['delivery_method'],
            'delivery_date' => $row['delivery_date'],
            'delivery_time' => $row['delivery_time'],
            'pickup_date' => $row['pickup_date'],
            'pickup_time' => $row['pickup_time'],
            'status' => $display_status,
            'db_status' => $row['status'], // Keep original for form submission
            'is_new' => $is_new
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    // Get total count for pagination (without since filter)
    $count_sql = "SELECT COUNT(*) as total FROM orders";
    $count_where_clauses = [];
    $count_params = [];
    $count_types = "";
    
    // Add same filters for count query (except since timestamp)
    if ($status_filter !== 'all') {
        $db_status = ($status_filter == 'Pending') ? 'Confirmed' : $status_filter;
        $count_where_clauses[] = "LOWER(TRIM(status)) = LOWER(?)";
        $count_params[] = $db_status;
        $count_types .= "s";
    }
    
    if (!empty($search)) {
        $count_where_clauses[] = "(customer_name LIKE ? OR order_id LIKE ?)";
        $count_params[] = "%$search%";
        $count_params[] = "%$search%";
        $count_types .= "ss";
    }
    
    if (!empty($count_where_clauses)) {
        $count_sql .= " WHERE " . implode(" AND ", $count_where_clauses);
    }
    
    $count_stmt = mysqli_prepare($conn, $count_sql);
    if (!empty($count_params)) {
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
    
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_orders = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_orders / $orders_per_page);
    mysqli_stmt_close($count_stmt);
    
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
        // Map database status to display status
        $display_status = ($count_row['status'] == 'Confirmed') ? 'Pending' : $count_row['status'];
        
        if (isset($status_counts[$display_status])) {
            $status_counts[$display_status] = (int)$count_row['count'];
            $status_counts['all'] += (int)$count_row['count'];
        }
    }
    
    // Check for new order flag (within last 10 seconds)
    $flag_check_sql = "SELECT id FROM order_update_flags 
                       WHERE flag_type = 'new_order' 
                       AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
                       LIMIT 1";
    $flag_result = mysqli_query($conn, $flag_check_sql);
    $has_new_order_flag = mysqli_num_rows($flag_result) > 0;
    
    // Return successful response
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'orders' => $orders,
        'status_counts' => $status_counts,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'has_new_order_flag' => $has_new_order_flag
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    error_log('Order list API error: ' . $e->getMessage());
}
?>
