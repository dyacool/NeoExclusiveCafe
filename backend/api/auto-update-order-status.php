<?php
/**
 * Automatic Order Status Update Cron Job
 * 
 * This script automatically updates order statuses based on delivery/pickup dates
 * Should be run hourly via Windows Task Scheduler
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/auto-status-updates.log');

// Start output buffering for JSON response
ob_start();

require_once '../pages/admin-includes/database.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'updated_count' => 0,
    'orders_updated' => [],
    'errors' => [],
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Check if auto-status is enabled
    $check_sql = "SELECT auto_status_enabled FROM order_status_settings WHERE admin_id IS NULL LIMIT 1";
    $check_result = mysqli_query($conn, $check_sql);
    
    $auto_status_enabled = false;
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $row = mysqli_fetch_assoc($check_result);
        $auto_status_enabled = (bool)$row['auto_status_enabled'];
    }
    
    if (!$auto_status_enabled) {
        $response['message'] = 'Auto-status is disabled';
        echo json_encode($response);
        exit();
    }
    
    // Get current date
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    error_log("[AUTO-STATUS] Starting auto-update for date: $today");
    
    // Array to track updated orders
    $updated_orders = [];
    
    // 1. Handle same-day pickup orders (order placed today for today)
    $sql = "UPDATE orders 
            SET status = 'Ready for Pick-up' 
            WHERE delivery_method = 'Pick-up' 
            AND pickup_date = DATE(order_date)
            AND pickup_date = ?
            AND status = 'Confirmed'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $today);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            error_log("[AUTO-STATUS] Updated $affected same-day pickup orders to 'Ready for Pick-up'");
            $response['updated_count'] += $affected;
            
            // Get the order IDs that were updated
            $get_orders_sql = "SELECT order_id FROM orders 
                              WHERE delivery_method = 'Pick-up' 
                              AND pickup_date = ?
                              AND status = 'Ready for Pick-up'
                              AND DATE(order_date) = pickup_date
                              LIMIT $affected";
            $get_stmt = mysqli_prepare($conn, $get_orders_sql);
            mysqli_stmt_bind_param($get_stmt, "s", $today);
            mysqli_stmt_execute($get_stmt);
            $result = mysqli_stmt_get_result($get_stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $updated_orders[] = $row['order_id'];
            }
            mysqli_stmt_close($get_stmt);
        }
    }
    mysqli_stmt_close($stmt);
    
    // 2. Handle same-day delivery orders (order placed today for today)
    $sql = "UPDATE orders 
            SET status = 'Ready for Delivery' 
            WHERE delivery_method = 'Delivery' 
            AND delivery_date = DATE(order_date)
            AND delivery_date = ?
            AND status = 'Confirmed'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $today);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            error_log("[AUTO-STATUS] Updated $affected same-day delivery orders to 'Ready for Delivery'");
            $response['updated_count'] += $affected;
            
            // Get the order IDs that were updated
            $get_orders_sql = "SELECT order_id FROM orders 
                              WHERE delivery_method = 'Delivery' 
                              AND delivery_date = ?
                              AND status = 'Ready for Delivery'
                              AND DATE(order_date) = delivery_date
                              LIMIT $affected";
            $get_stmt = mysqli_prepare($conn, $get_orders_sql);
            mysqli_stmt_bind_param($get_stmt, "s", $today);
            mysqli_stmt_execute($get_stmt);
            $result = mysqli_stmt_get_result($get_stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $updated_orders[] = $row['order_id'];
            }
            mysqli_stmt_close($get_stmt);
        }
    }
    mysqli_stmt_close($stmt);
    
    // 3. Update pickup orders due tomorrow to "Preparing"
    $sql = "UPDATE orders 
            SET status = 'Preparing' 
            WHERE delivery_method = 'Pick-up' 
            AND pickup_date = ? 
            AND status = 'Confirmed'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tomorrow);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            error_log("[AUTO-STATUS] Updated $affected pickup orders due tomorrow to 'Preparing'");
            $response['updated_count'] += $affected;
            
            // Get the order IDs
            $get_orders_sql = "SELECT order_id FROM orders 
                              WHERE delivery_method = 'Pick-up' 
                              AND pickup_date = ?
                              AND status = 'Preparing'
                              LIMIT $affected";
            $get_stmt = mysqli_prepare($conn, $get_orders_sql);
            mysqli_stmt_bind_param($get_stmt, "s", $tomorrow);
            mysqli_stmt_execute($get_stmt);
            $result = mysqli_stmt_get_result($get_stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $updated_orders[] = $row['order_id'];
            }
            mysqli_stmt_close($get_stmt);
        }
    }
    mysqli_stmt_close($stmt);
    
    // 4. Update pickup orders due today to "Ready for Pick-up"
    $sql = "UPDATE orders 
            SET status = 'Ready for Pick-up' 
            WHERE delivery_method = 'Pick-up' 
            AND pickup_date = ? 
            AND status = 'Preparing'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $today);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            error_log("[AUTO-STATUS] Updated $affected pickup orders due today to 'Ready for Pick-up'");
            $response['updated_count'] += $affected;
            
            // Get the order IDs
            $get_orders_sql = "SELECT order_id FROM orders 
                              WHERE delivery_method = 'Pick-up' 
                              AND pickup_date = ?
                              AND status = 'Ready for Pick-up'
                              LIMIT $affected";
            $get_stmt = mysqli_prepare($conn, $get_orders_sql);
            mysqli_stmt_bind_param($get_stmt, "s", $today);
            mysqli_stmt_execute($get_stmt);
            $result = mysqli_stmt_get_result($get_stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $updated_orders[] = $row['order_id'];
            }
            mysqli_stmt_close($get_stmt);
        }
    }
    mysqli_stmt_close($stmt);
    
    // 5. Update delivery orders due tomorrow to "Preparing"
    $sql = "UPDATE orders 
            SET status = 'Preparing' 
            WHERE delivery_method = 'Delivery' 
            AND delivery_date = ? 
            AND status = 'Confirmed'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tomorrow);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            error_log("[AUTO-STATUS] Updated $affected delivery orders due tomorrow to 'Preparing'");
            $response['updated_count'] += $affected;
            
            // Get the order IDs
            $get_orders_sql = "SELECT order_id FROM orders 
                              WHERE delivery_method = 'Delivery' 
                              AND delivery_date = ?
                              AND status = 'Preparing'
                              LIMIT $affected";
            $get_stmt = mysqli_prepare($conn, $get_orders_sql);
            mysqli_stmt_bind_param($get_stmt, "s", $tomorrow);
            mysqli_stmt_execute($get_stmt);
            $result = mysqli_stmt_get_result($get_stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $updated_orders[] = $row['order_id'];
            }
            mysqli_stmt_close($get_stmt);
        }
    }
    mysqli_stmt_close($stmt);
    
    // 6. Update delivery orders due today to "Ready for Delivery"
    $sql = "UPDATE orders 
            SET status = 'Ready for Delivery' 
            WHERE delivery_method = 'Delivery' 
            AND delivery_date = ? 
            AND status = 'Preparing'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $today);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            error_log("[AUTO-STATUS] Updated $affected delivery orders due today to 'Ready for Delivery'");
            $response['updated_count'] += $affected;
            
            // Get the order IDs
            $get_orders_sql = "SELECT order_id FROM orders 
                              WHERE delivery_method = 'Delivery' 
                              AND delivery_date = ?
                              AND status = 'Ready for Delivery'
                              LIMIT $affected";
            $get_stmt = mysqli_prepare($conn, $get_orders_sql);
            mysqli_stmt_bind_param($get_stmt, "s", $today);
            mysqli_stmt_execute($get_stmt);
            $result = mysqli_stmt_get_result($get_stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $updated_orders[] = $row['order_id'];
            }
            mysqli_stmt_close($get_stmt);
        }
    }
    mysqli_stmt_close($stmt);
    
    // Set success response
    $response['success'] = true;
    $response['orders_updated'] = array_unique($updated_orders);
    $response['message'] = "Successfully updated {$response['updated_count']} orders";
    
    error_log("[AUTO-STATUS] Completed. Total orders updated: {$response['updated_count']}");
    
} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
    error_log("[AUTO-STATUS] Error: " . $e->getMessage());
}

// Close database connection
mysqli_close($conn);

// Clear output buffer and send JSON response
ob_end_clean();
echo json_encode($response, JSON_PRETTY_PRINT);
?>
