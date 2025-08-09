<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check admin authentication
session_start();
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
        price = ?, 
        status_id = ?, 
        quantity = ?,
        is_featured = ?,
        show_when_unavailable = ?,
        hide_when_unavailable = ?,
        unavailable_status_id = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
    
    $stmt->bind_param("sdiiiiiii", 
        $name, 
        $price, 
        $status_id, 
        $quantity,
        $is_featured,
        $show_when_unavailable,
        $hide_when_unavailable,
        $unavailable_status_id,
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
        
        // Update available days in product_day table for Delivery, Pick Up, and Available Today
        if ($status_id == 1 || $status_id == 2 || $status_id == 3) {
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
            // If status is not Delivery, Pick Up, or Available Today, remove all available days
            $delete_stmt = $conn->prepare("DELETE FROM product_day WHERE product_id = ?");
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            $delete_stmt->close();
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
