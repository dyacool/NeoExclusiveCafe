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
        <button type="button" id="place-order-btn" class="btn-primary place-order-btn" style="background-color: #256035;">
            <span id="button-text">Place Order - ₱<?php echo number_format($cart_total, 2); ?></span>
            <span id="loading-spinner" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
                Processing...
            </span>
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

<!-- PayMongo SDK -->
<script src="https://js.paymongo.com/v1"></script>

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

// PayMongo Integration
let paymongoInstance;
let cardElement;

// Initialize PayMongo
document.addEventListener('DOMContentLoaded', function() {
    // Initialize PayMongo (we'll set the public key when needed)
    // paymongoInstance will be initialized when card payment is selected
    
    // Handle payment method changes
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(method => {
        method.addEventListener('change', handlePaymentMethodChange);
    });
    
    // Handle place order button
    const placeOrderBtn = document.getElementById('place-order-btn');
    if (placeOrderBtn) {
        placeOrderBtn.addEventListener('click', handlePlaceOrder);
    }
});

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

// Handle place order
async function handlePlaceOrder() {
    try {
        // Show loading state
        setLoadingState(true);
        
        // Validate form
        if (!validateForm()) {
            setLoadingState(false);
            return;
        }
        
        // Get form data
        const formData = getFormData();
        
        // Get selected payment method
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // Prepare payment data
        const paymentData = {
            payment_method: paymentMethod,
            order_type: 'availtoday',
            amount: <?php echo $cart_total; ?>,
            order_data: formData
        };
        
        console.log('Payment data being sent:', paymentData);
        
        // Clean the data to remove any potential circular references
        const cleanPaymentData = JSON.parse(JSON.stringify(paymentData));
        console.log('Cleaned payment data:', cleanPaymentData);
        
        // Process payment
        const paymentResult = await processPayment(cleanPaymentData);
        
        if (paymentResult.success) {
            if (paymentResult.payment_type === 'source') {
                // Redirect to PayMongo checkout for GCash/Maya
                window.location.href = paymentResult.checkout_url;
            } else if (paymentResult.payment_type === 'payment_intent') {
                // Handle card payment confirmation
                await handleCardPayment(paymentResult);
            }
        } else {
            throw new Error(paymentResult.error || 'Payment processing failed');
        }
        
    } catch (error) {
        console.error('Order processing error:', error);
        alert(error.message || 'An error occurred while processing your order. Please try again.');
        setLoadingState(false);
    }
}

// Process payment through backend
async function processPayment(paymentData) {
    const jsonString = JSON.stringify(paymentData);
    console.log('JSON string being sent:', jsonString);
    console.log('JSON string length:', jsonString.length);
    
    const response = await fetch('process-payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: jsonString
    });
    
    console.log('Response status:', response.status);
    console.log('Response headers:', response.headers);
    
    const responseText = await response.text();
    console.log('Raw response:', responseText);
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    try {
        return JSON.parse(responseText);
    } catch (e) {
        console.error('Failed to parse response JSON:', e);
        throw new Error('Invalid response format');
    }
}

// Handle card payment with PayMongo
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

// Set loading state
function setLoadingState(loading) {
    const buttonText = document.getElementById('button-text');
    const loadingSpinner = document.getElementById('loading-spinner');
    const placeOrderBtn = document.getElementById('place-order-btn');
    
    if (loading) {
        buttonText.style.display = 'none';
        loadingSpinner.style.display = 'inline-flex';
        placeOrderBtn.disabled = true;
    } else {
        buttonText.style.display = 'inline';
        loadingSpinner.style.display = 'none';
        placeOrderBtn.disabled = false;
    }
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

.place-order-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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
