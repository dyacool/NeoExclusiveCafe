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
require_once '../../user-includes/database.php';

// Test database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed");
}

// Initialize user array with default values
$user = array(
    'firstname' => $_SESSION['user_firstname'] ?? '',
    'lastname' => $_SESSION['user_lastname'] ?? '',
    'email' => null
);

// Get user data from session
if (isset($_SESSION['session_data']) && isset($_SESSION['session_data']['user_data'])) {
    $user = $_SESSION['session_data']['user_data'];
} elseif (isset($_SESSION['user_data'])) {
    $user = $_SESSION['user_data'];
}

// If still no email, try to get from database
if (empty($user['email']) && isset($_SESSION['user_id'])) {
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
            }
            $user_stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }
}

// Get Available Today cart items from cart_availtoday table
$cart_total = 0;
$cart_items = [];
$shipping_method = 'pickup'; // Default
$has_mixed_availtoday_status = false;
$availtoday_status_counts = ['pickup' => 0, 'delivery' => 0, 'null' => 0];

try {
    // Get cart items with availtoday_status information
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
                 FROM cart_availtoday ca
                 JOIN products p ON ca.product_id = p.id
                 LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                 LEFT JOIN product_day pd ON p.id = pd.product_id
                 WHERE ca.user_id = ? AND p.status_id = 3 AND p.deleted_at IS NULL
                 GROUP BY ca.id";
    
    $cart_stmt = $conn->prepare($cart_sql);
    if ($cart_stmt) {
        $cart_stmt->bind_param("i", $_SESSION['user_id']);
        
        if ($cart_stmt->execute()) {
            $cart_result = $cart_stmt->get_result();
            
            while ($item = $cart_result->fetch_assoc()) {
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
if ($availtoday_status_counts['delivery'] > 0 && $availtoday_status_counts['pickup'] > 0) {
    $has_mixed_availtoday_status = true;
    $shipping_method = 'mixed'; // We'll handle this case in the UI
} elseif ($availtoday_status_counts['delivery'] > 0) {
    $shipping_method = 'delivery';
} else {
    $shipping_method = 'pickup';
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
    <form id="availtoday-checkout-form" method="POST" action="process-availtoday-checkout.php">
        
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
                            $email = $user['email'] ?? 'Email not available';
                            echo htmlspecialchars($email);
                        ?>
                    </span>
                    <input type="hidden" name="email" value="<?php 
                        echo !empty($user['email']) ? htmlspecialchars($user['email']) : '';
                    ?>">
                    <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($user['firstname']); ?>">
                    <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($user['lastname']); ?>">
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

        <!-- Shipping Options & Details (Auto-assigned) -->
        <div class="section-card shipping-details">
            <h2>Delivery Method</h2>
            
            <?php if ($has_mixed_availtoday_status): ?>
                <div class="method-notice">
                    <p><strong>Mixed Methods:</strong> Your items have different delivery methods as shown in the order summary below.</p>
                </div>
            <?php else: ?>
                <div class="method-notice">
                    <p><strong>Method: <?php echo ucfirst($shipping_method); ?></strong></p>
                    <p class="auto-assigned-note">(Auto-assigned based on product availability - no calendar or time selection needed for Available Today items)</p>
                </div>
            <?php endif; ?>
            
            <!-- Hidden input to pass shipping method -->
            <input type="hidden" name="shipping_method" value="<?php echo htmlspecialchars($shipping_method); ?>">

            <!-- Delivery Address (only show if delivery items exist) -->
            <?php if ($availtoday_status_counts['delivery'] > 0 || $shipping_method === 'delivery'): ?>
            <div class="delivery-content" id="delivery-address-section">
                <div class="address-section">
                    <input type="text" id="address" name="address" 
                           placeholder="Enter delivery address" required>
                    <button type="button" id="setLocationBtn" class="btn-secondary">Set Location</button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Summary -->
        <div class="section-card order-summary">
            <h2>Order Summary</h2>
            
            <div class="summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="item">
                        <div class="item-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="quantity">Quantity: <?= $item['quantity'] ?></p>
                            <p class="item-method">
                                <?php if ($item['availtoday_status_id']): ?>
                                    <strong><?php echo htmlspecialchars($item['availtoday_status_name']); ?></strong>
                                <?php else: ?>
                                    <strong><?php echo $item['status_id'] == 1 ? 'Pick Up' : 'Delivery'; ?></strong>
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
                    </div>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="maya" id="maya">
                    <div class="payment-option-content">
                        <span class="payment-text">Maya</span>
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
            <textarea id="special_instructions" name="special_instructions" 
                      placeholder="Add any special instructions or notes here (optional)"></textarea>
        </div>

        <!-- Hidden fields for cart data -->
        <input type="hidden" name="cart_items" value="<?php echo htmlspecialchars(json_encode($cart_items)); ?>">
        <input type="hidden" name="cart_total" value="<?php echo $cart_total; ?>">
        <input type="hidden" name="has_mixed_status" value="<?php echo $has_mixed_availtoday_status ? '1' : '0'; ?>">

        <!-- Place Order Button -->
        <button type="submit" class="btn-primary place-order-btn" style="background-color: #256035;">
            Place Order - ₱<?php echo number_format($cart_total, 2); ?>
        </button>
    </form>
</div>

<!-- Location Modal (only if delivery items exist) -->
<?php if ($availtoday_status_counts['delivery'] > 0 || $shipping_method === 'delivery'): ?>
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
<?php endif; ?>

<script>
// Modal functionality (only if delivery items exist)
<?php if ($availtoday_status_counts['delivery'] > 0 || $shipping_method === 'delivery'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('locationModal');
    const setLocationBtn = document.getElementById('setLocationBtn');
    const closeBtn = document.querySelector('.close-btn');
    const saveLocationBtn = document.getElementById('saveLocationBtn');
    const deliveryAddressInput = document.getElementById('address');

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
    }
});
<?php endif; ?>

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

// Form submission handler
const checkoutForm = document.getElementById('availtoday-checkout-form');
if (checkoutForm) {
    checkoutForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            // Get all form data
            const formData = new FormData(this);
            
            // Add cart items and user info
            const cartItems = <?php echo json_encode($cart_items); ?>;
            const cartTotal = <?php echo json_encode($cart_total); ?>;
            
            // Validate cart items
            if (!cartItems || !Array.isArray(cartItems) || cartItems.length === 0) {
                throw new Error('Please ensure you have items in your cart before proceeding with checkout.');
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.place-order-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Processing Order...';
            submitBtn.disabled = true;
            
            // Submit the form
            this.submit();
            
        } catch (error) {
            console.error('Checkout error:', error);
            alert(error.message || 'An error occurred during checkout. Please try again.');
            
            // Reset button state
            const submitBtn = this.querySelector('.place-order-btn');
            if (submitBtn) {
                submitBtn.textContent = submitBtn.textContent.replace('Processing Order...', originalText || 'Place Order');
                submitBtn.disabled = false;
            }
        }
    });
}

// Additional styles for Available Today specific elements
const additionalStyles = `
<style>
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
</style>
`;

document.head.insertAdjacentHTML('beforeend', additionalStyles);
</script>

<?php
include '../../user-includes/footer.php';
?>
</body>
</html>
