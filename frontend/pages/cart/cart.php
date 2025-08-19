<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Include database connection
require_once '../../user-includes/database.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login/user/login-signup.php");
    exit();
}
require_once '../../user-includes/navbar/customer-navigation.php';
$user_id = $_SESSION['user_id'];

// Function to convert day names to abbreviations
function getDayAbbreviations($availableDays) {
    if (empty($availableDays)) {
        return '';
    }
    
    $dayMap = [
        'Sunday' => 'S',
        'Monday' => 'M',
        'Tuesday' => 'T',
        'Wednesday' => 'W',
        'Thursday' => 'Th',
        'Friday' => 'F',
        'Saturday' => 'Sa'
    ];
    
    $days = explode(', ', $availableDays);
    $abbreviations = [];
    
    foreach ($days as $day) {
        if (isset($dayMap[trim($day)])) {
            $abbreviations[] = $dayMap[trim($day)];
        }
    }
    
    return implode(', ', $abbreviations);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cart.css" />
    <link rel="stylesheet" href="../../confirmations.css" />
    <title>Shopping Cart</title>
    <style>
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1001;
        }
        .confirmation-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            min-width: 300px;
        }
        .confirmation-modal-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .confirmation-modal-buttons button {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .confirm-btn {
            background-color: #f44336;
            color: white;
        }
        .cancel-btn {
            background-color: #e0e0e0;
        }
        
        .cart-section {
            margin-bottom: 30px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #256035, #1a4a2a);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 1.3em;
        }
        
        .section-select-all {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-select-all input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: white;
        }
        
        .section-select-all label {
            color: white;
            font-weight: 500;
            cursor: pointer;
        }
        
        .section-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .days-filter {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .days-filter label {
            color: white;
            font-weight: 500;
            font-size: 14px;
        }
        
        .days-filter select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            font-size: 14px;
            cursor: pointer;
        }
        
        .days-column {
            text-align: center;
            font-weight: 500;
            color: #2f603c;
        }
        
        .day-abbreviations {
            font-size: 13px;
            white-space: nowrap;
        }
        
        /* Smart filtering visual feedback */
        .cart-table tbody tr.filtered-out {
            opacity: 0.3;
            background-color: #f8f8f8;
        }
        
        .cart-table tbody tr:not(.filtered-out) {
            background-color: #f0fff0;
            border-left: 3px solid #4CAF50;
        }
        
        .cart-table tbody tr:not(.filtered-out) .days-column {
            background-color: #e8f5e8;
            font-weight: bold;
        }
        

        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .section-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .days-filter {
                justify-content: center;
            }
            
            .days-filter select {
                flex: 1;
                max-width: 200px;
            }
            
            .days-column {
                font-size: 12px;
            }
            
            .day-abbreviations {
                font-size: 11px;
            }
        }
        
        .cart-table {
            border-radius: 0 0 8px 8px;
        }
        
        .cart-table.pickup-table {
            border-top: 3px solid #256035;
        }
        
        .cart-table.delivery-table {
            border-top: 3px solid #e67e22;
        }
        
        .no-items-message {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
            background: #f9f9f9;
            border-radius: 0 0 8px 8px;
        }
        
                 .mixed-selection-warning {
             background-color: #fff3cd;
             border: 1px solid #ffeaa7;
             color: #856404;
             padding: 12px;
             border-radius: 5px;
             margin: 10px 0;
             display: none;
         }
         

    </style>
</head>
<body>

<div id="confirmationPopup" class="confirmation-popup"></div>

<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-modal-content">
        <h3>Remove Item</h3>
        <p>Are you sure you want to remove this item from your cart?</p>
        <div class="confirmation-modal-buttons">
            <button class="cancel-btn" onclick="closeConfirmationModal()">Cancel</button>
            <button class="confirm-btn" id="confirmRemoveBtn">Remove</button>
        </div>
    </div>
</div>

<?php include '../../user-includes/user-header.php'; ?>

<div class="wrapper fade-in">
    <div class="main-container">
        <h2>Shopping Cart</h2>
        <p class="cart-info">
            Please note: NeoCafe operates on a preorder basis to ensure the freshness of our baked goods.<br>
            Orders must be placed at least 24 hours in advance.
        </p>

        <div id="mixedSelectionWarning" class="mixed-selection-warning">
            <strong>⚠️ Mixed Selection Warning:</strong> You cannot mix Pickup and Delivery products in the same order. Please select items from only one category.
        </div>

        <?php
        // Clean up any Available Today products that might be in the regular cart
        $cleanup_stmt = $conn->prepare("
            DELETE c FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ? AND p.status_id = 3
        ");
        if ($cleanup_stmt) {
            $cleanup_stmt->bind_param("i", $user_id);
            $cleanup_stmt->execute();
            $cleanup_stmt->close();
        }
        
        $stmt = $conn->prepare("
            SELECT c.id AS cart_id, c.quantity, c.price,
                   p.id AS product_id, p.name AS product_name, p.quantity as product_stock,
                   pi.image_url, ps.name as status_name,
                   GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN product_statuses ps ON p.status_id = ps.id
            LEFT JOIN product_day pd ON p.id = pd.product_id
            WHERE c.user_id = ? AND p.status_id IN (1, 2)
            GROUP BY c.id, c.quantity, c.price, p.id, p.name, p.quantity, pi.image_url, ps.name
            ORDER BY ps.name = 'Pickup' DESC, p.name ASC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        
        if ($result->num_rows > 0): 
            // Reset the result pointer to use the data again
            $result->data_seek(0);
            
            // Separate items by status
            $pickup_items = [];
            $delivery_items = [];
            
            while ($row = $result->fetch_assoc()) {
                if ($row['status_name'] === 'Pick Up') {
                    $pickup_items[] = $row;
                } elseif ($row['status_name'] === 'Delivery') {
                    $delivery_items[] = $row;
                }
            }
        ?>
        <form method="POST" action="checkout.php" id="cartForm">
            <input type="hidden" name="valid_cart_ids" value="<?= implode(',', array_merge(array_column($pickup_items, 'cart_id'), array_column($delivery_items, 'cart_id'))) ?>">
            <div class="cart-layout">
                <div class="cart-content">
                    <!-- Pickup Products Section -->
                    <div class="cart-section">
                        <div class="section-header">
                            <h3>Pickup</h3>
                            <div class="section-controls">
                                <div class="days-filter">
                                    <label for="pickupDaysFilter">Filter by Day:</label>
                                    <select id="pickupDaysFilter" onchange="filterByDay('pickup')">
                                        <option value="">All Days</option>
                                        <option value="Sunday">Sunday</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                    </select>
                                </div>
                                <div class="section-select-all">
                                    <input type="checkbox" id="selectAllPickup" class="section-checkbox" data-section="pickup">
                                    <label for="selectAllPickup">Select All Pickup</label>
                                </div>
                            </div>

                        </div>
                        
                        <?php if (!empty($pickup_items)): ?>
                        <table class="cart-table pickup-table">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th></th>
                                    <th>Product Name</th>
                                    <th>Days</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($pickup_items as $row):
                                    $imageUrl = $row['image_url'] ? "/assets/" . $row['image_url'] : "/assets/images/no-image.jpg";
                                    $item_total = $row['price'] * $row['quantity'];
                                ?>
                                <tr data-cart-id="<?= $row['cart_id'] ?>" data-product-id="<?= $row['product_id'] ?>" data-stock="<?= $row['product_stock'] ?>" data-price="<?= $row['price'] ?>" data-quantity="<?= $row['quantity'] ?>" data-status="pickup" data-days="<?= htmlspecialchars($row['available_days'] ?? '') ?>">
                                    <td>
                                        <input type="checkbox" name="selected_cart_ids[]" value="<?= $row['cart_id'] ?>" class="item-checkbox pickup-checkbox" data-total="<?= $item_total ?>" data-status="pickup">
                                    </td>
                                    <td><img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" style="width: 60px; height: 60px; object-fit: cover;"></td>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td class="days-column">
                                        <span class="day-abbreviations"><?= getDayAbbreviations($row['available_days'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <div class="quantity-controls">
                                            <button class="quantity-btn" type="button" onclick="updateQuantity(<?= $row['cart_id'] ?>, <?= $row['quantity'] - 1 ?>)">-</button>
                                            <span class="quantity"><?= $row['quantity'] ?></span>
                                            <button class="quantity-btn" type="button" onclick="updateQuantity(<?= $row['cart_id'] ?>, <?= $row['quantity'] + 1 ?>)">+</button>
                                        </div>
                                    </td>
                                    <td>₱<?= number_format($row['price'], 2) ?></td>
                                    <td class="item-total">₱<?= number_format($item_total, 2) ?></td>
                                    <td>
                                        <button class="remove-btn" type="button" onclick="showConfirmationModal(<?= $row['cart_id'] ?>)">
                                            <svg viewBox="0 0 448 512" class="svgIcon"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="no-items-message">
                            No pickup products in your cart
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Delivery Products Section -->
                    <div class="cart-section">
                        <div class="section-header">
                            <h3>Delivery</h3>
                            <div class="section-controls">
                                <div class="days-filter">
                                    <label for="daysFilter">Filter by Day:</label>
                                    <select id="daysFilter" onchange="filterByDay('delivery')">
                                        <option value="">All Days</option>
                                        <option value="Sunday">Sunday</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                    </select>
                                </div>
                                <div class="section-select-all">
                                    <input type="checkbox" id="selectAllDelivery" class="section-checkbox" data-section="delivery">
                                    <label for="selectAllDelivery">Select All Delivery</label>
                                </div>
                            </div>

                        </div>
                        
                        <?php if (!empty($delivery_items)): ?>
                        <table class="cart-table delivery-table">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th></th>
                                    <th>Product Name</th>
                                    <th>Days</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($delivery_items as $row):
                                    $imageUrl = $row['image_url'] ? "/assets/" . $row['image_url'] : "/assets/images/no-image.jpg";
                                    $item_total = $row['price'] * $row['quantity'];
                                ?>
                                <tr data-cart-id="<?= $row['cart_id'] ?>" data-product-id="<?= $row['product_id'] ?>" data-stock="<?= $row['product_stock'] ?>" data-price="<?= $row['price'] ?>" data-quantity="<?= $row['quantity'] ?>" data-status="delivery" data-days="<?= htmlspecialchars($row['available_days'] ?? '') ?>">
                                    <td>
                                        <input type="checkbox" name="selected_cart_ids[]" value="<?= $row['cart_id'] ?>" class="item-checkbox delivery-checkbox" data-total="<?= $item_total ?>" data-status="delivery">
                                    </td>
                                    <td><img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" style="width: 60px; height: 60px; object-fit: cover;"></td>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td class="days-column">
                                        <span class="day-abbreviations"><?= getDayAbbreviations($row['available_days'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <div class="quantity-controls">
                                            <button class="quantity-btn" type="button" onclick="updateQuantity(<?= $row['cart_id'] ?>, <?= $row['quantity'] - 1 ?>)">-</button>
                                            <span class="quantity"><?= $row['quantity'] ?></span>
                                            <button class="quantity-btn" type="button" onclick="updateQuantity(<?= $row['cart_id'] ?>, <?= $row['quantity'] + 1 ?>)">+</button>
                                        </div>
                                    </td>
                                    <td>₱<?= number_format($row['price'], 2) ?></td>
                                    <td class="item-total">₱<?= number_format($item_total, 2) ?></td>
                                    <td>
                                        <button class="remove-btn" type="button" onclick="showConfirmationModal(<?= $row['cart_id'] ?>)">
                                            <svg viewBox="0 0 448 512" class="svgIcon"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="no-items-message">
                            No delivery products in your cart
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cart-sidebar">
                    <div class="cart-summary">
                        <div class="cart-total"><h3>Subtotal: ₱<span id="displaySubtotal">0.00</span></h3></div>
                        <label><input type="checkbox" id="termsCheckbox" required /> I have read and agreed with the Terms and Conditions</label>
                        <button type="submit" name="checkout" class="checkout-btn" onclick="return validateCart()">Proceed to Checkout</button>
                        <input type="hidden" name="subtotal" id="subtotalInput" value="0">
                        <input type="hidden" name="cart_items" id="cartItemsInput" value="">
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="/frontend/pages/products/product-dashboard.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let currentCartId = null;

function showConfirmation(message, isError = false) {
    const popup = document.getElementById('confirmationPopup');
    popup.textContent = message;
    popup.className = 'confirmation-popup' + (isError ? ' error' : '');
    popup.classList.add('show');
    
    setTimeout(() => {
        popup.classList.remove('show');
        popup.classList.add('hide');
        setTimeout(() => {
            popup.classList.remove('hide');
        }, 300);
    }, 3000);
}

function showConfirmationModal(cartId) {
    currentCartId = cartId;
    document.getElementById('confirmationModal').style.display = 'block';
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    currentCartId = null;
}

function checkMixedSelection() {
    const pickupChecked = document.querySelectorAll('.pickup-checkbox:checked').length > 0;
    const deliveryChecked = document.querySelectorAll('.delivery-checkbox:checked').length > 0;
    const warning = document.getElementById('mixedSelectionWarning');
    
    if (pickupChecked && deliveryChecked) {
        warning.style.display = 'block';
        return true; // Mixed selection detected
    } else {
        warning.style.display = 'none';
        return false; // No mixed selection
    }
}

function preventMixedSelection(clickedCheckbox) {
    const isPickup = clickedCheckbox.classList.contains('pickup-checkbox');
    const isDelivery = clickedCheckbox.classList.contains('delivery-checkbox');
    
    if (isPickup) {
        // If user is trying to check a pickup item, check if delivery items are already selected
        const deliveryChecked = document.querySelectorAll('.delivery-checkbox:checked').length > 0;
        if (deliveryChecked) {
            showConfirmation('⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck delivery items first.', true);
            clickedCheckbox.checked = false;
            return false;
        }
    } else if (isDelivery) {
        // If user is trying to check a delivery item, check if pickup items are already selected
        const pickupChecked = document.querySelectorAll('.pickup-checkbox:checked').length > 0;
        if (pickupChecked) {
            showConfirmation('⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck pickup items first.', true);
            clickedCheckbox.checked = false;
            return false;
        }
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize section checkboxes
    const selectAllPickup = document.getElementById('selectAllPickup');
    const selectAllDelivery = document.getElementById('selectAllDelivery');
    const pickupCheckboxes = document.querySelectorAll('.pickup-checkbox');
    const deliveryCheckboxes = document.querySelectorAll('.delivery-checkbox');
    
    // Set up section select all event listeners
    selectAllPickup.addEventListener('change', function() {
        if (this.checked) {
            // Check if delivery items are already selected
            const deliveryChecked = document.querySelectorAll('.delivery-checkbox:checked').length > 0;
            if (deliveryChecked) {
                showConfirmation('⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck delivery items first.', true);
                this.checked = false;
                return;
            }
            
            // Warn user about Select All limitation
            showConfirmation('⚠️ Select All Notice: When all pickup items are selected, you can only delete them (not checkout) due to potentially incompatible pickup days. Select specific items for checkout.', true);
        }
        
        const visiblePickupCheckboxes = Array.from(pickupCheckboxes).filter(cb => {
            const row = cb.closest('tr');
            return row && row.style.display !== 'none';
        });
        
        visiblePickupCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllPickup.checked;
        });
        updateSubtotal();
        checkMixedSelection();
        
        // Apply smart filtering after select all
        if (this.checked) {
            applySmartFilter();
        }
    });
    
    selectAllDelivery.addEventListener('change', function() {
        if (this.checked) {
            // Check if pickup items are already selected
            const pickupChecked = document.querySelectorAll('.pickup-checkbox:checked').length > 0;
            if (pickupChecked) {
                showConfirmation('⚠️ Mixed Selection Warning: You cannot mix Pickup and Delivery products in the same order. Please uncheck pickup items first.', true);
                this.checked = false;
                return;
            }
            
            // Warn user about Select All limitation
            showConfirmation('⚠️ Select All Notice: When all delivery items are selected, you can only delete them (not checkout) due to potentially incompatible delivery days. Select specific items for checkout.', true);
        }
        
        const visibleDeliveryCheckboxes = Array.from(deliveryCheckboxes).filter(cb => {
            const row = cb.closest('tr');
            return row && row.style.display !== 'none';
        });
        
        visibleDeliveryCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllDelivery.checked;
        });
        updateSubtotal();
        checkMixedSelection();
        
        // Apply smart filtering after select all
        if (this.checked) {
            applySmartFilter(); // This function will now handle both pickup and delivery
        }
    });
    
    // Setup individual checkbox listeners
    const allItemCheckboxes = document.querySelectorAll('.item-checkbox');
    allItemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Check if this would create a mixed selection
                if (!preventMixedSelection(this)) {
                    return; // Checkbox was unchecked by preventMixedSelection
                }
            }
            updateSubtotal();
            updateSectionCheckboxes();
            checkMixedSelection();
            
            // Apply smart filtering for both pickup and delivery items
            if (this.classList.contains('pickup-checkbox') || this.classList.contains('delivery-checkbox')) {
                applySmartFilter();
            }
        });
    });

    // Setup confirmation modal button
    document.getElementById('confirmRemoveBtn').addEventListener('click', function() {
        if (currentCartId) {
            removeItem(currentCartId);
            closeConfirmationModal();
        }
    });
    
    // Initialize subtotal
    updateSubtotal();
});

function updateSectionCheckboxes() {
    const pickupCheckboxes = document.querySelectorAll('.pickup-checkbox');
    const deliveryCheckboxes = document.querySelectorAll('.delivery-checkbox');
    const selectAllPickup = document.getElementById('selectAllPickup');
    const selectAllDelivery = document.getElementById('selectAllDelivery');
    
    const checkedPickup = document.querySelectorAll('.pickup-checkbox:checked');
    const checkedDelivery = document.querySelectorAll('.delivery-checkbox:checked');
    
    selectAllPickup.checked = checkedPickup.length === pickupCheckboxes.length && pickupCheckboxes.length > 0;
    selectAllDelivery.checked = checkedDelivery.length === deliveryCheckboxes.length && deliveryCheckboxes.length > 0;
}

function updateSubtotal() {
    let subtotal = 0;
    const selectedCartIds = [];
    
    document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
        const row = checkbox.closest('tr');
        const price = parseFloat(row.dataset.price);
        const quantity = parseInt(row.dataset.quantity);
        subtotal += price * quantity;
        selectedCartIds.push(checkbox.value);
    });
    
    document.getElementById('displaySubtotal').textContent = subtotal.toFixed(2);
    document.getElementById('subtotalInput').value = subtotal;
    document.getElementById('cartItemsInput').value = selectedCartIds.join(',');
}

function validateCart() {
    // Check if terms are accepted
    if (!document.getElementById('termsCheckbox').checked) {
        showConfirmation('Please accept the Terms and Conditions', true);
        return false;
    }
    
    // Check if any item is selected
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    if (selectedItems.length === 0) {
        showConfirmation('Please select at least one item to checkout', true);
        return false;
    }

    // Check for mixed selection
    if (checkMixedSelection()) {
        showConfirmation('You cannot mix Pickup and Delivery products in the same order. Please select items from only one category.', true);
        return false;
    }

    // Check stock availability for selected items only
    let hasInsufficientStock = false;
    selectedItems.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const quantity = parseInt(row.dataset.quantity);
        const stock = parseInt(row.dataset.stock);
        if (quantity > stock) {
            hasInsufficientStock = true;
            showConfirmation(`Insufficient stock for ${row.querySelector('td:nth-child(3)').textContent}. Available: ${stock}`, true);
        }
    });

    if (hasInsufficientStock) {
        return false;
    }

    // Check day compatibility for both pickup and delivery items
    const selectedPickupItems = Array.from(selectedItems).filter(checkbox => 
        checkbox.classList.contains('pickup-checkbox')
    );
    const selectedDeliveryItems = Array.from(selectedItems).filter(checkbox => 
        checkbox.classList.contains('delivery-checkbox')
    );
    
    // Check pickup day compatibility
    if (selectedPickupItems.length > 1) {
        const pickupDaysList = selectedPickupItems.map(checkbox => {
            const row = checkbox.closest('tr');
            return row.dataset.days || '';
        }).filter(days => days);
        
        if (pickupDaysList.length > 1) {
            const commonDays = getCommonDays(pickupDaysList);
            if (commonDays.length === 0) {
                showConfirmation('⚠️ Pickup Day Conflict: The selected pickup items have no common pickup days. Please select items that can be picked up on the same day.', true);
                return false;
            }
        }
    }
    
    // Check delivery day compatibility
    if (selectedDeliveryItems.length > 1) {
        const deliveryDaysList = selectedDeliveryItems.map(checkbox => {
            const row = checkbox.closest('tr');
            return row.dataset.days || '';
        }).filter(days => days);
        
        if (deliveryDaysList.length > 1) {
            const commonDays = getCommonDays(deliveryDaysList);
            if (commonDays.length === 0) {
                showConfirmation('⚠️ Delivery Day Conflict: The selected delivery items have no common delivery days. Please select items that can be delivered on the same day.', true);
                return false;
            }
        }
    }
    
    // Check if Select All was used for pickup (prevent checkout)
    const selectAllPickup = document.getElementById('selectAllPickup');
    if (selectAllPickup && selectAllPickup.checked && selectedPickupItems.length > 1) {
        showConfirmation('⚠️ Select All Limitation: You cannot checkout when all pickup items are selected due to potential day conflicts. Please select specific compatible items.', true);
        return false;
    }
    
    // Check if Select All was used for delivery (prevent checkout)
    const selectAllDelivery = document.getElementById('selectAllDelivery');
    if (selectAllDelivery && selectAllDelivery.checked && selectedDeliveryItems.length > 1) {
        showConfirmation('⚠️ Select All Limitation: You cannot checkout when all delivery items are selected due to potential day conflicts. Please select specific compatible items.', true);
        return false;
    }
    
    return true;
}

function updateQuantity(cartId, newQuantity) {
    if (newQuantity < 1) return;

    const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
    const stock = parseInt(row.dataset.stock);
    
    if (newQuantity > stock) {
        showConfirmation(`Cannot exceed available stock of ${stock}`, true);
        return;
    }

    fetch("update-cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else showConfirmation("Error: " + (data.error || "Failed to update quantity"), true);
    })
    .catch(err => {
        console.error("Error:", err);
        showConfirmation("An error occurred while updating the cart", true);
    });
}

function removeItem(cartId) {
    fetch("remove-from-cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `cart_id=${cartId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showConfirmation("Item removed successfully");
            setTimeout(() => location.reload(), 1000);
        }
        else showConfirmation("Error: " + data.error, true);
    })
    .catch(err => {
        console.error("Error:", err);
        showConfirmation("An error occurred while removing the item", true);
    });
}

// Update the remove button click handler in the table
document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.onclick = function() {
        const cartId = this.closest('tr').dataset.cartId;
        showConfirmationModal(cartId);
    };
});

// Function to filter items by selected day
function filterByDay(sectionType) {
    const filterId = sectionType === 'pickup' ? 'pickupDaysFilter' : 'daysFilter';
    const selectedDay = document.getElementById(filterId).value;
    const table = document.querySelector(`.${sectionType}-table`);
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    let visibleRows = 0;
    
    rows.forEach(row => {
        const availableDays = row.dataset.days || '';
        
        if (selectedDay === '' || availableDays.includes(selectedDay)) {
            row.style.display = '';
            visibleRows++;
        } else {
            row.style.display = 'none';
            // Uncheck the checkbox if the row is hidden
            const checkbox = row.querySelector('.item-checkbox');
            if (checkbox && checkbox.checked) {
                checkbox.checked = false;
                updateSubtotal();
            }
        }
    });
    
    // Update the "Select All" checkbox state
    updateSelectAllState();
    
    // Show/hide empty message if needed
    const emptyMessage = table.parentNode.querySelector('.empty-message');
    if (visibleRows === 0 && !emptyMessage) {
        const message = document.createElement('div');
        message.className = 'empty-message';
        message.style.cssText = 'text-align: center; padding: 20px; color: #666; font-style: italic;';
        const sectionText = sectionType === 'pickup' ? 'pickup' : 'delivery';
        message.textContent = selectedDay ? `No ${sectionText} items available for ${selectedDay}` : `No ${sectionText} items in cart`;
        table.parentNode.appendChild(message);
    } else if (visibleRows > 0 && emptyMessage) {
        emptyMessage.remove();
    }
}

// Update selectAllInSection to only select visible rows
function selectAllInSection(section) {
    const sectionCheckbox = document.getElementById(`selectAll${section.charAt(0).toUpperCase() + section.slice(1)}`);
    const checkboxes = document.querySelectorAll(`.${section}-checkbox`);
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        // Only select checkboxes in visible rows
        if (row && row.style.display !== 'none') {
            checkbox.checked = sectionCheckbox.checked;
        }
    });
    
    updateSubtotal();
}

// Update the select all state based on visible items only
function updateSelectAllState() {
    const pickupCheckboxes = Array.from(document.querySelectorAll('.pickup-checkbox')).filter(cb => {
        const row = cb.closest('tr');
        return row && row.style.display !== 'none';
    });
    
    const deliveryCheckboxes = Array.from(document.querySelectorAll('.delivery-checkbox')).filter(cb => {
        const row = cb.closest('tr');
        return row && row.style.display !== 'none';
    });
    
    const selectAllPickup = document.getElementById('selectAllPickup');
    const selectAllDelivery = document.getElementById('selectAllDelivery');
    
    if (selectAllPickup && pickupCheckboxes.length > 0) {
        const checkedCount = pickupCheckboxes.filter(cb => cb.checked).length;
        selectAllPickup.checked = checkedCount === pickupCheckboxes.length;
        selectAllPickup.indeterminate = checkedCount > 0 && checkedCount < pickupCheckboxes.length;
    }
    
    if (selectAllDelivery && deliveryCheckboxes.length > 0) {
        const checkedCount = deliveryCheckboxes.filter(cb => cb.checked).length;
        selectAllDelivery.checked = checkedCount === deliveryCheckboxes.length;
        selectAllDelivery.indeterminate = checkedCount > 0 && checkedCount < deliveryCheckboxes.length;
    }
}

// Function to get common days between multiple products
function getCommonDays(daysList) {
    if (daysList.length === 0) return [];
    if (daysList.length === 1) {
        // Return the single day string as an array
        return daysList[0].split(', ').map(day => day.trim()).filter(day => day);
    }
    
    // Convert day strings to arrays
    const dayArrays = daysList.map(days => 
        days.split(', ').map(day => day.trim()).filter(day => day)
    );
    
    // Find intersection of all day arrays
    return dayArrays.reduce((common, current) => 
        common.filter(day => current.includes(day))
    );
}

// Function to check if two products have common days
function hasCommonDays(days1, days2) {
    if (!days1 || !days2) return false;
    
    const array1 = days1.split(', ').map(day => day.trim()).filter(day => day);
    const array2 = days2.split(', ').map(day => day.trim()).filter(day => day);
    
    return array1.some(day => array2.includes(day));
}

// Smart filtering based on selected products (both pickup and delivery)
function applySmartFilter() {
    const pickupTable = document.querySelector('.pickup-table');
    const deliveryTable = document.querySelector('.delivery-table');

    if (!pickupTable && !deliveryTable) return;

    // Handle pickup table filtering
    if (pickupTable) {
        const pickupRows = pickupTable.querySelectorAll('tbody tr');
        const selectedPickupRows = Array.from(pickupRows).filter(row => {
            const checkbox = row.querySelector('.pickup-checkbox');
            return checkbox && checkbox.checked;
        });
        
        if (selectedPickupRows.length > 0) {
            const pickupDaysList = selectedPickupRows.map(row => row.dataset.days || '').filter(days => days);
            const commonPickupDays = pickupDaysList.length > 0 ? getCommonDays(pickupDaysList) : [];
            
            pickupRows.forEach(row => {
                const checkbox = row.querySelector('.pickup-checkbox');
                const rowDays = row.dataset.days || '';
                
                // Always show selected rows
                if (checkbox && checkbox.checked) {
                    row.style.display = '';
                    row.classList.remove('filtered-out');
                    return;
                }
                
                // For unselected rows, check if they have any common days
                if (commonPickupDays.length === 0 || hasCommonDays(rowDays, commonPickupDays.join(', '))) {
                    row.style.display = '';
                    row.classList.remove('filtered-out');
                } else {
                    row.style.display = 'none';
                    row.classList.add('filtered-out');
                }
            });
            

        } else {
            // If no pickup items selected, show all pickup items
            pickupRows.forEach(row => {
                row.style.display = '';
                row.classList.remove('filtered-out');
            });

        }
    }

    // Handle delivery table filtering
    if (deliveryTable) {
        const deliveryRows = deliveryTable.querySelectorAll('tbody tr');
        const selectedDeliveryRows = Array.from(deliveryRows).filter(row => {
            const checkbox = row.querySelector('.delivery-checkbox');
            return checkbox && checkbox.checked;
        });
        
        if (selectedDeliveryRows.length > 0) {
            const deliveryDaysList = selectedDeliveryRows.map(row => row.dataset.days || '').filter(days => days);
            const commonDeliveryDays = deliveryDaysList.length > 0 ? getCommonDays(deliveryDaysList) : [];
            
            deliveryRows.forEach(row => {
                const checkbox = row.querySelector('.delivery-checkbox');
                const rowDays = row.dataset.days || '';
                
                // Always show selected rows
                if (checkbox && checkbox.checked) {
                    row.style.display = '';
                    row.classList.remove('filtered-out');
                    return;
                }
                
                // For unselected rows, check if they have any common days
                if (commonDeliveryDays.length === 0 || hasCommonDays(rowDays, commonDeliveryDays.join(', '))) {
                    row.style.display = '';
                    row.classList.remove('filtered-out');
                } else {
                    row.style.display = 'none';
                    row.classList.add('filtered-out');
                }
            });
            

        } else {
            // If no delivery items selected, show all delivery items
            deliveryRows.forEach(row => {
                row.style.display = '';
                row.classList.remove('filtered-out');
            });

        }
    }
    
    // Update select all state
    updateSelectAllState();
}
</script>
</body>
</html>