<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
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
    
    $action = $_GET['action'] ?? 'all';
    
    $response = ['success' => true, 'data' => []];
    
    // Revenue Trend Chart
    if ($action === 'all' || $action === 'revenue_trend') {
        $revenue_sql = "SELECT DATE(order_date) as date, SUM(total_amount) as daily_revenue 
                       FROM orders 
                       WHERE status IN ('Delivered', 'Picked-up') 
                       AND DATE(order_date) BETWEEN ? AND ?
                       GROUP BY DATE(order_date) 
                       ORDER BY DATE(order_date)";
        
        $stmt = mysqli_prepare($conn, $revenue_sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $revenue_data = [];
        $labels = [];
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = date('M j', strtotime($row['date']));
            $data[] = floatval($row['daily_revenue']);
        }
        
        $response['data']['revenue_trend'] = [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    // Payment Methods Chart
    if ($action === 'all' || $action === 'payment_methods') {
        $payment_sql = "SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total_amount
                       FROM orders 
                       WHERE status IN ('Delivered', 'Picked-up') 
                       AND DATE(order_date) BETWEEN ? AND ?
                       GROUP BY payment_method";
        
        $stmt = mysqli_prepare($conn, $payment_sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $payment_labels = [];
        $payment_data = [];
        $payment_colors = ['#7B61FF', '#1DA1F2', '#FFB800', '#FF5C8D', '#FF8C32'];
        $color_index = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $payment_labels[] = $row['payment_method'];
            $payment_data[] = intval($row['count']);
            $color_index++;
        }
        
        $response['data']['payment_methods'] = [
            'labels' => $payment_labels,
            'data' => $payment_data,
            'colors' => array_slice($payment_colors, 0, count($payment_labels))
        ];
    }
    
    // Order Status Chart
    if ($action === 'all' || $action === 'order_status') {
        $status_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as total_amount
                      FROM orders 
                      WHERE status IN ('Delivered', 'Picked-up') 
                      AND DATE(order_date) BETWEEN ? AND ?
                      GROUP BY status";
        
        $stmt = mysqli_prepare($conn, $status_sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $status_labels = [];
        $status_data = [];
        $status_colors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'];
        $color_index = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $status_labels[] = $row['status'];
            $status_data[] = floatval($row['total_amount']);
            $color_index++;
        }
        
        $response['data']['order_status'] = [
            'labels' => $status_labels,
            'data' => $status_data,
            'colors' => array_slice($status_colors, 0, count($status_labels))
        ];
    }
    
    // Delivery Methods Chart
    if ($action === 'all' || $action === 'delivery_methods') {
        $delivery_sql = "SELECT delivery_method, COUNT(*) as count, SUM(total_amount) as total_amount
                        FROM orders 
                        WHERE status IN ('Delivered', 'Picked-up') 
                        AND DATE(order_date) BETWEEN ? AND ?
                        GROUP BY delivery_method";
        
        $stmt = mysqli_prepare($conn, $delivery_sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $delivery_labels = [];
        $delivery_data = [];
        $delivery_colors = ['#7B61FF', '#FFB800'];
        $color_index = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $delivery_labels[] = $row['delivery_method'];
            $delivery_data[] = intval($row['count']);
            $color_index++;
        }
        
        $response['data']['delivery_methods'] = [
            'labels' => $delivery_labels,
            'data' => $delivery_data,
            'colors' => array_slice($delivery_colors, 0, count($delivery_labels))
        ];
    }
    
    // Top Products Chart
    if ($action === 'all' || $action === 'top_products') {
        $top_products_sql = "SELECT 
                            oi.product_name,
                            SUM(oi.quantity) as total_quantity,
                            SUM(oi.price * oi.quantity) as total_revenue,
                            COUNT(DISTINCT oi.order_id) as order_count
                            FROM order_items oi
                            INNER JOIN orders o ON oi.order_id = o.order_id
                            WHERE o.status IN ('Delivered', 'Picked-up') 
                            AND DATE(o.order_date) BETWEEN ? AND ?
                            GROUP BY oi.product_name
                            ORDER BY total_quantity DESC";
        
        $stmt = mysqli_prepare($conn, $top_products_sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $product_labels = [];
        $product_quantity_data = [];
        $product_revenue_data = [];
        $product_colors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $product_labels[] = $row['product_name'];
            $product_quantity_data[] = intval($row['total_quantity']);
            $product_revenue_data[] = floatval($row['total_revenue']);
        }
        
        $response['data']['top_products'] = [
            'labels' => $product_labels,
            'quantity_data' => $product_quantity_data,
            'revenue_data' => $product_revenue_data,
            'colors' => array_slice($product_colors, 0, count($product_labels))
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>