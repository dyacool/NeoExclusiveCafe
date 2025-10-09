<?php
function getOrderCounts($conn) {
    // Check if connection is valid and not closed
    if (!$conn || !($conn instanceof mysqli)) {
        return [
            'total' => 0,
            'active' => 0,
            'pending' => 0
        ];
    }
    
    // Check if the connection is closed by testing thread_id
    try {
        if ($conn->thread_id === null) {
            return [
                'total' => 0,
                'active' => 0,
                'pending' => 0
            ];
        }
    } catch (Exception $e) {
        return [
            'total' => 0,
            'active' => 0,
            'pending' => 0
        ];
    }
    
    // Additional safety check with mysqli_ping in try-catch
    try {
        if (!mysqli_ping($conn)) {
            return [
                'total' => 0,
                'active' => 0,
                'pending' => 0
            ];
        }
    } catch (Exception $e) {
        return [
            'total' => 0,
            'active' => 0,
            'pending' => 0
        ];
    }
    
    // Get total orders count
    $total_query = "SELECT COUNT(*) as total FROM orders";
    $total_result = mysqli_query($conn, $total_query);
    if (!$total_result) {
        return [
            'total' => 0,
            'active' => 0,
            'pending' => 0
        ];
    }
    $total_count = mysqli_fetch_assoc($total_result)['total'];
    
    // Get active orders count (excluding completed statuses)
    $active_query = "SELECT COUNT(*) as active FROM orders WHERE status NOT IN ('Delivered', 'Picked-up')";
    $active_result = mysqli_query($conn, $active_query);
    if (!$active_result) {
        return [
            'total' => $total_count,
            'active' => 0,
            'pending' => 0
        ];
    }
    $active_count = mysqli_fetch_assoc($active_result)['active'];
    
    // Get pending orders count
    $pending_query = "SELECT COUNT(*) as pending FROM orders WHERE status = 'Pending'";
    $pending_result = mysqli_query($conn, $pending_query);
    if (!$pending_result) {
        return [
            'total' => $total_count,
            'active' => $active_count,
            'pending' => 0
        ];
    }
    $pending_count = mysqli_fetch_assoc($pending_result)['pending'];
    
    return [
        'total' => $total_count,
        'active' => $active_count,
        'pending' => $pending_count
    ];
}

// Helper function to get bulk order counts
function getBulkOrderCounts($conn) {
    // Check if connection is valid and not closed
    if (!$conn || !($conn instanceof mysqli)) {
        return ['total' => 0, 'active' => 0];
    }
    
    // Check if the connection is closed by testing thread_id
    try {
        if ($conn->thread_id === null) {
            return ['total' => 0, 'active' => 0];
        }
    } catch (Exception $e) {
        return ['total' => 0, 'active' => 0];
    }
    
    // Additional safety check with mysqli_ping in try-catch
    try {
        if (!mysqli_ping($conn)) {
            return ['total' => 0, 'active' => 0];
        }
    } catch (Exception $e) {
        return ['total' => 0, 'active' => 0];
    }
    
    // Check if bulk_orders table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'bulk_orders'");
    if (!$table_check || mysqli_num_rows($table_check) == 0) {
        return ['total' => 0, 'active' => 0];
    }
    
    // Get total bulk orders count
    $total_query = "SELECT COUNT(*) as total FROM bulk_orders";
    $total_result = mysqli_query($conn, $total_query);
    if (!$total_result) {
        return ['total' => 0, 'active' => 0];
    }
    $total_count = mysqli_fetch_assoc($total_result)['total'];
    
    // Get active bulk orders count (excluding completed statuses)
    $active_query = "SELECT COUNT(*) as active FROM bulk_orders WHERE status NOT IN ('Completed', 'Delivered')";
    $active_result = mysqli_query($conn, $active_query);
    if (!$active_result) {
        return ['total' => $total_count, 'active' => 0];
    }
    $active_count = mysqli_fetch_assoc($active_result)['active'];
    
    return [
        'total' => $total_count,
        'active' => $active_count
    ];
}
?>