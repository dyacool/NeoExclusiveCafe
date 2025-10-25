<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

require_once '../../../backend/pages/admin-includes/database.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login/user/login-signup.php");
    exit();
}

require_once '../../user-includes/navbar/customer-navigation.php';
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get Pre-Order items (from cart table)
$preorder_query = "
    SELECT c.id AS cart_id, c.quantity, c.price, c.product_id,
           p.name AS product_name, p.quantity as product_stock, p.status_id,
           ps.name as status_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
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
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_url
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
    <link rel="stylesheet" href="cart.css" />
    <style>
        body {
            background-color: #f8f8f8;
            color: #333;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .main-container h2 {
            font-size: 2.5em;
            font-weight: 700;
            color: #444;
            text-shadow: 1px 1px 4px #6c6c6c;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .cart-info {
            color: #333;
            text-align: center;
            padding-bottom: 20px;
            font-size: 14px;
        }
        
        .cart-section {
            background: white;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section-header {
            background: linear-gradient(135deg, #256035, #1a4a2a);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header.sameday {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 1.3em;
        }
        
        .image-badge {
            position: absolute;
            top: 2px;
            left: 2px;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .badge-pickup {
            background: #4CAF50;
            color: white;
        }
        
        .badge-delivery {
            background: #2196F3;
            color: white;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        
        .cart-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-table tr:hover {
            background: #f9f9f9;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .quantity-btn:hover {
            background: #f0f0f0;
        }
        
        .remove-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .remove-btn:hover {
            background: #d32f2f;
        }
        
        .no-items {
            padding: 40px;
            text-align: center;
            color: #999;
        }
        
        .checkout-section {
            position: sticky;
            top: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 18px;
            font-weight: 600;
            border-top: 2px solid #ddd;
            margin-top: 10px;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .checkout-btn:hover {
            background: #45a049;
        }
        
        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<?php include '../../user-includes/user-header.php'; ?>

<div class="wrapper fade-in">
    <div class="main-container">
        <h2>Shopping Cart</h2>
        <p class="cart-info">
            Please note: NeoCafe operates on a preorder basis to ensure the freshness of our baked goods.<br>
            Orders must be placed at least 24 hours in advance.
        </p>
        
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
            <div>
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
                                $image = $item['image_url'] ? "/assets/" . $item['image_url'] : "/assets/images/no-image.jpg";
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
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>, 'preorder')">-</button>
                                        <span><?= $item['quantity'] ?></span>
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>, 'preorder')">+</button>
                                    </div>
                                </td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td>₱<?= number_format($item_total, 2) ?></td>
                                <td>
                                    <button class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>, 'preorder')">Remove</button>
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
                        <h3>Same Day Order Items (For Today: <?= date('M d, Y') ?>)</h3>
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
                                $image = $item['image_url'] ? "/assets/" . $item['image_url'] : "/assets/images/no-image.jpg";
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
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>, 'sameday')">-</button>
                                        <span><?= $item['quantity'] ?></span>
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>, 'sameday')">+</button>
                                    </div>
                                </td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td>₱<?= number_format($item_total, 2) ?></td>
                                <td>
                                    <button class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>, 'sameday')">Remove</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-items">No same day order items in cart</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- CHECKOUT SIDEBAR -->
            <div>
                <div class="checkout-section">
                    <h3>Order Summary</h3>
                    <div style="padding: 10px 0;">
                        <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                            <span>Selected Items:</span>
                            <span id="selectedCount">0</span>
                        </div>
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span id="totalAmount">₱0.00</span>
                        </div>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div style="margin: 15px 0;">
                        <label style="display: flex; align-items: start; gap: 8px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" id="termsCheckbox" style="margin-top: 3px; cursor: pointer;">
                            <span>I have read and agreed with the Terms and Conditions</span>
                        </label>
                    </div>
                    
                    <button class="checkout-btn" id="checkoutBtn" disabled onclick="proceedToCheckout()">
                        Proceed to Checkout
                    </button>
                    <p style="font-size: 12px; color: #666; margin-top: 10px; text-align: center;">
                        Select items and accept terms to checkout
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Track selected items and shipping method
let selectedItems = [];
let currentShippingMethod = <?= $current_shipping_method ?? 'null' ?>;
let inheritedShippingMethod = null; // For status_id 3 products

// Update totals when checkboxes change
document.querySelectorAll('.item-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateTotals);
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
        
        selectedItems.push({
            cartId: checkbox.value,
            statusId: statusId,
            total: itemTotal
        });
        
        total += itemTotal;
        
        if (statusId === 1) hasPickupOnly = true;
        if (statusId === 2) hasDeliveryOnly = true;
        if (statusId === 3) hasFlexible = true;
    });
    
    // Check for mixed shipping methods (1 and 2 cannot be together)
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
    
    // Update display
    document.getElementById('selectedCount').textContent = selectedItems.length;
    document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
    updateCheckoutButton();
}

// Update visual indicator for status_id 3 products
function updateFlexibleProductsDisplay(method) {
    document.querySelectorAll('tr[data-status-id="3"]').forEach(row => {
        const checkbox = row.querySelector('.item-checkbox');
        const productName = row.querySelector('td:nth-child(3)');
        
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
    document.getElementById('checkoutBtn').disabled = !(hasItems && termsChecked);
}

// Add terms checkbox listener
document.getElementById('termsCheckbox').addEventListener('change', updateCheckoutButton);

function proceedToCheckout() {
    if (selectedItems.length === 0) {
        alert('Please select items to checkout');
        return;
    }
    
    if (!document.getElementById('termsCheckbox').checked) {
        alert('Please accept the Terms and Conditions');
        return;
    }
    
    // Separate pre-order and same-day items
    const preorderIds = [];
    const samedayIds = [];
    
    selectedItems.forEach(item => {
        const checkbox = document.querySelector(`.item-checkbox[value="${item.cartId}"]`);
        if (checkbox.classList.contains('preorder-checkbox')) {
            preorderIds.push(item.cartId);
        } else {
            samedayIds.push(item.cartId);
        }
    });
    
    // Redirect to appropriate checkout
    if (samedayIds.length > 0) {
        // Same day checkout
        window.location.href = 'availtoday-checkout.php?cart_ids=' + samedayIds.join(',');
    } else {
        // Pre-order checkout
        window.location.href = 'checkout.php?cart_ids=' + preorderIds.join(',');
    }
}
</script>

</body>
</html>
<?php $conn->close(); ?>
