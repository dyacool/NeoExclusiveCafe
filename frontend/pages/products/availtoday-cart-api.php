<?php
/**
 * Available Today Cart API - Frontend Proxy
 * This file acts as a local endpoint for the product dashboard
 */

// Set JSON content type FIRST
header('Content-Type: application/json');

// Error handling
try {
    // Include database connection first (it starts the session properly)
    require_once __DIR__ . '/../../../backend/pages/admin-includes/database.php';
    require_once __DIR__ . '/../../../includes/session-manager.php';

    // Get the action
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

    error_log("[frontend availtoday-cart-api.php] API called with action: $action");
    error_log("[frontend availtoday-cart-api.php] Session ID: " . session_id());
    error_log("[frontend availtoday-cart-api.php] Session vars: " . print_r($_SESSION, true));
    error_log("[frontend availtoday-cart-api.php] User logged in: " . (SessionManager::isUserLoggedIn() ? 'yes' : 'no'));

    // Check if user is logged in
    if (!SessionManager::isUserLoggedIn()) {
        error_log("[frontend availtoday-cart-api.php] AUTHENTICATION FAILED - User not logged in");
        echo json_encode(['success' => false, 'error' => 'User not authenticated', 'debug' => 'User not logged in']);
        exit;
    }

    $user_id = SessionManager::getUserId();
    error_log("[frontend availtoday-cart-api.php] User authenticated with ID: $user_id - proceeding with action: $action");

// Handle different actions
switch ($action) {
    case 'add':
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
            exit;
        }
        
        if ($quantity < 1) {
            echo json_encode(['success' => false, 'error' => 'Invalid quantity']);
            exit;
        }
        
        // Get product details and check if it's available for same-day
        // Products can be same-day if: status_id = 4 OR has entry in todays_products_dates/regular_products_today_dates
        $stmt = $conn->prepare("
            SELECT p.name, p.price, p.status_id, qpd.quantity as sdo_stock
            FROM products p
            LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND DATE(qpd.date) = CURDATE()
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            exit;
        }
        
        $product = $result->fetch_assoc();
        
        // Check if product is available for same-day order
        $has_sameday = false;
        $available_stock = 0;
        
        // If product has stock in quantity_per_day_sdo for today, it's available for same-day
        $sdo_stock = intval($product['sdo_stock'] ?? 0);
        
        if ($sdo_stock > 0) {
            // Has same-day stock regardless of status
            $has_sameday = true;
            $available_stock = $sdo_stock;
        }
        
        if (!$has_sameday) {
            echo json_encode(['success' => false, 'error' => 'Product not available for same-day order']);
            exit;
        }
        
        // Check if requested quantity exceeds available stock
        if ($quantity > $available_stock) {
            echo json_encode([
                'success' => false, 
                'error' => "Insufficient stock. Only {$available_stock} available"
            ]);
            exit;
        }
        
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM availtoday_cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        if ($cart_result->num_rows > 0) {
            // Update existing cart item
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            // Check if total quantity exceeds available stock
            if ($new_quantity > $available_stock) {
                $available_to_add = max(0, $available_stock - $cart_item['quantity']);
                echo json_encode([
                    'success' => false, 
                    'error' => "Cannot add {$quantity} more. You already have {$cart_item['quantity']} in cart. Only {$available_to_add} more available."
                ]);
                exit;
            }
            
            // Check if table has updated_at column
            $check_column = $conn->query("SHOW COLUMNS FROM availtoday_cart LIKE 'updated_at'");
            if ($check_column->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ? WHERE id = ?");
            }
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            
            if (!$stmt->execute()) {
                error_log("[availtoday-cart-api.php] Failed to update cart: " . $stmt->error);
                echo json_encode(['success' => false, 'error' => 'Failed to update cart: ' . $stmt->error]);
                exit;
            }
        } else {
            // Insert new cart item
            $stmt = $conn->prepare("INSERT INTO availtoday_cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
            
            if (!$stmt->execute()) {
                error_log("[availtoday-cart-api.php] Failed to insert into cart: " . $stmt->error);
                echo json_encode(['success' => false, 'error' => 'Failed to add to cart: ' . $stmt->error]);
                exit;
            }
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
            // Check if table has updated_at column
            $check_column = $conn->query("SHOW COLUMNS FROM availtoday_cart LIKE 'updated_at'");
            if ($check_column->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE availtoday_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            }
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

if (isset($conn)) {
    $conn->close();
}

} catch (Exception $e) {
    error_log("[availtoday-cart-api.php] EXCEPTION: " . $e->getMessage());
    error_log("[availtoday-cart-api.php] TRACE: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}