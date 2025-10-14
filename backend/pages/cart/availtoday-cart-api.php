<?php
/**
 * Available Today Cart API
 * Handles database operations for Available Today cart items
 */

// Set session cookie parameters before starting session
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Empty domain works for all subdomains and localhost
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax' // Changed from Strict to Lax to allow same-site requests
]);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
$suppress_db_debug = true; // Suppress database debug output for API
require_once __DIR__ . '/../admin-includes/database.php';

// Set JSON content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins for development
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, log them instead
ini_set('log_errors', 1);

// Check if database connection exists
if (!isset($conn) || !$conn) {
    error_log("[availtoday-cart-api.php] DB connection failed");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Handle different HTTP methods

// Check if user is logged in
error_log("[availtoday-cart-api.php] Session check - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
error_log("[availtoday-cart-api.php] Full session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    error_log("[availtoday-cart-api.php] User not authenticated - session user_id not found");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated', 'debug' => 'Session user_id not found', 'session_id' => session_id()]);
    exit;
}

$user_id = $_SESSION['user_id'];
error_log("[availtoday-cart-api.php] User authenticated - user_id: $user_id");

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
        case 'check_limit':
            checkOrderLimit();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action', 'received_action' => $action]);
    }
} catch (Exception $e) {
    error_log("[availtoday-cart-api.php] Exception: " . $e->getMessage());
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
    
    // Check order limit first (robust against schema variations)
    $limit_check = $conn->prepare("SELECT limit_orders FROM availtoday_order_limit ORDER BY id DESC LIMIT 1");
    if ($limit_check && $limit_check->execute()) {
        $limit_result = $limit_check->get_result();
        if ($limit_result && $limit_result->num_rows > 0) {
            $limit_data = $limit_result->fetch_assoc();
            $limit_orders = (int)$limit_data['limit_orders'];
            
            if ($limit_orders === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Orders are temporarily closed']);
                return;
            }
            
            // Check if daily limit has been reached
            $today = date('Y-m-d');
            $today_orders = 0;
            
            // Prefer created_at if exists
            $colCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'created_at'");
            if ($colCheck && $colCheck->num_rows > 0) {
                $count_check = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE DATE(created_at) = ?");
                if ($count_check) {
                    $count_check->bind_param("s", $today);
                    if ($count_check->execute()) {
                        $count_result = $count_check->get_result();
                        if ($count_result) {
                            $count_data = $count_result->fetch_assoc();
                            $today_orders = (int)($count_data['order_count'] ?? 0);
                        }
                    }
                    $count_check->close();
                }
            } else {
                // Try alternative columns
                $alternativeColumns = ['order_date', 'date_created', 'timestamp', 'date'];
                foreach ($alternativeColumns as $col) {
                    $check = $conn->query("SHOW COLUMNS FROM orders LIKE '" . $conn->real_escape_string($col) . "'");
                    if ($check && $check->num_rows > 0) {
                        $sql = "SELECT COUNT(*) as order_count FROM orders WHERE DATE($col) = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("s", $today);
                            if ($stmt->execute()) {
                                $res = $stmt->get_result();
                                if ($res) {
                                    $row = $res->fetch_assoc();
                                    $today_orders = (int)($row['order_count'] ?? 0);
                                }
                            }
                            $stmt->close();
                        }
                        break;
                    }
                }
            }
            
            if ($today_orders >= $limit_orders) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Daily order limit has been reached']);
                return;
            }
        }
        $limit_check->close();
    }
    
    // Check business hours
    $business_hours_check = $conn->prepare("SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1");
    $business_hours_check->execute();
    $business_hours_result = $business_hours_check->get_result();
    
    if ($business_hours_result->num_rows > 0) {
        $business_hours = $business_hours_result->fetch_assoc();
        $current_time = date('H:i:s');
        
        // Check if current time is after closing time
        if ($current_time > $business_hours['closing_time']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Business hours closed. Cart has been cleared.']);
            
            // Clear the cart for this user
            clearAvailTodayCart();
            return;
        }
    }
    $business_hours_check->close();
    
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
    
    // Check if item already exists in cart
    $check_query = "SELECT quantity FROM cart_availtoday WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing item quantity
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        // Check if updated_at column exists
        $hasUpdatedAt = false;
        $updColCheck = $conn->query("SHOW COLUMNS FROM cart_availtoday LIKE 'updated_at'");
        if ($updColCheck && $updColCheck->num_rows > 0) { $hasUpdatedAt = true; }

        if ($hasUpdatedAt) {
            $update_query = "UPDATE cart_availtoday SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?";
        } else {
            $update_query = "UPDATE cart_availtoday SET quantity = ? WHERE user_id = ? AND product_id = ?";
        }
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item quantity updated in Available Today cart',
                'product_name' => $product['name']
            ]);
        } else {
            throw new Exception("Failed to update cart item: " . $conn->error);
        }
        $update_stmt->close();
    } else {
        // Insert new item (handle optional price column)
        $hasPriceColumn = false;
        $priceColCheck = $conn->query("SHOW COLUMNS FROM cart_availtoday LIKE 'price'");
        if ($priceColCheck && $priceColCheck->num_rows > 0) {
            $hasPriceColumn = true;
        }

        // Check created_at/updated_at columns
        $hasCreatedAt = false; $hasUpdatedAt = false;
        $crtColCheck = $conn->query("SHOW COLUMNS FROM cart_availtoday LIKE 'created_at'");
        if ($crtColCheck && $crtColCheck->num_rows > 0) { $hasCreatedAt = true; }
        $updColCheck2 = $conn->query("SHOW COLUMNS FROM cart_availtoday LIKE 'updated_at'");
        if ($updColCheck2 && $updColCheck2->num_rows > 0) { $hasUpdatedAt = true; }

        if ($hasPriceColumn && $hasCreatedAt && $hasUpdatedAt) {
            $insert_query = "INSERT INTO cart_availtoday (user_id, product_id, quantity, price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $priceValue = (float)$product['price'];
            $insert_stmt->bind_param("iiid", $user_id, $product_id, $quantity, $priceValue);
        } elseif ($hasPriceColumn) {
            $insert_query = "INSERT INTO cart_availtoday (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $priceValue = (float)$product['price'];
            $insert_stmt->bind_param("iiid", $user_id, $product_id, $quantity, $priceValue);
        } elseif ($hasCreatedAt && $hasUpdatedAt) {
            $insert_query = "INSERT INTO cart_availtoday (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
        } else {
            $insert_query = "INSERT INTO cart_availtoday (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
        }

        if ($insert_stmt && $insert_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item added to Available Today cart',
                'product_name' => $product['name']
            ]);
        } else {
            throw new Exception("Failed to add item to cart: " . $conn->error);
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
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        return;
    }
    
    if ($quantity <= 0) {
        // Remove item if quantity is 0 or negative
        $delete_query = "DELETE FROM cart_availtoday WHERE user_id = ? AND product_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $user_id, $product_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item removed from cart'
            ]);
        } else {
            throw new Exception("Failed to remove cart item: " . $conn->error);
        }
        $delete_stmt->close();
    } else {
        // Update quantity
        $update_query = "UPDATE cart_availtoday SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("iii", $quantity, $user_id, $product_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Cart updated'
            ]);
        } else {
            throw new Exception("Failed to update cart item: " . $conn->error);
        }
        $update_stmt->close();
    }
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
    
    $stmt = $conn->prepare("DELETE FROM cart_availtoday WHERE user_id = ? AND product_id = ?");
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
    
    $clear_query = "DELETE FROM cart_availtoday WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_query);
    $clear_stmt->bind_param("i", $user_id);
    
    if ($clear_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Available Today cart cleared'
        ]);
    } else {
        throw new Exception("Failed to clear cart: " . $conn->error);
    }
    
    $clear_stmt->close();
}

/**
 * Get all items in Available Today cart
 */
function getAvailTodayCart() {
    global $conn, $user_id;
    
    // Use direct query instead of view to avoid potential issues
    $stmt = $conn->prepare("
        SELECT 
            ca.id as cart_id,
            ca.product_id,
            p.name as product_name,
            p.description as product_description,
            ca.quantity,
            p.price as cart_price,
            p.price as current_price,
            (ca.quantity * p.price) as total_price,
            '' as image_url,
            p.quantity as stock_quantity,
            ps.name as status_name,
            '' as available_days,
            ca.created_at,
            ca.updated_at
        FROM cart_availtoday ca
        JOIN products p ON ca.product_id = p.id
        LEFT JOIN product_statuses ps ON p.status_id = ps.id
        WHERE ca.user_id = ? 
        ORDER BY ca.created_at DESC
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
    
    $count_query = "SELECT COUNT(*) as item_count FROM cart_availtoday WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $result = $count_stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'item_count' => intval($row['item_count'])
    ]);
    
    $count_stmt->close();
}

/**
 * Get Available Today cart total
 */
function getAvailTodayCartTotal() {
    global $conn, $user_id;
    
    // Calculate total directly instead of using stored function
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(ca.quantity * p.price), 0) as cart_total
        FROM cart_availtoday ca
        JOIN products p ON ca.product_id = p.id
        WHERE ca.user_id = ?
    ");
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

/**
 * Check order limit status
 */
function checkOrderLimit() {
    global $conn;
    
    try {
        // Get current limit
        $limitSql = "SELECT limit_orders FROM availtoday_order_limit ORDER BY id DESC LIMIT 1";
        $limitResult = $conn->query($limitSql);
        
        if (!$limitResult || $limitResult->num_rows === 0) {
            echo json_encode([
                'success' => true, 
                'can_order' => true, 
                'limit_orders' => 10
            ]);
            return;
        }
        
        $limitRow = $limitResult->fetch_assoc();
        $limitOrders = (int)$limitRow['limit_orders'];
        
        if ($limitOrders === 0) {
            echo json_encode([
                'success' => true, 
                'can_order' => false, 
                'limit_orders' => 0,
                'message' => 'Orders are temporarily closed'
            ]);
            return;
        }
        
        // Count today's orders - try different possible date columns
        $today = date('Y-m-d');
        $todayOrders = 0;
        
        // First try to check if orders table has created_at column
        $checkColumnSql = "SHOW COLUMNS FROM orders LIKE 'created_at'";
        $checkColumnResult = $conn->query($checkColumnSql);
        
        if ($checkColumnResult && $checkColumnResult->num_rows > 0) {
            // created_at column exists
            $countSql = "SELECT COUNT(*) as order_count FROM orders WHERE DATE(created_at) = ?";
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param("s", $today);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $todayOrders = (int)$countRow['order_count'];
            $countStmt->close();
        } else {
            // Try alternative date columns
            $alternativeColumns = ['order_date', 'date_created', 'timestamp', 'date'];
            foreach ($alternativeColumns as $col) {
                $checkColSql = "SHOW COLUMNS FROM orders LIKE '$col'";
                $checkColResult = $conn->query($checkColSql);
                if ($checkColResult && $checkColResult->num_rows > 0) {
                    $countSql = "SELECT COUNT(*) as order_count FROM orders WHERE DATE($col) = ?";
                    $countStmt = $conn->prepare($countSql);
                    $countStmt->bind_param("s", $today);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result();
                    $countRow = $countResult->fetch_assoc();
                    $countStmt->close();
                    $todayOrders = (int)$countRow['order_count'];
                    break;
                }
            }
        }
        
        $canOrder = $todayOrders < $limitOrders;
        
        echo json_encode([
            'success' => true, 
            'can_order' => $canOrder, 
            'limit_orders' => $limitOrders,
            'today_orders' => $todayOrders,
            'remaining_orders' => max(0, $limitOrders - $todayOrders),
            'message' => $canOrder ? 'Orders are open' : 'Daily order limit reached'
        ]);
        
        $countStmt->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Close database connection
$conn->close();
?>
