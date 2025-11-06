<?php
session_start();
require_once "../../../backend/pages/admin-includes/database.php";
require_once "../../../includes/session-manager.php";

// Set content type for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly
ini_set('log_errors', 1); // Log errors instead

if (!SessionManager::isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in again']);
    exit();
}

$user_id = SessionManager::getUserId();

// Get user information
$user_query = "SELECT email FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);

if (!$user_stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user_email = $user['email'];

// Check if order_refunds table exists
$refunds_table_check = "SHOW TABLES LIKE 'order_refunds'";
$refunds_table_result = $conn->query($refunds_table_check);
$refunds_table_exists = $refunds_table_result && $refunds_table_result->num_rows > 0;

// Pagination settings - Must match profile.php
$orders_per_page = 3;
$bulk_orders_per_page = 4;

$type = $_GET['type'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

if ($type === 'orders') {
    // Handle regular orders pagination
    $offset = ($page - 1) * $orders_per_page;
    
    // Get total count
    if ($refunds_table_exists) {
        $count_sql = "SELECT COUNT(*) as total FROM orders o WHERE o.customer_email = ?";
        $count_stmt = mysqli_prepare($conn, $count_sql);
        if (!$count_stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error preparing count query: ' . mysqli_error($conn)]);
            exit();
        }
        mysqli_stmt_bind_param($count_stmt, "s", $user_email);
    } else {
        $count_sql = "SELECT COUNT(*) as total FROM orders WHERE customer_email = ?";
        $count_stmt = mysqli_prepare($conn, $count_sql);
        if (!$count_stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error preparing count query: ' . mysqli_error($conn)]);
            exit();
        }
        mysqli_stmt_bind_param($count_stmt, "s", $user_email);
    }
    
    if (!mysqli_stmt_execute($count_stmt)) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error executing count query: ' . mysqli_stmt_error($count_stmt)]);
        mysqli_stmt_close($count_stmt);
        exit();
    }
    
    $count_result = mysqli_stmt_get_result($count_stmt);
    if (!$count_result) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error getting count result: ' . mysqli_stmt_error($count_stmt)]);
        mysqli_stmt_close($count_stmt);
        exit();
    }
    
    $total_orders = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_orders / $orders_per_page);
    mysqli_stmt_close($count_stmt);
    
    // Fetch orders
    if ($refunds_table_exists) {
        $sql = "SELECT o.order_id, o.status, o.order_date, o.total_items, o.total_amount, o.delivery_method,
                       r.refund_id, r.refund_status, r.refund_amount, r.created_at as refund_created_at
                FROM orders o
                LEFT JOIN order_refunds r ON o.order_id = r.order_id AND r.user_id = ?
                WHERE o.customer_email = ? 
                ORDER BY o.order_date DESC
                LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error preparing orders query: ' . mysqli_error($conn)]);
            exit();
        }
        mysqli_stmt_bind_param($stmt, "isii", $user_id, $user_email, $orders_per_page, $offset);
    } else {
        $sql = "SELECT order_id, status, order_date, total_items, total_amount, delivery_method FROM orders WHERE customer_email = ? ORDER BY order_date DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error preparing orders query: ' . mysqli_error($conn)]);
            exit();
        }
        mysqli_stmt_bind_param($stmt, "sii", $user_email, $orders_per_page, $offset);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error executing orders query: ' . mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        exit();
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error getting orders result: ' . mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        exit();
    }
    
    // Generate table HTML
    $table_html = '
    <thead>
        <tr>
            <th>Order Date</th>
            <th>Order ID</th>
            <th>Total Items</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Refund Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>';
    
    if (mysqli_num_rows($result) > 0) {
        while ($order = mysqli_fetch_assoc($result)) {
            $status_lower = strtolower($order['status']);
            $is_delivered = ($status_lower === 'delivered' || $status_lower === 'picked-up' || $status_lower === 'picked up');
            $has_refund = isset($order['refund_id']) && !empty($order['refund_id']);
            
            $table_html .= '<tr>';
            $table_html .= '<td>' . htmlspecialchars(date("M j, Y", strtotime($order['order_date']))) . '</td>';
            $table_html .= '<td>#' . htmlspecialchars($order['order_id']) . '</td>';
            $table_html .= '<td>' . htmlspecialchars($order['total_items']) . ' items</td>';
            $table_html .= '<td>₱' . htmlspecialchars(number_format($order['total_amount'], 2)) . '</td>';
            $table_html .= '<td><span class="status-badge status-' . htmlspecialchars($status_lower) . '">' . htmlspecialchars(ucfirst($order['status'])) . '</span></td>';
            $table_html .= '<td>';
            
            if ($has_refund) {
                $table_html .= '<div class="refund-status-container">';
                $table_html .= '<span class="refund-status-badge refund-status-' . htmlspecialchars($order['refund_status']) . '">';
                $table_html .= htmlspecialchars(ucfirst($order['refund_status']));
                $table_html .= '</span>';
                $table_html .= '<small class="refund-date">';
                $table_html .= date("M j, Y", strtotime($order['refund_created_at']));
                $table_html .= '</small>';
                $table_html .= '</div>';
            } elseif ($is_delivered) {
                $table_html .= '<span class="refund-available">Available</span>';
            } else {
                $table_html .= '<span class="refund-na">N/A</span>';
            }
            
            $table_html .= '</td>';
            $table_html .= '<td class="actions-column">';
            $table_html .= '<a href="../cart/order-details.php?order_id=' . $order['order_id'] . '" class="btn-view">View Order</a>';
            
            if ($is_delivered) {
                if ($has_refund) {
                    $table_html .= '<button onclick="viewRefundDetails(' . $order['order_id'] . ')" class="btn-refund">View Refund</button>';
                } else {
                    $table_html .= '<button onclick="openRefundRequestModal(' . $order['order_id'] . ')" class="btn-refund">Request Refund</button>';
                }
            }
            
            $table_html .= '</td>';
            $table_html .= '</tr>';
        }
    } else {
        $table_html .= '<tr><td colspan="7">No orders found for your account.</td></tr>';
    }
    
    $table_html .= '</tbody>';
    
    // Generate pagination HTML
    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html = '<div class="pagination">';
        
        if ($page > 1) {
            $pagination_html .= '<button type="button" data-page="' . ($page - 1) . '" data-type="orders" class="pagination-btn pagination-link">&laquo; Previous</button>';
        }
        
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                $pagination_html .= '<span class="pagination-btn active">' . $i . '</span>';
            } else {
                $pagination_html .= '<button type="button" data-page="' . $i . '" data-type="orders" class="pagination-btn pagination-link">' . $i . '</button>';
            }
        }
        
        if ($page < $total_pages) {
            $pagination_html .= '<button type="button" data-page="' . ($page + 1) . '" data-type="orders" class="pagination-btn pagination-link">Next &raquo;</button>';
        }
        
        $pagination_html .= '</div>';
        $pagination_html .= '<div class="pagination-info">';
        $pagination_html .= 'Showing ' . min($offset + $orders_per_page, $total_orders) . ' of ' . $total_orders . ' orders';
        $pagination_html .= '</div>';
    }
    
    echo json_encode([
        'table_html' => $table_html,
        'pagination_html' => $pagination_html
    ]);
    
    mysqli_stmt_close($stmt);
    
} elseif ($type === 'bulk_orders') {
    // Handle bulk orders pagination
    $offset = ($page - 1) * $bulk_orders_per_page;
    
    // Get total count of bulk orders
    $bulk_count_sql = "SELECT COUNT(*) as total FROM bulk_orders WHERE user_id = ?";
    $bulk_count_stmt = mysqli_prepare($conn, $bulk_count_sql);
    
    if ($bulk_count_stmt === false) {
        echo json_encode([
            'table_html' => '<tbody><tr><td colspan="6">No bulk orders found for your account.</td></tr></tbody>',
            'pagination_html' => ''
        ]);
        exit();
    }
    
    mysqli_stmt_bind_param($bulk_count_stmt, "i", $user_id);
    mysqli_stmt_execute($bulk_count_stmt);
    $bulk_count_result = mysqli_stmt_get_result($bulk_count_stmt);
    $total_bulk_orders = mysqli_fetch_assoc($bulk_count_result)['total'];
    $total_pages = ceil($total_bulk_orders / $bulk_orders_per_page);
    mysqli_stmt_close($bulk_count_stmt);
    
    // Fetch bulk orders
    $bulk_orders_sql = "SELECT id, 
                               unique_order_id as display_order_id,
                               name, contact, email, billing_address, order_type, delivery_address, purpose, date_needed, time_needed, created_at, status, total_items, total_amount, proof_of_payment, admin_updated, note, admin_notes 
                        FROM bulk_orders 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC
                        LIMIT ? OFFSET ?";
    $bulk_orders_stmt = mysqli_prepare($conn, $bulk_orders_sql);
    mysqli_stmt_bind_param($bulk_orders_stmt, "iii", $user_id, $bulk_orders_per_page, $offset);
    mysqli_stmt_execute($bulk_orders_stmt);
    $bulk_orders_result = mysqli_stmt_get_result($bulk_orders_stmt);
    
    // Generate table HTML
    $table_html = '
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Date Submitted</th>
            <th>Total Items</th>
            <th>Total Amount</th>
            <th>Order Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>';
    
    if (mysqli_num_rows($bulk_orders_result) > 0) {
        while ($bulk_order = mysqli_fetch_assoc($bulk_orders_result)) {
            $table_html .= '<tr>';
            $table_html .= '<td>#' . htmlspecialchars($bulk_order['display_order_id']) . '</td>';
            $table_html .= '<td>' . htmlspecialchars(date("M j, Y", strtotime($bulk_order['created_at']))) . '</td>';
            $table_html .= '<td>' . htmlspecialchars($bulk_order['total_items']) . ' items</td>';
            $table_html .= '<td>₱' . htmlspecialchars(number_format($bulk_order['total_amount'], 2)) . '</td>';
            $table_html .= '<td><span class="status-badge status-' . htmlspecialchars(strtolower($bulk_order['status'])) . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $bulk_order['status']))) . '</span></td>';
            $table_html .= '<td class="actions-column">';
            $table_html .= '<a href="../bulk/bulk-order-details.php?id=' . $bulk_order['display_order_id'] . '" class="btn-view">View Details</a>';
            
            if ($bulk_order['status'] == 'approved' && empty($bulk_order['proof_of_payment'])) {
                $table_html .= '<a href="../bulk/bulk-order-details.php?id=' . $bulk_order['display_order_id'] . '#proof-upload" class="btn-upload">Attach Proof</a>';
            }
            
            $table_html .= '</td>';
            $table_html .= '</tr>';
        }
    } else {
        $table_html .= '<tr><td colspan="6">No bulk orders found for your account.</td></tr>';
    }
    
    $table_html .= '</tbody>';
    
    // Generate pagination HTML
    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html = '<div class="pagination">';
        
        if ($page > 1) {
            $pagination_html .= '<button type="button" data-page="' . ($page - 1) . '" data-type="bulk_orders" class="pagination-btn pagination-link">&laquo; Previous</button>';
        }
        
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                $pagination_html .= '<span class="pagination-btn active">' . $i . '</span>';
            } else {
                $pagination_html .= '<button type="button" data-page="' . $i . '" data-type="bulk_orders" class="pagination-btn pagination-link">' . $i . '</button>';
            }
        }
        
        if ($page < $total_pages) {
            $pagination_html .= '<button type="button" data-page="' . ($page + 1) . '" data-type="bulk_orders" class="pagination-btn pagination-link">Next &raquo;</button>';
        }
        
        $pagination_html .= '</div>';
        $pagination_html .= '<div class="pagination-info">';
        $pagination_html .= 'Showing ' . min($offset + $bulk_orders_per_page, $total_bulk_orders) . ' of ' . $total_bulk_orders . ' bulk orders';
        $pagination_html .= '</div>';
    }
    
    echo json_encode([
        'table_html' => $table_html,
        'pagination_html' => $pagination_html
    ]);
    
    mysqli_stmt_close($bulk_orders_stmt);
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request type']);
}

mysqli_close($conn);
?>