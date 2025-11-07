<?php
/**
 * Backend Product List API Endpoint (Admin)
 * 
 * Returns product data for the admin-facing product list page
 * Used by polling system to update product stock and availability in real-time
 * 
 * Requires admin authentication
 */

header('Content-Type: application/json');

// Include database connection and session management
require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../../includes/session-manager.php';

try {
    // Check if user is logged in as admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized access',
            'timestamp' => date('Y-m-d H:i:s'),
            'products' => []
        ]);
        exit;
    }
    
    // Get request parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $status = isset($_GET['status']) ? (int)$_GET['status'] : null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $since = isset($_GET['since']) ? trim($_GET['since']) : null;
    
    // Pagination settings
    $items_per_page = 20;
    $offset = ($page - 1) * $items_per_page;
    
    // Build the SQL query
    $sql = "SELECT 
                p.id, 
                p.sku, 
                p.name, 
                p.description, 
                p.price, 
                p.status_id, 
                ps.name AS status_name, 
                p.unavailable_status_id, 
                ups.name AS unavailable_status_name,
                p.category_id, 
                c.name AS category_name,
                p.is_featured, 
                p.show_when_unavailable, 
                p.hide_when_unavailable,
                p.quantity, 
                p.availtoday_status_id, 
                ats.name AS availtoday_status_name,
                qpd.quantity as sameday_stock_today,
                p.updated_at,
                GROUP_CONCAT(DISTINCT pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days,
                GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ',') as todays_product_dates,
                GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ',') as regular_today_dates,
                CASE 
                    WHEN p.status_id = 4 AND EXISTS (
                        SELECT 1 FROM todays_products_dates tpd2 
                        WHERE tpd2.product_id = p.id AND tpd2.available_date >= CURDATE()
                    ) THEN 1
                    ELSE 0
                END as has_future_sdo_dates,
                CASE 
                    WHEN p.status_id = 4 THEN COALESCE(qpd.quantity, 0)
                    ELSE p.quantity
                END as effective_stock
            FROM products p
            LEFT JOIN product_statuses ps ON p.status_id = ps.id
            LEFT JOIN unavail_products_status ups ON p.unavailable_status_id = ups.id
            LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_day pd ON p.id = pd.product_id
            LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
            LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
            LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
            WHERE p.deleted_at IS NULL AND p.id > 0";
    
    // Add filters
    $params = [];
    $types = '';
    
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }
    
    if ($category) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
        $types .= 'i';
    }
    
    if ($status) {
        $sql .= " AND p.status_id = ?";
        $params[] = $status;
        $types .= 'i';
    }
    
    $sql .= " GROUP BY p.id
              ORDER BY 
                  CASE 
                      WHEN p.status_id = 4 AND has_future_sdo_dates = 1 AND effective_stock > 0 THEN 1
                      WHEN p.status_id IN (1, 2, 3) AND effective_stock > 0 THEN 2
                      WHEN p.status_id IN (1, 2, 3) AND effective_stock = 0 THEN 3
                      WHEN p.status_id = 4 THEN 4
                      ELSE 5
                  END ASC,
                  p.created_at DESC";
    
    // Count total products for pagination (before LIMIT)
    $count_sql = str_replace('SELECT ', 'SELECT COUNT(DISTINCT p.id) as total, ', $sql);
    $count_sql = preg_replace('/ORDER BY.*$/s', '', $count_sql);
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_products = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_products / $items_per_page);
    
    // Add pagination to main query
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Process products
    $products = [];
    
    while ($row = $result->fetch_assoc()) {
        $product = [
            'id' => (int)$row['id'],
            'sku' => $row['sku'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'sameday_stock_today' => $row['sameday_stock_today'] ? (int)$row['sameday_stock_today'] : 0,
            'effective_stock' => (int)$row['effective_stock'],
            'status_id' => (int)$row['status_id'],
            'status_name' => $row['status_name'],
            'unavailable_status_id' => $row['unavailable_status_id'] ? (int)$row['unavailable_status_id'] : null,
            'unavailable_status_name' => $row['unavailable_status_name'],
            'availtoday_status_id' => $row['availtoday_status_id'] ? (int)$row['availtoday_status_id'] : null,
            'availtoday_status_name' => $row['availtoday_status_name'],
            'category_id' => (int)$row['category_id'],
            'category_name' => $row['category_name'],
            'is_featured' => (bool)$row['is_featured'],
            'show_when_unavailable' => (bool)$row['show_when_unavailable'],
            'hide_when_unavailable' => (bool)$row['hide_when_unavailable'],
            'available_days' => $row['available_days'],
            'todays_product_dates' => $row['todays_product_dates'],
            'regular_today_dates' => $row['regular_today_dates'],
            'has_future_sdo_dates' => (bool)$row['has_future_sdo_dates'],
            'updated_at' => $row['updated_at']
        ];
        
        $products[] = $product;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'products' => $products,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Error in get-product-list-admin.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch product list',
        'timestamp' => date('Y-m-d H:i:s'),
        'products' => [],
        'total_pages' => 0,
        'current_page' => 1
    ]);
}
