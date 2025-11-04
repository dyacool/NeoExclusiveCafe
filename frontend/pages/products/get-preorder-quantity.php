<?php
/**
 * Get Pre-Order Quantity for a Product
 * 
 * Fetches the current pre-order stock quantity from products.quantity
 * This is used when the user selects "Pre-Order" in the quantity modal
 */

header('Content-Type: application/json');

require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";

// Validate product_id parameter
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid product_id parameter'
    ]);
    exit;
}

$product_id = intval($_GET['product_id']);

try {
    // Fetch pre-order quantity from products table
    $query = "SELECT quantity FROM products WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Product not found'
        ]);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $quantity = $row['quantity'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'quantity' => intval($quantity),
        'product_id' => $product_id
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching pre-order quantity: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
