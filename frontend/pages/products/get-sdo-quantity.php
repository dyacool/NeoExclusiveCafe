<?php
session_start();
header('Content-Type: application/json');

require_once '../../../backend/pages/admin-includes/database.php';

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit();
}

$product_id = intval($_GET['product_id']);
$today = date('Y-m-d');

try {
    // Get product status and availtoday_status_id
    $status_query = "SELECT status_id, availtoday_status_id, quantity FROM products WHERE id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("i", $product_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $product = $status_result->fetch_assoc();
    $status_stmt->close();
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit();
    }
    
    $quantity = 0;
    $source = '';
    
    // Scenario 1: Status 1/2/3 WITH availtoday_status_id (has both pre-order and same-day)
    // For same-day: Check regular_products_today_dates → Get from quantity_per_day_sdo
    if (($product['status_id'] == 1 || $product['status_id'] == 2 || $product['status_id'] == 3) 
        && $product['availtoday_status_id'] != null) {
        
        // Check if product has a date entry in regular_products_today_dates
        $date_check = "SELECT id FROM regular_products_today_dates 
                       WHERE product_id = ? AND available_date = ?";
        $date_stmt = $conn->prepare($date_check);
        $date_stmt->bind_param("is", $product_id, $today);
        $date_stmt->execute();
        $date_result = $date_stmt->get_result();
        
        if ($date_result->num_rows > 0) {
            // Product is available today, get quantity from quantity_per_day_sdo
            $query = "SELECT quantity FROM quantity_per_day_sdo 
                      WHERE product_id = ? AND date = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $product_id, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $quantity = $row['quantity'];
                $source = 'quantity_per_day_sdo (regular product with same-day)';
            }
            $stmt->close();
        }
        $date_stmt->close();
    }
    // Scenario 2: Status 4 ONLY (same day order only)
    // Check todays_products_dates → Get from quantity_per_day_sdo
    else if ($product['status_id'] == 4) {
        // Check if product has a date entry in todays_products_dates
        $date_check = "SELECT id FROM todays_products_dates 
                       WHERE product_id = ? AND available_date = ?";
        $date_stmt = $conn->prepare($date_check);
        $date_stmt->bind_param("is", $product_id, $today);
        $date_stmt->execute();
        $date_result = $date_stmt->get_result();
        
        if ($date_result->num_rows > 0) {
            // Product is available today, get quantity from quantity_per_day_sdo
            $query = "SELECT quantity FROM quantity_per_day_sdo 
                      WHERE product_id = ? AND date = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $product_id, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $quantity = $row['quantity'];
                $source = 'quantity_per_day_sdo (same day only)';
            }
            $stmt->close();
        }
        $date_stmt->close();
    }
    // Scenario 3: Status 1/2/3 WITHOUT availtoday_status_id (pre-order only)
    // Get from products table
    else {
        $quantity = $product['quantity'];
        $source = 'products table (pre-order only)';
    }
    
    // Check if there's a date for today
    $has_date_today = false;
    if (($product['status_id'] == 1 || $product['status_id'] == 2 || $product['status_id'] == 3) 
        && $product['availtoday_status_id'] != null) {
        // Check regular_products_today_dates
        $date_check = "SELECT id FROM regular_products_today_dates 
                       WHERE product_id = ? AND available_date = ?";
        $date_stmt = $conn->prepare($date_check);
        $date_stmt->bind_param("is", $product_id, $today);
        $date_stmt->execute();
        $date_result = $date_stmt->get_result();
        $has_date_today = $date_result->num_rows > 0;
        $date_stmt->close();
    } else if ($product['status_id'] == 4) {
        // Check todays_products_dates
        $date_check = "SELECT id FROM todays_products_dates 
                       WHERE product_id = ? AND available_date = ?";
        $date_stmt = $conn->prepare($date_check);
        $date_stmt->bind_param("is", $product_id, $today);
        $date_stmt->execute();
        $date_result = $date_stmt->get_result();
        $has_date_today = $date_result->num_rows > 0;
        $date_stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'quantity' => $quantity,
        'date' => $today,
        'status_id' => $product['status_id'],
        'availtoday_status_id' => $product['availtoday_status_id'],
        'source' => $source,
        'has_date_today' => $has_date_today
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
