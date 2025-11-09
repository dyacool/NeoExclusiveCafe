<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

// Debug: Log that the script was called
error_log("=== UPDATE-PRODUCT.PHP CALLED ===");

if (!SessionManager::isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

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
    // Handle quantity - convert to int to handle strings with leading zeros like "02"
    $quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;
    
    // Handle category
    $category_id = null;
    if (isset($input['category_id']) && !empty($input['category_id']) && $input['category_id'] !== 'null') {
        $category_id = filter_var($input['category_id'], FILTER_VALIDATE_INT);
    }
    
    // Get current product status to detect transitions
    $current_status_query = $conn->prepare("SELECT status_id FROM products WHERE id = ?");
    $current_status_query->bind_param("i", $id);
    $current_status_query->execute();
    $current_status_result = $current_status_query->get_result();
    $current_status_row = $current_status_result->fetch_assoc();
    $previous_status_id = $current_status_row ? $current_status_row['status_id'] : null;
    $current_status_query->close();
    
    // Handle available days - process for both Delivery and Pick Up
    $available_days = [];
    if (isset($input['available_days']) && is_array($input['available_days'])) {
        $available_days = $input['available_days'];
    }
    
    // Auto-assign global available days when transitioning from status 4 to status 1, 2, or 3
    if ($previous_status_id == 4 && ($status_id == 1 || $status_id == 2 || $status_id == 3)) {
        // Get global available days from system settings
        require_once __DIR__ . "/../admin-includes/settings-helper.php";
        $global_days = getSetting('global_available_days', []);
        
        // If no days were provided in the input, use global days
        if (empty($available_days) && !empty($global_days)) {
            $available_days = $global_days;
            error_log("Auto-assigned global available days for product $id: " . implode(', ', $available_days));
        }
    }
    
    // Auto-set quantity to 0 if status is Same Day Order (status_id 4)
    // Same Day Order uses date-specific quantities, not product-level quantity
    if ($status_id == 4) {
        $quantity = 0;
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
    // Use standard error_log (writes to PHP error log)
    error_log("Availtoday Status ID received: " . ($input['availtoday_status_id'] ?? 'NOT SET'));
    error_log("Availtoday Status ID after filter: " . ($availtoday_status_id ?? 'NULL'));
    error_log("Status ID: " . $status_id);
    error_log("Is Available Today: " . ($input['is_available_today'] ?? 'NOT SET'));
    error_log("Todays Product Dates: " . ($input['todays_product_dates'] ?? 'NOT SET'));
    error_log("Available Today Dates: " . ($input['available_today_dates'] ?? 'NOT SET'));
    
    // Auto-set quantity to 0 if product is being set to unavailable
    if ($unavailable_status_id !== null) {
        $quantity = 0;
    }

    if ($id === false || empty($name) || $price === false || $status_id === false) {
        throw new Exception('Invalid input data');
    }
    
    // Quantity can be 0 (for Same Day Order products), so don't validate against false
    if ($quantity < 0) {
        throw new Exception('Quantity cannot be negative');
    }

    // Update product with unavailable status and category
    $stmt = $conn->prepare("UPDATE products SET 
        name = ?, 
        description = ?,
        price = ?, 
        status_id = ?,
        category_id = ?,
        quantity = ?,
        is_featured = ?,
        show_when_unavailable = ?,
        hide_when_unavailable = ?,
        unavailable_status_id = ?,
        availtoday_status_id = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
    
    $stmt->bind_param("ssdiiiiiiiii", 
        $name, 
        $description,
        $price, 
        $status_id,
        $category_id,
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
        
        // Update available days in product_day table
        // Logic:
        // - Status 1, 2, or 3: Available days ALWAYS work
        // - Status 1, 2, or 3 with "Set to same day order too": Available days work + calendar dates
        // - Status 4 ONLY (without being 1, 2, or 3 first): NO available days, only calendar dates
        // 
        // To detect "Status 4 only": Check if there are entries in regular_products_today_dates
        // If yes, it means it was 1, 2, or 3 with same day order, so days should work
        // If no, it's pure status 4, so days shouldn't work
        
        if ($status_id == 1 || $status_id == 2 || $status_id == 3) {
            // Status 1, 2, or 3: Always use available days
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
        } else if ($status_id == 4) {
            // Status 4: Check if this product has regular_today_dates (meaning it was 1, 2, or 3 with same day order)
            $check_regular_stmt = $conn->prepare("SELECT COUNT(*) as count FROM regular_products_today_dates WHERE product_id = ?");
            $check_regular_stmt->bind_param("i", $id);
            $check_regular_stmt->execute();
            $check_result = $check_regular_stmt->get_result();
            $has_regular_dates = $check_result->fetch_assoc()['count'] > 0;
            $check_regular_stmt->close();
            
            if ($has_regular_dates) {
                // This product was 1, 2, or 3 with "set to same day order too"
                // Keep/update available days
                $delete_stmt = $conn->prepare("DELETE FROM product_day WHERE product_id = ?");
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                if (!empty($available_days)) {
                    $day_stmt = $conn->prepare("INSERT INTO product_day (product_id, day_of_week) VALUES (?, ?)");
                    foreach ($available_days as $day) {
                        $day_stmt->bind_param("is", $id, $day);
                        $day_stmt->execute();
                    }
                    $day_stmt->close();
                }
            } else {
                // Pure status 4 (Same Day Order only): Remove all available days
                $delete_stmt = $conn->prepare("DELETE FROM product_day WHERE product_id = ?");
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
        } else {
            // Any other status: remove all available days
            $delete_stmt = $conn->prepare("DELETE FROM product_day WHERE product_id = ?");
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
        
        // Handle Today's product dates (status_id == 4 for Same Day Order)
        error_log("=== UPDATE PRODUCT DATES DEBUG ===");
        error_log("Product ID: $id, Status ID: $status_id");
        error_log("Raw todays_product_dates INPUT: " . ($input['todays_product_dates'] ?? 'NOT SET'));
        
        if ($status_id == 4) {
            error_log("Processing Today's product dates for Same Day Order...");
            
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
            // If not Same Day Order, remove all Today's product dates
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
        
        // Handle image updates
        // Primary image
        if (isset($input['primary_image_url']) && isset($input['primary_image_public_id'])) {
            // Delete existing primary image
            $conn->query("DELETE FROM product_images WHERE product_id = $id AND is_primary = 1");
            
            // Insert new primary image
            $stmt_img = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 1)");
            $stmt_img->bind_param("iss", $id, $input['primary_image_url'], $input['primary_image_public_id']);
            $stmt_img->execute();
            $stmt_img->close();
        } else if (isset($input['remove_primary_image']) && $input['remove_primary_image']) {
            // Remove primary image
            $conn->query("DELETE FROM product_images WHERE product_id = $id AND is_primary = 1");
        }
        
        // Additional images
        if (isset($input['additional_image_urls']) && isset($input['additional_image_public_ids'])) {
            $urls = json_decode($input['additional_image_urls'], true);
            $publicIds = json_decode($input['additional_image_public_ids'], true);
            
            if (is_array($urls) && is_array($publicIds) && count($urls) === count($publicIds)) {
                $stmt_img = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 0)");
                
                foreach ($urls as $index => $url) {
                    $publicId = $publicIds[$index];
                    $stmt_img->bind_param("iss", $id, $url, $publicId);
                    $stmt_img->execute();
                }
                $stmt_img->close();
            }
        }
        
        // Remove additional images
        if (isset($input['remove_additional_image_ids'])) {
            $idsToRemove = json_decode($input['remove_additional_image_ids'], true);
            if (is_array($idsToRemove) && count($idsToRemove) > 0) {
                $placeholders = implode(',', array_fill(0, count($idsToRemove), '?'));
                $stmt_del = $conn->prepare("DELETE FROM product_images WHERE id IN ($placeholders)");
                $types = str_repeat('i', count($idsToRemove));
                $stmt_del->bind_param($types, ...$idsToRemove);
                $stmt_del->execute();
                $stmt_del->close();
            }
        }
        
        // Log the activity
        logAdminActivity($conn, 'UPDATE', "Updated product: $name (ID: $id)", 'products', $id);
        
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
