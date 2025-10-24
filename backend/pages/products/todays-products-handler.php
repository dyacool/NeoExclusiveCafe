<?php
/**
 * Today's Products Handler
 * Handles database operations for date-based Today's products
 */

require_once __DIR__ . "/../admin-includes/database.php";

/**
 * Save Today's product dates
 */
function saveTodaysProductDates($product_id, $dates, $availtoday_status_id = null) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Delete existing dates for this product
        $delete_stmt = $conn->prepare("DELETE FROM todays_products_dates WHERE product_id = ?");
        $delete_stmt->bind_param("i", $product_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Insert new dates
        if (!empty($dates)) {
            $insert_stmt = $conn->prepare("INSERT INTO todays_products_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
            
            foreach ($dates as $date) {
                $insert_stmt->bind_param("isi", $product_id, $date, $availtoday_status_id);
                $insert_stmt->execute();
            }
            $insert_stmt->close();
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving Today's product dates: " . $e->getMessage());
        return false;
    }
}

/**
 * Save regular product's additional today dates
 */
function saveRegularProductTodayDates($product_id, $dates, $availtoday_status_id = null) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Delete existing dates for this product
        $delete_stmt = $conn->prepare("DELETE FROM regular_products_today_dates WHERE product_id = ?");
        $delete_stmt->bind_param("i", $product_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Insert new dates
        if (!empty($dates)) {
            $insert_stmt = $conn->prepare("INSERT INTO regular_products_today_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
            
            foreach ($dates as $date) {
                $insert_stmt->bind_param("isi", $product_id, $date, $availtoday_status_id);
                $insert_stmt->execute();
            }
            $insert_stmt->close();
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving regular product today dates: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Today's product dates
 */
function getTodaysProductDates($product_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT available_date, availtoday_status_id FROM todays_products_dates WHERE product_id = ? ORDER BY available_date");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row;
    }
    
    $stmt->close();
    return $dates;
}

/**
 * Get regular product's additional today dates
 */
function getRegularProductTodayDates($product_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT available_date, availtoday_status_id FROM regular_products_today_dates WHERE product_id = ? ORDER BY available_date");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row;
    }
    
    $stmt->close();
    return $dates;
}

/**
 * Check if a Today's product is available on a specific date
 */
function isTodaysProductAvailableOnDate($product_id, $date) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM todays_products_dates WHERE product_id = ? AND available_date = ?");
    $stmt->bind_param("is", $product_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Check if a regular product is also available today on a specific date
 */
function isRegularProductAvailableTodayOnDate($product_id, $date) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM regular_products_today_dates WHERE product_id = ? AND available_date = ?");
    $stmt->bind_param("is", $product_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Get all Today's products available on a specific date
 */
function getTodaysProductsForDate($date, $availtoday_status_id = null) {
    global $conn;
    
    $sql = "SELECT p.*, tpd.available_date, tpd.availtoday_status_id, ats.name as availtoday_status_name,
                   pi.image_url
            FROM todays_products_dates tpd
            JOIN products p ON tpd.product_id = p.id
            LEFT JOIN availtoday_status ats ON tpd.availtoday_status_id = ats.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE tpd.available_date = ? AND p.deleted_at IS NULL";
    
    $params = [$date];
    $types = "s";
    
    if ($availtoday_status_id !== null) {
        $sql .= " AND tpd.availtoday_status_id = ?";
        $params[] = $availtoday_status_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY p.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

/**
 * Get regular products that are also available today on a specific date
 */
function getRegularProductsAvailableTodayForDate($date, $availtoday_status_id = null) {
    global $conn;
    
    $sql = "SELECT p.*, rptd.available_date, rptd.availtoday_status_id, ats.name as availtoday_status_name,
                   pi.image_url
            FROM regular_products_today_dates rptd
            JOIN products p ON rptd.product_id = p.id
            LEFT JOIN availtoday_status ats ON rptd.availtoday_status_id = ats.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE rptd.available_date = ? AND p.deleted_at IS NULL";
    
    $params = [$date];
    $types = "s";
    
    if ($availtoday_status_id !== null) {
        $sql .= " AND rptd.availtoday_status_id = ?";
        $params[] = $availtoday_status_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY p.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

/**
 * Clean up past dates (optional maintenance function)
 */
function cleanupPastDates() {
    global $conn;
    
    $today = date('Y-m-d');
    
    try {
        $conn->begin_transaction();
        
        // Clean up Today's products past dates
        $stmt1 = $conn->prepare("DELETE FROM todays_products_dates WHERE available_date < ?");
        $stmt1->bind_param("s", $today);
        $stmt1->execute();
        $deleted1 = $stmt1->affected_rows;
        $stmt1->close();
        
        // Clean up regular products today past dates
        $stmt2 = $conn->prepare("DELETE FROM regular_products_today_dates WHERE available_date < ?");
        $stmt2->bind_param("s", $today);
        $stmt2->execute();
        $deleted2 = $stmt2->affected_rows;
        $stmt2->close();
        
        // Clean up SDO quantity per day past dates
        $stmt3 = $conn->prepare("DELETE FROM quantity_per_day_sdo WHERE date < ?");
        $stmt3->bind_param("s", $today);
        $stmt3->execute();
        $deleted3 = $stmt3->affected_rows;
        $stmt3->close();
        
        $conn->commit();
        
        // Log cleanup results
        if ($deleted1 > 0 || $deleted2 > 0 || $deleted3 > 0) {
            error_log("Cleanup: Deleted $deleted1 from todays_products_dates, $deleted2 from regular_products_today_dates, $deleted3 from quantity_per_day_sdo");
        }
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error cleaning up past dates: " . $e->getMessage());
        return false;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'save_todays_product_dates':
            $product_id = intval($_POST['product_id']);
            $dates = isset($_POST['dates']) ? json_decode($_POST['dates'], true) : [];
            $availtoday_status_id = isset($_POST['availtoday_status_id']) ? intval($_POST['availtoday_status_id']) : null;
            
            if (saveTodaysProductDates($product_id, $dates, $availtoday_status_id)) {
                $response['success'] = true;
                $response['message'] = 'Today\'s product dates saved successfully';
            } else {
                $response['message'] = 'Failed to save Today\'s product dates';
            }
            break;
            
        case 'save_regular_product_today_dates':
            $product_id = intval($_POST['product_id']);
            $dates = isset($_POST['dates']) ? json_decode($_POST['dates'], true) : [];
            $availtoday_status_id = isset($_POST['availtoday_status_id']) ? intval($_POST['availtoday_status_id']) : null;
            
            if (saveRegularProductTodayDates($product_id, $dates, $availtoday_status_id)) {
                $response['success'] = true;
                $response['message'] = 'Regular product today dates saved successfully';
            } else {
                $response['message'] = 'Failed to save regular product today dates';
            }
            break;
            
        case 'get_todays_products_for_date':
            $date = $_POST['date'];
            $availtoday_status_id = isset($_POST['availtoday_status_id']) ? intval($_POST['availtoday_status_id']) : null;
            
            $products = getTodaysProductsForDate($date, $availtoday_status_id);
            $response['success'] = true;
            $response['data'] = $products;
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
    echo json_encode($response);
    exit;
}
?>
