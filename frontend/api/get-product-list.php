<?php
/**
 * Frontend Product List API Endpoint
 * 
 * Returns product data for the customer-facing product dashboard
 * Used by polling system to update product stock and availability in real-time
 * 
 * No authentication required (public endpoint)
 */

header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../../backend/pages/admin-includes/database.php';

try {
    // Get request parameters
    $category = isset($_GET['category']) ? trim($_GET['category']) : null;
    $since = isset($_GET['since']) ? trim($_GET['since']) : null;
    
    // Get today's date for availability calculations
    $today_date = date('Y-m-d');
    
    // Build the SQL query
    $sql = "SELECT 
                p.id, 
                p.name, 
                p.price, 
                p.description,
                p.status_id, 
                p.is_featured, 
                p.category_id,
                ps.name AS status_name, 
                COALESCE(pi.cloud_url, pi.image_url) as image_url,
                p.quantity, 
                p.show_when_unavailable, 
                p.hide_when_unavailable,
                p.availtoday_status_id, 
                ats.name AS availtoday_status_name,
                c.name AS category_name,
                c.slug AS category_slug,
                GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
                GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates,
                qpd.quantity as sameday_stock_today
            FROM products p
            LEFT JOIN product_statuses ps ON p.status_id = ps.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
            LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
            LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
            WHERE p.deleted_at IS NULL 
            AND p.id > 0 
            AND p.status_id IN (1, 2, 3, 4)";
    
    // Add category filter if provided
    $params = [];
    $types = '';
    
    if ($category) {
        // Get category ID from slug
        $cat_query = "SELECT id FROM categories WHERE slug = ? AND is_active = 1";
        $cat_stmt = mysqli_prepare($conn, $cat_query);
        mysqli_stmt_bind_param($cat_stmt, "s", $category);
        mysqli_stmt_execute($cat_stmt);
        $cat_result = mysqli_stmt_get_result($cat_stmt);
        
        if ($cat_row = mysqli_fetch_assoc($cat_result)) {
            $sql .= " AND p.category_id = ?";
            $params[] = $cat_row['id'];
            $types .= 'i';
        }
    }
    
    $sql .= " GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id, 
              ps.name, pi.cloud_url, pi.image_url, p.quantity, p.show_when_unavailable, p.hide_when_unavailable, 
              p.availtoday_status_id, ats.name, c.name, c.slug, qpd.quantity
              ORDER BY p.is_featured DESC, p.name ASC";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Process products and calculate availability
    $products = [];
    
    while ($row = $result->fetch_assoc()) {
        // Calculate availability flags
        $availability = calculateProductAvailability($row, $today_date);
        
        // Build product data
        $product = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'sameday_stock_today' => $row['sameday_stock_today'] ? (int)$row['sameday_stock_today'] : 0,
            'status_id' => (int)$row['status_id'],
            'status_name' => $row['status_name'],
            'availtoday_status_id' => $row['availtoday_status_id'] ? (int)$row['availtoday_status_id'] : null,
            'is_available' => !$availability['is_unavailable'],
            'has_preorder' => $availability['has_preorder'],
            'has_sameday' => $availability['has_sameday'],
            'unavailable_reason' => $availability['unavailable_reason'],
            'image_url' => $row['image_url'],
            'category_id' => (int)$row['category_id'],
            'category_name' => $row['category_name'],
            'category_slug' => $row['category_slug'],
            'is_featured' => (bool)$row['is_featured']
        ];
        
        $products[] = $product;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'products' => $products
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Error in get-product-list.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch product list',
        'timestamp' => date('Y-m-d H:i:s'),
        'products' => []
    ]);
}

/**
 * Calculate product availability based on stock levels and date availability
 * 
 * @param array $product_row Product data from database
 * @param string $today_date Current date in Y-m-d format
 * @return array Availability information
 */
function calculateProductAvailability($product_row, $today_date) {
    $result = [
        'has_preorder' => false,
        'has_sameday' => false,
        'is_unavailable' => false,
        'unavailable_reason' => ''
    ];
    
    $status_id = $product_row['status_id'];
    $preorder_stock = $product_row['quantity'] ?? 0;
    $sameday_stock = $product_row['sameday_stock_today'] ?? 0;
    $has_availtoday_config = !empty($product_row['availtoday_status_id']);
    $todays_dates = $product_row['todays_product_dates'] ? explode(', ', $product_row['todays_product_dates']) : [];
    $regular_dates = $product_row['regular_today_dates'] ? explode(', ', $product_row['regular_today_dates']) : [];
    
    // Pre-order capability check (status 1, 2, 3)
    $result['has_preorder'] = in_array($status_id, [1, 2, 3]) && $preorder_stock > 0;
    
    // Same-day capability check for status 4 (Same Day Only)
    if ($status_id == 4) {
        $has_valid_date = in_array($today_date, $todays_dates);
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    }
    
    // Same-day capability check for status 1/2/3 with availtoday config
    if (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
        $has_valid_date = in_array($today_date, $regular_dates);
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    }
    
    // Determine unavailability
    $result['is_unavailable'] = !$result['has_preorder'] && !$result['has_sameday'];
    
    if ($result['is_unavailable']) {
        if ($status_id == 4) {
            $result['unavailable_reason'] = ($sameday_stock <= 0) ? 'Out of Stock' : 'Not Available Today';
        } else {
            $result['unavailable_reason'] = 'Out of Stock';
        }
    }
    
    return $result;
}
