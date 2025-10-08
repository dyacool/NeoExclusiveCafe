<?php
// Helper function to get order counts
function getOrderCounts($conn) {
    // Get total orders count
    $total_query = "SELECT COUNT(*) as total FROM orders";
    $total_result = mysqli_query($conn, $total_query);
    $total_count = mysqli_fetch_assoc($total_result)['total'];
    
    // Get active orders count (excluding completed statuses)
    $active_query = "SELECT COUNT(*) as active FROM orders WHERE status NOT IN ('Delivered', 'Picked-up')";
    $active_result = mysqli_query($conn, $active_query);
    $active_count = mysqli_fetch_assoc($active_result)['active'];
    
    // Get pending orders count
    $pending_query = "SELECT COUNT(*) as pending FROM orders WHERE status = 'Pending'";
    $pending_result = mysqli_query($conn, $pending_query);
    $pending_count = mysqli_fetch_assoc($pending_result)['pending'];
    
    return [
        'total' => $total_count,
        'active' => $active_count,
        'pending' => $pending_count
    ];
}

// Helper function to get bulk order counts
function getBulkOrderCounts($conn) {
    // Check if bulk_orders table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'bulk_orders'");
    if (mysqli_num_rows($table_check) == 0) {
        return ['total' => 0, 'active' => 0];
    }
    
    // Get total bulk orders count
    $total_query = "SELECT COUNT(*) as total FROM bulk_orders";
    $total_result = mysqli_query($conn, $total_query);
    $total_count = mysqli_fetch_assoc($total_result)['total'];
    
    // Get active bulk orders count (excluding completed statuses)
    $active_query = "SELECT COUNT(*) as active FROM bulk_orders WHERE status NOT IN ('Completed', 'Delivered')";
    $active_result = mysqli_query($conn, $active_query);
    $active_count = mysqli_fetch_assoc($active_result)['active'];
    
    return [
        'total' => $total_count,
        'active' => $active_count
    ];
}
?>