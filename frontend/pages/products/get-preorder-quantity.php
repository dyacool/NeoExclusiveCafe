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

// Get user_id from session (if logged in)
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

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
    $stmt->close();
    
    // Get quantity already in cart for this user
    $cart_quantity = 0;
    if ($user_id > 0) {
        $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ? AND product_id = ?";
        $cart_stmt = $conn->prepare($cart_query);
        
        if ($cart_stmt) {
            $cart_stmt->bind_param("ii", $user_id, $product_id);
            $cart_stmt->execute();
            $cart_result = $cart_stmt->get_result();
            
            if ($cart_row = $cart_result->fetch_assoc()) {
                $cart_quantity = intval($cart_row['total'] ?? 0);
            }
            
            $cart_stmt->close();
        }
    }
    
    echo json_encode([
        'success' => true,
        'quantity' => intval($quantity),
        'cart_quantity' => $cart_quantity,
        'product_id' => $product_id
    ]);
    
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
