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
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['id'])) {
        throw new Exception('Product ID is required');
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id === false) {
        throw new Exception('Invalid product ID');
    }

    // Soft delete the product by setting deleted_at timestamp
    $stmt = $conn->prepare("UPDATE products SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Get product name for logging
        $name_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
        $name_stmt->bind_param("i", $id);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        $product_name = $name_result->num_rows > 0 ? $name_result->fetch_assoc()['name'] : "Product #$id";
        $name_stmt->close();
        
        // Log the activity
        logAdminActivity($conn, 'DELETE', "Deleted product: $product_name", 'products', $id);
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete product');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
