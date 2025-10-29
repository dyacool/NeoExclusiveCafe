<?php
// Session management is handled by included files
// No need to start session here as it's already started by the included files

require_once '../../../backend/pages/admin-includes/database.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

require_once '../../user-includes/navbar/customer-navigation.php';
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Auto-truncate same-day cart if business is closed OR items are from previous days
function checkAndTruncateSameDayCart($conn) {
    $truncated = false;
    
    // STEP 1: Remove old date assignments from products (date-based cleanup)
    // Remove dates from previous days so products are no longer marked for same-day delivery
    
    // Remove old dates from Today's products table
    $old_todays_dates = "DELETE FROM todays_products_dates WHERE available_date < CURDATE()";
    $result1 = $conn->query($old_todays_dates);
    $removed_todays = ($result1 && $conn->affected_rows > 0) ? $conn->affected_rows : 0;
    
    // Remove old dates from regular products' today dates table
    $old_regular_dates = "DELETE FROM regular_products_today_dates WHERE available_date < CURDATE()";
    $result2 = $conn->query($old_regular_dates);
    $removed_regular = ($result2 && $conn->affected_rows > 0) ? $conn->affected_rows : 0;
    
    $total_removed = $removed_todays + $removed_regular;
    if ($total_removed > 0) {
        error_log("Auto-cleanup (cart.php): Removed $removed_todays old dates from todays_products_dates, $removed_regular from regular_products_today_dates");
        $truncated = true;
    }
    
    // STEP 1B: Clean up cart items for products that no longer have valid same-day dates
    $cleanup_cart = "DELETE FROM availtoday_cart WHERE DATE(created_at) < CURDATE()";
    $cleanup_result = $conn->query($cleanup_cart);
    if ($cleanup_result && $conn->affected_rows > 0) {
        error_log("Auto-cleanup (cart.php): Removed {$conn->affected_rows} old cart items from previous days");
        $truncated = true;
    }
    
    // STEP 2: Check if business is closed (time-based truncation)
    $hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $hours_result = $conn->query($hours_query);
    
    if ($hours_result && $hours_result->num_rows > 0) {
        $hours = $hours_result->fetch_assoc();
        $opening_time = $hours['opening_time'];
        $closing_time = $hours['closing_time'];
        $current_time = date('H:i:s');
        
        // Convert to minutes for comparison
        $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
        $opening_minutes = (intval(substr($opening_time, 0, 2)) * 60) + intval(substr($opening_time, 3, 2));
        $closing_minutes = (intval(substr($closing_time, 0, 2)) * 60) + intval(substr($closing_time, 3, 2));
        
        // Check if business is closed
        // Business is OPEN only if current time is between opening and closing
        $is_closed = false;
        
        // Handle midnight crossing (e.g., closing time is after midnight)
        if ($closing_minutes < $opening_minutes) {
            // Business hours cross midnight (e.g., 8 PM to 2 AM)
            $is_closed = !($current_minutes >= $opening_minutes || $current_minutes < $closing_minutes);
        } else {
            // Normal business hours (e.g., 8 AM to 6 PM)
            $is_closed = !($current_minutes >= $opening_minutes && $current_minutes < $closing_minutes);
        }
        
        // Truncate remaining cart items if closed
        if ($is_closed) {
            $count_query = "SELECT COUNT(*) as count FROM availtoday_cart";
            $count_result = $conn->query($count_query);
            if ($count_result) {
                $count_data = $count_result->fetch_assoc();
                if ($count_data['count'] > 0) {
                    // Truncate the cart
                    $conn->query("TRUNCATE TABLE availtoday_cart");
                    error_log("Auto-truncate (cart.php): Cart cleared (business closed - hours: $opening_time to $closing_time, current time: $current_time)");
                    $truncated = true;
                }
            }
        }
    }
    
    return $truncated;
}

// Run the auto-truncate check
$cart_was_truncated = checkAndTruncateSameDayCart($conn);

// Get Pre-Order items (from cart table)
$preorder_query = "
    SELECT c.id AS cart_id, c.quantity, c.price, c.product_id,
           p.name AS product_name, p.quantity as product_stock, p.status_id,
           ps.name as status_name,
           (SELECT COALESCE(cloud_url, image_url) FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_statuses ps ON p.status_id = ps.id
    WHERE c.user_id = ? AND p.deleted_at IS NULL
    ORDER BY p.name ASC
";
$preorder_stmt = $conn->prepare($preorder_query);
$preorder_stmt->bind_param("i", $user_id);
$preorder_stmt->execute();
$preorder_result = $preorder_stmt->get_result();
$preorder_items = $preorder_result->fetch_all(MYSQLI_ASSOC);
$preorder_stmt->close();

// Get Same Day Order items (from availtoday_cart table)
$sameday_query = "
    SELECT c.id AS cart_id, c.quantity, c.product_id,
           p.name AS product_name, p.price, p.quantity as product_stock, p.status_id,
           ps.name as status_name,
           (SELECT COALESCE(cloud_url, image_url) FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
    FROM availtoday_cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_statuses ps ON p.status_id = ps.id
    WHERE c.user_id = ? AND p.deleted_at IS NULL
    ORDER BY p.name ASC
";
$sameday_stmt = $conn->prepare($sameday_query);
$sameday_stmt->bind_param("i", $user_id);
$sameday_stmt->execute();
$sameday_result = $sameday_stmt->get_result();
$sameday_items = $sameday_result->fetch_all(MYSQLI_ASSOC);
$sameday_stmt->close();

// Determine current shipping method from cart
$current_shipping_method = null;
foreach ($preorder_items as $item) {
    if ($item['status_id'] == 1 || $item['status_id'] == 2) {
        $current_shipping_method = $item['status_id'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="cart.css">

</head>
<body>

<?php include '../../user-includes/user-header.php'; ?>

<div class="wrapper fade-in">
    <div class="main-container">
        <h2>Shopping Cart</h2>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div style="background: #f44336; color: white; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>
        
        <?php if ($cart_was_truncated): ?>
        <div style="background: #ff9800; color: white; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            ⚠️ Your same-day cart has been cleared because items were from a previous day or the business is currently closed.
        </div>
        <?php endif; ?>
        
        <p class="cart-info">
            <span class="cart-info-full">Please note: Neo Café offers both same-day and pre-order options to serve you better. <br></span>
            Pre-order products must be placed at least 42 hours in advance to ensure freshness and quality.<br>
            <span class="cart-info-full">Same-day order products can be placed within business hours.</span>

        </p>
        
        <div class="cart-grid">
            <!-- CART CONTENT -->
            <div class="cart-content">
                <!-- PRE-ORDER SECTION -->
                <div class="cart-section">
                    <div class="section-header">
                        <h3>Pre-Order Items</h3>
                    </div>
                    
                    <?php if (!empty($preorder_items)): ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preorder_items as $item): 
                                // Handle both Cloudinary URLs and local paths
                                if (!empty($item['image_url'])) {
                                    if (strpos($item['image_url'], 'http://') === 0 || strpos($item['image_url'], 'https://') === 0) {
                                        $image = $item['image_url']; // Cloudinary URL
                                    } else {
                                        $image = "/assets/" . $item['image_url']; // Local path
                                    }
                                } else {
                                    $image = "/assets/images/no-image.jpg";
                                }
                                $item_total = $item['price'] * $item['quantity'];
                            ?>
                            <tr data-cart-id="<?= $item['cart_id'] ?>" data-status-id="<?= $item['status_id'] ?>">
                                <td>
                                    <input type="checkbox" class="item-checkbox preorder-checkbox" 
                                           value="<?= $item['cart_id'] ?>" 
                                           data-total="<?= $item_total ?>"
                                           data-status-id="<?= $item['status_id'] ?>">
                                </td>
                                <td>
                                    <div style="position: relative; display: inline-block;">
                                        <img src="<?= $image ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <?php if ($item['status_id'] == 1): ?>
                                            <span class="image-badge badge-pickup">Pick Up Only</span>
                                        <?php elseif ($item['status_id'] == 2): ?>
                                            <span class="image-badge badge-delivery">Delivery Only</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantityInstant(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>, 'preorder', <?= $item['product_stock'] ?>, this)">-</button>
                                        <span class="quantity-display"><?= $item['quantity'] ?></span>
                                        <button class="quantity-btn" onclick="updateQuantityInstant(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>, 'preorder', <?= $item['product_stock'] ?>, this)">+</button>
                                    </div>
                                </td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td>₱<?= number_format($item_total, 2) ?></td>
                                <td>
                                    <button class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>, 'preorder')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M10 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-items">No pre-order items in cart</div>
                    <?php endif; ?>
                </div>
                
                <!-- SAME DAY ORDER SECTION -->
                <div class="cart-section">
                    <div class="section-header sameday">
                        <h3>Same Day Order Items ( <?= date('M d, Y') ?>)</h3>
                    </div>
                    
                    <?php if (!empty($sameday_items)): ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sameday_items as $item): 
                                // Handle both Cloudinary URLs and local paths
                                if (!empty($item['image_url'])) {
                                    if (strpos($item['image_url'], 'http://') === 0 || strpos($item['image_url'], 'https://') === 0) {
                                        $image = $item['image_url']; // Cloudinary URL
                                    } else {
                                        $image = "/assets/" . $item['image_url']; // Local path
                                    }
                                } else {
                                    $image = "/assets/images/no-image.jpg";
                                }
                                $item_total = $item['price'] * $item['quantity'];
                            ?>
                            <tr data-cart-id="<?= $item['cart_id'] ?>" data-status-id="<?= $item['status_id'] ?>">
                                <td>
                                    <input type="checkbox" class="item-checkbox sameday-checkbox" 
                                           value="<?= $item['cart_id'] ?>" 
                                           data-total="<?= $item_total ?>"
                                           data-status-id="<?= $item['status_id'] ?>">
                                </td>
                                <td>
                                    <div style="position: relative; display: inline-block;">
                                        <img src="<?= $image ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <?php if ($item['status_id'] == 1): ?>
                                            <span class="image-badge badge-pickup">Pick Up Only</span>
                                        <?php elseif ($item['status_id'] == 2): ?>
                                            <span class="image-badge badge-delivery">Delivery Only</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantityInstant(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>, 'sameday', <?= $item['product_stock'] ?>, this)">-</button>
                                        <span class="quantity-display"><?= $item['quantity'] ?></span>
                                        <button class="quantity-btn" onclick="updateQuantityInstant(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>, 'sameday', <?= $item['product_stock'] ?>, this)">+</button>
                                    </div>
                                </td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td>₱<?= number_format($item_total, 2) ?></td>
                                <td>
                                    <button class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>, 'sameday')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M10 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-items">No same day order items in cart</div>
                    <?php endif; ?>
                </div>
            </div> <!-- End cart-content -->
            
            <!-- CHECKOUT SIDEBAR -->
            <div>
                <div class="checkout-section">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <!-- Desktop Layout -->
                    <div class="desktop-summary-layout">
                        <div class="summary-details">
                            <div class="selected-items-row">
                                <span>Selected Items:</span>
                                <span id="selectedCount">0</span>
                            </div>
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="totalAmount">₱0.00</span>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="terms-section">
                            <label class="terms-label">
                                <input type="checkbox" id="termsCheckbox" class="terms-checkbox">
                                <span class="terms-text">I have read and agreed with the <a href="/frontend/pages/terms/terms-conditions.php" target="_blank" class="terms-link">Terms and Conditions</a></span>
                            </label>
                        </div>
                        
                        <button class="checkout-btn" id="checkoutBtn" disabled onclick="proceedToCheckout()">
                            Proceed to Checkout
                        </button>
                        
                        <p class="checkout-help-text">
                            Select items and accept terms to checkout
                        </p>
                        <p class="checkout-warning-text">
                            ⓘ Pre-Order and Same Day items must be checked out separately
                        </p>
                    </div>
                    
                    <!-- Mobile Layout (1024px and below) -->
                    <div class="mobile-summary-layout">
                        <div class="mobile-total-checkout-row">
                            <div class="mobile-total-info">
                                <span class="mobile-total-label">Total: (<span id="selectedCountMobile">0</span> items) <span id="totalAmountMobile">₱0.00</span></span>
                            </div>
                            <button class="mobile-checkout-btn" id="checkoutBtnMobile" disabled onclick="proceedToCheckout()">
                                Checkout
                            </button>
                        </div>
                        
                        <!-- Terms and Conditions Mobile -->
                        <div class="mobile-terms-section">
                            <label class="mobile-terms-label">
                                <input type="checkbox" id="termsCheckboxMobile" class="mobile-terms-checkbox">
                                <span class="mobile-terms-text">I have read and agreed with the <a href="/frontend/pages/terms/terms-conditions.php" target="_blank" class="mobile-terms-link">Terms and Conditions</a></span>
                            </label>
                        </div>
                        
                        <p class="mobile-warning-text">
                            ⓘ Pre-Order and Same Day items must be checked out separately
                        </p>
                    </div>
                </div>
            </div>
        </div> <!-- End cart-grid -->
    </div>
</div>
<script>
// Track selected items and shipping method
let selectedItems = [];
let currentShippingMethod = <?= $current_shipping_method ?? 'null' ?>;
let inheritedShippingMethod = null; // For status_id 3 products

// Wait for DOM to be ready before attaching event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Update totals when checkboxes change
    const checkboxes = document.querySelectorAll('.item-checkbox');
    if (checkboxes && checkboxes.length > 0) {
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateTotals);
        });
    }
});

function updateTotals() {
    selectedItems = [];
    let total = 0;
    let hasPickupOnly = false;
    let hasDeliveryOnly = false;
    let hasFlexible = false;
    
    document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
        const statusId = parseInt(checkbox.dataset.statusId);
        const itemTotal = parseFloat(checkbox.dataset.total);
        
        // Determine order type based on which section the checkbox is in
        // NOT based on status_id, because a product can be in either table
        const orderType = checkbox.classList.contains('sameday-checkbox') ? 'sameday' : 'preorder';
        
        selectedItems.push({
            cartId: checkbox.value,
            statusId: statusId,
            total: itemTotal,
            type: orderType
        });
        
        total += itemTotal;
        
        // Only check shipping method for pre-order items
        if (orderType === 'preorder') {
            if (statusId === 1) hasPickupOnly = true;
            if (statusId === 2) hasDeliveryOnly = true;
            if (statusId === 3) hasFlexible = true;
        }
    });
    
    // Check for mixed order types (pre-order vs same-day)
    const hasPreorder = selectedItems.some(item => item.type === 'preorder');
    const hasSameday = selectedItems.some(item => item.type === 'sameday');
    
    if (hasPreorder && hasSameday) {
        alert('You cannot mix Pre-Order and Same Day Order items in the same checkout! Please select only one type.');
        // Uncheck the last selected item
        event.target.checked = false;
        updateTotals();
        return;
    }
    
    // Check for mixed shipping methods (1 and 2 cannot be together) - only for pre-orders
    if (hasPickupOnly && hasDeliveryOnly) {
        alert('You cannot mix Pick Up Only and Delivery Only products in the same order!');
        // Uncheck the last selected item
        event.target.checked = false;
        updateTotals();
        return;
    }
    
    // Determine inherited shipping method for status_id 3 products
    if (hasPickupOnly) {
        inheritedShippingMethod = 'pickup';
        updateFlexibleProductsDisplay('pickup');
    } else if (hasDeliveryOnly) {
        inheritedShippingMethod = 'delivery';
        updateFlexibleProductsDisplay('delivery');
    } else {
        inheritedShippingMethod = null;
        updateFlexibleProductsDisplay(null);
    }
    
    // Update display for both desktop and mobile
    document.getElementById('selectedCount').textContent = selectedItems.length;
    document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
    
    // Update mobile elements
    const selectedCountMobile = document.getElementById('selectedCountMobile');
    const totalAmountMobile = document.getElementById('totalAmountMobile');
    if (selectedCountMobile) selectedCountMobile.textContent = selectedItems.length;
    if (totalAmountMobile) totalAmountMobile.textContent = '₱' + total.toFixed(2);
    
    updateCheckoutButton();
}

// Update visual indicator for status_id 3 products
function updateFlexibleProductsDisplay(method) {
    document.querySelectorAll('tr[data-status-id="3"]').forEach(row => {
        const checkbox = row.querySelector('.item-checkbox');
        const productName = row.querySelector('td:nth-child(3)');
        
        // Null check for productName
        if (!productName) return;
        
        // Remove existing indicator
        const existingIndicator = productName.querySelector('.shipping-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        // Add indicator if this item is selected and there's an inherited method
        if (checkbox && checkbox.checked && method) {
            const indicator = document.createElement('span');
            indicator.className = 'shipping-indicator';
            indicator.style.cssText = 'display: inline-block; margin-left: 8px; padding: 2px 8px; background: #f0f0f0; border-radius: 3px; font-size: 11px; color: #666;';
            indicator.textContent = method === 'pickup' ? '→ Will be Pick Up' : '→ Will be Delivery';
            productName.appendChild(indicator);
        }
    });
}

function updateQuantity(cartId, newQuantity, type) {
    if (newQuantity < 1) {
        if (confirm('Remove this item from cart?')) {
            removeItem(cartId, type);
        }
        return;
    }
    
    const url = type === 'preorder' ? 'update-cart.php' : 'update-cart-quantity-sameday.php';
    
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update quantity');
        }
    });
}

// New instant update function without page refresh
function updateQuantityInstant(cartId, newQuantity, type, maxStock, element) {
    // Validate quantity
    if (newQuantity < 1) {
        if (confirm('Remove this item from cart?')) {
            removeItem(cartId, type);
        }
        return;
    }
    
    if (newQuantity > maxStock) {
        alert(`Maximum available quantity is ${maxStock}`);
        return;
    }
    
    // Disable buttons during update
    const row = element.closest('tr');
    const buttons = row.querySelectorAll('.quantity-btn');
    const quantityDisplay = row.querySelector('.quantity-display');
    
    buttons.forEach(btn => btn.disabled = true);
    
    const url = type === 'preorder' ? 'update-cart.php' : 'update-cart-quantity-sameday.php';
    
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the quantity display and total without page refresh
            quantityDisplay.textContent = newQuantity;
            
            // Update the total price for this row
            const priceCell = row.cells[4]; // Price column
            const totalCell = row.cells[5]; // Total column
            const priceText = priceCell.textContent.replace('₱', '').replace(',', '');
            const price = parseFloat(priceText);
            const newTotal = price * newQuantity;
            totalCell.textContent = '₱' + newTotal.toFixed(2);
            
            // Update checkbox data-total if checked
            const checkbox = row.querySelector('.item-checkbox');
            if (checkbox) {
                checkbox.setAttribute('data-total', newTotal);
                if (checkbox.checked) {
                    updateTotals(); // Update cart totals
                }
            }
            
            // Re-enable buttons
            buttons.forEach(btn => btn.disabled = false);
        } else {
            alert(data.message || 'Failed to update quantity');
            // Re-enable buttons
            buttons.forEach(btn => btn.disabled = false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update quantity');
        // Re-enable buttons
        buttons.forEach(btn => btn.disabled = false);
    });
}

function removeItem(cartId, type) {
    if (!confirm('Remove this item from cart?')) return;
    
    const url = type === 'preorder' ? 'remove-from-cart.php' : 'remove-from-cart-sameday.php';
    
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to remove item');
        }
    });
}

// Enable/disable checkout button based on selection and terms
function updateCheckoutButton() {
    const termsChecked = document.getElementById('termsCheckbox').checked;
    const hasItems = selectedItems.length > 0;
    
    // Update desktop button
    document.getElementById('checkoutBtn').disabled = !(hasItems && termsChecked);
    
    // Update mobile elements
    const termsCheckboxMobile = document.getElementById('termsCheckboxMobile');
    const checkoutBtnMobile = document.getElementById('checkoutBtnMobile');
    
    if (termsCheckboxMobile && checkoutBtnMobile) {
        const termsCheckedMobile = termsCheckboxMobile.checked;
        checkoutBtnMobile.disabled = !(hasItems && termsCheckedMobile);
    }
}

// Sync checkbox states
function syncCheckboxes(source) {
    const desktopCheckbox = document.getElementById('termsCheckbox');
    const mobileCheckbox = document.getElementById('termsCheckboxMobile');
    
    if (source === 'desktop' && mobileCheckbox) {
        mobileCheckbox.checked = desktopCheckbox.checked;
    } else if (source === 'mobile' && desktopCheckbox) {
        desktopCheckbox.checked = mobileCheckbox.checked;
    }
    
    updateCheckoutButton();
}

// Add terms checkbox listeners
document.getElementById('termsCheckbox').addEventListener('change', function() {
    syncCheckboxes('desktop');
});

// Add mobile checkbox listener if it exists
setTimeout(() => {
    const mobileCheckbox = document.getElementById('termsCheckboxMobile');
    if (mobileCheckbox) {
        mobileCheckbox.addEventListener('change', function() {
            syncCheckboxes('mobile');
        });
    }
}, 100);

function proceedToCheckout() {
    console.log('proceedToCheckout called, selectedItems:', selectedItems);
    
    // Determine which button was clicked based on screen size or button availability
    const checkoutBtn = document.getElementById('checkoutBtn');
    const checkoutBtnMobile = document.getElementById('checkoutBtnMobile');
    
    let activeButton, originalText;
    
    // Check if mobile layout is visible (1024px and below)
    if (window.innerWidth <= 1024 && checkoutBtnMobile) {
        activeButton = checkoutBtnMobile;
        originalText = 'Checkout';
    } else {
        activeButton = checkoutBtn;
        originalText = checkoutBtn.textContent;
    }
    
    // Show loading state
    activeButton.disabled = true;
    activeButton.classList.add('loading');
    activeButton.innerHTML = '<span class="loading-spinner-small"></span>Processing...';
    
    if (selectedItems.length === 0) {
        alert('Please select items to checkout by checking the boxes next to the items you want to purchase.');
        // Reset button state
        activeButton.disabled = false;
        activeButton.classList.remove('loading');
        activeButton.textContent = originalText;
        return;
    }
    
    // Check terms and conditions
    const termsChecked = window.innerWidth <= 1024 ? 
        document.getElementById('termsCheckboxMobile').checked : 
        document.getElementById('termsCheckbox').checked;
    
    if (!termsChecked) {
        alert('Please accept the Terms and Conditions');
        // Reset button state
        activeButton.disabled = false;
        activeButton.classList.remove('loading');
        activeButton.textContent = originalText;
        return;
    }
    
    // Separate pre-order and same-day items
    const preorderIds = [];
    const samedayIds = [];
    
    selectedItems.forEach(item => {
        if (item.type === 'sameday') {
            samedayIds.push(item.cartId);
        } else if (item.type === 'preorder') {
            preorderIds.push(item.cartId);
        }
    });
    
    console.log('Pre-order IDs:', preorderIds);
    console.log('Same-day IDs:', samedayIds);
    
    // Check if user selected both types
    if (preorderIds.length > 0 && samedayIds.length > 0) {
        alert('Please checkout Pre-Order and Same Day Order items separately. Select only one type at a time.');
        // Reset button state
        checkoutBtn.disabled = false;
        checkoutBtn.classList.remove('loading');
        checkoutBtn.textContent = originalText;
        return;
    }
    
    // Create a form to POST the data instead of using GET
    // This is more reliable than URL parameters
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    // Redirect to appropriate checkout based on item type
    if (samedayIds.length > 0) {
        // Same day checkout
        form.action = 'availtoday-checkout.php';
        samedayIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_cart_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        console.log('Submitting to same-day checkout with IDs:', samedayIds);
    } else if (preorderIds.length > 0) {
        // Pre-order checkout
        form.action = 'checkout.php';
        preorderIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_cart_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        console.log('Submitting to pre-order checkout with IDs:', preorderIds);
    } else {
        alert('No items selected for checkout. Please check the boxes next to items you want to purchase.');
        // Reset button state
        checkoutBtn.disabled = false;
        checkoutBtn.classList.remove('loading');
        checkoutBtn.textContent = originalText;
        return;
    }
    
    // Add subtotal
    const subtotalInput = document.createElement('input');
    subtotalInput.type = 'hidden';
    subtotalInput.name = 'subtotal';
    subtotalInput.value = document.getElementById('totalAmount').textContent.replace('₱', '').replace(',', '');
    form.appendChild(subtotalInput);
    
    // Submit the form
    document.body.appendChild(form);
    
    // Small delay to show loading state before redirecting
    setTimeout(() => {
        form.submit();
    }, 500);
}
</script>

</body>
</html>
<?php $conn->close(); ?>