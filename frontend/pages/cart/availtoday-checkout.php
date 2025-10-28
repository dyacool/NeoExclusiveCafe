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

$page_title = "Available Today Checkout";
$additional_css = [
    "checkout.css"
];

// Include database connection
require_once '../../../backend/pages/admin-includes/database.php';

// Test database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed");
}

// Debug session data
error_log("=== AVAILTODAY CHECKOUT DEBUG ===");
error_log("Session ID: " . session_id());
error_log("User ID from session: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Full session data: " . print_r($_SESSION, true));

// Initialize user array with default values
$user = array(
    'firstname' => $_SESSION['user_firstname'] ?? '',
    'lastname' => $_SESSION['user_lastname'] ?? '',
    'email' => null
);

// Get user data from session
if (isset($_SESSION['session_data']) && isset($_SESSION['session_data']['user_data'])) {
    $user = $_SESSION['session_data']['user_data'];
    error_log("Got user data from session_data->user_data: " . print_r($user, true));
} elseif (isset($_SESSION['user_data'])) {
    $user = $_SESSION['user_data'];
    error_log("Got user data from user_data: " . print_r($user, true));
} else {
    error_log("No user data found in session, using defaults: " . print_r($user, true));
}

// Always fetch user data from database to ensure we have the email
if (isset($_SESSION['user_id'])) {
    try {
        $user_id = intval($_SESSION['user_id']);
        $user_query = "SELECT firstname, lastname, email FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_query);
        
        if ($user_stmt) {
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $user['firstname'] = $user_data['firstname'];
                $user['lastname'] = $user_data['lastname'];
                $user['email'] = $user_data['email'];
                
                error_log("User data fetched successfully: " . print_r($user, true));
            } else {
                error_log("No user found with ID: " . $user_id);
            }
            $user_stmt->close();
        } else {
            error_log("Failed to prepare user query");
        }
    } catch (Exception $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }
} else {
    error_log("No user_id in session");
}

// Get selected cart IDs from GET or POST
$selected_cart_ids = [];

// First check GET parameters (from cart.php)
if (isset($_GET['cart_ids']) && !empty($_GET['cart_ids'])) {
    $selected_cart_ids = array_filter(array_map('intval', explode(',', $_GET['cart_ids'])));
    error_log("Got cart IDs from GET: " . print_r($selected_cart_ids, true));
}
// Then check POST parameters (from shopping-cart-sameday.php)
elseif (isset($_POST['cart_items']) && !empty($_POST['cart_items'])) {
    $selected_cart_ids = array_filter(array_map('intval', explode(',', $_POST['cart_items'])));
    error_log("Got cart IDs from POST cart_items: " . print_r($selected_cart_ids, true));
} elseif (isset($_POST['selected_cart_ids']) && is_array($_POST['selected_cart_ids'])) {
    $selected_cart_ids = array_filter(array_map('intval', $_POST['selected_cart_ids']));
    error_log("Got cart IDs from POST selected_cart_ids: " . print_r($selected_cart_ids, true));
}

// If no items selected, redirect back to cart
if (empty($selected_cart_ids)) {
    error_log("No items selected for checkout - redirecting to cart");
    $_SESSION['error_message'] = "Please select items to checkout.";
    header("Location: cart.php");
    exit();
}

// Get Available Today cart items from availtoday_cart table (ONLY SELECTED ITEMS)
$cart_total = 0;
$cart_items = [];
$shipping_method = 'pickup'; // Default
$has_mixed_availtoday_status = false;
$availtoday_status_counts = ['pickup' => 0, 'delivery' => 0, 'null' => 0];

try {
    // Build placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($selected_cart_ids), '?'));
    
    // Get cart items with availtoday_status information (FILTERED BY SELECTED IDs)
    $cart_sql = "SELECT 
                    ca.id as cart_id,
                    ca.product_id,
                    ca.quantity,
                    p.name,
                    p.price,
                    p.status_id,
                    p.availtoday_status_id,
                    ats.name as availtoday_status_name,
                    GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ',') as available_days
                 FROM availtoday_cart ca
                 JOIN products p ON ca.product_id = p.id
                 LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                 LEFT JOIN product_day pd ON p.id = pd.product_id
                 WHERE ca.user_id = ? AND ca.id IN ($placeholders) AND p.deleted_at IS NULL
                 GROUP BY ca.id";
    
    $cart_stmt = $conn->prepare($cart_sql);
    if ($cart_stmt) {
        // Bind user_id first, then all selected cart IDs
        $types = 'i' . str_repeat('i', count($selected_cart_ids));
        $params = array_merge([$_SESSION['user_id']], $selected_cart_ids);
        $cart_stmt->bind_param($types, ...$params);
        
        if ($cart_stmt->execute()) {
            $cart_result = $cart_stmt->get_result();
            
            while ($item = $cart_result->fetch_assoc()) {
                // Validate same-day stock availability before processing
                // Check quantity_per_day_sdo table for today's date
                $today_date = date('Y-m-d');
                $stock_check_sql = "SELECT quantity FROM quantity_per_day_sdo WHERE product_id = ? AND date = ?";
                $stock_check_stmt = $conn->prepare($stock_check_sql);
                $stock_check_stmt->bind_param("is", $item['product_id'], $today_date);
                $stock_check_stmt->execute();
                $stock_result = $stock_check_stmt->get_result();
                
                if ($stock_row = $stock_result->fetch_assoc()) {
                    $available_stock = $stock_row['quantity'];
                    
                    // Check if cart quantity exceeds available stock for today
                    if ($item['quantity'] > $available_stock) {
                        $_SESSION['error_message'] = "Insufficient stock for " . $item['name'] . " today. Available: " . $available_stock . ", Requested: " . $item['quantity'];
                        error_log("Same-day stock validation failed for product " . $item['product_id'] . " on $today_date: Available=" . $available_stock . ", Requested=" . $item['quantity']);
                        header("Location: cart.php");
                        exit();
                    }
                } else {
                    // No stock entry for today - product not available for same-day order
                    $_SESSION['error_message'] = $item['name'] . " is not available for same-day order today.";
                    error_log("No same-day stock entry found for product " . $item['product_id'] . " on $today_date");
                    header("Location: cart.php");
                    exit();
                }
                $stock_check_stmt->close();
                
                $cart_total += $item['price'] * $item['quantity'];
                
                // Determine shipping method based on availtoday_status_id
                $item_shipping_method = 'pickup'; // default
                if ($item['availtoday_status_id'] == 1) {
                    $item_shipping_method = 'pickup';
                    $availtoday_status_counts['pickup']++;
                } elseif ($item['availtoday_status_id'] == 2) {
                    $item_shipping_method = 'delivery';
                    $availtoday_status_counts['delivery']++;
                } else {
                    // If availtoday_status_id is null, run lines 266-280 logic (check status_id)
                    if ($item['status_id'] == 1) {
                        $item_shipping_method = 'pickup';
                        $availtoday_status_counts['pickup']++;
                    } elseif ($item['status_id'] == 2) {
                        $item_shipping_method = 'delivery';
                        $availtoday_status_counts['delivery']++;
                    } else {
                        $availtoday_status_counts['null']++;
                    }
                }
                
                $cart_items[] = [
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'cart_id' => $item['cart_id'],
                    'product_id' => $item['product_id'],
                    'status_id' => $item['status_id'],
                    'availtoday_status_id' => $item['availtoday_status_id'],
                    'availtoday_status_name' => $item['availtoday_status_name'],
                    'available_days' => $item['available_days'],
                    'shipping_method' => $item_shipping_method
                ];
            }
        }
        $cart_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching availtoday cart items: " . $e->getMessage());
}

// If no items found, redirect back
if (empty($cart_items)) {
    error_log("No availtoday cart items found - redirecting to product dashboard");
    $_SESSION['error_message'] = "No items found in your Available Today cart. Please add items and try again.";
    header("Location: ../products/product-dashboard.php");
    exit();
}

// Determine overall shipping method
// Count items by their restrictions (not flexible items)
$has_pickup_only_items = false;
$has_delivery_only_items = false;

foreach ($cart_items as $item) {
    // Check if item is pickup-only (status_id = 1 or availtoday_status_id = 1)
    if ($item['availtoday_status_id'] == 1 || ($item['availtoday_status_id'] === null && $item['status_id'] == 1)) {
        $has_pickup_only_items = true;
    }
    // Check if item is delivery-only (status_id = 2 or availtoday_status_id = 2)
    if ($item['availtoday_status_id'] == 2 || ($item['availtoday_status_id'] === null && $item['status_id'] == 2)) {
        $has_delivery_only_items = true;
    }
}

// Determine available options based on item restrictions
if ($has_pickup_only_items && $has_delivery_only_items) {
    // Both pickup-only and delivery-only items - conflict (force pickup)
    $has_mixed_availtoday_status = true;
    $shipping_method = 'pickup';
} elseif ($has_pickup_only_items) {
    // Has pickup-only items - only pickup allowed
    $shipping_method = 'pickup';
} elseif ($has_delivery_only_items) {
    // Has delivery-only items - only delivery allowed
    $shipping_method = 'delivery';
} else {
    // All items are flexible - allow both options
    $shipping_method = 'pickup'; // Default
}

// Store in session
$_SESSION['availtoday_cart_items'] = $cart_items;
$_SESSION['availtoday_cart_total'] = $cart_total;
$_SESSION['availtoday_shipping_method'] = $shipping_method;
$_SESSION['has_mixed_availtoday_status'] = $has_mixed_availtoday_status;

error_log("Available Today Checkout - Cart items: " . count($cart_items));
error_log("Available Today Checkout - Total: " . $cart_total);
error_log("Available Today Checkout - Shipping method: " . $shipping_method);
error_log("Available Today Checkout - Mixed status: " . ($has_mixed_availtoday_status ? 'Yes' : 'No'));

// Add debug information to be shown in console
$debug_info = [
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'user_data' => $user,
    'cart_items' => $cart_items,
    'shipping_method' => $shipping_method
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Available Today Checkout</title>
  <link rel="stylesheet" href="checkout.css">
</head>
<body class="checkout-page">
<?php include '../../user-includes/navbar/customer-navigation.php'; ?>

<div class="checkout-container">
    <form id="availtoday-checkout-form" action="process-availtoday-checkout.php" method="POST">
        
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
                            // Final attempt to get email - try direct query if still empty
                            if (empty($user['email']) && isset($_SESSION['user_id'])) {
                                try {
                                    $direct_query = "SELECT email FROM users WHERE id = " . intval($_SESSION['user_id']);
                                    $direct_result = $conn->query($direct_query);
                                    if ($direct_result && $direct_result->num_rows > 0) {
                                        $direct_row = $direct_result->fetch_assoc();
                                        $user['email'] = $direct_row['email'];
                                        error_log("Email retrieved via direct query: " . $user['email']);
                                    }
                                } catch (Exception $e) {
                                    error_log("Direct query failed: " . $e->getMessage());
                                }
                            }
                            
                            if (!empty($user['email'])) {
                                echo htmlspecialchars($user['email']);
                            } else {
                                echo '<span style="color: #f44336;">Email not found - please contact support</span>';
                                error_log("FINAL EMAIL DISPLAY ISSUE:");
                                error_log("- User data: " . print_r($user, true));
                                error_log("- Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
                                error_log("- Database connection: " . ($conn ? 'OK' : 'FAILED'));
                            }
                        ?>
                    </span>
                    <input type="hidden" name="email" value="<?php 
                        echo !empty($user['email']) ? htmlspecialchars($user['email']) : '';
                    ?>">
                    <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>">
                    <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>">
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact:</span>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="Enter your contact number" required 
                           pattern="(\+63|0)9\d{9}"
                           title="Please enter a valid 11-digit phone number"
                           maxlength="13"
                           inputmode="numeric">
                </div>
            </div>
        </div>

        <!-- Available Today Notice -->
        <?php if ($has_mixed_availtoday_status): ?>
        <div class="section-card mixed-status-notice">
            <h2>⚠️ Mixed Order Types</h2>
            <p>Your cart contains both pickup and delivery items. Each item's delivery method is automatically assigned based on product availability.</p>
        </div>
        <?php endif; ?>

        <!-- Shipping Options & Details -->
        <div class="section-card shipping-details">
            <h2>Shipping Options</h2>
            
            <?php 
            // Determine what options should be enabled based on item restrictions
            if ($has_pickup_only_items && $has_delivery_only_items) {
                // Conflict: both types exist - force pickup only
                $allow_pickup = true;
                $allow_delivery = false;
            } else {
                $allow_pickup = !$has_delivery_only_items; // Pickup allowed if no delivery-only items
                $allow_delivery = !$has_pickup_only_items; // Delivery allowed if no pickup-only items
            }
            ?>
            
            <?php if ($has_pickup_only_items && $has_delivery_only_items): ?>
                <div class="shipping-method-notice">
                    <p><strong>Mixed Cart:</strong> Your cart contains both pickup-only and delivery-only items. Only pickup is available.</p>
                </div>
            <?php elseif ($has_delivery_only_items): ?>
                <div class="shipping-method-notice">
                    <p><strong>Delivery Required:</strong> Your cart contains delivery-only products.</p>
                    <p><strong>Delivery Areas:</strong> We deliver to Sta. Rosa, Cabuyao, Calamba, Binan (Laguna), Silang, and Tagaytay (Cavite) only.</p>
                </div>
            <?php elseif ($has_pickup_only_items): ?>
                <div class="shipping-method-notice">
                    <p><strong>Pickup Required:</strong> Your cart contains pickup-only products.</p>
                </div>
            <?php else: ?>
                <div class="shipping-method-notice">
                    <p><strong>Flexible Options:</strong> All items in your cart can be either picked up or delivered. Choose your preferred method.</p>
                </div>
            <?php endif; ?>
            
            <div class="delivery-type">
                <label class="radio-option">
                    <input type="radio" id="pickup" name="delivery_method" value="pickup" 
                           <?= $shipping_method === 'pickup' ? 'checked' : '' ?>
                           <?= !$allow_pickup ? 'disabled' : '' ?>>
                    <span>Pick Up <?= !$allow_pickup ? '(Not available - cart has delivery-only items)' : '' ?></span>
                </label>
                <label class="radio-option">
                    <input type="radio" id="delivery" name="delivery_method" value="delivery"
                           <?= $shipping_method === 'delivery' ? 'checked' : '' ?>
                           <?= !$allow_delivery ? 'disabled' : '' ?>>
                    <span>Delivery <?= !$allow_delivery ? '(Not available - cart has pickup-only items)' : '' ?></span>
                </label>
            </div>
            
            <!-- Pickup Details -->
            <div id="pickup-details" class="delivery-content" style="display: none;">
                <div class="method-notice">
                    <p class="auto-assigned-note">Note: Available Today orders are prepared for same-day pickup. No calendar or time selection needed.</p>
                </div>
            </div>

            <!-- Delivery Details -->
            <div id="delivery-details" class="delivery-content" style="display: none;">
                <div class="method-notice">
                    <p class="auto-assigned-note">Note: Available Today orders are prepared for same-day delivery. No calendar or time selection needed.</p>
                </div>
                <div class="address-section">
                    <input type="text" id="delivery_address" name="delivery_address" 
                           placeholder="Enter delivery address" readonly>
                    <button type="button" id="setLocationBtn" class="btn-secondary">Set Location</button>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="section-card order-summary">
            <h2>Order Summary</h2>
            
            <!-- Coupon Code Section -->
            <div class="coupon-section">
                <div class="coupon-input-group">
                    <input type="text" id="coupon_code" name="coupon_code" 
                           placeholder="Enter coupon code" 
                           class="coupon-input">
                    <button type="button" id="check_coupon_btn" class="btn-check-coupon">Check Coupon</button>
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
            
            <div class="summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="item" data-status-id="<?= $item['status_id'] ?>" data-availtoday-status-id="<?= $item['availtoday_status_id'] ?? 'null' ?>">
                        <div class="item-info">
                            <h3>
                                <?= htmlspecialchars($item['name']) ?>
                                <?php if ($item['status_id'] == 3 || ($item['availtoday_status_id'] === null && $item['status_id'] == 3)): ?>
                                    <span class="shipping-indicator" style="display: inline-block; margin-left: 8px; padding: 2px 8px; background: #f0f0f0; border-radius: 3px; font-size: 11px; color: #666; font-weight: normal;"></span>
                                <?php endif; ?>
                            </h3>
                            <p class="quantity">Quantity: <?= $item['quantity'] ?></p>
                            <p class="item-method">
                                <?php if ($item['availtoday_status_id']): ?>
                                    <?php if ($item['availtoday_status_id'] == 1): ?>
                                        <strong style="color: #4CAF50;">Pick Up Only!</strong>
                                    <?php elseif ($item['availtoday_status_id'] == 2): ?>
                                        <strong style="color: #2196F3;">Delivery Only!</strong>
                                    <?php else: ?>
                                        <strong><?php echo htmlspecialchars($item['availtoday_status_name']); ?></strong>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($item['status_id'] == 1): ?>
                                        <strong style="color: #4CAF50;">Pick Up Only!</strong>
                                    <?php elseif ($item['status_id'] == 2): ?>
                                        <strong style="color: #2196F3;">Delivery Only!</strong>
                                    <?php else: ?>
                                        <strong><?php echo $item['status_id'] == 1 ? 'Pick Up' : ($item['status_id'] == 2 ? 'Delivery' : 'Flexible'); ?></strong>
                                    <?php endif; ?>
                                    <span class="auto-assigned">(Auto-assigned)</span>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($item['available_days'])): ?>
                                <p class="available-days">Available: <?php echo htmlspecialchars($item['available_days']); ?></p>
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
                    <span id="discount_amount" style="color: #28a745;">-₱0.00</span>
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

        <!-- Payment Mode -->
        <div class="section-card payment-mode">
            <h2>Mode of Payment</h2>
            <div class="payment-options">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="gcash" id="gcash" checked>
                    <div class="payment-option-content">
                        <span class="payment-text">GCash</span>
                        <small class="payment-desc">Pay with GCash e-wallet</small>
                    </div>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="paymaya" id="paymaya">
                    <div class="payment-option-content">
                        <span class="payment-text">Maya (PayMaya)</span>
                        <small class="payment-desc">Pay with Maya e-wallet</small>
                    </div>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="card" id="card">
                    <div class="payment-option-content">
                        <span class="payment-text">Credit/Debit Card</span>
                        <small class="payment-desc">Pay with Visa, Mastercard</small>
                    </div>
                </label>
            </div>
            
            <!-- Card Payment Form (hidden by default) -->
            <div id="card-payment-form" class="card-payment-form" style="display: none;">
                <h3>Card Details</h3>
                <div id="card-element">
                    <!-- PayMongo card element will be mounted here -->
                </div>
                <div id="card-errors" class="card-errors"></div>
            </div>
            
            <div class="payment-note">
                <p><strong>Secure Payment:</strong> All payments are processed securely through PayMongo.</p>
                <p><small>Test Mode: Use test card numbers for testing payments.</small></p>
            </div>
        </div>

        <!-- Order Notes -->
        <div class="section-card order-notes">
            <h2>Order Notes</h2>
            <textarea id="special_instructions" name="special_instructions" 
                      placeholder="Add any special instructions or notes here (optional)"></textarea>
        </div>

        <!-- Hidden fields for cart data -->
        <input type="hidden" name="cart_items" value="<?php echo htmlspecialchars(json_encode($cart_items)); ?>">
        <input type="hidden" name="cart_total" value="<?php echo $cart_total; ?>">
        <input type="hidden" name="has_mixed_status" value="<?php echo $has_mixed_availtoday_status ? '1' : '0'; ?>">

        <!-- Place Order Button -->
        <button type="submit" class="btn-primary place-order-btn" style="background-color: #256035;">Place Order - ₱<?php echo number_format($cart_total, 2); ?></button>
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
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<!-- Add jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- PayMongo SDK -->
<script src="https://js.paymongo.com/v1"></script>

<script>
// Coupon variables
let appliedCoupon = null;
let discountAmount = 0;
const subtotal = <?= json_encode($cart_total) ?>;

// Coupon helper functions
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

function updateOrderTotal() {
    const totalElement = document.getElementById('total');
    const shippingFee = parseFloat(document.getElementById('shipping_fee').textContent.replace('₱', '').replace(',', '')) || 0;
    
    const total = subtotal - discountAmount + shippingFee;
    
    if (totalElement) {
        totalElement.textContent = '₱' + total.toFixed(2);
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

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('locationModal');
    const setLocationBtn = document.getElementById('setLocationBtn');
    const closeBtn = document.querySelector('.close-btn');
    const saveLocationBtn = document.getElementById('saveLocationBtn');
    const deliveryAddressInput = document.getElementById('delivery_address');
    const pickupRadio = document.getElementById('pickup');
    const deliveryRadio = document.getElementById('delivery');
    const pickupDetails = document.getElementById('pickup-details');
    const deliveryDetails = document.getElementById('delivery-details');

    // Handle radio button changes
    function updateVisibility() {
        const isPickup = pickupRadio && pickupRadio.checked;
        
        if (pickupDetails) {
            pickupDetails.style.display = isPickup ? 'block' : 'none';
        }
        if (deliveryDetails) {
            deliveryDetails.style.display = isPickup ? 'none' : 'block';
        }
    }

    if (pickupRadio) {
        pickupRadio.addEventListener('change', updateVisibility);
    }
    if (deliveryRadio) {
        deliveryRadio.addEventListener('change', updateVisibility);
    }

    // Initialize visibility
    updateVisibility();
    
    // Update shipping method inheritance indicators for Status 3 products
    function updateShippingInheritance() {
        const cartItems = <?= json_encode($cart_items) ?>;
        let hasPickupOnly = false;
        let hasDeliveryOnly = false;
        
        // Check what types of products are in cart (considering both status_id and availtoday_status_id)
        cartItems.forEach(item => {
            // Check availtoday_status_id first, then fall back to status_id
            if (item.availtoday_status_id === 1 || (item.availtoday_status_id === null && item.status_id === 1)) {
                hasPickupOnly = true;
            }
            if (item.availtoday_status_id === 2 || (item.availtoday_status_id === null && item.status_id === 2)) {
                hasDeliveryOnly = true;
            }
        });
        
        // Determine inherited method
        let inheritedMethod = null;
        if (hasPickupOnly && !hasDeliveryOnly) {
            inheritedMethod = 'pickup';
        } else if (hasDeliveryOnly && !hasPickupOnly) {
            inheritedMethod = 'delivery';
        }
        
        // Update Status 3 product indicators (flexible products)
        document.querySelectorAll('.item[data-status-id="3"]').forEach(item => {
            const availtodayStatusId = item.getAttribute('data-availtoday-status-id');
            // Only update if availtoday_status_id is null (meaning it's flexible)
            if (availtodayStatusId === 'null') {
                const indicator = item.querySelector('.shipping-indicator');
                if (indicator && inheritedMethod) {
                    indicator.textContent = inheritedMethod === 'pickup' ? '→ Will be Pick Up' : '→ Will be Delivery';
                    indicator.style.display = 'inline-block';
                } else if (indicator) {
                    indicator.style.display = 'none';
                }
            }
        });
        
        console.log('Available Today - Shipping inheritance updated:', inheritedMethod);
    }
    
    // Initialize shipping inheritance
    updateShippingInheritance();

    if (setLocationBtn) {
        setLocationBtn.addEventListener('click', function() {
            modal.style.display = 'block';
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    if (saveLocationBtn) {
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
    }
});

// Coupon functions
async function checkCoupon() {
    const couponInput = document.getElementById('coupon_code');
    const checkBtn = document.getElementById('check_coupon_btn');
    const couponCode = couponInput.value.trim().toUpperCase();
    
    console.log('[COUPON] Starting checkCoupon function');
    console.log('[COUPON] Coupon code:', couponCode);
    console.log('[COUPON] Subtotal:', subtotal);
    
    if (!couponCode) {
        showCouponMessage('Please enter a coupon code');
        return;
    }
    
    // Disable button during request
    checkBtn.disabled = true;
    checkBtn.textContent = 'Checking...';
    
    try {
        console.log('[COUPON] Sending request to validate-coupon.php');
        const response = await fetch('../../../backend/pages/user-page-content/validate-coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: couponCode,
                subtotal: subtotal,
                cart_items: []
            })
        });
        
        console.log('[COUPON] Response status:', response.status);
        const result = await response.json();
        console.log('[COUPON] Response result:', result);
        
        if (result.success) {
            appliedCoupon = result.coupon;
            discountAmount = calculateDiscount(appliedCoupon, subtotal);
            
            console.log('[COUPON] Applied coupon:', appliedCoupon);
            console.log('[COUPON] Discount amount:', discountAmount);
            
            // Show applied coupon
            showAppliedCoupon(appliedCoupon);
            showCouponMessage(result.message, true);
            
            // Update totals
            updateOrderTotal();
            
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
            console.log('[COUPON] Validation failed:', result.message);
            showCouponMessage(result.message || 'Invalid coupon code');
        }
    } catch (error) {
        console.error('[COUPON] Error checking coupon:', error);
        showCouponMessage('Error checking coupon. Please try again.');
    } finally {
        checkBtn.disabled = false;
        checkBtn.textContent = 'Check Coupon';
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
    updateOrderTotal();
    
    showCouponMessage('Coupon removed successfully', true);
}

// Initialize coupon event listeners
document.addEventListener('DOMContentLoaded', function() {
    const checkCouponBtn = document.getElementById('check_coupon_btn');
    const removeCouponBtn = document.getElementById('remove_coupon_btn');
    const couponInput = document.getElementById('coupon_code');
    
    if (checkCouponBtn) {
        checkCouponBtn.addEventListener('click', checkCoupon);
    }
    
    if (removeCouponBtn) {
        removeCouponBtn.addEventListener('click', removeCoupon);
    }
    
    if (couponInput) {
        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                checkCoupon();
            }
        });
    }
});

// Phone input validation
const phoneInput = document.getElementById('phone');
if (phoneInput) {
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
}

// PayMongo Integration
let paymongoInstance;
let cardElement;

// Global variable to track if order is being processed
let orderProcessing = false;
let countdownTimer = null;

// Initialize PayMongo
document.addEventListener('DOMContentLoaded', function() {
    // Initialize PayMongo (we'll set the public key when needed)
    // paymongoInstance will be initialized when card payment is selected
    
    // Handle payment method changes
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(method => {
        method.addEventListener('change', handlePaymentMethodChange);
    });
    
    // Form submission handler with PayMongo integration - SAME AS checkout.php
    const checkoutForm = document.getElementById('availtoday-checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('[AVAILTODAY CHECKOUT] Form submit event triggered');
            
            // Prevent double submission
            if (orderProcessing) {
                console.log('[AVAILTODAY CHECKOUT] Already processing, aborting');
                return;
            }
            orderProcessing = true;
            
            try {
                console.log('[AVAILTODAY CHECKOUT] Starting payment process...');
                
                // Show loading state
                setLoadingState(true);
                
                // Get all form data
                const formData = new FormData();
                
                // Add cart items and user info
                const cartItems = <?php echo json_encode($cart_items); ?>;
                const cartTotal = <?php echo json_encode($cart_total); ?>;
                const userEmail = <?php echo json_encode($user['email'] ?? ''); ?>;
                const userName = <?php echo json_encode(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))); ?>;
                
                console.log('[AVAILTODAY CHECKOUT] Cart Items:', cartItems);
                console.log('[AVAILTODAY CHECKOUT] Cart Total:', cartTotal);
                console.log('[AVAILTODAY CHECKOUT] User Email:', userEmail);
                console.log('[AVAILTODAY CHECKOUT] User Name:', userName);
                
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
                const deliveryRadio = document.getElementById('delivery');
                const isDelivery = deliveryRadio && deliveryRadio.checked;
                const today = new Date();
                const todayStr = today.getFullYear() + '-' + 
                               String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(today.getDate()).padStart(2, '0');
                
                console.log('[AVAILTODAY CHECKOUT] Is Delivery:', isDelivery);
                console.log('[AVAILTODAY CHECKOUT] Today Date:', todayStr);
                
                formData.append('delivery_method', isDelivery ? 'delivery' : 'pickup');
                formData.append('delivery_date', todayStr);
                formData.append('pickup_date', todayStr);
                
                // Add delivery address if delivery is selected
                if (isDelivery) {
                    const deliveryAddress = document.getElementById('delivery_address');
                    if (deliveryAddress && deliveryAddress.value) {
                        formData.append('delivery_address', deliveryAddress.value);
                        console.log('[AVAILTODAY CHECKOUT] Delivery Address:', deliveryAddress.value);
                    } else {
                        throw new Error('Please enter your delivery address');
                    }
                }
                
                // Add contact number
                const contactNumber = document.getElementById('phone');
                if (contactNumber && contactNumber.value) {
                    formData.append('contact_number', contactNumber.value);
                } else {
                    throw new Error('Please enter your contact number');
                }
                
                // Add payment method
                const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
                if (!paymentMethodEl) {
                    throw new Error('Please select a payment method.');
                }
                formData.append('payment_method', paymentMethodEl.value);
                console.log('[AVAILTODAY CHECKOUT] Payment Method:', paymentMethodEl.value);
                
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
                    console.log('[AVAILTODAY CHECKOUT] Coupon Applied:', appliedCoupon);
                    console.log('[AVAILTODAY CHECKOUT] Discount Amount:', discountAmount);
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
                console.log('[AVAILTODAY CHECKOUT] DEBUG - appliedCoupon:', appliedCoupon);
                console.log('[AVAILTODAY CHECKOUT] DEBUG - discountAmount variable:', discountAmount);
                console.log('[AVAILTODAY CHECKOUT] DEBUG - typeof discountAmount:', typeof discountAmount);
                
                const finalAmount = cartTotal - (discountAmount || 0);
                console.log('[AVAILTODAY CHECKOUT] Cart Total:', cartTotal);
                console.log('[AVAILTODAY CHECKOUT] Discount Amount:', discountAmount);
                console.log('[AVAILTODAY CHECKOUT] Final Amount:', finalAmount);
                console.log('[AVAILTODAY CHECKOUT] Final Amount being sent to PayMongo:', parseFloat(finalAmount));
                
                // Prepare payment data for PayMongo
                const paymentData = {
                    payment_method: paymentMethodEl.value,
                    order_type: 'availtoday',  // Different from regular checkout
                    amount: parseFloat(finalAmount),
                    order_data: orderData
                };
                
                console.log('[AVAILTODAY CHECKOUT] Payment data being sent:', paymentData);
                
                // Process payment through PayMongo (same endpoint as regular checkout)
                const response = await fetch('process-payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(paymentData)
                });
                
                console.log('[AVAILTODAY CHECKOUT] Payment API response status:', response.status);
                
                const result = await response.json();
                console.log('[AVAILTODAY CHECKOUT] Payment API result:', result);
                
                if (result.success) {
                    if (result.payment_url) {
                        console.log('[AVAILTODAY CHECKOUT] Redirecting to payment URL:', result.payment_url);
                        // Redirect to PayMongo payment page
                        window.location.href = result.payment_url;
                    } else if (result.order_id) {
                        console.log('[AVAILTODAY CHECKOUT] Payment successful, redirecting to success page');
                        // Payment successful, redirect to success page
                        window.location.href = `payment-success.php?order_id=${result.order_id}`;
                    } else {
                        throw new Error('Payment processed but no redirect URL provided');
                    }
                } else {
                    throw new Error(result.message || 'Payment processing failed');
                }
                
            } catch (error) {
                console.error('[AVAILTODAY CHECKOUT] Payment error:', error);
                console.log('[LOADING STATE] Resetting loading state due to error');
                alert('An error occurred while processing your payment: ' + error.message);
                setLoadingState(false);
                orderProcessing = false;
            }
        });
    }
});

// Loading state functions
function setLoadingState(isLoading) {
    const submitButton = document.querySelector('button[type="submit"]');
    if (!submitButton) {
        console.error('Submit button not found!');
        return;
    }
    
    console.log('[LOADING STATE] Setting loading state:', isLoading, 'for button:', submitButton);
    
    const buttonText = submitButton.querySelector('.button-text') || submitButton;
    
    if (isLoading) {
        orderProcessing = true;
        submitButton.disabled = true;
        submitButton.classList.add('btn-processing');
        
        // Add spinner and text
        buttonText.innerHTML = '<span class="spinner"></span>Processing Order...';
        submitButton.style.opacity = '0.7';
        submitButton.style.cursor = 'not-allowed';
        
        console.log('[LOADING STATE] Button updated with loading state:', buttonText.innerHTML);
        
        // Start 20-second countdown to re-enable button
        startCountdownTimer(submitButton, buttonText);
    } else {
        // Only re-enable if not in countdown mode
        if (!orderProcessing) {
            submitButton.disabled = false;
            submitButton.classList.remove('btn-processing');
            buttonText.textContent = 'Place Order - ₱<?php echo number_format($cart_total, 2); ?>';
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
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
            buttonText.textContent = 'Place Order - ₱<?php echo number_format($cart_total, 2); ?>';
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
            
            // Clear the timer
            clearInterval(countdownTimer);
            countdownTimer = null;
            
            console.log('Order button re-enabled after countdown completed');
        }
    };
    
    // Start the countdown immediately
    updateCountdown();
    
    // Continue countdown every second
    countdownTimer = setInterval(updateCountdown, 1000);
}

// Handle payment method selection
function handlePaymentMethodChange(e) {
    const cardForm = document.getElementById('card-payment-form');
    
    if (e.target.value === 'card') {
        cardForm.style.display = 'block';
        initializeCardElement();
    } else {
        cardForm.style.display = 'none';
        if (cardElement) {
            cardElement.unmount();
            cardElement = null;
        }
    }
}

// Initialize PayMongo card element
async function initializeCardElement() {
    try {
        // We'll get the public key from the server when processing payment
        // For now, just prepare the card element container
        const cardElementContainer = document.getElementById('card-element');
        cardElementContainer.innerHTML = `
            <div class="card-field-group">
                <div class="card-field">
                    <label>Card Number</label>
                    <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="card-field-row">
                    <div class="card-field">
                        <label>Expiry Date</label>
                        <input type="text" id="card-expiry" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="card-field">
                        <label>CVC</label>
                        <input type="text" id="card-cvc" placeholder="123" maxlength="4">
                    </div>
                </div>
            </div>
        `;
        
        // Add input formatting
        formatCardInputs();
        
    } catch (error) {
        console.error('Error initializing card element:', error);
    }
}

// Format card inputs
function formatCardInputs() {
    const cardNumber = document.getElementById('card-number');
    const cardExpiry = document.getElementById('card-expiry');
    const cardCvc = document.getElementById('card-cvc');
    
    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.substring(0, 16);
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = value;
        });
    }
    
    if (cardExpiry) {
        cardExpiry.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }
    
    if (cardCvc) {
        cardCvc.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
        });
    }
}

// Handle card payment with PayMongo (kept for card payment support)
async function handleCardPayment(paymentResult) {
    try {
        // Initialize PayMongo with public key
        paymongoInstance = new PayMongo(paymentResult.public_key);
        
        // Get card details
        const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
        const cardExpiry = document.getElementById('card-expiry').value;
        const cardCvc = document.getElementById('card-cvc').value;
        
        // Parse expiry
        const [expMonth, expYear] = cardExpiry.split('/');
        
        // Create payment method
        const paymentMethod = await paymongoInstance.createPaymentMethod({
            type: 'card',
            details: {
                card_number: cardNumber,
                exp_month: parseInt(expMonth),
                exp_year: parseInt('20' + expYear),
                cvc: cardCvc
            }
        });
        
        if (paymentMethod.error) {
            throw new Error(paymentMethod.error.message);
        }
        
        // Confirm payment intent
        const result = await paymongoInstance.confirmPaymentIntent(
            paymentResult.payment_intent_id,
            {
                payment_method: paymentMethod.paymentMethod.id,
                return_url: window.location.origin + '/frontend/pages/cart/payment-return.php?type=availtoday'
            }
        );
        
        if (result.error) {
            throw new Error(result.error.message);
        }
        
        // Handle 3D Secure redirect if needed
        if (result.paymentIntent.status === 'requires_action') {
            window.location.href = result.paymentIntent.next_action.redirect.url;
        } else if (result.paymentIntent.status === 'succeeded') {
            // Payment successful
            window.location.href = 'payment-return.php?type=availtoday&status=success';
        }
        
    } catch (error) {
        console.error('Card payment error:', error);
        throw error;
    }
}

// Get form data
function getFormData() {
    const form = document.getElementById('availtoday-checkout-form');
    const formData = new FormData(form);
    
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Add customer name
    data.customer_name = (data.first_name || '') + ' ' + (data.last_name || '');
    data.customer_email = data.email;
    
    return data;
}

// Validate form
function validateForm() {
    const form = document.getElementById('availtoday-checkout-form');
    const requiredFields = form.querySelectorAll('input[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#f44336';
            isValid = false;
        } else {
            field.style.borderColor = '#ddd';
        }
    });
    
    // Validate card fields if card payment is selected
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    if (paymentMethod === 'card') {
        const cardNumber = document.getElementById('card-number');
        const cardExpiry = document.getElementById('card-expiry');
        const cardCvc = document.getElementById('card-cvc');
        
        if (!cardNumber.value || cardNumber.value.replace(/\s/g, '').length < 16) {
            cardNumber.style.borderColor = '#f44336';
            isValid = false;
        }
        
        if (!cardExpiry.value || cardExpiry.value.length < 5) {
            cardExpiry.style.borderColor = '#f44336';
            isValid = false;
        }
        
        if (!cardCvc.value || cardCvc.value.length < 3) {
            cardCvc.style.borderColor = '#f44336';
            isValid = false;
        }
    }
    
    if (!isValid) {
        alert('Please fill in all required fields correctly.');
    }
    
    return isValid;
}

// Additional styles for Available Today specific elements
const additionalStyles = `
<style>
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

.btn-check-coupon {
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

.btn-check-coupon:hover {
    background: #1a4a28;
}

.btn-check-coupon:disabled {
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
    
    .btn-check-coupon {
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

/* Shipping Method Notice */
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

/* Delivery Type Radio Buttons */
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

/* Disabled Radio Button Styles */
.radio-option input[type="radio"]:disabled + span {
    color: #999;
    cursor: not-allowed;
}

.radio-option input[type="radio"]:disabled {
    cursor: not-allowed;
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

.delivery-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.method-notice {
    background: #e8f5e8;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #4CAF50;
    margin: 15px 0;
}

.auto-assigned-note {
    font-style: italic;
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.auto-assigned {
    color: #666;
    font-size: 12px;
    font-style: italic;
}

.item-method {
    margin: 5px 0;
    font-size: 14px;
    color: #555;
}

.available-days {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #666;
}

.mixed-status-notice {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
}

.mixed-status-notice h2 {
    color: #856404;
    margin-bottom: 10px;
}

/* PayMongo Payment Styles */
.payment-option-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.payment-desc {
    color: #666;
    font-size: 12px;
}

.card-payment-form {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.card-field-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.card-field-row {
    display: flex;
    gap: 15px;
}

.card-field {
    flex: 1;
}

.card-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.card-field input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.card-field input:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.card-errors {
    color: #f44336;
    font-size: 14px;
    margin-top: 10px;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

#loading-spinner {
    display: inline-flex;
    align-items: center;
    gap: 8px;
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

/* Ensure no duplicate circles */
.place-order-btn::after,
.place-order-btn::before {
    display: none !important;
}

.btn-processing {
    position: relative;
    pointer-events: none;
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

.btn-primary:disabled:hover,
.place-order-btn:disabled:hover {
    background-color: #6c757d !important;
    transform: none !important;
    box-shadow: none !important;
    border-color: #6c757d !important;
}

@media (max-width: 768px) {
    .card-field-row {
        flex-direction: column;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', additionalStyles);
</script>

<?php
include '../../user-includes/footer.php';
?>
</body>
</html>
