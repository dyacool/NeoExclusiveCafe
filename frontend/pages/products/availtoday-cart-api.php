<?php
/**
 * Available Today Cart API - Frontend Proxy
 * This file acts as a local endpoint for the product dashboard
 */

// Start session
session_start();

// Include database connection
require_once __DIR__ . '/../../../backend/pages/admin-includes/database.php';
require_once __DIR__ . '/../../../includes/session-manager.php';

// Set JSON content type
header('Content-Type: application/json');

// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

error_log("[frontend availtoday-cart-api.php] API called with action: $action");
error_log("[frontend availtoday-cart-api.php] User logged in: " . (SessionManager::isUserLoggedIn() ? 'yes' : 'no'));

// Check if user is logged in
if (!SessionManager::isUserLoggedIn()) {
    error_log("[frontend availtoday-cart-api.php] AUTHENTICATION FAILED - User not logged in");
    echo json_encode(['success' => false, 'error' => 'User not authenticated', 'debug' => 'User not logged in']);
    exit;
}

$user_id = SessionManager::getUserId();
error_log("[frontend availtoday-cart-api.php] User authenticated - proceeding with action: $action");

// Handle different actions
switch ($action) {
    case 'add':
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
            exit;
        }
        
        // Get product details
        $stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            exit;
        }
        
        $product = $result->fetch_assoc();
        
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM availtoday_cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        if ($cart_result->num_rows > 0) {
            // Update existing cart item
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            $stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            $stmt->execute();
        } else {
            // Insert new cart item
            $stmt = $conn->prepare("INSERT INTO availtoday_cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => true, 
            'product_name' => $product['name'],
            'message' => 'Product added to cart'
        ]);
        break;
        
    case 'get':
        // Get all cart items for user
        $stmt = $conn->prepare("
            SELECT ac.id, ac.product_id, ac.quantity, p.name, p.price, pi.image_url
            FROM availtoday_cart ac
            JOIN products p ON ac.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE ac.user_id = ? AND p.deleted_at IS NULL
            ORDER BY ac.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cart_items = [];
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }
        
        echo json_encode(['success' => true, 'cart_items' => $cart_items]);
        break;
        
    case 'update':
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        
        if ($quantity <= 0) {
            // Remove from cart
            $stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        } else {
            // Update quantity
            $stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart updated']);
        break;
        
    case 'clear':
        // Clear all cart items for user
        $stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

$conn->close();
?>

