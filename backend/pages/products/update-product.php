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
    // Validate input
    if (!isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['price']) || 
        !isset($_POST['status_id']) || !isset($_POST['quantity'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize and validate input
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $status_id = filter_var($_POST['status_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    
    // Handle days_to_make - allow empty/null values since column is DEFAULT NULL
    $days_to_make = null;
    if (isset($_POST['days_to_make']) && $_POST['days_to_make'] !== '') {
        $days_to_make = filter_var($_POST['days_to_make'], FILTER_VALIDATE_INT);
        if ($days_to_make === false) {
            throw new Exception('Invalid days to make value');
        }
    }
    
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $show_when_unavailable = isset($_POST['show_when_unavailable']) ? 1 : 0;
    $hide_when_unavailable = isset($_POST['hide_when_unavailable']) ? 1 : 0;

    if ($id === false || empty($name) || $price === false || $status_id === false || $quantity === false) {
        throw new Exception('Invalid input data');
    }

    // Use a different approach for nullable days_to_make
    if ($days_to_make === null) {
        $stmt = $conn->prepare("UPDATE products SET 
            name = ?, 
            price = ?, 
            status_id = ?, 
            quantity = ?,
            days_to_make = NULL,
            is_featured = ?,
            show_when_unavailable = ?,
            hide_when_unavailable = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        
        $stmt->bind_param("sdiiiiis", 
            $name, 
            $price, 
            $status_id, 
            $quantity,
            $is_featured,
            $show_when_unavailable,
            $hide_when_unavailable,
            $id
        );
    } else {
        $stmt = $conn->prepare("UPDATE products SET 
            name = ?, 
            price = ?, 
            status_id = ?, 
            quantity = ?,
            days_to_make = ?,
            is_featured = ?,
            show_when_unavailable = ?,
            hide_when_unavailable = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        
        $stmt->bind_param("sdiiiiiii", 
            $name, 
            $price, 
            $status_id, 
            $quantity,
            $days_to_make,
            $is_featured,
            $show_when_unavailable,
            $hide_when_unavailable,
            $id
        );
    }

    if ($stmt->execute()) {
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
