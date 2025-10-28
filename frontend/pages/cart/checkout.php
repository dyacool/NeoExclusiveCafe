<?php
// Enable error reporting temporarily to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure output is not buffered
if (ob_get_level()) {
    ob_end_clean();
}

// Set session cookie parameters based on environment
$session_domain = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    // Only set domain for production environment
    if (strpos($host, 'neocafe.cafe') !== false) {
        $session_domain = 'neocafe.cafe';
    }
    // For localhost/local development, leave domain empty
}

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => $session_domain
]);
session_start();

// Require login for checkout - check for user role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    error_log("Checkout access denied - Session check failed:");
    error_log("user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
    error_log("user_role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NOT SET'));
    error_log("Redirecting to login page");
    header("Location: ../../login/user/login-signup.php");
    exit();
}

// Include database connection first (before any output)
require_once '../../../backend/pages/admin-includes/database.php';

// Validate cart items BEFORE any output
$selected_cart_ids = [];
$subtotal = 0;

// Cart table fixed - no more debug needed

// Get cart items from GET, POST, or SESSION
if (isset($_GET['cart_ids']) && !empty($_GET['cart_ids'])) {
    // From cart.php via GET
    $selected_cart_ids = array_filter(array_map('intval', explode(',', $_GET['cart_ids'])));
    error_log("Got cart IDs from GET: " . print_r($selected_cart_ids, true));
} elseif (isset($_POST['selected_cart_ids']) && !empty($_POST['selected_cart_ids'])) {
    // Sanitize array of cart IDs
    $selected_cart_ids = array_filter(array_map('intval', $_POST['selected_cart_ids']));
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    error_log("Got cart IDs from POST selected_cart_ids: " . print_r($selected_cart_ids, true));
} elseif (isset($_POST['selected_items']) && !empty($_POST['selected_items'])) {
    $selected_cart_ids = $_POST['selected_items'];
    $subtotal = $_POST['subtotal'] ?? 0;
    error_log("Got cart IDs from POST selected_items: " . print_r($selected_cart_ids, true));
} elseif (isset($_SESSION['selected_cart_ids'])) {
    $selected_cart_ids = $_SESSION['selected_cart_ids'];
    $subtotal = $_SESSION['subtotal'] ?? 0;
    error_log("Got cart IDs from SESSION: " . print_r($selected_cart_ids, true));
}

// If no cart items, redirect immediately (before any output)
if (empty($selected_cart_ids)) {
    error_log("No cart items found - redirecting to cart");
    error_log("GET: " . print_r($_GET, true));
    error_log("POST: " . print_r($_POST, true));
    error_log("SESSION selected_cart_ids: " . print_r($_SESSION['selected_cart_ids'] ?? 'NOT SET', true));
    $_SESSION['error_message'] = "No items selected for checkout. Please select items from your cart first.";
    header("Location: cart.php");
    exit();
}

$page_title = "Checkout";
$additional_css = [
    "checkout.css"
];


// Session validation passed - user is logged in

// Database already included above

// Test database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed");
} else {
    error_log("Database connection successful");
    
    // Test if we can access the cart table
    $test_query = "SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?";
    $test_stmt = $conn->prepare($test_query);
    if ($test_stmt) {
        $test_stmt->bind_param("i", $_SESSION['user_id']);
        $test_stmt->execute();
        $test_result = $test_stmt->get_result();
        if ($test_result->num_rows > 0) {
            $test_row = $test_result->fetch_assoc();
            error_log("User has " . $test_row['cart_count'] . " items in cart table");
        }
        $test_stmt->close();
    } else {
        error_log("Failed to prepare test query");
    }
    
    // Check cart table structure
    $structure_query = "DESCRIBE cart";
    $structure_result = $conn->query($structure_query);
    if ($structure_result) {
        error_log("Cart table structure:");
        while ($row = $structure_result->fetch_assoc()) {
            error_log("  " . $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . " - " . $row['Default'] . " - " . $row['Extra']);
        }
    } else {
        error_log("Failed to get cart table structure");
    }
    
    // Check if cart item 108 exists at all
    $check_cart_108 = "SELECT * FROM cart WHERE id = 108";
    $check_result = $conn->query($check_cart_108);
    if ($check_result && $check_result->num_rows > 0) {
        $check_row = $check_result->fetch_assoc();
        error_log("Cart item 108 exists: " . print_r($check_row, true));
    } else {
        error_log("Cart item 108 does not exist in cart table");
    }
}

// Initialize user array with default values
$user = array(
    'firstname' => $_SESSION['user_firstname'] ?? '',
    'lastname' => $_SESSION['user_lastname'] ?? '',
    'email' => null
);

// Get user data from session - use the exact structure we see in the console
if (isset($_SESSION['session_data']) && isset($_SESSION['session_data']['user_data'])) {
    $user = $_SESSION['session_data']['user_data'];
} elseif (isset($_SESSION['user_data'])) {
    $user = $_SESSION['user_data'];
}

// If still no email, try to get from database
if (empty($user['email']) && isset($_SESSION['user_id'])) {
    try {
        $user_id = intval($_SESSION['user_id']);
        $query = "SELECT firstname, lastname, email FROM neoexclusivecafe_crud.users WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $user = array(
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'email' => $row['email']
                );
                $_SESSION['user_data'] = $user;
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Debug output
error_log("Final user data: " . print_r($user, true));

// Add debug information to be shown in console
$debug_info = [
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'user_data' => $user,
    'session_data' => $_SESSION['session_data'] ?? []
];

// Get selected cart items from previous page
$selected_cart_ids = [];
$subtotal = 0;

// Debug: Log what we're receiving
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data at cart items check: " . print_r($_SESSION, true));

// Check for POST data first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_cart_ids'])) {
    // If it's a string (comma-separated), convert to array
    if (is_string($_POST['selected_cart_ids'])) {
        $selected_cart_ids = array_filter(explode(',', $_POST['selected_cart_ids']));
    } 
    // If it's already an array, use it directly
    elseif (is_array($_POST['selected_cart_ids'])) {
        $selected_cart_ids = array_filter($_POST['selected_cart_ids']);
    }
    $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
    error_log("Got cart IDs from POST: " . implode(', ', $selected_cart_ids));
    
    // Validate cart IDs against the valid_cart_ids from the form
    if (isset($_POST['valid_cart_ids']) && !empty($_POST['valid_cart_ids'])) {
        $valid_cart_ids = array_filter(explode(',', $_POST['valid_cart_ids']));
        $selected_cart_ids = array_intersect($selected_cart_ids, $valid_cart_ids);
        error_log("Validated cart IDs: " . implode(', ', $selected_cart_ids));
        
        if (empty($selected_cart_ids)) {
            error_log("No valid cart IDs found after validation");
            $_SESSION['error_message'] = "Invalid cart items selected. Please return to cart and try again.";
            // Can't redirect here - headers already sent
            echo '<script>window.location.href = "cart.php";</script>';
            exit();
        }
    }
}

// If no items in POST, check session
if (empty($selected_cart_ids) && isset($_SESSION['selected_cart_ids'])) {
    $selected_cart_ids = $_SESSION['selected_cart_ids'];
    $subtotal = $_SESSION['subtotal'] ?? 0;
    error_log("Got cart IDs from SESSION: " . implode(', ', $selected_cart_ids));
}

error_log("Final selected_cart_ids: " . print_r($selected_cart_ids, true));
error_log("Final subtotal: " . $subtotal);

// Debug cart data
error_log("Selected cart IDs: " . print_r($selected_cart_ids, true));
error_log("Subtotal: " . $subtotal);

// Cart validation already handled above - this redirect would fail due to headers already sent

// Validate that the selected cart IDs actually exist and belong to the current user
if (!empty($selected_cart_ids)) {
    $valid_cart_ids = [];
    foreach ($selected_cart_ids as $cart_id) {
        $validate_sql = "SELECT id, user_id FROM cart WHERE id = ? AND user_id = ?";
        $validate_stmt = $conn->prepare($validate_sql);
        if ($validate_stmt) {
            $validate_stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
            $validate_stmt->execute();
            $validate_result = $validate_stmt->get_result();
            if ($validate_result->num_rows > 0) {
                $valid_cart_ids[] = $cart_id;
                error_log("Cart item $cart_id is valid for user " . $_SESSION['user_id']);
            } else {
                error_log("Cart item $cart_id is invalid or doesn't belong to user " . $_SESSION['user_id']);
            }
            $validate_stmt->close();
        }
    }
    
    if (empty($valid_cart_ids)) {
        error_log("No valid cart items found - redirecting to cart");
        $_SESSION['error_message'] = "The selected cart items are no longer valid. Please return to cart and try again.";
        echo '<script>window.location.href = "cart.php";</script>';
        exit();
    }
    
    // Update selected_cart_ids to only include valid ones
    $selected_cart_ids = $valid_cart_ids;
    error_log("Validated cart IDs: " . implode(', ', $selected_cart_ids));
}

// Store cart selection in session for persistence
$_SESSION['selected_cart_ids'] = $selected_cart_ids;
$_SESSION['subtotal'] = $subtotal;

// Note: Available Today (status_id = 3) products have their own separate cart and checkout system
// This checkout page only handles regular cart items (status_id = 1 for Pickup, status_id = 2 for Delivery)

// Get cart items details
$cart_total = 0;
$cart_items = [];

if (!empty($selected_cart_ids)) {
    try {
                // Get cart items with product availability days
        if (count($selected_cart_ids) === 1) {
            $placeholders = '?';
            $cart_sql = "SELECT c.*, p.name, p.price, p.status_id, 
                         GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ',') as available_days
                         FROM cart c 
                         JOIN products p ON c.product_id = p.id 
                         LEFT JOIN product_day pd ON p.id = pd.product_id
                         WHERE c.id = ? AND p.status_id IN (1, 2, 3)
                         GROUP BY c.id";
        } else {
        $placeholders = str_repeat('?,', count($selected_cart_ids) - 1) . '?';
            $cart_sql = "SELECT c.*, p.name, p.price, p.status_id,
                         GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ',') as available_days
                     FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                         LEFT JOIN product_day pd ON p.id = pd.product_id
                         WHERE c.id IN ($placeholders) AND p.status_id IN (1, 2, 3)
                         GROUP BY c.id";
        }
        
        $cart_stmt = $conn->prepare($cart_sql);
        
        if ($cart_stmt) {
            $types = str_repeat('i', count($selected_cart_ids));
            $cart_stmt->bind_param($types, ...$selected_cart_ids);
            
            if ($cart_stmt->execute()) {
                $cart_result = $cart_stmt->get_result();
                
                while ($item = $cart_result->fetch_assoc()) {
                    // Validate stock availability before adding to cart_items
                    $stock_check_sql = "SELECT quantity FROM products WHERE id = ?";
                    $stock_check_stmt = $conn->prepare($stock_check_sql);
                    $stock_check_stmt->bind_param("i", $item['product_id']);
                    $stock_check_stmt->execute();
                    $stock_result = $stock_check_stmt->get_result();
                    
                    if ($stock_row = $stock_result->fetch_assoc()) {
                        $available_stock = $stock_row['quantity'];
                        
                        // Check if cart quantity exceeds available stock
                        if ($item['quantity'] > $available_stock) {
                            $_SESSION['error_message'] = "Insufficient stock for " . $item['name'] . ". Available: " . $available_stock . ", Requested: " . $item['quantity'];
                            error_log("Stock validation failed for product " . $item['product_id'] . ": Available=" . $available_stock . ", Requested=" . $item['quantity']);
                            echo '<script>alert("' . $_SESSION['error_message'] . '"); window.location.href = "cart.php";</script>';
                            exit();
                        }
                    }
                    $stock_check_stmt->close();
                    
                    $cart_total += $item['price'] * $item['quantity'];
                    $cart_items[] = [
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'cart_id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'status_id' => $item['status_id'],
                        'available_days' => $item['available_days']
                    ];
                }
            }
            $cart_stmt->close();
        }
        

        
    } catch (Exception $e) {
        error_log("Error fetching cart items: " . $e->getMessage());
    }
} else {
    error_log("No selected_cart_ids to process");
}

// Store cart items in session
$_SESSION['cart_items'] = $cart_items;
$_SESSION['cart_total'] = $cart_total;

// Check if we have any valid cart items after filtering
if (empty($cart_items)) {
    error_log("No valid cart items found after filtering for status_id 1,2 - redirecting to cart");
    $_SESSION['error_message'] = "No valid cart items found. Please check your cart and try again.";
    echo '<script>window.location.href = "cart.php";</script>';
    exit();
}

// Determine shipping method based on product status
// Status ID 1 = Pick Up Only (fixed)
// Status ID 2 = Delivery Only (fixed)
// Status ID 3 = Flexible (Delivery or Pick-Up)
$shipping_method = 'pickup'; // Default
$has_pickup_only = false;      // status_id = 1
$has_delivery_only = false;    // status_id = 2
$has_flexible = false;         // status_id = 3
$can_change_shipping = false;  // Whether user can change shipping method

if (!empty($cart_items)) {
    // Check what types of products are in the cart
    foreach ($cart_items as $item) {
        if ($item['status_id'] == 1) {
            $has_pickup_only = true;
        } elseif ($item['status_id'] == 2) {
            $has_delivery_only = true;
        } elseif ($item['status_id'] == 3) {
            $has_flexible = true;
        }
    }
    
    // Apply shipping rules
    if ($has_pickup_only && $has_delivery_only) {
        // ERROR: Cannot mix Pick Up Only (1) and Delivery Only (2)
        error_log("ERROR: Cart contains both Pick Up Only and Delivery Only items - this should have been prevented");
        $_SESSION['error_message'] = "You cannot mix Pick Up Only and Delivery Only products in the same order. Please checkout separately.";
        echo '<script>alert("You cannot mix Pick Up Only and Delivery Only products!"); window.location.href = "cart.php";</script>';
        exit();
    } elseif ($has_pickup_only) {
        // Has Pick Up Only (with or without Flexible)
        // Force Pick Up - status_id 1 takes precedence
        $shipping_method = 'pickup';
        $can_change_shipping = false;
        error_log("Cart has Pick Up Only items - forcing pickup (flexible items inherit pickup)");
    } elseif ($has_delivery_only) {
        // Has Delivery Only (with or without Flexible)
        // Force Delivery - status_id 2 takes precedence
        $shipping_method = 'delivery';
        $can_change_shipping = false;
        error_log("Cart has Delivery Only items - forcing delivery (flexible items inherit delivery)");
    } elseif ($has_flexible) {
        // Only Flexible items (status_id 3)
        // User can choose shipping method
        $shipping_method = 'pickup'; // Default to pickup
        $can_change_shipping = true;
        error_log("Cart has only Flexible items - user can choose shipping method");
    } else {
        // No items (shouldn't happen, but handle it)
        $shipping_method = 'pickup';
        $can_change_shipping = false;
        error_log("No items with valid status_id - defaulting to pickup");
    }
    
    error_log("Cart analysis: " . count($cart_items) . " items | Pickup Only: " . ($has_pickup_only ? 'YES' : 'NO') . " | Delivery Only: " . ($has_delivery_only ? 'YES' : 'NO') . " | Flexible: " . ($has_flexible ? 'YES' : 'NO') . " | Method: $shipping_method | Can Change: " . ($can_change_shipping ? 'YES' : 'NO'));
} else {
    error_log("No cart items to determine shipping method - keeping default (pickup)");
}

// Store shipping method and product type info in session
$_SESSION['shipping_method'] = $shipping_method;
$_SESSION['has_pickup_only'] = $has_pickup_only;
$_SESSION['has_delivery_only'] = $has_delivery_only;
$_SESSION['has_flexible'] = $has_flexible;
$_SESSION['can_change_shipping'] = $can_change_shipping;

// Debug output
error_log("Cart items: " . print_r($cart_items, true));
error_log("Cart total: " . $cart_total);

// Add debug information to be shown in console
$debug_info = [
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'user_data' => $user,
    'session_data' => $_SESSION['session_data'] ?? [],
    'cart_items' => $cart_items,
    'selected_cart_ids' => $selected_cart_ids
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout Page</title>

  <link rel="stylesheet" href="checkout.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  
  <style>
    /* Custom Calendar Styles */
    .custom-calendar {
      font-family: Arial, sans-serif;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }
    
    .calendar-header {
      background: #256035;
      color: white;
      padding: 12px;
      border-radius: 8px 8px 0 0;
      text-align: center;
      position: relative;
    }
    
    .calendar-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255,255,255,0.2);
      border: none;
      color: white;
      padding: 3px 10px;
      border-radius: 999px;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.3s;
    }
    
    .calendar-nav:hover {
      background: rgba(255,255,255,0.3);
    }
    
    .calendar-nav.prev {
      left: 15px;
    }
    
    .calendar-nav.next {
      right: 15px;
    }
    
    .calendar-title {
      font-size: 18px;
      font-weight: bold;
      margin: 0;
    }
    
    .calendar-weekdays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      background: #f8f9fa;
      border-bottom: 1px solid #ddd;
    }
    
    .weekday {
      padding: 10px 8px;
      text-align: center;
      font-weight: bold;
      color: #666;
      font-size: 14px;
      border-right: 1px solid #ddd;
    }
    
    .weekday:last-child {
      border-right: none;
    }
    
    .calendar-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
    }
    
    .calendar-day {
      padding: 12px 8px;
      text-align: center;
      cursor: pointer;
      border-right: 1px solid #ddd;
      border-bottom: 1px solid #ddd;
      transition: all 0.3s;
      min-height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    
    .calendar-day:nth-child(7n) {
      border-right: none;
    }
    
    .calendar-day:hover:not(.disabled):not(.not-accepting) {
      background: #e3f2fd;
    }
    
    .calendar-day.orders-count {
      font-size: 10px;
      position: absolute;
      bottom: 2px;
      right: 2px;
      background: rgba(0,0,0,0.1);
      padding: 1px 3px;
      border-radius: 2px;
    }
    
    .calendar-day.other-month {
      color: #ccc;
    }
    
    .calendar-day.today {
      background: #e8f5e9;
      color: #2e7d32;
      font-weight: bold;
    }
    
    .calendar-day.available {
      background: #e8f5e9;
      color: #2e7d32;
    }
    
    .calendar-day.available:hover {
      background: #c8e6c9;
    }
    
    .calendar-day.selected {
      background: #256035 !important;
      color: white !important;
    }
    
    .calendar-day.disabled {
      background: #f5f5f5;
      color: #ccc;
      cursor: not-allowed;
    }
    
    .calendar-day.not-accepting {
      background: #ffebee;
      color: #c62828;
      cursor: not-allowed;
    }
    
    .calendar-day.not-accepting::after {
      content: '✕';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 18px;
      font-weight: bold;
    }
    
    .calendar-day.unavailable-day {
      background: #f8f9fa;
      color: #ccc;
      cursor: not-allowed;
      position: relative;
    }
    
    .calendar-day.unavailable-day::after {
      content: '🚫';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 14px;
      opacity: 0.7;
    }
    
    .calendar-day.unavailable-day:hover {
      background: #f8f9fa;
      cursor: not-allowed;
    }
    
    /* Layout Fixes for Calendar and DateTime Inputs */
    .delivery-content {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
        .calendar-section {
      width: 100%;
      max-width: 400px;
      margin-bottom: 20px;
    }
    
    /* Ensure calendar is visible for both pickup and delivery */
    #pickup-details .calendar-section,
    #delivery-details .calendar-section {
      margin-bottom: 20px;
    }
    
    /* Calendar section styling when moved to delivery */
    .delivery-content .calendar-section {
      margin-top: 20px;
      margin-bottom: 20px;
    }
    

    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #333;
    }
    
    .form-group input {

      width: 100%;
      padding: 9px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
    }
    
    .form-group input:focus {
      outline: none;
      border-color: #256035;
      box-shadow: 0 0 0 2px rgba(37, 96, 53, 0.1);
    }
    
    .time-note {
      display: block;
      margin-top: 5px;
      font-size: 12px;
      color: #666;
      font-style: italic;
    }
    
    .address-section {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
      align-items: stretch;
    }
    
    .address-section input {
      flex: 2;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      margin-bottom: 0;
      height: auto;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      flex: 0 0 auto;
      min-width: 250px;
      font-size: 14px;
      font-weight: 500;
      white-space: nowrap;
      font-size: 14px;
    }
    
    .btn-secondary:hover {
      background: #5a6268;
    }
    
    /* Calendar Error State */
    .calendar-error {
      text-align: center;
      padding: 20px;
      color: #721c24;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      border-radius: 4px;
      margin: 10px 0;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .delivery-content {
        gap: 15px;
      }
      
      .calendar-section {
        max-width: 100%;
      }
      
      .datetime-inputs {
        max-width: 100%;
      }
      
      .custom-calendar {
        font-size: 14px;
      }
      
      .calendar-day {
        padding: 8px 6px;
        min-height: 40px;
      }
      
      .weekday {
        padding: 10px 6px;
        font-size: 12px;
      }
      
      .calendar-title {
        font-size: 16px;
      }
      
      .calendar-nav {
        padding: 6px 10px;
        font-size: 14px;
      }
    }
    
    /* Disabled Radio Button Styles */
    .radio-option input[type="radio"]:disabled + span {
      color: #999;
      cursor: not-allowed;
    }
    
    .radio-option input[type="radio"]:disabled {
      cursor: not-allowed;
    }
    
    /* Shipping Method Notice in Order Summary */
    .shipping-method-notice {
      background: #e8f5e9;
      border: 1px solid #c8e6c9;
      border-radius: 6px;
      padding: 12px 16px;
      text-align: center;
      margin: 15px 0;
      color: #2e7d32;
    }
    
    .shipping-method-notice p {
      margin: 0;
      font-size: 14px;
      line-height: 1.4;
    }
    
    .shipping-method-notice strong {
        color: #1b5e20;
    }

    /* Location Modal Styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 0;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    .modal-header {
        background-color: #256035;
        color: white;
        padding: 15px 20px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.2rem;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    .close-btn:hover {
        opacity: 0.7;
    }

    .modal-body {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #256035;
        box-shadow: 0 0 0 2px rgba(37, 96, 53, 0.2);
    }

    optgroup {
        font-weight: bold;
        color: #256035;
    }

    option {
        font-weight: normal;
        color: #333;
        padding: 5px;
    }

    .form-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
    }

    #saveLocationBtn {
        background-color: #256035;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        width: 100%;
    }

    #saveLocationBtn:hover {
        background-color: #1e4a2a;
    }

    /* Place Order Button Disabled State */
    .btn-primary:disabled,
    .place-order-btn:disabled {
        background-color: #6c757d !important;
        cursor: not-allowed !important;
        opacity: 0.7 !important;
        transform: none !important;
        box-shadow: none !important;
        border-color: #6c757d !important;
    }

    /* Loading Spinner Styles */
    .place-order-btn .spinner {
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #ffffff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        display: inline-block;
        margin-right: 8px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Button Processing State */
    .btn-processing {
        opacity: 0.7 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }
    
    /* Ensure no duplicate circles */
    .place-order-btn::after,
    .place-order-btn::before {
        display: none !important;
    }

    /* Full Page Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .loading-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .loading-content {
        background: white;
        padding: 40px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 90%;
    }

    .loading-spinner-large {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #256035;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    .loading-text {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .loading-subtext {
        font-size: 14px;
        color: #666;
        margin: 0;
    }

    .btn-primary:disabled:hover,
    .place-order-btn:disabled:hover {
        background-color: #6c757d !important;
        transform: none !important;
        box-shadow: none !important;
        border-color: #6c757d !important;
    }

    /* Loading Spinner */
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s ease-in-out infinite;
        margin-right: 8px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .btn-processing {
        position: relative;
        pointer-events: none;
    }
    
    /* Shipping Options Improvements */
    .shipping-details {
      margin-bottom: 30px;
    }

    /* Coupon Section Styles */
    .coupon-section {
      margin-top: 15px;
      padding: 10px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #e9ecef;
    }
    
    .coupon-input-group {
      display: flex;
      gap: 10px;
    }
    
    .coupon-input {
      flex: 1;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }
    
    .coupon-input:focus {
      outline: none;
      border-color: #256035;
      box-shadow: 0 0 0 2px rgba(37, 96, 53, 0.1);
    }
    
    .btn-apply-coupon {
      padding: 10px 2rem;
      background: #256035;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s ease;
      white-space: nowrap;
    }
    
    .btn-apply-coupon:hover {
      background: #1a4a28;
    }
    
    .btn-apply-coupon:disabled {
      background: #6c757d;
      cursor: not-allowed;
    }
    
    .coupon-message {
      font-size: 14px;
      margin-top: 8px;
      padding: 8px 12px;
      border-radius: 4px;
      display: none;
    }
    
    .coupon-message.success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      display: block;
    }
    
    .coupon-message.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      display: block;
    }
    
    .coupon-applied {
      background: #e8f5e9;
      border: 1px solid #c8e6c9;
      border-radius: 4px;
      padding: 12px;
      margin-top: 10px;
    }
    
    .applied-coupon-info {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    
    .coupon-code-display {
      font-weight: 600;
      color: #2e7d32;
      font-size: 14px;
    }
    
    .coupon-discount {
      font-weight: 600;
      color: #2e7d32;
      font-size: 14px;
    }
    
    .btn-remove-coupon {
      padding: 6px 12px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    .btn-remove-coupon:hover {
      background: #c82333;
    }
    
    /* Responsive Design for Coupon Section */
    @media (max-width: 768px) {
      .coupon-input-group {
        flex-direction: column;
      }
      
      .btn-apply-coupon {
        width: 100%;
      }
      
      .applied-coupon-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
      
      .btn-remove-coupon {
        align-self: flex-end;
      }
    }
  </style>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Declare calendar variables in the outer scope
        let pickupCalendar;
        const pickupCalendarEl = document.getElementById('calendar');
        const pickupRadio = document.getElementById('pickup');
        const deliveryRadio = document.getElementById('delivery');
        const pickupDetails = document.getElementById('pickup-details');
        const deliveryDetails = document.getElementById('delivery-details');
        const addressInput = document.getElementById('customer_address');
        const shippingFeeDisplay = document.getElementById('shipping_fee');
        const totalAmountDisplay = document.getElementById('total_amount');
        const subtotal = <?= json_encode($cart_total) ?>;
        const shippingMethod = <?= json_encode($shipping_method) ?>;

        // Global variables
        let dateLimits = {};
        let cartItems = <?= json_encode($cart_items) ?>;
        let combinedAvailableDays = [];
        
        // Coupon variables
        let appliedCoupon = null;
        let discountAmount = 0;
        
        // Function to calculate combined available days from cart items
        function calculateCombinedAvailableDays() {
            const availableDaysSet = new Set();
            
            cartItems.forEach(item => {
                if (item.available_days && item.available_days !== 'null' && item.available_days.trim() !== '') {
                    // Parse available days string (e.g., "Monday, Tuesday, Wednesday")
                    const days = item.available_days.split(',').map(day => day.trim());
                    days.forEach(day => availableDaysSet.add(day));
                }
            });
            
            combinedAvailableDays = Array.from(availableDaysSet);
            return combinedAvailableDays;
        }
        
        // Function to update calendar with new available days
        function updateCalendarAvailableDays() {
            if (pickupCalendar) {
                const availableDays = calculateCombinedAvailableDays();
                pickupCalendar.availableDays = availableDays;
                pickupCalendar.render();
                console.log('Calendar updated with new available days:', availableDays);
            }
        }

        // Custom Calendar Class
        class CustomCalendar {
            constructor(container, options = {}) {
                this.container = container;
                this.options = {
                    onDateSelect: options.onDateSelect || (() => {}),
                    ...options
                };
                
                this.currentDate = new Date();
                this.selectedDate = null;
                this.dateLimits = {};
                this.availableDays = options.availableDays || [];
                
                this.init();
            }
            
            init() {
                try {
                    this.render();
                    this.attachEventListeners();
                    
                    // Fetch date limits for current month
                    this.fetchCurrentMonthLimits();
                } catch (error) {
                    console.error('Error initializing calendar:', error);
                }
            }
            
            fetchCurrentMonthLimits() {
                try {
                    const year = this.currentDate.getFullYear();
                    const month = this.currentDate.getMonth();
                    
                    const startDate = new Date(year, month, 1);
                    const endDate = new Date(year, month + 1, 0);
                    
                    // Call the global fetchDateLimits function
                    if (typeof fetchDateLimits === 'function') {
                        fetchDateLimits(startDate, endDate);
                    }
                } catch (error) {
                    console.error('Error fetching current month limits:', error);
                }
            }
            
            render() {
                try {
                    const year = this.currentDate.getFullYear();
                    const month = this.currentDate.getMonth();
                    
                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    const startDate = new Date(firstDay);
                    startDate.setDate(startDate.getDate() - firstDay.getDay());
                    
                    const monthNames = [
                        'January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'
                    ];
                    
                    this.container.innerHTML = `
                        <div class="custom-calendar">
                            <div class="calendar-header">
                                <button class="calendar-nav prev" data-action="prev">&lt;</button>
                                <h3 class="calendar-title">${monthNames[month]} ${year}</h3>
                                <button class="calendar-nav next" data-action="next">&gt;</button>
                            </div>
                            <div class="calendar-weekdays">
                                <div class="weekday">Sun</div>
                                <div class="weekday">Mon</div>
                                <div class="weekday">Tue</div>
                                <div class="weekday">Wed</div>
                                <div class="weekday">Thu</div>
                                <div class="weekday">Fri</div>
                                <div class="weekday">Sat</div>
                            </div>
                            <div class="calendar-days">
                                ${this.generateDaysHTML(startDate, lastDay)}
                            </div>
                        </div>
                    `;
                } catch (error) {
                    console.error('Error rendering calendar:', error);
                    this.container.innerHTML = '<div class="calendar-error">Error loading calendar</div>';
                }
            }
            
            generateDaysHTML(startDate, lastDay) {
                try {
                    let html = '';
            const today = new Date();
            today.setHours(0, 0, 0, 0);

                    let currentDate = new Date(startDate);
                    let dayCount = 0;
                    const maxDays = 42; // 6 weeks * 7 days
                    
                    while ((currentDate <= lastDay || currentDate.getDay() !== 0) && dayCount < maxDays) {
                        // Use local timezone instead of UTC to avoid date offset issues
                        const dateStr = currentDate.getFullYear() + '-' + 
                                      String(currentDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                      String(currentDate.getDate()).padStart(2, '0');
                        const isCurrentMonth = currentDate.getMonth() === this.currentDate.getMonth();
                        const isToday = currentDate.getTime() === today.getTime();
                        const isPast = currentDate < today;
                        const isSelected = this.selectedDate && this.selectedDate.toDateString() === currentDate.toDateString();
                        
                        // Check if this date falls on an available day
                        const dayName = currentDate.toLocaleDateString('en-US', { weekday: 'long' });
                        const isAvailableDay = this.availableDays.length === 0 || this.availableDays.includes(dayName);
                        
                        let dayClass = 'calendar-day';
                        if (!isCurrentMonth) dayClass += ' other-month';
                        if (isToday) dayClass += ' today';
                        if (isPast) dayClass += ' disabled';
                        if (isSelected) dayClass += ' selected';
                        
                        // Check date limits
                        if (this.dateLimits[dateStr]) {
                            const dateInfo = this.dateLimits[dateStr];
                            if (dateInfo.limit === 0 || dateInfo.status === 'not_accepting' || dateInfo.is_full) {
                                dayClass += ' not-accepting';
                            } else if (dateInfo.count > 0) {
                                dayClass += ' available';
                            }
                        }
                        
                        // Add restriction for unavailable days
                        if (!isAvailableDay) {
                            dayClass += ' unavailable-day';
                        }
                        
                        const dayNumber = currentDate.getDate();
                        let ordersCount = '';
                        
                        if (this.dateLimits[dateStr] && this.dateLimits[dateStr].count > 0) {
                            ordersCount = `<span class="orders-count">${this.dateLimits[dateStr].count}</span>`;
                        }
                        
                        html += `
                            <div class="${dayClass}" data-date="${dateStr}" ${isPast ? 'data-disabled="true"' : ''} ${!isAvailableDay ? 'data-unavailable="true"' : ''}>
                                ${dayNumber}${ordersCount}
                            </div>
                        `;
                        
                        currentDate.setDate(currentDate.getDate() + 1);
                        dayCount++;
                    }
                    
                    return html;
                } catch (error) {
                    console.error('Error generating days HTML:', error);
                    return '<div class="calendar-error">Error generating calendar</div>';
                }
            }
            
            attachEventListeners() {
                try {
                    this.container.addEventListener('click', (e) => {
                        if (e.target.classList.contains('calendar-nav')) {
                            const action = e.target.dataset.action;
                            if (action === 'prev') {
                                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                            } else if (action === 'next') {
                                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                            }
                            this.render();
                            
                            // Fetch date limits for the new month
                            this.fetchCurrentMonthLimits();
                        }
                        
                        if (e.target.classList.contains('calendar-day') && !e.target.classList.contains('disabled') && !e.target.classList.contains('not-accepting') && !e.target.classList.contains('unavailable-day')) {
                            const dateStr = e.target.dataset.date;
                            if (dateStr) {
                                this.selectDate(dateStr);
                                this.options.onDateSelect(dateStr);
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error attaching event listeners:', error);
                }
            }
            
            selectDate(dateStr) {
                try {
                    if (!dateStr) {
                        console.warn('No date string provided for selection');
                return;
            }
            
                    // Remove previous selection
                    const prevSelected = this.container.querySelector('.calendar-day.selected');
                    if (prevSelected) {
                        prevSelected.classList.remove('selected');
                    }
                    
                    // Add selection to new date
                    const newSelected = this.container.querySelector(`[data-date="${dateStr}"]`);
                    if (newSelected) {
                        newSelected.classList.add('selected');
                        this.selectedDate = new Date(dateStr);
                        console.log('Date selected:', dateStr);
                    } else {
                        console.warn('Selected date element not found:', dateStr);
                    }
                } catch (error) {
                    console.error('Error selecting date:', error);
                }
            }
            
            updateDateLimits(dateLimits) {
                try {
                    if (dateLimits && typeof dateLimits === 'object') {
                        this.dateLimits = dateLimits;
                        this.render();
                        console.log('Calendar date limits updated');
                    } else {
                        console.warn('Invalid date limits provided:', dateLimits);
                    }
                } catch (error) {
                    console.error('Error updating date limits:', error);
                }
            }
            
            getSelectedDate() {
                return this.selectedDate;
            }
        }



        function initializeCalendars() {
            try {
                // Calculate combined available days from cart items
                const availableDays = calculateCombinedAvailableDays();
                
                if (pickupCalendarEl) {
                    pickupCalendar = new CustomCalendar(pickupCalendarEl, {
                        onDateSelect: (dateStr) => {
                            // Handle date selection for both pickup and delivery
                            handleDateSelect(dateStr, 'both');
                        },
                        availableDays: availableDays
                    });
                } else {
                    console.warn('Calendar element not found');
                }
            } catch (error) {
                console.error('Error initializing calendars:', error);
            }
        }

        function handleDateSelect(dateStr, type) {
            // Update both pickup and delivery date inputs when a date is selected
            const pickupDateInput = document.getElementById('pickup_date');
            const deliveryDateInput = document.getElementById('delivery_date');
            
            if (pickupDateInput) {
                pickupDateInput.value = dateStr;
            }
            if (deliveryDateInput) {
                deliveryDateInput.value = dateStr;
            }
            
            console.log(`Date ${dateStr} selected for ${type}`);
        }

        function setDefaultDateIfEmpty() {
            const pickupDateInput = document.getElementById('pickup_date');
            const deliveryDateInput = document.getElementById('delivery_date');
            const pickupTimeInput = document.getElementById('pickup_time');
            const deliveryTimeInput = document.getElementById('delivery_time');
            
            // Get the first available date
            const firstAvailableDate = getFirstAvailableDate();
            
            if (pickupDateInput && !pickupDateInput.value) {
                pickupDateInput.value = firstAvailableDate;
                console.log('[CHECKOUT] Set default pickup date:', firstAvailableDate);
            }
            
            if (deliveryDateInput && !deliveryDateInput.value) {
                deliveryDateInput.value = firstAvailableDate;
                console.log('[CHECKOUT] Set default delivery date:', firstAvailableDate);
            }
            
            // Set default times
            if (pickupTimeInput && !pickupTimeInput.value) {
                pickupTimeInput.value = '09:00'; // Default to 9:00 AM
                console.log('[CHECKOUT] Set default pickup time: 09:00');
            }
            
            if (deliveryTimeInput && !deliveryTimeInput.value) {
                deliveryTimeInput.value = '09:00'; // Default to 9:00 AM
                console.log('[CHECKOUT] Set default delivery time: 09:00');
            }
        }

        function getFirstAvailableDate() {
            const availableDays = calculateCombinedAvailableDays();
            console.log('[CHECKOUT] Available days for products:', availableDays);
            
            // If no specific days are available, use today
            if (availableDays.length === 0) {
                const today = new Date();
                const todayStr = today.getFullYear() + '-' + 
                               String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(today.getDate()).padStart(2, '0');
                console.log('[CHECKOUT] No specific days available, using today:', todayStr);
                return todayStr;
            }
            
            // Find the first available date starting from today
            const today = new Date();
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            
            // Check the next 14 days to find the first available day
            for (let i = 0; i < 14; i++) {
                const checkDate = new Date(today);
                checkDate.setDate(today.getDate() + i);
                const dayName = dayNames[checkDate.getDay()];
                
                if (availableDays.includes(dayName)) {
                    const dateStr = checkDate.getFullYear() + '-' + 
                                   String(checkDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(checkDate.getDate()).padStart(2, '0');
                    console.log('[CHECKOUT] First available date found:', dateStr, '(' + dayName + ')');
                    return dateStr;
                }
            }
            
            // Fallback to today if no available day found in next 14 days
            const todayStr = today.getFullYear() + '-' + 
                           String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(today.getDate()).padStart(2, '0');
            console.log('[CHECKOUT] No available day found in next 14 days, using today:', todayStr);
            return todayStr;
        }

        function fetchDateLimits(start, end) {
            // Use local timezone instead of UTC to avoid date offset issues
            const startStr = start.getFullYear() + '-' + 
                            String(start.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(start.getDate()).padStart(2, '0');
            const endStr = end.getFullYear() + '-' + 
                         String(end.getMonth() + 1).padStart(2, '0') + '-' + 
                         String(end.getDate()).padStart(2, '0');
            
            console.log('Fetching date limits for:', startStr, 'to', endStr);
            
            fetch("../../../backend/pages/homepage/get-date-limits.php?start=${startStr}&end=${endStr}", {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const jsonStr = text.replace(/<!--[\s\S]*?-->/g, '').trim();
                        const data = JSON.parse(jsonStr);
                        
                        if (data.success && data.dates) {
                            dateLimits = {};
                            
                            data.dates.forEach(date => {
                                dateLimits[date.date] = {
                                    limit: parseInt(date.limit) || 0,
                                    count: parseInt(date.current_orders) || 0,
                                    is_full: date.is_full || parseInt(date.current_orders) >= parseInt(date.limit),
                                    active_orders: parseInt(date.active_orders) || 0,
                                    remaining_slots: parseInt(date.limit) - (parseInt(date.current_orders) || 0),
                                    status: date.status || (parseInt(date.limit) === 0 ? 'not_accepting' : 'accepting')
                                };
                            });

                            console.log('Date limits loaded:', dateLimits);

                            // Update the calendar with new date limits
                            if (pickupCalendar) {
                                pickupCalendar.updateDateLimits(dateLimits);
                            }
                            
                            // Ensure the calendar is properly rendered
                            if (pickupCalendar) {
                                pickupCalendar.render();
                            }
                        } else {
                            console.warn('No date limits data received or invalid format');
                        }
                    } catch (e) {
                        console.error('Error parsing date limits response:', e);
                    }
                })
                .catch(error => {
                    console.error('Error fetching date limits:', error);
                });
        }

        function updateVisibility() {
            const isPickup = pickupRadio.checked;
            
            if (pickupDetails) {
                pickupDetails.style.display = isPickup ? 'block' : 'none';
            }
            if (deliveryDetails) {
                deliveryDetails.style.display = isPickup ? 'none' : 'block';
            }
            
            try {
                // Use the same calendar for both pickup and delivery
                if (pickupCalendar && pickupCalendarEl) {
                    // Ensure calendar is properly rendered
                    if (!pickupCalendar.container.innerHTML.trim()) {
                    pickupCalendar.render();
                    }
                    
                    // Show calendar for both pickup and delivery
                    pickupCalendarEl.style.display = 'block';
                    
                    // Move calendar to the appropriate section
                    if (isPickup) {
                        // Calendar stays in pickup details
                        pickupDetails.appendChild(pickupCalendarEl.parentNode);
                    } else {
                        // Move calendar to delivery details (after address section)
                        const addressSection = deliveryDetails.querySelector('.address-section');
                        if (addressSection && pickupCalendarEl.parentNode) {
                            deliveryDetails.insertBefore(pickupCalendarEl.parentNode, addressSection.nextSibling);
                        }
                    }
                }
            } catch (error) {
                console.error('Error rendering calendar:', error);
            }
            
            const shippingFee = isPickup ? 0 : 50;
            if (shippingFeeDisplay) {
                shippingFeeDisplay.textContent = '₱' + shippingFee.toFixed(2);
            }
            
            updateTotalAmount(shippingFee);
            
            if (addressInput) {
                addressInput.required = !isPickup;
                if (isPickup) {
                    addressInput.value = "Pickup at store";
                } else if (addressInput.value === "Pickup at store") {
                    addressInput.value = "";
                }
            }
        }
        
        // Coupon Functions
        function updateTotalAmount(shippingFee = 0) {
            const totalElement = document.getElementById('total');
            if (totalElement) {
                const total = subtotal + shippingFee - discountAmount;
                totalElement.textContent = '₱' + total.toFixed(2);
            }
        }
        
        function showCouponMessage(message, isSuccess = false) {
            const messageElement = document.getElementById('coupon_message');
            if (messageElement) {
                messageElement.textContent = message;
                messageElement.className = 'coupon-message ' + (isSuccess ? 'success' : 'error');
                messageElement.style.display = 'block';
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    messageElement.style.display = 'none';
                }, 5000);
            }
        }
        
        function showAppliedCoupon(coupon) {
            const appliedElement = document.getElementById('coupon_applied');
            const codeDisplay = appliedElement.querySelector('.coupon-code-display');
            const discountDisplay = appliedElement.querySelector('.coupon-discount');
            
            if (codeDisplay) {
                codeDisplay.textContent = `Coupon: ${coupon.code}`;
            }
            
            if (discountDisplay) {
                let discountText = '';
                if (coupon.type === 'percentage') {
                    discountText = `-${coupon.value}% (₱${discountAmount.toFixed(2)})`;
                } else if (coupon.type === 'fixed') {
                    discountText = `-₱${discountAmount.toFixed(2)}`;
                } else if (coupon.type === 'free_shipping') {
                    discountText = 'Free Shipping';
                }
                discountDisplay.textContent = discountText;
            }
            
            appliedElement.style.display = 'block';
        }
        
        function hideAppliedCoupon() {
            const appliedElement = document.getElementById('coupon_applied');
            if (appliedElement) {
                appliedElement.style.display = 'none';
            }
        }
        
        function calculateDiscount(coupon, subtotalAmount) {
            let discount = 0;
            
            if (coupon.type === 'percentage') {
                discount = (subtotalAmount * coupon.value) / 100;
            } else if (coupon.type === 'fixed') {
                discount = coupon.value;
            } else if (coupon.type === 'free_shipping') {
                // Free shipping discount will be applied to shipping fee
                discount = 0;
            }
            
            // Ensure discount doesn't exceed subtotal
            return Math.min(discount, subtotalAmount);
        }
        
        async function applyCoupon() {
            const couponInput = document.getElementById('coupon_code');
            const applyBtn = document.getElementById('apply_coupon_btn');
            const couponCode = couponInput.value.trim().toUpperCase();
            
            if (!couponCode) {
                showCouponMessage('Please enter a coupon code');
                return;
            }
            
            // Disable button during request
            applyBtn.disabled = true;
            applyBtn.textContent = 'Applying...';
            
            try {
                const response = await fetch('../../../backend/pages/user-page-content/validate-coupon.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        coupon_code: couponCode,
                        subtotal: subtotal,
                        cart_items: cartItems
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    appliedCoupon = result.coupon;
                    discountAmount = calculateDiscount(appliedCoupon, subtotal);
                    
                    // Show applied coupon
                    showAppliedCoupon(appliedCoupon);
                    showCouponMessage(result.message, true);
                    
                    // Update totals
                    updateTotalAmount(pickupRadio.checked ? 0 : 50);
                    
                    // Show discount row
                    const discountRow = document.getElementById('discount-row');
                    const discountAmountElement = document.getElementById('discount_amount');
                    if (discountRow && discountAmountElement) {
                        discountRow.style.display = 'flex';
                        discountAmountElement.textContent = `-₱${discountAmount.toFixed(2)}`;
                    }
                    
                    // Clear input
                    couponInput.value = '';
                } else {
                    showCouponMessage(result.message || 'Invalid coupon code');
                }
            } catch (error) {
                console.error('Error applying coupon:', error);
                showCouponMessage('Error applying coupon. Please try again.');
            } finally {
                applyBtn.disabled = false;
                applyBtn.textContent = 'Apply';
            }
        }
        
        function removeCoupon() {
            appliedCoupon = null;
            discountAmount = 0;
            
            // Hide applied coupon
            hideAppliedCoupon();
            
            // Hide discount row
            const discountRow = document.getElementById('discount-row');
            if (discountRow) {
                discountRow.style.display = 'none';
            }
            
            // Update totals
            updateTotalAmount(pickupRadio.checked ? 0 : 50);
            
            showCouponMessage('Coupon removed successfully', true);
        }

        function initializeTimeInputs() {
            const timeInputs = document.querySelectorAll('input[type="time"]');
            timeInputs.forEach(input => {
                input.value = '06:00';
                
                input.addEventListener('change', function() {
                    const time = this.value;
                    const [hours, minutes] = time.split(':').map(Number);
                    const totalMinutes = hours * 60 + minutes;
                    
                    if (totalMinutes < 360 || totalMinutes > 1080) {
                        alert('Please select a time between 6:00 AM and 6:00 PM');
                        this.value = '06:00';
                        return;
                    }
                    
                    const roundedMinutes = Math.round(minutes / 30) * 30;
                    const roundedHours = hours + Math.floor(roundedMinutes / 60);
                    const finalMinutes = roundedMinutes % 60;
                    
                    this.value = `${roundedHours.toString().padStart(2, '0')}:${finalMinutes.toString().padStart(2, '0')}`;
                });
            });
        }

        if (pickupRadio) {
            pickupRadio.addEventListener('change', function() {
                updateVisibility();
                updateShippingInheritance(); // Update flexible item indicators
            });
        }
        if (deliveryRadio) {
            deliveryRadio.addEventListener('change', function() {
                updateVisibility();
                updateShippingInheritance(); // Update flexible item indicators
                
                // Ensure pickup calendar is properly initialized for delivery
                if (this.checked && pickupCalendar && pickupCalendarEl) {
                    setTimeout(() => {
                        if (!pickupCalendar.container.innerHTML.trim()) {
                            pickupCalendar.render();
                        }
                        // Force refresh of date limits
                        pickupCalendar.fetchCurrentMonthLimits();
                        
                        // Ensure calendar is visible in delivery section
                        if (pickupCalendarEl.parentNode) {
                            pickupCalendarEl.parentNode.style.display = 'block';
                        }
                    }, 200);
                }
            });
        }

        try {
            // Debug logging
            console.log('Shipping Method:', shippingMethod);
            console.log('Cart Items:', <?= json_encode($cart_items) ?>);
            console.log('Product Status IDs:', <?= json_encode(array_column($cart_items, 'status_id')) ?>);
            console.log('Available Days:', <?= json_encode(array_column($cart_items, 'available_days')) ?>);
            
            initializeCalendars();
            initializeTimeInputs();
            updateVisibility();
            updateShippingInheritance(); // Initialize flexible item indicators
            updateShippingInheritance();
            
            // Set default date to today if no date is selected
            setDefaultDateIfEmpty();
            
            // Initialize coupon event listeners
            const applyCouponBtn = document.getElementById('apply_coupon_btn');
            const removeCouponBtn = document.getElementById('remove_coupon_btn');
            const couponInput = document.getElementById('coupon_code');
            
            if (applyCouponBtn) {
                applyCouponBtn.addEventListener('click', applyCoupon);
            }
            
            if (removeCouponBtn) {
                removeCouponBtn.addEventListener('click', removeCoupon);
            }
            
            if (couponInput) {
                couponInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyCoupon();
                    }
                });
            }
            
            // Fetch initial date limits for current month
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            fetchDateLimits(start, end);
            
            // Update calendar with available days
            updateCalendarAvailableDays();
        } catch (error) {
            // Error during initialization
        }

        // Update shipping method inheritance indicators for Status 3 (flexible) products
        function updateShippingInheritance() {
            const cartItems = <?= json_encode($cart_items) ?>;
            const hasPickupOnly = <?= json_encode($has_pickup_only) ?>;
            const hasDeliveryOnly = <?= json_encode($has_delivery_only) ?>;
            const canChangeShipping = <?= json_encode($can_change_shipping) ?>;
            
            // Determine inherited method for flexible items
            let inheritedMethod = null;
            if (hasPickupOnly) {
                // Status 1 takes precedence - flexible items inherit pickup
                inheritedMethod = 'pickup';
            } else if (hasDeliveryOnly) {
                // Status 2 takes precedence - flexible items inherit delivery
                inheritedMethod = 'delivery';
            } else if (canChangeShipping) {
                // Only flexible items - check current selection
                const selectedMethod = document.querySelector('input[name="delivery_method"]:checked')?.value;
                inheritedMethod = selectedMethod || 'pickup';
            }
            
            // Update Status 3 product indicators in order summary
            document.querySelectorAll('.item[data-status-id="3"]').forEach(item => {
                let indicator = item.querySelector('.shipping-indicator');
                
                // Create indicator if it doesn't exist
                if (!indicator && inheritedMethod) {
                    indicator = document.createElement('span');
                    indicator.className = 'shipping-indicator';
                    indicator.style.cssText = 'display: inline-block; margin-left: 8px; padding: 2px 8px; background: #f0f0f0; border-radius: 3px; font-size: 11px; color: #666;';
                    const nameElement = item.querySelector('.item-name');
                    if (nameElement) {
                        nameElement.appendChild(indicator);
                    }
                }
                
                if (indicator && inheritedMethod) {
                    indicator.textContent = inheritedMethod === 'pickup' ? '→ Will be Pick Up' : '→ Will be Delivery';
                    indicator.style.display = 'inline-block';
                } else if (indicator) {
                    indicator.style.display = 'none';
                }
            });
            
            console.log('Shipping inheritance updated:', {
                hasPickupOnly,
                hasDeliveryOnly,
                canChangeShipping,
                inheritedMethod
            });
        }

        // Form submission handler with PayMongo integration
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Check HTML5 validity first
                if (!checkoutForm.checkValidity()) {
                    console.log('[CHECKOUT] Form validation failed, showing validation errors');
                    // Trigger native validation display
                    checkoutForm.reportValidity();
                    return;
                }
                
                console.log('[CHECKOUT] Form validation passed, proceeding with submission');
                
                // Prevent double submission
                if (orderProcessing) {
                    return;
                }
                orderProcessing = true;
                
                try {
                    console.log('[CHECKOUT] Starting payment process...');
                    // Show loading state
                    setLoadingState(true);
                    
                    // Get all form data
                    const formData = new FormData();
                    
                    // Add cart items and user info
                    const cartItems = <?php echo json_encode($cart_items); ?>;
                    const cartTotal = <?php echo json_encode($cart_total); ?>;
                    const userEmail = <?php echo json_encode($user['email'] ?? ''); ?>;
                    const userName = <?php echo json_encode(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))); ?>;
                    
                    // Validate cart items
                    if (!cartItems || !Array.isArray(cartItems) || cartItems.length === 0) {
                        throw new Error('Please ensure you have items in your cart before proceeding with checkout.');
                    }
                    
                    // Add cart information
                    formData.append('cart_items', JSON.stringify(cartItems));
                    formData.append('selected_cart_ids', cartItems.map(item => item.cart_id).join(','));
                    formData.append('cart_total', cartTotal);
                    formData.append('user_name', userName);
                    formData.append('user_email', userEmail);
                    
                    // Add delivery/pickup information
                    const isDelivery = document.getElementById('delivery').checked;
                    const today = new Date();
                    const todayStr = today.getFullYear() + '-' + 
                                   String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(today.getDate()).padStart(2, '0');
                    
                    formData.append('delivery_method', isDelivery ? 'delivery' : 'pickup');
                    formData.append('delivery_date', isDelivery ? (document.getElementById('delivery_date').value || todayStr) : todayStr);
                    formData.append('pickup_date', !isDelivery ? (document.getElementById('pickup_date').value || todayStr) : todayStr);
                    
                    // Add contact number
                    const contactNumber = document.getElementById('contact_number');
                    if (contactNumber && contactNumber.value) {
                        formData.append('contact_number', contactNumber.value);
                    } else {
                        throw new Error('Please enter your contact number');
                    }
                    
                    // Add delivery address if delivery is selected
                    if (isDelivery) {
                        const deliveryAddress = document.getElementById('delivery_address');
                        if (deliveryAddress && deliveryAddress.value) {
                            formData.append('delivery_address', deliveryAddress.value);
                        } else {
                            throw new Error('Please enter your delivery address');
                        }
                    }
                    
                    // Add delivery/pickup time
                    if (isDelivery && document.getElementById('delivery_time')) {
                        formData.append('delivery_time', document.getElementById('delivery_time').value);
                    }
                    if (!isDelivery && document.getElementById('pickup_time')) {
                        formData.append('pickup_time', document.getElementById('pickup_time').value);
                    }
                    
                    // Add payment method
                    const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
                    if (!paymentMethodEl) {
                        throw new Error('Please select a payment method.');
                    }
                    formData.append('payment_method', paymentMethodEl.value);
                    
                    // Add notes if any
                    const notesEl = document.getElementById('order_notes');
                    if (notesEl) {
                        formData.append('notes', notesEl.value);
                    }
                    
                    // Add coupon information if applied
                    if (typeof appliedCoupon !== 'undefined' && appliedCoupon) {
                        formData.append('applied_coupon', JSON.stringify(appliedCoupon));
                        if (typeof discountAmount !== 'undefined') {
                            formData.append('discount_amount', discountAmount);
                        }
                    }
                    
                    // Convert FormData to regular object for PayMongo integration
                    const orderData = {};
                    for (let [key, value] of formData.entries()) {
                        orderData[key] = value;
                    }
                    
                    // Add customer name and email
                    orderData.customer_name = userName;
                    orderData.customer_email = userEmail;
                    
                    // Calculate final amount with discount
                    const finalAmount = cartTotal - (discountAmount || 0);
                    console.log('[CHECKOUT] Cart Total:', cartTotal);
                    console.log('[CHECKOUT] Discount Amount:', discountAmount);
                    console.log('[CHECKOUT] Final Amount:', finalAmount);
                    
                    // Prepare payment data for PayMongo
                    const paymentData = {
                        payment_method: paymentMethodEl.value,
                        order_type: 'regular',
                        amount: parseFloat(finalAmount),
                        order_data: orderData
                    };
                    
                    console.log('Payment data being sent:', paymentData);
                    
                    // Process payment through PayMongo
                    const response = await fetch('process-payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(paymentData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        if (result.payment_url) {
                            // Redirect to PayMongo payment page
                            window.location.href = result.payment_url;
                        } else if (result.order_id) {
                            // Payment successful, redirect to success page
                            window.location.href = `payment-success.php?order_id=${result.order_id}`;
                        } else {
                            throw new Error('Payment processed but no redirect URL provided');
                        }
                    } else {
                        throw new Error(result.message || 'Payment processing failed');
                    }
                    
                } catch (error) {
                    console.error('Payment error:', error);
                    console.log('[LOADING STATE] Resetting loading state due to error');
                    alert('An error occurred while processing your payment: ' + error.message);
                    setLoadingState(false);
                    orderProcessing = false;
                }
            });
        }
    });
  </script>
</head>
<body class="checkout-page">
<?php include '../../user-includes/navbar/customer-navigation.php'; ?>
    <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>


<div class="checkout-container">
    <form id="checkout-form">
        <!-- User Information Section -->
        <div class="section-card user-information">
            <h2>User Information</h2>
            <div class="user-details">
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value" id="user-name">
                        <?php 
                            $fullname = trim($user['firstname'] . ' ' . $user['lastname']);
                            echo htmlspecialchars($fullname); 
                        ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value" id="user-email">
                        <?php 
                            error_log("User data at display point: " . print_r($user, true));
                            error_log("Session data at display point: " . print_r($_SESSION, true));
                            
                            // Try multiple ways to get the email
                            $email = null;
                            
                            if (!empty($user['email'])) {
                                $email = $user['email'];
                            } elseif (isset($_SESSION['session_data']['user_data']['email'])) {
                                $email = $_SESSION['session_data']['user_data']['email'];
                            } elseif (isset($_SESSION['user_data']['email'])) {
                                $email = $_SESSION['user_data']['email'];
                            }
                            
                            if ($email) {
                                echo htmlspecialchars($email);
                            } else {
                                echo 'Email not available';
                            }
                        ?>
                    </span>
                    <input type="hidden" name="customer_email" value="<?php 
                        echo !empty($email) ? htmlspecialchars($email) : '';
                    ?>">
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact:</span>
                    <input type="tel" id="contact_number" name="contact_number" 
                           placeholder="09xxxxxxxxx (11 digits)" 
                           required 
                           pattern="09\d{9}"
                           title="Please enter a valid 11-digit phone number starting with 09"
                           maxlength="11"
                           minlength="11"
                           inputmode="numeric">
                </div>
            </div>
        </div>

        <!-- Combined Shipping Options & Details -->
        <div class="section-card shipping-details">
            <h2>Shipping Options</h2>
            
            <?php if ($has_pickup_only && $has_flexible): ?>
                <div class="shipping-method-notice">
                    <p><strong>Pick Up Required:</strong> The selected items for checkout are Pick Up Only products.</p>
                </div>
            <?php elseif ($has_delivery_only && $has_flexible): ?>
                <div class="shipping-method-notice">
                    <p>The selected items for checkout are Delivery Only products.</p>
                    <p><strong>Delivery Areas:</strong> We deliver to Sta. Rosa, Cabuyao, Calamba, Binan (Laguna), Silang, and Tagaytay (Cavite) only.</p>
                </div>
            <?php elseif ($has_pickup_only): ?>
                <div class="shipping-method-notice">
                    <p><strong>Pick Up Required:</strong> The selected items for checkout are Pick Up Only products.</p>
                </div>
            <?php elseif ($has_delivery_only): ?>
                <div class="shipping-method-notice">
                    <p>The selected items for checkout are Delivery Only products.</p>
                    <p><strong>Delivery Areas:</strong> We deliver to Sta. Rosa, Cabuyao, Calamba, Binan (Laguna), Silang, and Tagaytay (Cavite) only.</p>
                </div>
            <?php elseif ($has_flexible): ?>
                <div class="shipping-method-notice" style="background: #e3f2fd; border-color: #90caf9;">
                    <p><strong>Choose Your Shipping Method:</strong> The selected items for checkout have flexible shipping options. You can choose either Pick Up or Delivery.</p>
                </div>
            <?php endif; ?>
            
            <div class="delivery-modes">
                <div>
                    <label class="section-subtitle">Select Delivery Method:</label>
                </div>
                <div class="delivery-type">
                    <label class="radio-option">
                        <input type="radio" id="pickup" name="delivery_method" value="pickup" 
                            <?= $shipping_method === 'pickup' ? 'checked' : '' ?>
                            <?= !$can_change_shipping && $shipping_method !== 'pickup' ? 'disabled' : '' ?>>
                        <span>Pick Up <?= !$can_change_shipping && $shipping_method !== 'pickup' ? '(Not available)' : '' ?></span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" id="delivery" name="delivery_method" value="delivery"
                            <?= $shipping_method === 'delivery' ? 'checked' : '' ?>
                            <?= !$can_change_shipping && $shipping_method !== 'delivery' ? 'disabled' : '' ?>>
                        <span>Delivery <?= !$can_change_shipping && $shipping_method !== 'delivery' ? '(Not available)' : '' ?></span>
                    </label>
                </div>
            </div>
    
            <!-- Pickup Details -->
            <div id="pickup-details" class="delivery-content">
                <div class="calendar-section">
                    <div id="calendar"></div>
                </div>
                <div class="datetime-inputs">
                    <div class="form-group">
                        <label for="pickup_date">Pickup Date:</label>
                        <input type="text" id="pickup_date" name="pickup_date" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="pickup_time">Pickup Time:</label>
                        <input type="time" id="pickup_time" name="pickup_time" required>
                    </div>
                </div>
            </div>

            <!-- Delivery Details -->
            <div id="delivery-details" class="delivery-content" style="display: none;">
                <div class="address-section">
                    <div>
                        <label for="delivery_address" class="section-subtitle" >Delivery Address:</label>
                    </div>
                    <div class="address-input-group">
                        <input type="text" id="delivery_address" name="delivery_address" 
                               placeholder="Use set location button to enter delivery address" readonly>
                        <button type="button" id="setLocationBtn" class="btn-secondary">Set Location</button>
                    </div>
                </div>
                <div class="datetime-inputs">
                    <div class="form-group">
                        <label for="delivery_date">Delivery Date:</label>
                        <input type="text" id="delivery_date" name="delivery_date" placeholder="Select Date From the Calendar above" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="delivery_time">Delivery Time:</label>
                        <input type="time" id="delivery_time" name="delivery_time" 
                               min="06:00" max="18:00" step="1800" required>
                        <small class="time-note">Available time: 6:00 AM - 6:00 PM</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- DIV 4: Order Summary -->
        <div class="section-card order-summary">
            <h2>Order Summary</h2>
            
            <!-- Coupon Code Section -->

            
            <div class="summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="item" data-status-id="<?= $item['status_id'] ?>">
                        <div class="item-info">
                            <h3 class="item-name">
                                <?= htmlspecialchars($item['name']) ?>
                                <?php if ($item['status_id'] == 3): ?>
                                    <span class="shipping-indicator" style="display: inline-block; margin-left: 8px; padding: 2px 8px; background: #f0f0f0; border-radius: 3px; font-size: 11px; color: #666; font-weight: normal;"></span>
                                <?php endif; ?>
                            </h3>
                            <p class="quantity"><?= $item['quantity'] ?> x <?= $item['price'] ?></p>
                            <?php if ($item['status_id'] == 1): ?>
                                <p class="product-shipping-method" style="font-size: 12px; color: #4CAF50; font-weight: 600;">🚶 Pick Up Only</p>
                            <?php elseif ($item['status_id'] == 2): ?>
                                <p class="product-shipping-method" style="font-size: 12px; color: #2196F3; font-weight: 600;">🚚 Delivery Only</p>
                            <?php elseif ($item['status_id'] == 3): ?>
                                <p class="product-shipping-method" style="font-size: 12px; color: #9C27B0; font-weight: 600;">✨ Flexible (Delivery or Pick-Up)</p>
                            <?php endif; ?>
                        </div>
                        <div class="item-price">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>


            <div class="summary-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">₱<?= number_format($cart_total, 2) ?></span>
                </div>
                <div class="total-row" id="discount-row" style="display: none;">
                    <span>Discount:</span>
                    <span id="discount_amount">-₱0.00</span>
                </div>
                <div class="total-row" id="shipping-row">
                    <span>Shipping Fee:</span>
                    <span id="shipping_fee">₱0.00</span>
                </div>
                <div class="total-row total-final">
                    <span>Total:</span>
                    <span id="total">₱<?= number_format($cart_total, 2) ?></span>
                </div>
            </div>

            <div class="coupon-section">
                <div class="coupon-input-group">
                    <input type="text" id="coupon_code" name="coupon_code" 
                           placeholder="Enter coupon code" 
                           class="coupon-input">
                    <button type="button" id="apply_coupon_btn" class="btn-apply-coupon">Apply</button>
                </div>
                <div id="coupon_message" class="coupon-message"></div>
                <div id="coupon_applied" class="coupon-applied" style="display: none;">
                    <div class="applied-coupon-info">
                        <span class="coupon-code-display"></span>
                        <span class="coupon-discount"></span>
                        <button type="button" id="remove_coupon_btn" class="btn-remove-coupon">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- DIV 5: Payment Mode -->
        <div class="section-card payment-mode">
            <h2>Mode of Payment</h2>
            <div class="payment-options">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="gcash" id="gcash" checked>
                    <div class="payment-option-content">
                        <span class="payment-text">GCash</span>
                    </div>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="maya" id="maya">
                    <div class="payment-option-content">
                        <span class="payment-text">Maya</span>
                    </div>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="card" id="card">
                    <div class="payment-option-content">
                        <span class="payment-text">Credit/Debit Card</span>
                    </div>
                </label>
            </div>
            <div class="payment-note">
                <p>Payment instructions will be provided after placing your order.</p>
            </div>
        </div>

        <div class="section-card order-notes">
            <h2>Order Notes</h2>
            <textarea id="order_notes" name="order_notes" 
                      placeholder="Add any special instructions or notes here (optional)"></textarea>
        </div>

        <button type="submit" class="btn-primary place-order-btn" style="background-color: #256035;">Place Order</button>
    </form>
</div>

<div id="locationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Set Delivery Location</h2>
            <span class="close-btn">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group mb-3">
                <label class="form-label">Select Delivery Location *</label>
                <select name="delivery_location" class="form-control form-control-md" id="delivery_location" required>
                    <option value="">Choose your delivery location</option>
                    <optgroup label="Laguna">
                        <option value="Sta. Rosa, Laguna 4026">Sta. Rosa, Laguna 4026</option>
                        <option value="Sta. Rosa, Laguna 4034">Sta. Rosa, Laguna 4034</option>
                        <option value="Cabuyao, Laguna 4025">Cabuyao, Laguna 4025</option>
                        <option value="Calamba, Laguna 4027">Calamba, Laguna 4027</option>
                        <option value="Calamba, Laguna 4028">Calamba, Laguna 4028</option>
                        <option value="Calamba, Laguna 4029">Calamba, Laguna 4029</option>
                        <option value="Binan, Laguna 4024">Binan, Laguna 4024</option>
                    </optgroup>
                    <optgroup label="Cavite">
                        <option value="Silang, Cavite 4118">Silang, Cavite 4118</option>
                        <option value="Tagaytay, Cavite 4120">Tagaytay, Cavite 4120</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Complete Address *</label>
                <textarea name="complete_address" class="form-control" id="complete_address" rows="3" 
                         placeholder="Enter your complete address (house number, street, subdivision, etc.)" required></textarea>
                <small class="form-text text-muted">Please provide specific details like house/building number, street name, subdivision, landmarks, etc.</small>
            </div>
            <button type="button" id="saveLocationBtn" class="btn btn-success">Save Location</button>
        </div>
    </div>
</div>

<!-- Add Bootstrap CSS -->
<!-- Add jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('locationModal');
    const setLocationBtn = document.getElementById('setLocationBtn');
    const closeBtn = document.querySelector('.close-btn');
    const saveLocationBtn = document.getElementById('saveLocationBtn');
    const deliveryAddressInput = document.getElementById('delivery_address');

    setLocationBtn.addEventListener('click', function() {
        modal.style.display = 'block';
    });

    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    saveLocationBtn.addEventListener('click', function() {
        const deliveryLocation = document.getElementById('delivery_location').value;
        const completeAddress = document.getElementById('complete_address').value;

        if (!deliveryLocation) {
            alert('Please select a delivery location');
            return;
        }

        if (!completeAddress.trim()) {
            alert('Please enter your complete address');
            return;
        }

        // Combine the selected location with the complete address
        const fullAddress = `${completeAddress.trim()}, ${deliveryLocation}`;

        if (deliveryAddressInput) {
            deliveryAddressInput.value = fullAddress;
        }

        modal.style.display = 'none';
    });
});

const phoneInput = document.getElementById('contact_number');

// Add touched class handling for better validation UX
function addTouchedClassToInputs() {
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        // Add touched class on blur (when user leaves the field)
        input.addEventListener('blur', function() {
            this.classList.add('touched');
        });
        
        // Add touched class on first input
        input.addEventListener('input', function() {
            if (!this.classList.contains('touched')) {
                this.classList.add('touched');
            }
        }, { once: true });
    });
}

// Initialize touched class handling
addTouchedClassToInputs();

phoneInput.addEventListener('input', function () {
    // Only allow digits
    this.value = this.value.replace(/[^\d]/g, '');
    
    // Ensure it starts with 09
    if (this.value.length > 0 && !this.value.startsWith('09')) {
        // If user types something that doesn't start with 09, prepend 09
        if (this.value.startsWith('9')) {
            this.value = '0' + this.value;
        } else if (!this.value.startsWith('0')) {
            this.value = '09' + this.value;
        } else {
            // If starts with 0 but not 09, replace with 09
            this.value = '09' + this.value.substring(1);
        }
    }
    
    // Limit to 11 digits maximum
    if (this.value.length > 11) {
        this.value = this.value.substring(0, 11);
    }
});

// PayMongo Payment Processing Functions
async function processPayment(paymentData) {
    try {
        console.log('JSON string being sent:', JSON.stringify(paymentData));
        console.log('JSON string length:', JSON.stringify(paymentData).length);
        
        const response = await fetch('process-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(paymentData)
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const rawResponse = await response.text();
        console.log('Raw response:', rawResponse);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = JSON.parse(rawResponse);
        return result;
        
    } catch (error) {
        console.error('Payment processing error:', error);
        throw error;
    }
}

async function handleCardPayment(paymentResult) {
    try {
        // For card payments, we would need to handle 3D Secure or other confirmations
        // This is a simplified implementation
        console.log('Handling card payment:', paymentResult);
        
        if (paymentResult.client_secret) {
            // In a real implementation, you would use PayMongo's JS SDK here
            // For now, we'll redirect to a success page
            window.location.href = `payment-success.php?type=regular&order_id=${paymentResult.order_id}`;
        } else {
            throw new Error('Card payment confirmation failed');
        }
    } catch (error) {
        console.error('Card payment error:', error);
        throw error;
    }
}

// Global variable to track if order is being processed
let orderProcessing = false;
let countdownTimer = null;

// Check if there's an ongoing order process from previous session
document.addEventListener('DOMContentLoaded', function() {
    const orderStartTime = sessionStorage.getItem('orderStartTime');
    if (orderStartTime) {
        const elapsed = Math.floor((Date.now() - parseInt(orderStartTime)) / 1000);
        const remaining = 20 - elapsed;
        
        if (remaining > 0) {
            // Continue countdown from where it left off
            const submitButton = document.querySelector('button[type="submit"]');
            const buttonText = submitButton.querySelector('.button-text') || submitButton;
            
            orderProcessing = true;
            submitButton.disabled = true;
            submitButton.classList.add('btn-processing');
            submitButton.style.opacity = '0.7';
            submitButton.style.cursor = 'not-allowed';
            
            // Start countdown with remaining time
            startCountdownTimer(submitButton, buttonText, remaining);
        } else {
            // Time expired, clear the session storage
            sessionStorage.removeItem('orderStartTime');
        }
    }
});

function setLoadingState(isLoading) {
    const submitButton = document.querySelector('button[type="submit"]');
    const buttonText = submitButton.querySelector('.button-text') || submitButton;
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    console.log('[LOADING STATE] Setting loading state:', isLoading, 'for button:', submitButton);
    
    if (isLoading) {
        orderProcessing = true;
        submitButton.disabled = true;
        submitButton.classList.add('btn-processing');
        
        // Show overlay loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('show');
            console.log('[LOADING STATE] Overlay loading shown');
        }
        
        // Store start time in session storage
        sessionStorage.setItem('orderStartTime', Date.now().toString());
        
        // Start 20-second countdown to re-enable button
        startCountdownTimer(submitButton, buttonText);
    } else {
        // Only re-enable if not in countdown mode
        if (!orderProcessing) {
            submitButton.disabled = false;
            submitButton.classList.remove('btn-processing');
            buttonText.innerHTML = 'Place Order';
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
            
            // Hide overlay loading
            if (loadingOverlay) {
                loadingOverlay.classList.remove('show');
                console.log('[LOADING STATE] Overlay loading hidden');
            }
        }
    }
}

function startCountdownTimer(submitButton, buttonText, initialCountdown = 20) {
    let countdown = initialCountdown;
    
    // Clear any existing timer
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
    
    // Update button text with countdown
    const updateCountdown = () => {
        if (countdown > 0) {
            buttonText.innerHTML = `<span class="spinner"></span>Please wait... (${countdown}s)`;
            countdown--;
        } else {
            // Re-enable button after countdown
            orderProcessing = false;
            submitButton.disabled = false;
            submitButton.classList.remove('btn-processing');
            buttonText.innerHTML = 'Place Order';
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
            
            // Hide overlay loading
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('show');
                console.log('[LOADING STATE] Overlay loading hidden after countdown');
            }
            
            // Clear the timer and session storage
            clearInterval(countdownTimer);
            countdownTimer = null;
            sessionStorage.removeItem('orderStartTime');
            
            console.log('Order button re-enabled after countdown completed');
        }
    };
    
    // Start the countdown immediately
    updateCountdown();
    
    // Continue countdown every second
    countdownTimer = setInterval(updateCountdown, 1000);
}
</script>

<?php
include '../../user-includes/user-footer.php';
?>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-content">
        <div class="loading-spinner-large"></div>
        <div class="loading-text">Processing Your Order</div>
        <div class="loading-subtext">Please wait while we process your payment...</div>
    </div>
</div>

</body>
</html>
