<?php
/**
 * Available Today Cart API
 * Handles database operations for Available Today cart items
 */

// Include necessary files
require_once '../admin-includes/database.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, log them instead
ini_set('log_errors', 1);

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Debug information (remove in production)
error_log("Available Today Cart API - Method: $method, Action: $action");
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not authenticated - session user_id not found");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated', 'debug' => 'Session user_id not found']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'add':
            addToAvailTodayCart();
            break;
        case 'update':
            updateAvailTodayCartItem();
            break;
        case 'remove':
            removeFromAvailTodayCart();
            break;
        case 'clear':
            clearAvailTodayCart();
            break;
        case 'get':
            getAvailTodayCart();
            break;
        case 'count':
            getAvailTodayCartCount();
            break;
        case 'total':
            getAvailTodayCartTotal();
            break;
        case 'test':
            echo json_encode([
                'success' => true, 
                'message' => 'API is working',
                'user_id' => $user_id,
                'method' => $method,
                'action' => $action,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action', 'received_action' => $action]);
    }
} catch (Exception $e) {
    error_log("Available Today Cart API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

/**
 * Add item to Available Today cart
 */
function addToAvailTodayCart() {
    global $conn, $user_id;
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($product_id <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product ID or quantity']);
        return;
    }
    
    // Verify product exists and is Available Today (status_id = 3)
    $product_check = $conn->prepare("SELECT id, name, price, status_id, quantity FROM products WHERE id = ? AND status_id = 3 AND deleted_at IS NULL");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    $product_result = $product_check->get_result();
    
    if ($product_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Product not found or not available today']);
        return;
    }
    
    $product = $product_result->fetch_assoc();
    
    // Check if enough stock is available
    if ($product['quantity'] < $quantity) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Insufficient stock available']);
        return;
    }
    
    // Use stored procedure to add to cart
    $stmt = $conn->prepare("CALL AddToAvailTodayCart(?, ?, ?, ?)");
    $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $product['price']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Item added to Available Today cart',
            'product_name' => $product['name']
        ]);
    } else {
        throw new Exception("Failed to add item to cart: " . $conn->error);
    }
    
    $stmt->close();
    $product_check->close();
}

/**
 * Update cart item quantity
 */
function updateAvailTodayCartItem() {
    global $conn, $user_id;
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        return;
    }
    
    // Use stored procedure to update quantity
    $stmt = $conn->prepare("CALL UpdateAvailTodayCartQuantity(?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
    
    if ($stmt->execute()) {
        $action_message = $quantity > 0 ? 'Cart updated' : 'Item removed from cart';
        echo json_encode([
            'success' => true,
            'message' => $action_message
        ]);
    } else {
        throw new Exception("Failed to update cart item: " . $conn->error);
    }
    
    $stmt->close();
}

/**
 * Remove item from Available Today cart
 */
function removeFromAvailTodayCart() {
    global $conn, $user_id;
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM cart_availToday WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from Available Today cart'
        ]);
    } else {
        throw new Exception("Failed to remove item from cart: " . $conn->error);
    }
    
    $stmt->close();
}

/**
 * Clear all items from Available Today cart
 */
function clearAvailTodayCart() {
    global $conn, $user_id;
    
    // Use stored procedure to clear cart
    $stmt = $conn->prepare("CALL ClearAvailTodayCart(?)");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Available Today cart cleared'
        ]);
    } else {
        throw new Exception("Failed to clear cart: " . $conn->error);
    }
    
    $stmt->close();
}

/**
 * Get all items in Available Today cart
 */
function getAvailTodayCart() {
    global $conn, $user_id;
    
    // Use the view to get detailed cart information
    $stmt = $conn->prepare("SELECT * FROM view_cart_availToday_details WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $total = 0;
    $item_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = [
            'cart_id' => $row['cart_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'description' => $row['product_description'],
            'quantity' => $row['quantity'],
            'price' => floatval($row['cart_price']),
            'current_price' => floatval($row['current_price']),
            'total_price' => floatval($row['total_price']),
            'image_url' => $row['image_url'],
            'stock_quantity' => $row['stock_quantity'],
            'status_name' => $row['status_name'],
            'available_days' => $row['available_days'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
        
        $total += floatval($row['total_price']);
        $item_count += intval($row['quantity']);
    }
    
    echo json_encode([
        'success' => true,
        'cart_items' => $cart_items,
        'total' => $total,
        'item_count' => $item_count
    ]);
    
    $stmt->close();
}

/**
 * Get Available Today cart item count
 */
function getAvailTodayCartCount() {
    global $conn, $user_id;
    
    $stmt = $conn->prepare("SELECT GetAvailTodayCartCount(?) as item_count");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'item_count' => intval($row['item_count'])
    ]);
    
    $stmt->close();
}

/**
 * Get Available Today cart total
 */
function getAvailTodayCartTotal() {
    global $conn, $user_id;
    
    $stmt = $conn->prepare("SELECT GetAvailTodayCartTotal(?) as cart_total");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'cart_total' => floatval($row['cart_total'])
    ]);
    
    $stmt->close();
}

// Close database connection
$conn->close();
?>
