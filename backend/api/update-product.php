<?php
/**
 * AJAX Update Endpoint for Products
 * 
 * This endpoint handles product updates via AJAX without page refresh.
 */

// Initialize SessionManager
require_once __DIR__ . '/../../includes/session-manager.php';
SessionManager::init();

header('Content-Type: application/json');

// Verify admin authentication
if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please log in as admin.'
    ]);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid CSRF token. Please refresh the page and try again.'
    ]);
    exit();
}

require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../pages/admin-includes/settings-helper.php';
require_once __DIR__ . '/../pages/admin-includes/activity-logger.php';

try {
    // Get product ID
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }
    
    // Get form data
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $is_featured = isset($_POST['is_featured']) ? intval($_POST['is_featured']) : 0;
    
    // Handle visibility options
    $visibility_option = $_POST['visibility_option'] ?? 'default';
    $show_when_unavailable = ($visibility_option === 'show') ? 1 : 0;
    $hide_when_unavailable = ($visibility_option === 'hide' || $visibility_option === 'default') ? 1 : 0;
    
    // Handle order types
    $preOrderChecked = isset($_POST['preOrderCheckbox']) && $_POST['preOrderCheckbox'] === 'true';
    $sameDayChecked = isset($_POST['sameDayCheckbox']) && $_POST['sameDayCheckbox'] === 'true';
    
    $status_id = null;
    $availtoday_status_id = null;
    
    if ($preOrderChecked && $sameDayChecked) {
        // Both pre-order and same-day
        $status_id = isset($_POST['status_id']) ? intval($_POST['status_id']) : 1;
        $availtoday_status_id = isset($_POST['availtoday_status_id']) ? intval($_POST['availtoday_status_id']) : null;
    } elseif ($preOrderChecked) {
        // Only pre-order
        $status_id = isset($_POST['status_id']) ? intval($_POST['status_id']) : 1;
    } elseif ($sameDayChecked) {
        // Only same-day
        $status_id = 4;
        $availtoday_status_id = isset($_POST['availtoday_status_id']) ? intval($_POST['availtoday_status_id']) : null;
        $quantity = 0; // Auto-set quantity to 0 for Same Day Order only
    } else {
        throw new Exception('Please select at least one order type');
    }
    
    // Update product
    $stmt = $conn->prepare("UPDATE products SET 
        name = ?, 
        description = ?, 
        price = ?, 
        status_id = ?, 
        quantity = ?, 
        is_featured = ?, 
        show_when_unavailable = ?, 
        hide_when_unavailable = ?, 
        availtoday_status_id = ?,
        category_id = ?
        WHERE id = ?");
    
    $stmt->bind_param("ssdiiiiiii", 
        $name, 
        $description, 
        $price, 
        $status_id, 
        $quantity, 
        $is_featured, 
        $show_when_unavailable, 
        $hide_when_unavailable, 
        $availtoday_status_id,
        $category_id,
        $product_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update product: ' . $stmt->error);
    }
    
    // Handle available days for pre-order products
    if ($status_id == 1 || $status_id == 2 || $status_id == 3) {
        // Delete existing days
        $conn->query("DELETE FROM product_day WHERE product_id = $product_id");
        
        // Get global available days
        $available_days = getSetting('global_available_days', []);
        
        if (!empty($available_days)) {
            $day_stmt = $conn->prepare("INSERT INTO product_day (product_id, day_of_week) VALUES (?, ?)");
            foreach ($available_days as $day) {
                $day_stmt->bind_param("is", $product_id, $day);
                $day_stmt->execute();
            }
            $day_stmt->close();
        }
    }
    
    // Handle dates for same-day order
    if ($sameDayChecked && !$preOrderChecked) {
        // Only same-day: update todays_products_dates
        $conn->query("DELETE FROM todays_products_dates WHERE product_id = $product_id");
        
        if (isset($_POST['todays_product_dates']) && !empty($_POST['todays_product_dates'])) {
            $dates = explode(',', $_POST['todays_product_dates']);
            $date_stmt = $conn->prepare("INSERT INTO todays_products_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
            
            foreach ($dates as $date) {
                $trimmed_date = trim($date);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed_date)) {
                    $date_stmt->bind_param("isi", $product_id, $trimmed_date, $availtoday_status_id);
                    $date_stmt->execute();
                }
            }
            $date_stmt->close();
        }
    }
    
    // Handle dates for products with both pre-order and same-day
    if ($preOrderChecked && $sameDayChecked) {
        // Both: update regular_products_today_dates
        $conn->query("DELETE FROM regular_products_today_dates WHERE product_id = $product_id");
        
        if (isset($_POST['available_today_dates']) && !empty($_POST['available_today_dates'])) {
            $dates = explode(',', $_POST['available_today_dates']);
            $date_stmt = $conn->prepare("INSERT INTO regular_products_today_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
            
            foreach ($dates as $date) {
                $trimmed_date = trim($date);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed_date)) {
                    $date_stmt->bind_param("isi", $product_id, $trimmed_date, $availtoday_status_id);
                    $date_stmt->execute();
                }
            }
            $date_stmt->close();
        }
    }
    
    // Log the activity
    logAdminActivity($conn, 'UPDATE', "Updated product: $name (ID: $product_id)", 'products', $product_id);
    
    // Fetch updated product data to return
    $result = $conn->query("SELECT 
        p.id, p.sku, p.name, p.description, p.price, p.status_id, ps.name AS status_name, 
        p.category_id, c.name AS category_name,
        p.is_featured, p.show_when_unavailable, p.hide_when_unavailable,
        p.quantity, p.availtoday_status_id, ats.name AS availtoday_status_name,
        qpd.quantity as sameday_stock_today,
        GROUP_CONCAT(DISTINCT pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days,
        GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ',') as todays_product_dates,
        GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ',') as regular_today_dates
    FROM products p
    LEFT JOIN product_statuses ps ON p.status_id = ps.id
    LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_day pd ON p.id = pd.product_id
    LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
    LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
    LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
    WHERE p.id = $product_id
    GROUP BY p.id");
    
    $updated_product = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'product' => $updated_product
    ]);
    
} catch (Exception $e) {
    error_log("Product update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
