<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check admin authentication
session_start();

// Debug: Log that the script was called
error_log("=== UPDATE-PRODUCT.PHP CALLED ===");

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['id']) || !isset($input['name']) || !isset($input['price']) || 
        !isset($input['status_id']) || !isset($input['quantity'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize and validate input
    $id = filter_var($input['id'], FILTER_VALIDATE_INT);
    $name = trim($input['name']);
    $description = isset($input['description']) ? trim($input['description']) : '';
    $price = filter_var($input['price'], FILTER_VALIDATE_FLOAT);
    $status_id = filter_var($input['status_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($input['quantity'], FILTER_VALIDATE_INT);
    
    // Handle available days - process for both Delivery and Pick Up
    $available_days = [];
    if (isset($input['available_days']) && is_array($input['available_days'])) {
        $available_days = $input['available_days'];
    }
    
    $is_featured = isset($input['is_featured']) && $input['is_featured'] ? 1 : 0;
    $show_when_unavailable = isset($input['show_when_unavailable']) && $input['show_when_unavailable'] ? 1 : 0;
    $hide_when_unavailable = isset($input['hide_when_unavailable']) && $input['hide_when_unavailable'] ? 1 : 0;
    
    // Handle unavailable status
    $unavailable_status_id = null;
    if (isset($input['unavailable_status_id']) && !empty($input['unavailable_status_id'])) {
        $unavailable_status_id = filter_var($input['unavailable_status_id'], FILTER_VALIDATE_INT);
    }
    
    // Handle Available Today status
    $availtoday_status_id = null;
    if (isset($input['availtoday_status_id']) && $input['availtoday_status_id'] !== "null" && $input['availtoday_status_id'] !== "") {
        $availtoday_status_id = filter_var($input['availtoday_status_id'], FILTER_VALIDATE_INT);
    }
    $log_file = __DIR__ . "/../../../logs/php_errors.log";
    error_log("[" . date('Y-m-d H:i:s') . "] Availtoday Status ID received: " . ($input['availtoday_status_id'] ?? 'NOT SET') . "\n", 3, $log_file);
    error_log("[" . date('Y-m-d H:i:s') . "] Availtoday Status ID after filter: " . ($availtoday_status_id ?? 'NULL') . "\n", 3, $log_file);
    error_log("[" . date('Y-m-d H:i:s') . "] Status ID: " . $status_id . "\n", 3, $log_file);
    error_log("[" . date('Y-m-d H:i:s') . "] Is Available Today: " . ($input['is_available_today'] ?? 'NOT SET') . "\n", 3, $log_file);
    error_log("[" . date('Y-m-d H:i:s') . "] Todays Product Dates: " . ($input['todays_product_dates'] ?? 'NOT SET') . "\n", 3, $log_file);
    error_log("[" . date('Y-m-d H:i:s') . "] Available Today Dates: " . ($input['available_today_dates'] ?? 'NOT SET') . "\n", 3, $log_file);
    
    // Auto-set quantity to 0 if product is being set to unavailable
    if ($unavailable_status_id !== null) {
        $quantity = 0;
    }

    if ($id === false || empty($name) || $price === false || $status_id === false || $quantity === false) {
        throw new Exception('Invalid input data');
    }

    // Update product with unavailable status
    $stmt = $conn->prepare("UPDATE products SET 
        name = ?, 
        description = ?,
        price = ?, 
        status_id = ?, 
        quantity = ?,
        is_featured = ?,
        show_when_unavailable = ?,
        hide_when_unavailable = ?,
        unavailable_status_id = ?,
        availtoday_status_id = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
    
    $stmt->bind_param("ssdiiiiiiii", 
        $name, 
        $description,
        $price, 
        $status_id, 
        $quantity,
        $is_featured,
        $show_when_unavailable,
        $hide_when_unavailable,
        $unavailable_status_id,
        $availtoday_status_id,
        $id
    );

    if ($stmt->execute()) {
        // Auto-set unavailable status if quantity is 0 and no unavailable status is set
        if ($quantity <= 0 && $unavailable_status_id === null) {
            // Determine the appropriate unavailable status based on current status
            $auto_unavailable_status_id = null;
            
            if ($status_id == 1) {
                // Currently Pick Up - set to Unavailable Pick Up (ID 1)
                $auto_unavailable_status_id = 1;
            } else if ($status_id == 2) {
                // Currently Delivery - set to Unavailable Delivery (ID 2)
                $auto_unavailable_status_id = 2;
            } else {
                // For any other status, default to Unavailable Today (ID 3)
                $auto_unavailable_status_id = 3;
            }
            
            // Update the unavailable status
            $auto_status_sql = "UPDATE products SET unavailable_status_id = ? WHERE id = ?";
            $auto_status_stmt = $conn->prepare($auto_status_sql);
            $auto_status_stmt->bind_param("ii", $auto_unavailable_status_id, $id);
            $auto_status_stmt->execute();
            $auto_status_stmt->close();
        }
        
        // Update available days in product_day table for Delivery and Pick Up only
        if ($status_id == 1 || $status_id == 2) {
            // First, delete existing days for this product
            $delete_stmt = $conn->prepare("DELETE FROM product_day WHERE product_id = ?");
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Then insert new days
            if (!empty($available_days)) {
                $day_stmt = $conn->prepare("INSERT INTO product_day (product_id, day_of_week) VALUES (?, ?)");
                foreach ($available_days as $day) {
                    $day_stmt->bind_param("is", $id, $day);
                    $day_stmt->execute();
                }
                $day_stmt->close();
            }
        } else {
            // If status is not Delivery or Pick Up, remove all available days
            $delete_stmt = $conn->prepare("DELETE FROM product_day WHERE product_id = ?");
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
        
        // Handle Today's product dates (status_id == 3)
        error_log("=== UPDATE PRODUCT DATES DEBUG ===");
        error_log("Product ID: $id, Status ID: $status_id");
        error_log("Raw todays_product_dates INPUT: " . ($input['todays_product_dates'] ?? 'NOT SET'));
        
        if ($status_id == 3) {
            error_log("Processing Today's product dates...");
            
            // Delete existing Today's product dates
            $delete_today_stmt = $conn->prepare("DELETE FROM todays_products_dates WHERE product_id = ?");
            $delete_today_stmt->bind_param("i", $id);
            $delete_today_stmt->execute();
            $delete_today_stmt->close();
            error_log("Deleted existing Today's product dates");
            
            // Insert new Today's product dates
            $todays_product_dates = isset($input['todays_product_dates']) ? json_decode($input['todays_product_dates'], true) : [];
            error_log("Decoded todays_product_dates: " . print_r($todays_product_dates, true));
            error_log("Available today status ID: $availtoday_status_id");
            
            if (!empty($todays_product_dates)) {
                $today_date_stmt = $conn->prepare("INSERT INTO todays_products_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
                foreach ($todays_product_dates as $date) {
                    error_log("Processing date: '$date'");
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        $today_date_stmt->bind_param("isi", $id, $date, $availtoday_status_id);
                        if ($today_date_stmt->execute()) {
                            error_log("SUCCESS: Inserted date $date for product $id");
                        } else {
                            error_log("ERROR: Failed to insert date $date: " . $today_date_stmt->error);
                        }
                    } else {
                        error_log("INVALID date format: '$date'");
                    }
                }
                $today_date_stmt->close();
            } else {
                error_log("No todays_product_dates to insert");
            }
        } else {
            // If not Today's product, remove all Today's product dates
            $delete_today_stmt = $conn->prepare("DELETE FROM todays_products_dates WHERE product_id = ?");
            $delete_today_stmt->bind_param("i", $id);
            $delete_today_stmt->execute();
            $delete_today_stmt->close();
        }
        
    // Handle regular products that are also available today (keep functionality; term-only change)
    $available_today_dates = isset($input['available_today_dates']) ? json_decode($input['available_today_dates'], true) : [];
    
    // Always delete existing regular product today dates
    $delete_regular_today_stmt = $conn->prepare("DELETE FROM regular_products_today_dates WHERE product_id = ?");
    $delete_regular_today_stmt->bind_param("i", $id);
    $delete_regular_today_stmt->execute();
    $delete_regular_today_stmt->close();
    
    // Insert new regular product today dates if isAvailableToday is checked
    $is_available_today = isset($input['is_available_today']) ? (bool)$input['is_available_today'] : false;
    if ($is_available_today && !empty($available_today_dates)) {
        $regular_today_stmt = $conn->prepare("INSERT INTO regular_products_today_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
        foreach ($available_today_dates as $date) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $regular_today_stmt->bind_param("isi", $id, $date, $availtoday_status_id);
                $regular_today_stmt->execute();
            }
        }
        $regular_today_stmt->close();
    }
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update product');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
