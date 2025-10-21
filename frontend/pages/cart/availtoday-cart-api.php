<?php
/**
 * Available Today Cart API
 * Handles cart operations for same-day products
 */

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Include database connection
require_once '../../../backend/pages/admin-includes/database.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

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
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("[availtoday-cart-api.php] Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
}

/**
 * Add item to Available Today cart
 */
function addToAvailTodayCart() {
    global $conn, $user_id;
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID or quantity']);
        return;
    }
    
    // Verify product exists and is Available Today (status_id = 3)
    $product_check = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ? AND status_id = 3 AND deleted_at IS NULL");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    $product_result = $product_check->get_result();
    
    if ($product_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Product not available']);
        return;
    }
    
    $product = $product_result->fetch_assoc();
    
    // Check if enough stock is available
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'error' => 'Insufficient stock available']);
        return;
    }
    
    // Check if item already exists in cart
    $check_stmt = $conn->prepare("SELECT quantity FROM availtoday_cart WHERE user_id = ? AND product_id = ?");
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing item quantity
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        $update_stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item quantity updated',
                'product_name' => $product['name']
            ]);
        } else {
            throw new Exception("Failed to update cart item");
        }
        $update_stmt->close();
    } else {
        // Insert new item
        $insert_stmt = $conn->prepare("INSERT INTO availtoday_cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item added to cart',
                'product_name' => $product['name']
            ]);
        } else {
            throw new Exception("Failed to add item to cart");
        }
        $insert_stmt->close();
    }
    
    $check_stmt->close();
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
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        return;
    }
    
    if ($quantity <= 0) {
        // Remove item if quantity is 0 or negative
        $delete_stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE user_id = ? AND product_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $product_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } else {
            throw new Exception("Failed to remove cart item");
        }
        $delete_stmt->close();
    } else {
        // Update quantity
        $update_stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $update_stmt->bind_param("iii", $quantity, $user_id, $product_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        } else {
            throw new Exception("Failed to update cart item");
        }
        $update_stmt->close();
    }
}

/**
 * Remove item from cart
 */
function removeFromAvailTodayCart() {
    global $conn, $user_id;
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        throw new Exception("Failed to remove item from cart");
    }
    
    $stmt->close();
}

/**
 * Clear all items from cart
 */
function clearAvailTodayCart() {
    global $conn, $user_id;
    
    $stmt = $conn->prepare("DELETE FROM availtoday_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
    } else {
        throw new Exception("Failed to clear cart");
    }
    
    $stmt->close();
}

/**
 * Get all items in cart
 */
function getAvailTodayCart() {
    global $conn, $user_id;
    
    $stmt = $conn->prepare("
        SELECT 
            ca.id as cart_id,
            ca.product_id,
            p.name as product_name,
            ca.quantity,
            p.price,
            (ca.quantity * p.price) as total_price,
            (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image_url,
            p.quantity as stock_quantity
        FROM availtoday_cart ca
        JOIN products p ON ca.product_id = p.id
        WHERE ca.user_id = ? AND p.status_id = 3 AND p.deleted_at IS NULL
        ORDER BY ca.id DESC
    ");
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
            'name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => floatval($row['price']),
            'total_price' => floatval($row['total_price']),
            'image_url' => $row['image_url'],
            'stock_quantity' => $row['stock_quantity']
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
 * Get cart item count
 */
function getAvailTodayCartCount() {
    global $conn, $user_id;
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as item_count FROM availtoday_cart ca JOIN products p ON ca.product_id = p.id WHERE ca.user_id = ? AND p.status_id = 3");
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
 * Get cart total
 */
function getAvailTodayCartTotal() {
    global $conn, $user_id;
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(ca.quantity * p.price), 0) as cart_total FROM availtoday_cart ca JOIN products p ON ca.product_id = p.id WHERE ca.user_id = ? AND p.status_id = 3");
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

$conn->close();
?>

