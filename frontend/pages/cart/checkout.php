<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login for checkout - check for user role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

$page_title = "Checkout";
$additional_css = [
    "../../css/users/checkout.css"
];

require_once "../../user-includes/user-header.php";

// Debug session data
error_log("Session data at start: " . print_r($_SESSION, true));

// Include database connection
require_once '../../user-includes/database.php';

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
}

// If no items in POST, check session
if (empty($selected_cart_ids) && isset($_SESSION['selected_cart_ids'])) {
    $selected_cart_ids = $_SESSION['selected_cart_ids'];
    $subtotal = $_SESSION['subtotal'] ?? 0;
}

// Debug cart data
error_log("Selected cart IDs: " . print_r($selected_cart_ids, true));
error_log("Subtotal: " . $subtotal);

// If still no items selected, redirect back to cart
if (empty($selected_cart_ids)) {
    error_log("No cart items found - redirecting to cart");
    header("Location: cart.php");
    exit();
}

// Store cart selection in session for persistence
$_SESSION['selected_cart_ids'] = $selected_cart_ids;
$_SESSION['subtotal'] = $subtotal;

// Get cart items details
$cart_total = 0;
$cart_items = [];

if (!empty($selected_cart_ids)) {
    try {
        $placeholders = str_repeat('?,', count($selected_cart_ids) - 1) . '?';
        $cart_sql = "SELECT c.*, p.name, p.price 
                     FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                     WHERE c.id IN ($placeholders)";
        
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
                        'cart_id' => $item['id']
                    ];
                }
            }
            $cart_stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error fetching cart items: " . $e->getMessage());
    }
}

// Store cart items in session
$_SESSION['cart_items'] = $cart_items;
$_SESSION['cart_total'] = $cart_total;

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
  <script>
    console.log('PHP Debug Information:', <?php echo json_encode($debug_info); ?>);
  </script>
  <link rel="stylesheet" href="../../css/users/checkout.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Declare calendar variables in the outer scope
        let pickupCalendar, deliveryCalendar;
        const pickupCalendarEl = document.getElementById('calendar');
        const deliveryCalendarEl = document.getElementById('delivery-calendar');
        const pickupRadio = document.getElementById('pickup');
        const deliveryRadio = document.getElementById('delivery');
        const pickupDetails = document.getElementById('pickup-details');
        const deliveryDetails = document.getElementById('delivery-details');
        const addressInput = document.getElementById('customer_address');
        const shippingFeeDisplay = document.getElementById('shipping_fee');
        const totalAmountDisplay = document.getElementById('total_amount');
        const subtotal = <?= json_encode($cart_total) ?>;

        // Global variables
        let calendar;
        let dateLimits = {};

        function updateCalendarCell(arg) {
            const cellDate = new Date(arg.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Reset cell styles
            arg.el.style.backgroundColor = '#ffffff';  // Default white background
            arg.el.style.color = '#333';
            arg.el.style.cursor = '';
            arg.el.title = '';
            arg.el.classList.remove('fc-day-disabled', 'not-accepting-orders');
            
            const existingOverlay = arg.el.querySelector('.not-accepting-overlay');
            if (existingOverlay) {
                existingOverlay.remove();
            }
            
            if (cellDate < today) {
                arg.el.style.backgroundColor = '#999594';
                arg.el.style.color = 'black';
                arg.el.style.cursor = 'not-allowed';
                arg.el.setAttribute('aria-disabled', 'true');
                arg.el.style.pointerEvents = 'none';
                return;
            }

            const dateStr = arg.date.toISOString().split('T')[0];
            if (dateLimits[dateStr]) {
                const dateInfo = dateLimits[dateStr];
                
                if (dateInfo.limit === 0 || dateInfo.status === 'not_accepting' || dateInfo.is_full) {
                    arg.el.style.backgroundColor = '#999594';
                    arg.el.style.color = '#c62828';
                    arg.el.style.cursor = 'not-allowed';
                    arg.el.title = dateInfo.is_full ? `No available slots (${dateInfo.active_orders}/${dateInfo.limit} orders)` : 'Not Accepting Orders';
                    arg.el.classList.add('not-accepting-orders');
                    arg.el.setAttribute('aria-disabled', 'true');
                    arg.el.style.pointerEvents = 'none';
                    
                    const overlay = document.createElement('div');
                    overlay.className = 'not-accepting-overlay';
                    overlay.innerHTML = '✕';
                    arg.el.appendChild(overlay);
                } else {
                    const remaining = dateInfo.remaining_slots;
                    arg.el.title = `${remaining} slot${remaining !== 1 ? 's' : ''} available`;
                    
                    if (dateInfo.count > 0) {
                        arg.el.style.backgroundColor = '#e8f5e9';  // Light green for dates with orders
                        arg.el.style.color = '#2e7d32';
                    }
                }
            }
        }

        function initializeCalendars() {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const commonConfig = {
                initialView: 'dayGridMonth',
                selectable: true,
                headerToolbar: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                validRange: {
                    start: today
                },
                dayCellDidMount: function(arg) {
                    const cellDate = new Date(arg.date);
                    cellDate.setHours(0, 0, 0, 0);
                    if (cellDate < today) {
                        arg.el.style.backgroundColor = '#999594';
                        arg.el.style.color = '#999';
                        arg.el.style.cursor = 'not-allowed';
                        arg.el.classList.add('past-date');
                        arg.el.setAttribute('aria-disabled', 'true');
                        arg.el.style.pointerEvents = 'none'; // Make it not interactable
                        return;
                    }
                    updateCalendarCell(arg);
                },
                datesSet: function(arg) {
                    fetchDateLimits(arg.start, arg.end);
                }
            };

            pickupCalendar = new FullCalendar.Calendar(pickupCalendarEl, {
                ...commonConfig,
                dateClick: function(info) {
                    handleDateClick(info, 'pickup');
                }
            });

            deliveryCalendar = new FullCalendar.Calendar(deliveryCalendarEl, {
                ...commonConfig,
                dateClick: function(info) {
                    handleDateClick(info, 'delivery');
                }
            });

            if (pickupCalendar && pickupCalendarEl) {
                pickupCalendar.render();
            }
            if (deliveryCalendar && deliveryCalendarEl) {
                deliveryCalendar.render();
            }
        }

        function handleDateClick(info, type) {
            const clickedDate = new Date(info.dateStr);
            clickedDate.setHours(0, 0, 0, 0);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (clickedDate < today) {
                // Do nothing for past dates
                return;
            }
            
            const dateStr = info.dateStr;
            const dateInfo = dateLimits[dateStr];
            
            if (dateInfo && (dateInfo.limit === 0 || dateInfo.status === 'not_accepting' || dateInfo.is_full)) {
                // Do nothing for not accepting or full dates
                return;
            }
            
            const dateInput = document.getElementById(type === 'pickup' ? 'pickup_date' : 'delivery_date');
            if (dateInput) {
                dateInput.value = dateStr;
            }
        }

        function fetchDateLimits(start, end) {
            const startStr = start.toISOString().split('T')[0];
            const endStr = end.toISOString().split('T')[0];
            
            fetch("../../php/admin/get-date-limits.php?start=${startStr}&end=${endStr}", {
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
                        
                        if (data.success) {
                            dateLimits = {};
                            
                            data.dates.forEach(date => {
                                dateLimits[date.date] = {
                                    limit: parseInt(date.limit),
                                    count: parseInt(date.current_orders) || 0,
                                    is_full: date.is_full || parseInt(date.current_orders) >= parseInt(date.limit),
                                    active_orders: parseInt(date.active_orders) || 0,
                                    remaining_slots: parseInt(date.limit) - (parseInt(date.current_orders) || 0),
                                    status: date.status || (parseInt(date.limit) === 0 ? 'not_accepting' : 'accepting')
                                };
                            });

                            const cells = document.querySelectorAll('.fc-daygrid-day');
                            cells.forEach(cell => {
                                cell.style.backgroundColor = '#ffffff';  // Default white
                                cell.style.color = '#333';
                                cell.style.cursor = '';
                                cell.title = '';
                                cell.classList.remove('fc-day-disabled', 'not-accepting-orders');
                                
                                const overlay = cell.querySelector('.not-accepting-overlay');
                                if (overlay) {
                                    overlay.remove();
                                }
                                
                                const dateStr = cell.getAttribute('data-date');
                                if (dateStr) {
                                    updateCalendarCell({
                                        date: new Date(dateStr),
                                        el: cell
                                    });
                                }
                            });
                        } else {
                            console.error('Server returned error:', data.error);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
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
                if (isPickup && pickupCalendar && pickupCalendarEl) {
                    pickupCalendar.render();
                } else if (!isPickup && deliveryCalendar && deliveryCalendarEl) {
                    deliveryCalendar.render();
                }
            } catch (error) {
                console.error('Error rendering calendar:', error);
            }
            
            const shippingFee = isPickup ? 0 : 50;
            if (shippingFeeDisplay) {
                shippingFeeDisplay.textContent = '₱' + shippingFee.toFixed(2);
            }
            
            if (totalAmountDisplay) {
                totalAmountDisplay.textContent = '₱' + (subtotal + shippingFee).toFixed(2);
            }
            
            if (addressInput) {
                addressInput.required = !isPickup;
                if (isPickup) {
                    addressInput.value = "Pickup at store";
                } else if (addressInput.value === "Pickup at store") {
                    addressInput.value = "";
                }
            }
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
            deliveryRadio.addEventListener('change', updateVisibility);
        }

        try {
            initializeCalendars();
            initializeTimeInputs();
            updateVisibility();
        } catch (error) {
            console.error('Error during initialization:', error);
        }

        // Debug cart data before form submission
        console.log('Cart Items:', <?php echo json_encode($cart_items); ?>);
        console.log('Cart Total:', <?php echo json_encode($cart_total); ?>);
        console.log('Selected Cart IDs:', <?php echo json_encode($selected_cart_ids); ?>);

        // Form submission handler
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    // Debug form data
                    console.log('Form submission started');
                    
                    // Get all form data
                    const formData = new FormData(this);
                    
                    // Add cart items and user info - with debug logging
                    const cartItems = <?php echo json_encode($cart_items); ?>;
                    const cartTotal = <?php echo json_encode($cart_total); ?>;
                    console.log('Cart Items in submission:', cartItems);
                    console.log('Cart Total in submission:', cartTotal);
                    
                    const userEmail = <?php echo json_encode($user['email']); ?>;
                    const userName = <?php echo json_encode($user['firstname'] . ' ' . $user['lastname']); ?>;
                    console.log('User Email:', userEmail);
                    console.log('User Name:', userName);
                    
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
                    
                    // Debug formData
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                    
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
                    
                    // Disable submit button to prevent double submission
                    const submitButton = checkoutForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                    }
                    
                    console.log('Sending form data to server...');
                    const response = await fetch("../../php/users/process_order.php", {
                        method: 'POST',
                        body: formData
                    });
                    
                    const responseText = await response.text();
                    console.log('Server response:', responseText);
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('Failed to parse response:', responseText);
                        throw new Error('Invalid server response');
                    }
                    
                    if (result.success) {
                        alert('Order placed successfully! You will be redirected to your order receipt.');
                        window.location.href = result.receipt_url;
                    } else {
                        throw new Error(result.message || 'Failed to place order');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while placing your order: ' + error.message);
                } finally {
                    // Re-enable submit button
                    const submitButton = checkoutForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
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
                           pattern="[0-9]{11}" 
                           title="Please enter a valid 11-digit phone number">
                </div>
            </div>
        </div>

        <!-- Combined Shipping Options & Details -->
        <div class="section-card shipping-details">
            <h2>Shipping Options</h2>
            <div class="delivery-type">
                <label class="radio-option">
                    <input type="radio" id="pickup" name="delivery_method" value="pickup" checked>
                    <span>Pick Up</span>
                </label>
                <label class="radio-option">
                    <input type="radio" id="delivery" name="delivery_method" value="delivery">
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
                <div class="calendar-section">
                    <div id="delivery-calendar"></div>
                </div>
                <div class="datetime-inputs">
                    <div class="form-group">
                        <label for="delivery_date">Delivery Date:</label>
                        <input type="text" id="delivery_date" name="delivery_date" readonly required>
                    </div>
                    <div class="form-group">
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
<script src="../../js/users/ph-address-selector.js"></script>

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
</script>

<script src="../../js/users/checkout.js"></script>

<?php
include '../../user-includes/footer.php';
?>
</body>
</html>
