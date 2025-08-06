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
    
    // Handle available days - only process if status is Delivery
    $available_days = [];
    if (isset($input['available_days']) && is_array($input['available_days'])) {
        $available_days = $input['available_days'];
    }
    
    $is_featured = isset($input['is_featured']) ? 1 : 0;
    $show_when_unavailable = isset($input['show_when_unavailable']) ? 1 : 0;
    $hide_when_unavailable = isset($input['hide_when_unavailable']) ? 1 : 0;

    if ($id === false || empty($name) || $price === false || $status_id === false || $quantity === false) {
        throw new Exception('Invalid input data');
    }

    // Update product without days_to_make field
    $stmt = $conn->prepare("UPDATE products SET 
        name = ?, 
        price = ?, 
        status_id = ?, 
        quantity = ?,
        is_featured = ?,
        show_when_unavailable = ?,
        hide_when_unavailable = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
    
    $stmt->bind_param("sdiiiiii", 
        $name, 
        $price, 
        $status_id, 
        $quantity,
        $is_featured,
        $show_when_unavailable,
        $hide_when_unavailable,
        $id
    );

    if ($stmt->execute()) {
        // Update available days in product_day table only if status is Delivery
        if ($status_id == 2) {
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
            // If status is not Delivery, remove all available days
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
