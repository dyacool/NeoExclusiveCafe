<?php
// Enable error reporting temporarily to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Require login for checkout - check for user role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

$page_title = "Checkout";
$additional_css = [
    "checkout.css"
];

require_once "../../user-includes/user-header.php";

// Debug session data
error_log("Session data at start: " . print_r($_SESSION, true));
error_log("Session ID: " . session_id());
error_log("Session status: " . session_status());
error_log("Current user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Current user_role: " . ($_SESSION['user_role'] ?? 'NOT SET'));

// Include database connection
require_once '../../user-includes/database.php';

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
        $query = "SELECT firstname, lastname, email FROM crud.users WHERE id = ? LIMIT 1";
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
            header("Location: cart.php");
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

// If still no items selected, redirect back to cart
if (empty($selected_cart_ids)) {
    error_log("No cart items found - redirecting to cart");
    header("Location: cart.php");
    exit();
}

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
        header("Location: cart.php");
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
                         WHERE c.id = ? AND p.status_id IN (1, 2)
                         GROUP BY c.id";
        } else {
        $placeholders = str_repeat('?,', count($selected_cart_ids) - 1) . '?';
            $cart_sql = "SELECT c.*, p.name, p.price, p.status_id,
                         GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ',') as available_days
                     FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                         LEFT JOIN product_day pd ON p.id = pd.product_id
                         WHERE c.id IN ($placeholders) AND p.status_id IN (1, 2)
                         GROUP BY c.id";
        }
        
        $cart_stmt = $conn->prepare($cart_sql);
        
        if ($cart_stmt) {
            $types = str_repeat('i', count($selected_cart_ids));
            $cart_stmt->bind_param($types, ...$selected_cart_ids);
            
            if ($cart_stmt->execute()) {
                $cart_result = $cart_stmt->get_result();
                
                while ($item = $cart_result->fetch_assoc()) {
                    $cart_total += $item['price'] * $item['quantity'];
                    $cart_items[] = [
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'cart_id' => $item['id'],
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
    header("Location: cart.php");
    exit();
}

// Determine shipping method based on product status
$shipping_method = 'pickup'; // Default to pickup for all items

if (!empty($cart_items)) {
    error_log("Setting shipping method to pickup for " . count($cart_items) . " cart items");
} else {
    error_log("No cart items to determine shipping method - keeping default (pickup)");
}

// Store shipping method in session
$_SESSION['shipping_method'] = $shipping_method;

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
      padding: 15px;
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
      padding: 8px 12px;
      border-radius: 4px;
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
      padding: 12px 8px;
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
    
    .datetime-inputs {
      width: 100%;
      max-width: 400px;
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
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
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
    }
    
    .address-section input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      margin-bottom: 10px;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
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
    
    /* Shipping Options Improvements */
    .shipping-details {
      margin-bottom: 30px;
    }
    
    .delivery-type {
      margin-bottom: 20px;
    }
    
    .radio-option {
      display: inline-block;
      margin-right: 20px;
      cursor: pointer;
    }
    
    .radio-option input[type="radio"] {
      margin-right: 8px;
    }
    
    .radio-option span {
      font-weight: 500;
      color: #333;
    }
    
    /* Coupon Section Styles */
    .coupon-section {
      margin-bottom: 20px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #e9ecef;
    }
    
    .coupon-input-group {
      display: flex;
      gap: 10px;
      margin-bottom: 10px;
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
      padding: 10px 20px;
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
                        const dateStr = currentDate.toISOString().split('T')[0];
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



        function fetchDateLimits(start, end) {
            const startStr = start.toISOString().split('T')[0];
            const endStr = end.toISOString().split('T')[0];
            
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
            pickupRadio.addEventListener('change', updateVisibility);
        }
        if (deliveryRadio) {
            deliveryRadio.addEventListener('change', function() {
                updateVisibility();
                
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

        // Form submission handler
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    // Get all form data
                    const formData = new FormData(this);
                    
                    // Add cart items and user info
                    const cartItems = <?php echo json_encode($cart_items); ?>;
                    const cartTotal = <?php echo json_encode($cart_total); ?>;
                    
                    const userEmail = <?php echo json_encode($user['email']); ?>;
                    const userName = <?php echo json_encode($user['firstname'] . ' ' . $user['lastname']); ?>;
                    
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
                    const todayStr = new Date().toISOString().split('T')[0];
                    const deliveryDate = document.getElementById('delivery_date').value || todayStr;
                    const pickupDate = document.getElementById('pickup_date').value || todayStr;
                    

                    
                    formData.append('delivery_method', isDelivery ? 'delivery' : 'pickup');
                    formData.append('delivery_date', isDelivery ? deliveryDate : todayStr);
                    formData.append('pickup_date', !isDelivery ? pickupDate : todayStr);
                    formData.append('delivery_time', isDelivery ? document.getElementById('delivery_time').value : document.getElementById('pickup_time').value);
                    
                    // Add payment method
                    formData.append('payment_method', document.querySelector('input[name="payment_method"]:checked').value);
                    
                    // Add notes if any
                    formData.append('notes', document.getElementById('order_notes').value);
                    
                    // Add coupon information if applied
                    if (appliedCoupon) {
                        formData.append('applied_coupon', JSON.stringify(appliedCoupon));
                        formData.append('discount_amount', discountAmount);
                    }
                    
                    // Show loading state
                    setLoadingState(true);
                    
                    // Get selected payment method
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                    
                    // Convert FormData to regular object for PayMongo integration
                    const orderData = {};
                    for (let [key, value] of formData.entries()) {
                        orderData[key] = value;
                    }
                    
                    // Add customer name and email
                    orderData.customer_name = (orderData.first_name || '') + ' ' + (orderData.last_name || '');
                    orderData.customer_email = orderData.email;
                    
                    // Prepare payment data for PayMongo
                    const paymentData = {
                        payment_method: paymentMethod,
                        order_type: 'regular',
                        amount: <?php echo $cart_total; ?>,
                        order_data: orderData
                    };
                    
                    console.log('Payment data being sent:', paymentData);
                    
                    // For now, let's use the existing order processing instead of PayMongo
                    // This will allow the checkout to work while we fix the payment integration
                    console.log('Processing order without payment integration...');
                    
                    // Redirect to the existing order processing
                    const orderForm = document.createElement('form');
                    orderForm.method = 'POST';
                    orderForm.action = 'process_order.php';
                    
                    // Add all form data
                    for (let [key, value] of formData.entries()) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        orderForm.appendChild(input);
                    }
                    
                    document.body.appendChild(orderForm);
                    orderForm.submit();
                } catch (error) {
                    console.error('Order processing error:', error);
                    alert('An error occurred while placing your order: ' + error.message);
                    setLoadingState(false);
                }
            });
        }
    });
  </script>
</head>
<body class="checkout-page">
<?php include '../../user-includes/navbar/customer-navigation.php'; ?>

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
                           placeholder="Enter your contact number" required 
                           pattern="(\+63|0)9\d{9}"
                           title="Please enter a valid 11-digit phone number"
                           maxlength="13"
                           inputmode="numeric">
                </div>
            </div>
        </div>

        <!-- Combined Shipping Options & Details -->
        <div class="section-card shipping-details">
            <h2>Shipping Options</h2>
            <div class="delivery-type">
                <label class="radio-option">
                    <input type="radio" id="pickup" name="delivery_method" value="pickup" 
                           <?= $shipping_method === 'pickup' ? 'checked' : '' ?>
                           <?= $shipping_method === 'delivery' ? 'disabled' : '' ?>>
                    <span>Pick Up</span>
                </label>
                <label class="radio-option">
                    <input type="radio" id="delivery" name="delivery_method" value="delivery"
                           <?= $shipping_method === 'delivery' ? 'checked' : '' ?>
                           <?= $shipping_method === 'pickup' ? 'disabled' : '' ?>>
                    <span>Delivery</span>
                </label>
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
                    <input type="text" id="delivery_address" name="delivery_address" 
                           placeholder="Enter delivery address" readonly>
                    <button type="button" id="setLocationBtn" class="btn-secondary">Set Location</button>
                </div>
                <div class="datetime-inputs">
                    <div class="form-group">
                        <label for="delivery_date">Delivery Date:</label>
                        <input type="text" id="delivery_date" name="delivery_date" readonly required>
                    </div>
                    <div class="delivery_time">
                        <label for="delivery_time">Delivery Time:</label>
                        <input type="time" id="delivery_time" name="delivery_time" 
                               min="06:00" max="18:00" step="1800" required>
                        <small class="time-note">Available time: 6:00 AM - 6:00 PM (30-minute intervals)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- DIV 4: Order Summary -->
        <div class="section-card order-summary">
            <h2>Order Summary</h2>
            
            <!-- Coupon Code Section -->
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
            
            <?php if ($shipping_method === 'pickup' || $shipping_method === 'delivery'): ?>
                <div class="shipping-method-notice">
                    <p><strong>Shipping Method:</strong> <?= ucfirst($shipping_method) ?> (Automatically set based on product availability)</p>
                </div>
            <?php endif; ?>
            
            <div class="summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="item">
                        <div class="item-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="quantity">Quantity: <?= $item['quantity'] ?></p>
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

        <!-- Order Notes -->
        <div class="section-card order-notes">
            <h2>Order Notes</h2>
            <textarea id="order_notes" name="order_notes" 
                      placeholder="Add any special instructions or notes here (optional)"></textarea>
        </div>

        <!-- Place Order Button -->
        <button type="submit" class="btn-primary place-order-btn" style="background-color: #256035;">Place Order</button>
    </form>
</div>

<!-- Location Modal -->
<div id="locationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Set Delivery Location</h2>
            <span class="close-btn">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group mb-3">
                <label class="form-label">Region *</label>
                <select name="region" class="form-control form-control-md" id="region"></select>
                <input type="hidden" class="form-control form-control-md" name="region_text" id="region-text" required>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Province *</label>
                <select name="province" class="form-control form-control-md" id="province"></select>
                <input type="hidden" class="form-control form-control-md" name="province_text" id="province-text" required>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">City / Municipality *</label>
                <select name="city" class="form-control form-control-md" id="city"></select>
                <input type="hidden" class="form-control form-control-md" name="city_text" id="city-text" required>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Barangay *</label>
                <select name="barangay" class="form-control form-control-md" id="barangay"></select>
                <input type="hidden" class="form-control form-control-md" name="barangay_text" id="barangay-text" required>
            </div>
            <div class="form-group mb-3">
                <label for="street-text" class="form-label">Street (Optional)</label>
                <input type="text" class="form-control form-control-md" name="street_text" id="street-text">
            </div>
            <button type="button" id="saveLocationBtn" class="btn btn-success">Save Location</button>
        </div>
    </div>
</div>

<!-- Add Bootstrap CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<!-- Add jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Add Address Selector Script -->
<script src="ph-address-selector.js"></script>

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
        const region = document.getElementById('region-text').value;
        const province = document.getElementById('province-text').value;
        const city = document.getElementById('city-text').value;
        const barangay = document.getElementById('barangay-text').value;
        const street = document.getElementById('street-text').value;

        if (!region || !province || !city || !barangay) {
            alert('Please fill in all required fields');
            return;
        }

        const address = street 
            ? `${street}, Brgy. ${barangay}, ${city}, ${province}, ${region}`
            : `Brgy. ${barangay}, ${city}, ${province}, ${region}`;

        if (deliveryAddressInput) {
            deliveryAddressInput.value = address;
        }

        modal.style.display = 'none';
    });
});

const phoneInput = document.getElementById('contact_number');

phoneInput.addEventListener('input', function () {
    // Always allow only numbers and optional leading +
    this.value = this.value.replace(/[^\d+]/g, '');

    // Check prefix and set maxlength accordingly
    if (this.value.startsWith('+63')) {
      this.maxLength = 13;  // +63 + 9-digit number
    } else if (this.value.startsWith('0')) {
      this.maxLength = 11;  // 0 + 10-digit number
    } else {
      this.maxLength = 13; // default to max possible
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

function setLoadingState(isLoading) {
    const submitButton = document.querySelector('button[type="submit"]');
    const buttonText = submitButton.querySelector('.button-text') || submitButton;
    
    if (isLoading) {
        submitButton.disabled = true;
        buttonText.textContent = 'Processing Payment...';
        submitButton.style.opacity = '0.7';
    } else {
        submitButton.disabled = false;
        buttonText.textContent = 'Place Order';
        submitButton.style.opacity = '1';
    }
}
</script>

<?php
include '../../user-includes/footer.php';
?>
</body>
</html>
