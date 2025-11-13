<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection first (it starts the session)
require_once __DIR__ . "/../admin-includes/database.php";

// Include SessionManager and check admin authentication
require_once __DIR__ . '/../../../includes/session-manager.php';

if (!SessionManager::isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}
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

    // Restore the product by clearing deleted_at timestamp
    $stmt = $conn->prepare("UPDATE products SET deleted_at = NULL WHERE id = ?");
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
        logAdminActivity($conn, 'UPDATE', "Restored product from archive: $product_name", 'products', $id);
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to restore product');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
